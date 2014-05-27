SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES';
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;

START TRANSACTION;

-- Allow null value for scope and type of language
ALTER TABLE `languages` MODIFY COLUMN `scope` ENUM('I', 'M', 'S') COMMENT 'I(ndividual), M(acrolanguage), S(pecial)';
ALTER TABLE `languages` MODIFY COLUMN `type` ENUM('A', 'C', 'E', 'H', 'L', 'S') COMMENT 'A(ncient), C(onstructed), E(xtinct), H(istorical), L(iving), S(pecial)';

ALTER TABLE `persons` ADD COLUMN `identifier_orcid` VARCHAR(50);
ALTER TABLE `persons` ADD COLUMN `identifier_gnd` VARCHAR(50);
ALTER TABLE `persons` ADD COLUMN `identifier_misc` VARCHAR(50);

ALTER TABLE `collections` DROP `sort_order`;

-- Add fields for OpenAire Compliance
ALTER TABLE `documents` ADD COLUMN `embargo_date` DATE NULL COMMENT 'Embargoed date of document';
ALTER TABLE `document_files` DROP COLUMN `embargo_date`;

-- Add columns for frontdoor sort order and upload tracing
ALTER TABLE document_files ADD COLUMN `server_date_submitted` VARCHAR(50) COMMENT 'Date of file upload';
ALTER TABLE document_files ADD COLUMN `sort_order` VARCHAR(50) COMMENT 'Sort order in frontdoor for multiple files';

-- Add column 'visible_publish' for collections
ALTER TABLE `collections` ADD COLUMN `visible_publish` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Show collection in publish form';

COMMIT;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

