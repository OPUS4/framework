START TRANSACTION;

-- Add additional values to field PublicationState

ALTER TABLE `documents`
    MODIFY COLUMN `publication_state` ENUM(
        'draft', 'acceptedVersion', 'submittedVersion', 'publishedVersion', 'updatedVersion',
        'proof', 'authorsVersion', 'correctedVersion', 'enhancedVersion'
    ) COMMENT 'Version of publication.';

UPDATE `documents` SET `publication_state` = 'enhancedVersion' WHERE `publication_state` = 'updatedVersion';

ALTER TABLE `documents`
    MODIFY COLUMN `publication_state` ENUM(
        'draft', 'authorsVersion', 'submittedVersion', 'acceptedVersion', 'proof', 'publishedVersion',
        'correctedVersion', 'enhancedVersion'
        ) COMMENT 'Version of publication.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (23);

COMMIT;
