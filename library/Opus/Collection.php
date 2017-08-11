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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Michael Lang <lang@zib.de>
 * @copyright   Copyright (c) 2010-2014, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: CollectionOld.php -1$
 */

class Opus_Collection extends Opus_Model_AbstractDb {

    /**
     * Specify the table gateway.
     *
     * @see Opus_Db_Collections
     */
    protected static $_tableGatewayClass = 'Opus_Db_Collections';

    /**
     * Plugins to load
     *
     * @var array
     */
    protected $_plugins = array(
        'Opus_Model_Plugin_InvalidateDocumentCache' => null,
        'Opus_Collection_Plugin_DeleteSubTree' => null,
    );

    /**
     * The collections external fields, i.e. those not mapped directly to the
     * Opus_Db_Collections table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
        'Theme' => array(
            'fetch' => 'lazy',
        ),
        'Role' => array(
            'model' => 'Opus_CollectionRole',
            'fetch' => 'lazy',
        ),
        'RoleName' => array(
            'fetch' => 'lazy',
        ),
        'RoleDisplayFrontdoor' => array(
            'fetch' => 'lazy',
        ),
        'RoleVisibleFrontdoor' => array(
            'fetch' => 'lazy',
        ),


        'PositionKey' => array(),
        'PositionId' => array(),

        // Will contain the Collections to the Root Collection
        'Parents' => array(
            'model' => 'Opus_Collection',
            'fetch' => 'lazy',
        ),

        // Will contain the Collections with parentId = this->getId
        'Children' => array(
            'model' => 'Opus_Collection',
            'fetch' => 'lazy',
        ),

        // Pending nodes.
        'PendingNodes' => array(
            'model' => 'Opus_Collection',
            'fetch' => 'lazy',
        ),
    );

    /**
     * Sets up field by analyzing collection content table metadata.
     *
     * @return void
     */
    protected function _init() {

        $fields = array('Number', 'Name', 'OaiSubset',
            'RoleId', 'Role', 'RoleName',
            'RoleDisplayFrontdoor', 'RoleVisibleFrontdoor',
            'DisplayFrontdoor',
            'VisiblePublish');
        foreach ($fields as $field) {
            $field = new Opus_Model_Field($field);
            $this->addField($field);
        }

        $visible = new Opus_Model_Field('Visible');
        $visible->setCheckbox(true);
        $this->addField($visible);

        // Add a field to hold collection specific theme.
        $theme = new Opus_Model_Field('Theme');
        $theme->setSelection(true);
        $this->addField($theme);


        /**
         * External fields.
         */

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

        $pendingNodes = new Opus_Model_Field('PendingNodes');
        $pendingNodes->setMultiplicity('*');
        $this->addField($pendingNodes);
    }

    /**
     * Fetch the name of the theme that is associated with this collection.
     *
     * @return string The name of the theme.
     *
     * TODO: Unchecked Copy-Paste.  Check if this method still works.
     * TODO: Create model for these fields - don't ask the database manually!
     * TODO: Use attributes table for this and 'options' on $_externalFields.
     */
    protected function _fetchTheme() {
        if (is_null($this->getId())) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_CollectionsEnrichments');
        $theme = Zend_Registry::get('Zend_Config')->theme; // TODO Weitere Abhängigkeit auf Applikation, oder?

        // Search for theme in database and, if exists, overwrite default theme.
        $select = $table->select()
                        ->where('key_name = ?', "theme")
                        ->where('collection_id = ?', $this->getId());
        $row = $table->fetchRow($select);

        if (!is_null($row)) {
            $theme = $row->value;
        }

        return $theme;
    }

    /**
     * Store the name of the theme that is associated with this collection.
     *
     * @param string The name of the theme.
     *
     * TODO: Unchecked Copy-Paste.  Check if this method still works.
     * TODO: Create model for these fields - don't ask the database manually!
     * FIXME: Add unit test: new Collection(); ->setTheme(); ->store()
     */
    protected function _storeTheme($theme = '') {
        if (is_null($this->getId())) {
            return;
        }

        if (true === is_null($theme)) {
            $theme = '';
        }

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_CollectionsEnrichments');
        $select = $table->select()
                        ->where('key_name = ?', "theme")
                        ->where('collection_id = ?', $this->getId());
        $row = $table->fetchRow($select);

        if ($theme == '' || Zend_Registry::get('Zend_Config')->theme === $theme) {
            // No need to store default theme setting.  Delete row if exists.
            if (isset($row)) {
                $row->delete();
            }
            return;
        }

        if (true === is_null($row)) {
            $row = $table->createRow();
            $row->collection_id = $this->getId();
            $row->key_name = 'theme';
        }

        $row->value = $theme;
        $row->save();
    }

    /**
     * Method to fetch documents-ids assigned to this collection.
     *
     * @return array DocumentId(s).
     */
    public function getDocumentIds() {
        if (is_null($this->getId())) {
            return;
        }

        assert(!is_null($this->getId()));
        assert(!is_null($this->getRoleId()));

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_LinkDocumentsCollections');

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $select = $table->select()
                        ->from("link_documents_collections AS ldc", "document_id")
                        ->where('collection_id = ?', $this->getId())
                        ->distinct();

        return $table->getAdapter()->fetchCol($select);
    }

    /**
     * Method to fetch IDs of all documents in server_state published.
     */
    public function getPublishedDocumentIds() {
        if (is_null($this->getId())) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_LinkDocumentsCollections');

        // FIXME: Don't use internal knowledge of foreign models/tables.
        $select = $table->select()
                        ->from('link_documents_collections AS ldc', 'document_id')
                        ->from('documents AS d', array())
                        ->where('ldc.document_id = d.id')
                        ->where('ldc.collection_id = ?', $this->getId())
                        ->where("d.server_state = 'published'")
                        ->distinct();

        return $table->getAdapter()->fetchCol($select);
    }

    /**
     * Internal method to populate external field.
     */
    protected function _fetchRole() {
        $role = new Opus_CollectionRole($this->getRoleId());
        return $role;
    }

    protected function _fetchDisplayFrontdoor() {
        $displayName = $this->getDisplayName('frontdoor');
        $parentId = $this->getParentNodeId();
        if (!empty($parentId)) {
            $parent = new Opus_Collection($parentId);
            $parentDisplayName = $parent->getDisplayFrontdoor(); // implicitly calls $parent->_fetchDisplayFrontdoor()
            if (!empty($parentDisplayName)) {
                $displayName = $parentDisplayName . ' / ' . $displayName;
            }
        }
        return $displayName;
    }

    /**
     * empty method to prevent storing of read-only field DisplayFrontdoor
     */
    protected function _storeDisplayFrontdoor() {

    }

    /**
     * Internal method to store external field to model.
     */
    protected function _storeRole($role) {
    }

    /**
     * empty method to prevent storing of read-only field RoleDisplayFrontdoor
     */
    protected function _storeRoleDisplayFrontdoor($flag) {
    }

    /**
     * empty method to prevent storing of read-only field RoleVisibleFrontdoor
     */
    protected function _storeRoleVisibleFrontdoor($flag) {
    }

    /**
     * empty method to prevent storing of read-only field RoleName
     */
    protected function _storeRoleName($roleName) {
    }

    /**
     * Fetches contents of role-field "DisplayFrontdoor".
     *
     * @return string
     */
    protected function _fetchRoleDisplayFrontdoor() {
        $role = $this->getRole();
        if (!is_null($role)) {
            return $role->getDisplayFrontdoor();
        }
    }

    /**
     * Fetches contents of role-field "VisibleFrontdoor".
     *
     * @return string
     */
    protected function _fetchRoleVisibleFrontdoor() {
        $role = $this->getRole();
        if (!is_null($role)) {
            if ($role->getVisible() == 1 and $role->getVisibleFrontdoor() == 1) {
                return 'true';
            }
        }
        return 'false';
    }

    /**
     * Fetches role-name.
     *
     * @return string
     */
    protected function _fetchRoleName() {
        $role = $this->getRole();
        if (!is_null($role)) {
            return $role->getDisplayName();
        }
    }

    /**
     * Returns custom string representation depending on role settings.
     *
     * @return string
     */
    public function getDisplayName($context = 'browsing', $role = null) {
        if (!is_null($role) && (!$role instanceof Opus_CollectionRole || $role->getId() != $this->getRoleId())) {
            throw new InvalidArgumentException('given Collection Role is not compatible');
        }

        if (is_null($role)) {
            $role = $this->getRole();
        }
        $fieldnames = $role->_getField('Display' . ucfirst($context))->getValue();
        $display = '';

        if (false === empty($fieldnames)) {
            foreach (explode(',', $fieldnames) as $fieldname) {
                $field = $this->_getField(trim($fieldname));
                if (false === is_null($field)) {
                    $display .= $field->getValue() . ' ';
                }
            }
        }
        else {
            $display = $this->getName();
        }

        /* TODO use role name for root collection?
        if ((strlen(trim($display)) === 0) && $this->isRoot()) {
            $display = $role->getDisplayName();
        }*/

        return trim($display);
    }

    public function getDisplayNameForBrowsingContext($role = null) {
        return $this->getDisplayName('browsing', $role);
    }

    /**
     * Returns the complete string representation for the current collection (consists of
     * Number and Name).
     *
     * @param string $delimiter
     *
     * @return string
     */
    public function getNumberAndName($delimiter = ' ') {
        $name = trim($this->getName());
        $number = trim($this->getNumber());
        if ($number == '') {
            return $name;
        }
        if ($name == '') {
            return $number;
        }
        return $number . $delimiter . $name;
    }


    /**
     * Returns debug name.
     *
     * @return string
     */
    public function getDebugName() {
        return get_class($this) . '#' . $this->getId() . '#' . $this->getRoleId();
    }

    /**
     * Returns the ID of the parent node.
     *
     * @return integer
     */
    public function getParentNodeId() {
        $table = $this->_primaryTableRow->getTable();
        $parentIdField = $table->getParentFieldName();
        return $this->_primaryTableRow->$parentIdField;
    }

    // TODO: Add documentation for method.
    protected function linkDocument($documentId) {
        if (isset($documentId) === false) {
            throw new Exception("linkDocument() needs documend_id parameter.");
        }

        if (is_null($this->getId()) === true) {
            throw new Exception("linkDocument() only on stored records.");
        }

        if (!$this->holdsDocumentById($documentId)) {
            $this->linkDocumentById($documentId);
        }
    }

    /**
     * Add document to current collection by adding an entry in the relation
     * table "link_documents_collections".
     *
     * @param int $documentId
     *
     * TODO: Move method to Opus_Db_LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public function linkDocumentById($documentId = null) {
        if (is_null($this->getId())) {
            throw new Exception("linkDocumentById() is not allowed on NewRecord.");
        }

        if (is_null($documentId)) {
            throw new Exception("linkDocumentById() needs valid document_id.");
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        $insertData = array(
            'collection_id' => $this->getId(),
            'role_id' => $this->getRoleId(),
            'document_id' => $documentId,
        );

        return $db->insert('link_documents_collections', $insertData);
    }

    /**
     * Removes document from current collection by deleting from the relation
     * table "link_documents_collections".
     *
     * @param int $documentId
     *
     * TODO: Move method to Opus_Db_LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public static function unlinkCollectionsByDocumentId($documentId = null) {
        if (is_null($documentId)) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_LinkDocumentsCollections');
        $db = $table->getAdapter();

        $condition = array(
            'document_id = ?' => $documentId
        );

        return $db->delete("link_documents_collections", $condition);
    }

    /**
     * Checks if document is linked to current collection.
     *
     * @param  int  $documentId
     * @return bool
     *
     * TODO: Move method to Opus_Db_LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public function holdsDocumentById($documentId = null) {

        if (is_null($documentId)) {
            return false;
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $select = $db->select()
                        ->from("link_documents_collections AS ldc", "document_id")
                        ->where('collection_id = ?', $this->getId())
                        ->where('document_id = ?', $documentId);

        $result = $db->fetchRow($select);

        if (is_array($result) && isset($result['document_id'])) {
            return true;
        }

        return false;
    }

    /**
     * Overwrites standard toArray() to prevent infinite recursion due to parent collections.
     *
     * @return array A (nested) array representation of the model.
     *
     * FIXME: Part of old API.  Please check, if everything works fine.
     * FIXME: Seems unused.  Check if we still need it.
     */
    public function toArray($call = null) {
        $role = $this->getRole();
        return array(
            'Id' => $this->getId(),
            'RoleId' => $this->getRoleId(),
            'RoleName' => $role->getDisplayName(),
            'DisplayBrowsing' => $this->getDisplayName('browsing'),
            'DisplayFrontdoor' => $this->getDisplayName('frontdoor'),
        );
    }

    /**
     * Returns Xml representation of the collection.
     *
     * @param  array $excludeFields Fields to exclude from the Xml output.
     * @param Opus_Model_Xml_Strategy $strategy Version of Xml to process
     * @return DomDocument Xml representation of the collection.
     */
    public function toXml(array $excludeFields = null, $strategy = null) {
        // TODO: comment why these fields should always be excluded.
        $alwaysExclude = array('Theme');
        if (is_null($excludeFields) === true) {
            $excludeFields = $alwaysExclude;
        }
        else {
            $excludeFields = array_merge($excludeFields, $alwaysExclude);
        }
        return parent::toXml($excludeFields, $strategy);
    }

    /**
     * Returns all collection for given (role_id, collection number) as array
     * with Opus_Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int     $roleId
     * @param  string  $number
     * @return array   Array of Opus_Collection objects.
     */
    public static function fetchCollectionsByRoleNumber($roleId, $number) {
        if (!isset($number)) {
            throw new Exception("Parameter 'number' is required.");
        }

        if (!isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $roleId)
                        ->where('number = ?', "$number");
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection for given (role_id, collection name) as array
     * with Opus_Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int     $roleId
     * @param  string  $name
     * @return array   Array of Opus_Collection objects.
     */
    public static function fetchCollectionsByRoleName($roleId, $name) {
        if (!isset($name)) {
            throw new Exception("Parameter 'name' is required.");
        }

        if (!isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $roleId)
                        ->where('name = ?', $name);
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection for given (role_id) as array
     * with Opus_Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int     $roleId
     * @return array   Array of Opus_Collection objects.
     */
    public static function fetchCollectionsByRoleId($roleId) {
       if (!isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
       }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $roleId);
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection_ids for a given document_id.
     *
     * @param  int    $documentId
     * @return array  Array of collection Ids.
     *
     * FIXME: This method belongs to Opus_Db_Link_Documents_Collections
     */
    public static function fetchCollectionIdsByDocumentId($documentId) {
        if (!isset($documentId)) {
            return array();
        }

        // FIXME: self::$_tableGatewayClass not possible in static methods.
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Collections');

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $select = $table->getAdapter()->select()
                        ->from("link_documents_collections AS ldc", "collection_id")
                        ->where('ldc.document_id = ?', $documentId)
                        ->distinct();

        $ids = $table->getAdapter()->fetchCol($select);
        return $ids;
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
            $c = new Opus_Collection($element);
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
     * Creating new collections.
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

    public function moveAfterNextSibling() {
        $nestedsets = $this->_primaryTableRow->getTable();
        $nestedsets->moveSubTreeAfterNextSibling($this->getId());
    }

    public function moveBeforePrevSibling() {
        $nestedsets = $this->_primaryTableRow->getTable();
        $nestedsets->moveSubTreeBeforePreviousSibling($this->getId());
    }

    public function moveToPosition($position) {
        $nestedSets = $this->_primaryTableRow->getTable();
        $nestedSets->moveSubTreeToPosition($this->getId(), $position);
    }

    public function moveToStart() {
        $nestedSets = $this->_primaryTableRow->getTable();
        $nestedSets->moveSubTreeToPosition($this->getId(), 0);
    }

    /**
     */
    public function moveToEnd() {
        $nestedSets = $this->_primaryTableRow->getTable();
        $nestedSets->moveSubTreeToPosition($this->getId());
    }

    /**
     * _storeInternalFields(): Manipulate _primaryTableRow to preserve the
     * nested set property.
     *
     * @return int The primary id of the created row.
     */
    public function _storeInternalFields() {

        if (is_null($this->getRoleId())) {
            throw new Exception("RoleId must be set when storing Collection!");
        }

        if ($this->isNewRecord()) {

            $nestedSets = $this->_primaryTableRow->getTable();

            // Insert new node into the tree.  The position is specified by
            //     PositionKey = { root,   First-/LastChild, Next-/PrevSibling }
            //     PositionId  = { roleId, ParentId,         SiblingId }
            $positionKey = $this->getPositionKey();
            $positionId  = $this->getPositionId();

            if (false === isset($positionKey)) {
                throw new Exception('PositionKey must be set!');
            }

            $data = null;
            switch ($positionKey) {
                case 'FirstChild':
                    $data = $nestedSets->insertFirstChild($positionId);
                    break;
                case 'LastChild':
                    $data = $nestedSets->insertLastChild($positionId);
                    break;
                case 'NextSibling':
                    $data = $nestedSets->insertNextSibling($positionId);
                    break;
                case 'PrevSibling':
                    $data = $nestedSets->insertPrevSibling($positionId);
                    break;
                case 'Root':
                    $data = $nestedSets->createRoot();
                    break;
                default:
                    throw new Exception("PositionKey($positionKey) invalid.");
            }

            // Dirty fix: After storing the nested set information, the row
            // has still the old information.  But we need the role_id in
            // many other places!
            // $this->setRoleId( $data['role_id'] );

            // Store nested set information in current table row.
            $this->_primaryTableRow->setFromArray($data);
        }

        return parent::_storeInternalFields();
    }

    /**
     * PendingNodes: Add new nodes to the tree.  The position depends on the
     * $key parameter.
     *
     * @param string              $key  (First|Last)Child, (Next|Prev)Sibling.
     * @param Opus_CollectionNode $collection
     * @return <type>
     */
    protected function addPendingNodes($key = null, $collection = null) {
        if (isset($collection)) {
            $collection = parent::addPendingNodes($collection);
        }
        else {
            $collection = parent::addPendingNodes();
        }

        // TODO: Workaround for missing/wrong parent-handling: If parent model
        // TODO: is already stored, we can get it's Id and RoleId before we
        // TODO: reach _storePendingNodes.  (Copy-paste!)
        if ($collection->isNewRecord()) {
            $collection->setRoleId($this->getRoleId());
            $collection->setPositionId($this->getId());
        }

        $collection->setPositionKey($key);
        return $collection;
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
    public function _storePendingNodes($collections) {
        if (is_null($collections)) {
            return;
        }

        if (false === is_array($collections)) {
            throw new Exception("Expecting array-value argument!");
        }

        foreach ($collections AS $collection) {
            if ($collection->isNewRecord()) {
                $collection->setRoleId($this->getRoleId());
                $collection->setPositionId($this->getId());
            }
            $collection->store();
        }
    }

    /**
     * Returns documents of complete subtree.
     *
     * @return int Number of subtree Entries.
     *
     * TODO modify subselect to exclude invisible nodes
     */
    public function getNumSubtreeEntries() {
        $nestedsets = $this->_primaryTableRow->getTable();
        $subselect = $nestedsets
                ->selectSubtreeById($this->getId(), 'id')
                ->where("start.visible = 1")
                ->where("node.visible = 1")
                ->distinct();

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db = $this->_primaryTableRow->getTable()->getAdapter();
        $select = $db->select()
                        ->from('link_documents_collections AS ldc', 'count(distinct ldc.document_id)')
                        ->from('documents AS d', array())
                        ->where("ldc.document_id = d.id")
                        ->where("d.server_state = ?", 'published')
                        ->where("ldc.collection_id IN ($subselect)");

        $count = $db->fetchOne($select);
        return (int) $count;
    }

    /**
     * Filter documents from subtree from a given list of document-ids.  The
     * first argument is mandadory and can be an int-array or a SQL-query used
     * as a subselect.  This query must have only have an id-column.
     *
     * @param  $docIds
     * @return array
     */
    public function filterSubtreeDocumentIds($docIds) {
        if (is_null($docIds) or (is_array($docIds) && empty($docIds))) {
            return array();
        }

        $nestedsets = $this->_primaryTableRow->getTable();
        $subselect = $nestedsets
                ->selectSubtreeById($this->getId(), 'id')
                ->distinct();

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db = $this->_primaryTableRow->getTable()->getAdapter();
        $select = $db->select()->from('link_documents_collections AS ldc', 'ldc.document_id')
                        ->where("ldc.collection_id IN ($subselect)")
                        ->where("ldc.document_id IN (?)", $docIds)
                        ->distinct();

        return $db->fetchCol($select);
    }

    /**
     * Returns nodes for breadcrumb path.
     *
     * @return Array of Opus_Collection objects.
     */

    public function _fetchParents() {
        if (is_null($this->getId())) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectParentsById($this->getId());
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns children of current collection.
     *
     * @return Array of Opus_Collection objects.
     */
    protected function _fetchChildren() {
        if (is_null($this->getId())) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Overwrite _store: We cannot add children directly.  This has to be done
     * via "addLastChild" and "addFirstChild".
     *
     * @return void
     */
    protected function _storeChildren() {
    }


    /**
     * An unexpensive way to find out, if the current collection has children,
     * i.e. if it is a leaf node in the tree.
     */
    public function hasChildren() {
        if ($this->isNewRecord()) {
            return;
        }

        return !$this->_primaryTableRow->getTable()->isLeaf(
            $this->_primaryTableRow->toArray()
        );
    }

    /**
     * Overwrite describe: Do not export external fields to XML.
     *
     * @return array
     */
    public function describe() {
        $excludeFields = array( 'Children', 'Role', 'PendingNodes', 'Parents' );
        return array_diff(parent::describe(), $excludeFields);
    }

    /**
     * isRoot()
     */
    public function isRoot() {
        if ($this->isNewRecord()) {
            return;
        }

        return $this->_primaryTableRow->getTable()->isRoot(
            $this->_primaryTableRow->toArray()
        );
    }

    public function getVisibleChildren() {
        if (is_null($this->getId())) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());
        $select->where("visible = 1");
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    public function hasVisibleChildren() {
        if (is_null($this->getId())) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();
        $select = $table->selectChildrenById($this->getId());
        $select->where("visible = 1");
        $select->reset('columns');
        $select->distinct(true)->columns("count(id)");

        return intval($table->getAdapter()->fetchOne($select)) > 0;
    }

    public function getVisiblePublishChildren() {
        if (is_null($this->getId())) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());
        $select->where("visible_publish = 1");
        $select->where("visible = 1");
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    public function hasVisiblePublishChildren() {
        if (is_null($this->getId())) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();
        $select = $table->selectChildrenById($this->getId());
        $select->where("visible_publish = 1");
        $select->where("visible = 1");
        $select->reset('columns');
        $select->distinct(true)->columns("count(id)");

        return intval($table->getAdapter()->fetchOne($select)) > 0;
    }

    /**
     * Sorts the child nodes by value of model field name.
     */
    public function sortChildrenByName($reverse = false) {
        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());

        $children = $table->getAdapter()->fetchAll($select);

        if ($reverse === false) {
            usort(
                $children, function ($nodeOne, $nodeTwo) {
                if ($nodeOne['name'] == $nodeTwo['name']) {
                    return 0;
                }
                return ($nodeOne['name'] < $nodeTwo['name']) ? -1 : 1;
                }
            );
        }
        else {
            usort(
                $children, function ($nodeOne, $nodeTwo) {
                if ($nodeOne['name'] == $nodeTwo['name']) {
                    return 0;
                }
                return ($nodeOne['name'] > $nodeTwo['name']) ? -1 : 1;
                }
            );
        }

        foreach ($children as $index => $child) {
            $table->moveSubTreeToPosition($child['id'], $index);
        }
    }

    /**
     * Sorts children by value of model field number.
     */
    public function sortChildrenByNumber($reverse = false) {
        $table = $this->_primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());

        $children = $table->getAdapter()->fetchAll($select);

        if ($reverse === false) {
            usort(
                $children, function ($nodeOne, $nodeTwo) {
                if ($nodeOne['number'] == $nodeTwo['number']) {
                    return 0;
                }
                return ($nodeOne['number'] < $nodeTwo['number']) ? -1 : 1;
                }
            );
        }
        else {
            usort(
                $children, function ($nodeOne, $nodeTwo) {
                if ($nodeOne['number'] == $nodeTwo['number']) {
                    return 0;
                }
                return ($nodeOne['number'] > $nodeTwo['number']) ? -1 : 1;
                }
            );
        }

        foreach ($children as $index => $child) {
            $table->moveSubTreeToPosition($child['id'], $index);
        }
    }

    /**
     * Sorts children in the specified order.
     *
     * @param $sortedIds Array with node IDs in desired order
     * @throws InvalidArgumentException if one of the IDs ist not a child node
     */
    public function applySortOrderOfChildren($sortedIds) {
        $table = $this->_primaryTableRow->getTable();
        $table->applySortOrderOfChildren($this->getId(), $sortedIds);
    }

    /**
     * Checks if collection is visible based on settings including parents.
     */
    public function isVisible() {
        $colId = $this->getId();

        // return value for collection that has not been stored yet
        if (is_null($colId)) {
            $visible = $this->getVisible();
            return is_null($visible) ? false : (bool) $visible;
        }

        $table = $this->_primaryTableRow->getTable();

        return $table->isVisible($colId);
    }

}
