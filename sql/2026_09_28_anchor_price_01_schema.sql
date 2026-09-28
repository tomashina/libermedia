-- Sidrene cijene - 01/04: shema podataka
--
-- Redoslijed pokretanja:
--   1. ova datoteka
--   2. 2026_09_28_anchor_price_02_activation.sql
--   3. 2026_09_28_anchor_price_03_backfill.sql
--   4. 2026_09_28_anchor_price_04_verify_read_only.sql
--
-- Skripta je idempotentna: postojece tablice i podaci se ne brisu niti
-- prepisuju. Ako tablica istog imena vec postoji s drugacijom shemom,
-- pokrenuti read-only provjeru iz koraka 4 i rucno razrijesiti razliku.

CREATE TABLE IF NOT EXISTS `oc_anchor_price` (
  `anchor_price_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `store_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `price` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
  `gross_price` DECIMAL(15,4) NOT NULL DEFAULT '0.0000',
  `currency_code` CHAR(3) NOT NULL DEFAULT 'EUR',
  `tax_class_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `tax_context` TEXT NOT NULL,
  `reference_date` DATE NOT NULL,
  `rule_code` VARCHAR(32) NOT NULL,
  `source` VARCHAR(32) NOT NULL,
  `verification_status` VARCHAR(20) NOT NULL DEFAULT 'confirmed',
  `created_by` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `date_added` DATETIME NOT NULL,
  `date_modified` DATETIME NOT NULL,
  PRIMARY KEY (`anchor_price_id`),
  UNIQUE KEY `product_store` (`product_id`, `store_id`),
  KEY `reference_date` (`reference_date`),
  KEY `verification_status` (`verification_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `oc_anchor_price_audit` (
  `audit_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `anchor_price_id` INT(11) UNSIGNED NOT NULL,
  `product_id` INT(11) UNSIGNED NOT NULL,
  `store_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `user_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `action` VARCHAR(32) NOT NULL,
  `old_data` MEDIUMTEXT NOT NULL,
  `new_data` MEDIUMTEXT NOT NULL,
  `reason` VARCHAR(255) NOT NULL,
  `date_added` DATETIME NOT NULL,
  PRIMARY KEY (`audit_id`),
  KEY `anchor_price_id` (`anchor_price_id`),
  KEY `product_store` (`product_id`, `store_id`),
  KEY `date_added` (`date_added`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

CREATE TABLE IF NOT EXISTS `oc_anchor_price_publication` (
  `publication_id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `store_id` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `batch_key` CHAR(32) NOT NULL DEFAULT '',
  `location_code` VARCHAR(16) NOT NULL,
  `sequence_no` INT(11) UNSIGNED NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  `relative_path` VARCHAR(255) NOT NULL,
  `status` VARCHAR(20) NOT NULL,
  `product_count` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `checksum_sha256` CHAR(64) NOT NULL DEFAULT '',
  `error_message` TEXT NULL,
  `created_by` INT(11) UNSIGNED NOT NULL DEFAULT '0',
  `published_at` DATETIME NULL,
  `date_added` DATETIME NOT NULL,
  PRIMARY KEY (`publication_id`),
  UNIQUE KEY `store_location_sequence` (`store_id`, `location_code`, `sequence_no`),
  KEY `batch_key` (`batch_key`),
  KEY `status_published` (`status`, `published_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

-- Idempotentni upgrade za razvojne/ranije instalacije koje su tablicu već
-- stvorile prije uvođenja atomske PJ1/PJ3 batch objave.
SET @anchor_batch_column_exists := (
  SELECT COUNT(*)
  FROM `information_schema`.`columns`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `column_name` = 'batch_key'
);
SET @anchor_batch_column_sql := IF(
  @anchor_batch_column_exists = 0,
  'ALTER TABLE `oc_anchor_price_publication` ADD `batch_key` CHAR(32) NOT NULL DEFAULT '''' AFTER `store_id`',
  'SELECT 1'
);
PREPARE anchor_batch_column_statement FROM @anchor_batch_column_sql;
EXECUTE anchor_batch_column_statement;
DEALLOCATE PREPARE anchor_batch_column_statement;

-- Ispravi definiciju samo ako je razvojna verzija već imala batch_key drugog
-- tipa. Time ponovljeno pokretanje ne zaključava ispravnu produkcijsku tablicu.
SET @anchor_batch_column_valid := (
  SELECT CASE
    WHEN COUNT(*) = 1
     AND MAX(`column_type` = 'char(32)') = 1
     AND MAX(`is_nullable` = 'NO') = 1
     AND MAX(COALESCE(`column_default`, '<NULL>') = '') = 1
    THEN 1 ELSE 0
  END
  FROM `information_schema`.`columns`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `column_name` = 'batch_key'
);
SET @anchor_batch_column_fix_sql := IF(
  @anchor_batch_column_valid = 0,
  'ALTER TABLE `oc_anchor_price_publication` MODIFY `batch_key` CHAR(32) NOT NULL DEFAULT ''''',
  'SELECT 1'
);
PREPARE anchor_batch_column_fix_statement FROM @anchor_batch_column_fix_sql;
EXECUTE anchor_batch_column_fix_statement;
DEALLOCATE PREPARE anchor_batch_column_fix_statement;

SET @anchor_batch_index_valid := (
  SELECT CASE
    WHEN COUNT(*) = 1
     AND MAX(`column_name` = 'batch_key' AND `seq_in_index` = 1) = 1
     AND MAX(`non_unique`) = 1
    THEN 1 ELSE 0
  END
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `index_name` = 'batch_key'
);
SET @anchor_batch_index_named := (
  SELECT COUNT(*)
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `index_name` = 'batch_key'
);
SET @anchor_batch_index_drop_sql := IF(
  @anchor_batch_index_named > 0 AND @anchor_batch_index_valid = 0,
  'ALTER TABLE `oc_anchor_price_publication` DROP INDEX `batch_key`',
  'SELECT 1'
);
PREPARE anchor_batch_index_drop_statement FROM @anchor_batch_index_drop_sql;
EXECUTE anchor_batch_index_drop_statement;
DEALLOCATE PREPARE anchor_batch_index_drop_statement;

SET @anchor_batch_index_sql := IF(
  @anchor_batch_index_valid = 0,
  'ALTER TABLE `oc_anchor_price_publication` ADD KEY `batch_key` (`batch_key`)',
  'SELECT 1'
);
PREPARE anchor_batch_index_statement FROM @anchor_batch_index_sql;
EXECUTE anchor_batch_index_statement;
DEALLOCATE PREPARE anchor_batch_index_statement;

SET @anchor_sequence_index_valid := (
  SELECT CASE
    WHEN COUNT(*) = 3
     AND GROUP_CONCAT(`column_name` ORDER BY `seq_in_index` SEPARATOR ',') = 'store_id,location_code,sequence_no'
     AND MAX(`non_unique`) = 0
    THEN 1 ELSE 0
  END
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `index_name` = 'store_location_sequence'
);
SET @anchor_sequence_index_named := (
  SELECT COUNT(*)
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `index_name` = 'store_location_sequence'
);
SET @anchor_sequence_index_drop_sql := IF(
  @anchor_sequence_index_named > 0 AND @anchor_sequence_index_valid = 0,
  'ALTER TABLE `oc_anchor_price_publication` DROP INDEX `store_location_sequence`',
  'SELECT 1'
);
PREPARE anchor_sequence_index_drop_statement FROM @anchor_sequence_index_drop_sql;
EXECUTE anchor_sequence_index_drop_statement;
DEALLOCATE PREPARE anchor_sequence_index_drop_statement;
SET @anchor_sequence_index_add_sql := IF(
  @anchor_sequence_index_valid = 0,
  'ALTER TABLE `oc_anchor_price_publication` ADD UNIQUE KEY `store_location_sequence` (`store_id`, `location_code`, `sequence_no`)',
  'SELECT 1'
);
PREPARE anchor_sequence_index_add_statement FROM @anchor_sequence_index_add_sql;
EXECUTE anchor_sequence_index_add_statement;
DEALLOCATE PREPARE anchor_sequence_index_add_statement;

SET @anchor_status_index_valid := (
  SELECT CASE
    WHEN COUNT(*) = 2
     AND GROUP_CONCAT(`column_name` ORDER BY `seq_in_index` SEPARATOR ',') = 'status,published_at'
     AND MAX(`non_unique`) = 1
    THEN 1 ELSE 0
  END
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `index_name` = 'status_published'
);
SET @anchor_status_index_named := (
  SELECT COUNT(*)
  FROM `information_schema`.`statistics`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
    AND `index_name` = 'status_published'
);
SET @anchor_status_index_drop_sql := IF(
  @anchor_status_index_named > 0 AND @anchor_status_index_valid = 0,
  'ALTER TABLE `oc_anchor_price_publication` DROP INDEX `status_published`',
  'SELECT 1'
);
PREPARE anchor_status_index_drop_statement FROM @anchor_status_index_drop_sql;
EXECUTE anchor_status_index_drop_statement;
DEALLOCATE PREPARE anchor_status_index_drop_statement;
SET @anchor_status_index_add_sql := IF(
  @anchor_status_index_valid = 0,
  'ALTER TABLE `oc_anchor_price_publication` ADD KEY `status_published` (`status`, `published_at`)',
  'SELECT 1'
);
PREPARE anchor_status_index_add_statement FROM @anchor_status_index_add_sql;
EXECUTE anchor_status_index_add_statement;
DEALLOCATE PREPARE anchor_status_index_add_statement;

SET @anchor_publication_engine := (
  SELECT `engine`
  FROM `information_schema`.`tables`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_publication'
  LIMIT 1
);
SET @anchor_publication_engine_sql := IF(
  UPPER(COALESCE(@anchor_publication_engine, '')) <> 'INNODB',
  'ALTER TABLE `oc_anchor_price_publication` ENGINE=InnoDB',
  'SELECT 1'
);
PREPARE anchor_publication_engine_statement FROM @anchor_publication_engine_sql;
EXECUTE anchor_publication_engine_statement;
DEALLOCATE PREPARE anchor_publication_engine_statement;

-- Revizijski trag i sidrene cijene moraju biti transakcijski zajedno. Starije
-- razvojne instalacije mogle su naslijediti zadani MyISAM engine poslužitelja.
SET @anchor_price_engine := (
  SELECT `engine`
  FROM `information_schema`.`tables`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price'
  LIMIT 1
);
SET @anchor_price_engine_sql := IF(
  UPPER(COALESCE(@anchor_price_engine, '')) <> 'INNODB',
  'ALTER TABLE `oc_anchor_price` ENGINE=InnoDB',
  'SELECT 1'
);
PREPARE anchor_price_engine_statement FROM @anchor_price_engine_sql;
EXECUTE anchor_price_engine_statement;
DEALLOCATE PREPARE anchor_price_engine_statement;

SET @anchor_audit_engine := (
  SELECT `engine`
  FROM `information_schema`.`tables`
  WHERE `table_schema` = DATABASE()
    AND `table_name` = 'oc_anchor_price_audit'
  LIMIT 1
);
SET @anchor_audit_engine_sql := IF(
  UPPER(COALESCE(@anchor_audit_engine, '')) <> 'INNODB',
  'ALTER TABLE `oc_anchor_price_audit` ENGINE=InnoDB',
  'SELECT 1'
);
PREPARE anchor_audit_engine_statement FROM @anchor_audit_engine_sql;
EXECUTE anchor_audit_engine_statement;
DEALLOCATE PREPARE anchor_audit_engine_statement;
