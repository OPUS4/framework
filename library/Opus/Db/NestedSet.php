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
 * @package	Opus_Collections
 * @author     	Thoralf Klein <thoralf.klein@zib.de>
 * @copyright  	Copyright (c) 2010, OPUS 4 development team
 * @license    	http://www.gnu.org/licenses/gpl.html General Public License
 * @version    	$Id$
 */

/**
 * Table gateway class to nested sets.
 *
 * @category    Framework
 * @package     Opus_Db
 * @uses        Zend_Db_Table_Abstract
 *
 * WARNING: This class does not use transactions.  If you want to be transaction
 * WARNING: safe, beginTransaction() before using methods from here and commit()
 * WARNING: when you're done.
 *
 * ANOTHER WARNING: Always make sure, $treeId, $id and all the parameters you
 * ANOTHER WARNING: are clean.  Currently, we assume that tree_id and id are
 * ANOTHER WARNING: integer datatypes and explicitly cast to int.  This might
 * ANOTHER WARNING: result in strange behaviour, if you're not using integers
 * ANOTHER WARNING: or submitting NULL values.
 *
 */
abstract class Opus_Db_NestedSet extends Zend_Db_Table_Abstract {

    /**
     * Table name of the nested set table.
     *
     * @var string
     */
    protected $_name;

    /**
     * Table column holding the left-id for the nested set structure.
     *
     * @var string
     */
    protected $_left;

    /**
     * Table column holding the right-id for the nested set structure.
     *
     * @var string
     */
    protected $_right;

    /**
     * Table column holding the parent-id for the structure.  This actually is
     * more than a nested set structure, but we need this for fast retrieval of
     * one nodes' children.
     *
     * @var string
     */
    protected $_parent;

    /**
     * Table column holding the sort-key for the structure.  This is needed
     * to allow swapping elements easily without changing the whole tree.
     *
     * @var string
     */
    protected $_sort;

    /**
     * Table column holding the tree-id for the structure.  We're holding more
     * than one nested-set structure in the table and we're distinguishing the
     * different trees by this ID.
     *
     * @var string
     */
    protected $_tree;

    /**
     * Override setup logic.
     *
     * @return void
     * @see    Zend_Db_Table_Abstract::_setup()
     */
    protected function _setup() {
        parent::_setup();

        // Set up primary key in $this->_primary[1].  It will not be set on
        // construction, so we do it manually.  This way, we can assume that
        // $this->_primary[1] is always set!
        $this->_setupPrimaryKey();
        assert(false === is_null($this->_primary[1]));
    }

    /**
     * Retrieve node.
     *
     * @param  int  $treeId  ID of tree you want to use.
     * @param  int  $id       Primary key of the node.
     * @throws Opus_Model_Exception
     * @return Zend_Db_Row
     */
    private function getNodeById($id) {
        $select = $this->selectNodeById($id);
        $row = $this->fetchRow($select);

        if (true === is_null($row)) {
            throw new Opus_Model_Exception("Node $id not found.");
        }

        return $row;
    }

    /**
     * Retrieve root node, i.e. node with $leftId=1.  Returns NULL if row was
     * not found.
     *
     * @param  int  $treeId  ID of tree you want to use.
     * @throws Opus_Model_Exception
     * @return Zend_Db_Row
     */
    public function getRootNode($treeId) {
        $select = $this->selectNodeByLeftId($treeId, 1);
        $row = $this->fetchRow($select);

        return $row;
    }

    /**
     * Build SQL statement for retrieving nodes by ID.
     *
     * @param  int  $id       Primary key of the node.
     * @return Zend_Db_Table_Select
     */
    private function selectNodeById($id) {
        return $this->select()
                ->from("{$this->_name} AS node")
                ->where("node.{$this->_primary[1]} = ?", $id);
    }

    /**
     * Build SQL statement for retrieving nodes by (tree id, left id).
     *
     * @param  int  $treeId  ID of tree you want to use.
     * @param  int  $leftId  Left-ID of the node.
     * @return Zend_Db_Table_Select
     */
    public function selectNodeByLeftId($treeId, $leftId) {
        return $this->select()
                ->from("{$this->_name} AS node")
                ->where("{$this->_tree} = ?", $treeId)
                ->where("{$this->_left} = ?", $leftId);
    }

    /**
     * Delete the whole tree.  Returns affected rows.
     *
     * @param  int     $treeId The id of the tree you want to delete.
     * @return int
     */
    public function deleteTree($treeId) {
        return $this->_db->query("DELETE FROM {$this->_name} WHERE {$this->_tree} = {$treeId}  ORDER BY {$this->_left}  DESC");
    }

    /**
     * Delete node with it's child(s) and return affected rows.
     *
     * @param  int   $id
     * @return int   The number of affected rows.
     */
    public function deleteNodeXXX($id) {
        $row = $this->getNodeById($id);
        $tree = $row->{$this->_tree};
        $right = (int) $row->{$this->_right};
        $left = (int) $row->{$this->_left};
        $width = $right - $left + 1;

        // NOTE: ORDER-BY is needed, because MySQL does not support deferred
        // NOTE: constraint checks.
        $stmt = "DELETE FROM {$this->_name}  WHERE {$this->_left} BETWEEN {$left} AND {$right} AND {$this->_tree} = {$tree}  ORDER BY {$this->_left}  DESC";
        // echo "statement: $stmt\n";
        $res = $this->_db->query($stmt);
        $this->_db->query("UPDATE {$this->_name} SET {$this->_left}  = {$this->_left} - {$width}   WHERE {$this->_left} > {$right}  AND {$this->_tree} = {$tree}  ORDER BY {$this->_left}  ASC");
        $this->_db->query("UPDATE {$this->_name} SET {$this->_right} = {$this->_right} - {$width}  WHERE {$this->_right} > {$right} AND {$this->_tree} = {$tree}  ORDER BY {$this->_right} ASC");

        return $res->rowCount();
    }

    /**
     * Retrieve whole tree (as statement!) with additional depth field.
     *
     * FIXME: Might be useful, but needs some testing.  Currently unused.
     *
     * @access public
     * @return Zend_Db_Table_Select
     */
    public function selectTreeDepth($treeId) {
        $showFields = array();
        $showFields[] = $this->_primary[1];
        $showFields[] = $this->_tree;
        $showFields[] = $this->_left;
        $showFields[] = $this->_right;
        $showFields[] = $this->_parent;
        $showFields[] = "ROUND((node.{$this->_right} - node.{$this->_left} - 1)/2) AS children";

        $select = $this->select()
                        ->from("{$this->_name} AS parent", "COUNT(parent.{$this->_primary[1]}) - 1 AS depth")
                        ->from("{$this->_name} AS node", $showFields)
                        ->where("node.{$this->_left} BETWEEN parent.{$this->_left} AND parent.{$this->_right}")
                        ->where("node.{$this->_tree} = ?", $treeId)
                        ->where("parent.{$this->_tree} = ?", $treeId)
                        ->group("node.{$this->_primary[1]}")
                        ->order("node.{$this->_left}");

        // echo "selectTreeDepthById($treeId) new: ", $select->__toString(), "\n";
        return $select;
    }

    /**
     * FIXME: Documentation.
     * FIXME: Add constraints to statements.
     */
    public function selectSubtreeDepthByIdXXX($treeId = null, $id = null) {
        $select = $this->selectTreeDepth($treeId);
        $select = $this->_addSelectConstraint($select, $treeId, $id, 'node');

        echo "selectSubtreeDepthById($treeId, $id): ", $select->__toString(), "\n";
        return $select;
    }

    /**
     * Retrieve whole tree (as statement!)
     *
     * @access public
     * @return Zend_Db_Table_Select
     */
    public function selectSubtreeById($id, $cols = '*') {

        $select = $this->select()
                        ->from("{$this->_name} AS node", $cols)
//                ->order("node.{$this->_left}")
                        ->from("{$this->_name} AS start", "")
                        ->where("start.{$this->_primary[1]} = ?", $id)
                        ->where("node.{$this->_left} BETWEEN start.{$this->_left} AND start.{$this->_right}")
                        ->where("node.{$this->_tree} = start.{$this->_tree}");

        // echo "selectSubtreeById($id) new: ", $select->__toString(), "\n";
        return $select;
    }

    /**
     * Build select statement for fetching all parents of the node $id,
     * i.e. all nodes between and including root and the node.  A second
     * parameter allows the selection of the returned columns in the same
     * format Zend_Db_Table_Select::from() takes.
     *
     * @see Zend_Db_Table_Select
     *
     * @param int   $id    The ID is the parent node.
     * @param mixed $cols  The columns to show, defaults to '*'.
     *
     * @return Zend_Db_Table_Select
     */
    public function selectParentsById($id, $cols = '*') {

        $select = $this->select()
                        ->from("{$this->_name} AS node", $cols)
                        ->from("{$this->_name} AS target", '')
                        ->where("target.{$this->_left} BETWEEN node.{$this->_left} AND node.{$this->_right}")
                        ->where("target.{$this->_primary[1]} = ?", $id)
                        ->where("node.{$this->_tree} = target.{$this->_tree}")
                        ->order("node.{$this->_left} DESC");
        return $select;
    }

    /*
     * Build select statement for fetching all children of the node $id.
     */

    /**
     * Build select statement for fetching all children of the node $id.  A
     * second parameter allows the selection of the returned columns in the
     * same format Zend_Db_Table_Select::from() takes.
     *
     * @see Zend_Db_Table_Select
     *
     * @param int   $id    The ID is the parent node.
     * @param mixed $cols  The columns to show, defaults to '*'.
     *
     * @return Zend_Db_Table_Select
     */
    public function selectChildrenById($id, $cols = '*') {
        $select = $this->select()
                        ->from("{$this->_name} AS node", $cols)
                        ->where("node.{$this->_parent} = ?", $id)
                        ->order("node.{$this->_sort} ASC")
                        ->order("node.{$this->_left} ASC");
        return $select;
    }

    /*
     * Tree manipulation.
     * FIXME: Documentation.
     */

    /**
     * Create root node for new tree.
     *
     * Actually, this method only initializes left, right and tree id and
     * returns them as array.  The treeId must be added lated.
     *
     * @param  integer $id The ID of the tree.
     * @return array
     */
    public function createRoot() {
        return array(
            $this->_left => 1,
            $this->_right => 2,
        );
    }

    /**
     * Insert new left-most (first) child of $id.
     *
     * Actually, this method only shifts existing left and right ids to make
     * space for the new one.  The new tree, left, right and parent ids will
     * be returned as array.
     *
     * TODO: Decide, if we want to add treeId "outside" or here.
     *
     * @param  integer $id The ID of the parent row (must be unique in schema!).
     * @return array
     */
    public function insertFirstChild($id) {
        $row = $this->getNodeById($id);
        $right = (int) $row->{$this->_right};
        $left = (int) $row->{$this->_left};
        $tree = $row->{$this->_tree};

        // NOTE: ORDER-BY is needed, because MySQL does not support deferred constraint checks
        $this->_db->query("UPDATE {$this->_name} SET {$this->_right} = {$this->_right} + 2 WHERE {$this->_right} > {$left} AND {$this->_tree} = {$tree}  ORDER BY {$this->_right} DESC");
        $this->_db->query("UPDATE {$this->_name} SET {$this->_left}  = {$this->_left} + 2  WHERE {$this->_left}  > {$left} AND {$this->_tree} = {$tree}  ORDER BY {$this->_left}  DESC");

        return array(
//                $this->_tree   => $tree,
            $this->_left => $left + 1,
            $this->_right => $left + 2,
            $this->_parent => $id,
        );
    }

    /**
     * Insert new right-most (last) child of $id.
     *
     * Actually, this method only shifts existing left and right ids to make
     * space for the new one.  The new tree, left, right and parent ids will
     * be returned as array.
     *
     * TODO: Decide, if we want to add treeId "outside" or here.
     *
     * @param  integer $id The ID of the parent row (must be unique in schema!).
     * @return array
     */
    public function insertLastChild($id) {
        $row = $this->getNodeById($id);
        $right = (int) $row->{$this->_right};
        $left = (int) $row->{$this->_left};
        $tree = $row->{$this->_tree};

        // NOTE: ORDER-BY is needed, because MySQL does not support deferred constraint checks.
        $this->_db->query("UPDATE {$this->_name} SET {$this->_right} = {$this->_right} + 2 WHERE {$this->_right} >= {$right} AND {$this->_tree} = {$tree}  ORDER BY {$this->_right} DESC");
        $this->_db->query("UPDATE {$this->_name} SET {$this->_left}  = {$this->_left} + 2  WHERE {$this->_left}  >  {$right} AND {$this->_tree} = {$tree}  ORDER BY {$this->_left}  DESC");

        return array(
//                $this->_tree   => $tree,
            $this->_left => $right,
            $this->_right => $right + 1,
            $this->_parent => $id,
        );
    }

    /**
     * Insert new next (right) sibling of $id.
     *
     * Actually, this method only shifts existing left and right ids to make
     * space for the new one.  The new tree, left, right and parent ids will
     * be returned as array.
     *
     * TODO: Decide, if we want to add treeId "outside" or here.
     *
     * @param  integer $id  The sibling row ID (must be unique in schema!).
     * @throws Opus_Model_Exception
     * @return array
     */
    public function insertNextSibling($id) {
        $row = $this->getNodeById($id);
        $right = (int) $row->{$this->_right};
        $left = (int) $row->{$this->_left};
        $tree = $row->{$this->_tree};
        $parent = $row->{$this->_parent};

        if ($left === 1) {
            throw new Opus_Model_Exception("Root node can't have siblings");
        }

        // NOTE: ORDER-BY is needed, because MySQL does not support deferred constraint checks
        $this->_db->query("UPDATE {$this->_name} SET {$this->_right} = {$this->_right} + 2 WHERE {$this->_right} > {$right} AND {$this->_tree} = {$tree}  ORDER BY {$this->_right} DESC");
        $this->_db->query("UPDATE {$this->_name} SET {$this->_left}  = {$this->_left} + 2  WHERE {$this->_left} > {$right}  AND {$this->_tree} = {$tree}  ORDER BY {$this->_left}  DESC");

        return array(
//                $this->_tree   => $tree,
            $this->_left => $right + 1,
            $this->_right => $right + 2,
            $this->_parent => $parent,
        );
    }

    /**
     * Insert new previous (left) sibling of $id.
     *
     * Actually, this method only shifts existing left and right ids to make
     * space for the new one.  The new tree, left, right and parent ids will
     * be returned as array.
     *
     * TODO: Decide, if we want to add treeId "outside" or here.
     *
     * @param  integer $id  The sibling row ID (must be unique in schema!).
     * @throws Opus_Model_Exception
     * @return array
     */
    public function insertPrevSibling($id) {
        $row = $this->getNodeById($id);
        $right = (int) $row->{$this->_right};
        $left = (int) $row->{$this->_left};
        $tree = $row->{$this->_tree};
        $parent = $row->{$this->_parent};

        if ($left === 1) {
            throw new Opus_Model_Exception("Root node can't have siblings");
        }

        // NOTE: ORDER-BY is needed, because MySQL does not support deferred constraint checks
        $this->_db->query("UPDATE {$this->_name} SET {$this->_right} = {$this->_right} + 2 WHERE {$this->_right} > {$left} AND {$this->_tree} = {$tree}  ORDER BY {$this->_right} DESC");
        $this->_db->query("UPDATE {$this->_name} SET {$this->_left}  = {$this->_left} + 2 WHERE  {$this->_left} >= {$left} AND {$this->_tree} = {$tree}  ORDER BY {$this->_left}  DESC");

        return array(
//                $this->_tree   => $tree,
            $this->_left => $left,
            $this->_right => $left + 1,
            $this->_parent => $parent,
        );
    }

    /**
     * Check if node is root node.
     */
    public function isRoot($data) {
        return array_key_exists($this->_left, $data)
                and ($data[$this->_left] == 1);
    }

    /**
     * Check if node is leaf node.
     */
    public function isLeaf($data) {
        return array_key_exists($this->_left, $data) 
                and array_key_exists($this->_right, $data)
                and ($data[$this->_left] + 1 == $data[$this->_right]);
    }

}
?>
