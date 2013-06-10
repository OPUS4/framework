--
-- this file was left intentionally empty since no database changes need to be made
--

-- Erlaube NULL fuer YearApplied Feld in Opus_Patent
ALTER TABLE `documents`
    MODIFY COLUMN `year_applied` SMALLINT( 4 ) UNSIGNED ZEROFILL NULL DEFAULT NULL COMMENT 'Year of the application.';

