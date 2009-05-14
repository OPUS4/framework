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
 * @package     Opus_Model
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * This singleton class encapsulates all security secific information
 * like the current User, Role and Acl.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_Realm {


    /**
     * The current access control list.
     *
     * @var Zend_Acl
     */
    protected $_acl = null;

    /**
     * The current user role.
     *
     * @var Zend_Acl_Role_Interface
     */
    protected $_role = null;


    /**
     * The current master resource all newly created resources shall belong to.
     *
     * @var Zend_Acl_Resource_Interface
     */
    protected $_resource = null;

    /**
     * Set the current Acl instance.
     *
     * @param Zend_Acl Acl instance to be set.
     * @return Opus_Security_Realm Fluent interface.
     */
    public function setAcl($acl) {
        $this->_acl = $acl;
    }

    /**
     * Return the current Acl.
     *
     * @return Zend_Acl Current Acl instance.
     */
    public function getAcl() {
        return $this->_acl;
    }


    /**
     * Set the current Role instance.
     *
     * @param Zend_Acl_Role_Interface Role instance to be set.
     * @return Opus_Security_Realm Fluent interface.
     */
    public function setRole($role) {
        $this->_role = $role;
    }

    /**
     * Return the current Role.
     *
     * @return Zend_Acl_Role_Interface Current Role instance.
     */
    public function getRole() {
        return $this->_role;
    }


    /**
     * Set the current Master Resource instance.
     *
     * @param Zend_Acl_Resource_Interface Resource instance to be set.
     * @return Opus_Security_Realm Fluent interface.
     */
    public function setResourceMaster(Zend_Acl_Resource_Interface $master) {
        $this->_resource = $master;
    }

    /**
     * Return the current Master Resource.
     *
     * @return Zend_Acl_Resource_Interface Current Master Resource instance.
     */
    public function getResourceMaster() {
        return $this->_resource;
    }


    /**
     * Get the roles that are assigned to the specified identity.
     *
     * @param string $identity The name of the queried identity.
     * @throws Opus_Security_Exception Thrown if the supplied identity could not be found.
     * @return string|array
     */
    public function getIdentityRole($identity) {
        $accounts = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $account = $accounts->fetchRow($accounts->select()->where('login = ?', $identity));
        if (null === $account) {
            throw new Opus_Security_Exception("An identity with the given name: $identity could not be found.");
        }

        $roles = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        $link = Opus_Db_TableGateway::getInstance('Opus_Db_LinkAccountsRoles');
        $assignedRoles = $account->findManyToManyRowset($roles, $link);

        if (1 === $assignedRoles->count()) {
            // return the role name
            return $assignedRoles->current()->name;
        } else if ($assignedRoles->count() > 1) {
            $result = array();
            foreach ($assignedRoles as $arole) {
                $result[] = $arole->name;
            }
            return $result;
        }

        return null;
    }

    public function getIpaddressRole($ipaddress) {
        // FIXME
        return null;
    }

    /********************************************************************************************/
    /* Singleton code below                                                                     */
    /********************************************************************************************/

    /**
     * Holds instance.
     *
     * @var Opus_Security_Realm.
     */
    private static $instance = null;

     /**
     * Delivers the singleton instance.
     *
     * @return Opus_Security_Realm
     */
    final public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new Opus_Security_Realm;
        }
        return self::$instance;
    }

    /**
     * Disallow construction.
     *
     */
    final private function __construct() {
    }

    /**
     * Singleton classes cannot be cloned!
     *
     * @return void
     */
    final private function __clone() {
    }

    /**
     * Singleton classes should not be put to sleep!
     *
     * @return void
     */
    final private function __sleep() {
    }

}