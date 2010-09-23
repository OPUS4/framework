<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @package     Opus
 * @author     	Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright  	Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: CollectionOld.php -1$
 */

class Opus_CollectionNode extends Opus_Model_AbstractDb {

    /**
     * Specify the table gateway.
     *
     * @see Opus_Db_Collections
     */
    protected static $_tableGatewayClass = 'Opus_Db_CollectionsNodes';


    /**
     * The collections external fields, i.e. those not mapped directly to the
     * Opus_Db_Collections table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
            'PositionKey' => array(),
            'PositionId'  => array(),

            // Will contain the Collection behind the field CollectionId
            'Collection' => array(
                            'model'   => 'Opus_Collection',
                            'fetch'   => 'lazy',
            ),

            // Will contain the CollectionNode to the Root Node
            'Parents' => array(
                            'model'   => 'Opus_CollectionNode',
                            'fetch'   => 'lazy',
            ),

            // Will contain the CollectionNodes with parentId = this->getId
            'Children' => array(
                            'model'   => 'Opus_CollectionNode',
                            'fetch'   => 'lazy',
            ),

            // Pending nodes.
            'PendingNodes' => array(
                            'model'   => 'Opus_CollectionNode',
                            'fetch'   => 'lazy',
            ),
    );


    /**
     * Sets up field by analyzing collection content table metadata.
     *
     * @return void
     */
    protected function _init() {

        /*
         * Fields directly mapped to the table.
        */

        $roleId = new Opus_Model_Field('RoleId');
        $this->addField($roleId);

        $visible = new Opus_Model_Field('Visible');
        $this->addField($visible);

        // The collection_id which has been assigned to this node.
        $collection_id = new Opus_Model_Field('CollectionId');
        $collection_id->setMultiplicity(1);
        $this->addField($collection_id);


        /*
         * External fields.
        */

        // The collection which has been assigned to this node.
        $collection = new Opus_Model_Field('Collection');
        $collection->setMultiplicity(1);
        $this->addField($collection);

        $children = new Opus_Model_Field('Children');
        $children->setMultiplicity('*');
        $this->addField($children);

        // Contains the path back to the root node.
        $parents = new Opus_Model_Field('Parents');
        $parents->setMultiplicity('*');
        $this->addField($parents);


        /*
         * Fields used to define the position of new nodes.
        */
        $positionKeys = array( 'Root',
                'FirstChild', 'LastChild',
                'NextSibling', 'PrevSibling'
        );

        $positionKey = new Opus_Model_Field('PositionKey');
        $positionKey->setDefault($positionKeys);
        $this->addField($positionKey);

        $positionId = new Opus_Model_Field('PositionId');
        $this->addField($positionId);

        $pending_nodes = new Opus_Model_Field('PendingNodes');
        $pending_nodes->setMultiplicity('*');
        $this->addField($pending_nodes);

    }


    /**
     * Returns display name.
     *
     * @return string
     */
    public function getDisplayName() {
        $role_id = $this->getRoleId();
        return get_class($this) . '#' . $this->getId()  . '#' . $role_id;
    }

    /**
     * Returns debug name.
     *
     * @return string
     */
    public function getDebugName() {
        $role_id = $this->getRoleId();
        return get_class($this) . '#' . $this->getId()  . '#' . $role_id;
    }

    /**
     * _storeInternalFields(): Manipulate _primaryTableRow to preserve the
     * nested set property.
     *
     * @return int The primary id of the created row.
     */
    public function _storeInternalFields() {

        if (is_null( $this->getRoleId() )) {
            throw new Exception("RoleId must be set when storing Node!");
        }

        if ($this->isNewRecord()) {

            $nested_sets = $this->_primaryTableRow->getTable();

            // Insert new node into the tree.  The position is specified by
            //     PositionKey = { root,   First-/LastChild, Next-/PrevSibling }
            //     PositionId  = { roleId, ParentId,         SiblingId }
            $position_key = $this->getPositionKey();
            $position_id  = $this->getPositionId();

            if (false === isset($position_key)) {
                throw new Exception('PositionKey must be set!');
            }

            $data = null;
            switch ($position_key) {
                case 'FirstChild':
                    $data = $nested_sets->insertFirstChild($position_id);
                    break;
                case 'LastChild':
                    $data = $nested_sets->insertLastChild($position_id);
                    break;
                case 'NextSibling':
                    $data = $nested_sets->insertNextSibling($position_id);
                    break;
                case 'PrevSibling':
                    $data = $nested_sets->insertPrevSibling($position_id);
                    break;
                case 'Root':
                    $data = $nested_sets->createRoot();
                    break;
                default:
                    throw new Exception("PositionKey($position_key) invalid.");
            }

            // var_dump($data);

            // Dirty fix: After storing the nested set information, the row
            // has still the old information.  But we need the role_id in
            // many other places!
            // $this->setRoleId( $data['role_id'] );

            // Store nested set information in current table row.
            $this->_primaryTableRow->setFromArray($data);
        }

        // var_dump( $this->_primaryTableRow );
        return parent::_storeInternalFields();
    }


    /**
     * Mass-constructur.
     *
     * @param array $array Array of whatever new Opus_Collection(...) takes.
     * @return array|Opus_CollectionNode Constructed Opus_CollectionNode(s).
     *
     * TODO: Refactor this method as fetchAllFromSubselect(...) in AbstractDb?
     * TODO: Code duplication from/in Opus_CollectionRole!
     */

    public static function createObjects($array) {

        $results = array();

        // FIXME: get_called_class() only supported in PHP5 >= 5.3
        //   $class   = get_called_class();
        //   echo "class: $class\n";

        foreach ($array AS $element) {
            $c = new Opus_CollectionNode($element);
            $results[] = $c;
        }

        return $results;
    }



    /**
     * If this node is new, PositionKey and PositionId define the position
     * in the tree.  Do *not* store these values to any external model.
     */

    public function _fetchPositionKey() {
    }
    public function _storePositionKey() {
    }
    public function _fetchPositionId() {
    }
    public function _storePositionId() {
    }


    /**
     * Creating new nodes.
     */

    public function addFirstChild($node = null) {
        return $this->addPendingNodes('FirstChild', $node);
    }

    public function addLastChild($node = null) {
        return $this->addPendingNodes('LastChild', $node);
    }

    public function addNextSibling($node = null) {
        return $this->addPendingNodes('NextSibling', $node);
    }

    public function addPrevSibling($node = null) {
        return $this->addPendingNodes('PrevSibling', $node);
    }



    /**
     * PendingNodes: Add new nodes to the tree.  The position depends on the
     * $key parameter.
     *
     * @param string              $key  (First|Last)Child, (Next|Prev)Sibling.
     * @param Opus_CollectionNode $node
     * @return <type>
     */

    protected function addPendingNodes($key = null, $node = null) {
        if (isset($node)) {
            $node = parent::addPendingNodes($node);
        }
        else {
            $node = parent::addPendingNodes();
        }

        // TODO: Workaround for missing/wrong parent-handling: If parent model
        // TODO: is already stored, we can get it's Id and RoleId before we
        // TODO: reach _storePendingNodes.  (Copy-paste!)
        if ($node->isNewRecord()) {
            $node->setRoleId($this->getRoleId());
            $node->setPositionId($this->getId());
        }

        $node->setPositionKey($key);
        return $node;
    }

    /**
     * This is an internal field, which doesn't get stored in the model.  There
     * is no reason to "fetch" pending nodes.
     */

    public function _fetchPendingNodes() {
    }

    /**
     * Storing pending nodes makes sure, that every node knowns which role_id
     * it belongs to and next to which node it will be inserted.
     */
    public function _storePendingNodes($nodes) {
        if (is_null($nodes)) {
            return;
        }

        if (false === is_array($nodes)) {
            throw new Exception("Expecting array-value argument!");
        }

        foreach ($nodes AS $node) {
            if ($node->isNewRecord()) {
                $node->setRoleId( $this->getRoleId() );
                $node->setPositionId( $this->getId() );
            }
            $node->store();
        }
    }


    /**
     * Store CollectionId field: In this class, two redundant fields exist to
     * hold collection information: Collection and CollectionId.  The field
     * CollectionId always takes precedence over the Collection field and has
     * to be updated by the Collection-setter.
     *
     * If the CollectionId is *not* set, then either
     * (1) No Collection has been assigned and no action is required.
     * (2) A new Collection has been assigned: Propagate role_id and store.
     */

    public function _storeCollectionId($id) {
        $colname   = 'collection_id';

        if (is_null( $this->getRoleId() )) {
            throw new Exception("RoleId must be set when storing Collections!");
        }

        // Trivial case: Id is known.
        if (isset($id)) {
            $this->_primaryTableRow->{$colname} = $id;
            return;
        }

        // Non-trivial case: Collection is set, but Id not known.
        $collection = $this->getCollection();

        if (isset($collection)) {
            $collection->setRoleId( $this->getRoleId() );
            $collection->store();
            $id = $collection->getId();

            if (is_null($id)) {
                throw new Exception("Could not store new collection.");
            }

            $this->_primaryTableRow->{$colname} = $id;
        }

    }


    /**
     * Overriding setter: Only the collection_id field will be stored to the
     * model.  On every change of the collection field, we have to update the
     * collection_id!  If the Id of the new collection is not yet known, just
     * reset the collection_id field to null.
     */

    public function addCollection($collection = null) {
        $collection = isset($collection) ?
                parent::addCollection($collection) :
                parent::addCollection();

        $this->setCollectionId( $collection->getId() );
        return $collection;
    }

    public function setCollection($collection) {
        $collection = isset($collection) ?
                parent::setCollection($collection) :
                parent::setCollection();

        $this->setCollectionId( $collection->getId() );
        return $collection;
    }

    public function _fetchCollection() {
        $collection_id = $this->getCollectionId();
        if (isset( $collection_id )) {
            $collection = new Opus_Collection( $collection_id );
            return $collection;
        }

        return;
    }

    public function _storeCollection($collection = null) {
        if (isset($collection)) {
            // TODO: Erklären, wieso setRoleId hier nicht nötig ist.
            // $collection->setRoleId( $this->getRoleId() );
            $collection->store();
        }
    }

    /**
     * Children
     */

    public function _fetchChildren() {
        if (is_null( $this->getId() )) {
            return;
        }

        // $row = $this->_primaryTableRow;
        // return self::createObjects( $row->findDependentRowset('Opus_Db_CollectionsNodes', 'Parent') );

        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectChildrenById( $this->getId() );
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Compute documents counts.
     *
     * @return int Number of collection Entries.
     * @deprecated Method will be removed shortly!
     * 
     * TODO: Add model fields such we can cache the returned counts.
     */
    public function getNumEntries() {
        if (is_null($this->getCollectionId())) {
            return 0;
        }

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db = $this->_primaryTableRow->getTable()->getAdapter();
        $select = $db->select()->from('link_documents_collections AS ldc', 'count(distinct ldc.document_id)')
                        ->where("collection_id = ?", $this->getCollectionId());

        $count = $db->fetchOne($select);
        return (int) $count;
    }

    /**
     * Returns documents of complete subtree.
     *
     * @return int Number of subtree Entries.
     */

    public function getNumSubtreeEntries() {
        $nestedsets = $this->_primaryTableRow->getTable();
        $subselect = $nestedsets
                ->selectSubtreeById($this->getId(), 'collection_id')
                ->where("start.visible = 1")  // FIXME: Kapselung von Datenbank verletzt!
                ->where("node.visible = 1")  // FIXME: Kapselung von Datenbank verletzt!
                ->distinct();

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db = $this->_primaryTableRow->getTable()->getAdapter();
        $select = $db->select()->from('link_documents_collections AS ldc', 'count(distinct ldc.document_id)')
                        ->where("role_id = ?", $this->getRoleId())
                        ->where("collection_id IN ($subselect)");
                        // TODO add server_state = published condition

        $count = $db->fetchOne($select);
        return (int) $count;
    }

    /**
     * Fetch all documents assigned to this node.
     *
     * @return array|Opus_Document Array of documents.
     *
     * @deprecated
     *
     * TODO: Methode gehört in die Collection-Klasse
     * TODO: Eigentlich gehoerts eher in die Link_Document_Collections Klasse.
     */
    public function getEntries() {
        if ($this->isNewRecord()) {
            return;
        }

        $documents = $this->getCollection()->getDocuments();
        return $documents;
    }

    /**
     * Returns documents of complete subtree.
     *
     * @return Array of Opus_Document entries.
     */
    public function getSubtreeEntries() {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');

        $nestedsets = $this->_primaryTableRow->getTable();
        $subselect = $nestedsets
                ->selectSubtreeById($this->getId(), 'collection_id')
                ->distinct();

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $subselect = $table->getAdapter()->select()
                        ->from("link_documents_collections AS ldc", "document_id")
                        ->where("collection_id IN ($subselect)")
                        ->where('role_id = ?', $this->getRoleId())
                        ->distinct();

        $select = $table->select()
                ->where("id IN ($subselect)");
        $rows = $table->fetchAll($select);

        $results = array();
        foreach ($rows as $row) {
            $results[] = new Opus_Document($row);
        }

        return $results;
    }

    /**
     * LEGACY.
     */

    /**
     * Returns subcollections.
     *
     * @return Opus_Collection
     */
    public function getSubCollection() {
        if (is_null($this->getId())) {
            return;
        }

        // $row = $this->_primaryTableRow;
        // return self::createObjects( $row->findDependentRowset('Opus_Db_CollectionsNodes', 'Parent') );
        // Select all child nodes.
        $nodes_table = $this->_primaryTableRow->getTable();
        $subselect = $nodes_table->selectChildrenById($this->getId(), 'id');

        // Find collections of child nodes.
        // TODO: Add static Opus_Collection::fetchBySubselect.
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Collections');
        $select = $table->select()->where("id IN ($subselect)");

        $rows = $table->fetchAll($select);
        return Opus_Collection::createObjects($rows);
    }

    /**
     * Checks if current node is visible.  Database stores ints 0|1.
     *
     * @return boolean
     */
    public function getVisibility() {
        $visible = $this->getVisible();
        return isset($visible) && $visible === '1';
    }

    /**
     * Sets if node is visible.  Database stores ints 0|1.  Set first parameter
     * to true to make node visible.  Everything != true will hide it.
     *
     * @param  boolean Set to "true" to make node visible.
     * @return void
     */
    public function setVisibility($visible = true) {
        if (isset($visible) && $visible === true) {
            return $this->setVisible(1);
        }
        else {
            return $this->setVisible(0);
        }
    }

    /**
     * Returns nodes for breadcrumb path.
     *
     * @return Array of Opus_CollectionNode objects.
     */

    public function _fetchParents() {
        if (is_null($this->getId())) {
            return;
        }

        // $row = $this->_primaryTableRow;
        // return self::createObjects( $row->findDependentRowset('Opus_Db_CollectionsNodes', 'Parent') );

        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectParentsById( $this->getId() );
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }


    /**
     *  Debugging helper.  Sends the given message to Zend_Log.
     *
     * @param string $message
     */
    protected function logger($message) {
        $registry = Zend_Registry::getInstance();
        $logger = $registry->get('Zend_Log');

        $logger->info("Opus_CollectionNode: $message");
    }

}
?>
