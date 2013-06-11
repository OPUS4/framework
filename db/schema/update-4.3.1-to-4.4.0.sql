--
-- this file was left intentionally empty since no database changes need to be made
--

-- Erlaube NULL fuer YearApplied Feld in Opus_Patent
ALTER TABLE `documents`
    MODIFY COLUMN `year_applied` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of the application.';

-- if standardized enrichment_key_names from bibtex-upload are already used set them to a temporary name

UPDATE `document_enrichments` SET `key_name` = 'TempBibtexRecord' WHERE `key_name` = 'BibtexRecord';
UPDATE `enrichmentkeys`SET `name`= 'TempBibtexRecord' WHERE `name` = 'BibtexRecord';

INSERT INTO `enrichmentkeys` (`name`) VALUES
  ('BibtexRecord');

