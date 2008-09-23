<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library - 
 * Dresden State and University Library, the Bielefeld University Library and 
 * the University Library of Hamburg University of Technology with funding from 
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License 
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51 
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @category	Framework
 * @package		Opus_Collections
 * @author     	Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright  	Copyright (c) 2008, OPUS 4 development team
 * @license    	http://www.gnu.org/licenses/gpl.html General Public License
 * @version    	$Id$
 */

/**
 * Collection role related methods.
 *
 * @category Framework
 * @package  Opus_Collections
 */
class Opus_Collection_Roles {
    
    /**
     * The collection-roles array. 
     * 
     * @var array 
     */
    public $collectionRoles;
    
    /**
     * ID for this collections_roles. 
     * 
     * @var integer 
     */
    public $roles_id;
    
    /**
     * Container for collections_roles table gateway. 
     * 
     * @var object 
     */
    private $collections_roles;
    
    /**
     * Container for collections_roles table metadata. 
     * 
     * @var array 
     */
    private $collections_roles_info;
    
    /**
     * Constructor. 
     */
    public function __construct() {
        $this->collectionRoles          = array();
        $this->collections_roles        = new Opus_Db_CollectionsRoles();
        $this->collections_roles_info   = $this->collections_roles->info();
    }
    
    
    /**
     * Creates a blank collection role array. 
     *
     * @param array(integer => string) $languages (Optional) Array of ISO-Code identifying the languages.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function create($languages = array()) {
        // Clear collection-role array
        $this->collectionRoles = array();
        // New generated collection-role gets temporary ID 0
        $this->roles_id = 0;
        if (is_array($languages) === false) {
            throw new InvalidArgumentException('Given languages parameter is not an array.');
        }
        foreach ($languages as $language) {
            $this->addLanguage($language);
        }
    }
    
    /**
     * Adds a new language to role.
     *
     * @param string $language ISO-Code identifying the language.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function addLanguage($language) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->language($language);
        $collectionRolesRecord = array_fill_keys($this->collections_roles_info['cols'] , null);
        $collectionRolesRecord[$this->collections_roles_info['primary'][2]] = $language;
        $collectionRolesRecord[$this->collections_roles_info['primary'][1]] = $this->roles_id;
        $this->collectionRoles[$language] = $collectionRolesRecord;
    }
    
    
    /**
     * Updating collection-role.
     *
     * @param array(string => array(string => string)) $collectionRolesRecords A collection-role array
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function update($collectionRolesRecords){
        // For every given language
        foreach ($collectionRolesRecords as $language => $collectionRolesRecord) {
            // Is the language code valid for this record?
            if (isset($this->collectionRoles[$language]) === false) {
                throw new InvalidArgumentException("Unknown language code '$language'.");
            }
            // For every given attribute
            foreach ($collectionRolesRecord as $attribute => $content) {
                if (in_array($attribute, $this->collections_roles_info['primary']) === true) {
                    throw new InvalidArgumentException('Primary key attributes may not be updated.');
                } else if (in_array($attribute, $this->collections_roles_info['cols']) === false) {
                    throw new InvalidArgumentException("Unknown attribute '$attribute'.");
                }
                $this->collectionRoles[$language][$attribute] = $content;
            }
            // Setting the ID 
            $this->collectionRoles[$language][$this->collections_roles_info['primary'][1]] = $this->roles_id;
        }
    }
    
    /**
     * Load collection-role from database.
     *
     * @param   integer $roles_id Number identifying the role.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function load($roles_id) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($roles_id);
        $collectionRoles = $this->collections_roles
                                    ->fetchAll($this->collections_roles
                                                    ->select()
                                                    ->where($this->collections_roles_info['primary'][1] . ' = ?', $roles_id))
                                    ->toArray();
        // Replace numeric index by language codes
        foreach ($collectionRoles as $numIndex => $record) {
            $this->collectionRoles[$record[$this->collections_roles_info['primary'][2]]] = $record;
        }
        // Has the collection-role already an ID?
        if ($this->roles_id > 0) {
            // Then overwrite the loaded data
            foreach ($this->collectionRoles as $index => $record) {
                $this->collectionRoles[$index][$this->collections_roles_info['primary'][1]] = $this->roles_id;
            }
        } else {
            // Otherwise take the ID of the loaded collection-content as the collection-content ID
            $this->roles_id = $roles_id;
        }
    }
    
    /**
     * Save collection-role to database.
     *
     * @throws  Exception On failed database access.
     * @return void
     */
    public function save() {
        try {
            // Is the collection-role a complete new one?
            if ($this->roles_id === 0) {
                // Find out valid ID for the new record
                $db = Zend_Registry::get('db_adapter');
                $selectMaxId = $db->select()
                                ->from($this->collections_roles_info['name'],
                                'MAX(' . $this->collections_roles_info['primary'][1] . ')');
                $this->roles_id = ($db->fetchOne($selectMaxId) + 1);
            }
            // Delete outdated database records
            $this->collections_roles->delete($this->collections_roles_info['primary'][1] . ' = ' . $this->roles_id);
            // Insert updated database records
            foreach ($this->collectionRoles as $language => $record) {
                foreach ($record as $index => $attribute) {
                    if ($attribute === null) {
                        unset ($record[$index]);
                    }
                }
                $record[$this->collections_roles_info['primary'][1]] = $this->roles_id;
                $this->collections_roles
                     ->insert($record);
            }
        } catch (Exception $e) {
            $db = Zend_Registry::get('db_adapter');
            $db->rollBack();
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
    
    
    /**
     * Create database tables "collections_contents_X", "collections_replacement_X" and
     * "collections_structure_X" where X is the current roles_id.
     *
     * @throws  Exception On failed database access.
     * @return void
     */
    public function createDatabaseTables() {
        // Fetch DB adapter
        $db = Zend_Registry::get('db_adapter');
        
        $tabellenname = 'collections_contents_' . $this->roles_id;
        $query = 'CREATE TABLE ' . $db->quoteIdentifier($tabellenname) . ' (
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            `collections_language` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT "ger",
            `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
            PRIMARY KEY ( `collections_id` , `collections_language` ) 
            ) ENGINE = MYISAM';
        
        try {
            $db->query($query);
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception('Error creating collection content table: ' . $e->getMessage());
        }
        
        $tabellenname = 'collections_replacement_' . $this->roles_id;
        $query = 'CREATE  TABLE ' . $db->quoteIdentifier($tabellenname) . ' (
              `collections_replacement_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` INT UNSIGNED NOT NULL,
              `replacement_for_id` INT UNSIGNED,
              `replacement_by_id` INT UNSIGNED,
              `current_replacement_id` INT UNSIGNED,
              PRIMARY KEY (`collections_replacement_id`) ,
              INDEX fk_link_collections_' . $this->roles_id . ' (`collections_id` ASC) ,
              INDEX fk_link_collections_replacement_for_' . $this->roles_id . ' (`replacement_for_id` ASC) ,
              INDEX fk_link_collections_replacement_by_' . $this->roles_id . ' (`replacement_by_id` ASC) ,
              INDEX fk_link_collections_current_replacement_' . $this->roles_id . ' (`current_replacement_id` ASC) ,
              CONSTRAINT `fk_link_collections_' . $this->roles_id . '`
                FOREIGN KEY (`collections_id` )
                REFERENCES `collections_contents_' . $this->roles_id . '` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_replacement_for_' . $this->roles_id . '`
                FOREIGN KEY (`replacement_for_id` )
                REFERENCES `collections_contents_' . $this->roles_id . '` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_replacement_by_' . $this->roles_id . '`
                FOREIGN KEY (`replacement_by_id` )
                REFERENCES `collections_contents_' . $this->roles_id . '` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_current_replacement_' . $this->roles_id . '`
                FOREIGN KEY (`current_replacement_id` )
                REFERENCES `collections_contents_' . $this->roles_id . '` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;';
        try {
            $db->query($query);
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception('Error creating collection replacement table: ' . $e->getMessage());
        }
        
        $tabellenname = 'collections_structure_' . $this->roles_id;
        $query = 'CREATE  TABLE ' . $db->quoteIdentifier($tabellenname) . ' (
              `collections_structure_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` int(10) UNSIGNED NOT NULL ,
              `left` int(10) UNSIGNED NOT NULL ,
              `right` int(10) UNSIGNED NOT NULL ,
              `visible` tinyint(1) NOT NULL default 1,
              PRIMARY KEY (`collections_structure_id`) ,
              INDEX fk_collections_structure_collections_contents_' . $this->roles_id . ' (`collections_id` ASC) ,
              CONSTRAINT `fk_collections_structure_collections_contents_' . $this->roles_id . '`
                FOREIGN KEY (`collections_id` )
                REFERENCES `collections_contents` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;';
        try {
            $db->query($query);
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception('Error creating collection structure table: ' . $e->getMessage());
        }
    }
}