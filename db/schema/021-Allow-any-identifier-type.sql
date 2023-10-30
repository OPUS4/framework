START TRANSACTION;

-- Replace identifier type ENUM with VARCHAR to allow any type

ALTER TABLE `document_identifiers`
    ADD COLUMN `typestr` VARCHAR(10) NULL COMMENT 'Type of identifier';

UPDATE `document_identifiers`
    SET `typestr` = CAST(`type` as CHAR);

ALTER TABLE `document_identifiers`
    DROP COLUMN `type`;

ALTER TABLE `document_identifiers`
    RENAME COLUMN `typestr` TO `type`;

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (21);

COMMIT;
