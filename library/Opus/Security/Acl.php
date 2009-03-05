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
     * Holds the ResourceIds of already loaded resources.
     *
     * @var array
     */
    protected $_loadedResources = array();

    /**
     * Table gateway to privileges table.
     *
     * @var Zend_Db_Table
     */
    protected $_dba = null;

    /**
     * Table gateway to resources table.
     *
     * @var Zend_Db_Table
     */
    protected $_resourcesTable = null;

    /**
     * Initialize table gateway.
     *
     */
    public function __construct() {
        $this->_dba = Opus_Db_TableGateway::getInstance('Opus_Db_Privileges');
        $this->_resourcesTable = Opus_Db_TableGateway::getInstance('Opus_Db_Resources');
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
     * Adds a Resource having an identifier unique to the ACL and persist its to the database.
     *
     * The $parent parameter may be a reference to, or the string identifier for,
     * the existing Resource from which the newly added Resource will inherit.
     *
     * @param  Zend_Acl_Resource_Interface        $resource
     * @param  Zend_Acl_Resource_Interface|string $parent
     * @throws Zend_Acl_Exception
     * @return Zend_Acl Provides a fluent interface
     * @see Zend_Acl::add()
     */
    public function add(Zend_Acl_Resource_Interface $resource, $parent = null) {
        // check if resource exist already
        if (true === in_array($resource->getResourceId(), $this->_loadedResources)) {
            throw new Zend_Acl_Exception("Resource id '" . $resource->getResourceId() . "' already exists in the ACL");
        }
        // check if resource exists in DB
        if (false === is_null($this->_loadResource($resource))) {
            throw new Zend_Acl_Exception("Resource id '" . $resource->getResourceId() . "' already exists in the ACL");
        }

        // get parentname
        $parentname = null;
        if (false === is_null($parent)) {
            if ($parent instanceof Zend_Acl_Resource_Interface) {
                $parentname = $parent->getResourceId();
            } else {
                $parentname = $parent;
            }
            // try to load parent if not loaded yet
            if (false === in_array($parentname, $this->_loadedResources)) {
                $this->_loadResource($parentname);
            }
        }

        // add resource to the acl, parrent::add checks if parent exists (throws Zend_Acl_Exception)
        parent::add($resource, $parent);

        // persist resource
        $this->_resourcesTable->insert(array(
            'name' => $resource->getResourceId(),
            'parent' => $parentname,
            ));

        // mark that resource as loaded
        $this->_loadedResources[] = $resource->getResourceId();
        // print("adding " . $resource->getResourceId() . "to array.\n");
        // print_r($this->_loadedResources);

        return $this;
    }

    public function get($resource) {
        $resourceId = $resource;
        if ($resource instanceof Zend_Acl_Resource_Interface) {
            $resourceId = $resource->getResourceId();
        }

        if (false === in_array($resourceId, $this->_loadedResources)) {
            $this->_loadResource($resource);
        }
        return parent::get($resource);
    }

    /**
     * Returns true if and only if the Resource exists in the ACL
     *
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @return boolean
     */
    public function has($resource)
    {
        if ($resource instanceof Zend_Acl_Resource_Interface) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        if (false === in_array($resourceId, $this->_loadedResources)) {
            // print("$resourceId not found in");
            // print_r($this->_loadedResources);
            // print("\n");
            if (true === is_null($this->_loadResource($resource))) {
                return false;
            }
        } else {
            //print("nothing loaded\n");
        }
        return isset($this->_resources[$resourceId]);
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

        // check if resource isn't loaded yet.
        if (true === in_array($resourceId, $this->_loadedResources)) {
            return;
        }
        // fetch the resource from DB
        $resourceRow = $this->_resourcesTable->fetchRow($this->_resourcesTable->select()
            ->where('name = ?', $resourceId));
        if (true === is_null($resourceRow)) {
            // resource dose not exist.
            return null;
        }

        // load parent
        if (false === is_null($resourceRow->parent)) {
            // fetch parent name (resourceId of the parent)
            $where = $this->_resourceTable->select()->where('id = ?', $resourceRow->parent);
            $parentRow = $this->_resourcesTable->fetchRow($where);
            if (true === is_null($parentRow)) {
                throw new Zend_Acl_Exception("Parent Resource id '" . $parentRow->name . "' does not exist");
            }

            if (false === in_array($parentRow->name, $this->_loadedResources)) {
                // load grandparent
                $this->_loadResource(new Zend_Acl_Resource($parentRow->name));
            }
        }
        // FIXME: instanzen der Resourcen richtig wieder herstellen!
        $resourceInstance = new Zend_Acl_Resource($resourceRow->name);

        // add ressource to the acl.
        if (true === is_null($resourceRow->parent)) {
            parent::add($resourceInstance);
        } else {
            parent::add($resourceInstance, $resourceRow->parent);
        }

        // mark the ressource as loaded
        $this->_loadedResources[] = $resourceRow->name;
        // print("adding " . $resourceRow->name . " to array.\n");
        // print_r($this->_loadedResources);

        return $resourceInstance;
    }

}
