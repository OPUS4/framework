SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;

-- -----------------------------------------------------
-- Table to store schema versioning information
-- -----------------------------------------------------
DROP TABLE IF EXISTS `schema_version`;
CREATE TABLE `schema_version` (
  `last_changed_date` VARCHAR(100) ,
  `revision` VARCHAR(20) ,
  `author` VARCHAR(100)
)
  ENGINE = InnoDB
  COMMENT = 'Holds revision information from subversion properties.';
-- -----------------------------------------------------
-- Insert revision information
--
-- The values are generated through svn checkin.
-- Do not edit here.
-- -----------------------------------------------------
INSERT INTO `schema_version` (last_changed_date, revision, author) VALUES ('$LastChangedDate: 2014-07-23 13:17:09 +0200 (Wed, 23 Jul 2014) $', '$Rev: 13465 $', '$Author: schwidder $');

-- -----------------------------------------------------
-- Table `documents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `documents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `completed_date` VARCHAR(50) NULL COMMENT 'Date of completion of the publication.' ,
  `completed_year` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of completion of the publication, if the \"completed_date\" (exact date) is unknown.' ,
  `contributing_corporation` TEXT NULL COMMENT 'Contribution corporate body.' ,
  `creating_corporation` TEXT NULL COMMENT 'Creating corporate body.' ,
  `thesis_date_accepted` VARCHAR(50) NULL COMMENT 'Date of final exam (date of the doctoral graduation).' ,
  `thesis_year_accepted` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of final exam (if exacat date is unknown).' ,
  `type` VARCHAR(100) NOT NULL COMMENT 'Document type.' ,
  `edition` VARCHAR(255) NULL COMMENT 'Edition of a monograph.' ,
  `issue` VARCHAR(255) NULL COMMENT 'Issue.' ,
  `language` VARCHAR(255) NULL COMMENT 'Language(s) of the document.' ,
  `page_first` VARCHAR(255) NULL COMMENT 'First page of a publication.' ,
  `page_last` VARCHAR(255) NULL COMMENT 'Last page of a publication.' ,
  `page_number` VARCHAR(255) NULL COMMENT 'Total page numbers.' ,
  `publication_state` ENUM('draft', 'accepted', 'submitted', 'published', 'updated') NOT NULL COMMENT 'Version of publication.' ,
  `published_date` VARCHAR(50) NULL COMMENT 'Exact date of publication. Could differ from \"server_date_published\".' ,
  `published_year` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of the publication, if the \"published_date\" (exact date) is unknown.  Could differ from \"server_date_published\".' ,
  `publisher_name` VARCHAR(255) NOT NULL COMMENT 'Name of an external publisher, e.g. Springer' ,
  `publisher_place` VARCHAR(255) NULL COMMENT 'City/State of external publisher, e.g. Berlin' ,
  `server_date_created` VARCHAR(50) NOT NULL COMMENT 'Date of insertion into the database (is generated by the system).' ,
  `server_date_modified` VARCHAR(50) NULL COMMENT 'Last modification of the document (is generated by the system).' ,
  `server_date_published` VARCHAR(50) NULL COMMENT 'Date of publication on the repository (is generated by the system).' ,
  `server_date_deleted` VARCHAR(50) NULL COMMENT 'Date of deletion, if server_state = delete (is generated by the system).' ,
  `server_state` ENUM('audited', 'published', 'restricted', 'inprogress', 'unpublished', 'deleted', 'temporary') NOT NULL COMMENT 'Status of publication process in the repository.' ,
  `volume` VARCHAR(255) NULL COMMENT 'Volume.',
  `belongs_to_bibliography` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'States, if document will be part of the bibliography? (1=yes, 0=no).' ,
  `embargo_date` VARCHAR(50) NULL COMMENT 'Embargo date for document files',
  PRIMARY KEY (`id`) )
  ENGINE = InnoDB
  COMMENT = 'Document related data (monolingual, unreproducible colums).';


-- -----------------------------------------------------
-- Table `document_identifiers`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_identifiers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to: documents.documents_id.' ,
  `type` ENUM('doi', 'handle', 'urn', 'std-doi', 'url', 'cris-link', 'splash-url', 'isbn', 'issn', 'opus3-id', 'opac-id', 'uuid', 'serial', 'old', 'pmid', 'arxiv') NOT NULL COMMENT 'Type of the identifier.' ,
  `value` TEXT NOT NULL COMMENT 'Value of the identifier.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_document_identifiers_documents` (`document_id` ASC) ,
  INDEX `fk_document_identifiers_documents_type` (`document_id` ASC, `type` ASC) ,
  CONSTRAINT `fk_document_identifiers_documents`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table for identifiers  related to the document.';

-- -----------------------------------------------------
-- Table `document_files`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_files` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to: documents.documents_id.' ,
  `path_name` TEXT NOT NULL COMMENT 'File and path name.' ,
  `label` TEXT NOT NULL COMMENT 'Display text of the file.' ,
  `comment` TEXT NULL COMMENT 'Comment for a file.',
  `mime_type` VARCHAR(255) NOT NULL COMMENT 'Mime type of the file.' ,
  `language` VARCHAR(3) NULL COMMENT 'Language of the file.' ,
  `file_size` BIGINT UNSIGNED NOT NULL COMMENT 'File size in bytes.',
  `visible_in_frontdoor` BOOLEAN NOT NULL DEFAULT 1 COMMENT 'States, will be shown in the front door (1=yes, 0=no).' ,
  `visible_in_oai` BOOLEAN NOT NULL DEFAULT 1 COMMENT 'States, will be shown in the OAI-PMH output (1=yes, 0=no).' ,
  `server_date_submitted` VARCHAR(50) COMMENT 'Date of file upload',
  `sort_order` INTEGER NOT NULL DEFAULT 0 COMMENT 'Sort order in frontdoor for multiple files',
  PRIMARY KEY (`id`) ,
  INDEX `fk_document_files_documents` (`document_id` ASC) ,
  CONSTRAINT `fk_document_files_documents`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table for file related data.';


-- -----------------------------------------------------
-- Table `file_hashvalues`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `file_hashvalues` (
  `file_id` INT UNSIGNED NOT NULL ,
  `type` VARCHAR(50) NOT NULL COMMENT 'Type of the hash value.' ,
  `value` TEXT NOT NULL COMMENT 'Hash value.' ,
  PRIMARY KEY (`type`, `file_id`) ,
  INDEX `fk_file_hashvalues_document_files` (`file_id` ASC) ,
  CONSTRAINT `fk_file_hashvalues_document_files`
  FOREIGN KEY (`file_id` )
  REFERENCES `document_files` (`id` )
    ON DELETE CASCADE
    ON UPDATE NO ACTION)
  ENGINE = InnoDB
  COMMENT = 'Table for hash values.';


-- -----------------------------------------------------
-- Table `document_subjects`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_subjects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NULL COMMENT 'Foreign key to: documents.documents_id.' ,
  `language` VARCHAR(3) NULL COMMENT 'Language of the subject heading.' ,
  `type` VARCHAR(30) NULL COMMENT 'Subject type, i. e. a specific authority file.' ,
  `value` VARCHAR(255) NOT NULL COMMENT 'Value of the subject heading, i. e. text, notation etc.' ,
  `external_key` VARCHAR(255) NULL COMMENT 'Identifier for linking the subject heading to external systems such as authority files.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_document_subjects_documents` (`document_id` ASC) ,
  CONSTRAINT `fk_document_subjects_documents`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table for subject heading related data.';

-- -----------------------------------------------------
-- Table `document_series`
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_series` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` mediumtext NOT NULL COMMENT 'Title of document set (e.g. series)',
  `infobox` text COMMENT 'infobox (can contain HTML markup)',
  `visible` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'visibility state (defaults to visible)',
  `sort_order` INTEGER NOT NULL DEFAULT 0 COMMENT 'position in sorted order',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


-- -----------------------------------------------------
-- Table `link_documents_series`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `link_documents_series` (
  `document_id` int(10) unsigned NOT NULL,
  `series_id` int(10) unsigned NOT NULL,
  `number` varchar(20) NOT NULL COMMENT 'corresponding number (e.g. serial number)',
  `doc_sort_order` INTEGER NOT NULL DEFAULT 0 COMMENT 'sort key (determines ordering of documents in a series)',
  PRIMARY KEY (`document_id`, `series_id`),
  UNIQUE KEY (`series_id`, `number`),
  KEY (`series_id`),
  CONSTRAINT `link_documents_series_ibfk_2` FOREIGN KEY (`series_id`) REFERENCES `document_series` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `link_documents_series_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;


-- -----------------------------------------------------
-- Table `document_title_abstracts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_title_abstracts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NULL COMMENT 'Foreign key to: documents.documents_id.' ,
  `type` ENUM('main', 'parent', 'abstract', 'sub', 'additional') NOT NULL COMMENT 'Type of title or abstract.' ,
  `value` TEXT NOT NULL COMMENT 'Value of title or abstract.' ,
  `language` VARCHAR(3) NOT NULL COMMENT 'Language of the title or abstract.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_document_title_abstracts_documents` (`document_id` ASC) ,
  CONSTRAINT `fk_document_title_abstracts_documents`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table with title and abstract related data.';


-- -----------------------------------------------------
-- Table `persons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `persons` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `academic_title` VARCHAR(255) NULL COMMENT 'Academic title.' ,
  `date_of_birth` VARCHAR(50) NULL COMMENT 'Date of birth.' ,
  `email` VARCHAR(100) NULL COMMENT 'E-mail address.' ,
  `first_name` VARCHAR(255) NULL COMMENT 'First name.' ,
  `last_name` VARCHAR(255) NOT NULL COMMENT 'Last name.' ,
  `place_of_birth` VARCHAR(255) NULL COMMENT 'Place of birth.' ,
  `identifier_orcid` VARCHAR(50) NULL COMMENT 'orc-id' ,
  `identifier_gnd` VARCHAR(50) NULL COMMENT 'gnd id' ,
  `identifier_misc` VARCHAR(50) NULL COMMENT 'misc id' ,
  PRIMARY KEY (`id`) ,
  INDEX `last_name` (`last_name` ASC) )
  ENGINE = InnoDB
  COMMENT = 'Person related data.';


-- -----------------------------------------------------
-- Table `link_persons_documents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `link_persons_documents` (
  `person_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: persons.persons_id.' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: documents.documents_id.' ,
  `role` ENUM('advisor', 'author', 'contributor', 'editor', 'referee',  'other', 'translator', 'owner', 'submitter') NOT NULL COMMENT 'Role of the person in the actual document-person context.' ,
  `sort_order` TINYINT UNSIGNED NOT NULL COMMENT 'Sort order of the persons related to the document.' ,
  `allow_email_contact` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Is e-mail contact in the actual document-person context allowed? (1=yes, 0=no).' ,
  INDEX `fk_link_documents_persons_persons` (`person_id` ASC) ,
  PRIMARY KEY (`person_id`, `document_id`, `role`) ,
  INDEX `fk_link_persons_documents_documents` (`document_id` ASC) ,
  CONSTRAINT `fk_link_documents_persons_persons`
  FOREIGN KEY (`person_id` )
  REFERENCES `persons` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_link_persons_documents_documents`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Relation table (documents, persons).';


-- -----------------------------------------------------
-- Table `document_patents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_patents` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to: documents.documents_id.' ,
  `countries` TEXT NOT NULL COMMENT 'Countries in which the patent was granted.' ,
  `date_granted` VARCHAR(50) NULL COMMENT 'Date when the patent was granted.' ,
  `number` VARCHAR(255) NOT NULL COMMENT 'Patent number / Publication number.' ,
  `year_applied` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of the application.' ,
  `application` TEXT NOT NULL COMMENT 'Description of the patent.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_patent_information_document` (`document_id` ASC) ,
  CONSTRAINT `fk_patent_information_document`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table for patent related data.';


-- -----------------------------------------------------
-- Table `document_statistics`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_statistics` (
  `document_id` int(10) unsigned NOT NULL COMMENT 'Foreign key to: documents.documents_id.',
  `count` int(11) NOT NULL,
  `year` year(4) NOT NULL,
  `month` tinyint(1) NOT NULL,
  `type` enum('frontdoor','files') NOT NULL,
  PRIMARY KEY  (`document_id`,`year`,`month`,`type`),
  CONSTRAINT `fk_document_statistics_Document`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table for statistic related data.';

-- -----------------------------------------------------
-- Table `document_notes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_notes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to: documents.documents_id.' ,
  `message` TEXT NOT NULL COMMENT 'Message text.' ,
  `visibility` ENUM('private', 'public') NOT NULL COMMENT 'Visibility: private, public to another document version.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_document_notes_document` (`document_id` ASC) ,
  CONSTRAINT `fk_document_notes_document`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table for notes to documents.';

-- -----------------------------------------------------
-- Table `document_enrichments`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_enrichments` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to: documents.documents_id.' ,
  `key_name` VARCHAR(255) NOT NULL COMMENT 'Foreign key to: enrichmentkeys.name.' ,
  `value` MEDIUMTEXT NOT NULL COMMENT 'Value of the enrichment.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_document_enrichments_document` (`document_id` ASC) ,
  INDEX `fk_document_enrichments_document_keyname` (`document_id` ASC, `key_name`) ,
  CONSTRAINT `fk_document_enrichments_document`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_document_enrichment_enrichmentkeys`
  FOREIGN KEY (`key_name` )
  REFERENCES `enrichmentkeys` (`name` )
)ENGINE = InnoDB
  COMMENT = 'Key-value table for database scheme enhancements.';

-- -----------------------------------------------------
-- Table `enrichmentkeys`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `enrichmentkeys` (
  `name` VARCHAR(255) NOT NULL COMMENT 'The enrichment key.' ,
  PRIMARY KEY (`name`)
) ENGINE = InnoDB
  COMMENT = 'Key table for database scheme enhancements.';


-- -----------------------------------------------------
-- Table `document_licences`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_licences` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `active` TINYINT NOT NULL COMMENT 'Flag: can authors choose this licence (0=no, 1=yes)?' ,
  `comment_internal` MEDIUMTEXT NULL COMMENT 'Internal comment.' ,
  `desc_markup` MEDIUMTEXT NULL COMMENT 'Description of the licence in a markup language (XHTML etc.).' ,
  `desc_text` MEDIUMTEXT NULL COMMENT 'Description of the licence in short and pure text form.' ,
  `language` VARCHAR(3) NOT NULL COMMENT 'Language of the licence.' ,
  `link_licence` MEDIUMTEXT NOT NULL COMMENT 'URI of the licence text.' ,
  `link_logo` MEDIUMTEXT NULL COMMENT 'URI of the licence logo.' ,
  `link_sign` MEDIUMTEXT NULL COMMENT 'URI of the licence contract form.' ,
  `mime_type` VARCHAR(30) NOT NULL COMMENT 'Mime type of the licence text linked in \"link_licence\".' ,
  `name_long` VARCHAR(255) NOT NULL COMMENT 'Full name of the licence as displayed to users.' ,
  `pod_allowed` TINYINT(1) NOT NULL COMMENT 'Flag: is print on demand allowed. (1=yes, 0=yes).' ,
  `sort_order` TINYINT NOT NULL COMMENT 'Sort order (00 to 99).' ,
  PRIMARY KEY (`id`) )
  ENGINE = InnoDB
  COMMENT = 'Table for licence related data.';


-- -----------------------------------------------------
-- Table `accounts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `accounts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `login` VARCHAR(45) NOT NULL COMMENT 'Login name.' ,
  `password` VARCHAR(45) NOT NULL COMMENT 'Password.' ,
  `email` VARCHAR(255) NOT NULL COMMENT 'Email address.',
  `first_name` VARCHAR(255) NOT NULL COMMENT 'First name of person.',
  `last_name` VARCHAR(255) NOT NULL COMMENT 'Last name of person.',
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `UNIQUE_LOGIN` (`login` ASC) )
  ENGINE = InnoDB
  COMMENT = 'Table for system user accounts.';


-- -----------------------------------------------------
-- Table `ipranges`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `ipranges` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `startingip` INT UNSIGNED NOT NULL COMMENT 'IP address the range starts with. Use MYSQL functions INET_ATON and INET_NTOA.' ,
  `endingip` INT UNSIGNED NOT NULL COMMENT 'IP address the range end with. Use MYSQL function INET_ATON and INET_NTOA.' ,
  `name` VARCHAR(255) COMMENT 'Name of the range f.e. university or administration.',
  PRIMARY KEY (`id`) ,
  UNIQUE INDEX `UNIQUE_IP_RANGE` (startingip, endingip) )
  ENGINE = InnoDB
  COMMENT = 'Table for ranges of ip addresses.';

-- -----------------------------------------------------
-- Table `user_roles`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `user_roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL UNIQUE,
  PRIMARY KEY (`id`) )
  ENGINE = InnoDB
  COMMENT = 'Table for managing user roles (i.e. groups of users).';

-- -----------------------------------------------------
-- Table `access_documents`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_documents` (
  `role_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: user_roles.id' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: documents.id' ,
  PRIMARY KEY (`role_id`, `document_id`) ,
  INDEX `fk_access_documents_role` (`role_id` ASC) ,
  CONSTRAINT `fk_access_documents_role`
  FOREIGN KEY (`role_id` )
  REFERENCES `user_roles` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX `fk_access_documents_document` (`document_id` ASC) ,
  CONSTRAINT `fk_access_documents_document`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  COMMENT =  'Contains access rights for (given groups) to (documents).';

-- -----------------------------------------------------
-- Table `access_files`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_files` (
  `role_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: user_roles.id' ,
  `file_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: document_files.id' ,
  PRIMARY KEY (`role_id`, `file_id`) ,
  INDEX `fk_access_files_role` (`role_id` ASC) ,
  CONSTRAINT `fk_access_files_role`
  FOREIGN KEY (`role_id` )
  REFERENCES `user_roles` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX `fk_access_files_file` (`file_id` ASC) ,
  CONSTRAINT `fk_access_files_file`
  FOREIGN KEY (`file_id` )
  REFERENCES `document_files` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE = InnoDB
  COMMENT =  'Contains access rights for (given groups) to (files).';

-- -----------------------------------------------------
-- Table `access_modules`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_modules` (
  `role_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: user_roles.id' ,
  `module_name` VARCHAR(255) NOT NULL COMMENT 'Primary key and name of application module' ,
  PRIMARY KEY (`module_name`, `role_id` ) ,
  INDEX `fk_access_modules_role` (`role_id` ASC) ,
  CONSTRAINT `fk_access_modules_role`
  FOREIGN KEY (`role_id` )
  REFERENCES `user_roles` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX `fk_access_modules_module` (`module_name` ASC)
) ENGINE = InnoDB
  COMMENT =  'Contains access rights for (user groups) to (modules).';

-- -----------------------------------------------------
-- Table `link_accounts_roles`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `link_accounts_roles` (
  `account_id` INT UNSIGNED NOT NULL ,
  `role_id` INT UNSIGNED NOT NULL ,
  PRIMARY KEY (`account_id`, `role_id`) ,
  INDEX `fk_accounts_roles_link_accounts` (`account_id` ASC) ,
  INDEX `fk_accounts_roles_link_roles` (`role_id` ASC) ,
  CONSTRAINT `fk_accounts_roles_link_accounts`
  FOREIGN KEY (`account_id` )
  REFERENCES `accounts` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_accounts_roles_link_roles`
  FOREIGN KEY (`role_id` )
  REFERENCES `user_roles` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Relation table (user_roles, accounts).';

-- -----------------------------------------------------
-- Table `link_ipranges_roles`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `link_ipranges_roles` (
  `role_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: user_roles.id.' ,
  `iprange_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: ipranges.id.' ,
  PRIMARY KEY (`role_id`, `iprange_id`) ,
  INDEX `fk_iprange_has_roles` (`role_id` ASC) ,
  INDEX `fk_role_has_ipranges` (`iprange_id` ASC) ,
  CONSTRAINT `fk_iprange_has_role`
  FOREIGN KEY (`role_id` )
  REFERENCES `user_roles` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_role_has_ipranges`
  FOREIGN KEY (`iprange_id` )
  REFERENCES `ipranges` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Relation table (user_roles, ipranges).';


-- -----------------------------------------------------
-- Table `link_documents_licences`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `link_documents_licences` (
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: documents.documents_id.' ,
  `licence_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: licences.licences_id.' ,
  PRIMARY KEY (`document_id`, `licence_id`) ,
  INDEX `fk_documents_has_document_licences_documents` (`document_id` ASC) ,
  INDEX `fk_documents_has_document_licences_document_licences` (`licence_id` ASC) ,
  CONSTRAINT `fk_documents_has_document_licences_documents`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_documents_has_document_licences_document_licences`
  FOREIGN KEY (`licence_id` )
  REFERENCES `document_licences` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Relation table (documents, document_licences).';


-- -----------------------------------------------------
-- Table `document_references`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `document_references` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to referencing document.' ,
  `type` ENUM('doi', 'handle', 'urn', 'std-doi', 'url', 'cris-link', 'splash-url', 'isbn', 'issn', 'opus4-id') NOT NULL COMMENT 'Type of the identifier.' ,
  `relation` ENUM('updates', 'updated-by', 'other') COMMENT 'Describes the type of the relation.',
  `value` TEXT NOT NULL COMMENT 'Value of the identifier.' ,
  `label` TEXT NOT NULL COMMENT 'Display text of the identifier.' ,
  PRIMARY KEY (`id`) ,
  INDEX `fk_document_references_documents` (`document_id` ASC) ,
  CONSTRAINT `fk_document_references_documents`
  FOREIGN KEY (`document_id` )
  REFERENCES `documents` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Table for identifiers referencing to related documents.';


--
-- Table `languages`
-- Based on http://sil.org/iso639-3/download.asp
--
CREATE  TABLE IF NOT EXISTS `languages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `part2_b` char(3) DEFAULT NULL COMMENT 'Equivalent 639-2 identifier of the bibliographic applications code set, if there is one',
  `part2_t` char(3) DEFAULT NULL COMMENT 'Equivalent 639-2 identifier of the terminology applications code set, if there is one',
  `part1` char(2) DEFAULT NULL COMMENT 'Equivalent 639-1 identifier, if there is one',
  `scope` ENUM('I', 'M', 'S') COMMENT 'I(ndividual), M(acrolanguage), S(pecial)',
  `type` ENUM('A', 'C', 'E', 'H', 'L', 'S') COMMENT 'A(ncient), C(onstructed), E(xtinct), H(istorical), L(iving), S(pecial)',
  `ref_name` varchar(150) NOT NULL COMMENT 'Reference language name',
  `comment` varchar(150) DEFAULT NULL COMMENT 'Comment relating to one or more of the columns',
  `active` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Is the language visible? (1=yes, 0=no).' ,
  PRIMARY KEY (`id`)
)
  ENGINE=InnoDB;


-- -----------------------------------------------------
-- Table `dnb_institutes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `dnb_institutes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `name` VARCHAR(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `address` MEDIUMTEXT ,
  `city` VARCHAR(255) NOT NULL ,
  `phone` VARCHAR(255) ,
  `dnb_contact_id` VARCHAR(255) COMMENT 'Contact id of the german national library.' ,
  `is_grantor` TINYINT (1) NOT NULL DEFAULT 0 COMMENT 'Flag: is the institution grantor of academic degrees?' ,
  `is_publisher` TINYINT (1) NOT NULL DEFAULT 0 COMMENT 'Flag: is the institution of academic theses?' ,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`name`, `department`)
)
  ENGINE = InnoDB
  COMMENT = 'Table for thesisPublishers or thesisGrantors.';

-- -----------------------------------------------------
-- Table `link_documents_dnb_institutes`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `link_documents_dnb_institutes` (
  `document_id` INT UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: documents.documents_id.' ,
  `dnb_institute_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `role` ENUM('publisher', 'grantor') NOT NULL COMMENT 'Role of the institute in the actual document-institute context.' ,
  PRIMARY KEY (`document_id`, `dnb_institute_id`, `role`) ,
  INDEX `fk_link_documents_dnb_institutes_documents` (`document_id` ASC) ,
  INDEX `fk_link_documents_dnb_institutes_dnb_institutes` (`dnb_institute_id` ASC) ,
  CONSTRAINT `fk_link_documents_dnb_institutes_documents`
  FOREIGN KEY (`document_id`)
  REFERENCES `documents` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `fk_link_documents_dnb_institutes_dnb_institutes`
  FOREIGN KEY (`dnb_institute_id`)
  REFERENCES `dnb_institutes` (`id`)
    ON DELETE CASCADE
    ON UPDATE CASCADE)
  ENGINE = InnoDB
  COMMENT = 'Relation table (documents, dnb_institutes).';

-- -----------------------------------------------------
-- document xml serializer cache
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_xml_cache` (
  `document_id` INT UNSIGNED NOT NULL,
  `xml_version` INT UNSIGNED NOT NULL,
  `server_date_modified` VARCHAR(50) NULL,
  `xml_data` MEDIUMTEXT,
  PRIMARY KEY (`document_id`, `xml_version`)
)
  ENGINE = InnoDB
  COMMENT = 'Caches XML for Opus_Document objects.';

-- -----------------------------------------
-- Table holding scheduled job information
-- -----------------------------------------
CREATE TABLE IF NOT EXISTS `jobs` (
  `id`          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
  `sha1_id`     VARCHAR(40)     NOT NULL,
  `label`       VARCHAR(50)     NOT NULL,
  `timestamp`   TIMESTAMP       DEFAULT CURRENT_TIMESTAMP,
  `state`       VARCHAR(50),
  `data`        MEDIUMTEXT,
  `errors`      MEDIUMTEXT,
  PRIMARY KEY (`id`),
  INDEX `job_sha1_ids` (`sha1_id` ASC)
)
  ENGINE = InnoDB
  COMMENT = 'Table for schedule jobs.';

--
-- Table structure for table `collections`
--

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

CREATE TABLE IF NOT EXISTS `collections` (
    `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
    `role_id` int(10) unsigned NOT NULL,

    `number` varchar(255) DEFAULT NULL,
    `name` varchar(255) DEFAULT NULL,
    `oai_subset` varchar(255) DEFAULT NULL,

    `left_id` int(10) unsigned NOT NULL,
    `right_id` int(10) unsigned NOT NULL,
    `parent_id` int(10) unsigned DEFAULT NULL,
    `visible` tinyint(1) unsigned NOT NULL,
    `visible_publish` tinyint(1) unsigned NOT NULL DEFAULT 1 COMMENT 'Show collection in publish form',

    PRIMARY KEY (`id`),
    KEY `role_id` (`role_id`,`id`),
    UNIQUE KEY `role_id_left` (`role_id`,`left_id`),
    UNIQUE KEY `role_id_right` (`role_id`,`right_id`),
    -- UNIQUE KEY `role_id_number` (`role_id`,`number`), -- Uniqueness-constraint on number?
    KEY `parent_id` (`parent_id`),
    CONSTRAINT `collections_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `collections_roles` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
    CONSTRAINT `collections_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `collections` (`id`)
)
    ENGINE=InnoDB
    DEFAULT CHARSET=utf8
    AUTO_INCREMENT=15985;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `collections_roles`
--

CREATE TABLE IF NOT EXISTS `collections_roles` (
    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.' ,
    `name` VARCHAR(255) NOT NULL COMMENT 'Name, label or type of the collection role, i.e. a specific classification or conference.' ,
    `oai_name` VARCHAR(255) NOT NULL COMMENT 'Shortname identifying role in oai context.' ,
    `position` INT(11) UNSIGNED NOT NULL COMMENT 'Position of this collection tree (role) in the sorted list of collection roles for browsing and administration.' ,
    `visible` TINYINT(1) UNSIGNED NOT NULL COMMENT 'Deleted collection trees are invisible. (1=visible, 0=invisible).' ,
    `visible_browsing_start`     TINYINT(1) UNSIGNED NOT NULL    COMMENT 'Show tree on browsing start page. (1=yes, 0=no).' ,
    `display_browsing`           VARCHAR(512) NULL               COMMENT 'Comma separated list of collection_contents_x-fields to display in browsing list context.' ,
    `visible_frontdoor`          TINYINT(1) UNSIGNED NOT NULL    COMMENT 'Show tree on frontdoor. (1=yes, 0=no).' ,
    `display_frontdoor`          VARCHAR(512) NULL               COMMENT 'Comma separated list of collection_contents_x-fields to display in frontdoor context.' ,
    `visible_oai`                TINYINT(1) UNSIGNED NOT NULL    COMMENT 'Show tree in oai output. (1=yes, 0=no).' ,
    PRIMARY KEY (`id`) ,
    UNIQUE INDEX `UNIQUE_NAME` (`name` ASC) ,
    UNIQUE INDEX `UNIQUE_OAI_NAME` (`oai_name` ASC)
)
    ENGINE = InnoDB
    DEFAULT CHARSET=utf8
    COMMENT = 'Administration table for the individual collection trees.'
    AUTO_INCREMENT=17;

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `collections_enrichments`
--

CREATE TABLE IF NOT EXISTS collections_enrichments (
    id            INT UNSIGNED NOT NULL,
    collection_id INT(10) unsigned NOT NULL,
    key_name      VARCHAR(255),
    value         VARCHAR(255),
    PRIMARY KEY(id),
    INDEX(collection_id, key_name),
    CONSTRAINT `collections_enrichments_ibfk_1` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
)
    ENGINE = InnoDB
    CHARACTER SET = 'utf8'
    COLLATE = 'utf8_general_ci'
    AUTO_INCREMENT=1;

-- --------------------------------------------------------

--
-- Tabellenstruktur fÃ¼r Tabelle `link_documents_collections`
--

CREATE TABLE IF NOT EXISTS `link_documents_collections` (
    `document_id` int(10) unsigned NOT NULL,
    `collection_id` int(10) unsigned NOT NULL,
    `role_id` int(10) unsigned NOT NULL,
    PRIMARY KEY  (`document_id`,`collection_id`),
    KEY `role_id` (`role_id`,`collection_id`),
    KEY `collection_id` (`collection_id`),
    KEY `document_id` (`document_id`),
    CONSTRAINT `link_documents_collections_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `link_documents_collections_ibfk_2` FOREIGN KEY (`collection_id`) REFERENCES `collections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `link_documents_collections_ibfk_3` FOREIGN KEY (`role_id`) REFERENCES `collections_roles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `link_documents_collections_ibfk_4` FOREIGN KEY (`role_id`, `collection_id`) REFERENCES `collections` (`role_id`, `id`)
)
    ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=20;


SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;