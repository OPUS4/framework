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
 * @package     Opus_Security
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * This class extends Zend_Acl to load and store rules automatically.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_Acl extends Zend_Acl {

    /**
     * Table gateway to privileges table.
     *
     * @var Zend_Db_Table
     */
    protected $_privilegesTable = null;

    /**
     * Table gateway to resources table.
     *
     * @var Zend_Db_Table
     */
    protected $_resourcesTable = null;

    /**
     * To temporarly disable storing resource ids wich
     * would otherwise lead to constraint violation in the database.
     *
     * @var Boolean
     */
    protected $_disableStorage = false;
    
    /**
     * To temporarly disable has() method.
     * 
     * @var Boolean
     */
    protected $_hasReturnsAlwaysFalse = false;

    /**
     * Initialize table gateway.
     *
     */
    public function __construct() {
        $this->_privilegesTable = Opus_Db_TableGateway::getInstance('Opus_Db_Privileges');
        $this->_resourcesTable = Opus_Db_TableGateway::getInstance('Opus_Db_Resources');
    }

    /**
     * Adds a Resource having an identifier unique to the ACL.
     * The specified resource identifier is persisted in the database.
     *
     * The $parent parameter may be a reference to, or the string identifier for,
     * the existing Resource from which the newly added Resource will inherit.
     *
     * @param  Zend_Acl_Resource_Interface        $resource
     * @param  Zend_Acl_Resource_Interface|string $parent
     * @throws Zend_Acl_Exception
     * @return Zend_Acl Provides a fluent interface
     */
    public function add(Zend_Acl_Resource_Interface $resource, $parent = null) {
    
        parent::add($resource, $parent);
        
        // Get Resource identifier
        if ($resource instanceof Zend_Acl_Resource_Interface) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }
        
        // store resource id if not prohibited
        if (false === $this->_disableStorage) {
            $this->_resourcesTable->insert(array(
                'name' => $resourceId));
        }
    }

    /**
     * Returns the Role registry for this ACL. The Role registry as delivered
     * by this method is able deliver the identifier of persisted roles.
     *
     * If no Role registry has been created yet, a new default Role registry
     * is created and returned.
     *
     * @return Opus_Security_RoleRegistry
     */
    protected function _getRoleRegistry()
    {
        if (null === $this->_roleRegistry) {
            $this->_roleRegistry = new Opus_Security_RoleRegistry();
        }
        return $this->_roleRegistry;
    }

    /**
     * Returns true if and only if the Resource exists in the ACL
     *
     * If the protected variable $_hasReturnsAlwaysFalse is set to true
     * this method always returns false.
     * 
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @return boolean
     */
    public function has($resource) {
    
        if ($this->_hasReturnsAlwaysFalse === true) {
            // calls to has() are not permitted.
            return false;
        }
        
        $result = parent::has($resource);
        
        if (false === $result) {
            // Resource not yet registered, see database

            // Get Resource identifier
            if ($resource instanceof Zend_Acl_Resource_Interface) {
                $resourceId = $resource->getResourceId();
            } else {
                $resourceId = (string) $resource;
            }

            try {
                // Attempt to load resource
                $loaded = $this->_loadResource($resource);
            } catch (Exception $ex) {
                $loaded = null;
            }
            $result = (null !== $loaded);            
        }
        
        return $result;
    }

    /**
     * Loads an resource from the db an ads it to the acl.
     *
     * @param $resource Zend_Acl_Resource_Interface|string RessourceId from the resource to load.
     * @return Zend_Acl_Resource_Interface|null Instance of Zend_Acl_Resource containing the
     *                                          ResourceId or null if the resource can not be
     *                                          found in the db.
     */
    protected function _loadResource($resource) {
        // get id
        $resourceId = $resource;
        if ($resource instanceof Zend_Acl_Resource_Interface) {
            $resourceId = $resource->getResourceId();
        }

        // fetch the resource from DB
        $resourceRow = $this->_resourcesTable->fetchRow($this->_resourcesTable->select()
            ->where('name = ?', $resourceId));
        if (true === is_null($resourceRow)) {
            // resource does not exist.
            return null;
        }

        $resourceInstance = new Zend_Acl_Resource($resourceRow->name);
        $resourceParent = null;


        // Disable unwanted creation of database records
        $this->_disableStorage = true;
        // Disable unwanted recursion on has()
        $this->_hasReturnsAlwaysFalse = true;

        // Add resource and parent resource
        $this->add($resourceInstance, $resourceParent);

        // Enable recursion on has()
        $this->_hasReturnsAlwaysFalse = false;

        // Enable creation of database records
        $this->_disableStorage = false;

        return $resourceInstance;
    }

}
