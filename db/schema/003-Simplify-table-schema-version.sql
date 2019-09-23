START TRANSACTION;

-- Change table "schema_version" OPUSVIER-3775

ALTER TABLE `schema_version`
    MODIFY `version` INT UNSIGNED NOT NULL COMMENT 'Version number of schema.',
    DROP COLUMN `hash`,
    DROP COLUMN `revision`,
    DROP COLUMN `last_changed_date`,
    DROP COLUMN `author`;

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (3);

COMMIT;
