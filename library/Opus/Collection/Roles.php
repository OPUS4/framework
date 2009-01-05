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
    private $collectionRoles;
    
    /**
     * ID for this collections_roles. 
     * 
     * @var integer 
     */
    private $roles_id;
    
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
        $this->roles_id                 = 0;
    }
    
    /**
     * Returns collection roles array.
     *
     * @return array
     */
    public function getCollectionRoles() {
        return $this->collectionRoles;
    }
    
    /**
     * Returns roles_id.
     *
     * @return integer
     */
    public function getRolesID() {
        return (int) $this->roles_id;
    }
    
    
    /**
     * Updating collection-role.
     *
     * @param array(string => array(string => string)) $collectionRolesRecords A collection-role array
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function update(array $collectionRolesRecords){
        // For every given attribute
        foreach ($collectionRolesRecords as $attribute => $content) {
            if (in_array($attribute, $this->collections_roles_info['primary']) === true) {
                throw new InvalidArgumentException('Primary key attributes may not be updated.');
            } else if (in_array($attribute, $this->collections_roles_info['cols']) === false) {
                throw new InvalidArgumentException("Unknown attribute '$attribute'.");
            }
            $this->collectionRoles[$attribute] = $content;
        }
        // Setting the ID 
        $this->collectionRoles[$this->collections_roles_info['primary'][1]] = $this->roles_id;
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
        $this->validation->constructorID($roles_id);
        $cr = $this->collections_roles
                                    ->fetchAll($this->collections_roles
                                                    ->select()
                                                    ->where($this->collections_roles_info['primary'][1] . ' = ?', $roles_id))
                                    ->toArray();
        if (true === empty($cr)) {
            throw new InvalidArgumentException("Collection Role with ID $roles_id not found.");                                   
        }
        $this->collectionRoles = $cr[0];                                    
        
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
     * Fetch all collection-roles from database.
     *
     * @param   boolean $alsoHidden (Optional) Flag: Fetch also hidden roles.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    public function getAllRoles($alsoHidden = false) {
        
        // Argument validation
        if (false === is_bool($alsoHidden)) {
            throw new InvalidArgumentException('AlsoHidden flag must be boolean.');
        }
        
        if ($alsoHidden === true) {
            $allCollectionRoles = $this ->collections_roles
                                        ->fetchAll($this ->collections_roles->select()
                                        ->order('position'))
                                        ->toArray();
        } else {
            $allCollectionRoles = $this ->collections_roles
                                        ->fetchAll($this ->collections_roles->select()
                                        ->order('position')
                                        ->where('visible = ?', 1))
                                        ->toArray();
        }
        return $allCollectionRoles;
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
            foreach ($this->collectionRoles as $index => $attribute) {
                if ($attribute === null) {
                    unset ($this->collectionRoles[$index]);
                }
            }
            $this->collectionRoles[$this->collections_roles_info['primary'][1]] = $this->roles_id;
            $this->collections_roles
                 ->insert($this->collectionRoles);
        } catch (Exception $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
    
    /**
     * Create database tables "collections_contents_X", "collections_replacement_X" and
     * "collections_structure_X" where X is the current roles_id.
     *
     * @param array(array)                             $content_fields Array with collection_role database records.
     * @throws  Exception On failed database access.
     * @return void
     */
    public function createDatabaseTables(array $content_fields = array(array(
                                              'name' => 'name',
                                              'type' => 'VARCHAR',
                                              'length' => 255
                                         ))) {
        // Fetch DB adapter
        $db = Zend_Registry::get('db_adapter');

        $tabellenname = 'link_documents_collections_' . $this->roles_id;
        $query = 'CREATE TABLE ' . $db->quoteIdentifier($tabellenname) . ' (
            `link_documents_collections_id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            `documents_id` INT( 11 ) UNSIGNED NOT NULL ,
            PRIMARY KEY ( `link_documents_collections_id` ) 
            ) ENGINE = InnoDB'
            ;
        
        try {
            $db->query($query);
        } catch (Exception $e) {
            throw new Exception('Error creating collection document linking table: ' . $e->getMessage());
        }
        
        
        $tabellenname = 'collections_contents_' . $this->roles_id;
        $query = 'CREATE TABLE ' . $db->quoteIdentifier($tabellenname) . ' (
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            PRIMARY KEY ( `collections_id` ) 
            ) ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci'
            ;
        
        try {
            $db->query($query);
            $db->setTablePrefix('');
            foreach ($content_fields as $content_field) {
                $db->addField($tabellenname, $content_field);
            }
        } catch (Exception $e) {
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
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;';
        try {
            $db->query($query);
        } catch (Exception $e) {
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
                REFERENCES `collections_contents_' . $this->roles_id . '` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;';
        try {
            $db->query($query);
        } catch (Exception $e) {
            throw new Exception('Error creating collection structure table: ' . $e->getMessage());
        }
    }


    /**
     * Shift the positions of the roles for inserting/deleting
     *
     * @param   integer $from    Position from which should be shifted.
     * @param   boolean $shiftup (Optional) Incremental or decremental shifting.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function shiftPositions($from, $shiftup = true) {
        if (false === is_int($from)) {
            throw new InvalidArgumentException('Shifting position must be integer');
        } 
        if ($from < 0) {
            throw new InvalidArgumentException('Shifting position must be positive integer');
        } 
        $db = Zend_Registry::get('db_adapter');
        if (true === $shiftup) {
            $db->query('UPDATE collections_roles SET `position` = `position`+1 WHERE `position` >= ' . (int) $from);
        } else {
            $db->query('UPDATE collections_roles SET `position` = `position`-1 WHERE `position` >= ' . (int) $from);
        }
        
    }

    
    /**
     * Find out the lowest free position for a role
     *
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer First free position
     */
    public function nextPosition() {
            $maxPosition = $this->collections_roles
                                        ->fetchRow($this->collections_roles->select()
                                        ->from($this->collections_roles, array('max' => 'max(position)'))
                                        ->order('position'))
                                        ->toArray();
            return($maxPosition['max'] + 1);
    }
    
    
    
}