SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES';
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;

START TRANSACTION;

-- erlaube NULL fuer YearApplied Feld in Opus_Patent
ALTER TABLE `document_patents` MODIFY COLUMN `year_applied` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of the application.';

-- if standardized enrichment_key_names from bibtex-upload are already used set them to a temporary name
UPDATE `document_enrichments` SET `key_name` = 'TempBibtexRecord' WHERE `key_name` = 'BibtexRecord';
UPDATE `enrichmentkeys`SET `name`= 'TempBibtexRecord' WHERE `name` = 'BibtexRecord';
INSERT INTO `enrichmentkeys` (`name`) VALUES ('BibtexRecord');

COMMIT;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;
