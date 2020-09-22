START TRANSACTION;

-- Add table "model_properties" OPUSVIER-4368

CREATE TABLE IF NOT EXISTS `model_properties` (
    `model_type` ENUM('document', 'file', 'identifier') NOT NULL COMMENT 'Type of model object',
    `model_id` INT UNSIGNED NOT NULL COMMENT 'ID of model object',
    `key_name` VARCHAR(30) NOT NULL COMMENT 'Name of property',
    `value` MEDIUMTEXT NOT NULL COMMENT 'Value of property',
    PRIMARY KEY (`model_type`, `model_id`, `key_name`)
)
    COMMENT = 'Holds internal properties for OPUS model objects.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (19);

COMMIT;
