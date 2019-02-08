START TRANSACTION;

-- Reduce size of columns for utf8mb4 to work with MySQL 5.6 and older, that often use a lower limit
-- for key length.

ALTER TABLE `persons`
  MODIFY COLUMN `last_name` VARCHAR(191) NOT NULL COMMENT 'Last name.';

ALTER TABLE `user_roles`
  MODIFY COLUMN `name` VARCHAR(100) NOT NULL UNIQUE;

ALTER TABLE `access_modules`
  MODIFY COLUMN `module_name` VARCHAR(100) NOT NULL COMMENT 'Primary key and name of application module';

ALTER TABLE `dnb_institutes`
  MODIFY COLUMN `name` VARCHAR(191) NOT NULL,
  MODIFY COLUMN `department` varchar(191) DEFAULT NULL;

ALTER TABLE `collections_roles`
  MODIFY COLUMN `name` VARCHAR(191) NOT NULL
    COMMENT 'Name, label or type of the collection role, i.e. a specific classification or conference.',
  MODIFY COLUMN `oai_name` VARCHAR(191) NOT NULL
    COMMENT 'Shortname identifying role in oai context.';

ALTER TABLE `collections_enrichments`
  MODIFY COLUMN `key_name` VARCHAR(191) NOT NULL;

ALTER TABLE `document_licences`
  MODIFY COLUMN `name` VARCHAR(191) NULL UNIQUE COMMENT 'Short name of the licence as displayed to users.';

-- For column 'name' in table 'enrichmentkeys' the foreign key constraint has to be dropped before the key column
-- can be modified. Afterwards the constraint is restored.

ALTER TABLE `document_enrichments` DROP FOREIGN KEY `fk_document_enrichment_enrichmentkeys`;

ALTER TABLE `document_enrichments`
  MODIFY COLUMN `key_name` VARCHAR(191) NOT NULL COMMENT 'Foreign key to: enrichmentkeys.name.';

ALTER TABLE `enrichmentkeys`
  MODIFY COLUMN `name` VARCHAR(191) NOT NULL COMMENT 'The enrichment key.';

ALTER TABLE `document_enrichments`
  ADD CONSTRAINT `fk_document_enrichment_enrichmentkeys` FOREIGN KEY (`key_name`) REFERENCES `enrichmentkeys` (`name`);

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (10);

COMMIT;



