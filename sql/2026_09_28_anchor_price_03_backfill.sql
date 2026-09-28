-- Sidrene cijene - 03/04: pocetno punjenje iz postojecih OpenCart cijena
--
-- Pokrenuti nakon 01_schema i 02_activation.
-- Skripta je idempotentna: INSERT IGNORE i UNIQUE(product_id, store_id)
-- cuvaju svaku vec postojecu sidrenu cijenu bez prepisivanja.
--
-- Potvrdena produkcijska pretpostavka:
--   * oc_product.tax_class_id = 0
--   * oc_product.price vec jest prikazana/bruto cijena
-- Zbog toga su price i gross_price namjerno jednaki, a tax_context je prazan
-- JSON popis. Ukljuceni su svi proizvodi koji su postojali na referentni dan te
-- aktivni/dostupni proizvodi nastali nakon njega. Za postojece post-cutover
-- artikle date_added je samo kandidat datuma prve objave, pa zapis ostaje
-- pending dok administrator ne potvrdi ili ispravi datum. Post-cutover draftovi
-- i buduci artikli namjerno se ne seedaju: njihov se stvarni datum prve objave
-- hvata tek pri aktivaciji kroz admin event.
--
-- PJ1 (fizicka trgovina) i PJ3 (web) koriste isti skup artikala i cijena.
-- Ne stvaraju se dvije sidrene stavke: obje dnevne publikacije citaju ovu istu
-- product/store evidenciju, a razlikuju se samo po location_code u publikaciji.

START TRANSACTION;

INSERT IGNORE INTO `oc_anchor_price` (
  `product_id`,
  `store_id`,
  `price`,
  `gross_price`,
  `currency_code`,
  `tax_class_id`,
  `tax_context`,
  `reference_date`,
  `rule_code`,
  `source`,
  `verification_status`,
  `created_by`,
  `date_added`,
  `date_modified`
)
SELECT
  p.`product_id`,
  p2s.`store_id`,
  p.`price`,
  p.`price`,
  COALESCE(
    (
      SELECT s_store.`value`
      FROM `oc_setting` s_store
      WHERE s_store.`store_id` = p2s.`store_id`
        AND s_store.`key` = 'config_currency'
      ORDER BY s_store.`setting_id` DESC
      LIMIT 1
    ),
    (
      SELECT s_default.`value`
      FROM `oc_setting` s_default
      WHERE s_default.`store_id` = 0
        AND s_default.`key` = 'config_currency'
      ORDER BY s_default.`setting_id` DESC
      LIMIT 1
    ),
    'EUR'
  ) AS `currency_code`,
  p.`tax_class_id`,
  '[]' AS `tax_context`,
  CASE
    WHEN DATE(p.`date_added`) > '2026-09-10' THEN DATE(p.`date_added`)
    ELSE '2026-09-10'
  END AS `reference_date`,
  CASE
    WHEN DATE(p.`date_added`) > '2026-09-10' THEN 'first_listing'
    ELSE 'baseline_2026_09_10'
  END AS `rule_code`,
  'migration_backfill_2026_09_10' AS `source`,
  CASE
	WHEN p.`status` <> 1 OR p.`date_available` > CURDATE() THEN 'pending'
    WHEN DATE(p.`date_added`) > '2026-09-10' THEN 'pending'
    ELSE 'confirmed'
  END AS `verification_status`,
  0 AS `created_by`,
  NOW() AS `date_added`,
  NOW() AS `date_modified`
FROM `oc_product` p
INNER JOIN `oc_product_to_store` p2s
  ON p2s.`product_id` = p.`product_id`
WHERE p.`date_added` < '2026-09-11 00:00:00'
   OR (p.`status` = 1 AND p.`date_available` <= CURDATE());

-- Audit trag početnog punjenja. Dodaju se samo zapisi koje je stvorila ova
-- migracija i koji još nemaju odgovarajući create audit, pa je blok idempotentan.
INSERT INTO `oc_anchor_price_audit` (
  `anchor_price_id`,
  `product_id`,
  `store_id`,
  `user_id`,
  `action`,
  `old_data`,
  `new_data`,
  `reason`,
  `date_added`
)
SELECT
  ap.`anchor_price_id`,
  ap.`product_id`,
  ap.`store_id`,
  0,
  'create',
  '{}',
  JSON_OBJECT(
    'price', ap.`price`,
    'gross_price', ap.`gross_price`,
    'currency_code', ap.`currency_code`,
    'tax_class_id', ap.`tax_class_id`,
    'tax_context', ap.`tax_context`,
    'reference_date', ap.`reference_date`,
    'rule_code', ap.`rule_code`,
    'source', ap.`source`,
    'verification_status', ap.`verification_status`
  ),
  'Automatic snapshot: migration_backfill_2026_09_10',
  NOW()
FROM `oc_anchor_price` ap
LEFT JOIN `oc_anchor_price_audit` audit
  ON audit.`anchor_price_id` = ap.`anchor_price_id`
 AND audit.`action` = 'create'
WHERE ap.`source` = 'migration_backfill_2026_09_10'
  AND audit.`audit_id` IS NULL;

-- Upgrade zaštita: ranija razvojna verzija mogla je automatski potvrditi
-- post-cutover datum bez dokaza stvarne prve objave ili potvrditi povijesni
-- proizvod koji trenutačno nije javno aktivan. Automatske, još neuređene zapise
-- vrati na pending. Ručno pregledani zapisi imaju source=admin i ostaju
-- netaknuti. Audit se upisuje prije promjene statusa u istoj transakciji.
INSERT INTO `oc_anchor_price_audit` (
  `anchor_price_id`,
  `product_id`,
  `store_id`,
  `user_id`,
  `action`,
  `old_data`,
  `new_data`,
  `reason`,
  `date_added`
)
SELECT
  ap.`anchor_price_id`,
  ap.`product_id`,
  ap.`store_id`,
  0,
  'status_review_required',
  JSON_OBJECT(
    'price', ap.`price`,
    'gross_price', ap.`gross_price`,
    'currency_code', ap.`currency_code`,
    'tax_class_id', ap.`tax_class_id`,
    'tax_context', ap.`tax_context`,
    'reference_date', ap.`reference_date`,
    'rule_code', ap.`rule_code`,
    'source', ap.`source`,
    'verification_status', 'confirmed'
  ),
  JSON_OBJECT(
    'price', ap.`price`,
    'gross_price', ap.`gross_price`,
    'currency_code', ap.`currency_code`,
    'tax_class_id', ap.`tax_class_id`,
    'tax_context', ap.`tax_context`,
    'reference_date', ap.`reference_date`,
    'rule_code', ap.`rule_code`,
    'source', ap.`source`,
    'verification_status', 'pending'
  ),
  'Automatic first-listing date requires manual verification',
  NOW()
FROM `oc_anchor_price` ap
LEFT JOIN `oc_product` p
  ON p.`product_id` = ap.`product_id`
WHERE (
    ap.`rule_code` = 'first_listing'
    OR p.`product_id` IS NULL
    OR p.`status` <> 1
    OR p.`date_available` > CURDATE()
  )
  AND ap.`verification_status` = 'confirmed'
  AND ap.`source` IN (
    'migration_backfill_2026_09_10',
    'install',
    'sync',
    'price_list_sync',
	'cron_sync',
	'product_event',
	'product_edit_event'
  );

UPDATE `oc_anchor_price` ap
LEFT JOIN `oc_product` p
  ON p.`product_id` = ap.`product_id`
SET ap.`verification_status` = 'pending',
    ap.`date_modified` = NOW()
WHERE (
    ap.`rule_code` = 'first_listing'
    OR p.`product_id` IS NULL
    OR p.`status` <> 1
    OR p.`date_available` > CURDATE()
  )
  AND ap.`verification_status` = 'confirmed'
  AND ap.`source` IN (
    'migration_backfill_2026_09_10',
    'install',
    'sync',
    'price_list_sync',
	'cron_sync',
	'product_event',
	'product_edit_event'
  );

COMMIT;
