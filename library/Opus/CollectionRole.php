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
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for collection roles in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_CollectionRole extends Opus_Model_AbstractDb {

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_CollectionsRoles';

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Documents table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
            'CollectionsContentSchema' => array(),
            'SubCollection' => array(
                'fetch' => 'lazy',
                'model' => 'Opus_Collection'
            ),
        );

    /**
     * Track from where on subCollections have to be stored. Blindly calling
     * store on all subCollections leads to performance issues.
     *
     * @var mixed  Defaults to null.
     */
    private $__updateBelow = null;

    /**
     * Initialize model with the following fields:
     * - Name
     * - Position
     * - LinkDocsPathToRoot
     * - Visible
     * - Collections
     *
     * @return void
     */
    protected function _init() {
        //$allRoles = self::getAll();
        //$countRoles = count($allRoles);

        $name = new Opus_Model_Field('Name');
        $oaiName = new Opus_Model_Field('OaiName');
        $position = new Opus_Model_Field('Position');
        //$position->setDefault(array_combine(range(1,$countRoles+1),range(1,$countRoles+1)))->setSelection(true);
        $links_docs_path_to_root = new Opus_Model_Field('LinkDocsPathToRoot');
        $links_docs_path_to_root->setDefault(array('none'=>'none', 'count'=>'count', 'display'=>'display', 'both'=>'both'))->setSelection(true);
        $visible = new Opus_Model_Field('Visible');
        $visible->setCheckbox(true);
        $visibleBrowsingStart = new Opus_Model_Field('VisibleBrowsingStart');
        $visibleBrowsingStart->setCheckbox(true);
        $subcollection = new Opus_Model_Field('SubCollection');
        $subcollection->setMultiplicity('*');
        $collectionsContentSchema = new Opus_Model_Field('CollectionsContentSchema');
        $collectionsContentSchema->setMultiplicity('*');
        $displayBrowsing = new Opus_Model_Field('DisplayBrowsing');
        $displayFrontdoor = new Opus_Model_Field('DisplayFrontdoor');
        $visibleFrontdoor = new Opus_Model_Field('VisibleFrontdoor');
        $visibleFrontdoor->setCheckbox(true);
        $displayOai = new Opus_Model_Field('DisplayOai');
        $visibleOai = new Opus_Model_Field('VisibleOai');
        $visibleOai->setCheckbox(true);

        $this->addField($name)
            ->addField($oaiName)
            ->addField($position)
            ->addField($links_docs_path_to_root)
            ->addField($visible)
            ->addField($visibleBrowsingStart)
            ->addField($subcollection)
            ->addField($collectionsContentSchema)
            ->addField($displayBrowsing)
            ->addField($visibleFrontdoor)
            ->addField($displayFrontdoor)
            ->addField($visibleOai)
            ->addField($displayOai);
        Opus_Collection_Information::cleanup();

  }

  /**
   * Returns associated collections.
   *
   * @return Opus_Collection|array Collection(s).
   */
    protected function _fetchSubCollection() {
        $collections = Opus_Collection_Information::getSubCollections((int) $this->getId(), 1, true, true);
        $collectionIds = array();
        foreach ($collections as $collection) {
            $collectionIds[] = $collection['collections_id'];
        }
        $result = array();
        if (empty($collectionIds) === false) {
            $result = array();
            $table = new Opus_Db_CollectionsContents($this->getId());
            // Unfortunaley, we cannot use find() here, since it destroys the order of the Ids.
            // TODO: Find a way to make the query more performant.
            // $rows = $table->find($collectionIds);
            $rows = array();
            foreach ($collectionIds as $id) {
                $rows[] = $table->fetchRow($table->select()->where('id = ?', $id));
            }
            foreach ($rows as $row) {
                $result[] = new Opus_Collection((int) $this->getId(), $row, $this);
            }
        }
        return $result;
    }

    /**
     * Overwrites standard store procedure to account for subcollections.
     *
     * @return void
     */
    protected function _storeSubCollection($subCollections) {
        if (false === $this->_getField('SubCollection', true)->isModified()) {
            return;
        }
        $updatedSubCollections = array();
        // Store subcollections as they were before the update.
        $collections = Opus_Collection_Information::getSubCollections((int) $this->getId(), 1, true);
        $previousCollections = array();
        foreach ($collections as $collection) {
            $previousCollections[] = $collection['collections_id'];
        }
        foreach ($subCollections as $index => $subCollection) {
            $subCollection->store();
            $id = (int) $subCollection->getId();
            $updatedSubCollections[] = $id;
            if ($index < $this->__updateBelow) {
                continue;
            }
            if ($index === 0) {
                $leftSibling = 0;
            } else {
                $leftSibling = (int) $subCollections[$index - 1]->getId();
            }
            Opus_Collection_Information::newCollectionPosition((int) $this->getId(), $id, 1, $leftSibling);
        }
        // Remove subcollections that are not supposed to be there any more.
        $removeCollections = array_diff($previousCollections, $updatedSubCollections);
        foreach ($removeCollections as $removeCollection) {
            Opus_Collection_Information::deleteCollectionPosition((int) $this->getId(), $removeCollection, 1);
        }
        $this->getField('SubCollection', true)->clearModified();
    }

    /**
     * Extend standard adder function to track changes in multivalue field.
     *
     * @return void
     */
    public function addSubCollection($subCollection) {
        // FIXME: Workaround for parent::addSubCollection($subCollection)
        parent::__call('addSubCollection', array($subCollection));
        $this->__updateBelow = count($this->_getField('SubCollection')->getValue()) - 1;
    }

    /**
     * Insert a subcollection at a specific position.
     *
     * @param  integer          $position      Where to insert the subcollection.
     * @param  Opus_Collection  $subCollection The subcollection to insert.
     *
     * @return void
     */
    public function insertSubCollectionAt($position, $subCollection) {
        $subCollections = $this->_getField('SubCollection')->getValue();
        if ($position > count($subCollections)) {
            $this->addSubCollection($subCollection);
        } else {
            array_splice($subCollections, $position, 0, array($subCollection));
            $this->setSubCollection($subCollections);
            $this->__updateBelow = $position;
        }
    }

    /**
     * Content schema information is only relevant internally and needs not get stored.
     *
     * @return void
     */
    protected function _fetchCollectionsContentSchema() {
        return $this->_fields['CollectionsContentSchema']->getValue();
    }

    /**
     * Creates the collection's database tables.
     *
     * @return void
     */
    protected function _storeCollectionsContentSchema() {
        if ($this->_isNewRecord === false) {
            return;
        }
        $schema = array();
        $tablecolumns = $this->_fields['CollectionsContentSchema']->getValue();
        $tablecolumns[] = 'Name';
        $tablecolumns = array_unique($tablecolumns);
        foreach ($tablecolumns as $tablecolumn) {
            $schema[] = array('name' => $tablecolumn, 'type' => 'VARCHAR', 'length' => 255);
        }
        $role = new Opus_Collection_Roles();
        $role->createDatabaseTables($schema, $this->getId());

        // Write pseudo content for the hidden root node to fullfill foreign key constraint
        $occ = new Opus_Collection_Contents((int) $this->getId());
        $occ->root();

        // Write hidden root node to nested sets structure
        $ocs = new Opus_Collection_Structure((int) $this->getId());
        $ocs->create();
        $ocs->save();
    }

    /**
     * Overwrite standard storage procedure to shift positions.
     *
     * @return void
     */
    protected function _storePosition($to) {
        if ($to < 1) {
            return;
        }
        $table = $this->_primaryTableRow->getTable();
        $roles = $table->fetchAll();
        $from = $this->_primaryTableRow->position;

        if (true === empty($from)) {
            $from = PHP_INT_MAX ;
            if ($to > (count($roles)+1)) {
                $to = count($roles);
            }
        } else if ($to > (count($roles))) {
            $to = count($roles);
        }

        if ($from < $to) {
            foreach ($roles as $role) {
                if ($role->position <= $to and $role->position > $from) {
                    $role->position--;
                    $role->save();
                }
            }
        } else if ($to < $from) {
            foreach ($roles as $role) {
                if ($role->position < $from and $role->position >= $to) {
                    $role->position++;
                    $role->save();
                }
            }
        }
        $this->_primaryTableRow->position = $to;
    }

    /**
     * Returns long name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getName();
    }

    /**
     * Retrieve all Opus_CollectionRole instances from the database.
     *
     * @return array Array of Opus_CollectionRole objects.
     *
     * TODO: Parametrize query to account for hidden collection roles.
     */
    public static function getAll() {
        $roles = self::getAllFrom('Opus_CollectionRole', 'Opus_Db_CollectionsRoles', null, 'position');
        // Exclude role with id 1, this is reserved for organisational units.
        // FIXME: Move exclusion to database query.
        $result = array();
        foreach ($roles as $role) {
            if ($role->getId() != 1) {
                $result[] = $role;
            }
        }
        return $result;

    }

    /**
     * Overwrites standard toArray() to prevent infinite recursion due to parent collections.
     *
     * @return array A (nested) array representation of the model.
     */
    public function toArray() {
        $result = array();
        foreach ($this->_getField('SubCollection')->getValue() as $subCollection) {
            $result[] = array(
                    'Id' => $subCollection->getId(),
                    'Name' => $subCollection->getName(),
                    'Parent' => $this->getId(),
                    'SubCollection' => $subCollection->toArray(),
                );
        }
        return $result;
    }

    /**
     * Returns Xml representation of the collection role.
     *
     * @param  array $excludeFields Fields to exclude from the Xml output.
     * @return DomDocument Xml representation of the collection role.
     */
    public function toXml(array $excludeFields = null) {
        return parent::toXml(array('ParentCollection'));
    }

    /**
     * Extend standard deletion to delete collection roles tables.
     *
     * @return void
     */
    public function delete() {
        $dbadapter = Zend_Db_Table::getDefaultAdapter();
        $id = $this->getId();
        $collectionsContentsTable = $dbadapter->quoteIdentifier("collections_contents_$id");
        $collectionsStructureTable = $dbadapter->quoteIdentifier("collections_structure_$id");
        $collectionsReplacementTable = $dbadapter->quoteIdentifier("collections_replacement_$id");
        $collectionsLinkTable = $dbadapter->quoteIdentifier("link_documents_collections_$id");
        $dbadapter->query("DROP TABLE $collectionsStructureTable");
        $dbadapter->query("DROP TABLE $collectionsReplacementTable");
        $dbadapter->query("DROP TABLE $collectionsLinkTable");
        $dbadapter->query("DROP TABLE $collectionsContentsTable");
        parent::delete();
    }

    /**
     * TODO: Implement theme handling for collection roles.
     *
     * @return string
     */
    public function getTheme() {
        // FIXME: As hot-fix, we take the theme which is assigned to the root collection,
        // FIXME: i.e. the collection we get by omitting the collection_id parameter.
        // FIXME: Please check and remove TODO-tag if fixed properly.
        $collection = new Opus_Collection( $this->getId() );
        return $collection->getTheme();
    }

    /**
     * Returns all valid oai set names (i.e. for those collections
     * that contain at least one document).
     *
     * @return array An array of strings containing oai set names.
     */
    public function getOaiSetNames() {
        $db = Zend_Db_Table::getDefaultAdapter();
        $id = $this->getId();
        $oaiSetNames = array();
        $oaiPrefix = $this->getOaiName();
        $oaiPostfixColumn = $this->getDisplayOai();
        $collectionsContentsTable = $db->quoteIdentifier("collections_contents_$id");
        $collectionsLinkTable = $db->quoteIdentifier("link_documents_collections_$id");
        $result = $db->fetchCol("SELECT DISTINCT CONCAT('$oaiPrefix:', c.$oaiPostfixColumn)
                                 FROM $collectionsContentsTable AS c
                                 JOIN $collectionsLinkTable AS l
                                 ON (c.id = l.collections_id)
                                 ");
        return $result;
    }

    /**
     * Return the ids of documents in an oai set.
     *
     * @param  string $oaiSetName The name of the oai set.
     * @return array The ids of the documents in the set.
     */
    public static function getDocumentIdsInSet($oaiSetName) {
        $oaiPrefix = substr($oaiSetName, 0, strrpos($oaiSetName, ':'));
        $oaiPostfix = substr($oaiSetName, strrpos($oaiSetName, ':') + 1);
        $db = Zend_Db_Table::getDefaultAdapter();
        $role = $db->fetchRow('SELECT id, display_oai FROM collections_roles WHERE oai_name = ?', $oaiPrefix);
        if (true === is_null($role)) {
            return null;
        }
        $roleId = $role['id'];
        $oaiPostfixColumn = $role['display_oai'];
        $collectionsContentsTable = $db->quoteIdentifier("collections_contents_$roleId");
        $collectionsLinkTable = $db->quoteIdentifier("link_documents_collections_$roleId");
        $result = $db->fetchCol("SELECT DISTINCT documents_id
                                 FROM $collectionsLinkTable
                                 WHERE collections_id IN (
                                     SELECT id FROM $collectionsContentsTable WHERE $oaiPostfixColumn = $oaiPostfix
                                 )");
        return $result;
    }
}
