-- ---------------------------------------------------------------------------
--  SQL update for OPUS 4.5
-- ---------------------------------------------------------------------------

-- Prepare settings for update

SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES';
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;

-- Schema changes

START TRANSACTION;

-- OPUSVIER-3415 Fix values without comma for display fields of collection roles.
UPDATE `collections_roles` SET `display_browsing` = 'Name,Number' WHERE `display_browsing` = 'NameNumber';
UPDATE `collections_roles` SET `display_browsing` = 'Number,Name' WHERE `display_browsing` = 'NumberName';
UPDATE `collections_roles` SET `display_frontdoor` = 'Name,Number' WHERE `display_frontdoor` = 'NameNumber';
UPDATE `collections_roles` SET `display_frontdoor` = 'Number,Name' WHERE `display_frontdoor` = 'NumberName';

-- OPUSVIER-3519 Extending schema_version table

ALTER TABLE `schema_version`
    ADD COLUMN `hash` TEXT COMMENT 'Hash of schema file.',
    ADD COLUMN `version` TEXT COMMENT 'Version number of schema.';

COMMIT;

-- Update database

START TRANSACTION;

-- OPUSVIER-3519 Set version for schema (delete previous entries first)

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES ('4.5');

-- OPUSVIER-


ALTER TABLE `documents`
    MODIFY `type` VARCHAR(100) COMMENT 'Document type.',
    MODIFY `publisher_name` VARCHAR(255) COMMENT 'Name of an external publisher, e.g. Springer';

ALTER TABLE `document_files`
    MODIFY `label` TEXT COMMENT 'Display name of the file.',
    MODIFY `mime_type` VARCHAR(255) COMMENT 'Mime type of the file.',
    MODIFY `file_size` BIGINT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'File size in bytes.';

ALTER TABLE `document_licences`
    MODIFY `active` TINYINT NOT NULL DEFAULT 1 COMMENT 'Flag: can authors choose this licence (0=no, 1=yes)?',
    MODIFY `language` VARCHAR(3) NULL COMMENT 'Language of the licence.',
    MODIFY `mime_type` VARCHAR(30) NULL COMMENT 'Mime type of the licence text linked in \"link_licence\".',
    MODIFY `pod_allowed` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Flag: is print on demand allowed (0=no, 1=yes).',
    MODIFY `sort_order` TINYINT NOT NULL DEFAULT 0 COMMENT 'Sort order (00 to 99).';

ALTER TABLE `accounts`
    MODIFY `email` VARCHAR(255) NULL COMMENT 'Email address.',
    MODIFY `first_name` VARCHAR(255) NULL COMMENT 'First name of person.',
    MODIFY `last_name` VARCHAR(255) NULL COMMENT 'Last name of person.';

ALTER TABLE `collections`
    MODIFY `visible` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1;

ALTER TABLE `collections_roles`
    MODIFY `position` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Position of this collection tree (role) in the sorted list of collection roles for browsing and administration.',
    MODIFY `visible` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Deleted collection trees are invisible. (1=visible, 0=invisible).',
    MODIFY `visible_browsing_start` TINYINT(1) UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Show tree on browsing start page. (1=yes, 0=no).',
    MODIFY `visible_frontdoor` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Show tree on frontdoor. (1=yes, 0=no).',
    MODIFY `visible_oai` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Show tree in oai output. (1=yes, 0=no).';

ALTER TABLE `collections_enrichments`
    MODIFY `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.';

ALTER TABLE `link_persons_documents`
    MODIFY `role` ENUM('advisor', 'author', 'contributor', 'editor', 'referee',  'other', 'translator', 'submitter') NOT NULL COMMENT 'Role of the person in the actual document-person context.';

COMMIT;

-- Modify constraints

START TRANSACTION;

ALTER TABLE `link_documents_collections`
    DROP FOREIGN KEY `link_documents_collections_ibfk_4`;

ALTER TABLE `link_documents_collections`
    ADD CONSTRAINT `link_documents_collections_ibfk_4` FOREIGN KEY (`role_id`, `collection_id`) REFERENCES `collections` (`role_id`, `id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;

-- Reset settings

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;



