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

/**
 *
 * @category    Framework
 * @package     Opus
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

            // Will contain the Collection behind the field CollectionId
            'Parent' => array(
                            'model'   => 'Opus_Collection',
                            'fetch'   => 'lazy',
            ),

            // Will contain the Collection behind the field CollectionId
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

        // TODO: Doku
        $roleId = new Opus_Model_Field('RoleId');
        $this->addField($roleId);

        // TODO: Doku
        // $collectionId = new Opus_Model_Field('CollectionId');
        // $this->addField($collectionId);

        // TODO: Doku
        $visible = new Opus_Model_Field('Visible');
        $this->addField($visible);


        // FIXME: Is this the best way to define positions?
        $positionKeys = array( 'Root',
                'FirstChild', 'LastChild',
                'NextSibling', 'PrevSibling'
        );

        $positionKey = new Opus_Model_Field('PositionKey');
        $positionKey->setDefault($positionKeys);
        $this->addField($positionKey);

        // FIXME: Is this the best way to define positions?
        // FIXME: This field is for internal use only.
        $positionId = new Opus_Model_Field('PositionId');
        $this->addField($positionId);

        // TODO: Doku
        $collection = new Opus_Model_Field('Collection');
        $collection->setMultiplicity(1);
        $this->addField($collection);

        // TODO: Doku
        $collection_id = new Opus_Model_Field('CollectionId');
        $collection_id->setMultiplicity(1);
        $this->addField($collection_id);

        // TODO: Doku
        $pending_nodes = new Opus_Model_Field('PendingNodes');
        $pending_nodes->setMultiplicity('*');
        $this->addField($pending_nodes);

        // TODO: Doku
        $children = new Opus_Model_Field('Children');
        $children->setMultiplicity('*');
        $this->addField($children);
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
     * @return array|Opus_Collection Array of constructed Opus_Collections.
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
     * PendingNodes: Neu erstellten/hinzugefügte Knoten, die gespeichert
     * werden müssen.
     */

    protected function addPendingNodes($key = null, $node = null) {
        if (isset($node)) {
            $node = parent::addPendingNodes($node);
        }
        else {
            $node = parent::addPendingNodes();
        }

        $node->setPositionKey($key);
        return $node;
    }

    public function _fetchPendingNodes() {
    }

    // TODO: Doku.  Erklaeren, dass RoleId gesetzt sein muss vor "->store()".
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
     * CollectionId
     *
     * TODO: Dokumentation
     * TODO: Zusammenhang von Collection und CollectionId erklären!
     * TODO: RoleId setzen, bevor c->store() aufgerufen wird.
     */

    public function _storeCollectionId($id) {
        $fieldname = 'CollectionId';
        $colname   = 'collection_id';

        $field = $this->_fields[$fieldname];
        $value = $field->getValue();

        // TODO: Soll nur geaendert werden, wenn sich das Feld CollectionId
        // TODO: oder die Collection geändert hat.  Bitte sicherstellen, dass
        // TODO: keine überflüssigen Updates stattfinden.

        if (is_null( $this->getRoleId() )) {
            throw new Exception("RoleId must be set when storing Collections!");
        }

        if (! $field->isModified()) {
            return;
        }

        if (! isset($value)) {
            $collection = $this->getCollection();
            $collection->setRoleId( $this->getRoleId() );
            $value = $collection->store();
        }

        $this->_primaryTableRow->{$colname} = $value;
    }


    /**
     * Collection
     *
     * TODO: Dokumentation
     * TODO: Wichtig: Collections ändern muss auch CollectionIds ändern.
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
        if (!is_null( $collection_id )) {
            $collection = new Opus_Collection( $collection_id );
            // echo "fetchCollection returns ", $collection,"\n";
            return $collection;
        }

        // echo "fetchCollection returns null\n";
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
        if ($this->isNewRecord()) {
            // TODO: Check if doing nothing on new records is reasonable.
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
     */

    // TODO: Setze getter durch _fetchNum
    public function getNumEntries() {
        if (is_null($this->getCollectionId())) {
            return 0;
        }

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db = $this->_primaryTableRow->getTable()->getAdapter();
        $select = $db->select()->from('link_documents_collections AS ldc', 'count(distinct ldc.document_id)')
                ->where("collection_id = ?", $this->getCollectionId());

        $count = $db->fetchOne($select);
        return $count;
    }

    // TODO: Setze getter durch _fetchNumSubtreeEntries
    public function getNumSubtreeEntries() {
        $nestedsets = $this->_primaryTableRow->getTable();
        $subselect = $nestedsets
                ->selectSubtreeById($this->getId(), 'collection_id')
                ->distinct();

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db = $this->_primaryTableRow->getTable()->getAdapter();
        $select = $db->select()->from('link_documents_collections AS ldc', 'count(distinct ldc.document_id)')
                ->where("role_id = ?", $this->getRoleId())
                ->where("collection_id IN ($subselect)");

        $count = $db->fetchOne($select);
        return $count;
    }

    // TODO: Diese Methode gehört in die Collection-Klasse.
    public function getEntries() {
        if ($this->isNewRecord()) {
            return;
        }

        $documents = $this->getCollection()->getDocuments();
        return $documents;
    }

    // TODO: Setze getter durch _fetchSubtreeEntries
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

    public function getSubCollection() {
        $collection = $this->getCollection();
        if (isset($collection)) {
           return $collection->getSubCollections();
        }
        return array();
    }

}

?>
