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
 * @author		Pascal-Nicolas Becker <becker@zib.de>
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
     * The current user roles.
     *
	 * @var array
     */
    protected $_roles = array();

	/**
	 * The current username.
	 * 
	 * @var string
	 */
	protected $_username = 'guest';

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
		if (true === is_null($username)) {
			$username = 'guest';
		}
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
	 *
	 * @return Opus_Security_Realm Fluent interface.
	 */
	protected function _setRoles() {
		$this->_roles = array_merge($this->_getIpaddressRoles(), $this->_getUsernameRoles());
		return $this;
	}

    /**
	 * FIXME
     * Get the roles that are assigned to the specified username.
     *
     * @param string $username The name of the queried username.
     * @throws Opus_Security_Exception Thrown if the supplied identity could not be found.
     * @return string|array
     */
    protected function _getUsernameRoles() {
        // $accounts = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        // $account = $accounts->fetchRow($accounts->select()->where('login = ?', $identity));
        // if (null === $account) {
        //     throw new Opus_Security_Exception("An identity with the given name: $identity could not be found.");
        // }

        // $roles = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        // $link = Opus_Db_TableGateway::getInstance('Opus_Db_LinkAccountsRoles');
        // $assignedRoles = $account->findManyToManyRowset($roles, $link);

        // if (1 === $assignedRoles->count()) {
        //     // return the role name
        //     return $assignedRoles->current()->name;
        // } else if ($assignedRoles->count() > 1) {
        //     $result = array();
        //     foreach ($assignedRoles as $arole) {
        //         $result[] = $arole->name;
        //     }
        //     return $result;
        // }

        return array();
    }

    /**
	 * FIXME
     * Map an IP address to Roles.
     *
     * @param string $ipaddress IP address.
     * @return string|array|null The assigned role name or array of role names.
     *                           Null if no Role is assigned to the given IP address.
     */
    protected function _getIpaddressRoles() {
        // if (preg_match('/^[\d]{1-3}\.[\d]{1-3}\.[\d]{1-3}\.[\d]{1-3}\$/', $ipaddress) === false) {
        //     return null;
        // }

        // $ipTable = new Opus_Db_Ipaddresses();
        // $iprow = $ipTable->fetchRow($ipTable->select()->where('ipaddress = ?', $ipaddress));
        // if ($iprow === null) {
        //     return null;
        // }

        // $roles = Opus_Db_TableGateway::getInstance('Opus_Db_Roles');
        // $link = Opus_Db_TableGateway::getInstance('Opus_Db_LinkIpaddressesRoles');
        // $assignedRoles = $iprow->findManyToManyRowset($roles, $link);

        // if (1 === $assignedRoles->count()) {
        //     // return the role name
        //     return $assignedRoles->current()->name;
        // } else if ($assignedRoles->count() > 1) {
        //     $result = array();
        //     foreach ($assignedRoles as $arole) {
        //         $result[] = $arole->name;
        //     }
        //     return $result;
        // }

        return array();
    }

	/**
	 * FIXME
	 */
	public function readFile($file) {
		return true;
	}

	/**
	 * FIXME
	 */
	public function administrate()  {
		return true;
	}

	/**
	 * FIXME
	 */
	public function readMetadata($document) {
		return true;
	}

	/**
	 * FIXME
	 */
	public function publish() {
		return true;
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
