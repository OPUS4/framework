SET @OLD_UNIQUE_CHECKS = @@UNIQUE_CHECKS, UNIQUE_CHECKS = 0;
SET @OLD_FOREIGN_KEY_CHECKS = @@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS = 0;
SET @OLD_SQL_MODE = @@SQL_MODE, SQL_MODE = 'STRICT_TRANS_TABLES,NO_AUTO_VALUE_ON_ZERO';
SET @OLD_AUTOCOMMIT = @@AUTOCOMMIT, AUTOCOMMIT = 0;
SET @OLD_TIME_ZONE = @@TIME_ZONE, TIME_ZONE = "+00:00";

-- Schema changes

START TRANSACTION;

-- Add columns for controlling assignment of documents to collection roles (OPUSVIER-3154)

ALTER TABLE `collections_roles`
  ADD COLUMN `is_classification` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Collection role is a classification (1=yes, 0=no).',
  ADD COLUMN `assign_root` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Documents can be assigned to root collection (1=yes, 0=no).',
  ADD COLUMN `assign_leaves_only` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0
  COMMENT 'Documents can only be assigned to leaf nodes (1=yes, 0=no).';

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (6);

COMMIT;

-- Reset settings

SET TIME_ZONE = @OLD_TIME_ZONE;
SET SQL_MODE = @OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS = @OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS = @OLD_UNIQUE_CHECKS;
SET AUTOCOMMIT = @OLD_AUTOCOMMIT;
