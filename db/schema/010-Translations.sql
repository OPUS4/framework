START TRANSACTION;

-- Add tables for storing translations

-- TODO cascade deleting translationkey

CREATE TABLE IF NOT EXISTS `translationkeys` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.',
  `key` VARCHAR(100) NOT NULL COMMENT 'Key for translation.',
  `module` VARCHAR(50) DEFAULT NULL COMMENT 'Name of source module.',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`key`, `module`)
)
COMMENT = 'Stores keys for custom translations.';

CREATE TABLE IF NOT EXISTS `translations` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT COMMENT 'Primary key.',
  `key_id` INT UNSIGNED NOT NULL COMMENT 'Foreign key to: translationskeys.id',
  `locale` VARCHAR(2) NOT NULL COMMENT 'Locale of translation.',
  `value` TEXT NOT NULL COMMENT 'Translated text.',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`key_id`, `locale`),
  CONSTRAINT `fk_translationkeys`
    FOREIGN KEY (`key_id`)
    REFERENCES `translationkeys` (`id`)
    ON DELETE CASCADE
)
COMMENT = 'Stores custom translations.';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (10);

COMMIT;
