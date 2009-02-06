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
 * @package     Opus_Model
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Bridges Opus_Collection_Information to Opus_Model_Abstract.
 *
 */
class Opus_Model_Collection extends Opus_Model_Abstract
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_CollectionsContents';

    /**
     * Holds internal representation of the collection.
     *
     * @var mixed
     */
    private $__collection_id = null;

    /**
     * Holds the role of the collection.
     *
     * @var int
     */
    private $__role_id = null;

    /**
     * Fetches existing or creates new collection.
     *
     * @param  int|string  $role           The role that this collection is in.
     * @param  int         $collection_id  (Optional) Id of an existing collection.
     * @param  int         $parent         (Optional) parent Id of a new collection.
     * @param  int         $left_sibling   (Optional) left sibling Id of a new collection.
     */
    public function __construct($role, $collection_id = null, $parent = null, $left_sibling = null) {
        // If a role name is passed, resolve to corresponding role id.
        if (is_string($role) === true) {
            $rolesTable = new Opus_Db_CollectionsRoles();
            $select = $rolesTable->select()
                ->from($rolesTable, array('collections_roles_id'))
                ->where('name = ?', $role);
            $row = $rolesTable->fetchRow($select);
            $role_id = (int) $row->collections_roles_id;
        } else {
            $role_id = (int) $role;
        }

        if (is_null($collection_id) === true) {
            if (is_null($parent) === true or is_null($left_sibling) === true) {
                throw new Opus_Model_Exception('New collection requires parent and left sibling id to be passed.');
            } else {
                $id = Opus_Collection_Information::newCollection($role_id, $parent, $left_sibling, null);
                $collection = Opus_Collection_Information::getCollection($role_id, $id);
                $this->__collection_id = $collection['id'];
            }
        } else {
            $collection = Opus_Collection_Information::getCollection($role_id, $collection_id);
            $this->__collection_id = $collection['id'];
        }
        $this->__role_id = $role_id;
        parent::__construct($this->__collection_id, new Opus_Db_CollectionsContents($this->__role_id));
    }

    /**
     * Sets up field by analyzing collection content table metadata.
     *
     * @return void
     */
    protected function _init() {
        $table = new Opus_Db_CollectionsContents($this->__role_id);
        $info = $table->info();
        $dbFields = $info['metadata'];

        // Add all database column names except primary keys as Opus_Model_Fields
        foreach (array_keys($dbFields) as $dbField) {
            if (in_array($dbField, $info['primary'])) {
                continue;
            }
            // Convert snake_case to CamelCase for fieldnames
            $fieldname = '';
            foreach(explode('_', $dbField) as $part) {
                $fieldname .= ucfirst($part);
            }
            $field = new Opus_Model_Field($fieldname);
            $this->addField($field);
        }

        // Add a field to hold subcollections
        $subCollectionField = new Opus_Model_Field('SubCollection');
        $subCollectionField->setMultiplicity('*');
        $this->_externalFields['SubCollection'] = array('fetch' => 'lazy', 'model' => 'Opus_Model_Collection');
        $this->addField($subCollectionField);

        // Add all first level sibling Ids as fields,
        // collections will be instantiated later on
        // be lazy fetching.
        $subCollections = Opus_Collection_Information::getSubCollections($this->__role_id, (int) $this->__collection_id);
        foreach ($subCollections as $subCollection) {
            $subCollectionId = $subCollection['content'][0]['id'];
            $this->_fields['SubCollection']->addValue((int) $subCollectionId);
        }

        // Add a field to hold parentcollections
        $parentCollectionField = new Opus_Model_Field('ParentCollection');
        $parentCollectionField->setMultiplicity('*');
        $this->_externalFields['ParentCollection'] = array('fetch' => 'lazy', 'model' => 'Opus_Model_Collection');
        $this->addField($parentCollectionField);

        // Add all first level parent Ids as fields,
        // collections will be instantiated later on
        // be lazy fetching.
        $parentCollectionIds = Opus_Collection_Information::getAllParents($this->__role_id, (int) $this->__collection_id);
        foreach ($parentCollectionIds as $parentCollectionId) {
            $this->_fields['ParentCollection']->addValue((int) $parentCollectionId);
        }
    }

    /**
     * Fetches the entries in this collection.
     *
     * @return array $documents The documents in the collection.
     */
    public function getEntries() {
        $docIds = Opus_Collection_Information::getAllCollectionDocuments((int) $this->__role_id, (int) $this->__collection_id);
        $documents = array();
        foreach ($docIds as $docId) {
            $documents[] = new Opus_Model_Document($docId);
        }
        return $documents;
    }

    /**
     * Adds a document to this collection.
     *
     * @param  Opus_Model_Document  $document The document to add.
     * @return void
     */
    public function addEntry(Opus_Model_Abstract $model) {
        $linkTable = new Opus_Db_LinkDocumentsCollections((int) $this->__role_id);
        $link = $linkTable->createRow();
        $link->documents_id = $model->getId();
        $link->collections_id = $this->__collection_id;
        $link->save();
    }

    /**
     * Returns subcollections.
     *
     * @param  int  $index (Optional) Index of the subcollection to fetchl.
     * @return Opus_Model_Collection|array Subcollection(s).
     */
    protected function _fetchSubCollection($index = null) {
        if (is_null($index) === false) {
            $subCollectionId = $this->_fields['SubCollection']->getValue($index);
            return new Opus_Model_Collection($this->__role_id, $subCollectionId);
        } else {
            $subCollections = array();
            foreach ($this->_fields['SubCollection']->getValue() as $subCollectionId) {
                $subCollections[] = new Opus_Model_Collection($this->__role_id, $subCollectionId);
            }
            return $subCollections;
        }
    }

    /**
     * Overwrites store procedure.
     * TODO: Implement storing collection structures.
     *
     * @return void
     */
    protected function _storeSubCollection() {

    }

    /**
     * Returns parentcollections.
     *
     * @param  int  $index (Optional) Index of the parentcollection to fetchl.
     * @return Opus_Model_Collection|array Parentcollection(s).
     */
    protected function _fetchParentCollection($index = null) {
        if (is_null($index) === false) {
            $parentCollectionId = $this->_fields['ParentCollection']->getValue($index);
            // When the root is reached, return the role.
            if ($parentCollectionId === 0) {
                return new Opus_Model_CollectionRole($this->__role_id);
            } else {
                return new Opus_Model_Collection($this->__role_id, (int) $parentCollectionId);
            }
        } else {
            $parentCollections = array();
            foreach ($this->_fields['ParentCollection']->getValue() as $parentCollectionId) {
                // When the root is reached, return the role.
                if ($parentCollectionId === 0) {
                    $parentCollections[] = new Opus_Model_CollectionRole($this->__role_id);
                } else {
                    $parentCollections[] = new Opus_Model_Collection($this->__role_id, (int) $parentCollectionId);
                }
            }
            return $parentCollections;
        }
    }

    /**
     * Overwrites store procedure.
     * TODO: Implement storing collection structures.
     *
     * @return void
     */
    protected function _storeParentCollection() {

    }

}
