SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `opus400` ;
USE `opus400`;

-- -----------------------------------------------------
-- Table `opus400`.`licences`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`licences` (
  `licences_id` INT NOT NULL AUTO_INCREMENT ,
  `shortname` VARCHAR(20) NOT NULL ,
  `longname` VARCHAR(255) NOT NULL ,
  `desc_text` MEDIUMTEXT NULL ,
  `active` TINYINT NOT NULL ,
  `sort` TINYINT NOT NULL ,
  `pod_allowed` TINYINT(1) NOT NULL ,
  `language` VARCHAR(3) NOT NULL ,
  `link` MEDIUMTEXT NOT NULL ,
  `link_to_sign` MEDIUMTEXT NOT NULL ,
  `desc_html` MEDIUMTEXT NULL ,
  `mime_type` VARCHAR(30) NOT NULL ,
  `logo` MEDIUMTEXT NULL ,
  `comment` MEDIUMTEXT NULL ,
  PRIMARY KEY (`licences_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `opus400`.`documents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`documents` (
  `documents_id` INT NOT NULL AUTO_INCREMENT COMMENT 'eindeutige datenbankinterne Kennnummer, Primärschlüssel für andere Tabellen' ,
  `licences_id` INT NOT NULL COMMENT 'Nutzungslizenz' ,
  `book_series_volume` VARCHAR(25) NULL COMMENT 'Band des Gesamttitels' ,
  `book_volume` VARCHAR(25) NULL COMMENT 'Band' ,
  `contributing_corporation` TEXT NULL COMMENT 'Sonstige beteiligte Institutionen (dc:contributor.corporate)' ,
  `creating_corporation` TEXT NULL COMMENT 'Urheber (dc:creator.corporate)' ,
  `date_accepted` DATE NULL COMMENT 'Datum der mündlichen Prüfung (für Dissertationen)' ,
  `server_date_modified` DATETIME NULL COMMENT 'letzte Änderung des Datensatzes (dc:date.modified)' ,
  `server_date_published` DATETIME NOT NULL COMMENT 'Veröffentlichungsdatum auf Server (dc:date.creation)' ,
  `server_date_unlocking` DATE NULL COMMENT 'Freigabedatum (Embargofrist)' ,
  `server_date_valid` DATE NULL COMMENT 'Gültig bis' ,
  `document_type` ENUM('article', 'book section', 'monograph', 'report', 'doctoral thesis') NOT NULL COMMENT 'Dokumenttyp (type)' ,
  `edition` VARCHAR(25) NULL COMMENT 'Auflage' ,
  `first_page` INT NULL COMMENT 'Seite von' ,
  `last_page` INT NULL COMMENT 'Seite bis' ,
  `publication_status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Bearbeitungsstatus (temp, in review, published)' ,
  `journal_issue` VARCHAR(25) NULL COMMENT 'Heft' ,
  `journal_volume` VARCHAR(25) NULL COMMENT 'Jahrgang' ,
  `language` VARCHAR(3) NULL COMMENT '(hauptsächliche) Sprache des Dokuments' ,
  `page_number` INT NULL COMMENT 'Seitenzahl' ,
  `place` VARCHAR(255) NULL COMMENT 'Veröffentlichungsort' ,
  `publisher` VARCHAR(255) NULL COMMENT 'Verlag' ,
  `publisher_university` INT NOT NULL COMMENT 'Veröffentlichende Universität' ,
  `range_id` INT NULL COMMENT 'Bereichs-Id (Zugänglichkeit nur auf Campus)' ,
  `reviewed` ENUM('peer', 'editorial', 'open') NOT NULL COMMENT 'Art der Review' ,
  `source` VARCHAR(255) NULL COMMENT 'Bibliographische Daten aus OPUS 3' ,
  `swb_id` VARCHAR(255) NULL COMMENT 'SWB ID' ,
  `completed_year` YEAR NOT NULL COMMENT 'Jahr der Fertigstellung (bei Dissertationen)' ,
  `completed_date` DATE NULL COMMENT 'Datum der Fertigstellung' ,
  `published_year` YEAR NOT NULL COMMENT 'Erscheinungsjahr der Primärveröffentlichung' ,
  `published_date` DATE NULL ,
  `non_institute_affiliation` TEXT NULL COMMENT 'Für alles, was kein universitätsinternes Institut im Sinne von Tabelle Institutes ist' ,
  `vg_wort_pixel_url` TEXT NULL COMMENT 'URL auf den VG-Wort-Zählpixel' ,
  PRIMARY KEY (`documents_id`) ,
  INDEX fk_Document_license (`licences_id` ASC) ,
  CONSTRAINT `fk_Document_license`
    FOREIGN KEY (`licences_id` )
    REFERENCES `opus400`.`licences` (`licences_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_identifiers`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_identifiers` (
  `document_identifiers_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel auf DocumentTabelle' ,
  `identifier_type` ENUM('doi', 'handle', 'urn', 'std-doi', 'url', 'cris-link', 'splash-url', 'isbn', 'issn') NOT NULL COMMENT 'Art des Verweises (intern und extern)' ,
  `identifier_value` TEXT NOT NULL COMMENT 'Verweis' ,
  `identifier_label` TEXT NOT NULL COMMENT 'Anzeigetext für den Verweis' ,
  PRIMARY KEY (`document_identifiers_id`) ,
  INDEX has (`documents_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = '\n'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`institutes_contents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`institutes_contents` (
  `institutes_contents_id` INT NOT NULL COMMENT 'Primärschlüssel' ,
  `institutes_type` VARCHAR(50) NOT NULL COMMENT 'Art der Einrichtung' ,
  `institutes_name` VARCHAR(255) NOT NULL COMMENT 'Bezeichnung der Einrichtung' ,
  `postal_adress` TEXT NULL ,
  `institutes_site` TEXT NULL ,
  PRIMARY KEY (`institutes_contents_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_files`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_files` (
  `document_files_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `file_path_name` TEXT NOT NULL COMMENT 'Pfad und Dateiname' ,
  `file_sort_order` TINYINT(4) NOT NULL COMMENT 'Sortierung der Dateien' ,
  `file_label` TEXT NOT NULL COMMENT 'Anzeigetext für Datei' ,
  `file_type` VARCHAR(255) NOT NULL COMMENT 'Dateityp nach Dublin Core' ,
  `mime_type` VARCHAR(255) NOT NULL COMMENT 'Mime type der Datei' ,
  `file_language` VARCHAR(3) NULL COMMENT 'Sprache der Datei' ,
  PRIMARY KEY (`document_files_id`) ,
  INDEX has (`documents_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`file_hashvalues`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`file_hashvalues` (
  `file_hashvalues_id` TINYINT UNSIGNED NOT NULL COMMENT 'Primärschlüssel' ,
  `document_files_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document_Files Tabelle' ,
  `hash_type` VARCHAR(50) NOT NULL COMMENT 'Art des Hashes' ,
  `hash_value` TEXT NOT NULL COMMENT 'Hashwert ' ,
  PRIMARY KEY (`file_hashvalues_id`, `document_files_id`) ,
  INDEX fk_file_hashvalues_document_files (`document_files_id` ASC) ,
  CONSTRAINT `fk_file_hashvalues_document_files`
    FOREIGN KEY (`document_files_id` )
    REFERENCES `opus400`.`document_files` (`document_files_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_subjects`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_subjects` (
  `document_subjects_id` INT NOT NULL COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `subject_type` ENUM('psyndex terms', 'ddc', 'swd') NOT NULL COMMENT 'Art der Erschließung' ,
  `subject_value` VARCHAR(255) NOT NULL COMMENT 'Wert zu subject_type (kontrolliertes/freies Schlagwort, Notation, etc)' ,
  `language` VARCHAR(3) NULL COMMENT 'Sprache des Erschließungssystems' ,
  `external_subject_key` VARCHAR(255) NULL COMMENT 'Identifikator zur Auflösung von Deskriptoren in Fremdsystemen' ,
  PRIMARY KEY (`document_subjects_id`) ,
  INDEX opus_subject_FKIndex1 (`documents_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_title_abstracts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_title_abstracts` (
  `document_title_abstracts_id` INT UNSIGNED NOT NULL COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `title_abstract_type` ENUM('main', 'parent', 'abstract') NOT NULL COMMENT 'Titel/Abstract des Dokuments' ,
  `title_abstract_value` TEXT NOT NULL COMMENT 'Inhalt zu title_abstract_type' ,
  `title_abstract_language` VARCHAR(3) NOT NULL COMMENT 'Sprache des Titels, Abstracts' ,
  PRIMARY KEY (`document_title_abstracts_id`) ,
  INDEX Title_Abstract_FKIndex1 (`documents_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` ))
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`persons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`persons` (
  `persons_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `academic_title` VARCHAR(255) NULL COMMENT 'Akademischer Titel' ,
  `date_of_birth` DATETIME NULL COMMENT 'Geburtsdatum' ,
  `email` VARCHAR(100) NULL COMMENT 'Email-Adresse' ,
  `first_name` VARCHAR(255) NULL COMMENT 'Vorname' ,
  `last_name` VARCHAR(255) NOT NULL COMMENT 'Nachname' ,
  `place_of_birth` VARCHAR(255) NULL COMMENT 'Geburtsort' ,
  PRIMARY KEY (`persons_id`) ,
  INDEX last_name (`last_name` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`person_external_keys`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`person_external_keys` (
  `person_external_keys_Id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `persons_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Person Tabelle' ,
  `type` ENUM('pnd') NOT NULL COMMENT 'Art der externen ID (z.B. PND)' ,
  `value` TEXT NOT NULL COMMENT 'Wert' ,
  `resolver` VARCHAR(255) NULL COMMENT 'URL zum Auflösungsmechanismus' ,
  PRIMARY KEY (`person_external_keys_Id`) ,
  INDEX Person_External_Key_FKIndex1 (`persons_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`persons_id` )
    REFERENCES `opus400`.`persons` (`persons_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`link_documents_persons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`link_documents_persons` (
  `link_documents_persons_id` INT NOT NULL COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Primärschlüssel' ,
  `institutes_contents_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Institute Tabelle' ,
  `persons_id` INT NOT NULL ,
  `role` ENUM('advisor', 'author', 'contributor', 'editor', 'referee',  'other', 'translator') NOT NULL ,
  `sort_order` TINYINT UNSIGNED NOT NULL COMMENT 'Reihenfolge der Autoren' ,
  PRIMARY KEY (`link_documents_persons_id`) ,
  INDEX Person_has_Document_FKIndex1 (`link_documents_persons_id` ASC) ,
  INDEX Person_has_Document_FKIndex2 (`documents_id` ASC) ,
  INDEX Rel_06 (`institutes_contents_id` ASC) ,
  CONSTRAINT `Rel_04`
    FOREIGN KEY (`persons_id` )
    REFERENCES `opus400`.`persons` (`persons_id` ),
  CONSTRAINT `Rel_05`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` ),
  CONSTRAINT `Rel_06`
    FOREIGN KEY (`institutes_contents_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_contents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Possilbe values for role:\n\nENUM(\'advisor\', \'author\', \'contributor\', \'editor\', \'referee\',  \'other\', \'translator\')'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_patents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_patents` (
  `document_patents_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel auf Document Tabelle' ,
  `patent_countries` TEXT NOT NULL COMMENT 'Länder, in denen Patent erteilt wurde' ,
  `patent_date_granted` DATE NOT NULL COMMENT 'Datum der Patenterteilung' ,
  `patent_number` VARCHAR(255) NOT NULL COMMENT 'Patentnummer' ,
  `patent_year_applied` YEAR NOT NULL COMMENT 'Jahr der Antragsstellung' ,
  `patent_application` TEXT NOT NULL COMMENT 'Beschreibung der Anwendung/ des Patents' ,
  PRIMARY KEY (`document_patents_id`) ,
  INDEX fk_Patent_Information_Document (`documents_id` ASC) ,
  CONSTRAINT `fk_Patent_Information_Document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `opus400`.`document_statistics`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_statistics` (
  `document_statistics_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `statistic_type` TEXT NOT NULL COMMENT 'Art der dokumentbezogenen Statistik' ,
  `statistic_value` TEXT NOT NULL COMMENT 'Kennwert' ,
  `start_survey_period` DATETIME NOT NULL ,
  `end_survey_period` DATETIME NOT NULL ,
  PRIMARY KEY (`document_statistics_id`) ,
  INDEX fk_Document_Statistics_Document (`documents_id` ASC) ,
  CONSTRAINT `fk_Document_Statistics_Document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `opus400`.`document_notes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_notes` (
  `document_notes_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `message` TEXT NOT NULL COMMENT 'Mitteilungstext' ,
  `creator` TEXT NOT NULL COMMENT 'Verfasser der Mitteilung' ,
  `scope` ENUM('private', 'public', 'reference') NOT NULL COMMENT 'Sichtbarkeit: intern, extern, Verweis auf andere Dokumentversion ' ,
  PRIMARY KEY (`document_notes_id`) ,
  INDEX fk_Document_Notes_Document (`documents_id` ASC) ,
  CONSTRAINT `fk_Document_Notes_Document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci;


-- -----------------------------------------------------
-- Table `opus400`.`document_enrichments`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_enrichments` (
  `document_enrichments_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `enrichment_type` VARCHAR(255) NOT NULL COMMENT 'Art der Erweiterung' ,
  `enrichment_value` TEXT NOT NULL COMMENT 'Wert der Erweiterung' ,
  PRIMARY KEY (`document_enrichments_id`) ,
  INDEX fk_Document_Enrichment_Document (`documents_id` ASC) ,
  CONSTRAINT `fk_Document_Enrichment_Document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Multivalue Tabelle zur unkomplizierten Metadaten-Erweiterung';


-- -----------------------------------------------------
-- Table `opus400`.`institutes_structure`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`institutes_structure` (
  `institutes_structure_id` INT NOT NULL COMMENT 'Primärschlüssel' ,
  `institutes_contents_id` INT NOT NULL ,
  `left` INT NOT NULL ,
  `right` INT NULL ,
  PRIMARY KEY (`institutes_structure_id`) ,
  INDEX institutes_contents_id (`institutes_contents_id` ASC) ,
  CONSTRAINT `institutes_contents_id`
    FOREIGN KEY (`institutes_contents_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_contents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`institutes_replacement`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`institutes_replacement` (
  `institutes_replacement_id` INT NOT NULL COMMENT 'Primärschlüssel' ,
  `institutes_contents_id` INT NOT NULL ,
  `replacement_for_id` INT NOT NULL ,
  `replacement_by_id` INT NOT NULL ,
  `current_replacement_id` INT NOT NULL ,
  PRIMARY KEY (`institutes_replacement_id`) ,
  INDEX institutes_contents_id (`institutes_contents_id` ASC) ,
  INDEX replacement_for_id (`replacement_for_id` ASC) ,
  INDEX replacement_by_id (`replacement_by_id` ASC) ,
  INDEX current_replacement (`current_replacement_id` ASC) ,
  CONSTRAINT `institutes_contents_id`
    FOREIGN KEY (`institutes_contents_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_contents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `replacement_for_id`
    FOREIGN KEY (`replacement_for_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_contents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `replacement_by_id`
    FOREIGN KEY (`replacement_by_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_contents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `current_replacement`
    FOREIGN KEY (`current_replacement_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_contents_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
