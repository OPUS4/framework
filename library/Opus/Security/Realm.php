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
 * @package     Opus\Model
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Security;

use Opus\Db\TableGateway;

/**
 * This singleton class encapsulates all security specific information
 * like the current User, IP address, and method to check rights.
 *
 * @category    Framework
 * @package     Opus\Security
 *
 * TODO NAMESPACE rename class?
 */
class Realm implements IRealm
{

    /**
     * The current user roles (merged userRoles and ipaddressRoles).
     *
     * @var array
     */
    protected $_roles = ['guest'];

    /**
     * The current user roles (based on the user name).
     *
     * @var array
     */
    protected $_userRoles = [];

    /**
     * Thre current ip address
     *
     * @var string
     */
    protected $_ipaddressRoles = [];

    /**
     * Set the current username.
     *
     * @param string username username to be set.
     * @throws SecurityException Thrown if the supplied identity could not be found.
     * @return Realm Fluent interface.
     */
    public function setUser($username)
    {
        // reset "old" credentials
        $this->_userRoles = [];
        $this->_setRoles();

        $this->_userRoles = self::_getUsernameRoles($username);
        $this->_setRoles();
        return $this;
    }

    /**
     * Set the current ip address.
     *
     * @param string ipaddress ip address to be set.
     * @throws SecurityException Thrown if the supplied ip address is not a valid ip address.
     * @return Realm Fluent interface.
     */
    public function setIp($ipaddress)
    {
        // reset "old" credentials
        $this->_ipaddressRoles = [];
        $this->_setRoles();

        $this->_ipaddressRoles = self::_getIpaddressRoles($ipaddress);
        $this->_setRoles();
        return $this;
    }

    /**
     * Set internal roles from current username/ipaddress.
     * Adds the default role "guest", if not done by username/ipaddress.
     *
     * @return Realm Fluent interface.
     */
    private function _setRoles()
    {
        $this->_roles = array_merge($this->_userRoles, $this->_ipaddressRoles);
        $this->_roles[] = 'guest';

        $this->_roles = array_unique($this->_roles);
        return $this;
    }

    /**
     * Get the roles that are assigned to the specified username.
     *
     * @param string username username to be set.
     * @throws SecurityException Thrown if the supplied identity could not be found.
     * @return array Array of assigned roles or an empty array.
     */
    private static function _getUsernameRoles($username)
    {
        if (true === is_null($username) || true === empty($username)) {
            return [];
        }

        $accounts = TableGateway::getInstance('Opus\Db\Accounts');
        $account = $accounts->fetchRow($accounts->select()->where('login = ?', $username));
        if (null === $account) {
            $logger = Log::get();
            $message = "An user with the given name: $username could not be found.";
            if (! is_null($logger)) {
                $logger->err($message);
            }
            throw new SecurityException($message);
        }

        $db = TableGateway::getInstance('Opus\Db\UserRoles')->getAdapter();
        $roles = $db->fetchCol(
            $db->select()
                                ->from(['r' => 'user_roles'], ['r.name'])
                                ->join(['l' => 'link_accounts_roles'], 'l.role_id = r.id', '')
                                ->join(['a' => 'accounts'], 'l.account_id = a.id', '')
                                ->where('login = ?', $username)
                                ->distinct()
        );

        return $roles;
    }

    /**
     * Map an IP address to Roles.
     *
     * @param string ipaddress ip address to be set.
     * @throws SecurityException Thrown if the supplied ip is not valid.
     * @return array Array of assigned roles or an empty array.
     */
    private static function _getIpaddressRoles($ipaddress)
    {
        if (true === is_null($ipaddress) || true === empty($ipaddress)) {
            return [];
        }

        if (! self::validateIpAddress($ipaddress)) {
            throw new SecurityException('Your IP address could not be validated.');
        }

        $db = TableGateway::getInstance('Opus\Db\UserRoles')->getAdapter();
        $roles = $db->fetchCol(
            $db->select()
                                ->from(['r' => 'user_roles'], ['r.name'])
                                ->join(['l' => 'link_ipranges_roles'], 'l.role_id = r.id', '')
                                ->join(['i' => 'ipranges'], 'l.iprange_id = i.id', '')
                                ->where('i.startingip <= ?', sprintf("%u", ip2long($ipaddress)))
                                ->where('i.endingip >= ?', sprintf("%u", ip2long($ipaddress)))
                                ->distinct()
        );

        return $roles;
    }

    /**
     * Returns all module resources to which the current user and ip address
     * has access.
     *
     * @param $username     name of the account to get resources for.
     *                      Defaults to currently logged in user
     * @param $ipaddress    IP address to get resources for.
     *                      Defaults to current remote address if available.
     * @throws SecurityException Thrown if the supplied ip is not valid or
     *                      user can not be determined
     * @return array        array of module resource names
     */

    public static function getAllowedModuleResources($username = null, $ipaddress = null)
    {
        $resources = [];
        if (! is_null($ipaddress) && ! self::validateIpAddress($ipaddress)) {
            throw new SecurityException('Your IP address could not be validated.');
        }

        if (empty($ipaddress) && empty($username)) {
            throw new SecurityException('username and / or IP address must be provided.');
        } else {
            $db = TableGateway::getInstance('Opus\Db\UserRoles')->getAdapter();
            $select = $db->select();
            $select->from(['am' => 'access_modules'], ['am.module_name'])
                    ->joinLeft(['r' => 'user_roles'], 'r.id = am.role_id')
                    ->distinct();
            if (! is_null($username)) {
                $select->joinLeft(['la' => 'link_accounts_roles'], 'la.role_id = r.id', '')
                        ->joinLeft(['a' => 'accounts'], 'la.account_id = a.id', '')
                        ->where('login = ?', $username);
            }
            if (! is_null($ipaddress)) {
                $select->joinLeft(['li' => 'link_ipranges_roles'], 'li.role_id = r.id', '')
                        ->joinLeft(['i' => 'ipranges'], 'li.iprange_id = i.id', '');
                $select->orWhere('i.startingip <= ? AND i.endingip >= ?', sprintf("%u", ip2long($ipaddress)), sprintf("%u", ip2long($ipaddress)));
            }
            $resources = $db->fetchCol($select);
        }
        return $resources;
    }

    /**
     * checks if the string provided is a valid ip address
     *
     * @param string ipaddress ip address to validate.
     * @return boolean Returns true if validation succeeded
     */
    private static function validateIpAddress($ipaddress)
    {
        $regex = '/^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/';
        return preg_match($regex, $ipaddress) === 1;
    }

    /**
     * Checks, if the logged user is allowed to access (document_id).
     *
     * @param string $document_id ID of the document to check
     * @return boolean  Returns true only if access is granted.
     */
    public function checkDocument($document_id = null)
    {
        if ($this->skipSecurityChecks()) {
            return true;
        }

        if (empty($document_id)) {
            return false;
        }

        $db = TableGateway::getInstance('Opus\Db\UserRoles')->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                                ->from(['ad' => 'access_documents'], ['document_id'])
                                ->join(['r' => 'user_roles'], 'ad.role_id = r.id', '')
                                ->where('r.name IN (?)', $this->_roles)
                                ->where('ad.document_id = ?', $document_id)
        );
        return (1 <= count($results)) ? true : false;
    }

    /**
     * Checks, if the logged user is allowed to access (file_id).
     *
     * @param string $file_id ID of the file to check
     * @return boolean  Returns true only if access is granted.
     */
    public function checkFile($file_id = null)
    {
        if ($this->skipSecurityChecks()) {
            return true;
        }

        if (empty($file_id)) {
            return false;
        }

        $db = TableGateway::getInstance('Opus\Db\UserRoles')->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                                ->from(['af' => 'access_files'], ['file_id'])
                                ->join(['r' => 'user_roles'], 'af.role_id = r.id', '')
                                ->where('r.name IN (?)', $this->_roles)
                                ->where('af.file_id = ?', $file_id)
        );
        return (1 <= count($results)) ? true : false;
    }

    /**
     * Checks, if the logged user is allowed to access (module_name).
     *
     * @param string $module_name Name of the module to check
     * @return boolean  Returns true only if access is granted.
     */
    public function checkModule($module_name = null)
    {
        if ($this->skipSecurityChecks()) {
            return true;
        }

        if (empty($module_name)) {
            return false;
        }

        $db = TableGateway::getInstance('Opus\Db\UserRoles')->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                                ->from(['am' => 'access_modules'], ['module_name'])
                                ->join(['r' => 'user_roles'], 'am.role_id = r.id', '')
                                ->where('r.name IN (?)', $this->_roles)
                                ->where('am.module_name = ?', $module_name)
        );
        return (1 <= count($results)) ? true : false;
    }

    /**
     * Checks if a user has access to a module.
     * @param $module_name Name of module
     * @param $user Name of user
     */
    public static function checkModuleForUser($module_name, $user)
    {
        $roles = self::_getUsernameRoles($user);

        $db = TableGateway::getInstance('Opus\Db\UserRoles')->getAdapter();
        $results = $db->fetchAll(
            $db->select()
                ->from(['am' => 'access_modules'], ['module_name'])
                ->join(['r' => 'user_roles'], 'am.role_id = r.id', '')
                ->where('r.name IN (?)', $roles)
                ->where('am.module_name = ?', $module_name)
        );
        return (1 <= count($results)) ? true : false;
    }

    /**
     * Check if user with administrator-role or security is disabled.
     *
     * @return boolean
     */
    public function skipSecurityChecks()
    {
        // Check if security is switched off
        $conf = Config::get();
        if (isset($conf->security) && (! filter_var($conf->security, FILTER_VALIDATE_BOOLEAN))) {
            return true;
        }

        if (true === in_array('administrator', $this->_roles)) {
            return true;
        }

        return false;
    }

    /**
     * Returns the names of the roles for current user and ip address range.
     * @return array of strings - Names of roles
     */
    public function getRoles()
    {
        return $this->_roles;
    }

    /**
     * Checks if a privilege is granted for actual context (usersession, ip address).
     * If administrator is one of the current roles true will be returned ingoring everything else.
     *
     * @deprecated
     */
    public function check($privilege, $documentServerState = null, $fileId = null)
    {
        return $this->skipSecurityChecks();
    }

    /********************************************************************************************/
    /* Singleton code below                                                                     */
    /********************************************************************************************/

    /**
     * Holds instance.
     *
     * @var Realm.
     */
    private static $instance = null;

    /**
     * Delivers the singleton instance.
     *
     * @return Realm
     */
    final public static function getInstance()
    {
        if (null === self::$instance) {
            $class = get_called_class();
            self::$instance = new $class;
        }
        return self::$instance;
    }

    /**
     * Disallow construction.
     *
     */
    final private function __construct()
    {
    }

    /**
     * Singleton classes cannot be cloned!
     *
     * @return void
     */
    final private function __clone()
    {
    }

    /**
     * Singleton classes should not be put to sleep!
     *
     * @return void
     */
    final private function __sleep()
    {
    }
}
