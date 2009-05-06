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
     * Fields that should not be displayed on a form.
     *
     * @var array  Defaults to array('SubCollection').
     */
    protected $_hiddenFields = array(
            'SubCollection',
        );

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
        $name = new Opus_Model_Field('Name');
        $position = new Opus_Model_Field('Position');
        $links_docs_path_to_root = new Opus_Model_Field('LinkDocsPathToRoot');
        $links_docs_path_to_root->setCheckbox(true);
        $visible = new Opus_Model_Field('Visible');
        $visible->setCheckbox(true);
        $subcollection = new Opus_Model_Field('SubCollection');
        $subcollection->setMultiplicity('*');
        $collectionsContentSchema = new Opus_Model_Field('CollectionsContentSchema');
        $collectionsContentSchema->setMultiplicity('*');


        $this->addField($name)
            ->addField($position)
            ->addField($links_docs_path_to_root)
            ->addField($visible)
            ->addField($subcollection)
            ->addField($collectionsContentSchema);
        Opus_Collection_Information::cleanup();
  }

  /**
   * Returns associated collections.
   *
   * @return Opus_Collection|array Collection(s).
   */
    protected function _fetchSubCollection() {
        $collections = Opus_Collection_Information::getSubCollections((int) $this->getId(), 1, true);
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
                $result[] = new Opus_Collection((int) $this->getId(), $row);
            }
        }
        return $result;
    }

    /**
     * Overwrites standard store procedure to account for subcollections.
     *
     * @return void
     */
    protected function _storeSubCollection() {
        $updatedSubCollections = array();
        // Store subcollections as they were before the update.
        $collections = Opus_Collection_Information::getSubCollections((int) $this->getId(), 1, true);
        $previousCollections = array();
        foreach ($collections as $collection) {
            $previousCollections[] = $collection['collections_id'];
        }
        foreach ($this->getSubCollection() as $index => $subCollection) {
            $subCollection->store();
            $id = (int) $subCollection->getId();
            $updatedSubCollections[] = $id;
            if ($index === 0) {
                $leftSibling = 0;
            } else {
                $leftSibling = (int) $this->getSubCollection($index - 1)->getId();
            }
            Opus_Collection_Information::deleteCollectionPosition((int) $this->getId(), $id, 1);
            Opus_Collection_Information::newCollectionPosition((int) $this->getId(), $id, 1, $leftSibling);
            // FIXME: Resolve calling store() twice issue.
            // It's due to the nature of deleteCollectionPosition, which
            // removes subcollections as well.
            $subCollection->store();
        }
        // Remove subcollections that are not supposed to be there any more.
        $removeCollections = array_diff($previousCollections, $updatedSubCollections);
        foreach ($removeCollections as $removeCollection) {
            Opus_Collection_Information::deleteCollectionPosition((int) $this->getId(), $removeCollection, 1);
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
        // FIXME: As soon as the document builder supports multiple
        // values for atomic field types, remove artificial array
        // construction.
        if (is_array($this->_fields['CollectionsContentSchema']->getValue()) === false) {
            $tablecolumns = array('name', $this->_fields['CollectionsContentSchema']->getValue());
        } else {
            $tablecolumns = $this->_fields['CollectionsContentSchema']->getValue();
        }
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
     */
    public static function getAll() {
        return self::getAllFrom('Opus_CollectionRole', 'Opus_Db_CollectionsRoles');
    }

    /**
     * Overwrites standard toArray() to prevent infinite recursion due to parent collections.
     *
     * @return array A (nested) array representation of the model.
     */
    public function toArray() {
        $result = array();
        foreach ($this->getSubCollection() as $subCollection) {
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
}
