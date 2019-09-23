START TRANSACTION;

-- Add two columns to document_identifiers for DOI support

ALTER TABLE `document_identifiers`
  ADD `status`          ENUM('registered', 'verified') NULL COMMENT 'DOI registration status',
  ADD `registration_ts` TIMESTAMP                      NULL COMMENT 'timestamp of DOI registration';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (9);

COMMIT;
