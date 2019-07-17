START TRANSACTION;

-- Add column language to table collection_roles

ALTER TABLE `collections_roles`
  ADD COLUMN `language` VARCHAR(2);

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (14);

COMMIT;
