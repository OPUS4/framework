SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `opus400` ;
USE `opus400`;

-- -----------------------------------------------------
-- Table `opus400`.`licences`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`licences` (
  `licences_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key. / Primärschlüssel' ,
  `active` TINYINT NOT NULL COMMENT 'Flag: can authors choose this licence (0=no, 1=yes)? / Flag: kann die Lizenz ausgewählt werden  (0=nein, 1=ja)?' ,
  `comment_internal` MEDIUMTEXT NULL COMMENT 'Internal comment. / Interner Kommentar.' ,
  `desc_markup` MEDIUMTEXT NULL COMMENT 'Description of the licence in a markup language (XHTML etc.). / Beschreibung der Lizenz in einer Auszeichnungssprache (XHTML etc).' ,
  `desc_text` MEDIUMTEXT NULL COMMENT 'Description of the licence in short and pure text form. / Kurzbeschreibung der Lizenz in reiner Textform.' ,
  `licence_language` VARCHAR(3) NOT NULL COMMENT 'Language of the licence (triple-digit, ISO639-2/B). / Sprache der Lizenz (dreistellig, ISO639-2/B).' ,
  `link_licence` MEDIUMTEXT NOT NULL COMMENT 'URI of the licence. / URI der Lizenz.' ,
  `link_logo` MEDIUMTEXT NULL COMMENT 'URI of the licence logo. / URI des Lizenzlogos.' ,
  `link_sign` MEDIUMTEXT NULL COMMENT 'URI of the licence contract form. / URI des Lizenzvertrages.' ,
  `mime_type` VARCHAR(30) NOT NULL COMMENT 'Mime type. / Mime-type.' ,
  `name_long` VARCHAR(255) NOT NULL COMMENT 'Full name of the licence as displayed to users. / Kompletter Name der Lizenz. Dieser wird angezeigt.' ,
  `pod_allowed` TINYINT(1) NOT NULL COMMENT 'Flag: is print on demand allowed (0=no, 1=yes)? / Flag: ist Print-on-Demand erlaubt (0=nein, 1=ja)?' ,
  `sort_order` TINYINT NOT NULL COMMENT 'Sort order (00 to 99). / Sortierreihenfolge (00 bis 99).' ,
  PRIMARY KEY (`licences_id`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Table for licence related data.'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`documents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`documents` (
  `documents_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key. / Primärschlüssel.' ,
  `licences_id` INT UNSIGNED NULL COMMENT 'Foreign key: licences.licences_id / Fremdschlüssel: licences.licences_id' ,
  `range_id` INT NULL COMMENT 'Foreign key: ?.? / Fremdschlüssel: ?.?' ,
  `completed_date` DATE NULL COMMENT 'Date of completion. / Datum der Fertigstellung.' ,
  `completed_year` YEAR NOT NULL COMMENT 'Year of completion, if the complete date is unknown. / Jahr der Fertigstellung, wenn das vollständige Datum unbekannt ist.' ,
  `contributing_corporation` TEXT NULL COMMENT 'Contribution corporation. / Sonstige beteiligte Körperschaft.' ,
  `creating_corporation` TEXT NULL COMMENT 'Creating corporation. / Körperschaft als Urheber.' ,
  `date_accepted` DATE NULL COMMENT 'Date of final exam (date of the doctoral graduation). / Datum der letzten Prüfung (Datum der Promotion).' ,
  `document_type` ENUM('article', 'book section', 'monograph', 'report', 'doctoral thesis') NOT NULL COMMENT 'Document type. / Dokumenttyp.' ,
  `edition` VARCHAR(25) NULL COMMENT 'Edition. / Auflage.' ,
  `issue` VARCHAR(25) NULL COMMENT 'Issue. / Heft.' ,
  `language` VARCHAR(3) NULL COMMENT 'Primary language of the document (triple-digit, ISO639-2/B). / Haupsächliche Sprache des Dokuments (dreistellig, ISO639-2/B).' ,
  `non_institute_affiliation` TEXT NULL COMMENT 'Institutions, which are not officialy part of the university. / Einrichtungen, welche kein offizielles universitätsinternes Institut sind.' ,
  `page_first` INT NULL COMMENT 'First page of a text. / Erste Seite eines Textes.' ,
  `page_last` INT NULL COMMENT 'Last page of a text. / Letzte Seite eines Textes.' ,
  `page_number` INT NULL COMMENT 'Page number. / Seitenzahl.' ,
  `publication_status` TINYINT(1) NOT NULL COMMENT 'Processing status of the document. / Bearbeitungsstatus des Dokuments.' ,
  `published_date` DATE NULL COMMENT 'Date of first publication. / Datum der Primärveröffentlichung.' ,
  `published_year` YEAR NOT NULL COMMENT 'Year of first publication, if the complete date is unknown. / Erscheinungsjahr der Primärveröffentlichung, wenn das vollständige Datum unbekannt ist.' ,
  `publisher_name` VARCHAR(255) NULL COMMENT 'Name of publisher. / Name des Verlags.' ,
  `publisher_place` VARCHAR(255) NULL COMMENT 'Place of publication. / Veröffentlichungsort.' ,
  `publisher_university` INT NOT NULL COMMENT 'Publishing university. / Veröffentlichende Universität.' ,
  `reviewed` ENUM('peer', 'editorial', 'open') NOT NULL COMMENT 'Style of the review process. / Art der Begutachtung.' ,
  `server_date_modified` DATETIME NULL COMMENT 'Last modification of the document (is generated by the system). / Letzte Änderung des Dokumentes (wird vom System generiert).' ,
  `server_date_published` DATETIME NOT NULL COMMENT 'Date of publication on the repository (is generated by the system). / Veröffentlichungsdatum auf  dem  Repository (wird vom System generiert).' ,
  `server_date_unlocking` DATE NULL COMMENT 'Expiration date of a embargo. / Datum des Ablaufs einer Sperrfrist (Embargofrist).' ,
  `server_date_valid` DATE NULL COMMENT 'Expiration date of the validity of the document. / Ablauf des Gültigkeitsdatums des Dokuments.' ,
  `source` VARCHAR(255) NULL COMMENT 'Bibliographic date from OPUS 3.x (formerly source_text). / Bibliographische Daten aus OPUS 3.x (ehemals source_text).' ,
  `swb_id` VARCHAR(255) NULL COMMENT 'Identification number of the SWB. / Identifikationsnummer des SWB.' ,
  `vg_wort_pixel_url` TEXT NULL COMMENT 'URI to the VG Wort tracking pixel. / URI auf den VG-Wort-Zählpixel.' ,
  `volume` VARCHAR(25) NULL COMMENT 'Volume. / Jahrgang.' ,
  PRIMARY KEY (`documents_id`) ,
  INDEX fk_documents_licences (`licences_id` ASC) ,
  CONSTRAINT `fk_documents_licences`
    FOREIGN KEY (`licences_id` )
    REFERENCES `opus400`.`licences` (`licences_id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Document related data (monolingual, unreproducible colums).'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_identifiers`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_identifiers` (
  `document_identifiers_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT UNSIGNED NOT NULL COMMENT 'Fremdschlüssel auf DocumentTabelle' ,
  `identifier_type` ENUM('doi', 'handle', 'urn', 'std-doi', 'url', 'cris-link', 'splash-url', 'isbn', 'issn') NOT NULL COMMENT 'Art des Verweises (intern und extern)' ,
  `identifier_value` TEXT NOT NULL COMMENT 'Verweis' ,
  `identifier_label` TEXT NOT NULL COMMENT 'Anzeigetext für den Verweis' ,
  PRIMARY KEY (`document_identifiers_id`) ,
  INDEX has (`documents_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
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
  `institutes_id` INT UNSIGNED NOT NULL COMMENT 'Ident. Einrichtung unabh. v. d. verwendeten Sprache' ,
  `institutes_language` VARCHAR(3) NOT NULL COMMENT 'ISO Sprachkürzel' ,
  `type` VARCHAR(50) NOT NULL COMMENT 'Art der Einrichtung' ,
  `name` VARCHAR(255) NOT NULL COMMENT 'Bezeichnung der Einrichtung' ,
  `postal_address` TEXT NULL ,
  `site` TEXT NULL ,
  PRIMARY KEY (`institutes_id`, `institutes_language`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_files`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_files` (
  `document_files_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT UNSIGNED NULL ,
  `file_path_name` TEXT NOT NULL COMMENT 'Pfad und Dateiname' ,
  `file_sort_order` TINYINT(4) NOT NULL COMMENT 'Sortierung der Dateien' ,
  `file_label` TEXT NOT NULL COMMENT 'Anzeigetext für Datei' ,
  `file_type` VARCHAR(255) NOT NULL COMMENT 'Dateityp nach Dublin Core' ,
  `mime_type` VARCHAR(255) NOT NULL COMMENT 'Mime type der Datei' ,
  `file_language` VARCHAR(3) NULL COMMENT 'Sprache der Datei' ,
  PRIMARY KEY (`document_files_id`) ,
  INDEX fk_document_files_documents (`documents_id` ASC) ,
  CONSTRAINT `fk_document_files_documents`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
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
  `document_files_id` INT UNSIGNED NOT NULL COMMENT 'Fremdschlüssel zur Document_Files Tabelle' ,
  `hash_type` VARCHAR(50) NOT NULL COMMENT 'Art des Hashes' ,
  `hash_value` TEXT NOT NULL COMMENT 'Hashwert ' ,
  PRIMARY KEY (`file_hashvalues_id`, `document_files_id`) ,
  INDEX fk_file_hashvalues_document_files (`document_files_id` ASC) ,
  CONSTRAINT `fk_file_hashvalues_document_files`
    FOREIGN KEY (`document_files_id` )
    REFERENCES `opus400`.`document_files` (`document_files_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_subjects`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_subjects` (
  `document_subjects_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT UNSIGNED NULL ,
  `subject_language` VARCHAR(3) NULL COMMENT 'Sprache des Erschließungssystems' ,
  `subject_type` ENUM('ddc', 'swd', 'psyndex', 'uncontrolled') NOT NULL COMMENT 'Art der Erschließung' ,
  `subject_value` VARCHAR(255) NOT NULL COMMENT 'Wert zu subject_type (kontrolliertes/freies Schlagwort, Notation, etc)' ,
  `external_subject_key` VARCHAR(255) NULL COMMENT 'Identifikator zur Auflösung von Deskriptoren in Fremdsystemen' ,
  PRIMARY KEY (`document_subjects_id`) ,
  INDEX fk_document_subjects_documents (`documents_id` ASC) ,
  CONSTRAINT `fk_document_subjects_documents`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_title_abstracts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_title_abstracts` (
  `document_title_abstracts_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key. / Primärschlüssel.' ,
  `documents_id` INT UNSIGNED NULL COMMENT 'Foreign key: documents.documents_id / Fremdschlüssel: documents.documents_id' ,
  `title_abstract_type` ENUM('main', 'parent', 'abstract') NOT NULL COMMENT 'Type of title or abstract. / Art des Titels oder des Abstracts.' ,
  `title_abstract_value` TEXT NOT NULL COMMENT 'Value of title or abstract. / Wert des Titels oder Abstracts.' ,
  `title_abstract_language` VARCHAR(3) NOT NULL COMMENT 'Language of the title or abstract (triple-digit, ISO639-2/B). / Sprache des Titels oder Abstracts (dreistelligt, ISO639-2/B).' ,
  PRIMARY KEY (`document_title_abstracts_id`) ,
  INDEX fk_document_title_abstracts_documents (`documents_id` ASC) ,
  CONSTRAINT `fk_document_title_abstracts_documents`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Table with title and abstract related data.'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`persons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`persons` (
  `persons_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
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
  `persons_id` INT UNSIGNED NULL ,
  `type` ENUM('pnd') NOT NULL COMMENT 'Art der externen ID (z.B. PND)' ,
  `value` TEXT NOT NULL COMMENT 'Wert' ,
  `resolver` VARCHAR(255) NULL COMMENT 'URL zum Auflösungsmechanismus' ,
  PRIMARY KEY (`person_external_keys_Id`) ,
  INDEX fk_person_external_keys_persons (`persons_id` ASC) ,
  CONSTRAINT `fk_person_external_keys_persons`
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
  `link_documents_persons_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT UNSIGNED NOT NULL ,
  `persons_id` INT UNSIGNED NOT NULL ,
  `institutes_id` INT UNSIGNED NOT NULL ,
  `role` ENUM('advisor', 'author', 'contributor', 'editor', 'referee',  'other', 'translator') NOT NULL COMMENT 'Rolle der Person im aktuellen Dokument-Institut-Kontext' ,
  `sort_order` TINYINT UNSIGNED NOT NULL COMMENT 'Reihenfolge der Autoren' ,
  PRIMARY KEY (`link_documents_persons_id`) ,
  INDEX fk_link_documents_persons_documents (`documents_id` ASC) ,
  INDEX fk_link_documents_persons_persons (`persons_id` ASC) ,
  INDEX fk_link_documents_persons_institutes_contents (`institutes_id` ASC) ,
  CONSTRAINT `fk_link_documents_persons_documents`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_link_documents_persons_persons`
    FOREIGN KEY (`persons_id` )
    REFERENCES `opus400`.`persons` (`persons_id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `fk_link_documents_persons_institutes_contents`
    FOREIGN KEY (`institutes_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Role of person regarding a document.'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_patents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_patents` (
  `document_patents_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT UNSIGNED NOT NULL COMMENT 'Fremdschlüssel auf Document Tabelle' ,
  `patent_countries` TEXT NOT NULL COMMENT 'Länder, in denen Patent erteilt wurde' ,
  `patent_date_granted` DATE NOT NULL COMMENT 'Datum der Patenterteilung' ,
  `patent_number` VARCHAR(255) NOT NULL COMMENT 'Patentnummer' ,
  `patent_year_applied` YEAR NOT NULL COMMENT 'Jahr der Antragsstellung' ,
  `patent_application` TEXT NOT NULL COMMENT 'Beschreibung der Anwendung/ des Patents' ,
  PRIMARY KEY (`document_patents_id`) ,
  INDEX fk_patent_information_document (`documents_id` ASC) ,
  CONSTRAINT `fk_patent_information_document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_statistics`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_statistics` (
  `document_statistics_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel.' ,
  `documents_id` INT UNSIGNED NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `statistic_type` TEXT NOT NULL COMMENT 'Art der dokumentbezogenen Statistik' ,
  `statistic_value` TEXT NOT NULL COMMENT 'Kennwert' ,
  `start_survey_period` DATETIME NOT NULL ,
  `end_survey_period` DATETIME NOT NULL ,
  PRIMARY KEY (`document_statistics_id`) ,
  INDEX fk_document_statistics_Document (`documents_id` ASC) ,
  CONSTRAINT `fk_document_statistics_Document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE RESTRICT
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_notes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_notes` (
  `document_notes_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `documents_id` INT UNSIGNED NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `message` TEXT NOT NULL COMMENT 'Mitteilungstext' ,
  `creator` TEXT NOT NULL COMMENT 'Verfasser der Mitteilung' ,
  `scope` ENUM('private', 'public', 'reference') NOT NULL COMMENT 'Sichtbarkeit: intern, extern, Verweis auf andere Dokumentversion ' ,
  PRIMARY KEY (`document_notes_id`) ,
  INDEX fk_document_notes_document (`documents_id` ASC) ,
  CONSTRAINT `fk_document_notes_document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_enrichments`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_enrichments` (
  `document_enrichments_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key. / Primärschlüssel.' ,
  `documents_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key: documents.documents_id / Fremdschlüssel: documents.documents_id ' ,
  `enrichment_type` VARCHAR(255) NOT NULL COMMENT 'Type of enrichment. / Art der Erweiterung.' ,
  `enrichment_value` TEXT NOT NULL COMMENT 'Value of the enrichment. / Wert der Erweiterung.' ,
  PRIMARY KEY (`document_enrichments_id`) ,
  INDEX fk_document_enrichment_document (`documents_id` ASC) ,
  CONSTRAINT `fk_document_enrichment_document`
    FOREIGN KEY (`documents_id` )
    REFERENCES `opus400`.`documents` (`documents_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Table for data model enhancements.'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`institutes_structure`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`institutes_structure` (
  `institutes_structure_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `institutes_id` INT UNSIGNED NOT NULL ,
  `left` INT UNSIGNED NOT NULL ,
  `right` INT UNSIGNED NOT NULL ,
  `visible` TINYINT NOT NULL ,
  PRIMARY KEY (`institutes_structure_id`) ,
  INDEX fk_institutes_structure_institutes_contents (`institutes_id` ASC) ,
  CONSTRAINT `fk_institutes_structure_institutes_contents`
    FOREIGN KEY (`institutes_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Nested Sets Struktur der Institute-Hierarchie'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`institutes_replacement`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`institutes_replacement` (
  `institutes_replacement_id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `institutes_id` INT UNSIGNED NOT NULL COMMENT 'betrachtete Einrichtung' ,
  `replacement_for_id` INT UNSIGNED NULL COMMENT 'ersetzte Einrichtung' ,
  `replacement_by_id` INT UNSIGNED NULL COMMENT 'ersetzende Einrichtung' ,
  `current_replacement_id` INT UNSIGNED NULL COMMENT 'aktuell nachfolgende Einrichtung' ,
  PRIMARY KEY (`institutes_replacement_id`) ,
  INDEX fk_link_institute (`institutes_id` ASC) ,
  INDEX fk_link_institute_replacement_for (`replacement_for_id` ASC) ,
  INDEX fk_link_institute_replacement_by (`replacement_by_id` ASC) ,
  INDEX fk_link_institute_current_replacement (`current_replacement_id` ASC) ,
  CONSTRAINT `fk_link_institute`
    FOREIGN KEY (`institutes_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_link_institute_replacement_for`
    FOREIGN KEY (`replacement_for_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_link_institute_replacement_by`
    FOREIGN KEY (`replacement_by_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_link_institute_current_replacement`
    FOREIGN KEY (`current_replacement_id` )
    REFERENCES `opus400`.`institutes_contents` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`accounts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`accounts` (
  `account_id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `login` VARCHAR(45) NOT NULL ,
  `password` VARCHAR(45) NOT NULL ,
  PRIMARY KEY (`account_id`) ,
  UNIQUE INDEX UNIQUE_LOGIN (`login` ASC) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'System user accounts.';


-- -----------------------------------------------------
-- Table `opus400`.`collections_roles`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`collections_roles` (
  `collections_roles_id` INT(11) UNSIGNED NOT NULL ,
  `collections_language` VARCHAR(3) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL ,
  `name` VARCHAR(255) CHARACTER SET 'utf8' COLLATE 'utf8_general_ci' NOT NULL ,
  `visible` TINYINT(1) UNSIGNED NULL ,
  PRIMARY KEY (`collections_roles_id`, `collections_language`) )
ENGINE = InnoDB
DEFAULT CHARACTER SET = utf8
COLLATE = utf8_general_ci
COMMENT = 'Verwaltungstabelle fuer die einzelnen Collection-Baeume';



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
