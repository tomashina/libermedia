-- Sidrene cijene - 04/04: read-only preflight i provjera migracije
--
-- Ova datoteka sadrzi iskljucivo SELECT upite i ne mijenja podatke.
-- Pokrenuti cijelu datoteku nakon koraka 01-03, prije prvog produkcijskog crona.
-- Prvi blok istodobno dokumentira preflight pretpostavke izvornog skupa.
--
-- Ocekivani kljucni rezultati:
--   * required source/target tables: PRESENT
--   * nonzero_tax_class_products: 0
--   * products_without_store_mapping: 0 (ili svjesno objasnjen izuzetak)
--   * missing_anchor_rows / orphan_anchor_rows: 0
--   * svi *_mismatch brojevi: 0 neposredno nakon backfilla
--   * tocno 1 extension red, 6 aktivnih eventa i po 1 trazeni setting
--   * Administrator access_permission i modify_permission: OK
--   * cron kljuc se ne ispisuje; provjeravaju se samo duljina i format
--   * prazni EAN/JAN/ISBN podaci su dopusteni; svaki upisani GTIN mora biti valjan

-- A. Okolina i potrebne tablice.
SELECT
  VERSION() AS `mysql_version`,
  DATABASE() AS `database_name`,
  NOW() AS `checked_at`;

SELECT
  required.`table_name`,
  CASE WHEN actual.`table_name` IS NULL THEN 'MISSING' ELSE 'PRESENT' END AS `table_status`
FROM (
  SELECT 'oc_product' AS `table_name`
  UNION ALL SELECT 'oc_product_to_store'
  UNION ALL SELECT 'oc_product_description'
  UNION ALL SELECT 'oc_manufacturer'
  UNION ALL SELECT 'oc_language'
  UNION ALL SELECT 'oc_setting'
  UNION ALL SELECT 'oc_extension'
  UNION ALL SELECT 'oc_event'
  UNION ALL SELECT 'oc_user_group'
  UNION ALL SELECT 'oc_anchor_price'
  UNION ALL SELECT 'oc_anchor_price_audit'
  UNION ALL SELECT 'oc_anchor_price_publication'
) required
LEFT JOIN `information_schema`.`tables` actual
  ON actual.`table_schema` = DATABASE()
 AND actual.`table_name` = required.`table_name`
ORDER BY required.`table_name`;

-- B. Izvorni podaci i potvrda backfill pretpostavki.
SELECT
  COUNT(*) AS `total_products`,
  SUM(CASE WHEN p.`tax_class_id` <> 0 THEN 1 ELSE 0 END) AS `nonzero_tax_class_products`,
  SUM(CASE WHEN YEAR(p.`date_added`) = 0 THEN 1 ELSE 0 END) AS `zero_date_added_products`,
  MIN(p.`date_added`) AS `oldest_date_added`,
  MAX(p.`date_added`) AS `newest_date_added`
FROM `oc_product` p;

SELECT
  COUNT(*) AS `product_store_rows`,
  COUNT(DISTINCT p2s.`product_id`) AS `mapped_products`,
  COUNT(DISTINCT p2s.`store_id`) AS `mapped_stores`
FROM `oc_product_to_store` p2s;

SELECT
  COUNT(*) AS `products_without_store_mapping`
FROM `oc_product` p
LEFT JOIN `oc_product_to_store` p2s
  ON p2s.`product_id` = p.`product_id`
WHERE p2s.`product_id` IS NULL;

SELECT
  COUNT(*) AS `store_mappings_without_product`
FROM `oc_product_to_store` p2s
LEFT JOIN `oc_product` p
  ON p.`product_id` = p2s.`product_id`
WHERE p.`product_id` IS NULL;

SELECT
  CASE
    WHEN DATE(p.`date_added`) > '2026-09-10' THEN 'first_listing'
    ELSE 'baseline_2026_09_10'
  END AS `expected_rule_code`,
  COUNT(*) AS `expected_anchor_rows`
FROM `oc_product` p
INNER JOIN `oc_product_to_store` p2s
  ON p2s.`product_id` = p.`product_id`
WHERE p.`date_added` < '2026-09-11 00:00:00'
   OR (p.`status` = 1 AND p.`date_available` <= CURDATE())
GROUP BY `expected_rule_code`
ORDER BY `expected_rule_code`;

-- C. Stvarna shema i indeksi (usporediti sa 01_schema).
SELECT
  c.`table_name`,
  c.`ordinal_position`,
  c.`column_name`,
  c.`column_type`,
  c.`is_nullable`,
  c.`column_default`,
  c.`extra`
FROM `information_schema`.`columns` c
WHERE c.`table_schema` = DATABASE()
  AND c.`table_name` IN (
    'oc_anchor_price',
    'oc_anchor_price_audit',
    'oc_anchor_price_publication'
  )
ORDER BY c.`table_name`, c.`ordinal_position`;

SELECT
  s.`table_name`,
  s.`index_name`,
  s.`non_unique`,
  GROUP_CONCAT(s.`column_name` ORDER BY s.`seq_in_index` SEPARATOR ',') AS `indexed_columns`
FROM `information_schema`.`statistics` s
WHERE s.`table_schema` = DATABASE()
  AND s.`table_name` IN (
    'oc_anchor_price',
    'oc_anchor_price_audit',
    'oc_anchor_price_publication'
  )
GROUP BY s.`table_name`, s.`index_name`, s.`non_unique`
ORDER BY s.`table_name`, s.`index_name`;

SELECT
  COUNT(*) AS `batch_key_columns`,
  MAX(c.`column_type`) AS `column_type`,
  MAX(c.`is_nullable`) AS `is_nullable`,
  MAX(c.`column_default`) AS `column_default`,
  CASE
    WHEN COUNT(*) = 1
     AND MAX(c.`column_type` = 'char(32)') = 1
     AND MAX(c.`is_nullable` = 'NO') = 1
     AND MAX(COALESCE(c.`column_default`, '<NULL>') = '') = 1
    THEN 'OK' ELSE 'CHECK'
  END AS `result`
FROM `information_schema`.`columns` c
WHERE c.`table_schema` = DATABASE()
  AND c.`table_name` = 'oc_anchor_price_publication'
  AND c.`column_name` = 'batch_key';

SELECT
  COUNT(*) AS `batch_key_index_columns`,
  CASE
    WHEN COUNT(*) = 1
     AND MAX(s.`column_name` = 'batch_key' AND s.`seq_in_index` = 1) = 1
     AND MAX(s.`non_unique`) = 1
    THEN 'OK' ELSE 'CHECK'
  END AS `result`
FROM `information_schema`.`statistics` s
WHERE s.`table_schema` = DATABASE()
  AND s.`table_name` = 'oc_anchor_price_publication'
  AND s.`index_name` = 'batch_key';

SELECT
  t.`table_name`,
  t.`engine`,
  CASE WHEN UPPER(t.`engine`) = 'INNODB' THEN 'OK' ELSE 'CHECK' END AS `result`
FROM `information_schema`.`tables` t
WHERE t.`table_schema` = DATABASE()
  AND t.`table_name` IN (
    'oc_anchor_price',
    'oc_anchor_price_audit',
    'oc_anchor_price_publication'
  )
ORDER BY t.`table_name`;

SELECT
  expected.`index_name`,
  actual.`non_unique`,
  actual.`indexed_columns`,
  CASE
    WHEN actual.`index_name` IS NOT NULL
     AND actual.`non_unique` = expected.`expected_non_unique`
     AND actual.`indexed_columns` = expected.`expected_columns`
    THEN 'OK'
    ELSE 'CHECK'
  END AS `result`
FROM (
  SELECT
    'store_location_sequence' AS `index_name`,
    0 AS `expected_non_unique`,
    'store_id,location_code,sequence_no' AS `expected_columns`
  UNION ALL SELECT
    'status_published',
    1,
    'status,published_at'
) expected
LEFT JOIN (
  SELECT
    s.`index_name`,
    s.`non_unique`,
    GROUP_CONCAT(s.`column_name` ORDER BY s.`seq_in_index` SEPARATOR ',') AS `indexed_columns`
  FROM `information_schema`.`statistics` s
  WHERE s.`table_schema` = DATABASE()
    AND s.`table_name` = 'oc_anchor_price_publication'
    AND s.`index_name` IN ('store_location_sequence', 'status_published')
  GROUP BY s.`index_name`, s.`non_unique`
) actual
  ON actual.`index_name` = expected.`index_name`
ORDER BY expected.`index_name`;

-- D. Registracija modula i postavke. Cron tajna se namjerno ne prikazuje.
SELECT
  COUNT(*) AS `extension_rows`,
  CASE WHEN COUNT(*) = 1 THEN 'OK' ELSE 'CHECK' END AS `result`
FROM `oc_extension`
WHERE `type` = 'module'
  AND `code` = 'anchor_price';

SELECT
  expected.`setting_key`,
  expected.`expected_value`,
  COUNT(actual.`setting_id`) AS `matching_rows`,
  COALESCE(SUM(actual.`value` = expected.`expected_value`), 0) AS `rows_with_expected_value`,
  COALESCE(SUM(actual.`serialized` = 0), 0) AS `rows_read_as_plain_text`,
  CASE
    WHEN COUNT(actual.`setting_id`) = 1
     AND COALESCE(SUM(actual.`value` = expected.`expected_value`), 0) = 1
     AND COALESCE(SUM(actual.`serialized` = 0), 0) = 1
    THEN 'OK'
    ELSE 'CHECK'
  END AS `result`
FROM (
  SELECT 'module_anchor_price_status' AS `setting_key`, '1' AS `expected_value`
  UNION ALL SELECT 'module_anchor_price_reference_date', '2026-09-10'
  UNION ALL SELECT 'module_anchor_price_default_unit', 'kom'
) expected
LEFT JOIN `oc_setting` actual
  ON actual.`store_id` = 0
 AND actual.`code` = 'module_anchor_price'
 AND actual.`key` = expected.`setting_key`
GROUP BY expected.`setting_key`, expected.`expected_value`
ORDER BY expected.`setting_key`;

SELECT
  COUNT(*) AS `cron_setting_rows`,
  COALESCE(SUM(CHAR_LENGTH(`value`) = 64), 0) AS `rows_with_64_char_key`,
  COALESCE(SUM(`value` REGEXP '^[0-9a-f]{64}$'), 0) AS `rows_with_safe_hex_format`,
  COALESCE(SUM(`serialized` = 0), 0) AS `rows_read_as_plain_text`,
  CASE
    WHEN COUNT(*) = 1
     AND COALESCE(SUM(CHAR_LENGTH(`value`) = 64), 0) = 1
     AND COALESCE(SUM(`value` REGEXP '^[0-9a-f]{64}$'), 0) = 1
     AND COALESCE(SUM(`serialized` = 0), 0) = 1
    THEN 'OK'
    ELSE 'CHECK'
  END AS `result`
FROM `oc_setting`
WHERE `store_id` = 0
  AND `code` = 'module_anchor_price'
  AND `key` = 'module_anchor_price_cron_key';

-- E. Sest trazenih eventa mora postojati tocno jednom i biti aktivno.
SELECT
  desired.`trigger`,
  desired.`action`,
  COUNT(actual.`event_id`) AS `matching_rows`,
  COALESCE(SUM(actual.`status` = 1), 0) AS `active_rows`,
  COALESCE(SUM(actual.`sort_order` = 0), 0) AS `rows_with_expected_sort_order`,
  CASE
    WHEN COUNT(actual.`event_id`) = 1
     AND COALESCE(SUM(actual.`status` = 1), 0) = 1
     AND COALESCE(SUM(actual.`sort_order` = 0), 0) = 1
    THEN 'OK'
    ELSE 'CHECK'
  END AS `result`
FROM (
  SELECT
    'admin/model/catalog/product/addProduct/after' AS `trigger`,
    'extension/module/anchor_price/captureProduct' AS `action`
  UNION ALL SELECT
    'admin/model/catalog/product/editProduct/after',
    'extension/module/anchor_price/captureProduct'
  UNION ALL SELECT
    'admin/model/extension/module/product_quick_edit/quickEditProduct/after',
    'extension/module/anchor_price/captureQuickEdit'
  UNION ALL SELECT
    'catalog/model/module/oc_model/addProduct/after',
    'extension/module/anchor_price/captureProduct'
  UNION ALL SELECT
    'catalog/model/module/oc_model/editProduct/after',
    'extension/module/anchor_price/captureProduct'
  UNION ALL SELECT
    'catalog/view/*/before',
    'extension/module/anchor_price/beforeView'
) desired
LEFT JOIN `oc_event` actual
  ON actual.`code` = 'anchor_price'
 AND actual.`trigger` = desired.`trigger`
 AND actual.`action` = desired.`action`
GROUP BY desired.`trigger`, desired.`action`
ORDER BY desired.`trigger`;

SELECT
  COUNT(*) AS `anchor_price_event_rows`,
  CASE WHEN COUNT(*) = 6 THEN 'OK' ELSE 'CHECK' END AS `result`
FROM `oc_event`
WHERE `code` = 'anchor_price';

-- F. Administrator mora imati access i modify za novu administratorsku rutu.
-- Nevaljani permission JSON prikazuje se zasebno, bez pokusaja parsiranja.
SELECT
  ug.`user_group_id`,
  ug.`name`,
  JSON_VALID(ug.`permission`) AS `permission_json_valid`,
  CASE
    WHEN JSON_TYPE(IF(JSON_VALID(ug.`permission`), ug.`permission`, '{}')) <> 'OBJECT' THEN 'CHECK'
    WHEN JSON_TYPE(JSON_EXTRACT(
      IF(JSON_VALID(ug.`permission`), ug.`permission`, '{}'),
      '$.access'
    )) NOT IN ('ARRAY', 'OBJECT') THEN 'CHECK'
    WHEN JSON_SEARCH(
      JSON_EXTRACT(IF(JSON_VALID(ug.`permission`), ug.`permission`, '{}'), '$.access'),
      'one',
      'extension/module/anchor\\_price',
      '\\'
    ) IS NULL THEN 'CHECK'
    ELSE 'OK'
  END AS `access_permission`,
  CASE
    WHEN JSON_TYPE(IF(JSON_VALID(ug.`permission`), ug.`permission`, '{}')) <> 'OBJECT' THEN 'CHECK'
    WHEN JSON_TYPE(JSON_EXTRACT(
      IF(JSON_VALID(ug.`permission`), ug.`permission`, '{}'),
      '$.modify'
    )) NOT IN ('ARRAY', 'OBJECT') THEN 'CHECK'
    WHEN JSON_SEARCH(
      JSON_EXTRACT(IF(JSON_VALID(ug.`permission`), ug.`permission`, '{}'), '$.modify'),
      'one',
      'extension/module/anchor\\_price',
      '\\'
    ) IS NULL THEN 'CHECK'
    ELSE 'OK'
  END AS `modify_permission`
FROM `oc_user_group` ug
WHERE (ug.`user_group_id` = 1 OR LOWER(TRIM(ug.`name`)) IN ('administrator', 'admin'))
  AND JSON_VALID(ug.`permission`)
ORDER BY ug.`user_group_id`;

SELECT
  ug.`user_group_id`,
  ug.`name`,
  'CHECK: permission is not valid JSON; activation left it unchanged' AS `result`
FROM `oc_user_group` ug
WHERE (ug.`user_group_id` = 1 OR LOWER(TRIM(ug.`name`)) IN ('administrator', 'admin'))
  AND COALESCE(JSON_VALID(ug.`permission`), 0) = 0
ORDER BY ug.`user_group_id`;

SELECT
  COUNT(*) AS `administrator_target_groups`,
  SUM(JSON_VALID(ug.`permission`)) AS `valid_permission_json_groups`,
  CASE WHEN COUNT(*) >= 1 THEN 'OK' ELSE 'CHECK: target group not found' END AS `result`
FROM `oc_user_group` ug
WHERE ug.`user_group_id` = 1
   OR LOWER(TRIM(ug.`name`)) IN ('administrator', 'admin');

-- G. Potpunost backfilla i provjera pravila datuma/cijene.
SELECT
  (
    SELECT COUNT(*)
    FROM `oc_product` p
    INNER JOIN `oc_product_to_store` p2s
      ON p2s.`product_id` = p.`product_id`
    WHERE p.`date_added` < '2026-09-11 00:00:00'
       OR (p.`status` = 1 AND p.`date_available` <= CURDATE())
  ) AS `expected_product_store_rows`,
  (SELECT COUNT(*) FROM `oc_anchor_price`) AS `actual_anchor_rows`,
  (
    SELECT COUNT(*)
    FROM `oc_product` p
    INNER JOIN `oc_product_to_store` p2s
      ON p2s.`product_id` = p.`product_id`
    LEFT JOIN `oc_anchor_price` ap
      ON ap.`product_id` = p2s.`product_id`
     AND ap.`store_id` = p2s.`store_id`
    WHERE (p.`date_added` < '2026-09-11 00:00:00'
       OR (p.`status` = 1 AND p.`date_available` <= CURDATE()))
      AND ap.`anchor_price_id` IS NULL
  ) AS `missing_anchor_rows`,
  (
    SELECT COUNT(*)
    FROM `oc_anchor_price` ap
    LEFT JOIN `oc_product_to_store` p2s
      ON p2s.`product_id` = ap.`product_id`
     AND p2s.`store_id` = ap.`store_id`
    WHERE p2s.`product_id` IS NULL
  ) AS `orphan_anchor_rows`;

SELECT
  COUNT(*) AS `checked_anchor_rows`,
  SUM(
    CASE
      WHEN ap.`rule_code` = 'baseline_2026_09_10'
       AND ap.`reference_date` <> '2026-09-10'
      THEN 1
      WHEN ap.`rule_code` = 'first_listing'
       AND (ap.`reference_date` <= '2026-09-10' OR ap.`reference_date` > CURDATE())
	  THEN 1
	  WHEN DATE(p.`date_added`) > '2026-09-10'
	   AND (ap.`rule_code` <> 'first_listing' OR ap.`reference_date` <= '2026-09-10')
      THEN 1 ELSE 0
    END
  ) AS `reference_date_mismatch`,
  SUM(
    CASE
      WHEN ap.`rule_code` NOT IN ('baseline_2026_09_10', 'first_listing')
      THEN 1 ELSE 0
    END
  ) AS `rule_code_mismatch`,
  SUM(CASE WHEN ap.`source` = 'migration_backfill_2026_09_10' AND ap.`price` <> p.`price` THEN 1 ELSE 0 END) AS `net_price_mismatch`,
  SUM(CASE WHEN ap.`source` = 'migration_backfill_2026_09_10' AND ap.`gross_price` <> p.`price` THEN 1 ELSE 0 END) AS `gross_price_mismatch`,
  SUM(CASE WHEN ap.`tax_class_id` <> p.`tax_class_id` THEN 1 ELSE 0 END) AS `tax_class_mismatch`,
  SUM(CASE WHEN p.`status` = 1 AND p.`date_available` <= CURDATE() AND ap.`verification_status` <> 'confirmed' THEN 1 ELSE 0 END) AS `active_not_confirmed_rows`,
  SUM(CASE WHEN CHAR_LENGTH(ap.`currency_code`) <> 3 THEN 1 ELSE 0 END) AS `invalid_currency_rows`
FROM `oc_anchor_price` ap
INNER JOIN `oc_product` p
  ON p.`product_id` = ap.`product_id`;

SELECT
  ap.`store_id`,
  ap.`rule_code`,
  ap.`verification_status`,
  COUNT(*) AS `anchor_rows`,
  MIN(ap.`reference_date`) AS `first_reference_date`,
  MAX(ap.`reference_date`) AS `last_reference_date`
FROM `oc_anchor_price` ap
GROUP BY ap.`store_id`, ap.`rule_code`, ap.`verification_status`
ORDER BY ap.`store_id`, ap.`rule_code`, ap.`verification_status`;

SELECT
  COUNT(*) AS `anchor_rows_without_create_audit`
FROM `oc_anchor_price` ap
LEFT JOIN `oc_anchor_price_audit` audit
  ON audit.`anchor_price_id` = ap.`anchor_price_id`
 AND audit.`action` = 'create'
WHERE audit.`audit_id` IS NULL;

-- Post-cutover zapisi iz početnog backfilla ostaju pending jer date_added nije
-- dovoljan dokaz stvarnog prvog javnog listanja. Svaki red treba ručno provjeriti,
-- po potrebi ispraviti u admin modulu i potvrditi prije prve objave.
SELECT
  ap.`anchor_price_id`,
  ap.`product_id`,
  pd.`name`,
  p.`date_added` AS `candidate_first_listing_at`,
  ap.`reference_date`,
  ap.`verification_status`
FROM `oc_anchor_price` ap
INNER JOIN `oc_product` p
  ON p.`product_id` = ap.`product_id`
LEFT JOIN `oc_product_description` pd
  ON pd.`product_id` = p.`product_id`
 AND pd.`language_id` = (
   SELECT l.`language_id`
   FROM `oc_setting` s
   INNER JOIN `oc_language` l
     ON l.`code` = s.`value`
   WHERE s.`store_id` = 0
     AND s.`key` = 'config_language'
   ORDER BY s.`setting_id` DESC
   LIMIT 1
 )
WHERE ap.`rule_code` = 'first_listing'
  AND ap.`verification_status` <> 'confirmed'
	AND p.`status` = 1
	AND p.`date_available` <= CURDATE()
ORDER BY ap.`reference_date`, ap.`product_id`;

-- Objava se mora zaustaviti ako ijedan aktivan/listan proizvod nema potvrđenu
-- sidrenu cijenu ili obvezni naziv, šifru i marku. EAN/JAN/ISBN nisu obvezni,
-- ali svaka upisana vrijednost mora biti valjani GTIN-8, GTIN-12, GTIN-13 ili
-- GTIN-14, uključujući ispravnu kontrolnu znamenku. Očekivani rezultat je 0.
WITH
`active_product_data` AS (
  SELECT
    p.`product_id`,
    IF(
      ap.`anchor_price_id` IS NULL
      OR ap.`verification_status` <> 'confirmed'
      OR pd.`product_id` IS NULL
      OR TRIM(pd.`name`) = ''
      OR TRIM(p.`model`) = ''
      OR m.`manufacturer_id` IS NULL
      OR TRIM(m.`name`) = '',
      1,
      0
    ) AS `missing_required_data`,
    NULLIF(TRIM(p.`ean`), '') AS `ean`,
    NULLIF(TRIM(p.`jan`), '') AS `jan`,
    NULLIF(TRIM(p.`isbn`), '') AS `isbn`
  FROM `oc_product` p
  INNER JOIN `oc_product_to_store` p2s
    ON p2s.`product_id` = p.`product_id`
   AND p2s.`store_id` = 0
  LEFT JOIN `oc_anchor_price` ap
    ON ap.`product_id` = p.`product_id`
   AND ap.`store_id` = p2s.`store_id`
  LEFT JOIN `oc_product_description` pd
    ON pd.`product_id` = p.`product_id`
   AND pd.`language_id` = (
     SELECT l.`language_id`
     FROM `oc_setting` s
     INNER JOIN `oc_language` l
       ON l.`code` = s.`value`
     WHERE s.`store_id` = 0
       AND s.`key` = 'config_language'
     ORDER BY s.`setting_id` DESC
     LIMIT 1
   )
  LEFT JOIN `oc_manufacturer` m
    ON m.`manufacturer_id` = p.`manufacturer_id`
  WHERE p.`status` = 1
    AND p.`date_available` <= CURDATE()
),
`barcode_candidates` AS (
  SELECT `product_id`, 'EAN' AS `barcode_field`, `ean` AS `barcode`
  FROM `active_product_data`
  WHERE `ean` IS NOT NULL
  UNION ALL
  SELECT `product_id`, 'JAN' AS `barcode_field`, `jan` AS `barcode`
  FROM `active_product_data`
  WHERE `jan` IS NOT NULL
  UNION ALL
  SELECT `product_id`, 'ISBN' AS `barcode_field`, `isbn` AS `barcode`
  FROM `active_product_data`
  WHERE `isbn` IS NOT NULL
),
`candidate_checks` AS (
  SELECT
    candidate.`product_id`,
    candidate.`barcode_field`,
    candidate.`barcode`,
    COALESCE(SUM(
      CAST(SUBSTRING(candidate.`barcode`, digit.`position`, 1) AS UNSIGNED)
      * IF(MOD(CHAR_LENGTH(candidate.`barcode`) - digit.`position`, 2) = 0, 1, 3)
    ), 0) AS `gtin_weighted_sum`
  FROM `barcode_candidates` candidate
  LEFT JOIN (
    SELECT 1 AS `position` UNION ALL SELECT 2 UNION ALL SELECT 3
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
    UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
    UNION ALL SELECT 13
  ) digit
    ON digit.`position` < CHAR_LENGTH(candidate.`barcode`)
  GROUP BY candidate.`product_id`, candidate.`barcode_field`, candidate.`barcode`
),
`product_checks` AS (
  SELECT
    `product_id`,
    MAX(
      CASE
        WHEN `barcode` REGEXP '^[0-9]{8}$|^[0-9]{12,14}$'
         AND MOD(10 - MOD(`gtin_weighted_sum`, 10), 10)
           = CAST(RIGHT(`barcode`, 1) AS UNSIGNED)
        THEN 0 ELSE 1
      END
    ) AS `has_invalid_gtin`
  FROM `candidate_checks`
  GROUP BY `product_id`
)
SELECT COUNT(*) AS `active_products_not_ready_for_publication`
FROM `active_product_data` data
LEFT JOIN `product_checks` checked
  ON checked.`product_id` = data.`product_id`
WHERE data.`missing_required_data` = 1
   OR COALESCE(checked.`has_invalid_gtin`, 0) = 1;

-- Ovaj popis mora biti prazan prije produkcijske objave. Prikazuje samo upisane
-- EAN/JAN/ISBN vrijednosti koje su pogrešno oblikovane ili imaju nevaljanu
-- kontrolnu znamenku. Potpuno prazni EAN/JAN/ISBN podaci su dopušteni. UPC se u
-- ovoj trgovini koristi kao GLS zastavica, a MPN je oznaka proizvođača, pa se
-- namjerno ne tretiraju kao barkod.
WITH
`active_barcode_data` AS (
  SELECT
    p.`product_id`,
    pd.`name`,
    p.`model`,
    p.`sku`,
    NULLIF(TRIM(p.`ean`), '') AS `ean`,
    NULLIF(TRIM(p.`jan`), '') AS `jan`,
    NULLIF(TRIM(p.`isbn`), '') AS `isbn`
  FROM `oc_product` p
  INNER JOIN `oc_product_to_store` p2s
    ON p2s.`product_id` = p.`product_id`
   AND p2s.`store_id` = 0
  LEFT JOIN `oc_product_description` pd
    ON pd.`product_id` = p.`product_id`
   AND pd.`language_id` = (
     SELECT l.`language_id`
     FROM `oc_setting` s
     INNER JOIN `oc_language` l
       ON l.`code` = s.`value`
     WHERE s.`store_id` = 0
       AND s.`key` = 'config_language'
     ORDER BY s.`setting_id` DESC
     LIMIT 1
   )
  WHERE p.`status` = 1
    AND p.`date_available` <= CURDATE()
),
`barcode_candidates` AS (
  SELECT `product_id`, 'EAN' AS `barcode_field`, `ean` AS `barcode`
  FROM `active_barcode_data`
  WHERE `ean` IS NOT NULL
  UNION ALL
  SELECT `product_id`, 'JAN' AS `barcode_field`, `jan` AS `barcode`
  FROM `active_barcode_data`
  WHERE `jan` IS NOT NULL
  UNION ALL
  SELECT `product_id`, 'ISBN' AS `barcode_field`, `isbn` AS `barcode`
  FROM `active_barcode_data`
  WHERE `isbn` IS NOT NULL
),
`candidate_checks` AS (
  SELECT
    candidate.`product_id`,
    candidate.`barcode_field`,
    candidate.`barcode`,
    COALESCE(SUM(
      CAST(SUBSTRING(candidate.`barcode`, digit.`position`, 1) AS UNSIGNED)
      * IF(MOD(CHAR_LENGTH(candidate.`barcode`) - digit.`position`, 2) = 0, 1, 3)
    ), 0) AS `gtin_weighted_sum`
  FROM `barcode_candidates` candidate
  LEFT JOIN (
    SELECT 1 AS `position` UNION ALL SELECT 2 UNION ALL SELECT 3
    UNION ALL SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6
    UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9
    UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
    UNION ALL SELECT 13
  ) digit
    ON digit.`position` < CHAR_LENGTH(candidate.`barcode`)
  GROUP BY candidate.`product_id`, candidate.`barcode_field`, candidate.`barcode`
)
SELECT
  data.`product_id`,
  data.`name`,
  data.`model`,
  data.`sku`,
  checked.`barcode_field`,
  checked.`barcode` AS `invalid_gtin`
FROM `candidate_checks` checked
INNER JOIN `active_barcode_data` data
  ON data.`product_id` = checked.`product_id`
WHERE checked.`barcode` NOT REGEXP '^[0-9]{8}$|^[0-9]{12,14}$'
   OR MOD(10 - MOD(checked.`gtin_weighted_sum`, 10), 10)
        <> CAST(RIGHT(checked.`barcode`, 1) AS UNSIGNED)
ORDER BY data.`product_id`, checked.`barcode_field`;

-- H. Publikacije PJ1/PJ3. Prije prvog crona prvi upit legitimno vraca 0 redaka.
SELECT
  `location_code`,
  `status`,
  COUNT(*) AS `publication_rows`,
  MAX(`published_at`) AS `last_published_at`
FROM `oc_anchor_price_publication`
GROUP BY `location_code`, `status`
ORDER BY `location_code`, `status`;

SELECT
  COUNT(*) AS `invalid_location_rows`
FROM `oc_anchor_price_publication`
WHERE `location_code` NOT IN ('PJ1', 'PJ3');

SELECT
  COUNT(*) AS `published_rows_without_batch_key`
FROM `oc_anchor_price_publication`
WHERE `status` = 'published'
  AND (`batch_key` = '' OR `batch_key` NOT REGEXP '^[0-9a-f]{32}$');

-- Svaki batch kljuc smije pripadati samo jednom atomskom PJ1/PJ3 paru s istim
-- punim vremenom objave i jednakim brojem artikala. Retke s result=CHECK istraziti.
SELECT
  p.`store_id`,
  p.`batch_key`,
  MIN(p.`published_at`) AS `published_at`,
  COUNT(DISTINCT p.`published_at`) AS `distinct_publication_times`,
  COUNT(DISTINCT DATE(p.`published_at`)) AS `distinct_publication_dates`,
  SUM(p.`location_code` = 'PJ1') AS `pj1_published_rows`,
  SUM(p.`location_code` = 'PJ3') AS `pj3_published_rows`,
  MAX(CASE WHEN p.`location_code` = 'PJ1' THEN p.`product_count` END) AS `pj1_product_count`,
  MAX(CASE WHEN p.`location_code` = 'PJ3' THEN p.`product_count` END) AS `pj3_product_count`,
  CASE
    WHEN p.`batch_key` <> ''
     AND COUNT(*) = 2
     AND SUM(p.`location_code` = 'PJ1') = 1
     AND SUM(p.`location_code` = 'PJ3') = 1
     AND COUNT(DISTINCT p.`published_at`) = 1
     AND COUNT(DISTINCT DATE(p.`published_at`)) = 1
     AND MAX(CASE WHEN p.`location_code` = 'PJ1' THEN p.`product_count` END)
       = MAX(CASE WHEN p.`location_code` = 'PJ3' THEN p.`product_count` END)
    THEN 'OK'
    ELSE 'CHECK'
  END AS `result`
FROM `oc_anchor_price_publication` p
WHERE p.`status` = 'published'
GROUP BY p.`store_id`, p.`batch_key`
ORDER BY `published_at` DESC, p.`store_id`;
