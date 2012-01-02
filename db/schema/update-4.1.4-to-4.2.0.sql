-- -----------------------------------------------------
-- Table `document_series`
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_series` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` mediumtext NOT NULL COMMENT 'Title of document set (e.g. series)',
  `logo` text COMMENT 'Pfad zum Logo des Containers',
  `publisher` mediumtext COMMENT 'Name of Publisher',
  `issn` mediumtext COMMENT 'ISSN of that document set',
  `infobox` text COMMENT 'html-fähige Infobox',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


-- -----------------------------------------------------
-- Table `link_document_series`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `link_documents_series` (
  `document_id` int(10) unsigned NOT NULL,
  `series_id` int(10) unsigned NOT NULL,
  `number` varchar(20) NOT NULL COMMENT 'corresponding number (e.g. serial number)',
  PRIMARY KEY (`document_id`,`series_id`),
  UNIQUE KEY `document_id` (`document_id`,`number`),
  KEY `series_id` (`series_id`),
  CONSTRAINT `link_documents_series_ibfk_2` FOREIGN KEY (`series_id`) REFERENCES `document_series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `link_documents_series_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------
-- Table `enrichmentkeys`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `enrichmentkeys` (
  `name` VARCHAR(255) NOT NULL COMMENT 'The enrichment key.' ,
  PRIMARY KEY (`name`)
) ENGINE = InnoDB
COMMENT = 'Key table for database scheme enhancements.';

INSERT INTO `enrichmentkeys` (`name`) VALUES
  ('submitter.user_id'),
  ('reviewer.user_id'),
  ('review.rejected_by'),
  ('review.accepted_by'),
  ('LegalNotices');

-- Insert existing key_names from document_enrichments
INSERT IGNORE INTO enrichmentkeys (`name`) SELECT distinct key_name FROM `document_enrichments`;

-- -----------------------------------------------------
-- Table `documents` column `server_state` modified
-- -----------------------------------------------------
ALTER TABLE `documents`
    MODIFY COLUMN `server_state` ENUM('audited', 'published', 'restricted', 'inprogress', 'unpublished', 'deleted', 'temporary') NOT NULL COMMENT 'Status of publication process in the repository.';

-- -----------------------------------------------------
-- Table `documents` column `thesis_year_accepted` added
-- -----------------------------------------------------
ALTER TABLE `documents`
    ADD COLUMN `thesis_year_accepted` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of final exam (if exacat date is unknown).' AFTER `thesis_date_accepted`;

-- --------------------------------------------------------------------------------------------------
-- Table `document_enrichments` columns `key_name`,  `value` modified, foreign-key constraint added
-- --------------------------------------------------------------------------------------------------
ALTER TABLE `document_enrichments`
    MODIFY COLUMN `key_name` VARCHAR(255) NOT NULL COMMENT 'Foreign key to: enrichmentkeys.name.' ,
    MODIFY COLUMN `value` MEDIUMTEXT NOT NULL COMMENT 'Value of the enrichment.' ,
    ADD CONSTRAINT `fk_document_enrichment_enrichmentkeys`
        FOREIGN KEY (`key_name` )
        REFERENCES `enrichmentkeys` (`name` );

-- --------------------------------------------------------------------
-- Fill 'oai_subset' column with content of 'number' of all collections
-- where 'oai_subset' IS NULL (see ticket OPUSVIER-2116).
-- --------------------------------------------------------------------
UPDATE `collections` SET oai_subset = number WHERE oai_subset IS NULL AND number IS NOT NULL AND number != "";
