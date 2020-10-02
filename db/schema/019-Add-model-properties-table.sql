START TRANSACTION;

-- Add tables for storing model properties (OPUSVIER-4368)

CREATE TABLE IF NOT EXISTS `model_types` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
    `type` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Type of supported models',
    PRIMARY KEY (`id`)
)
    COMMENT = 'Model types supported by properties.';

CREATE TABLE IF NOT EXISTS `propertykeys` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key',
    `name` VARCHAR(50) NOT NULL UNIQUE COMMENT 'Property key name',
    PRIMARY KEY (`id`)
)
    COMMENT = 'Valid keys of model properties';

CREATE TABLE IF NOT EXISTS `model_properties` (
    `model_type_id` INT UNSIGNED NOT NULL COMMENT 'ID of model type',
    `model_id` INT UNSIGNED NOT NULL COMMENT 'ID of model object',
    `key_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to propertykeys.id',
    `value` MEDIUMTEXT NOT NULL COMMENT 'Value of property',
    PRIMARY KEY (`model_type_id`, `model_id`, `key_id`),
    CONSTRAINT `fk_model_properties_propertykeys`
        FOREIGN KEY (`key_id`)
            REFERENCES `propertykeys` (`id`)
            ON DELETE CASCADE,
    CONSTRAINT `fk_model_properties_model_types`
        FOREIGN KEY (`model_type_id`)
            REFERENCES `model_types` (`id`)
            ON DELETE CASCADE
)
    COMMENT = 'Holds internal properties for OPUS model objects.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (19);

COMMIT;
