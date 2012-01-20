-- ----------------------------------------------------------------------
-- UPDATE SERIES! (See OPUSVIER-2131)
-- 
-- Make old series collections (level = 1) new series and store 
-- id, name, visible and sort_order
-- ----------------------------------------------------------------------
INSERT INTO document_series (id, title, visible, sort_order)
  SELECT `id`, `name`, `sort_order`, `visible`  FROM `collections` WHERE `role_id` = (SELECT id FROM `collections_roles` WHERE name="Series") AND `parent_id` = (SELECT id  FROM `collections` WHERE `role_id` =  (SELECT id FROM `collections_roles` WHERE name="Series") AND `parent_id` IS NULL);

-- ----------------------------------------------------------------------
-- UPDATE SERIES! (See OPUSVIER-2131)
-- 
-- Make old series collections (> level 1) new series and store 
-- id, name, visible and sort_order
-- ----------------------------------------------------------------------

-- Temporarily store Collection IDs in special table
CREATE TABLE temp(id INT(10));
INSERT INTO temp(id)
  SELECT `id` FROM `collections` WHERE `role_id` = (SELECT id FROM `collections_roles` WHERE name="Series") AND `parent_id` <> (SELECT id  FROM `collections` WHERE `role_id` =  (SELECT id FROM `collections_roles` WHERE name="Series") AND `parent_id` IS NULL);

-- Procedure seriesname:
-- Updates the name of subcollections of series by string concatenation with names of parent nodes.
-- @param IN currid current collection id
-- @param IN realid start collection id
-- @param IN root ID of collection roode node
-- @param OUT longname result of concatening
delimiter //
CREATE PROCEDURE seriesname (OUT longname TEXT, IN currid INT(10), IN realid INT(10), IN root INT(10))
BEGIN  
  DECLARE currname VARCHAR(255) DEFAULT '';
  DECLARE until_id INT(10);
  SET longname = '';
  REPEAT     
    SELECT `name`, `parent_id` INTO currname, until_id FROM `collections` WHERE `id` = currid;    
    SET currid=until_id;
    IF longname='' THEN SET longname = currname;    
    ELSE SET longname = CONCAT(currname, ' - ', longname);
    END IF;
  UNTIL until_id = root END REPEAT;
  UPDATE `collections` SET `name` = longname WHERE `id` = realid;
END;
//

-- Procedure iterateSeriesname: 
-- calls procedure seriesname for every id in the temporary table temp
delimiter //
CREATE PROCEDURE iterateSeriesname ()
BEGIN  
  DECLARE currentId INT(10);
  DECLARE root INT(10);
  DECLARE count INT;
  SET @c = 0;
  SELECT `id` INTO root FROM `collections` WHERE `role_id` =  (SELECT `id` FROM `collections_roles` WHERE name='series') AND `parent_id` IS NULL;  
  SELECT count(id) INTO count FROM temp;
  REPEAT
     SELECT `id` INTO currentId FROM `temp` ORDER BY `id` DESC LIMIT 1;         
     CALL seriesname(@a, currentId, currentId, root);
     DELETE FROM `temp` WHERE `id` = currentId;
     SET @c = @c + 1;
  UNTIL @c > count END REPEAT;  
END;
//

delimiter ;

-- call iterateSeriesname and update every name of subcollections of series 
CALL iterateSeriesname();

-- insert subcollections into the new series table
INSERT INTO document_series (id, title, visible, sort_order)
   SELECT `id`, `name`, `sort_order`, `visible`  FROM `collections` WHERE `role_id` = (SELECT id FROM `collections_roles` WHERE name="Series") AND `parent_id` <> (SELECT id  FROM `collections` WHERE `role_id` =  (SELECT id FROM `collections_roles` WHERE name="Series") AND `parent_id` IS NULL);

-- cleanup 
DROP Table temp;
DROP PROCEDURE iterateSeriesname;
DROP PROCEDURE seriesname;

-- ----------------------------------------------------------------------
-- UPDATE SERIES! (See OPUSVIER-2131)
-- 
-- Assign new series to the same documents as old series collections
-- id, name, visible and sort_order
-- ----------------------------------------------------------------------

-- create temp table and insert all series documents that have a number and those without a number
CREATE TABLE temp (`series_id` INT, `document_id` INT, `number` VARCHAR(265));
INSERT INTO temp (document_id, series_id, number)
    SELECT document_id, collection_id AS series_id, value AS number  FROM `link_documents_collections` LEFT JOIN `document_identifiers` USING (document_id) WHERE `role_id` = (SELECT id FROM `collections_roles` WHERE name="series" and type="serial");
INSERT INTO temp (document_id, series_id)
    SELECT document_id, collection_id AS series_id  FROM `link_documents_collections` WHERE `role_id` = (SELECT id FROM `collections_roles` WHERE name="series") AND document_id NOT IN (SELECT document_id FROM `link_documents_collections` LEFT JOIN `document_identifiers` USING (document_id) WHERE `role_id` = (SELECT id FROM `collections_roles` WHERE name="series" and type="serial"));

CREATE TABLE temp2(`series_id` INT);
INSERT INTO temp2
    SELECT `series_id` FROM temp GROUP BY (series_id) HAVING 2<= count(*);

-- Procedure setNumber:
-- Creates missing numbers for series and documents
delimiter //
CREATE PROCEDURE setNumber()
BEGIN             
    DECLARE global INT;
    DECLARE currId INT;
    DECLARE currMax INT; 
    SELECT count(*) INTO global FROM `temp2`;
    SET @outerCount = 0;
    
    IF global>0 THEN 
    
         REPEAT 
             SELECT `series_id` INTO currId FROM `temp2` ORDER BY `series_id` DESC LIMIT 1;                        
              SELECT count(*) INTO currMax FROM `temp` WHERE `series_id` = currId;        
              SET @innerCount = 1;
        
              REPEAT            
                   UPDATE temp SET `number` = @innerCount WHERE `number` IS NULL AND `series_id`=currId LIMIT 1;
                   SET @innerCount = @innerCount + 1;                   
              UNTIL @innerCount = currMax+1 END REPEAT;
                
             DELETE FROM `temp2` WHERE `series_id` = currId;
             SET @outerCount = @outerCount + 1;
         UNTIL @outerCount = global END REPEAT;
    END IF;
END;
//

delimiter ;

CALL setNumber();

-- Another Nulls are set to 1 and alle temp rows are inserted in the final table.
UPDATE temp SET number=1 WHERE number IS NULL;
INSERT INTO link_documents_series (series_id, document_id, number)
   SELECT * FROM temp;

-- Cleanup
DROP PROCEDURE setNumber;
DROP TABLE temp;
DROP TABLE temp2;

-- ----------------------------------------------------------------------
-- UPDATE SERIES! (See OPUSVIER-1796)
-- 
-- Cleanup: OPUSVIER-2229
-- Set old Collection_Role 'series' and all of its visible flags to 0 
-- Make all Subcollection invisible
-- Delete old IdentifierSerial in document_identifiers
-- ----------------------------------------------------------------------
UPDATE `collections_roles` SET `visible` = '0', `visible_browsing_start` = '0', `visible_frontdoor` = '0', `visible_oai` = '0' WHERE `name`='series';
UPDATE `collections` SET `visible` = '0' WHERE `role_id`=(SELECT id FROM `collections_roles` WHERE `name` = 'Series');
CREATE TABLE temp (id INT);
INSERT INTO temp
   SELECT id FROM `link_documents_collections` LEFT JOIN `document_identifiers` a USING (document_id) WHERE `role_id` = (SELECT id FROM `collections_roles` WHERE name="series") AND a.type="serial";
DELETE FROM `document_identifiers` WHERE id IN (SELECT * FROM temp);
DROP TABLE temp;