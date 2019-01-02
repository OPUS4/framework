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
