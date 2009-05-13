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
 * In difference to Zend_Acl this implementation does not support to get the
 * instance of a ressource. The method get() returns an instance of
 * Zend_Acl_Ressource containing the same ResourceId as the ressource added
 * to the acl.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_Acl extends Zend_Acl {

    /**
     * Table gateway to rules table.
     *
     * @var Zend_Db_Table
     */
    protected $_rulesTable = null;

    /**
     * Table gateway to resources table.
     *
     * @var Zend_Db_Table
     */
    protected $_resourcesTable = null;

    /**
     * Holds the ResourceIds of already loaded resources.
     *
     * @var array
     */
    protected $_loadedResources = array();

    /**
     * Initialize table gateway.
     *
     */
    public function __construct() {
        $this->_rulesTable = Opus_Db_TableGateway::getInstance('Opus_Db_Rules');
        $this->_resourcesTable = Opus_Db_TableGateway::getInstance('Opus_Db_Resources');
    }

    /**
     * Adds a Resource having an identifier unique to the ACL.
     * The specified resource identifier is persisted in the database.
     * The instance of the resource is not persisted, as documented in method get().
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
        // Get Resource identifier
        if ($resource instanceof Zend_Acl_Resource_Interface) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        // in cause of persistant we can not save all instances of ressources
        // to be consistent we want to return instances of Zend_Acl_Resource always
        $resource = new Zend_Acl_Resource($resourceId);

        // The next 4 lines are quite important to stop recursion once.
        if (true === $this->_resourceExists($resourceId)) {
            throw new Zend_Acl_Exception('Resource id \'' . $resourceId . '\' already exists in the ACL.');
        }
        $this->_loadedResources[] = $resourceId;

        parent::add($resource, $parent);

        // Get database identifier of parent if given
        if (null !== $parent) {
            if ($parent instanceof Zend_Acl_Resource_Interface) {
                $parentResourceId = $parent->getResourceId();
            } else {
                $parentResourceId = (string) $parent;
            }
            // Fetch database identifier
            $parentId = $this->getId($parentResourceId);
            if (is_null($parentId) === true) {
                throw new Opus_Security_Exception('Acl is corrupt. A resource parent is missing!');
            }
        } else {
            $parentId = null;
        }

        $this->_resourcesTable->insert(array(
            'name' => $resourceId,
            'parent_id' => $parentId));

        return $this;
    }

    /**
     * Returns an instance of Zend_Acl_Interface containing the same ResourceId, as
     * the ressource that was added to the acl. To be able to safe the acl in a
     * database, we had to break with the original API of Zend_Acl, that returns
     * the original instance of the resource here.
     *
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @throws Zend_Acl_Exception
     * @return Zend_Acl_Resource
     */
    public function get($resource) {
        return parent::get($resource);
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
            // FIXME: load RoleRegistry dynamicly.
            $this->_roleRegistry = new Opus_Security_RoleRegistry();
        }
        return $this->_roleRegistry;
    }

    /**
     * Returns true if and only if the Resource exists in the ACL.
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @return boolean
     */
    public function has($resource) {
        $result = parent::has($resource);

        if (false === $result) {
            // Get Resource identifier
            if ($resource instanceof Zend_Acl_Resource_Interface) {
                $resourceId = $resource->getResourceId();
            } else {
                $resourceId = (string) $resource;
            }

            // Did we load the ressource already?
            if (true === in_array($resourceId, $this->_loadedResources)) {
                // call comes from parrent:add(), return false
                // this is important to stop recursion while adding resources!
                return false;
            }

            // Resource not yet registered, see database
            if (false === $this->_resourceExists($resourceId)) {
                return false;
            }

            // load resource
            $loaded = $this->_loadResource($resource);
            $result = (null !== $loaded);
        }

        return $result;
    }

    /**
     * Removes a Resource and all of its children
     *
     * The $resource parameter can either be a Resource or a Resource identifier.
     *
     * @param  Zend_Acl_Resource_Interface|string $resource
     * @throws Zend_Acl_Exception
     * @return Zend_Acl Provides a fluent interface
     */
    public function remove($resource)
    {
        try {
            $resourcename = $this->get($resource)->getResourceId();
        } catch (Zend_Acl_Exception $e) {
            throw $e;
        }

        // load the database id of the resource
        $resourceDbId = $this->getId($resourcename);
        if (is_null($resourcename === true)) {
            // resource does not exists
            return null;
        }

        // load children
        // FIXME: ZEND
        $tablename = $this->_resourcesTable->info(Zend_Db_Table::NAME);
        $db = $this->_resourcesTable->getAdapter();
        $select = $db->select()->from($tablename, 'name')->where('parent_id = ?', $resourceDbId);
        $children = $this->_resourcesTable->getAdapter()->fetchCol($select);
        // remove all children
        foreach ($children as $child) {
            $this->remove($child);
        }

        // remove the resource
        $this->_resourcesTable->delete($this->_resourcesTable
            ->getAdapter()->quoteInto('id = ?', $resourceDbId));
        // remove from the list of laded Resources
        unset($this->_loadedResources[$resourcename]);

        // TODO: Remove rules concerning the ressource.

        parent::remove($resource);

        return $this;
    }

   /**
     * Removes all Resources
     *
     * @return Zend_Acl Provides a fluent interface
     */
    public function removeAll()
    {
        $db = Zend_Registry::get('db_adapter');
        $db->delete($this->_resourcesTable->info(Zend_Db_Table::NAME));
        $this->_loadedResources = array();

        parent::removeAll();
        return $this;
    }

    /**
     * Adds or deletes a rule.
     *
     * @see    Zend_Acl::setRule
     * @param  string                                   $operation  Either Zend_Acl::OP_ADD or Zend_Acl::OP_REMOVE
     * @param  string                                   $type       Either Zend_Acl::TYPE_ALLOW or Zend_Acl::TYPE_REMOVE
     * @param  Zend_Acl_Role_Interface|string|array     $roles      Role(s) to which the rule belong to.
     * @param  Zend_Acl_Resource_Interface|string|array $resources  Resources the rule covers.
     * @param  string|array                             $privileges Which privilege should be allowed or denied?
     * @param  Zend_Acl_Assert_Interface                $assert     Is not supported by Opus_Securty_Acl, leave always blank!
     * @throws Zend_Acl_Exception
     * @throws Opus_Security_Exception
     * @return Zend_Acl Provides a fluent interface
     */
    public function setRule($operation, $type, $roles = null, $resources = null, $privileges = null,
                            Zend_Acl_Assert_Interface $assert = null) {
        if (is_null($assert) === false) {
            throw new Opus_Security_Exception('Use of assertion prohibited, can not save assertion to database yet.');
        }

        // add rule to the acl
        try {
            parent::setRule($operation, $type, $roles, $resources, $privileges);
        } catch (Exception $e) {
            throw $e;
        }
        // acl excepted type and operation, no exception was thrown.
        // So we can expect they are consistend with Zend_Acl::TYPE_ALLOW, TYPE_DENY
        // OP_ADD or OP_REMOVE

        // normalize type
        $type = strtoupper($type);
        if ($type === Zend_Acl::TYPE_ALLOW) {
            $type = 1;
        } else if ($type === Zend_Acl::TYPE_DENY) {
            $type = 0;
        } else {
            throw new Zend_Acl_Exception('Unkown type: ' . $type);
        }

        // normalize operation
        $operation = strtoupper($operation);

        // load roles id
        $roles_id = array();
        if (is_array($roles) === false) {
            $roles = array($roles);
        }
        foreach ($roles as $role) {
            if (is_null($role) === true) {
                $roles_id[] = null;
            } else {
                $roleId = $this->_getRoleRegistry()->getId($role);
                if (is_null($roleId) === true) {
                    throw new Opus_Security_Exception('Error getting roleId.');
                }
                $roles_id[] = $roleId;
            }
        }

        // load resources id
        $resources_id = array();
        if (is_array($resources) === false) {
            $resources = array($resources);
        }
        foreach ($resources as $resource) {
            if (is_null($resource) === true) {
                $resources_id[] = null;
            } else {
                $resourceId = $this->getId($resource);
                if (is_null($resourceId) === null) {
                    throw new Opus_Security_Exception("Resource does not exists.");
                }
                $resources_id[] = $resourceId;
            }
        }

        // normalize privileges
        if (is_null($privileges) === true) {
            $privileges = array(null);
        }
        if (is_array($privileges) === false) {
            $privileges = array($privileges);
        }
        foreach ($privileges as $i => $privilege) {
            if (is_null($privilege) === false) {
            $privileges[$i] = mb_strtolower(trim($privilege));
            }
        }

        if ($operation === Zend_Acl::OP_ADD) {
            // for each ressource we need a rule
            foreach ($resources_id as $resourceId) {
                // for each role we need a rule
                foreach ($roles_id as $roleId) {
                    // for each privilege we need a rule
                    foreach ($privileges as $privilege) {
                        // check if rule exists already
                        $select = $this->_rulesTable->select();
                        if (is_null($roleId) === true) {
                            $select = $select->where('role_id IS NULL');
                        } else {
                            $select = $select->where('role_id = ?', $roleId);
                        }
                        if (is_null($resourceId) === true) {
                            $select = $select->where('resource_id IS NULL');
                        } else {
                            $select = $select->where('resource_id = ?', $resourceId);
                        }
                        if (is_null($privilege) === true) {
                            $select = $select->where('privilege IS NULL');
                        } else {
                            $select = $select->where('privilege = ?', $privilege);
                        }
                        $row = $this->_rulesTable->fetchRow($select);
                        if (is_null($row) === false) {
                            // rule exists already
                            if ($row->granted === $type) {
                                // nothing to do: rule is persited already
                                return $this;
                            } else {
                                // update rule
                                $row->granted = $type;
                                $row->save();
                            }
                        } else {
                            // Rule ist not persisted yet
                            $this->_rulesTable->insert(array(
                                    'role_id'       => $roleId,
                                    'resource_id'   => $resourceId,
                                    'privilege'     => $privilege,
                                    'granted'       => $type
                                    ));
                        }
                    } //foreach privileges
                } //foreach roles
            } //foreach roles
        } else if ($operation === Zend_Acl::OP_REMOVE) {
            // for each ressource we need a rule
            foreach ($resources_id as $resourceId) {
                // for each role we need a rule
                foreach ($roles_id as $roleId) {
                    // for each privilege we need a rule
                    foreach ($privileges as $privilege) {
                        // check if rule exists already
                        $where = '';
                        if (is_null($roleId) === true) {
                            if (mb_strlen($where) > 0) {
                                $where .= ' AND ';
                            }
                            $where .= 'role_id IS NULL';
                        } else {
                            if (mb_strlen($where) > 0) {
                                $where .= ' AND ';
                            }
                            $where .= $this->_rulesTable->getAdapter()
                                ->quoteInto('role_id = ?', $roleId);
                        }
                        if (is_null($resourceId) === true) {
                            if (mb_strlen($where) > 0) {
                                $where .= ' AND ';
                            }
                            $where .= 'resource_id IS NULL';
                        } else {
                            if (mb_strlen($where) > 0) {
                                $where .= ' AND ';
                            }
                            $where .= $this->_rulesTable->getAdapter()
                                ->quoteInto('resource_id = ?', $resourceId);
                        }
                        if (is_null($privilege) === true) {
                            if (mb_strlen($where) > 0) {
                                $where .= ' AND ';
                            }
                            $where .= 'privilege IS NULL';
                        } else {
                            if (mb_strlen($where) > 0) {
                                $where .= ' AND ';
                            }
                            $where .= $this->_rulesTable->getAdapter()
                                ->quoteInto('privilege = ?', $privilege);
                        }
                        $this->_rulesTable->delete($where);
                    } //foreach privileges
                } //foreach roles
            } //foreach roles
        } else {
            throw new Zend_Acl_Exception('Unknown operation: ' . $operation);
        }

        return $this;
    }

    /**
     * Returns the database ID of given resource.
     * @param $resource string|Zend_Acl_Resource_Interface Resource to look db id for.
     * @return          string|null                        Database id of the resource of null if resource could not be found in the db.
     */
    public function getId($resource) {
        // get resource name
        if ($resource instanceof Zend_Acl_Resource_Interface) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        // find resource in table
        $row = $this->_resourcesTable->fetchRow($this->_resourcesTable
            ->select()->where('name = ?', $resourceId));

        // if resource does not exists
        if (is_null($row) === true) {
            return null;
        }

        // return id
        return $row->id;
    }

    /**
     * Checks if a resource exists in DB.
     * This method ist needed to stop recursion between the methods
     * _loadResource an parent::add.
     *
     * @param $resource string|Zend_Acl_Resource_Interface Resource to look for in the database.
     * @return boolean True if and only if the resource exists in the database.
     */
    protected function _resourceExists($resource) {
        // Get Resource identifier
        if ($resource instanceof Zend_Acl_Resource_Interface) {
            $resourceId = $resource->getResourceId();
        } else {
            $resourceId = (string) $resource;
        }

        // try to find in DB
        $resourceRow = $this->_resourcesTable->fetchRow($this->_resourcesTable->select()
            ->where('name = ?', $resourceId));
        return !is_null($resourceRow);
    }

    /**
     * Loads an resource from the db an ads it to the acl.
     *
     * @param $resource Zend_Acl_Resource_Interface|string Ressource to load from database.
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

        $this->_loadedResources[] = $resourceId;

        $resourceInstance = new Zend_Acl_Resource($resourceRow->name);
        $resourceParent = null;

        // Fetch parent resource if not already registered
        if (null !== $resourceRow->parent_id) {
            $parentId = $resourceRow->parent_id;

            $parentRow = $this->_resourcesTable->fetchRow($this->_resourcesTable->select()
            ->where('id = ?', $parentId));
            $resourceParent = $parentRow->name;
        }

        // Add resource and parent resource
        parent::add($resourceInstance, $resourceParent);

        return $resourceInstance;
    }


    /**
     * Fetch all persisted rules for a given role and merge them into
     * the objects internal array rules registry.
     *
     * @param Zend_Acl_Role_Interface $role A Role object to fetch res for.
     * @return void
     */
    protected function _fetchRoleRules(Zend_Acl_Role_Interface $role) {
        // Fetch corresponding rules records
        $roleId = $this->_getRoleRegistry()->getId($role);
        $select = $this->_rulesTable->select()->where('role_id = ?', $roleId);
        $rows = $this->_rulesTable->fetchAll($select);
        foreach ($rows as $row) {
            // Get Resource
            $resourceRow = $this->_resourcesTable->find($row->resource_id)->current();
            if (null === $resourceRow) {
                $resource = null;
            } else {
                // Ensure Resource loading through get() call
                $resource = $this->get($resourceRow->name);
            }
            // Add to Acl (but not again to database)
            if (true === ((bool) $row->granted)) {
                parent::setRule(Zend_Acl::OP_ADD, Zend_Acl::TYPE_ALLOW, $role, $resource, $row->privilege);
            } else {
                parent::setRule(Zend_Acl::OP_ADD, Zend_Acl::TYPE_DENY, $role, $resource, $row->privilege);
            }
        }

    }

    /**
     * Visits an $role in order to look for a rule allowing/denying $role access to all privileges upon $resource.
     *
     * Before passing the original request to the parent Zend_Acl method, it tries to query all rules
     * corresponding with the given Role from the database.
     *
     * @see    Zend_Acl::_roleDFSVisitAllPrivileges
     * @param  Zend_Acl_Role_Interface     $role
     * @param  Zend_Acl_Resource_Interface $resource
     * @param  array                       $dfs
     * @throws Zend_Acl_Exception
     * @return boolean|null
     */
    protected function _roleDFSVisitAllPrivileges(Zend_Acl_Role_Interface $role,
        Zend_Acl_Resource_Interface $resource = null, &$dfs = null) {

        $this->_fetchRoleRules($role);

        return parent::_roleDFSVisitAllPrivileges($role, $resource, $dfs);
    }

    /**
     * Performs a depth-first search of the Role DAG, starting at $role, in order to find a rule
     * allowing/denying $role access to a $privilege upon $resource
     *
     * Before passing the original request to the parent Zend_Acl method, it tries to query all privileges
     * corresponding with the given Role from the database.
     *
     * @param  Zend_Acl_Role_Interface     $role
     * @param  Zend_Acl_Resource_Interface $resource
     * @param  string                      $privilege
     * @throws Zend_Acl_Exception
     * @return boolean|null
     */
    protected function _roleDFSOnePrivilege(Zend_Acl_Role_Interface $role,
        Zend_Acl_Resource_Interface $resource = null, $privilege = null) {

        $this->_fetchRoleRules($role);

        return parent::_roleDFSOnePrivilege($role, $resource, $privilege);
    }
}
