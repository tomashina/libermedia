-- Retire legacy dual-location publications only after the first valid WEB
-- publication has been created and verified. Files are intentionally retained
-- in private storage for rollback; expired rows are not publicly downloadable.

START TRANSACTION;

UPDATE `oc_anchor_price_publication` AS legacy
INNER JOIN (
    SELECT COUNT(*) AS ready
    FROM `oc_anchor_price_publication`
    WHERE `store_id` = 0
      AND `location_code` = 'WEB'
      AND `status` = 'published'
      AND `product_count` > 0
      AND `checksum_sha256` REGEXP '^[0-9a-f]{64}$'
) AS web_publication ON web_publication.ready > 0
SET legacy.`status` = 'expired',
    legacy.`error_message` = 'Zamijenjeno jedinstvenim cjenikom web trgovine.'
WHERE legacy.`store_id` = 0
  AND legacy.`location_code` IN ('PJ1', 'PJ3')
  AND legacy.`status` = 'published';

COMMIT;
