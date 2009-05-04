<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
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
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for organisational units in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_OrganisationalUnit extends Opus_Model_AbstractDb {

    /**
     * Holds the name of the models table gateway class.
     *
     * @var string Classname of Zend_Db_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_CollectionsContents';

    /**
     * Identifier of Organisational Unit.
     *
     * @var int
     */
    private $id = null;
    
    /**
     * Cache the Role identifier for the created root CollectionRole.
     *
     * @var integer
     */
    private $roleId = null;

    /**
     * Set up table in the database that mapps from a single unique identifer
     * to a Role and Collection id pair.
     *
     * FIXME This is an ugly hack to workaround the collection id problem
     *
     * @return Zend_Db_Table_Row Table row to query 
     */
    private function __getCollectionMappingTable() {
        $reg = Zend_Registry::getInstance();

        // if table and gateway already iniialised, just return the gateway
        if (true === $reg->isRegistered('Opus_Db_OaIdMap')) {
            return $reg->get('Opus_Db_OaIdMap');
        }
        
        // dynamicly create table if not existent
        $dba = $reg->get('db_adapter');
        $sql = 'CREATE TABLE IF NOT EXISTS oa_id_map (' . 
               'id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,' .
               'role_id INT(11) UNSIGNED NOT NULL,' .
               'collection_id INT(11) UNSIGNED NOT NULL,' .
               'PRIMARY KEY (id)' .
               ')';
        $dba->query($sql);
    
        // build a dynamic table gateway
        if (false === (class_exists('Opus_Db_OaIdMap'))) {
            eval('class Opus_Db_OaIdMap extends Zend_Db_Table_Abstract {}');
        }
        $table = new Opus_Db_OaIdMap(array('name' => 'oa_id_map', 'primary' => 'id'));
        
        // register this table gateway for later calls
        $reg->set('Opus_Db_OaIdMap', $table);
        
        return $table;
    }
    
    /**
     * Add a Role/Collection Id mapping to the mapping table.
     *
     * @return integer Identifier of the mapping
     */
    private function __addMapping($roleId, $collectionId) {
        $map = $this->__getCollectionMappingTable();
        $id = $map->insert(array(
            'role_id' => $roleId,
            'collection_id' => $collectionId));
        return (int) $id;
    }
    
    /**
     * Map an Identifier to a Role-Collection-Id pair.
     *
     * @param integer $id Identifier.
     * @return array Associative array containing roleId and collectionId keys or null 
     *               if this Id could not be mapped. 
     */
    private function __mapId($id) {
        $map = $this->__getCollectionMappingTable();
        $row = $map->find($id)->current();
        if (null === $row) return null;
        return array('roleId' => (int) $row->role_id, 'collectionId' => (int) $row->collection_id);
    }
    
    /**
     * Map an Collection and Role Id pair to an unique identifier.
     *
     * @param integer $roleId
     * @param integer $collectionId
     * @return integer Unique identifier for the specified collection.
     */
    private function __mapCollectionId($roleId, $collectionId) {
        $map = $this->__getCollectionMappingTable();
        $row = $map->fetchRow($map->select()
            ->where('role_id = ?', $roleId)
            ->where('collection_id = ?', $collectionId));
        if (null === $row) return null;
        return $row->id;
    }    

    /**
     * Retrieve the identifier of the CollectionRole named "Organisational Units" 
     * used as tree for all collections of this class. If no such CollectionRole
     * exists, a new one is created.
     *
     * @return int RoleId 
     */
    private function __getRoleId() {
    
        if (null !== $this->roleId) {
            return $this->roleId;
        }
    
        // Try to get CollectionRole by name
        $roles = Opus_Collection_Information::getAllCollectionRoles();
        foreach ($roles as $role) {
            if ($role['name'] === 'Organisational Units') {
                $this->roleId = (int) $role['id'];
                break;
            }
        }

        // no CollectionRole found, create a new one        
        if (null === $this->roleId) {
            // FIXME Hard coded mapping of fields to SQL types
            $fields = array(
                array('name' => 'name', 'type' => 'VARCHAR', 'length' => 255),
                array('name' => 'postal_address', 'type' => 'VARCHAR', 'length' => 255),
                array('name' => 'homepage', 'type' => 'VARCHAR', 'length' => 255)
            );
            $this->roleId = Opus_Collection_Information::newCollectionTree(
                    array('name' => 'Organisational Units'), $fields);
        }
        
        return $this->roleId;
    }
   

    /**
     * Initialise a new instance. If the ID of a persisted model is given
     * its values get fetched from the Database.
     *
     * @param integer $id (Optional) The Identifier of an organisational is an integer.
     */
    public function __construct($id = null) {
        if (null === $id) {
            $this->_init();
            return;
        }
        
        // map the unique id to a Role-Collection-Id pair
        $cids = $this->__mapId($id);
        if (null === $cids) {
            throw new Opus_Model_Exception('Cannot find Organisational Unit with ID ' . $id);
        }
        $this->id = (int) $id;

        // connect to CollectionContents table row
        $classname = $this->getTableGatewayClass();
        $gateway = new $classname((int) $cids['roleId']);
        $this->_primaryTableRow = $gateway->find((int) $cids['collectionId'])->current();
        
        // call parent constructor to set up Opus_Model_AbstractDb infrastructure
        parent::__construct($this->_primaryTableRow, $gateway);
    }
    
        
    

    /**
     * Add metadata fields.
     *
     * @return void
     * @see library/Opus/Model/Opus_Model_Abstract#_init()
     */
    protected function _init() {
        $name = new Opus_Model_Field('Name');
        $name->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $postalAddress = new Opus_Model_Field('PostalAddress');

        $homepage = new Opus_Model_Field('Homepage');

        $this->addField($name)
            ->addField($postalAddress)
            ->addField($homepage);
    }
    
    
    /**
     * Fetch values from CollectionContents table. If no such table
     * is initialized yet, skip fetching.
     *
     * @return void
     */
    protected function _fetchValues() {
        if (null !== $this->_primaryTableRow) {
            parent::_fetchValues();
        }
    }
    
    
    /**
     * Store the objects information to the database. If no Collection has been
     * created up to this point, it creates one an does setup all database related
     * information to connect to a database record.
     *
     * @return void
     */
    public function store() {
        if (null === $this->_primaryTableRow) {
            // create a new collection representing the new organisational unit
            $roleId = (int) $this->__getRoleId();           
            $collId = (int) Opus_Collection_Information::newCollection(
                $roleId, 1, 0);
            
            // create a new mapping to obtain a single value unique id
            $this->id = $this->__addMapping($roleId, $collId);
            
            // connect to CollectionContents table row
            $classname = $this->getTableGatewayClass();
            $gateway = new $classname($roleId);
            $this->_primaryTableRow = $gateway->find($collId)->current();
        }
        parent::store();
        return (int) $this->id;
    }
    
    /**
     * Return unique Id instead of primary table row's.
     *
     * @return integer Identifier for this Organisational Unit model.
     */
    public function getId() {
        return $this->id;
    }   
    
    /**
     * Creates a new Organisational Unit and adds it as subdivision.
     * Note that the new subdevision element is instantly persisted in the collection database.
     *
     * @param string $name Name of the subdivision to append.
     * @return Opus_OrganisationalUnit The newly created Organisational Unit
     */
    public function addSubdivision($name) {
        // creates a new collection under the root collection element
        $subdivision = new Opus_OrganisationalUnit();
        $subdivision->setName($name);
        $subdivision->store();
    
        // this is "creating" a new position for the given Collection
        $my = $this->__mapId($this->getId());
        $sub = $this->__mapId($subdivision->getId());
        $colpos = Opus_Collection_Information::newCollectionPosition(
            $my['roleId'], $sub['collectionId'], $my['collectionId'], 1);
        // this is "removing" the old collection position,
        // assuming that the collection is been located in the
        // internal root collection (#1).
        Opus_Collection_Information::deleteCollectionPosition(
            $my['roleId'], $sub['collectionId'], 1);
    
        return $subdivision;
    }   
    
    /**
     * Returns an array containing all appended subdivision objects.
     *
     * @return array Array of all appended subdivisions models.
     */
    public function getSubdivisions() {
    
        // this returnes all collection of the Organisational Unit role
        $my = $this->__mapId($this->getId());
        $subcollections = Opus_Collection_Information::getSubCollections(
            $my['roleId'], $my['collectionId']);
    
        $subdivisions = array();
        foreach ($subcollections as $subcoll) {
            $subcolid = $subcoll['content'][0]['id'];
            // the root collection itself may be part of this list
            // so only instanciate a collection object if its id
            // differs from the collection id of $this object
            if ($subcolid !== $my['collectionId']) {
                $uid = $this->__mapCollectionId($this->__getRoleId(), $subcolid);
                $subdivision = new Opus_OrganisationalUnit($uid);
                $subdivisions[$subdivision->getName()] = $subdivision;
            }
        }
        return $subdivisions;
    }   
    
}
