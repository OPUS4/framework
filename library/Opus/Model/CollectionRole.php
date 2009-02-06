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
 * Domain model for collection roles in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Abstract
 */
class Opus_Model_CollectionRole extends Opus_Model_Abstract {

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
            'CollectionsContentSchema' => array()
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
        $visible = new Opus_Model_Field('Visible');
        $collections = new Opus_Model_Field('Collections');
        $collections->setMultiplicity('*');
        $collectionsContentSchema = new Opus_Model_Field('CollectionsContentSchema');
        $collectionsContentSchema->setMultiplicity('*');

        $this->_externalFields['Collections'] = array('fetch' => 'lazy', 'model' => 'Opus_Model_Collection');

        $this->addField($name)
            ->addField($position)
            ->addField($links_docs_path_to_root)
            ->addField($visible)
            ->addField($collections)
            ->addField($collectionsContentSchema);

      // If persistent, fetch associated collection ids.
        if (is_null($this->getId()) === false) {
            $collections = Opus_Collection_Information::getSubCollections((int) $this->getId());
            foreach ($collections as $collection) {
                $collectionId = $collection['content'][0]['id'];
                $this->_fields['Collections']->addValue((int) $collectionId);
            }
        }
  }

  /**
   * Returns associated collections.
   *
   * @param  int  $index (Optional) Index of the collection to fetch.
   * @return Opus_Model_Collection|array Collection(s).
   */
    protected function _fetchCollections($index = null) {
        if (is_null($index) === false) {
            $collectionId = $this->_fields['Collections']->getValue($index);
            return new Opus_Model_Collection($this->getId(), $collectionId);
        } else {
            $collections = array();
            foreach ($this->_fields['Collections']->getValue() as $collectionId) {
                $collections[] = new Opus_Model_Collection((int)$this->getId(), $collectionId);
            }
            return $collections;
        }
    }

    /**
     * Overwrites store procedure.
     * TODO: Implement storing collection structures.
     *
     * @return void
     */
    protected function _storeCollections() {

    }

    /**
     * Content schema information is only relevant internally and needs not get stored.
     *
     * @return void
     */
    protected function _fetchCollectionsContentSchema() {
    }

    /**
     * Creates the collection content table.
     *
     * @return void
     */
    protected function _storeCollectionsContentSchema() {
        $schema = array();
        foreach ($this->_fields['CollectionsContentSchema']->getValue() as $fieldname) {
            $schema[] = array('name' => $fieldname, 'type' => 'VARCHAR', 'length' => 255);
        }
        $role = new Opus_Collection_Roles();
        $role->createDatabaseTables($schema, $this->getId());
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
     * Retrieve all Opus_Model_CollectionRole instances from the database.
     *
     * @return array Array of Opus_Model_CollectionRole objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Model_CollectionRole', 'Opus_Db_CollectionsRoles');
    }
}
