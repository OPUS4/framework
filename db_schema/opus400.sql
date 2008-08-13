SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='TRADITIONAL';

CREATE SCHEMA IF NOT EXISTS `opus400` ;
USE `opus400`;

-- -----------------------------------------------------
-- Table `opus400`.`licences`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`licences` (
  `licence_id` INT NOT NULL AUTO_INCREMENT ,
  `shortname` VARCHAR(20) NULL ,
  `longname` VARCHAR(255) NULL ,
  `desc_text` MEDIUMTEXT NULL ,
  `active` TINYINT NULL ,
  `sort` TINYINT(2) NULL ,
  `pod_allowed` TINYINT(1) NULL ,
  `language` CHAR(3) NULL ,
  `link` MEDIUMTEXT NULL ,
  `link_to_sign` MEDIUMTEXT NULL ,
  `desc_html` MEDIUMTEXT NULL ,
  `mime_type` VARCHAR(30) NULL ,
  `logo` MEDIUMTEXT NULL ,
  `comment` MEDIUMTEXT NULL ,
  PRIMARY KEY (`licence_id`) )
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `opus400`.`documents`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`documents` (
  `document_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'eindeutige datenbankinterne Kennnummer, Primärschlüssel für andere Tabellen' ,
  `licence_id` INT NOT NULL COMMENT 'Nutzungslizenz' ,
  `book_series_volume` VARCHAR(25) NOT NULL COMMENT 'Band des Gesamttitels' ,
  `book_volume` VARCHAR(25) NOT NULL COMMENT 'Band' ,
  `contributing_corporation` TEXT NOT NULL COMMENT 'Sonstige beteiligte Institutionen (dc:contributor.corporate)' ,
  `creating_corporation` TEXT NOT NULL COMMENT 'Urheber (dc:creator.corporate)' ,
  `diss_date_accepted` DATE NOT NULL COMMENT 'Datum der mündlichen Prüfung (für Dissertationen)' ,
  `server_date_modified` DATETIME NOT NULL COMMENT 'letzte Änderung des Datensatzes (dc:date.modified)' ,
  `server_date_published` DATETIME NOT NULL COMMENT 'Veröffentlichungsdatum auf Server (dc:date.creation)' ,
  `server_date_unlocking` DATE NOT NULL COMMENT 'Freigabedatum (Embargofrist)' ,
  `server_date_valid` DATE NOT NULL COMMENT 'Gültig bis' ,
  `document_type` ENUM('article', 'book section', 'monograph', 'report', 'doctoral thesis') NOT NULL COMMENT 'Dokumenttyp (type)' ,
  `edition` VARCHAR(25) NOT NULL COMMENT 'Auflage' ,
  `first_page` INT NOT NULL COMMENT 'Seite von' ,
  `last_page` INT NOT NULL COMMENT 'Seite bis' ,
  `publication_status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Bearbeitungsstatus (temp, in review, published)' ,
  `isbn` TEXT NOT NULL COMMENT 'ISBN' ,
  `issn` TEXT NOT NULL COMMENT 'ISSN' ,
  `journal_issue` VARCHAR(25) NOT NULL COMMENT 'Heft' ,
  `journal_volume` VARCHAR(25) NOT NULL COMMENT 'Jahrgang' ,
  `language` VARCHAR(3) NOT NULL COMMENT '(hauptsächliche) Sprache des Dokuments' ,
  `page_number` INT NOT NULL COMMENT 'Seitenzahl' ,
  `place` VARCHAR(255) NOT NULL COMMENT 'Veröffentlichungsort' ,
  `publisher` VARCHAR(255) NOT NULL COMMENT 'Verlag' ,
  `publisher_university` INT NOT NULL COMMENT 'Veröffentlichende Universität' ,
  `range_id` INT NOT NULL COMMENT 'Bereichs-Id (Zugänglichkeit nur auf Campus)' ,
  `reviewed` ENUM('peer', 'editorial', 'open') NOT NULL COMMENT 'Art der Review' ,
  `source` VARCHAR(255) NOT NULL COMMENT 'Bibliographische Daten aus OPUS 3' ,
  `swb_id` VARCHAR(255) NOT NULL COMMENT 'SWB ID' ,
  `completed_year` YEAR NOT NULL COMMENT 'Jahr der Fertigstellung (bei Dissertationen)' ,
  `completed_date` DATE NULL ,
  `published_year` YEAR NOT NULL COMMENT 'Erscheinungsjahr der Primärveröffentlichung' ,
  `published_date` DATE NULL ,
  `non_institute_affiliation` TEXT NOT NULL COMMENT 'Für alles, was kein universitätsinternes Institut im Sinne von Tabelle Institutes ist' ,
  `vg_wort_pixel_url` TEXT NOT NULL COMMENT 'URL auf den VG-Wort-Zählpixel' ,
  PRIMARY KEY (`document_id`) ,
  INDEX fk_Document_license (`licence_id` ASC) ,
  CONSTRAINT `fk_Document_license`
    FOREIGN KEY (`licence_id` )
    REFERENCES `opus400`.`licences` (`licence_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_resource_identifiers`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_resource_identifiers` (
  `document_resources_identifiers_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `document_id` INT NOT NULL COMMENT 'Fremdschlüssel auf DocumentTabelle' ,
  `resource_link_type` ENUM('DOI', 'Handle', 'URN', 'STD-DOI', 'URL', 'CRIS-Link', 'Splash-URL') NOT NULL COMMENT 'Art des Verweises (intern und extern)' ,
  `resource_link_value` TEXT NOT NULL COMMENT 'Verweis' ,
  `resource_Label` TEXT NOT NULL COMMENT 'Anzeigetext für den Verweis' ,
  PRIMARY KEY (`document_resources_identifiers_id`) ,
  INDEX has (`document_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = '\n';


-- -----------------------------------------------------
-- Table `opus400`.`institutes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`institutes` (
  `institutes_id` INT(11) NOT NULL COMMENT 'Primärschlüssel' ,
  `institutes_type` VARCHAR(50) NOT NULL COMMENT 'Art der Einrichtung' ,
  `institutes_name` VARCHAR(255) NOT NULL COMMENT 'Bezeichnung der Einrichtung' ,
  `postal_adress` TEXT NULL ,
  `institutes_site` TEXT NULL ,
  PRIMARY KEY (`institutes_id`) )
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_files`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_files` (
  `document_files_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `document_id` INT(11) NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `file_path_name` TEXT NOT NULL COMMENT 'Pfad und Dateiname' ,
  `file_sort_order` TINYINT(4) NOT NULL COMMENT 'Sortierung der Dateien' ,
  `file_label` TEXT NOT NULL COMMENT 'Anzeigetext für Datei' ,
  `file_type` VARCHAR(255) NOT NULL COMMENT 'Dateityp nach Dublin Core' ,
  `mime_type` VARCHAR(255) NOT NULL COMMENT 'Mime type der Datei' ,
  `file_language` VARCHAR(3) NULL COMMENT 'Sprache der Datei' ,
  PRIMARY KEY (`document_files_id`) ,
  INDEX has (`document_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`file_hashvalues`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`file_hashvalues` (
  `file_hashvalues_id` TINYINT UNSIGNED NOT NULL COMMENT 'Primärschlüssel' ,
  `document_files_id` INT(11) NOT NULL COMMENT 'Fremdschlüssel zur Document_Files Tabelle' ,
  `hash_type` VARCHAR(50) NOT NULL COMMENT 'Art des Hashes' ,
  `hash_value` TEXT NOT NULL COMMENT 'Hashwert ' ,
  PRIMARY KEY (`file_hashvalues_id`, `document_files_id`) ,
  INDEX fk_file_hashvalues_document_files (`document_files_id` ASC) ,
  CONSTRAINT `fk_file_hashvalues_document_files`
    FOREIGN KEY (`document_files_id` )
    REFERENCES `opus400`.`document_files` (`document_files_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_subjects`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_subjects` (
  `document_subject_id` INT NOT NULL COMMENT 'Primärschlüssel' ,
  `document_id` INT(11) NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `subject_type` ENUM('Psyndex Terms', 'DDC', 'SWD', '...') NOT NULL COMMENT 'Art der Erschließung' ,
  `subject_value` VARCHAR(255) NOT NULL COMMENT 'Wert zu subject_type (kontrolliertes/freies Schlagwort, Notation, etc)' ,
  `language` VARCHAR(3) NULL COMMENT 'Sprache des Erschließungssystems' ,
  `external_subject_key` VARCHAR(255) NULL COMMENT 'Identifikator zur Auflösung von Deskriptoren in Fremdsystemen' ,
  PRIMARY KEY (`document_subject_id`) ,
  INDEX opus_subject_FKIndex1 (`document_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` ))
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`document_title_abstracts`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_title_abstracts` (
  `document_title_abstract_id` INT(11) UNSIGNED NOT NULL COMMENT 'Primärschlüssel' ,
  `document_id` INT(11) NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `title_abstract_type` ENUM('Title', 'Abstract', 'Subtitle') NOT NULL COMMENT 'Titel/Abstract des Dokuments' ,
  `title_abstract_value` TEXT NOT NULL COMMENT 'Inhalt zu title_abstract_type' ,
  `title_abstract_language` VARCHAR(3) NOT NULL COMMENT 'Sprache des Titels, Abstracts' ,
  PRIMARY KEY (`document_title_abstract_id`) ,
  INDEX Title_Abstract_FKIndex1 (`document_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` ))
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`persons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`persons` (
  `person_id` INT(11) NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `academic_title` VARCHAR(255) NULL COMMENT 'Akademischer Titel' ,
  `date_of_birth` DATETIME NOT NULL COMMENT 'Geburtsdatum' ,
  `email` VARCHAR(100) NOT NULL COMMENT 'Email-Adresse' ,
  `first_name` VARCHAR(255) NOT NULL COMMENT 'Vorname' ,
  `last_name` VARCHAR(255) NOT NULL COMMENT 'Nachname' ,
  `place_of_birth` VARCHAR(255) NOT NULL COMMENT 'Geburtsort' ,
  PRIMARY KEY (`person_id`) ,
  INDEX last_name (`last_name` ASC) )
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`person_external_keys`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`person_external_keys` (
  `person_external_key_Id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `person_id` INT(11) NOT NULL COMMENT 'Fremdschlüssel zur Person Tabelle' ,
  `type` ENUM('PND', '...') NOT NULL COMMENT 'Art der externen ID (z.B. PND)' ,
  `value` TEXT NOT NULL COMMENT 'Wert' ,
  `resolver` VARCHAR(255) NOT NULL COMMENT 'URL zum Auflösungsmechanismus' ,
  PRIMARY KEY (`person_external_key_Id`) ,
  INDEX Person_External_Key_FKIndex1 (`person_id` ASC) ,
  CONSTRAINT `has`
    FOREIGN KEY (`person_id` )
    REFERENCES `opus400`.`persons` (`person_id` )
    ON DELETE CASCADE
    ON UPDATE CASCADE)
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`link_documents_persons`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`link_documents_persons` (
  `link_document_person_id` INT(11) NOT NULL COMMENT 'Primärschlüssel' ,
  `document_id` INT(11) NOT NULL COMMENT 'Primärschlüssel' ,
  `institutes_id` INT(11) NOT NULL COMMENT 'Fremdschlüssel zur Institute Tabelle' ,
  `role` ENUM('Author', 'Editor', 'Referee', 'Contributor') NOT NULL ,
  `sort_order` TINYINT UNSIGNED NOT NULL COMMENT 'Reihenfolge der Autoren' ,
  PRIMARY KEY (`link_document_person_id`) ,
  INDEX Person_has_Document_FKIndex1 (`link_document_person_id` ASC) ,
  INDEX Person_has_Document_FKIndex2 (`document_id` ASC) ,
  INDEX Rel_06 (`institutes_id` ASC) ,
  CONSTRAINT `Rel_04`
    FOREIGN KEY (`link_document_person_id` )
    REFERENCES `opus400`.`persons` (`person_id` ),
  CONSTRAINT `Rel_05`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` ),
  CONSTRAINT `Rel_06`
    FOREIGN KEY (`institutes_id` )
    REFERENCES `opus400`.`institutes` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
COMMENT = 'Possilbe values for role:\nauthor\nadvisor\neditor\ntranslator'
PACK_KEYS = 0
ROW_FORMAT = DEFAULT;


-- -----------------------------------------------------
-- Table `opus400`.`link_subjects_relations`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`link_subjects_relations` (
  `link_subject_relations_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `document_subject_mother_id` INT NOT NULL COMMENT 'Elternknoten der Beziehung' ,
  `document_subject_daughter_id` INT NOT NULL COMMENT 'Kind der Beziehung' ,
  `relation_type` ENUM('broader', 'narrower', 'related', 'synonym') NOT NULL COMMENT 'Art der Beziehung' ,
  PRIMARY KEY (`link_subject_relations_id`) ,
  INDEX fk_Subject_Relations_Document_Subject (`document_subject_mother_id` ASC) ,
  INDEX fk_Subject_Relations_Document_Subject1 (`document_subject_daughter_id` ASC) ,
  CONSTRAINT `fk_Subject_Relations_Document_Subject`
    FOREIGN KEY (`document_subject_mother_id` )
    REFERENCES `opus400`.`document_subjects` (`document_subject_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_Subject_Relations_Document_Subject1`
    FOREIGN KEY (`document_subject_daughter_id` )
    REFERENCES `opus400`.`document_subjects` (`document_subject_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Abbildung qualifizierter Beziehungen zwischen Schlagworten';


-- -----------------------------------------------------
-- Table `opus400`.`document_patent_informations`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_patent_informations` (
  `patent_information_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `document_id` INT NOT NULL COMMENT 'Fremdschlüssel auf Document Tabelle' ,
  `patent_countries` TEXT NOT NULL COMMENT 'Länder, in denen Patent erteilt wurde' ,
  `patent_date_granted` DATE NOT NULL COMMENT 'Datum der Patenterteilung' ,
  `patent_number` VARCHAR(255) NOT NULL COMMENT 'Patentnummer' ,
  `patent_year_applied` YEAR NOT NULL COMMENT 'Jahr der Antragsstellung' ,
  `patent_application` TEXT NOT NULL COMMENT 'Beschreibung der Anwendung/ des Patents' ,
  PRIMARY KEY (`patent_information_id`) ,
  INDEX fk_Patent_Information_Document (`document_id` ASC) ,
  CONSTRAINT `fk_Patent_Information_Document`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `opus400`.`document_statistics`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_statistics` (
  `document_statistics_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `document_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `statistic_type` TEXT NOT NULL COMMENT 'Art der dokumentbezogenen Statistik' ,
  `statistic_value` TEXT NOT NULL COMMENT 'Kennwert' ,
  `start_survey_period` DATETIME NOT NULL ,
  `end_survey_period` DATETIME NOT NULL ,
  PRIMARY KEY (`document_statistics_id`) ,
  INDEX fk_Document_Statistics_Document (`document_id` ASC) ,
  CONSTRAINT `fk_Document_Statistics_Document`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `opus400`.`document_notes`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_notes` (
  `document_notes_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `document_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `message` TEXT NOT NULL COMMENT 'Mitteilungstext' ,
  `creator` TEXT NOT NULL COMMENT 'Verfasser der Mitteilung' ,
  `scope` ENUM('private', 'public', 'reference') NOT NULL COMMENT 'Sichtbarkeit: intern, extern, Verweis auf andere Dokumentversion ' ,
  PRIMARY KEY (`document_notes_id`) ,
  INDEX fk_Document_Notes_Document (`document_id` ASC) ,
  CONSTRAINT `fk_Document_Notes_Document`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;


-- -----------------------------------------------------
-- Table `opus400`.`document_enrichments`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`document_enrichments` (
  `document_enrichment_id` INT NOT NULL AUTO_INCREMENT COMMENT 'Primärschlüssel' ,
  `document_id` INT NOT NULL COMMENT 'Fremdschlüssel zur Document Tabelle' ,
  `enrichment_type` VARCHAR(255) NOT NULL COMMENT 'Art der Erweiterung' ,
  `enrichment_value` TEXT NOT NULL COMMENT 'Wert der Erweiterung' ,
  PRIMARY KEY (`document_enrichment_id`) ,
  INDEX fk_Document_Enrichment_Document (`document_id` ASC) ,
  CONSTRAINT `fk_Document_Enrichment_Document`
    FOREIGN KEY (`document_id` )
    REFERENCES `opus400`.`documents` (`document_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB
COMMENT = 'Multivalue Tabelle zur unkomplizierten Metadaten-Erweiterung';


-- -----------------------------------------------------
-- Table `opus400`.`link_institutes_relations`
-- -----------------------------------------------------
CREATE  TABLE IF NOT EXISTS `opus400`.`link_institutes_relations` (
  `link_institutes_relation_id` INT NOT NULL AUTO_INCREMENT ,
  `institutes_mother_id` INT NOT NULL ,
  `institutes_daughter_id` INT NOT NULL ,
  `relation_type` ENUM('sub','top') NOT NULL ,
  PRIMARY KEY (`link_institutes_relation_id`) ,
  INDEX fk_link_institutes_relation_institutes (`institutes_mother_id` ASC) ,
  INDEX fk_link_institutes_relation_institutes1 (`institutes_daughter_id` ASC) ,
  CONSTRAINT `fk_link_institutes_relation_institutes`
    FOREIGN KEY (`institutes_mother_id` )
    REFERENCES `opus400`.`institutes` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `fk_link_institutes_relation_institutes1`
    FOREIGN KEY (`institutes_daughter_id` )
    REFERENCES `opus400`.`institutes` (`institutes_id` )
    ON DELETE NO ACTION
    ON UPDATE NO ACTION)
ENGINE = InnoDB;



SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
