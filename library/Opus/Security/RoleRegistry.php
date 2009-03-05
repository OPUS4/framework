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
 * This class extends Zend_Acl_Role_Registry to load and store Roles using
 * via the Opus_Security_Role model.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_RoleRegistry extends Zend_Acl_Role_Registry {



    /**
     * Returns the identified Role
     *
     * The $role parameter can either be a Role or a Role identifier.
     *
     * @param  Zend_Acl_Role_Interface|string $role
     * @throws Zend_Acl_Role_Registry_Exception
     * @return Zend_Acl_Role_Interface
     */
    public function get($role)
    {
        if ($role instanceof Zend_Acl_Role_Interface) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = (string) $role;
        }

        if (!$this->has($role)) {
            /**
             * @see Zend_Acl_Role_Registry_Exception
             */
            require_once 'Zend/Acl/Role/Registry/Exception.php';
            throw new Zend_Acl_Role_Registry_Exception("Role '$roleId' not found");
        }

        // Check if it is really in the internal array list
        if (in_array($roleId, $this->_roles) === true) {
            return $this->_roles[$roleId]['instance'];
        } else {
            // If not, fetch it from the database
            $model = Opus_Security_Role::getByRoleId($roleId);
            return $model;
        }
    }

    /**
     * Returns true if and only if the Role exists in the registry
     * or as record in the database.
     *
     * The $role parameter can either be a Role or a Role identifier.
     *
     * @param  Zend_Acl_Role_Interface|string $role
     * @return boolean
     */
    public function has($role)
    {
        if ($role instanceof Zend_Acl_Role_Interface) {
            $roleId = $role->getRoleId();
        } else {
            $roleId = (string) $role;
        }

        return (isset($this->_roles[$roleId]) or Opus_Security_Role::isRoleIdExistent($roleId));
    }
   

}
