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
 * @package  Opus_Collections
 */
class Opus_Collection_Contents {

    /**
     * The collection-content array
     *
     * @var array
     */
    private $collectionContents;

    /**
     * ID for this collection-content
     *
     * @var integer
     */
    private $collections_id;

    /**
     * Container for collections_contents table gateway
     *
     * @var object
     */
    private $collections_contents;

    /**
     * Container for collections_contents table metadata
     *
     * @var array
     */
    private $collections_contents_info;

    /**
     * Container for identifying attribute
     *
     * @var string
     */
    private $collectionsIdentifier;

    /**
     * ID for this collections_roles
     *
     * @var integer
     */
    private $role_id;

    /**
     * Container for validation object
     *
     * @var object
     */
    private $validation;

    /**
     * Constructor.
     *
     * @param string|integer $ID Number identifying the collection tree (role)
     *                           or 'institute' for the institutes tree.
     */
    public function __construct($ID) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->constructorID($ID);
        $this->collectionsIdentifier    = 'id';
        if ($ID === 'institute') {
            //$this->collectionsIdentifier    = 'id';
            $this->collections_contents     = new Opus_Db_InstitutesContents();
        } else {
            // For throwing Inv Arg Exception on non existing roles IDs
            $ocr  = new Opus_Collection_Roles();
            $ocr->load($ID);
            //$this->collectionsIdentifier    = 'collections_id';
            $this->collections_contents     = new Opus_Db_CollectionsContents((int) $ID);
        }
        $this->collectionContents           = array();
        $this->collections_contents_info    = $this->collections_contents->info();
        $this->role_id                      = $ID;
        $this->collections_id               = 0;
    }

    /**
     * Getter ID.
     *
     * @return integer
     */
    public function getCollectionsID() {
        return (int) $this->collections_id;
    }

    /**
     * Getter collectionContents.
     *
     * @return array
     */
    public function getCollectionContents() {
        return $this->collectionContents;
    }


    /**
     * Updating collection-content.
     *
     * @param array(string => array(string => string)) $collectionContentsRecords A collection-content array
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function update(array $collectionContentsRecords){

        // For every given attribute
        foreach ($collectionContentsRecords as $attribute => $content) {
            if (in_array($attribute, $this->collections_contents_info['primary']) === true) {
                throw new InvalidArgumentException('Primary key attributes may not be updated.');
            } else if (in_array($attribute, $this->collections_contents_info['cols']) === false) {
                throw new InvalidArgumentException("Unknown attribute '$attribute'.");
            }
            $this->collectionContents[$attribute] = $content;
        }
        // Setting the ID
        $this->collectionContents[$this->collections_contents_info['primary'][1]] = $this->collections_id;
    }

    /**
     * Load collection-content from database.
     *
     * @param integer $collections_id Number identifying the specific collection-content.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function load($collections_id) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($collections_id);
        $this->collectionContents = $this->collections_contents
                                    ->fetchAll($this->collections_contents
                                                    ->select()
                                                    ->where($this->collections_contents_info['primary'][1] . ' = ?', $collections_id))
                                    ->toArray();
        if (true === empty($this->collectionContents)) {
            throw new InvalidArgumentException("Collection with ID '$collections_id' not found.");
        }

        // Has the collection-content already an ID?
        if ($this->collections_id > 0) {
            // Then overwrite the loaded data
            $this->collectionContents[$this->collections_contents_info['primary'][1]] = $this->collections_id;
        } else {
            // Otherwise take the ID of the loaded collection-content as the collection-content ID
            $this->collections_id = $collections_id;
        }
    }



    /**
     * Save (pseudo)-content for root node to database.
     *
     * @throws Exception On failed database access.
     * @return void
     */
    public function root() {
        try {
            $this->collections_contents
                 ->insert(array($this->collections_contents_info['primary'][1] => 1));
        } catch (Exception $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }




    /**
     * Save collection-content to database.
     *
     * @throws Exception On failed database access.
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
                                'MAX(' . $this->collections_contents_info['primary'][1] . ')');
                $this->collections_id = ($db->fetchOne($selectMaxId) + 1);
            }
            // Delete outdated database records
            $this->collections_contents->delete($this->collections_contents_info['primary'][1] . ' = ' . $this->collections_id);
            // Insert updated database records
            $this->collectionContents[$this->collections_contents_info['primary'][1]] = $this->collections_id;
            $this->collections_contents
                 ->insert($this->collectionContents);
        } catch (Exception $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
}