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
 * @copyright  	Copyright (c) 2010, OPUS 4 development team
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
     * Path to location of available themes.
     *
     * @var string
     */
    protected static $_themesPath = '';
    /**
     * Available themes from directory self::$_themesPath.
     *
     * @var array
     */
    protected static $_themes = array();


    /**
     * Name of the default theme.
     *
     */
    const DEFAULT_THEME_NAME = 'default';


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
        'SubCollections' => array(
            'model' => 'Opus_Collection',
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

        $fields = array('Number', 'Name', 'OaiSubset', 'Visible',
            'RoleId', 'Role', 'RoleName',
            'RoleDisplayFrontdoor', 'RoleVisibleFrontdoor');
        foreach ($fields as $field) {
            $field = new Opus_Model_Field($field);
            $this->addField($field);
        }

        // Add a field to hold subcollections
        $subCollections = new Opus_Model_Field('SubCollections');
        $subCollections->setMultiplicity('*');
        $this->addField($subCollections);

        // Add a field to hold collection specific theme.
        $theme = new Opus_Model_Field('Theme');
        $theme->setDefault(self::$_themes);
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

        $pending_nodes = new Opus_Model_Field('PendingNodes');
        $pending_nodes->setMultiplicity('*');
        $this->addField($pending_nodes);

    }

    /**
     * Overwrites store procedure.
     *
     * @return void
     */
    protected function _storeParentCollections() {
        // FIXME: Every method, that returns external fields, should store them!
        // Storing parent collections is not possible.  But:
        // FIXME: Maybe we want to propagate changes to the parent?
        throw new Exception("Method not supported.  Check API.");

        // Recursive updating can be expensive! - Try to avoid this.
        if (false === $this->_getField('ParentCollections', true)->isModified()) {
            return;
        }
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
        // $config = Zend_Registry::get('Zend_Config');

        // Find default theme: if not set in config file, set to default.
        $theme = isset($config->theme) === true ? $config->theme : self::DEFAULT_THEME_NAME;

        // Search for theme in database and, if exists, overwrite default theme.
        if (false === is_null($this->getId())) {
            $select = $table->select()
                            ->where('key_name = ?', "theme")
                            ->where('collection_id = ?', $this->getId());
            $row = $table->fetchRow($select);

            if (false === is_null($row)) {
                $theme = $row->value;
            }
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
    protected function _storeTheme($theme) {
        if (is_null($this->getId())) {
            return;
        }

        if (true === is_null($theme)) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_CollectionsEnrichments');
        $select = $table->select()
                        ->where('key_name = ?', "theme")
                        ->where('collection_id = ?', $this->getId());
        $row = $table->fetchRow($select);

        if (self::DEFAULT_THEME_NAME === $theme) {
            // No need to store default theme setting.
            $row->delete();
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
                        ->where('role_id = ?', $this->getRoleId())
                        ->distinct();

        $results = $table->getAdapter()->fetchAll($select);

        return $results;
    }

    /**
     * Internal method to populate external field.
     */
//    protected static $_role_cache = null;
    protected function _fetchRole() {
        $role = new Opus_CollectionRole($this->getRoleId());
        return $role;

        // TODO: Experiments with role object caching.
        // TODO: protected static $_role_cache = null;
//        if ( !is_null(self::$_role_cache) && self::$_role_cache->getId() === $this->getRoleId() ) {
//            $this->logger('Role: Restoring from cache.');
//        }
//        else {
//            $this->logger('Role: new');
//            self::$_role_cache = new Opus_CollectionRole( $this->getRoleId() );
//        }
//
//        return self::$_role_cache;
    }

    /**
     * Internal method to store external field to model.
     */
    protected function _storeRole($role) {
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
    public function getDisplayName($context = 'browsing') {
        $role = $this->getRole();
        $fieldnames = $role->_getField('Display' . ucfirst($context))->getValue();
        $display = '';

        if (false === empty($fieldnames)) {
            foreach (explode(',', $fieldnames) as $fieldname) {
                $field = $this->_getField(trim($fieldname));
                if (false === is_null($field)) {
                    $display .= $field->getValue() . ' ';
                }
            }
        } else {
            $display = $this->getName();
        }

        return trim($display);
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
     * Set location of available themes.
     *
     * @param  string $path
     */
    public static function setThemesPath($path) {
        if (is_dir($path) === false) {
            throw new InvalidArgumentException("Argument should be a valid path.");
        }

        $themes = array();
        foreach (glob($path . '/*') as $entry) {
            if (true === is_dir($entry)) {
                $theme = basename($entry);
                $themes[$theme] = $theme;
            }
        }

        self::$_themesPath = $path;
        self::$_themes = $themes;
    }

    /**
     * Returns the OAI set name that corresponds with this collection.
     *
     * @return string The name of the OAI set.
     */
    public function getOaiSetName() {
        return $this->getRole()->getOaiName() . ':' . $this->getOaiSubset();
    }

    // TODO: Add documentation for method.
    public function linkDocument($document_id) {
        if (isset($document_id) === false) {
            throw new Exception("linkDocument() needs documend_id parameter.");
        }

        if (is_null($this->getId()) === true) {
            throw new Exception("linkDocument() only on stored records.");
        }

        if (!$this->holdsDocumentById($document_id)) {
            $this->linkDocumentById($document_id);
        }
    }

    /**
     * Add document to current collection by adding an entry in the relation
     * table "link_documents_collections".
     *
     * @param int $document_id
     *
     * TODO: Move method to Opus_Db_LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public function linkDocumentById($document_id = null) {
        if (is_null($this->getId())) {
            throw new Exception("linkDocumentById() is not allowed on NewRecord.");
        }

        if (is_null($document_id)) {
            throw new Exception("linkDocumentById() needs valid document_id.");
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        $insert_data = array(
            'collection_id' => $this->getId(),
            'role_id' => $this->getRoleId(),
            'document_id' => $document_id,
        );

        return $db->insert('link_documents_collections', $insert_data);
    }

    /**
     * Removes document from current collection by deleting from the relation
     * table "link_documents_collections".
     *
     * @param int $document_id
     *
     * TODO: Move method to Opus_Db_LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public function unlinkDocumentById($document_id = null) {
        if (is_null($this->getId()) || is_null($document_id)) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        $condition = array(
            'collection_id = ?' => $this->getId(),
            'document_id = ?' => $document_id
        );

        return $db->delete("link_documents_collections", $condition);
    }

    /**
     * Checks if document is linked to current collection.
     *
     * @param  int  $document_id
     * @return bool
     *
     * TODO: Move method to Opus_Db_LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public function holdsDocumentById($document_id = null) {

        if (is_null($document_id)) {
            return false;
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $select = $db->select()
                        ->from("link_documents_collections AS ldc", "document_id")
                        ->where('collection_id = ?', $this->getId())
                        ->where('document_id = ?', $document_id);

        $result = $db->fetchRow($select);

        if (is_array($result) && array_key_exists('document_id', $result)) {
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
        $result = array(
            'Id' => $this->getId(),
            'RoleId' => $this->getRoleId(),
            'RoleName' => $role->getDisplayName(),
            'DisplayBrowsing' => $this->getDisplayName('browsing'),
            'DisplayFrontdoor' => $this->getDisplayName('frontdoor'),
            'DisplayOai' => $this->getDisplayName('oai'),
        );

        $exclude_fields = array('SubCollections', 'Theme', 'Role');
        $search_fields = array_diff(array_keys($this->_fields), $exclude_fields);

        foreach ($search_fields as $fieldname) {
            $field = $this->_getField($fieldname);

            if (!isset($field)) {
                continue;
            }

            $fieldvalue = $field->getValue();

            if ($field->hasMultipleValues()) {
                $fieldvalues = array();
                foreach ($fieldvalue as $value) {
                    if ($value instanceof Opus_Collection) {
                        $val = $value->toArray($fieldname);
                        if (false !== $val) {
                            $fieldvalues[] = $val;
                        }
                    } else {
                        $fieldvalues[] = $value;
                    }
                }
                if (false === empty($fieldvalues)) {
                    $result[$fieldname] = $fieldvalues;
                }
            } else {
                if ($fieldvalue instanceof Opus_Collection) {
                    $val = $fieldvalue->toArray($fieldname);
                    if (false !== $val) {
                        $result[$fieldname] = $val;
                    }
                } else {
                    $result[$fieldname] = $fieldvalue;
                }
            }
        }
        return $result;
    }

    /**
     * Returns Xml representation of the collection.
     *
     * @param  array $excludeFields Fields to exclude from the Xml output.
     * @param Opus_Model_Xml_Strategy $strategy Version of Xml to process
     * @return DomDocument Xml representation of the collection.
     */
    public function toXml(array $excludeFields = null,  $strategy = null) {
        // TODO: comment why these fields should always be excluded.
        $alwaysExclude = array('ParentCollection', 'SubCollections', 'Theme');
        if (is_null($excludeFields) === true) {
            $excludeFields = $alwaysExclude;
        } else {
            $excludeFields = array_merge($excludeFields, $alwaysExclude);
        }
        return parent::toXml($excludeFields, $strategy);
    }

    /**
     * Returns all collection for given (role_id, collection number) as array
     * with Opus_Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int     $role_id
     * @param  string  $number
     * @return array   Array of Opus_Collection objects.
     */
    public static function fetchCollectionsByRoleNumber($role_id, $number) {
        if (!isset($number)) {
            throw new Exception("Parameter 'number' is required.");
        }

        if (!isset($role_id)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $role_id)
                        ->where('number = ?', $number);
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

      /**
     * Returns all collection for given (role_id, collection name) as array
     * with Opus_Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int     $role_id
     * @param  string  $name
     * @return array   Array of Opus_Collection objects.
     */
    public static function fetchCollectionsByRoleName($role_id, $name) {
        if (!isset($name)) {
            throw new Exception("Parameter 'name' is required.");
        }

        if (!isset($role_id)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $role_id)
                        ->where('name = ?', $name);
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection for given (role_id) as array
     * with Opus_Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int     $role_id
     * @return array   Array of Opus_Collection objects.
     */
    public static function fetchCollectionsByRoleId($role_id) {
       if (!isset($role_id)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $role_id);
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection_ids for a given document_id.
     *
     * @param  int    $document_id
     * @return array  Array of collection Ids.
     *
     * FIXME: This method belongs to Opus_Db_Link_Documents_Collections
     */
    public static function fetchCollectionIdsByDocumentId($document_id) {
        if (!isset($document_id)) {
            return array();
        }

        // FIXME: self::$_tableGatewayClass not possible in static methods.
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Collections');

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $select = $table->getAdapter()->select()
                        ->from("link_documents_collections AS ldc", "collection_id")
                        ->where('ldc.document_id = ?', $document_id)
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

    /**
     * _storeInternalFields(): Manipulate _primaryTableRow to preserve the
     * nested set property.
     *
     * @return int The primary id of the created row.
     */
    public function _storeInternalFields() {

        if (is_null( $this->getRoleId() )) {
            throw new Exception("RoleId must be set when storing Collection!");
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
                $collection->setRoleId( $this->getRoleId() );
                $collection->setPositionId( $this->getId() );
            }
            $collection->store();
        }
    }

    /**
     * Returns documents of complete subtree.
     *
     * @return int Number of subtree Entries.
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
        $select = $db->select()->from('link_documents_collections AS ldc', 'count(distinct ldc.document_id)')
                        ->where("role_id = ?", $this->getRoleId())
                        ->where("collection_id IN ($subselect)");
                        // TODO add server_state = published condition

        $count = $db->fetchOne($select);
        return (int) $count;
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

}
?>
