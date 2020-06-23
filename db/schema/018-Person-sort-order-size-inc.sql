START TRANSACTION;

-- Increase size of sort_order field for persons, so more than 255 authors are supported (OPUSVIER-4257)

ALTER TABLE `link_persons_documents`
    MODIFY COLUMN `sort_order` SMALLINT UNSIGNED NOT NULL COMMENT 'Sort order of the persons related to the document.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (18);

COMMIT;
