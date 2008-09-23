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
 * Provides functions to add, remove, alter and retrieve collection information.
 *
 * @category Framework
 * @package  Opus_Collection
 */
class Opus_Collection_Information {
    
    
    /**
     * Create a complete new collection structure (role). 
     *
     * @param   array $roleArray Array with collection_role database records.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer ID of the newely created Collection Tree
     */
    static public function newCollectionTree($roleArray) {
        $role = new Opus_Collection_Roles();
        $role->create();
        foreach ($roleArray as $language => $record) {
            $role->addLanguage($language);
            $role->update(array($language => $record));   
        }
        
        $db = Zend_Registry::get('db_adapter');
        $db->beginTransaction();
        $role->save();
        $role->createDatabaseTables();
        
        $ocs = new Opus_Collection_Structure($role->roles_id);
        $ocs->create();
        $ocs->save();
        $db->commit();
        
        return $role->roles_id;
    }
    
    
    /**
     * Create a new collection . 
     *
     * @param   integer $role_id Identifies tree for new collection.
     * @param   integer $parent_id Parent node of collection.
     * @param   integer $leftSibling_id Left sibling node of collection.
     * @param   array $contentArray Array with collection_content database records.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer $collections_id ID of the newely created Collection
     */
    static public function newCollection($role_id, $parent_id, $leftSibling_id, $contentArray) {
        $occ = new Opus_Collection_Contents($role_id);
        $occ->create();
        foreach ($contentArray as $language => $record) {
            $occ->addLanguage($language);
            $occ->update(array($language => $record));   
        }
        $db = Zend_Registry::get('db_adapter');
        $db->beginTransaction();
        $occ->save();        
        $collections_id = $occ->collections_id;
        
        $ocs = new Opus_Collection_Structure($role_id);    
        $ocs->load();
        $ocs->insert($collections_id, $parent_id, $leftSibling_id);
        $ocs->save();
        $db->commit();
        
        return $collections_id;
    }
    
    
    /**
     * Create a new position in the tree for a given collection . 
     *
     * @param   integer $collections_id Identifies the collection.
     * @param   integer $role_id Identifies tree for collection.
     * @param   integer $parent_id Parent node of collection.
     * @param   integer $leftSibling_id Left sibling node of collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function newCollectionPosition($role_id, $collections_id, $parent_id, $leftSibling_id) {
        $ocs = new Opus_Collection_Structure($role_id);  
        $db = Zend_Registry::get('db_adapter');  
        $db->beginTransaction();
        $ocs->load();
        $ocs->insert($collections_id, $parent_id, $leftSibling_id);
        $ocs->save();
        $db->commit();
    }
    
    /**
     * Delete an occurrence of a collection in the tree. 
     *
     * @param   integer $role_id Identifies tree for collection.
     * @param   integer $left LEFT attribute of the tree position.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function deleteCollectionPosition($role_id, $left) {
        $ocs = new Opus_Collection_Structure($role_id);
        $db = Zend_Registry::get('db_adapter');  
        $db->beginTransaction();
        $ocs->load();
        $collections_id = (int) $ocs->collectionStructure[$left]['collections_id'];
        if ($ocs->count($collections_id) < 2) {
            // Last occurrence of collection => normal delete
            $db->rollBack();
            self::deleteCollection($role_id, $collections_id);
        } else {
            $ocs->delete($left);
            $ocs->save();
            $db->commit();
        }    
    }
    
    /**
     * Delete a collection (not really). 
     *
     * @param   integer $role_id Identifies tree for collection.
     * @param   integer $collections_id Identifies the collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function deleteCollection($role_id, $collections_id) {
        // Do not really kill from structure but hide
        $ocs = new Opus_Collection_Structure($role_id);
        $db = Zend_Registry::get('db_adapter');  
        $db->beginTransaction();
        $ocs->load();
        $ocs->hide($collections_id);
        $ocs->save();
        // Make history entry
        $ocr = new Opus_Collection_Replacement($role_id);    
        $ocr->delete($collections_id);
        $db->commit();
    }
}