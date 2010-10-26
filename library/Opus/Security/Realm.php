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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
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
     * Array of privileges that are defined in database:
     *  - 'administrate' means use of module /admin.
     *  - 'publish' means use of module /publish.
     *  - 'publishUnvalidated' means the possibility to ignore validation
     *    while publishing.
     *  - 'readMetadata' checks if somone is allowed to read meatdata of
     *    a document (f.e. if the not published by an administrator yet).
     *    This privilege makes it necessary to give a document id with it.
     *  - 'readFile' is checked before a document_file will be delivered.
     *    This privilege makes it necessary to give a file id with it.
     *
     * @var array
     */
    protected $_privileges = array(
        'remotecontrol',
        'administrate',
        'clearance',
        'publish',
        'publishUnvalidated',
        'readMetadata',
        'readFile',
    );

    /**
     * The current user roles.
     *
     * @var array
     */
    protected $_roles = array('guest');

    /**
     * The current username.
     *
     * @var string
     */
    protected $_username = null;

    /**
     * Thre current ip address
     *
     * @var string
     */
    protected $_ipaddress = null;

    /**
     * Set the current username.
     *
     * @param string username username to be set.
     * @return Opus_Security_Realm Fluent interface.
     */
    public function setUser($username) {
        $this->_username = $username;
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
        $regex = '/^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/';
        if (false === is_null($ipaddress) && 1 !== preg_match($regex, $ipaddress)) {
            throw new Opus_Security_Exception("$ipaddress is not a valid IP address!");
        }
        $this->_ipaddress = $ipaddress;
        $this->_setRoles();
        return $this;

    }

    /**
     * Set internal roles from current username/ipaddress.
     * Adds the default role "guest", if not done by username/ipaddress.
     *
     * @return Opus_Security_Realm Fluent interface.
     */
    protected function _setRoles() {
        $this->_roles = array_merge($this->_getIpaddressRoles(), $this->_getUsernameRoles());
        if (false === in_array('guest', $this->_roles)) {
            $this->_roles[] = 'guest';
        }
        return $this;

    }

    /**
     * Get the roles that are assigned to the specified username.
     *
     * @throws Opus_Security_Exception Thrown if the supplied identity could not be found.
     * @return array Array of assigned roles or an empty array.
     */
    protected function _getUsernameRoles() {
        if (true === is_null($this->_username) || true === empty($this->_username)) {
            return array('guest');
        }

        $result = array();
        $accounts = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $account = $accounts->fetchRow($accounts->select()->where('login = ?', $this->_username));
        if (null === $account) {
            throw new Opus_Security_Exception("An user with the given name: $this->_username could not be found.");
        }

        $roles = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        $link = Opus_Db_TableGateway::getInstance('Opus_Db_LinkAccountsRoles');
        $assignedRoles = $account->findManyToManyRowset($roles, $link);

        if (count($assignedRoles) >= 1) {
            foreach ($assignedRoles as $arole) {
                $result[] = $arole->name;
            }
        }

        return $result;

    }

    /**
     * Map an IP address to Roles.
     *
     * @throws Opus_Security_Exception Thrown if the supplied ip is not valid.
     * @return array Array of assigned roles or an empty array.
     */
    protected function _getIpaddressRoles() {
        if (true === is_null($this->_ipaddress)) {
            return array();
        }

        $ip = array();
        $regex = '/^(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])\.'
                . '(25[0-5]|2[0-4][0-9]|[01]?[0-9]?[0-9])$/';
        if (1 !== preg_match($regex, $this->_ipaddress, $ip)) {
            throw new Opus_Security_Exception('Your IP address could not be validated.');
        }

        $iprangeTable = Opus_Db_TableGateway::getInstance('Opus_Db_Ipranges');
        $iprows = $iprangeTable->fetchAll(
                                $iprangeTable->select()
                                ->where('startingip <= ?', sprintf("%u", ip2long($this->_ipaddress)))
                                ->where('endingip >= ?', sprintf("%u", ip2long($this->_ipaddress)))
        );
        if (0 === count($iprows)) {
            return array();
        }

        $result = array();
        $roles = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        $link = Opus_Db_TableGateway::getInstance('Opus_Db_LinkIprangesRoles');
        foreach ($iprows as $iprow) {
            $assignedRoles = $iprow->findManyToManyRowset($roles, $link);
            foreach ($assignedRoles as $arole) {
                $result[] = $arole->name;
            }
        }
        // remove duplicated entries with array_unique, restore key structure with array_values.
        return array_values(array_unique($result));

    }

    /**
     * Checks if a privilege is granted for actual context (usersession, ip address).
     * If administrator is one of the current roles true will be returned ingoring everything else.
     *
     * @param $privilege           string The privilege to check, a value out of Opus_Security_Realm->getPrivileges().
     * @param $documentServerState string The privilege readMetadata depends on the server_state of the document the metadata belongs to.
     *                                    Set this null for all other privileges.
     * @param $fileId              int    The privilege readFile depends on the fileId of the file to read.
     *                                    Set this null for all other privileges.
     * @return boolean  Returns true only if a privilege for any role (guest, from the ip or a usersession) is stored in db table privilege.
     * @throws Opus_Security_Exception Throws Exception if a privilege is called with the wrong parameters or if the privilege is unkown.
     */
    public function check($privilege, $documentServerState = null, $fileId = null) {
        // Check if security is switched off
        $conf = Zend_Registry::get('Zend_Config');
        if (isset($conf) and $conf->security === '0') {
            return true;
        }

        if (true === in_array('administrator', $this->_roles)) {
            return true;
        }

        if (false === in_array($privilege, $this->_privileges)) {
            throw new Opus_Security_Exception('Unknown privilege checked!');
        }

        // We need this switch-case to handle special cases, which cannot be
        // handled with "_checkPrivilege".
        switch ($privilege) {
            case 'readMetadata':
                if (true === is_null($documentServerState) || true === empty($documentServerState)) {
                    throw new Opus_Security_Exception('Missing argument: Privilege "readMetadata" needs a documentServerState.');
                }
                if (false === is_null($fileId)) {
                    throw new Opus_Security_Exception('Privilege "readMetadata" can be checked only depending on a document server state, not for a single file.');
                }
                return $this->_checkReadMetadata($documentServerState);
                break;
            case 'readFile':
                if (true === is_null($fileId) || true === empty($fileId)) {
                    throw new Opus_Security_Exception('Missing argument: Privilege "readFile" needs a fileId.');
                }
                if (false === is_null($documentServerState)) {
                    throw new Opus_Security_Exception('Privilege "readFile" can be checked only for a single file, not depending on a document server state.');
                }
                return $this->_checkReadFile($fileId);
                break;
            default:
                if (false === is_null($documentServerState) || false === is_null($fileId)) {
                    throw new Opus_Security_Exception('Privilege "'. $privilege . '" can be checked only generally, not depending on document server state or for a file.');
                }
                return $this->_checkPrivilege($privilege);
                break;
        }
        return false;
    }

    /**
     * This messages checks if the privilege administrate is allowed for one of the current roles.
     * @return boolean true if the privilege administrate is granted for one of the current roles.
     */
    private function _checkPrivilege($privilege) {
        if (is_null($privilege) || !is_string($privilege) || $privilege == '') {
            return false;
        }

        $db = Opus_Db_TableGateway::getInstance('Opus_Db_Roles')->getAdapter();
        $select = $db->select()->from(array('p' => 'privileges'), array('id'))
                        ->join(array('r' => 'roles'), 'p.role_id = r.id', '')
                        ->where('r.name IN (?)', $this->_roles)
                        ->where('p.privilege = ?', $privilege);
        $privileges = $db->fetchAll($select);
        return (1 <= count($privileges)) ? true : false;
    }

    /**
     * This messages checks if the privilege readMetadata is allowed for one of the current roles  and the specified server state.
     * @param  string $docState The server_state the document to read has (f.e. 'published').
     * @return boolean true if the privilege readMetadata is granted for one of the current roles and the specified server state.
     */
    protected function _checkReadMetadata($docState) {
        $db = Opus_Db_TableGateway::getInstance('Opus_Db_Roles')->getAdapter();
        $privileges = $db->fetchAll(
                                $db->select()
                                ->from(array('p' => 'privileges'), array('id'))
                                ->join(array('r' => 'roles'), 'p.role_id = r.id')
                                ->where('r.name IN (?)', $this->_roles)
                                ->where('p.privilege = ?', 'readMetadata')
                                ->where('p.document_server_state = ?', $docState)
        );
        return (1 <= count($privileges)) ? true : false;
    }

    /**
     * This messages checks if the privilege readMetadata is allowed for one of the current roles  and the specified server state.
     * @param string $fileId The id of the document_file, that should be read.
     * @return boolean true if the privilege readMetadata is granted for one of the current roles and the specified server state.
     */
    protected function _checkReadFile($fileId) {
        $db = Opus_Db_TableGateway::getInstance('Opus_Db_Roles')->getAdapter();
        $privileges = $db->fetchAll(
                                $db->select()
                                ->from(array('p' => 'privileges'), array('id'))
                                ->join(array('r' => 'roles'), 'p.role_id = r.id')
                                ->where('r.name IN (?)', $this->_roles)
                                ->where('p.privilege = ?', 'readFile')
                                ->where('p.file_id = ?', $fileId)
        );
        return (1 <= count($privileges)) ? true : false;
    }

    /**
     * Returns an array with all known privileges.
     * @return array Array with all known privileges.
     */
    public function getPrivileges() {
        return $this->_privileges;
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
