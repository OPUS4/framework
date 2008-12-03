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
 * @category    Framework
 * @package     Opus_Collections
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Provides functions to add, remove, alter and retrieve collection information.
 *
 * @category Framework
 * @package  Opus_Collections
 */
class Opus_Collection_Information {
     
    /**
     * Create a complete new collection structure (role). 
     *
     * @param array(string => array(string => string)) $roleArray Array with collection_role database records.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return integer ID of the newely created Collection Tree
     */
    static public function newCollectionTree(array $roleArray, $hidden = false) {
        $role = new Opus_Collection_Roles();
        $role->create();
        foreach ($roleArray as $language => $record) {
            if ($hidden === true) {
                $record['visible'] = 0;
            } else {
                $record['visible'] = 1;
            }
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
     * @param integer                                  $role_id        Identifies tree for new collection.
     * @param integer                                  $parent_id      Parent node of collection.
     * @param integer                                  $leftSibling_id Left sibling node of collection.
     * @param array(string => array(string => string)) $contentArray   Array with collection_content database records.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return integer $collections_id ID of the newely created Collection
     */
    static public function newCollection($role_id, $parent_id, $leftSibling_id, array $contentArray) {
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
        $ocs->insert((int) $collections_id, (int) $parent_id, (int) $leftSibling_id);
        $ocs->save();
        $db->commit();
        
        return $collections_id;
    }
    
    /**
     * Create a new position in the tree for a given collection . 
     *
     * @param integer $role_id        Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @param integer $parent_id      Parent node of collection.
     * @param integer $leftSibling_id Left sibling node of collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
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
     * @param integer $role_id Identifies tree for collection.
     * @param integer $left    LEFT attribute of the tree position.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
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
     * @param integer $role_id        Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
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

    /**
     * Fetch all collection roles from DB. 
     *
     * @param boolean $alsoHidden Decides whether or not hidden trees are regarded.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getAllCollectionRoles($alsoHidden = false) {
        // DB table gateway for the collection roles
        $collections_roles  = new Opus_Db_CollectionsRoles();
        
        // Fetch all or fetch only visible
        if ($alsoHidden === true) {
            $allCollectionRoles = $collections_roles
                                        ->fetchAll($collections_roles->select())
                                        ->toArray();
        } else {
            $allCollectionRoles = $collections_roles
                                        ->fetchAll($collections_roles->select()
                                        ->where('visible = ?', 1))
                                        ->toArray();
        }
        
        // Map into an ID- and language-indexed array 
        foreach ($allCollectionRoles as $record) {
            $allCollectionRolesOutput[$record['collections_roles_id']][$record['collections_language']] = $record;
        }
        
        return $allCollectionRolesOutput;
    }
    
    /**
     * Fetch all child collections of a collection. 
     *
     * @param integer $roles_id Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getSubCollections($roles_id, $collections_id = 0) {
        // Container for the child collections
        $children = array();
        
        // Load complete tree information 
        $ocs = new Opus_Collection_Structure($roles_id);    
        $ocs->load();
        $tree = $ocs->collectionStructure;
        
        // Create collection content object
        $occ = new Opus_Collection_Contents($roles_id);
        
        /*
         * Find out left and right values of the given collection id.
         * It should not matter which occurence of the collection in the tree we get 
         * since every subtree should lead to the same subtree-collections_ids.
         */
        foreach ($tree as $node) {
            if ((int) $node['collections_id'] === (int) $collections_id) {
                $left  = $node['left'];
                $right = $node['right'];
            }
        }    
        
        // Walk through the children and load the corresponding collection contents
        while ($left < $right-1) {
            $left++;
            $occ->create();
            $occ->load((int) $tree[$left]['collections_id']);
            $children[] = array('content' => $occ->collectionContents, 'structure' => $tree[$left]);
            $left = $tree[$left]['right'];
        }
        return $children;
    }
    
    /**
     * Fetch all document IDs belonging to a collection. 
     *
     * @param integer $roles_id Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @param boolean $alsoSubCollections Decides if documents in the subcollections should be regarded.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getAllCollectionDocuments($roles_id, $collections_id = 0, $alsoSubCollections = false) {
        if (false === is_int($collections_id)) {
            $collections_id = 0;
        }
        // DB table gateway for the linking table between collections and documents
        $linkDocColl  = new Opus_Db_LinkDocumentsCollections($roles_id);
        // Container array for the raw collection ID array and the reformatted collection ID array
        $allCollectionDocumentsOut = array();
        $allCollectionDocuments = array();
        // Fetch all document IDs linked with the collection ID
        $allCollectionDocuments = $linkDocColl
                                        ->fetchAll($linkDocColl->select()
                                        ->from($linkDocColl, array('documents_id'))
                                        ->where('collections_id = ?', $collections_id))
                                        ->toArray();
        // Reformat array                                        
        foreach ($allCollectionDocuments as $doc_id) {
            $allCollectionDocumentsOut[] =  $doc_id['documents_id'];                                  
        }
        return $allCollectionDocumentsOut;                                        
    }

    /**
     * Fetch role information to a given role ID. 
     *
     * @param integer $roles_id Identifies tree for collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getCollectionRole($roles_id) {
        $ocr  = new Opus_Collection_Roles();
        $ocr->load($roles_id);
        return $ocr->collectionRoles;
    }
    
    /**
     * Fetch all collections on the pathes to the root node. 
     *
     * @param integer $roles_id Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getPathToRoot($roles_id, $collections_id) {
        // Container for the array of pathes
        $paths = array();
        // Load collection tree
        $ocs = new Opus_Collection_Structure($roles_id);    
        $ocs->load();
        $tree = $ocs->collectionStructure;
        // Create collection content object
        $occ = new Opus_Collection_Contents($roles_id);
        
        // Find every occurence of the collection ID in the tree
        foreach ($tree as $node) {
            if ((int) $node['collections_id'] === (int) $collections_id) {
                // Container for this path
                $path = array();
                // First node in path is the given collection
                $occ->create();
                $occ->load((int) $collections_id);
                $path[] = $occ->collectionContents;
                // Search outwards the left/right-borders of the current node
                $left  = $node['left'];
                $right = $node['right'];
                $currentCollID = $collections_id;
                for ($l = $left-1; $l>1; $l--) {
                    if (isset($tree[$l])) {
                        $node = $tree[$l];
                        if (($node['right'] > $right)) {
                            $right = $node['right'];
                            $occ->create();
                            $occ->load((int) $node['collections_id']);
                            $path[] = $occ->collectionContents;
                        }
                    }
                }
                $paths[$left] = $path;
            }
        }    
        
        return $paths;
    }
    
    
    
    /**
     * Fetch collection information. 
     *
     * @param integer $roles_id Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getCollection($roles_id, $collections_id) {
        // Create collection content object and load information from DB
        $occ = new Opus_Collection_Contents($roles_id);
        $occ->create();
        $occ->load((int) $collections_id);
        return $occ->collectionContents;
    }
    
    /**
     * Assign a document to a collection. 
     *
     * @param integer $documents_id Identifies the document.
     * @param integer $roles_id Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function assignDocumentToCollection($documents_id, $roles_id, $collections_id) {
        // DB table gateway for the documents-collections linking table
        $link_documents_collections  = new Opus_Db_LinkDocumentsCollections($roles_id);
        
        $link_documents_collections->insert(array('collections_id' => $collections_id, 
                                    'documents_id'   => $documents_id));
    }
    
}