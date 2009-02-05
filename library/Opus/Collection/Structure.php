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
 * Collection tree structure related methods.
 *
 * @category Framework
 * @package  Opus_Collections
 */
class Opus_Collection_Structure {

    /**
     * The collection-structure (tree) array
     *
     * @var array
     */
    private $collectionStructure;

    /**
     * Container for collections_structure table gateway
     *
     * @var object
     */
    private $collections_structure;

    /**
     * Container for collections_structure table metadata
     *
     * @var array
     */
    private $collections_structure_info;

    /**
     * Container for identifying attribute
     *
     * @var string
     */
    private $collectionsIdentifier;

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
        if ($ID === 'institute') {
            $this->collectionsIdentifier = 'institutes_id';
            $this->collections_structure = new Opus_Db_InstitutesStructure();
        } else {
            // For throwing Inv Arg Exception on non existing roles IDs
            $ocr  = new Opus_Collection_Roles();
            $ocr->load($ID);

            $this->collectionsIdentifier = 'collections_id';
            $this->collections_structure = new Opus_Db_CollectionsStructure((int) $ID);
        }
        $this->collectionStructure = array();
        $this->collections_structure_info = $this->collections_structure->info();
    }

    /**
     * Creates an collection-structure array. A standard hidden root node simplifies manipulating methods.
     *
     * @return void
     */
    public function create() {
        $this->collectionStructure[1] = array(  $this->collectionsIdentifier => 0,
                                                'left' => 1,
                                                'right' => 2,
                                                'visible' => 0);
    }

    /**
     * Returns collections_id to the given LEFT attribute.
     *
     * @param integer $left LEFT attribute.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer
     */
    public function leftToID($left) {
        // Argument validation
        if ( (false === is_int($left)) or (0 >= $left) ) {
            throw new InvalidArgumentException('LEFT value must be a positive integer.');
        }
        if (false === isset($this->collectionStructure[$left])) {
            throw new InvalidArgumentException('Given LEFT value not found in structure.');
        }
        $this->leftOrder();
        return (int) $this->collectionStructure[$left]['collections_id'];
    }

    /**
     * Returns collection structure array.
     *
     * @return array
     */
    public function getCollectionStructure() {
        return $this->collectionStructure;
    }

    /**
     * Load structure from database.
     *
     * @return void
     */
    public function load() {
        $this->collectionStructure = $this->collections_structure->fetchAll()->toArray();
        // Erase primary key attribute
        foreach ($this->collectionStructure as $index => $record) {
            unset($this->collectionStructure[$index][$this->collections_structure_info['primary'][1]]);
        }
        $this->leftOrder();
    }

    /**
     * Save structure to database.
     *
     * @throws  Exception On failed database access.
     * @return void
     */
    public function save() {
        try {
            // Erase outdated structure
            $this->collections_structure->delete(true);
            // Write new structure
            foreach ($this->collectionStructure as $record) {
                $this->collections_structure
                     ->insert($record);
            }
        } catch (Exception $e) {
            throw new Exception('Database error: ' . $e->getMessage());
        }
    }

    /**
     * Insert a new node below the given parent and right of the given sibling.
     *
     * @param integer $collections_id Number identifying the specific collection.
     * @param integer $parent         Designated parent node.
     * @param integer $leftSibling    (Optional) Designated left sibling node or 0 for no sibling.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function insert($collections_id, $parent, $leftSibling = 0) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($collections_id);
        $this->validation->node($parent);
        $this->validation->node($leftSibling);
        $this->leftOrder();
        $parentNodeFound = false;
        $new_left = 0;
        // For each node
        foreach ($this->collectionStructure as $index1 => $nested_set) {

            // If node is the designated parent
            if ($parent === (int) $this->collectionStructure[$index1][$this->collectionsIdentifier]) {
                // Ensure that collection isn't already child node of designated parent node
                $collectionAlreadyChild = false;
                $leftSiblingFound = false;
                $left = (int) $this->collectionStructure[$index1]['left'];
                $right = (int) $this->collectionStructure[$index1]['right'];
                while ($left < ($right-1)) {
                    $left++;
                    if ((int) $this->collectionStructure[$left][$this->collectionsIdentifier] === $collections_id) {
                        $collectionAlreadyChild = true;
                    }
                    if ((int) $this->collectionStructure[$left][$this->collectionsIdentifier] === $leftSibling) {
                        $leftSiblingFound = true;
                    }
                    $left = $this->collectionStructure[$left]['right'];
                }

                if (false === $leftSiblingFound) {
                    $leftSibling = 0;
                }

                if (false === $collectionAlreadyChild) {

                    // If parent has no child or new node shall be most left sibling
                    if (($this->collectionStructure[$index1]['right'] === $this->collectionStructure[$index1]['left']+1) or
                        ($leftSibling === 0)) {
                        // LEFT of new node is RIGHT of the parent
                        $new_left = (int) $this->collectionStructure[$index1]['right'];
                    } else {
                        // If parent has other children
                        // Find designated left sibling below designated parent
                        // This is the node with the correct collections_id which LEFT and RIGHT
                        // are between LEFT and RIGHT of the designated parent
                        $left = (int) $this->collectionStructure[$index1]['left'];
                        $right = (int) $this->collectionStructure[$index1]['right'];
                        while ($left < ($right-1)) {
                            $left++;
                            if (((int) $this->collectionStructure[$left][$this->collectionsIdentifier] === (int) $leftSibling)
                              and ((int) $this->collectionStructure[$left]['left']  > (int) $this->collectionStructure[$index1]['left'])
                              and ((int) $this->collectionStructure[$left]['right'] < (int) $this->collectionStructure[$index1]['right'])) {
                                $new_left = (((int) $this->collectionStructure[$left]['right']) + 1);
                            }
                            $left = $this->collectionStructure[$left]['right'];
                        }
                    }
                    $insertionLeft[] = $new_left;

                }
                $parentNodeFound = true;
            }
        }

        if (isset($insertionLeft)) {
            rsort($insertionLeft);
            foreach ($insertionLeft as $new_left) {
                foreach ($this->collectionStructure as $index3 => $nested_set3) {
                        if ((int) $this->collectionStructure[$index3]['left'] >= $new_left) {
                            $this->collectionStructure[$index3]['left'] += 2;
                        }
                        if ((int) $this->collectionStructure[$index3]['right'] >= $new_left) {
                            $this->collectionStructure[$index3]['right'] += 2;
                        }
                    }
                $this->collectionStructure[] = array($this->collectionsIdentifier => $collections_id,
                                                                            'left'    => (int) $new_left,
                                                                            'right'   => (int) $new_left+1,
                                                                            'visible' => 1);
            }

            $subTreeFound = false;
            // Rekursives Subtree-kopieren
            foreach ($this->collectionStructure as $index => $nested_set) {
                // If subtree not found yet AND node is the right collection AND node is not a leaf
                if ((false === $subTreeFound) AND
                    ($collections_id === (int) $nested_set[$this->collectionsIdentifier]) AND
                    ((int) $nested_set['right'] > ((int) $nested_set['left']) + 1)) {

                    $subTreeFound = true;
                    $left = (int) $nested_set['left'];
                    $right = (int) $nested_set['right'];
                    $leftSibling = 0;
                    while ($left < ($right-1)) {
                        $left++;
                        $this->leftOrder();
                        $subcollection_id = (int) $this->collectionStructure[$left][$this->collectionsIdentifier];
                        $insertionArray[] = array ($subcollection_id, $collections_id, $leftSibling);
                        $leftSibling = $subcollection_id;
                        $left = $this->collectionStructure[$left]['right'];
                    }

                    foreach ($insertionArray as $record) {
                        $this->insert($record[0], $record[1], $record[2]);
                    }
                }
            }
        }

        $this->leftOrder();
        if ($parentNodeFound === false) {
            throw new InvalidArgumentException("Parent node $parent not found for insertion.");
        }
    }




    /**
     * Returns the nested set node to a given left value.
     *
     * @param integer $left Left value identifying the node.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    public function parent($left) {
        // TODO: Validation
        $this->leftOrder();
        $tree = $this->collectionStructure;
        // Look for maximum left value lower given left value: That's the parent
        for ($leftValue = 1; $leftValue < $left; $leftValue++) {
            if ((isset($tree[$leftValue])) AND ($tree[$leftValue]['right'] > $tree[$left]['right'])) {
                $parent = $tree[$leftValue];
            }
        }
        if (true === isset($parent)) {
            return $parent;
        } else {
            return false;
        }
    }


    /**
     * Returns the left values to a given collection ID under a specific parent
     *
     * @param integer $collection_id ID identifying collection.
     * @param integer $parent_id     ID identifying parent collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    public function IDToleft($collection_id, $parent_id) {
        // TODO: Validation
        $this->leftOrder();
        foreach ($this->collectionStructure as $index => $nested_set) {
            $parent_set = $this->parent($index);
            if (((int) $nested_set[$this->collectionsIdentifier] === (int) $collection_id)
                AND ((int) $parent_set[$this->collectionsIdentifier] === (int) $parent_id)) {
                $left_array[] = $nested_set['left'];
            }
        }
        if (true === isset($left_array)) {
            return $left_array;
        } else {
            return false;
        }
    }

    /**
     * Delete the node with the given LEFT attribute.
     *
     * @param integer $left Number identifying the specific node.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function delete($left) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($left);
        $collections_id = $this->leftToID($left);
        $this->leftOrder();

        if (false === isset($this->collectionStructure[$left])) {
            throw new InvalidArgumentException('Left value ' . $left . ' not found in tree.');
        }

        $right = $left+1;

        while ((int) $this->collectionStructure[$left]['right'] !== ($right)) {
            $this->delete($right);
        }
        unset($this->collectionStructure[$left]);

        foreach ($this->collectionStructure as $index => $nested_set) {
                if ($this->collectionStructure[$index]['left'] >= $left) {
                    $this->collectionStructure[$index]['left'] -= 2;
                }
                if ($this->collectionStructure[$index]['right'] >= $left) {
                    $this->collectionStructure[$index]['right'] -= 2;
                }

        }

        $this->leftOrder();
    }


    /**
     * Hide nodes with the given collections_id.
     *
     * @param integer $collections_id ID identifying collection.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    public function hide($collections_id) {
        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($collections_id);
        foreach ($this->collectionStructure as $index => $record) {
            if ((int) $record[$this->collectionsIdentifier] === $collections_id) {
                $this->collectionStructure[$index]['visible'] = 0;
            }
        }
    }

    /**
     * Count occurrences of a collection in the tree.
     *
     * @param   integer $collections_id ID identifying collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return integer Number of occurrences
     */
    public function count($collections_id) {
        $count = 0;
        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($collections_id);
        foreach ($this->collectionStructure as $index => $record) {
            if ((int) $record[$this->collectionsIdentifier] === $collections_id) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Fetch every parent for a given collection ID.
     *
     * @param   integer $collections_id ID identifying collection.
     * @throws  InvalidArgumentException Is thrown on invalid arguments.
     * @return array
     */
    public function getAllParents($collections_id) {

        $this->validation = new Opus_Collection_Validation();
        $this->validation->ID($collections_id);

        // Fetch occurences of the given collection
        foreach ($this->collectionStructure as $record) {
            if ((int) $record[$this->collectionsIdentifier] === $collections_id) {
                $positions[] = $record;
            }
        }
        if (false === isset($positions)) {
            return false;
        }
        // Fetch parent for every occurence
        foreach ($positions as $position) {
            $temp_parent = 0;
            foreach ($this->collectionStructure as $record) {
                if (($record['left'] < $position['left']) AND ($record['right'] > $position['right'])) {
                    $temp_parent = ($record['left'] > $temp_parent['left']) ? $record : $temp_parent;
                }
            }
            $parents[] = $temp_parent[$this->collectionsIdentifier];
        }
        return array_unique($parents);
    }

    /**
     * Set collectionStructure indizes to LEFT attribute for easier access
     *
     * @return void
     */
    public function leftOrder() {
        $tmpCollectionStructure = array();
        foreach ($this->collectionStructure as $index => $record) {
            $tmpCollectionStructure[$record['left']] = $record;
        }
        $this->collectionStructure = $tmpCollectionStructure;
    }
}