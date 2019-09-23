START TRANSACTION;

-- Add table "opus_version" OPUSVIER-3577

CREATE TABLE IF NOT EXISTS `opus_version` (
    `version` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Internal version number of OPUS.'
)
COMMENT = 'Holds internal OPUS version for controlling update steps.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (4);

COMMIT;
