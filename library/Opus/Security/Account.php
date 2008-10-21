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
 * Represents a system account and provides static methods to find and/or
 * remove accounts. Thus, every account has to have a password, those password
 * can be changed only by providing the current valid password. 
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_Account {
    
    /**
     * Holds the login name.
     *
     * @var string
     */
    protected $_login;
    
    /**
     * Holds the account password in md5 hash format.
     *
     * @var string
     */
    protected $_password;
    
    /**
     * Set to true if a new password is required. A given password will
     * never be validated correct as long as this variable demands a new password.
     *
     * @var boolean
     */
    protected $_new_password_required = true;
    
    /**
     * Initialize account with given credentials.
     *
     * @param string $login         Login name.
     * @param string $firstpassword Password.
     */
    protected function __construct($login, $password) {
        $this->_login = $login;
        $this->_password = $password;
    }
    
    /**
     * Create account with given credentials.
     *
     * @param string $login         Login name.
     * @param string $firstpassword Password for first login. Has to be changed later on.
     * 
     * @throws Opus_Security_Exception Thrown if the account to create already exists. 
     * 
     * @return Opus_Security_Account Account object. 
     */
    public static function create($login, $firstpassword) {
        $row = self::getRecord($login);
        if (is_null($row) === false) {
            throw new Opus_Security_Exception('Account with login name ' . $login . ' already exists.');
        }
            
        $accounts = new Opus_Db_Accounts();
        $row = $accounts->createRow();
        $row->login = $login;
        $row->password = $firstpassword;
        $row->save();
        
        return new self($row->login, $row->password);
    }
    
    /**
     * Deliver an account object given a login name.
     *
     * @param string $login Login name to query for.
     * @return Opus_Security_Account If an account with the given login name exists,
     *                               an instance of Opus_Security_Account is returned
     *                               representing this account.  
     */
    public static function find($login) {
        $row = self::getRecord($login);
                
        if (is_null($row) === true) {
            return null;
        }
         
        return new self($row->login, $row->password);
    }
    
    /**
     * Remove an account given an login name. If the specified account does not exist,
     * nothing happens. 
     *
     * @param string $login Login name.
     */
    public static function remove($login) {
        $row = self::getRecord($login);
        
        if (is_null($row) === false) {
            $row->delete();
        }
    }
    
    /**
     * Check if a given string is the correct password for this account.
     *
     * @param string $password Password.
     * @return boolean
     */
    public function isPasswordCorrect($password) {
        return ($this->_password === $password);
    }

    /**
     * Tells whether a new password is required.
     *
     * @return boolean True, if a new password is required.
     */
    public function isNewPasswordRequired() {
        return $this->_new_password_required;
    }
    

    /**
     * Get the accounts login name.
     *
     * @return string Login name.
     */
    public function getLogin() {
        return $this->_login;
    }
    
    /**
     * Given the accounts current password a new password can be set.
     *
     * @param string $old Old password.
     * @param string $new New password.
     */
    public function setPassword($old, $new) {
        $this->_new_password_required = false;
        
        if ($this->isPasswordCorrect($old) === true) {
            // Change password
            $this->_password = $new;
            
            // Update record
            $row = self::getRecord($this->_login);
            $row->password = $this->_password;
            $row->save();
        } else {
            throw new Opus_Security_Exception('Password not correct.');
        }
    }
    
    /**
     * Given the accounts current password the login name can be altered.
     *
     * @param string $password Current account password.
     * @param string $login    New login name.
     */
    public function setLogin($password, $login) {
        if ($this->isPasswordCorrect($password) === true) {

            // Get record
            $row = self::getRecord($this->_login);
            
            // Change login
            $this->_login = $login;
            
            // Update
            $row->login = $this->_login;
            $row->save();
        } else {
            throw new Opus_Security_Exception('Password not correct.');
        }
    }

    /**
     * Return the database record given a login name.
     *
     * @param string $login Login name.
     * @return Zend_Db_Row Database row representing the record.
     */
    protected static function getRecord($login) {
        $accounts = new Opus_Db_Accounts();
        $select = $accounts->select()->where('login = ?', $login);
        $row = $accounts->fetchRow($select);
        return $row;
    }
    
}