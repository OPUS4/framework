SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0;
SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0;
SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='STRICT_TRANS_TABLES';
SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT, AUTOCOMMIT=0;

START TRANSACTION;

-- OPUSVIER-3415 Fix values without comma for display fields of collection roles.
UPDATE collections_roles SET display_browsing = "Name,Number" WHERE display_browsing = "NameNumber";
UPDATE collections_roles SET display_browsing = "Number,Name" WHERE display_browsing = "NumberName";
UPDATE collections_roles SET display_frontdoor = "Name,Number" WHERE display_frontdoor = "NameNumber";
UPDATE collections_roles SET display_frontdoor = "Number,Name" WHERE display_frontdoor = "NumberName";

COMMIT;

SET SQL_MODE=@OLD_SQL_MODE;
SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;
SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS;
SET AUTOCOMMIT=@OLD_AUTOCOMMIT;

