<?php
/**
 * Defines a model for manipulating Account entities.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id$
 */

/**
 * Provides accessor functions and business logic related to Accounts.
 * While authentication is handled by Zend_Auth component, methods defined
 * in this class are to be used for authentication and management.
 *
 * @package     Opus_Application_Framework
 * @subpackage 	Data_Model
 */
class Opus_Data_Model_Account {

    /**
     * Holds identifier key if the entity has been succesfully persisted
     * to a datastore.
     *
     * @var mixed
     */
    protected $_id = null;


    /**
     * Holds the identifier key of a Site the Account is bound to or a reference
     * to an already stored Site.
     *
     * @var mixed
     */
    public $site = null;

    /**
     * Holds the login name.
     *
     * @var string
     */
    public $username;


    /**
     * Holds the account's password.
     *
     * @var string
     */
    public $password;

    /**
     * Return the datastore identifier.
     *
     * @return mixed
     */
    public function getId() {
        return $this->_id;
    }

	/**
	 * Check whether the current user is belongs to the selected site.
	 *
	 * @param string  $username Name of the user to check for site membership.
	 * @param integer $site_id  Id of selected site.
	 * @return boolean True, if the user belongs to the site with the given id.
	 *
	 */
	public static function isUserFromSite($username, $site_id) {
	    $accounts = new Opus_Data_Db_Accounts();
		$row = $accounts->fetchRow($accounts->select()
		->where('site_id = ?', $site_id)
		->where('username = ?', $username));
		return (boolean) $row;
	}

	/**
	 * Fetch all Account entities from the datastore. Returning an array
	 * using the "username" field of each entity as key.
	 *
	 * @return array Array of Opus_Data_Model_Account objects.
	 */
	public static function getAll() {
	    $accounts = new Opus_Data_Db_Accounts();
        $rowset = $accounts->fetchAll();
        $result = array();
        foreach ($rowset as $row) {
            $account = new Opus_Data_Model_Account();
            $account->_id = $row->account_id;
            $account->site = $row->site_id;
            $account->username = $row->username;
            $account->password = $row->password;
            $result[$account->username] = $account;
        }
        return $result;
	}

	/**
	 * Return a specific Account object from the datastore given
	 * an identifier.
	 *
	 * @param mixed $id Identifier of Account entity to get.
	 * @return Opus_Data_Model_Account Instance of Account entity if found, null if not.
	 */
	public static function get($id) {
	    $accounts = new Opus_Data_Db_Accounts();
        $row = $accounts->find($id)->getRow(0);
        if ( is_null($row) === false ) {
            $account = new Opus_Data_Model_Account();
            $account->_id = $row->account_id;
            $account->site = $row->site_id;
            $account->username = $row->username;
            $account->password = $row->password;
            return $account;
        }
	    return null;
	}

	/**
	 * Persist the Account object returning its identifier.
	 *
	 * @return mixed Datastore identifier.
	 */
	public function save() {
	    $accounts = new Opus_Data_Db_Accounts();
        if ( is_null($this->_id) === true ) {
            $row = $accounts->createRow();
        } else {
            $row = $accounts->find($this->_id)->getRow(0);
        }
        $row->username = $this->username;
        $row->password = $this->password;

        if ( $this->site instanceof Opus_Data_Model_Site ) {
            $row->site_id = $this->site->getId();
        } else {
            $row->site_id = $this->site;
        }

        $row->save();

        if ( is_null($this->_id) === true ) {
            $this->_id = $accounts->getAdapter()->lastInsertId();
        }
        return $this->_id;
	}
}