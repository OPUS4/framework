START TRANSACTION;

-- Increase size of enrichment options for longer select lists

ALTER TABLE `enrichmentkeys`
    MODIFY COLUMN `options` VARCHAR(15000) NULL COMMENT 'Configuration of enrichment type';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (20);

COMMIT;
