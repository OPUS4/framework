-- -----------------------------------------------------
-- Table `document_sets`
-- ----------------------------------------------------
CREATE TABLE IF NOT EXISTS `document_sets` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` mediumtext NOT NULL COMMENT 'Title of document set (e.g. series)',
  `logo` text NOT NULL COMMENT 'Pfad zum Logo des Containers',
  `publisher` mediumtext NOT NULL COMMENT 'Name of Publisher',
  `issn` mediumtext COMMENT 'ISSN of that document set',
  `infobox` text COMMENT 'html-fähige Infobox',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB;


-- -----------------------------------------------------
-- Table `link_document_sets`
-- -----------------------------------------------------
CREATE TABLE IF NOT EXISTS `link_documents_sets` (
  `document_id` int(10) unsigned NOT NULL,
  `set_id` int(10) unsigned NOT NULL,
  `number` int(11) NOT NULL COMMENT 'corresponding number (e.g. serial number)',
  PRIMARY KEY (`document_id`,`set_id`),
  UNIQUE KEY `document_id` (`document_id`,`number`),
  KEY `set_id` (`set_id`),
  CONSTRAINT `link_documents_sets_ibfk_2` FOREIGN KEY (`set_id`) REFERENCES `document_sets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `link_documents_sets_ibfk_1` FOREIGN KEY (`document_id`) REFERENCES `documents` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;
