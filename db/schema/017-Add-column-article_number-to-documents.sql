START TRANSACTION;

-- add column article_number to table documents

ALTER TABLE `documents`
  ADD COLUMN `article_number` VARCHAR(255) NULL COMMENT 'article number / Aufsatznummer';

-- update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (17);

COMMIT;