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
 * Collection history (replacement) related methods.
 *
 * @category Framework
 * @package  Opus_Collection
 */
class Opus_Collection_Replacement {

    /**
     * Container for institutes_replacement table gateway
     */
    private $collections_replacement;
    
    /**
     * Container for identifying attribute
     */
    private $collectionsIdentifier;
    
    /**
     * Container for validation object
     */
    private $validation;
    
    /**
     * Constructor. 
     *
     * @param   string/integer $ID Number identifying the collection tree (role) 
     *                             or 'institute' for the institutes tree.
     * @return void
     */
    public function __construct($ID) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->constructorID($ID);
        if ($ID === 'institute') {
            $this->collectionsIdentifier    = 'institutes_id';
            $this->collections_replacement = new Opus_Db_InstitutesReplacement();
        } else {
            $this->collectionsIdentifier    = 'collections_id';
            $this->collections_replacement = new Opus_Db_CollectionsReplacement($ID);
        }
    }
    
    
    /**
     * Creates a database entry for a deleted collection. 
     *
     * @param   integer $collections_id Number identifying the deleted collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function delete($collections_id) {
        try {
            $this->collections_replacement
                 ->insert(array($this->collectionsIdentifier         => $collections_id,
                                'replacement_for_id'       => null,
                                'replacement_by_id'        => null,
                                'current_replacement_id'   => null,
                                ));
        } catch (Exception $e) {
            $db = Zend_Registry::get('db_adapter');
            $db->rollBack();
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
    
    
    /**
     * Creates a database entry for a replaced collection. 
     *
     * @param   integer $collections_id_old Number identifying the replaced collection.
     * @param   integer $collections_id_new Number identifying the replacing collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function replace($collections_id_old, $collections_id_new) {
        try {
            $this->collections_replacement
                 ->insert(array($this->collectionsIdentifier         => $collections_id_old,
                                'replacement_for_id'       => NULL,
                                'replacement_by_id'        => $collections_id_new,
                                'current_replacement_id'   => $collections_id_new,
                                ));
            $this->collections_replacement
                 ->insert(array($this->collectionsIdentifier         => $collections_id_new,
                                'replacement_for_id'       => $collections_id_old,
                                'replacement_by_id'        => NULL,
                                'current_replacement_id'   => $collections_id_new,
                                ));
        } catch (Exception $e) {
            $db = Zend_Registry::get('db_adapter');
            $db->rollBack();
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
    
    
    /**
     * Creates a database entry for a collection divided into two new collections. 
     *
     * @param   integer $collections_id_old Number identifying the divided collection.
     * @param   integer $collections_id_new1 Number identifying the first replacing collection.
     * @param   integer $collections_id_new2 Number identifying the second replacing collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function split($collections_id_old, $collections_id_new1, $collections_id_new2) {
        try {
            $this->replace($collections_id_old, $collections_id_new1);
            $this->replace($collections_id_old, $collections_id_new2);
        } catch (Exception $e) {
            $db = Zend_Registry::get('db_adapter');
            $db->rollBack();
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
    
    
    /**
     * Creates a database entry for two collections merged together into a new collection. 
     *
     * @param   integer $collections_id_old1 Number identifying the first of the merged collections.
     * @param   integer $collections_id_old2 Number identifying the second of the merged collections.
     * @param   integer $collections_id_new Number identifying the new created merged collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function merge($collections_id_old1, $collections_id_old2, $collections_id_new) {
        try {
            $this->replace($collections_id_old1, $collections_id_new);
            $this->replace($collections_id_old2, $collections_id_new);
        } catch (Exception $e) {
            $db = Zend_Registry::get('db_adapter');
            $db->rollBack();
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }
    
    
    /**
     * Fetch the replacement records for a collection. 
     *
     * @param   integer $collections_id Number identifying the collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return array Replacement records
     */
    public function getReplacementRecords($collections_id) {
        $set = $this->collections_replacement
                    ->fetchAll($this->collections_replacement
                                ->select()
                                ->where($this->collectionsIdentifier . ' = ?', $collections_id))
                    ->toArray();
        return $set;
    }
    
    
    /**
     * Fetch the actual (last) replacement for a collection. 
     *
     * @param   integer $collections_id Number identifying the collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer ID of current replacing collection 
     */
    public function getCurrent($collections_id) {
        $set = $this->getReplacementRecords($collections_id);
        foreach($set as $row) {
            $current[] = $row['current_replacement_id'];
        }
        return array_unique($current);

    }
    
    
    /**
     * Fetch the direct ancestor of a collection. 
     *
     * @param   integer $collections_id Number identifying the collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer ID of direct ancestor collection
     */
    public function getAncestor($collections_id) {
        $ancestor = array();
        $set = $this->getReplacementRecords($collections_id);
        foreach($set as $row) {
            $ancestor[] = (int) $row['replacement_for_id'];
        }
        return array_unique($ancestor);
    }
    
    
    /**
     * Fetch the direct replacement for a collection. 
     *
     * @param   integer $collections_id Number identifying the collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer ID of direct replacing collection
     */
    public function getReplacement($collections_id) {
        $replacement = array();
        $set = $this->getReplacementRecords($collections_id);
        foreach($set as $row) {
            $replacement[] = (int) $row['replacement_by_id'];
        }
        return array_unique($replacement);
    }
}