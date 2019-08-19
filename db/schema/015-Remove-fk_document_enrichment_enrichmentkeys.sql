START TRANSACTION;

-- Remove enrichmentkeys foreign key in table document_enrichments

ALTER TABLE `document_enrichments`
  DROP FOREIGN KEY `fk_document_enrichment_enrichmentkeys`;

ALTER TABLE `document_enrichments`
  DROP INDEX `fk_document_enrichment_enrichmentkeys`;

-- Update database version

TRUNCATE TABLE `schema_version`;
INSERT INTO `schema_version` (`version`) VALUES (15);

COMMIT;
