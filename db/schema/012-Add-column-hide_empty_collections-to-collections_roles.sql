START TRANSACTION;

-- add column hide_empty_collections to table collections_roles

ALTER TABLE `collections_roles`
  ADD COLUMN `hide_empty_collections` BOOLEAN NOT NULL DEFAULT 0 COMMENT 'Hide empty collections in collection browsing.';

-- update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (12);

COMMIT;