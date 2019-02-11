START TRANSACTION;

-- Add columns to table enrichmentkeys to support enrichment types

ALTER TABLE `enrichmentkeys`
  ADD COLUMN `type`    VARCHAR(255) NULL COMMENT 'Name of enrichment type.',
  ADD COLUMN `options` VARCHAR(255) NULL COMMENT 'Options to allow configuration of enrichment type.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (11);

COMMIT;