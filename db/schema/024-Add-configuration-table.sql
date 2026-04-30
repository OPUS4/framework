START TRANSACTION;

-- Add table for storing local configuration options

CREATE TABLE IF NOT EXISTS `configuration` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
    `option_key` VARCHAR(255) NOT NULL UNIQUE COMMENT 'Full name of option',
    `option_value` TEXT NOT NULL COMMENT 'Value of option',
    PRIMARY KEY (`id`)
)
    COMMENT = 'Editable configuration options';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (24);

COMMIT;