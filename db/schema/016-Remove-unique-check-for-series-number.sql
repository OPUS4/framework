START TRANSACTION;

-- Remove unique constraint for series numbers

ALTER TABLE `link_documents_series`
    DROP INDEX `series_id`;

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (16);

COMMIT;
