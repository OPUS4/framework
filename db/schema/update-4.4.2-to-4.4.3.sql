SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES';
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;

START TRANSACTION;

-- Allow null value for scope and type of language
ALTER TABLE `languages` MODIFY COLUMN `scope` ENUM('I', 'M', 'S') COMMENT 'I(ndividual), M(acrolanguage), S(pecial)';
ALTER TABLE `languages` MODIFY COLUMN `type` ENUM('A', 'C', 'E', 'H', 'L', 'S') COMMENT 'A(ncient), C(onstructed), E(xtinct), H(istorical), L(iving), S(pecial)';

ALTER TABLE `persons` ADD COLUMN `identifier_orcid` VARCHAR(50);
ALTER TABLE `persons` ADD COLUMN `identifier_gndid` VARCHAR(50);
ALTER TABLE `persons` ADD COLUMN `identifier_misc` VARCHAR(50);

ALTER TABLE `collections` DROP `sort_order`;

-- Add title-source field to document
ALTER TABLE `document_title_abstracts` MODIFY COLUMN `type` ENUM('main','parent','abstract','sub','additional', 'source');

-- Add fields for OpenAire Compliance
ALTER TABLE documents
ADD COLUMN `dc_relation` VARCHAR(255) NULL COMMENT 'Project name and number',
ADD COLUMN `dc_rights` ENUM ('info:eu-repo/semantics/closedAccess', 'info:eu-repo/semantics/embargoedAccess', 'info:eu-repo/semantics/openAccess') NOT NULL COMMENT 'Access type of document',
ADD COLUMN `dc_date` DATE NULL COMMENT 'Embargoed date of document';

COMMIT;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

