START TRANSACTION;

-- Add column "name" to table "document_licences" (OPUSVIER-3791)
-- Remove column "link_sign" from "document_licences" (OPUSVIER-1492)

ALTER TABLE `document_licences`
  ADD COLUMN `name` VARCHAR(191) NULL UNIQUE COMMENT 'Short name of the licence as displayed to users.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (5);

COMMIT;
