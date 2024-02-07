START TRANSACTION;

-- Replace identifier type ENUM with VARCHAR to allow any type

ALTER TABLE `documents`
    MODIFY COLUMN `publication_state` ENUM('draft', 'acceptedVersion', 'submittedVersion', 'publishedVersion', 'updatedVersion') COMMENT 'Version of publication.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (22);

COMMIT;
