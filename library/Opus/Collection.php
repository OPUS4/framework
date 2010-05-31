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
            'Nodes' => array(
                            'model' => 'Opus_CollectionNode',
                            'fetch' => 'lazy',
            ),

            'Theme' => array(
                            'fetch' => 'lazy',
            ),

            'Role' => array(
                            'model' => 'Opus_CollectionRole',
                            'fetch' => 'lazy',
            ),

            'SubCollections' => array(
                            'model' => 'Opus_Collection',
                            'fetch' => 'lazy',
            ),
//            'ParentCollections' => array(
//                            'model' => 'Opus_Collection',
//                            'fetch' => 'lazy',
//            ),

            'Documents' => array(
                            'model' => 'Opus_Document',
                            'fetch' => 'lazy',
            ),

            'Visibility' => array(),
            'SeveralAppearances' => array(),
            'Theme' => array(),
    );


    /**
     * Sets up field by analyzing collection content table metadata.
     *
     * @return void
     */
    protected function _init() {

        // $fields = array('SubsetKey', 'Name', 'Visible', 'RoleId');
        $fields = array('RoleId', 'Role', 'Name', 'Number',
            'RoleName', 'RoleDisplayFrontdoor' );
        foreach ($fields as $field) {
            $field = new Opus_Model_Field($field);
            $this->addField($field);
        }

        // Add a field to hold subcollections
        $subCollections = new Opus_Model_Field('SubCollections');
        $subCollections->setMultiplicity('*');
        $this->addField($subCollections);
//
//        // Add a field to hold parent collections
//        $parentCollections = new Opus_Model_Field('ParentCollections');
//        $parentCollections->setMultiplicity('*');
//        $this->addField($parentCollections);

        // TODO: New field.  Create getter/setter.
        $documents = new Opus_Model_Field('Documents');
        $documents->setMultiplicity('*');
        $this->addField($documents);

        // Add a field to hold collection specific theme.
        $theme = new Opus_Model_Field('Theme');
        $theme->setDefault(self::$_themes);
        $theme->setSelection(true);
        $this->addField($theme);

        // TODO: New field.  Create getter/setter.
        $nodes = new Opus_Model_Field('Nodes');
        $nodes->setMultiplicity('*');
        $this->addField($nodes);
    }


    /**
     * Returns custom string representation depending on role settings.
     *
     * @return string
     *
     * TODO: Implement collections_attributes and change this stub-method.
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
        return get_class($this) . '#' . $this->getId()  . '#' . $this->getRoleId();
    }


    /**
     * Fetch all children of the current node.
     * 
     * FIXME: Documentation.
     */
    public function _fetchSubCollections() {
        if ($this->isNewRecord()) {
            return;
        }

        $parent_id = $this->getNode()->getId();
        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        $subselect = "SELECT collection_id FROM collections_nodes WHERE parent_id = ? AND collection_id IS NOT NULL ORDER BY left_id";
        $subselect = $db->quoteInto($subselect, $parent_id);

        $select = $table->select()
                ->where("id IN ($subselect)");
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Overwrites store procedure.
     *
     * @return void
     */
    protected function _storeSubCollections($subCollections) {
        foreach ($subCollections AS $collection) {
            $collection->store();
        }
    }


    /**
     *
     * @param Opus_Collection $subCollections (optional)
     * @return Opus_Collection The added collection.
     */
    public function addSubCollections($subCollections = null) {
        throw new Exception("Method not supported.  Check API.");

        if (isset($subCollections)) {
            $subCollections = parent::addSubCollections($subCollections);
        }
        else {
            $subCollections = parent::addSubCollections();
        }

        return $subCollections;
    }


    /**
     * Fetch all nodes between (including) the root and this node.
     * FIXME: Documentation.
     */
    public function _fetchParentCollections() {
        throw new Exception("Method not supported.  Check API.");

        if ($this->isNewRecord()) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();
        $select = $table->selectParentsById( $this->getId() );
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
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
        if ($this->isNewRecord()) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_CollectionsThemes');
        $config = Zend_Registry::get('Zend_Config');

        // Find default theme: if not set in config file, set to default.
        $theme = isset($config->theme) === true ? $config->theme : self::DEFAULT_THEME_NAME;

        // Search for theme in database and, if exists, overwrite default theme.
        if (false === $this->isNewRecord()) {
            $select = $table->select()
                    ->where('role_id = ?', $this->getRoleId())
                    ->where('collection_id = ?', $this->getId());
            $row = $table->fetchRow($select);

            if (false === is_null($row)) {
                $theme = $row->theme;
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
        if ($this->isNewRecord()) {
            // FIXME: Maybe there is something to be done on isNewRecord?
            return;
        }

        if (true === is_null($theme)) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_CollectionsThemes');
        $select = $table->select()
                ->where('role_id = ?', $this->getRoleId())
                ->where('collection_id = ?', $this->getId());
        $row = $table->fetchRow($select);

        if (true === is_null($row)) {
            $row = $table->createRow();
        }

        if (self::DEFAULT_THEME_NAME === $theme) {
            // No need to store default theme setting.
            $row->delete();
        } else {
            $row->role_id = $this->getRoleId();
            $row->collection_id = $this->getId();
            $row->theme = $theme;
            $row->save();
        }
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
                $themes[ $theme ] = $theme;
            }
        }

        self::$_themesPath = $path;
        self::$_themes     = $themes;
    }


    /**
     * Returns the OAI set name that corresponds with this collection.
     *
     * @return string The name of the OAI set.
     *
     * TODO: Unchecked Copy-Paste.  Check if this method still works.
     */
    public function getOaiSetName() {
        throw new Exception("Unchecked Copy-Paste.  Write unit tests first.");

        $role = $this->getRole();
        $oaiPrefix = $role->getOaiName();
        $oaiPostfixColumn = $role->getDisplayOai();
        $accessor = 'get' . ucfirst($oaiPostfixColumn);
        $oaiPostfix = $this->$accessor();
        return $oaiPrefix . ':' . $oaiPostfix;
    }


    /**
     * FIXME: Documentation.
     * FIXME: Only use RoleId
     */
    public static function getAllByRoleId($role_id) {
        throw new Exception("Unchecked Copy-Paste.  Write unit tests first.");

        if (false === isset($role_id)) {
            throw new Exception("role_id not defined.");
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $role_id);
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Fetches all collections for a given sub-SELECT.  The sub-SELECT must
     * have a collection_id column.  Additional columns are not allowed.
     *
     * @param  mixed $subselect       Subselect statement with id column.
     * @return array|Opus_Collection  All fetched collections.
     */
    public static function fetchBySubSelectXXX($subselect) {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->where("id IN ($subselect)");

        $rows = $table->fetchAll($select);
        return self::createObjects($rows);
    }

    /**
     * Internal method to fetch documents for this collection.  Is called by the
     * model, do not use manually.
     *
     * @return Opus_Document|array Document(s).
     */
    protected function _fetchDocuments() {
        if ($this->isNewRecord()) {
            return;
        }

        assert( !is_null( $this->getId() ) );
        assert( !is_null( $this->getRoleId() ) );

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $subselect = $table->getAdapter()->select()
                ->from("link_documents_collections AS ldc", "document_id")
                ->where('collection_id = ?', $this->getId())
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
     * Internal method for storing *and* linking documents.  Is called by the
     * model, do not use manually.
     *
     * @param mixed $documents
     */
    protected function _storeDocuments($documents = null) {
        return;

        assert(is_array($documents));
        assert( !is_null( $this->getId() ) );
        assert( !is_null( $this->getRoleId() ) );

        if (is_null( $this->getRoleId() )) {
            var_dump($this);
            throw new Exception("foobar");
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        foreach ($documents AS $document) {
            $add_link = false;

            if ($document->isNewRecord()) {
                $add_link = true;
            }
            else if ( !$this->holdsDocument($document->getId()) ) {
                $add_link = true;
            }

            $document->store();

            if (true === $add_link) {
                $this->linkDocument( $document->getId() );
            }
        }

    }

    /**
     * FIXME: Documentation.
     *
     * @param <type> $document_id
     * @return <type>
     */
    public function linkDocument($document_id = null) {
        if ($this->isNewRecord()) {
            throw new Exception("linkDocument() is not allowed on NewRecord.");
        }

        if (is_null($document_id)) {
            throw new Exception("linkDocument() needs valid document_id.");
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        $insert_data = array(
                'collection_id' => $this->getId(),
                'role_id'       => $this->getRoleId(),
                'document_id'   => $document_id,
        );

        return $db->insert('link_documents_collections', $insert_data);
    }

    /**
     * FIXME: Documentation.
     *
     * @param <type> $document_id
     * @return <type>
     */
    public function unlinkDocument($document_id = null) {
        if ($this->isNewRecord() || is_null($document_id)) {
            return;
        }

        $table = $this->_primaryTableRow->getTable();
        $db = $table->getAdapter();

        $condition = array(
                'collection_id' => $this->getId(),
                'document_id'   => $document_id,
        );

        return $db->delete("link_documents_collections", $condition);
    }

    /**
     * FIXME: Documentation.
     *
     * @param <type> $document_id
     * @return <type>
     */
    public function holdsDocument($document_id = null) {

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

//    /**
//     * Overwrite standard deletion in favour of collections history tracking.
//     *
//     * @return void
//     */
//    public function delete() {
//        // FIXME: Deleting linked collections would delete whole subtrees.
//        // FIXME: Delete fails, if there are still subcollections.
//
//        parent::delete();
//    }


//    /**
//     * Un-deleting a collection.
//     *
//     * @return void
//     */
//    public function undelete() {
//        // FIXME: Method not implemented.  Please refactor from old API.
//        // FIXME: Search for new Opus_Collection(null, $role) in current code!
//        throw new Exception('Method not implemented.  Please refactor from old API.');
//
//        // Opus_Collection_Information::undeleteCollection($this->__role_id, (int) $this->getId());
//    }


    /**
     * Overwrites standard toArray() to prevent infinite recursion due to parent collections.
     *
     * @return array A (nested) array representation of the model.
     *
     * FIXME: Part of old API.  Please check, if everything works fine.
     */
    public function toArray($call = null) {
        $this->logger('toArray');
        return array();

        // FIXME: Method not implemented.  Please refactor from old API.
        // FIXME: Search for new Opus_Collection(null, $role) in current code!
        throw new Exception('Method not implemented.  Please refactor from old API.');

        $role = $this->getRole();

        $result = array(
                'Id' => $this->getId(),
                'RoleId' => $this->getRoleId(),
                'RoleName' => $role->getDisplayName(),
                'DisplayBrowsing' => $this->getDisplayName('browsing'),
                'DisplayFrontdoor' => $this->getDisplayName('frontdoor'),
                'DisplayOai' => $this->getDisplayName('oai'),
        );

        return $result;

        foreach (array_keys($this->_fields) as $fieldname) {
            $field = $this->_getField($fieldname);
            $fieldvalue = $field->getValue();

            if ('SubCollection' === $call AND ('SubCollection' === $fieldname
                            OR 'ParentCollection' === $fieldname)
                    OR 'ParentCollection' === $call AND 'SubCollection' === $fieldname
            ) {
                continue;
            }
            if ('ParentCollection' === $call AND '1' === $this->getId()) {
                return false;
            }

            if ($field->hasMultipleValues()) {
                $fieldvalues = array();
                foreach($fieldvalue as $value) {
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
     * @return DomDocument Xml representation of the collection.
     */
    public function toXml(array $excludeFields = null) {
        $this->logger('toXml');
        return parent::toXml(array('ParentCollection', 'Nodes', 'SubCollection', 'SubCollections', 'Theme', 'Documents'));

        // FIXME: Method not implemented.  Please refactor from old API.
        // FIXME: Search for new Opus_Collection(null, $role) in current code!
        throw new Exception('Method not implemented.  Please refactor from old API.');

        // FIXME: Doesn't make use of $excludeFields!
        return parent::toXml(array('ParentCollection'));
    }


    /**
     * LEGACY.
     */
    public function getSubCollection() {
        return $this->getSubCollections();
    }


    // This can be very helpful.
    public function getFoo() {
        return new Opus_CollectionRole( $this->getRoleId() );
        return $this->_primaryTableRow->findParentRow('Opus_Db_CollectionsRoles');
    }

    public function getNode() {
        $nodes = $this->getNodes();

        if (count($nodes) === 1) {
            return $nodes[0];
        }
        else if (count($nodes) > 1) {
            throw new Exception("Collections linked to more than one node are currently not supported!");
        }

        return;
    }

    public function getEntries() {
        if ($this->isNewRecord()) {
            return array();
        }

        $documents = $this->getDocuments();
        return $documents;
    }


    public static function fetchCollectionIdsByDocumentId($document_id) {
        if (! isset ($document_id)) {
            return array();
        }

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



    // TODO: Experiments with role object caching.
    protected static $_role_cache = null;

    protected function _fetchRole() {
        $role = new Opus_CollectionRole( $this->getRoleId() );
        return $role;

        // TODO: Experiments with role object caching.
        if ( !is_null(self::$_role_cache) && self::$_role_cache->getId() === $this->getRoleId() ) {
            $this->logger('Role: Restoring from cache.');
        }
        else {
            $this->logger('Role: new');
            self::$_role_cache = new Opus_CollectionRole( $this->getRoleId() );
        }

        return self::$_role_cache;
    }

    protected function _fetchRoleDisplayFrontdoor() {
        return $this->getRole()->getDisplayFrontdoor();
    }

    protected function _fetchRoleName() {
        return $this->getRole()->getDisplayName();
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

    // FIXME: Debugging.
    protected function logger($message) {
        $registry = Zend_Registry::getInstance();
        $logger = $registry->get('Zend_Log');
        $logger->info("Opus_Collection: $message");
    }
}

?>
