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
 * @author	Pascal-Nicolas Becker <becker@zib.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * This singleton class encapsulates all security specific information
 * like the current User, IP address, and method to check rights.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_Realm {

    /**
     * The current user roles (merged userRoles and ipaddressRoles).
     *
     * @var array
     */
    protected $_roles = array('guest');

    /**
     * The current user roles (based on the user name).
     *
     * @var array
     */
    protected $_userRoles = array();

    /**
     * Thre current ip address
     *
     * @var string
     */
    protected $_ipaddressRoles = array();

    /**
     * Set the current username.
     *
     * @param string username username to be set.
     * @throws Opus_Security_Exception Thrown if the supplied identity could not be found.
     * @return Opus_Security_Realm Fluent interface.
     */
    public function setUser($username) {
        // reset "old" credentials
        $this->_userRoles = array();
        $this->_setRoles();

        $this->_userRoles = self::_getUsernameRoles($username);
        $this->_setRoles();
        return $this;
    }

    /**
     * Set the current ip address.
     *
     * @param string ipaddress ip address to be set.
     * @throws Opus_Security_Exception Thrown if the supplied ip address is not a valid ip address.
     * @return Opus_Security_Realm Fluent interface.
     */
    public function setIp($ipaddress) {
        // reset "old" credentials
        $this->_ipaddressRoles = array();
        $this->_setRoles();

        $this->_ipaddressRoles = self::_getIpaddressRoles($ipaddress);
        $this->_setRoles();
        return $this;
    }

    /**
     * Set internal roles from current username/ipaddress.
     * Adds the default role "guest", if not done by username/ipaddress.
     *
     * @return Opus_Security_Realm Fluent interface.
     */
    private function _setRoles() {
        $this->_roles = array_merge($this->_userRoles, $this->_ipaddressRoles);
        $this->_roles[] = 'guest';

        $this->_roles = array_unique($this->_roles);
        return $this;
    }

    /**
     * Get the roles that are assigned to the specified username.
     *
     * @param string username username to be set.
     * @throws Opus_Security_Exception Thrown if the supplied identity could not be found.
     * @return array Array of assigned roles or an empty array.
     */
    private static function _getUsernameRoles($username) {
        if (true === is_null($username) || true === empty($username)) {
            return array();
        }

        $accounts = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $account = $accounts->fetchRow($accounts->select()->where('login = ?', $username));
        if (null === $account) {
            throw new Opus_Security_Exception("An user with the given name: $username could not be found.");
        }

        $db = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles')->getAdapter();
        $roles = $db->fetchCol(
                                $db->select()
                                ->from(array('r' => 'user_roles'), array('r.name'))
                                ->join(array('l' => 'link_accounts_roles'), 'l.role_id = r.id', '')
                                ->join(array('a' => 'accounts'), 'l.account_id = a.id', '')
                                ->where('login = ?', $username)
                                ->distinct()
        );

        return $roles;
    }

    /**
     * Map an IP address to Roles.
     *
     * @param string ipaddress ip address to be set.
     * @throws Opus_Security_Exception Thrown if the supplied ip is not valid.
     * @return array Array of assigned roles or an empty array.
     */
    private static function _getIpaddressRoles($ipaddress) {
        if (true === is_null($ipaddress) || true === empty($ipaddress)) {
            return array();
        }

        $regex = '/^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/';
        if (1 !== preg_match($regex, $ipaddress)) {
            throw new Opus_Security_Exception('Your IP address could not be validated.');
        }

        $db = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles')->getAdapter();
        $roles = $db->fetchCol(
                                $db->select()
                                ->from(array('r' => 'user_roles'), array('r.name'))
                                ->join(array('l' => 'link_ipranges_roles'), 'l.role_id = r.id', '')
                                ->join(array('i' => 'ipranges'), 'l.iprange_id = i.id', '')
                                ->where('i.startingip <= ?', sprintf("%u", ip2long($ipaddress)))
                                ->where('i.endingip >= ?', sprintf("%u", ip2long($ipaddress)))
                                ->distinct()
        );

        return $roles;
    }

    /**
     * Checks, if the logged user is allowed to access (document_id).
     *
     * @param string $document_id ID of the document to check
     * @return boolean  Returns true only if access is granted.
     */
    public function checkDocument($document_id = null) {
        if ($this->skipSecurityChecks()) {
            return true;
        }

        if (empty($document_id)) {
            return false;
        }

        $db = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles')->getAdapter();
        $results = $db->fetchAll(
                                $db->select()
                                ->from(array('ad' => 'access_documents'), array('document_id'))
                                ->join(array('r' => 'user_roles'), 'ad.role_id = r.id', '')
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
    public function checkFile($file_id = null) {
        if ($this->skipSecurityChecks()) {
            return true;
        }

        if (empty($file_id)) {
            return false;
        }

        $db = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles')->getAdapter();
        $results = $db->fetchAll(
                                $db->select()
                                ->from(array('af' => 'access_files'), array('file_id'))
                                ->join(array('r' => 'user_roles'), 'af.role_id = r.id', '')
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
    public function checkModule($module_name = null) {
        if ($this->skipSecurityChecks()) {
            return true;
        }

        if (empty($module_name)) {
            return false;
        }

        $db = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles')->getAdapter();
        $results = $db->fetchAll(
                                $db->select()
                                ->from(array('am' => 'access_modules'), array('module_name'))
                                ->join(array('r' => 'user_roles'), 'am.role_id = r.id', '')
                                ->where('r.name IN (?)', $this->_roles)
                                ->where('am.module_name = ?', $module_name)
        );
        return (1 <= count($results)) ? true : false;
    }

    /**
     * Check if user with administrator-role or security is disabled.
     *
     * @return boolean
     */
    private function skipSecurityChecks() {
        // Check if security is switched off
        $conf = Zend_Registry::get('Zend_Config');
        if (isset($conf) and $conf->security === '0') {
            return true;
        }

        if (true === in_array('administrator', $this->_roles)) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a privilege is granted for actual context (usersession, ip address).
     * If administrator is one of the current roles true will be returned ingoring everything else.
     *
     * @deprecated
     */
    public function check($privilege, $documentServerState = null, $fileId = null) {
        return $this->skipSecurityChecks();
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
