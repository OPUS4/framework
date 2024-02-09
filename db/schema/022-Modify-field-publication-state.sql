START TRANSACTION;

-- Replace identifier type ENUM with VARCHAR to allow any type

ALTER TABLE `documents`
    MODIFY COLUMN `publication_state` ENUM(
        'draft', 'accepted', 'submitted', 'published', 'updated',
        'acceptedVersion', 'submittedVersion', 'publishedVersion', 'updatedVersion') COMMENT 'Version of publication.';

UPDATE `documents` SET `publication_state` = 'acceptedVersion' WHERE `publication_state` = 'accepted';
UPDATE `documents` SET `publication_state` = 'submittedVersion' WHERE `publication_state` = 'submitted';
UPDATE `documents` SET `publication_state` = 'publishedVersion' WHERE `publication_state` = 'published';
UPDATE `documents` SET `publication_state` = 'updatedVersion' WHERE `publication_state` = 'updated';

ALTER TABLE `documents`
    MODIFY COLUMN `publication_state` ENUM(
        'draft', 'acceptedVersion', 'submittedVersion', 'publishedVersion', 'updatedVersion'
    ) COMMENT 'Version of publication.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (22);

COMMIT;
