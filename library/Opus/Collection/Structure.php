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
        $parentNodeFound = false;
        // For each node
        foreach ($this->collectionStructure as $index1 => $nested_set) {
            // If node is the desgnated parent
            if ($parent === (int) $this->collectionStructure[$index1][$this->collectionsIdentifier]) {
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
                    foreach ($this->collectionStructure as $index2 => $nested_set2) {
                        if (((int) $this->collectionStructure[$index2][$this->collectionsIdentifier] === (int) $leftSibling)
                          and ((int) $this->collectionStructure[$index2]['left']  > (int) $this->collectionStructure[$index1]['left'])
                          and ((int) $this->collectionStructure[$index2]['right'] < (int) $this->collectionStructure[$index1]['right'])) {
                            $new_left = (((int) $this->collectionStructure[$index2]['right']) + 1);
                        }
                    }
                }
                // Shift LEFTs and RIGHTs of nodes right of the new one
                foreach ($this->collectionStructure as $index3 => $nested_set3) {
                    if ((int) $this->collectionStructure[$index3]['left'] >= $new_left) {
                        $this->collectionStructure[$index3]['left'] += 2;
                    }
                    if ((int) $this->collectionStructure[$index3]['right'] >= $new_left) {
                        $this->collectionStructure[$index3]['right'] += 2;
                    }
                }
                // Insert new node
                $this->collectionStructure[] = array($this->collectionsIdentifier => $collections_id,
                                                                        'left'    => (int) $new_left,
                                                                        'right'   => (int) $new_left+1,
                                                                        'visible' => 1);
                $parentNodeFound = true;
            }
        }
        $this->leftOrder();
        if ($parentNodeFound === false) {
            throw new InvalidArgumentException("Parent node $parent not found for insertion.");
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
        $leftFound = false;
        foreach ($this->collectionStructure as $index => $nested_set) {
            if ((int) $this->collectionStructure[$index]['left'] === $left) {
                unset($this->collectionStructure[$index]);
                $leftFound = true;
            } else {
                if ($this->collectionStructure[$index]['left'] >= $left) {
                    $this->collectionStructure[$index]['left'] -= 2;
                }
                if ($this->collectionStructure[$index]['right'] >= $left) {
                    $this->collectionStructure[$index]['right'] -= 2;
                }
            }
        }
        if ($leftFound === false) {
            throw new InvalidArgumentException('Left value ' . $left . ' not found in tree.');
        }
        $this->leftOrder();
    }
        
    
    /**
     * Hide nodes with the given collections_id.
     *
     * @param integer $collections_id Institute to hide.
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
     * @param   integer $collections_id Institute to count.
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