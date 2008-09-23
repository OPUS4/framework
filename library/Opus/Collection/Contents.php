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
 * Collection content attribute related methods.
 *
 * @category Framework
 * @package  Opus_Collection
 */
class Opus_Collection_Contents {
    
    /**
     * The collection-content array
     */
    public $collectionContents;

    /**
     * ID for this collection-content
     */
    public $collections_id;

    /**
     * Container for collections_contents table gateway
     */
    private $collections_contents;

    /**
     * Container for collections_contents table metadata
     */
    private $collections_contents_info;

    /**
     * Container for identifying attribute
     */
    private $collectionsIdentifier;

    /**
     * ID for this collections_roles
     */
    private $role_id;

    /**
     * Container for validation object
     */
    private $validation;
    
    /**
     * Constructor. 
     *
     * @param string|integer $ID Number identifying the collection tree (role) 
     *                            or 'institute' for the institutes tree.
     * @return void
     */
    public function __construct($ID) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->constructorID($ID);
        if ($ID === 'institute') {
            $this->collectionsIdentifier    = 'institutes_id';
            $this->collections_contents     = new Opus_Db_InstitutesContents();
        } else {
            $this->collectionsIdentifier    = 'collections_id';
            $this->collections_contents     = new Opus_Db_CollectionsContents($ID);
        }
        $this->collectionContents           = array();
        $this->collections_contents_info    = $this->collections_contents->info();
        $this->role_id                      = $ID;
    }
    
    /**
     * Creates a collection-content array. 
     *
     * @param   array $languages (Optional) Array of ISO-Code identifying the languages.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function create($languages = array()) {
        // Clear collection-content array
        $this->collectionContents = array();
        // New generated collection-content gets temporary ID 0
        $this->collections_id = 0;
        if (is_array($languages) === false) {
            throw new InvalidArgumentException('Given languages parameter is not an array.');
        }
        foreach ($languages as $language) {
            $this->addLanguage($language);
        }
    }
    
    /**
     * Adds a new language to collection-content.
     *
     * @param   string $language ISO-Code identifying the language.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function addLanguage($language) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->language($language);
        $collectionContentsRecord = array_fill_keys($this->collections_contents_info['cols'] , null );
        $collectionContentsRecord[$this->collections_contents_info['primary'][2]] = $language;
        $collectionContentsRecord[$this->collections_contents_info['primary'][1]] = $this->collections_id;
        $this->collectionContents[$language] = $collectionContentsRecord;
    }
    
    /**
     * Updating collection-content.
     *
     * @param   array $collectionContentsRecords A collection-content array
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function update($collectionContentsRecords){
        // For every given language
        foreach ($collectionContentsRecords as $language => $collectionContentsRecord) {
            // Is the language code valid for this record?
            if (isset($this->collectionContents[$language]) === false) {
                throw new InvalidArgumentException("Unknown language code '$language'.");
            }
            // For every given attribute
            foreach ($collectionContentsRecord as $attribute => $content) {
                if (in_array($attribute, $this->collections_contents_info['primary']) === true) {
                    throw new InvalidArgumentException('Primary key attributes may not be updated.');
                } else if (!in_array($attribute, $this->collections_contents_info['cols'])) {
                    throw new InvalidArgumentException("Unknown attribute '$attribute'.");
                }
                $this->collectionContents[$language][$attribute] = $content;
            }
            // Setting the ID 
            $this->collectionContents[$language][$this->collections_contents_info['primary'][1]] = $this->collections_id;
        }
    }
    
    /**
     * Load collection-content from database.
     *
     * @param   integer $collections_id Number identifying the specific collection-content.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function load($collections_id) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($collections_id);
        $collectionContents = $this->collections_contents
                                    ->fetchAll($this->collections_contents
                                                    ->select()
                                                    ->where($this->collections_contents_info['primary'][1] . ' = ?', $collections_id))
                                    ->toArray();
        // Replace numeric index by language codes
        foreach ($collectionContents as $numIndex => $record) {
            $this->collectionContents[$record[$this->collections_contents_info['primary'][2]]] = $record;
        }
        // Has the collection-content already an ID?
        if ($this->collections_id > 0) {
            // Then overwrite the loaded data
            foreach ($this->collectionContents as $index => $record) {
                $this->collectionContents[$index][$this->collections_contents_info['primary'][1]] = $this->collections_id;
            }
        } else {
            // Otherwise take the ID of the loaded collection-content as the collection-content ID
            $this->collections_id = $collections_id;
        }
    }
    
    /**
     * Save collection-content to database.
     *
     * @throws  Exception   On failed database access.
     * @return void
     */
    public function save() {
        try {
            // Is the collection-content a complete new one?
            if ($this->collections_id === 0) {
                // Find out valid ID for the new record
                $db = Zend_Registry::get('db_adapter');
                $selectMaxId = $db->select()
                                ->from($this->collections_contents_info['name'],
                                'MAX(' . $this->collections_contents_info['primary'][1] . ')')
                ;
                $this->collections_id = ($db->fetchOne($selectMaxId) + 1);
            }
            // Delete outdated database records
            $this->collections_contents->delete($this->collections_contents_info['primary'][1] . ' = '.$this->collections_id);
            // Insert updated database records
            foreach ($this->collectionContents as $language => $record) {
                $record[$this->collections_contents_info['primary'][1]] = $this->collections_id;
                $this->collections_contents
                     ->insert($record);
            }
        } catch (Exception $e) {
            $db = Zend_Registry::get('db_adapter');
            $db->rollBack();
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
}