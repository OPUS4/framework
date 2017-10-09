START TRANSACTION;

-- Add default values to some columns (OPUSVIER-2034)

ALTER TABLE `link_persons_documents`
  MODIFY `sort_order` TINYINT UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Sort order of the persons related to the document.';

ALTER TABLE `document_notes`
  MODIFY `visibility` ENUM('private', 'public') NOT NULL DEFAULT 'private'
  COMMENT 'Visibility: private, public to another document version.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (8);

COMMIT;

