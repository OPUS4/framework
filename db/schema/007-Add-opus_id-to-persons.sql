START TRANSACTION;

-- Add internal identifier to persons (OPUSVIER-3806)

ALTER TABLE `persons`
  ADD COLUMN `opus_id` INT UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Internal person identifier.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (7);

COMMIT;

