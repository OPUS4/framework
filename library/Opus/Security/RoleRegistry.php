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
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * This class extends Zend_Acl_Role_Registry to load and store Roles.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_RoleRegistry extends Zend_Acl_Role_Registry {


    /**
     * Load all Roles from the database.
     *
     */
    public function __construct() {
        // Query all Roles from the database beginning with the parents
        $db = Zend_Db_Table::getDefaultAdapter();
        $select = $db->select()
            ->from(array('r1' => 'roles'), array('id','name'))
            ->joinLeft(array('r2' => 'roles'), 'r2.id = r1.parent', array('name AS parent'))
            ->order(array('r1.parent ASC'));
        $rowset = $db->fetchAll($select);
        
        foreach ($rowset as $row) {
            $role = new Zend_Acl_Role($row['name']);
            parent::add($role, $row['parent']);
            $roleId = $role->getRoleId();
            $this->_roles[$roleId]['dbid'] = $row['id'];
        }        
    }

    /**
     * Return the database id of specified Role. If the role has no id, null is returned.
     *
     * @param Zend_Acl_Role_Interface|string $role Role or role identifer.
     * @return mixed Database id.
     */
    public function getId($role) {
        if ($role instanceof Zend_Acl_Role_Interface) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = (string) $role;
        }
        if (array_key_exists('dbid', $this->_roles[$roleId])) {
            return $this->_roles[$roleId]['dbid'];
        }
        return null;
    }
    
    /**
     * Adds a Role having an identifier unique to the registry.
     * The added Role gets stored to the roles table in the database.
     *
     * @see Zend_Acl_Role_Registry::add()
     * @param  Zend_Acl_Role_Interface              $role
     * @param  Zend_Acl_Role_Interface|string|array $parents
     * @throws Zend_Acl_Role_Registry_Exception
     * @return Zend_Acl_Role_Registry Provides a fluent interface
     */
    public function add(Zend_Acl_Role_Interface $role, $parents = null) {
        parent::add($role, $parents);
        
        // Determine a parent Role id
        $parentId = null;
        if (false === empty($this->_roles[$role->getRoleId()]['parents'])) {
            $parentId = $this->getId(current($this->_roles[$role->getRoleId()]['parents']));
        }
        
        $roles = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        $id = $roles->insert(array(
            'name' => $role->getRoleId(),
            'parent' => $parentId));
        $this->_roles[$role->getRoleId()]['dbid'] = $id;
        
        return $this;
    }

}
