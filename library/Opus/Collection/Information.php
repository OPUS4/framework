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
     * @param array(string => array(string => string)) $roleArray      Array with collection_role database records.
     * @param array(array)                             $content_fields (Optional) Array with collection_role database records.
     * @param integer                                  $position       (Optional) Position for the new role.
     * @param boolean                                  $hidden         (Optional) True if tree should be hidden.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @throws Exception Is thrown on DB errors.
     * @return integer ID of the newely created Collection Tree
     */
    static public function newCollectionTree(array $roleArray, array $content_fields = array(), $position = 0, $hidden = false) {

        // Argument validation
        if ( (false === is_int($position)) or (0 > $position) ) {
            throw new InvalidArgumentException('Position must be a non-negative integer.');
        }

        if (false === is_bool($hidden)) {
            throw new InvalidArgumentException('Hidden flag must be boolean.');
        }

        // Create an empty role
        $role = new Opus_Collection_Roles();

        // Following operations are atomic
        $db = Zend_Registry::get('db_adapter');
        $db->beginTransaction();

        try {
            // If no position given take the next free one
            if ((int) $position === 0) {
                $position = $role->nextPosition();
            }

            // Setting the visibility flag for the collection tree
            if ($hidden === true) {
                $roleArray['visible'] = 0;
            } else {
                $roleArray['visible'] = 1;
            }
            // Setting the position of the new collection tree
            $roleArray['position'] = $position;
            // Add a record for each language
            $role->update($roleArray);

            // Shift the role positions to make space for the new one
            $role->shiftPositions((int) $position);

            // Write new role entry to DB table
            $role->save();

            // Create collection tables for the newly created role
            $role->createDatabaseTables($content_fields);

            // Write pseudo content for the hidden root node to fullfill foreign key constraint
            $occ = new Opus_Collection_Contents($role->getRolesID());
            $occ->root();

            // Write hidden root node to nested sets structure
            $ocs = new Opus_Collection_Structure($role->getRolesID());
            $ocs->create();
            $ocs->save();
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }
        return $role->getRolesID();
    }

    /**
     * Create a new collection.
     *
     * @param integer                                  $role_id        Identifies tree for new collection.
     * @param integer                                  $parent_id      Parent node of collection.
     * @param integer                                  $leftSibling_id Left sibling node of collection.
     * @param array(string => array(string => string)) $contentArray   Array with collection_content database records.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @throws Exception Is thrown on DB errors.
     * @return integer $collections_id ID of the newely created Collection
     */
    static public function newCollection($role_id, $parent_id, $leftSibling_id, array $contentArray = null) {

        // Argument validation
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($role_id);

        if ( (false === is_int($parent_id)) or (0 > $parent_id) ) {
            throw new InvalidArgumentException("Parent ID must be a non-negative integer but is $parent_id");
        }

        if ( (false === is_int($leftSibling_id)) or (0 > $leftSibling_id) ) {
            throw new InvalidArgumentException('Left Sibling ID must be a non-negative integer.');
        }

        // Create a new collection content container
        $occ = new Opus_Collection_Contents($role_id);

        // Fill the collection content with data
        if (is_null($contentArray) === false) {
            $occ->update($contentArray);
        }
        // Following operations are atomic
        $db = Zend_Registry::get('db_adapter');
        $db->beginTransaction();

        try {
            // Save content to DB
            $occ->save();

            // Fetch ID of the newely created collection
            $collections_id = $occ->getCollectionsID();

            // Load nested sets structure from DB
            $ocs = new Opus_Collection_Structure($role_id);
            $ocs->load();
            
            // Insert new collection underneath given parent to the right of the given left sibling
            $ocs->insert($collections_id, (int) $parent_id, (int) $leftSibling_id);

            // Save updated structure to DB
            $ocs->save();

            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }

        return (int) $collections_id;
    }

    /**
     * Create a new position in the tree for a given collection .
     *
     * @param integer $role_id        Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @param integer $parent_id      Parent node of collection.
     * @param integer $leftSibling_id Left sibling node of collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @throws Exception Is thrown on DB errors.
     * @return void
     */
    static public function newCollectionPosition($role_id, $collections_id, $parent_id, $leftSibling_id) {

        // Argument validation
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($role_id);

        if ( (false === is_int($collections_id)) or (0 >= $collections_id) ) {
            throw new InvalidArgumentException('Collection ID must be a positive integer.');
        }

        if ( (false === is_int($parent_id)) or (0 > $parent_id) ) {
            throw new InvalidArgumentException('Parent ID must be a non-negative integer.');
        }

        if ( (false === is_int($leftSibling_id)) or (0 > $leftSibling_id) ) {
            throw new InvalidArgumentException('Left Sibling ID must be a non-negative integer.');
        }

        // Following operations are atomic
        $db = Zend_Registry::get('db_adapter');
        $db->beginTransaction();
        try {
            // Load nested sets structure from DB
            $ocs = new Opus_Collection_Structure($role_id);
            $ocs->load();

            // Insert given collection underneath given parent to the right of the given left sibling
            $ocs->insert($collections_id, $parent_id, $leftSibling_id);

            // Save updated structure to DB
            $ocs->save();
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }
    }


    /**
     * Delete an occurrence of a collection in the tree.
     *
     * @param integer $role_id       Identifies tree for collection.
     * @param integer $collection_id Identifies collection to delete.
     * @param integer $parent_id     Identifies position where to delete collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @throws Exception Is thrown on DB errors.
     * @return void
     */
    static public function deleteCollectionPosition($role_id, $collection_id, $parent_id) {
        // TODO: Validation
        $ocs = new Opus_Collection_Structure($role_id);
        $ocs->load();
        $leftValues = $ocs->IDToleft($collection_id, $parent_id);
        rsort($leftValues);
        foreach ($leftValues as $left) {
            self::deleteCollectionPositionByLeft($role_id, (int) $left);
        }
    }



    /**
     * Delete an occurrence of a collection in the tree.
     *
     * @param integer $role_id Identifies tree for collection.
     * @param integer $left    LEFT attribute of the tree position.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @throws Exception Is thrown on DB errors.
     * @return void
     */
    static public function deleteCollectionPositionByLeft($role_id, $left) {

        // Argument validation
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($role_id);

        if ( (false === is_int($left)) or (0 >= $left) ) {
            throw new InvalidArgumentException('LEFT value must be a positive integer.');
        }

        // Following operations are atomic
        $db = Zend_Registry::get('db_adapter');
        $db->beginTransaction();

        // Load nested sets structure from DB
        $ocs = new Opus_Collection_Structure($role_id);
        $ocs->load();

        // Fetch collection ID belonging with given LEFT
        try {
            $collections_id = $ocs->leftToID($left);
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }
        if ($ocs->count($collections_id) < 2) {
            // Last occurrence of collection => normal delete
            $db->rollBack();
            self::deleteCollection($role_id, $collections_id);
        } else {
            try {
                $ocs->delete($left);
                $ocs->save();
                $db->commit();
            } catch (Exception $e) {
                $db->rollBack();
                throw new Exception($e->getMessage());
            }
        }
    }

    /**
     * Delete a collection (not really).
     *
     * @param integer $role_id        Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @throws Exception Is thrown on DB errors.
     * @return void
     */
    static public function deleteCollection($role_id, $collections_id) {

        // Argument validation
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($role_id);

        if ( (false === is_int($collections_id)) or (0 >= $collections_id) ) {
            throw new InvalidArgumentException('Collection ID must be a positive integer.');
        }

        // Following operations are atomic
        $db = Zend_Registry::get('db_adapter');
        $db->beginTransaction();
        try {
            // Load nested sets structure from DB
            $ocs = new Opus_Collection_Structure($role_id);
            $ocs->load();

            // Hide given collection and save structure to DB
            $ocs->hide($collections_id);
            $ocs->save();

            // Make history entry in replacement table
            $ocr = new Opus_Collection_Replacement($role_id);
            $ocr->delete($collections_id);
            $db->commit();
        } catch (Exception $e) {
            $db->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Fetch all collection roles from DB.
     *
     * @param boolean $alsoHidden (Optional) Decides whether or not hidden trees are regarded.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getAllCollectionRoles($alsoHidden = false) {

        // Argument validation
        if (false === is_bool($alsoHidden)) {
            throw new InvalidArgumentException('AlsoHidden flag must be boolean.');
        }

        $role = new Opus_Collection_Roles();
        $allCollectionRolesOutput = array();

        // Fetch all or fetch only visible
        $allCollectionRoles = $role->getAllRoles($alsoHidden);

        // Map into an ID-indexed array
        foreach ($allCollectionRoles as $record) {
            $record['id'] = (int) $record['id'];
            $allCollectionRolesOutput[$record['id']] = $record;
        }

        return $allCollectionRolesOutput;
    }

    /**
     * Fetch all child collections of a collection.
     *
     * @param integer $roles_id       Identifies tree for collection.
     * @param integer $collections_id (Optional) Identifies the collection.
     * @param boolean $alsoHidden     (Optional) Return also hidden collections?
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getSubCollections($roles_id, $collections_id = 1, $alsoHidden = false) {

        // Argument validation
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($roles_id);

        if ( (false === is_int($collections_id)) or (0 > $collections_id) ) {
            throw new InvalidArgumentException('Collection ID must be a non-negative integer.');
        }

        // Container for the child collections
        $children = array();

        // Load complete tree information
        $ocs = new Opus_Collection_Structure($roles_id);
        $ocs->load();
        $tree = $ocs->getCollectionStructure();

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

        /*if (false === isset($left)) {
            throw new InvalidArgumentException("Collection ID $collections_id not found in Structure");
        }*/

        if (true === isset($left)) {
        // Walk through the children and load the corresponding collection contents
        while ($left < ($right-1)) {
            $left++;
            if ( (1 === (int) $tree[$left]['visible']) or (true === $alsoHidden) ) {
                $occ->load((int) $tree[$left]['collections_id']);
                $children[] = array('content' => $occ->getCollectionContents(), 'structure' => $tree[$left]);
            }
            $left = $tree[$left]['right'];
        }
        }
        return $children;
    }

    /**
     * Fetch all document IDs belonging to a collection.
     *
     * @param integer $roles_id       Identifies tree for collection.
     * @param integer $collections_id (Optional) Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getAllCollectionDocuments($roles_id, $collections_id = 1) {

        
        $collections_id = (int) $collections_id;
        $roles_id = (int) $roles_id;
        
        if (false === is_int($collections_id)) {
            $collections_id = 0;
        }
        
        // Argument validation
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($roles_id);


        // DB table gateway for the linking table between collections and documents
        $linkDocColl  = new Opus_Db_LinkDocumentsCollections($roles_id);
        // Container array for the raw collection ID array and the reformatted collection ID array
        $allCollectionDocumentsOut = array();
        $allCollectionDocuments = array();

        // Look for 'link_docs_path_to_root' attribute
        $ocr  = new Opus_Collection_Roles();
        $ocr->load($roles_id);
        $cr = $ocr->getCollectionRoles();
        // If !=0 fetch every ID on path to root
        if (0 !== (int) $cr['link_docs_path_to_root']) {
            $sc = self::getSubCollections($roles_id, $collections_id, false);
            // For every such ID: fetch all related docs recursively
            foreach ($sc as $index => $record) {
                $allCollectionDocumentsOut = array_merge($allCollectionDocumentsOut, self::getAllCollectionDocuments($roles_id, (int) $record['structure']['collections_id']));
            }
        }

        $ocr  = new Opus_Collection_Replacement($roles_id);
        $ancestors = $ocr->getAncestor($collections_id);
        foreach ($ancestors as $ancestor) {
            if (false === empty($ancestor)) {
                $allCollectionDocumentsOut = array_merge($allCollectionDocumentsOut, self::getAllCollectionDocuments($roles_id, (int) $ancestor));
            }
        }

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
        return array_unique($allCollectionDocumentsOut);
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
        return $ocr->getCollectionRoles();
    }

    /**
     * Fetch every parent of a given collection.
     *
     * @param   integer $roles_id       ID identifying collection tree.
     * @param   integer $collections_id ID identifying collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getAllParents($roles_id, $collections_id) {
        // Load collection tree
        $ocs = new Opus_Collection_Structure($roles_id);
        $ocs->load();
        $parents = $ocs->getAllParents($collections_id);
        return $parents;
    }

    /**
     * Fetch all collections on the pathes to the root node.
     *
     * @param integer $roles_id       Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getPathToRoot($roles_id, $collections_id) {
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($roles_id);

        if ( (false === is_int($collections_id)) or (0 >= $collections_id) ) {
            throw new InvalidArgumentException('Collection ID must be a positive integer.');
        }

        // Container for the array of pathes
        $paths = array();
        // Load collection tree
        $ocs = new Opus_Collection_Structure($roles_id);
        $ocs->load();

        $tree = $ocs->getCollectionStructure();
        // Create collection content object
        $occ = new Opus_Collection_Contents($roles_id);

        // Find every occurence of the collection ID in the tree
        foreach ($tree as $node) {
            if ((int) $node['collections_id'] === (int) $collections_id) {
                // Container for this path
                $path = array();
                // First node in path is the given collection
                $occ->load((int) $collections_id);
                $path[] = $occ->getCollectionContents();
                // Search outwards the left/right-borders of the current node
                $left  = $node['left'];
                $right = $node['right'];
                $currentCollID = $collections_id;
                for ($l = ($left-1); $l>1; $l--) {
                    if (true === isset($tree[$l])) {
                        $node = $tree[$l];
                        if (($node['right'] > $right)) {
                            $right = $node['right'];
                            $occ->load((int) $node['collections_id']);
                            $path[] = $occ->getCollectionContents();
                        }
                    }
                }
                $paths[$left] = $path;
            }
        }
        if (true === empty($paths)) {
            throw new InvalidArgumentException("Collection ID $collections_id not found in Structure.");
        }
        return $paths;
    }

    /**
     * Fetch collection information.
     *
     * @param integer $roles_id       Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    static public function getCollection($roles_id, $collections_id) {
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($roles_id);

        if ( (false === is_int($collections_id)) or (0 >= $collections_id) ) {
            throw new InvalidArgumentException('Collection ID must be a positive integer.');
        }

        // Create collection content object and load information from DB
        $occ = new Opus_Collection_Contents($roles_id);
        $occ->load((int) $collections_id);
        $content = $occ->getCollectionContents();
        return $content[0];
    }

    /**
     * Assign a document to a collection.
     *
     * @param integer $documents_id   Identifies the document.
     * @param integer $roles_id       Identifies tree for collection.
     * @param integer $collections_id Identifies the collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function assignDocumentToCollection($documents_id, $roles_id, $collections_id) {
        $validation = new Opus_Collection_Validation();
        $validation->constructorID($roles_id);

        if ( (false === is_int($collections_id)) or (0 >= $collections_id) ) {
            throw new InvalidArgumentException('Collection ID must be a positive integer.');
        }

        if ( (false === is_int($documents_id)) or (0 >= $documents_id) ) {
            throw new InvalidArgumentException('Document ID must be a positive integer.');
        }

        // DB table gateway for the documents-collections linking table
        $link_documents_collections  = new Opus_Db_LinkDocumentsCollections($roles_id);

        $link_documents_collections->insert(array('collections_id' => $collections_id,
                                    'documents_id'   => $documents_id));
    }

    /**
     *
     */
    static public function replace($roles_id, $collections_id, array $contentArray) {
        // TODO: Verification, Comments, $ocs->load();
        $new_collections_id = 0;
        // Load collection tree
        $ocs = new Opus_Collection_Structure($roles_id);
        $ocs->load();

        $parents_ids = $ocs->getAllParents($collections_id);

        // Beneath every parent a new collection (the replacement) is placed right of the replaced collection
        foreach ($parents_ids as $parents_id) {
            // First a complete new collection is created, then the copies (positions) follow
            if (0 === $new_collections_id) {
                $new_collections_id = self::newCollection($roles_id, (int) $parents_id, $collections_id, $contentArray);
                $ocs->load();

                $subColls = self::getSubCollections($roles_id, $collections_id, true);
                $leftSibling = 0;
                foreach ($subColls as $subColl) {
                    self::newCollectionPosition($roles_id, (int) $subColl['structure']['collections_id'], (int) $new_collections_id, $leftSibling);
                    $ocs->load();
                    $leftSibling = (int) $subColl['structure']['collections_id'];
                }
            } else {
                self::newCollectionPosition($roles_id, $new_collections_id, (int) $parents_id, $collections_id);
                $ocs->load();
            }

            $ocs->hide($collections_id);
            $ocs->save();
        }
        // Entry in the replacement table
        $ocr = new Opus_Collection_Replacement($roles_id);
        $ocr->replace($collections_id, $new_collections_id);

        return $new_collections_id;
    }

    /**
     *
     */
    static public function merge($roles_id, $collections_id1, $collections_id2, array $contentArray) {
        // TODO: Verification, Comments, $ocs->load();
        $new_collections_id = 0;
        // Load collection tree
        $ocs = new Opus_Collection_Structure($roles_id);
        $ocs->load();

        $parents_ids = $ocs->getAllParents($collections_id1);

        // Beneath every parent a new collection (the replacement) is placed right of the replaced collection
        foreach ($parents_ids as $parents_id) {
            // First a complete new collection is created, then the copies (positions) follow
            if (0 === $new_collections_id) {
                $new_collections_id = self::newCollection($roles_id, (int) $parents_id, $collections_id1, $contentArray);
                $ocs->load();

                $subColls1 = self::getSubCollections($roles_id, $collections_id1, true);
                $subColls2 = self::getSubCollections($roles_id, $collections_id2, true);
                $subColls = array_merge($subColls1, $subColls2);
                $leftSibling = 0;
                foreach ($subColls as $subColl) {
                    self::newCollectionPosition($roles_id, (int) $subColl['structure']['collections_id'], (int) $new_collections_id, $leftSibling);
                    $ocs->load();
                    $leftSibling = (int) $subColl['structure']['collections_id'];
                }
            } else {
                self::newCollectionPosition($roles_id, $new_collections_id, (int) $parents_id, $collections_id1);
                $ocs->load();
            }

            $ocs->hide($collections_id1);
            $ocs->save();
        }

        // Und der zweite Streich
        $ocs->load();

        $parents_ids = $ocs->getAllParents($collections_id2);

        // Beneath every parent a new collection (the replacement) is placed right of the replaced collection
        foreach ($parents_ids as $parents_id) {
            // First a complete new collection is created, then the copies (positions) follow
            if (0 === $new_collections_id) {
                $new_collections_id = self::newCollection($roles_id, (int) $parents_id, $collections_id2, $contentArray);
                $ocs->load();

                $subColls = self::getSubCollections($roles_id, $collections_id2, true);
                $leftSibling = 0;
                foreach ($subColls as $subColl) {
                    self::newCollectionPosition($roles_id, (int) $subColl['structure']['collections_id'], (int) $new_collections_id, $leftSibling);
                    $ocs->load();
                    $leftSibling = (int) $subColl['structure']['collections_id'];
                }
            } else {
                self::newCollectionPosition($roles_id, $new_collections_id, (int) $parents_id, $collections_id2);
                $ocs->load();
            }

            $ocs->hide($collections_id2);
            $ocs->save();
        }

        $ocs->load();
        // Entry in the replacement table
        $ocr = new Opus_Collection_Replacement($roles_id);
        $ocr->merge($collections_id1, $collections_id2, $new_collections_id);

        return $new_collections_id;
    }

    /**
     *
     */
    static public function split($roles_id, $collections_id, array $contentArray1, array $contentArray2) {
        // TODO: Verification, Comments, $ocs->load();

        // Load collection tree
        $ocs = new Opus_Collection_Structure($roles_id);
        $ocs->load();

        $parents_ids = $ocs->getAllParents($collections_id);

        $new_collections_id1 = 0;
        // Beneath every parent a new collection (the replacement) is placed right of the replaced collection
        foreach ($parents_ids as $parents_id) {
            // First a complete new collection is created, then the copies (positions) follow
            if (0 === $new_collections_id1) {
                $new_collections_id1 = self::newCollection($roles_id, (int) $parents_id, $collections_id, $contentArray2);
                $ocs->load();

                $subColls = self::getSubCollections($roles_id, $collections_id, true);
                $leftSibling = 0;
                foreach ($subColls as $subColl) {
                    self::newCollectionPosition($roles_id, (int) $subColl['structure']['collections_id'], (int) $new_collections_id1, $leftSibling);
                    $ocs->load();
                    $leftSibling = (int) $subColl['structure']['collections_id'];
                }
            } else {
                self::newCollectionPosition($roles_id, $new_collections_id1, (int) $parents_id, $collections_id);
                $ocs->load();
            }
        }


        $new_collections_id2 = 0;
        // Beneath every parent a new collection (the replacement) is placed right of the replaced collection
        foreach ($parents_ids as $parents_id) {
            // First a complete new collection is created, then the copies (positions) follow
            if (0 === $new_collections_id2) {
                $new_collections_id2 = self::newCollection($roles_id, (int) $parents_id, $collections_id, $contentArray1);
                $ocs->load();

                $subColls = self::getSubCollections($roles_id, $collections_id, true);
                $leftSibling = 0;
                foreach ($subColls as $subColl) {
                    self::newCollectionPosition($roles_id, (int) $subColl['structure']['collections_id'], (int) $new_collections_id2, $leftSibling);
                    $ocs->load();
                    $leftSibling = (int) $subColl['structure']['collections_id'];
                }
            } else {
                self::newCollectionPosition($roles_id, $new_collections_id2, (int) $parents_id, $collections_id);
                $ocs->load();
            }

            $ocs->hide($collections_id);
            $ocs->save();
        }



        // Entry in the replacement table
        $ocr = new Opus_Collection_Replacement($roles_id);
        $ocr->split($collections_id, $new_collections_id1, $new_collections_id2);

        return array($new_collections_id1, $new_collections_id2);
    }
}
