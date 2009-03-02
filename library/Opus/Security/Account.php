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
class Opus_Security_Account extends Opus_Model_AbstractDb {
    
    /**
     * Table to store account information to.
     *
     * @var string
     */
    protected static $_tableGatewayClass = 'Opus_Db_Accounts';

    /**
     * Set to true if a new password is required. 
     *
     * @var boolean
     */
    protected $_newPasswordRequired = true;
    
    /**
     * Override to allow retrieving an account record from the unique login name.
     *
     * @param string|integer|Zend_Db_Table_Row $id                (Optional) Id or login of existing record.
     * @param Zend_Db_Table                    $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @throws Opus_Model_Exception            Thrown if passed id is invalid.
     * @throws Opus_Security_Exception         Thrown if a passed login is invalid.
     */
    public function __construct($id = null, Opus_Db_TableGateway $tableGatewayModel = null) {
        $rec = $id;
        if (is_string($rec) === true) {
            $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
            $rec = $table->fetchRow($table->select()->where('login = ?', $rec));
            if (is_null($rec) === true) {
                throw new Opus_Security_Exception('An account with the login name ' . $id . ' cannot be found.');
            }
        }
        parent::__construct($rec, $tableGatewayModel);
    }
    
    /**
     * Initialize with login and password fields.
     *
     * @return void
     */
    protected function _init() {
        $login = new Opus_Model_Field('Login');
        $loginValidator = new Zend_Validate;
        $loginValidator->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator(new Zend_Validate_Alnum);
        $login->setValidator($loginValidator);
        
        $password = new Opus_Model_Field('Password');
        $this->addField($login)
            ->addField($password);    
    }
    
    /**
     * Tells whether a new password is required.
     *
     * @return boolean True, if a new password is required.
     */
    public function isNewPasswordRequired() {
        return $this->_newPasswordRequired;
    }


    /**
     * Stores the accounts credentials. Throws exception if something failes
     * during the store operation.
     *
     * @throws Opus_Security_Exception If storing failes.
     * @return void
     */
    public function store() {
        // Check for a proper credentials
        if ($this->isValid() === false) {
            throw new Opus_Security_Exception('Credentials are invalid.');
        }
    
        // Check if there is a account with the same
        // loginname before creating a new record.
        if (is_null($this->getId() === true)) {
            // brand new record here
            $accounts = Opus_Db_TableGateway::getInstance($tableGatewayClassName);
            $select = $accounts->select()->where('login=?', $this->getLogin());
            $row = $accounts->fetchRow($select);
            if ($row === false) {
                throw new Opus_Security_Exception('Account with login name ' . $this->getLogin() . ' already exists.'); 
            }
        }
        try {
            parent::store();
        } catch (Exception $ex) {
            throw new Opus_Security_Exception($ex->getMessage());
        }
    }
    
    
    /**
     * Validate the login before accepting the value.
     *
     * @param string $login Login name.
     * @throws Opus_Security_Exception Thrown if the login name is not valid.
     * @return Opus_Security_Account Fluent interface.
     */
    public function setLogin($login) {
        $loginField = $this->getField('Login');
        if ($loginField->getValidator()->isValid($login) === false) {
            throw new Opus_Security_Exception('Login name should only contain alpha numeric characters.');
        }
        $loginField->setValue($login);
        return $this;
    }
    
    
    /**
     * Set a new password and reset isNewPasswordRequired flag.
     * The password goes through the PHP sha1 hash algorithm.
     *
     * @param string A new password to set.
     * @return Opus_Security_Account Fluent interface.
     */
    public function setPassword($password) {
        $this->getField('Password')->setValue(sha1($password));
        $this->_newPasswordRequired = false;
        return $this;
    }


    /**
     * Check if a given string is the correct password for this account.
     *
     * @param string $password Password.
     * @return boolean
     */
    public function isPasswordCorrect($password) {
        return ($this->getPassword() === sha1($password));
    }

}

