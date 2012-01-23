SET AUTOCOMMIT = 0;
SET sql_mode = 'STRICT_TRANS_TABLES';
START TRANSACTION;

ALTER TABLE `document_xml_cache`
  ENGINE = InnoDB
  COMMENT = 'Caches XML for Opus_Document objects.';


-- Change YEAR fields to UNSIGNED SMALLINT (see OPUSVIER-1511)
-- MYSQL YEAR only allows values from 1901 to 2155
ALTER TABLE  `documents` CHANGE  `completed_year`  `completed_year` YEAR( 4 ) NULL DEFAULT NULL;
UPDATE `documents` SET completed_year = NULL WHERE completed_year = '0000';
ALTER TABLE  `documents` CHANGE  `completed_year`  `completed_year` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL;

ALTER TABLE  `documents` CHANGE  `published_year`  `published_year` YEAR( 4 ) NULL DEFAULT NULL;
UPDATE `documents` SET published_year = NULL WHERE published_year = '0000';
ALTER TABLE  `documents` CHANGE  `published_year`  `published_year` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL;

ALTER TABLE  `document_patents` CHANGE  `year_applied`  `year_applied` YEAR( 4 ) NULL DEFAULT NULL;
UPDATE `document_patents` SET year_applied = NULL WHERE year_applied = '0000';
ALTER TABLE  `document_patents` CHANGE  `year_applied`  `year_applied` SMALLINT( 4 ) UNSIGNED ZEROFILL NOT NULL;

-- Change NULL constraints on date fields (see OPUSVIER-1334)
ALTER TABLE  `documents` CHANGE  `server_date_published`  `server_date_published` VARCHAR( 50 ) NULL;

COMMIT;