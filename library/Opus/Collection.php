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
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use DOMDocument;
use Exception;
use InvalidArgumentException;
use Opus\Common\CollectionInterface;
use Opus\Common\Config;
use Opus\Common\Model\NotFoundException;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Opus\Model\Xml\StrategyInterface;

use function array_diff;
use function array_merge;
use function assert;
use function explode;
use function intval;
use function is_array;
use function trim;
use function ucfirst;
use function usort;

/**
 * Collection model for documents.
 *
 * phpcs:disable
 *
 * @method void setNumber(string $number)
 * @method string getNumber()
 * @method void setName(string $name)
 * @method string getName()
 * @method void setOaiSubset(string $oaiSubset)
 * @method string getOaiSubset()
 * @method void setVisible(boolean $visible)
 * @method boolean getVisible()
 * @method void setVisiblePublish(boolean $visiblePublish)
 * @method boolean getVisiblePublish
 *
 * Fields proxied from Opus\CollectionRole
 * @method void setRoleId(integer $roleId) // TODO correct?
 * @method integer getRoleId()
 * @method void setRole(CollectionRole $role)
 * @method CollectionRole getRole()
 * @method void setRoleDisplayFrontdoor() // TODO
 * @method void setRoleVisibleFrontdoor() // TODO
 * @method string getDisplayFrontdoor() // TODO
 *
 * TODO check what output array for Opus\Collection looks like - document!!!
 */
class Collection extends AbstractDb implements CollectionInterface
{
    /**
     * Specify the table gateway.
     *
     * @see \Opus\Db\Collections
     */
    protected static $tableGatewayClass = Db\Collections::class;

    /**
     * Plugins to load
     *
     * @var array
     */
    public function getDefaultPlugins()
    {
        return [
            Model\Plugin\InvalidateDocumentCache::class,
            Collection\Plugin\DeleteSubTree::class,
        ];
    }

    /**
     * The collections external fields, i.e. those not mapped directly to the
     * Opus\Db\Collections table gateway.
     *
     * @see \Opus\Model\Abstract::$_externalFields
     *
     * @var array
     */
    protected $externalFields = [
        'Theme'                => [
            'fetch' => 'lazy',
        ],
        'Role'                 => [
            'model' => CollectionRole::class,
            'fetch' => 'lazy',
        ],
        'RoleName'             => [
            'fetch' => 'lazy',
        ],
        'RoleDisplayFrontdoor' => [
            'fetch' => 'lazy',
        ],
        'RoleVisibleFrontdoor' => [
            'fetch' => 'lazy',
        ],
        'PositionKey'          => [],
        'PositionId'           => [],

        // Will contain the Collections to the Root Collection
        'Parents' => [
            'model' => self::class,
            'fetch' => 'lazy',
        ],

        // Will contain the Collections with parentId = this->getId
        'Children' => [
            'model' => self::class,
            'fetch' => 'lazy',
        ],

        // Pending nodes.
        'PendingNodes' => [
            'model' => self::class,
            'fetch' => 'lazy',
        ],
    ];

    /**
     * Sets up field by analyzing collection content table metadata.
     */
    protected function init()
    {
        $fields = [
            'Number',
            'Name',
            'OaiSubset',
            'RoleId',
            'Role',
            'RoleName',
            'RoleDisplayFrontdoor',
            'RoleVisibleFrontdoor',
            'DisplayFrontdoor',
            'VisiblePublish',
        ];

        foreach ($fields as $field) {
            $field = new Field($field);
            $this->addField($field);
        }

        $visible = new Field('Visible');
        $visible->setCheckbox(true);
        $this->addField($visible);

        // Add a field to hold collection specific theme.
        $theme = new Field('Theme');
        $theme->setSelection(true);
        $this->addField($theme);

        /**
         * External fields.
         */

        $children = new Field('Children');
        $children->setMultiplicity('*');
        $this->addField($children);

        // Contains the path back to the root node.
        $parents = new Field('Parents');
        $parents->setMultiplicity('*');
        $this->addField($parents);

        /*
         * Fields used to define the position of new nodes.
        */
        $positionKeys = [
            'Root',
            'FirstChild',
            'LastChild',
            'NextSibling',
            'PrevSibling',
        ];

        $positionKey = new Field('PositionKey');
        $positionKey->setDefault($positionKeys);
        $this->addField($positionKey);

        $positionId = new Field('PositionId');
        $this->addField($positionId);

        $pendingNodes = new Field('PendingNodes');
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
    protected function _fetchTheme()
    {
        if ($this->getId() === null) {
            return;
        }

        $table = TableGateway::getInstance(Db\CollectionsEnrichments::class);
        $theme = Config::get()->theme; // TODO Weitere Abhängigkeit auf Applikation, oder?

        // Search for theme in database and, if exists, overwrite default theme.
        $select = $table->select()
                        ->where('key_name = ?', "theme")
                        ->where('collection_id = ?', $this->getId());
        $row    = $table->fetchRow($select);

        if ($row !== null) {
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
    protected function _storeTheme($theme = '')
    {
        if ($this->getId() === null) {
            return;
        }

        if ($theme === null) {
            $theme = '';
        }

        $table  = TableGateway::getInstance(Db\CollectionsEnrichments::class);
        $select = $table->select()
                        ->where('key_name = ?', "theme")
                        ->where('collection_id = ?', $this->getId());
        $row    = $table->fetchRow($select);

        if ($theme === '' || Config::get()->theme === $theme) {
            // No need to store default theme setting.  Delete row if exists.
            if (isset($row)) {
                $row->delete();
            }
            return;
        }

        if ($row === null) {
            $row                = $table->createRow();
            $row->collection_id = $this->getId();
            $row->key_name      = 'theme';
        }

        $row->value = $theme;
        $row->save();
    }

    /**
     * Method to fetch documents-ids assigned to this collection.
     *
     * @return array DocumentId(s).
     */
    public function getDocumentIds()
    {
        if ($this->getId() === null) {
            return;
        }

        assert($this->getId() !== null);
        assert($this->getRoleId() !== null);

        $table = TableGateway::getInstance(Db\LinkDocumentsCollections::class);

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
    public function getPublishedDocumentIds()
    {
        if ($this->getId() === null) {
            return;
        }

        $table = TableGateway::getInstance(Db\LinkDocumentsCollections::class);

        // FIXME: Don't use internal knowledge of foreign models/tables.
        $select = $table->select()
                        ->from('link_documents_collections AS ldc', 'document_id')
                        ->from('documents AS d', [])
                        ->where('ldc.document_id = d.id')
                        ->where('ldc.collection_id = ?', $this->getId())
                        ->where("d.server_state = 'published'")
                        ->distinct();

        return $table->getAdapter()->fetchCol($select);
    }

    /**
     * Internal method to populate external field.
     */
    protected function _fetchRole()
    {
        return new CollectionRole($this->getRoleId());
    }

    protected function _fetchDisplayFrontdoor()
    {
        $displayName = $this->getDisplayName('frontdoor');
        $parentId    = $this->getParentNodeId();
        if (! empty($parentId)) {
            $parent            = new Collection($parentId);
            $parentDisplayName = $parent->getDisplayFrontdoor(); // implicitly calls $parent->_fetchDisplayFrontdoor()
            if (! empty($parentDisplayName)) {
                $displayName = $parentDisplayName . ' / ' . $displayName;
            }
        }
        return $displayName;
    }

    /**
     * empty method to prevent storing of read-only field DisplayFrontdoor
     */
    protected function _storeDisplayFrontdoor()
    {
    }

    /**
     * Internal method to store external field to model.
     */
    protected function _storeRole($role)
    {
    }

    /**
     * empty method to prevent storing of read-only field RoleDisplayFrontdoor
     */
    protected function _storeRoleDisplayFrontdoor($flag)
    {
    }

    /**
     * empty method to prevent storing of read-only field RoleVisibleFrontdoor
     */
    protected function _storeRoleVisibleFrontdoor($flag)
    {
    }

    /**
     * empty method to prevent storing of read-only field RoleName
     */
    protected function _storeRoleName($roleName)
    {
    }

    /**
     * Fetches contents of role-field "DisplayFrontdoor".
     *
     * @return string
     */
    protected function _fetchRoleDisplayFrontdoor()
    {
        $role = $this->getRole();
        if ($role !== null) {
            return $role->getDisplayFrontdoor();
        }
    }

    /**
     * Fetches contents of role-field "VisibleFrontdoor".
     *
     * @return string
     */
    protected function _fetchRoleVisibleFrontdoor()
    {
        $role = $this->getRole();
        if ($role !== null) {
            if (( int )$role->getVisible() === 1 && ( int )$role->getVisibleFrontdoor() === 1) {
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
    protected function _fetchRoleName()
    {
        $role = $this->getRole();
        if ($role !== null) {
            return $role->getDisplayName();
        }
    }

    /**
     * Returns custom string representation depending on role settings.
     *
     * @return string
     */
    public function getDisplayName($context = 'browsing', $role = null)
    {
        if ($role !== null && (! $role instanceof CollectionRole || $role->getId() !== $this->getRoleId())) {
            throw new InvalidArgumentException('given Collection Role is not compatible');
        }

        if ($role === null) {
            $role = $this->getRole();
        }
        $fieldnames = $role->_getField('Display' . ucfirst($context))->getValue();
        $display    = '';

        if (false === empty($fieldnames)) {
            foreach (explode(',', $fieldnames) as $fieldname) {
                $field = $this->_getField(trim($fieldname));
                if ($field !== null) {
                    $display .= $field->getValue() . ' ';
                }
            }
        } else {
            $display = $this->getName();
        }

        /* TODO use role name for root collection?
        if ((strlen(trim($display)) === 0) && $this->isRoot()) {
            $display = $role->getDisplayName();
        }*/

        return trim($display);
    }

    public function getDisplayNameForBrowsingContext($role = null)
    {
        return $this->getDisplayName('browsing', $role);
    }

    /**
     * Returns the complete string representation for the current collection (consists of
     * Number and Name).
     *
     * @param string $delimiter
     * @return string
     */
    public function getNumberAndName($delimiter = ' ')
    {
        $name   = trim($this->getName());
        $number = trim($this->getNumber());
        if ($number === '') {
            return $name;
        }
        if ($name === '') {
            return $number;
        }
        return $number . $delimiter . $name;
    }

    /**
     * Returns debug name.
     *
     * @return string
     */
    public function getDebugName()
    {
        return static::class . '#' . $this->getId() . '#' . $this->getRoleId();
    }

    /**
     * Returns the ID of the parent node.
     *
     * @return int
     */
    public function getParentNodeId()
    {
        $table         = $this->primaryTableRow->getTable();
        $parentIdField = $table->getParentFieldName();
        return $this->primaryTableRow->$parentIdField;
    }

    // TODO: Add documentation for method.
    protected function linkDocument($documentId)
    {
        if (isset($documentId) === false) {
            throw new Exception("linkDocument() needs documend_id parameter.");
        }

        if ($this->getId() === null) {
            throw new Exception("linkDocument() only on stored records.");
        }

        if (! $this->holdsDocumentById($documentId)) {
            $this->linkDocumentById($documentId);
        }
    }

    /**
     * Add document to current collection by adding an entry in the relation
     * table "link_documents_collections".
     *
     * @param null|int $documentId
     *
     * TODO: Move method to Opus\Db\LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public function linkDocumentById($documentId = null)
    {
        if ($this->getId() === null) {
            throw new Exception("linkDocumentById() is not allowed on NewRecord.");
        }

        if ($documentId === null) {
            throw new Exception("linkDocumentById() needs valid document_id.");
        }

        $table = $this->primaryTableRow->getTable();
        $db    = $table->getAdapter();

        $insertData = [
            'collection_id' => $this->getId(),
            'role_id'       => $this->getRoleId(),
            'document_id'   => $documentId,
        ];

        return $db->insert('link_documents_collections', $insertData);
    }

    /**
     * Removes document from current collection by deleting from the relation
     * table "link_documents_collections".
     *
     * @param null|int $documentId
     *
     * TODO: Move method to Opus\Db\LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public static function unlinkCollectionsByDocumentId($documentId = null)
    {
        if ($documentId === null) {
            return;
        }

        $table = TableGateway::getInstance(Db\LinkDocumentsCollections::class);
        $db    = $table->getAdapter();

        $condition = [
            'document_id = ?' => $documentId,
        ];

        return $db->delete("link_documents_collections", $condition);
    }

    /**
     * Checks if document is linked to current collection.
     *
     * @param  null|int $documentId
     * @return bool
     *
     * TODO: Move method to Opus\Db\LinkDocumentsCollections.
     * TODO: Usable return value.
     */
    public function holdsDocumentById($documentId = null)
    {
        if ($documentId === null) {
            return false;
        }

        $table = $this->primaryTableRow->getTable();
        $db    = $table->getAdapter();

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
    public function toArray($call = null)
    {
        $role = $this->getRole();
        return [
            'Id'                   => $this->getId(),
            'RoleId'               => $this->getRoleId(),
            'RoleName'             => $role->getDisplayName(),
            'Name'                 => $this->getName(),
            'Number'               => $this->getNumber(),
            'OaiSubset'            => $this->getOaiSubset(),
            'RoleDisplayFrontdoor' => $role->getDisplayFrontdoor(),
            'RoleDisplayBrowsing'  => $role->getDisplayBrowsing(),
            'DisplayFrontdoor'     => $this->getDisplayName('Frontdoor'),
            'DisplayBrowsing'      => $this->getDisplayName('Browsing'),
        ];
    }

    /**
     * Returns Xml representation of the collection.
     *
     * @param  null|array    $excludeFields Fields to exclude from the Xml output.
     * @param null|StrategyInterface $strategy Version of Xml to process
     * @return DOMDocument Xml representation of the collection.
     */
    public function toXml(?array $excludeFields = null, $strategy = null)
    {
        // TODO: comment why these fields should always be excluded.
        $alwaysExclude = ['Theme'];
        if ($excludeFields === null) {
            $excludeFields = $alwaysExclude;
        } else {
            $excludeFields = array_merge($excludeFields, $alwaysExclude);
        }
        return parent::toXml($excludeFields, $strategy);
    }

    /**
     * Returns all collection for given (role_id, collection number) as array
     * with Opus\Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int    $roleId
     * @param  string $number
     * @return array   Array of Opus\Collection objects.
     */
    public static function fetchCollectionsByRoleNumber($roleId, $number)
    {
        if (! isset($number)) {
            throw new Exception("Parameter 'number' is required.");
        }

        if (! isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $roleId)
                        ->where('number = ?', "$number");
        $rows   = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection for given (role_id, collection name) as array
     * with Opus\Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int    $roleId
     * @param  string $name
     * @return array   Array of Opus\Collection objects.
     */
    public static function fetchCollectionsByRoleName($roleId, $name)
    {
        if (! isset($name)) {
            throw new Exception("Parameter 'name' is required.");
        }

        if (! isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $roleId)
                        ->where('name = ?', $name);
        $rows   = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection for given (role_id) as array
     * with Opus\Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int $roleId
     * @return array   Array of Opus\Collection objects.
     */
    public static function fetchCollectionsByRoleId($roleId)
    {
        if (! isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->where('role_id = ?', $roleId);
        $rows   = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns all collection_ids for a given document_id.
     *
     * @param  int $documentId
     * @return array  Array of collection Ids.
     *
     * FIXME: This method belongs to Opus\Db\Link\Documents\Collections
     */
    public static function fetchCollectionIdsByDocumentId($documentId)
    {
        if (! isset($documentId)) {
            return [];
        }

        // FIXME: self::$tableGatewayClass not possible in static methods.
        $table = TableGateway::getInstance(Db\Collections::class);

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $select = $table->getAdapter()->select()
                        ->from("link_documents_collections AS ldc", "collection_id")
                        ->where('ldc.document_id = ?', $documentId)
                        ->distinct();

        return $table->getAdapter()->fetchCol($select);
    }

    /**
     * Mass-constructur.
     *
     * @param array $array Array of whatever new Collection(...) takes.
     * @return array|Collection Array of constructed Opus\Collections.
     *
     * TODO: Refactor this method as fetchAllFromSubselect(...) in AbstractDb?
     * TODO: Code duplication from/in Opus\CollectionRole!
     */
    public static function createObjects($array)
    {
        $results = [];

        // FIXME: get_called_class() only supported in PHP5 >= 5.3
        //   $class   = get_called_class();
        //   echo "class: $class\n";

        foreach ($array as $element) {
            $c         = new Collection($element);
            $results[] = $c;
        }

        return $results;
    }

    /**
     * If this node is new, PositionKey and PositionId define the position
     * in the tree.  Do *not* store these values to any external model.
     */

    public function _fetchPositionKey()
    {
    }

    public function _storePositionKey()
    {
    }

    public function _fetchPositionId()
    {
    }

    public function _storePositionId()
    {
    }

    /**
     * Creating new collections.
     */

    public function addFirstChild($node = null)
    {
        return $this->addPendingNodes('FirstChild', $node);
    }

    public function addLastChild($node = null)
    {
        return $this->addPendingNodes('LastChild', $node);
    }

    public function addNextSibling($node = null)
    {
        return $this->addPendingNodes('NextSibling', $node);
    }

    public function addPrevSibling($node = null)
    {
        return $this->addPendingNodes('PrevSibling', $node);
    }

    public function moveAfterNextSibling()
    {
        $nestedsets = $this->primaryTableRow->getTable();
        $nestedsets->moveSubTreeAfterNextSibling($this->getId());
    }

    public function moveBeforePrevSibling()
    {
        $nestedsets = $this->primaryTableRow->getTable();
        $nestedsets->moveSubTreeBeforePreviousSibling($this->getId());
    }

    public function moveToPosition($position)
    {
        $nestedSets = $this->primaryTableRow->getTable();
        $nestedSets->moveSubTreeToPosition($this->getId(), $position);
    }

    public function moveToStart()
    {
        $nestedSets = $this->primaryTableRow->getTable();
        $nestedSets->moveSubTreeToPosition($this->getId(), 0);
    }

    public function moveToEnd()
    {
        $nestedSets = $this->primaryTableRow->getTable();
        $nestedSets->moveSubTreeToPosition($this->getId());
    }

    /**
     * _storeInternalFields(): Manipulate _primaryTableRow to preserve the
     * nested set property.
     *
     * @return int The primary id of the created row.
     */
    public function _storeInternalFields()
    {
        if ($this->getRoleId() === null) {
            throw new Exception("RoleId must be set when storing Collection!");
        }

        if ($this->isNewRecord()) {
            $nestedSets = $this->primaryTableRow->getTable();

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
            $this->primaryTableRow->setFromArray($data);
        }

        return parent::_storeInternalFields();
    }

    /**
     * PendingNodes: Add new nodes to the tree.  The position depends on the
     * $key parameter.
     *
     * @param null|string         $key (First|Last)Child, (Next|Prev)Sibling.
     * @param null|CollectionNode $collection
     * @return <type>
     */
    protected function addPendingNodes($key = null, $collection = null)
    {
        if (isset($collection)) {
            $collection = parent::addPendingNodes($collection);
        } else {
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
    public function _fetchPendingNodes()
    {
    }

    /**
     * Storing pending nodes makes sure, that every node knowns which role_id
     * it belongs to and next to which node it will be inserted.
     */
    public function _storePendingNodes($collections)
    {
        if ($collections === null) {
            return;
        }

        if (false === is_array($collections)) {
            throw new Exception("Expecting array-value argument!");
        }

        foreach ($collections as $collection) {
            if ($collection->isNewRecord()) {
                $collection->setRoleId($this->getRoleId());
                $collection->setPositionId($this->getId());
            }
            $collection->store();
        }
    }

    /**
     * Returns number of published documents of complete subtree.
     *
     * @return int Number of subtree Entries.
     *
     * TODO modify subselect to exclude invisible nodes
     */
    public function getNumSubtreeEntries()
    {
        $nestedsets = $this->primaryTableRow->getTable();
        $subselect  = $nestedsets
                ->selectSubtreeById($this->getId(), 'id')
                ->where("start.visible = 1")
                ->where("node.visible = 1")
                ->distinct();

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db     = $this->primaryTableRow->getTable()->getAdapter();
        $select = $db->select()
                        ->from('link_documents_collections AS ldc', 'count(distinct ldc.document_id)')
                        ->from('documents AS d', [])
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
    public function filterSubtreeDocumentIds($docIds)
    {
        if ($docIds === null || (is_array($docIds) && empty($docIds))) {
            return [];
        }

        $nestedsets = $this->primaryTableRow->getTable();
        $subselect  = $nestedsets
                ->selectSubtreeById($this->getId(), 'id')
                ->distinct();

        // TODO: Kapselung verletzt: Benutzt Informationen über anderes Model.
        $db     = $this->primaryTableRow->getTable()->getAdapter();
        $select = $db->select()->from('link_documents_collections AS ldc', 'ldc.document_id')
                        ->where("ldc.collection_id IN ($subselect)")
                        ->where("ldc.document_id IN (?)", $docIds)
                        ->distinct();

        return $db->fetchCol($select);
    }

    /**
     * Returns nodes for breadcrumb path.
     *
     * @return array of Opus\Collection objects.
     */

    public function _fetchParents()
    {
        if ($this->getId() === null) {
            return;
        }

        $table = $this->primaryTableRow->getTable();

        $select = $table->selectParentsById($this->getId());
        $rows   = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Returns children of current collection.
     *
     * @return array of Opus\Collection objects.
     */
    protected function _fetchChildren()
    {
        if ($this->getId() === null) {
            return;
        }

        $table = $this->primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());
        $rows   = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    /**
     * Overwrite _store: We cannot add children directly.  This has to be done
     * via "addLastChild" and "addFirstChild".
     */
    protected function _storeChildren()
    {
    }

    /**
     * An unexpensive way to find out, if the current collection has children,
     * i.e. if it is a leaf node in the tree.
     */
    public function hasChildren()
    {
        if ($this->isNewRecord()) {
            return; // TODO true or false?
        }

        return ! $this->primaryTableRow->getTable()->isLeaf(
            $this->primaryTableRow->toArray()
        );
    }

    /**
     * Overwrite describe: Do not export external fields to XML.
     *
     * @return array
     */
    public function describe()
    {
        $excludeFields = ['Children', 'Role', 'PendingNodes', 'Parents'];
        return array_diff(parent::describe(), $excludeFields);
    }

    /**
     * isRoot()
     */
    public function isRoot()
    {
        if ($this->isNewRecord()) {
            return;
        }

        return $this->primaryTableRow->getTable()->isRoot(
            $this->primaryTableRow->toArray()
        );
    }

    public function getVisibleChildren()
    {
        if ($this->getId() === null) {
            return;
        }

        $table = $this->primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());
        $select->where("visible = 1");
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    public function hasVisibleChildren()
    {
        if ($this->getId() === null) {
            return;
        }

        $table  = $this->primaryTableRow->getTable();
        $select = $table->selectChildrenById($this->getId());
        $select->where("visible = 1");
        $select->reset('columns');
        $select->distinct(true)->columns("count(id)");

        return intval($table->getAdapter()->fetchOne($select)) > 0;
    }

    public function getVisiblePublishChildren()
    {
        if ($this->getId() === null) {
            return;
        }

        $table = $this->primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());
        $select->where("visible_publish = 1");
        $select->where("visible = 1");
        $rows = $table->fetchAll($select);

        return self::createObjects($rows);
    }

    public function hasVisiblePublishChildren()
    {
        if ($this->getId() === null) {
            return;
        }

        $table  = $this->primaryTableRow->getTable();
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
    public function sortChildrenByName($reverse = false)
    {
        $table = $this->primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());

        $children = $table->getAdapter()->fetchAll($select);

        if ($reverse === false) {
            usort(
                $children,
                function ($nodeOne, $nodeTwo) {
                    if ($nodeOne['name'] === $nodeTwo['name']) {
                        return 0;
                    }
                    return $nodeOne['name'] < $nodeTwo['name'] ? -1 : 1;
                }
            );
        } else {
            usort(
                $children,
                function ($nodeOne, $nodeTwo) {
                    if ($nodeOne['name'] === $nodeTwo['name']) {
                        return 0;
                    }
                    return $nodeOne['name'] > $nodeTwo['name'] ? -1 : 1;
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
    public function sortChildrenByNumber($reverse = false)
    {
        $table = $this->primaryTableRow->getTable();

        $select = $table->selectChildrenById($this->getId());

        $children = $table->getAdapter()->fetchAll($select);

        if ($reverse === false) {
            usort(
                $children,
                function ($nodeOne, $nodeTwo) {
                    if ($nodeOne['number'] === $nodeTwo['number']) {
                        return 0;
                    }
                    return $nodeOne['number'] < $nodeTwo['number'] ? -1 : 1;
                }
            );
        } else {
            usort(
                $children,
                function ($nodeOne, $nodeTwo) {
                    if ($nodeOne['number'] === $nodeTwo['number']) {
                        return 0;
                    }
                    return $nodeOne['number'] > $nodeTwo['number'] ? -1 : 1;
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
     * @param array $sortedIds Array with node IDs in desired order
     * @throws InvalidArgumentException if one of the IDs ist not a child node
     */
    public function applySortOrderOfChildren($sortedIds)
    {
        $table = $this->primaryTableRow->getTable();
        $table->applySortOrderOfChildren($this->getId(), $sortedIds);
    }

    /**
     * Checks if collection is visible based on settings including parents.
     */
    public function isVisible()
    {
        $colId = $this->getId();

        // return value for collection that has not been stored yet
        if ($colId === null) {
            $visible = $this->getVisible();
            return $visible === null ? false : (bool) $visible;
        }

        $table = $this->primaryTableRow->getTable();

        return $table->isVisible($colId);
    }

    /**
     * Creates collection object from data in array.
     *
     * If array contains 'Id' the corresponding existing collection is used.
     *
     * TODO If 'Id' is from a different system the wrong collection might be used.
     *      How can we deal with the possible problems? Does it make more sense to
     *      handle reuse of existing objects outside the fromArray function?
     *      Would it make more sense if we generate new objects and then apply
     *      another function that maps the attributes of a document to existing
     *      objects in the database. It seems this really depends on the type of
     *      object in question.
     *
     * TODO Collections should probably never be created as part of an import. When
     *      a document is stored the connected collections should already exist. If
     *      not the storing operation should fail OR generate a message.
     *
     * @return mixed|void
     */
    public static function fromArray($data)
    {
        $col = null;

        if (isset($data['Id'])) {
            try {
                $col = new Collection($data['Id']);

                // TODO update from array not supported (handling of roleId)
                // $col->updateFromArray($data);
            } catch (NotFoundException $omnfe) {
                // TODO handle it
            }
        }

        if ($col === null) {
            $col = parent::fromArray($data);
        }

        return $col;
    }

    /**
     * @param string $term Search term for matching collections
     * @param int|array $roles CollectionRole IDs
     * @return array
     */
    public function find($term, $roles = null)
    {
        $table = TableGateway::getInstance(Db\Collections::class);

        $database = $table->getAdapter();

        $quotedTerm = $database->quote("%$term%");

        $select = $table->select()
            ->from("collections", ['Id' => 'id', 'RoleId' => 'role_id', 'Name' => 'name', 'Number' => 'number'])
            ->where("name LIKE $quotedTerm OR number LIKE $quotedTerm")
            ->distinct()
            ->order(['role_id', 'number', 'name']);

        if ($roles !== null) {
            if (! is_array($roles)) {
                $select->where('role_id = ?', $roles );
            } else {
                $select->where( 'role_id IN (?)', $roles);
            }
        }

        return $database->fetchAll($select);
    }
}
