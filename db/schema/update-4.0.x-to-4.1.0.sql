ALTER TABLE `documents`
    DROP COLUMN `server_date_unlocking`,
    ADD COLUMN `server_date_created` VARCHAR(50) NULL COMMENT 'Date of insertion into the database (is generated by the system).' AFTER `publisher_place`,
    ADD COLUMN `server_date_deleted` VARCHAR(50) NULL COMMENT 'Date of deletion, if server_state = delete (is generated by the system).' AFTER `server_date_published`,
    MODIFY COLUMN `server_state` ENUM('published', 'restricted', 'inprogress', 'unpublished', 'deleted', 'temporary') NOT NULL COMMENT 'Status of publication process in the repository.';

ALTER TABLE `document_identifiers`
    MODIFY COLUMN `type` ENUM('doi', 'handle', 'urn', 'std-doi', 'url', 'cris-link', 'splash-url', 'isbn', 'issn', 'opus3-id', 'opac-id', 'uuid', 'serial', 'old', 'pmid', 'arxiv') NOT NULL COMMENT 'Type of the identifier.' ,
    ADD INDEX `fk_document_identifiers_documents_type` (`document_id` ASC, `type` ASC);

ALTER TABLE `document_files`
    ADD COLUMN `comment` TEXT NULL COMMENT 'Comment for a file.' AFTER `label`,
    ADD COLUMN `embargo_date` VARCHAR(50) NULL COMMENT 'Embargo date of file, after which it will be publicly available.';

ALTER TABLE `document_references`
    MODIFY COLUMN `type` ENUM('doi', 'handle', 'urn', 'std-doi', 'url', 'cris-link', 'splash-url', 'isbn', 'issn', 'opus4-id') NOT NULL COMMENT 'Type of the identifier.' ,
    ADD COLUMN `relation` ENUM('updated-by', 'updates') COMMENT 'Describes the type of the relation.' AFTER `type`;

ALTER TABLE `dnb_institutes`
    MODIFY COLUMN `is_grantor` TINYINT (1) NOT NULL DEFAULT 0 COMMENT 'Flag: is the institution grantor of academic degrees?' ,
    ADD COLUMN `is_publisher` TINYINT (1) NOT NULL DEFAULT 0 COMMENT 'Flag: is the institution of academic theses?';

DROP TABLE `person_external_keys`;

-- -----------------------------------------------------
-- Changing security model: drop, rename and create tables
-- -----------------------------------------------------
DROP TABLE `privileges`;

ALTER TABLE `link_accounts_roles`
    COMMENT =  'Relation table (user_roles, accounts).';

ALTER TABLE `link_ipranges_roles`
    COMMENT =  'Relation table (user_roles, ipranges).'
    ALTER TABLE  `link_ipranges_roles` CHANGE  `role_id`  `role_id` INT( 10 ) UNSIGNED NOT NULL COMMENT 'Primary key and foreign key to: user_roles.id.';

RENAME TABLE `roles` TO `user_roles` ;
ALTER TABLE `user_roles`
    COMMENT =  'Table for managing user roles (i.e. groups of users).';

-- -----------------------------------------------------
-- Table `access_documents`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `access_documents` (
    `role_id` INT UNSIGNED NOT NULL COMMENT "Primary key and foreign key to: user_roles.id" ,
    `document_id` INT UNSIGNED NOT NULL COMMENT "Primary key and foreign key to: documents.id" ,
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
    `role_id` INT UNSIGNED NOT NULL COMMENT "Primary key and foreign key to: user_roles.id" ,
    `file_id` INT UNSIGNED NOT NULL COMMENT "Primary key and foreign key to: document_files.id" ,
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
    `role_id` INT UNSIGNED NOT NULL COMMENT "Primary key and foreign key to: user_roles.id" ,
    `module_name` VARCHAR(255) NOT NULL COMMENT "Primary key and name of application module" ,
    `controller_name` VARCHAR(255) NOT NULL COMMENT "Primary key and name of module controller" ,
  PRIMARY KEY (`role_id`, `module_name`, `controller_name`) ,
  INDEX `fk_access_modules_role` (`role_id` ASC) ,
  CONSTRAINT `fk_access_modules_role`
    FOREIGN KEY (`role_id` )
    REFERENCES `user_roles` (`id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  INDEX `fk_access_modules_module` (`module_name` ASC)
) ENGINE = InnoDB
COMMENT =  'Contains access rights for (user groups) to (modules).';




