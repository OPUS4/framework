START TRANSACTION;

-- Remove table languages

DROP TABLE `languages`;

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (25);

COMMIT;
