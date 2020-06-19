<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @package     Opus
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for accounts in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 *
 * @method string getLogin()
 *
 * @method string getPassword()
 *
 * @method string getFirstName()
 * @method void setFirstName(string $firstName)
 *
 * @method string getLastName()
 * @method void setLastName(string $lastName)
 *
 * @method string getEmail()
 * @method void setEmail(string $email)
 *
 * @method void addRole(Opus_UserRole $role)
 * @method Opus_UserRole[] getRole()
 * @method void setRole(Opus_UserRole[] $roles)
 */
class Opus_Account extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Accounts';

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Account table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = [
        'Role' => [
            'model' => 'Opus_UserRole',
            'through' => 'Opus_Model_Dependent_Link_AccountRole',
            'fetch' => 'lazy'
        ],
    ];

    /**
     * Retrieve all Opus_Account instances from the database.
     *
     * @return array Array of Opus_Account objects.
     */
    public static function getAll()
    {
        return self::getAllFrom('Opus_Account', 'Opus_Db_Accounts');
    }

    /**
     * Override to allow retrieving an account either by id or by the unique login name.
     * If neither id nor login are specified a new persistant instance gets created which
     * got idts id set as soon as it is stored via a call to _store().
     *
     * @param integer|Zend_Db_Table_Row $id                (Optional) (Id of) Existing database row.
     * @param Zend_Db_Table_Abstract    $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @param string                    $id                (Optional) Login of existing record.
     * @throws Opus\Model\Exception     Thrown if passed id is invalid or login and id are specified.
     */
    public function __construct($id = null, Zend_Db_Table_Abstract $tableGatewayModel = null, $login = null)
    {
        if (false === is_null($login) && false === empty($login)) {
            if (false === is_null($id) && false === empty($id)) {
                 throw new Opus\Model\Exception('Login and id of an account are specified, specify either id or login.');
            }
            $id = Opus_Account::fetchAccountRowByLogin($login);
            if (! isset($id)) {
                throw new Opus_Security_Exception('An account with the login name ' . $login . ' cannot be found.');
            }
        }
        parent::__construct($id, $tableGatewayModel);
    }

    /**
     * Initialize model with the following fields:
     * - Username
     * - Password
     *
     * @return void
     */
    protected function _init()
    {
        $login = new Opus_Model_Field('Login');
        $loginValidator = new Zend_Validate;

        // NOTE: Validation is also defined in Application_Form_Element_Login
        $loginValidator->addValidator(new Zend_Validate_Regex('/^[A-Za-z0-9@._-]+$/'));
        $login->setValidator($loginValidator)->setMandatory(true);

        $password = new Opus_Model_Field('Password');
        $password->setMandatory(true);

        $email = new Opus_Model_Field('Email');
        $emailValidator = new Zend_Validate;
        $emailValidator->addValidator(new Zend_Validate_EmailAddress());
        $email->setMandatory(true);

        $firstName = new Opus_Model_Field('FirstName');
        $lastName = new Opus_Model_Field('LastName');

        $role = new Opus_Model_Field('Role');
        $role->setMultiplicity('*');
        $role->setSelection(true);

        $this->addField($login)
                ->addField($password)
                ->addField($email)
                ->addField($firstName)
                ->addField($lastName)
                ->addField($role);
    }

    /**
     * Stores the accounts credentials. Throws exception if something failes
     * during the store operation.
     *
     * @throws Opus_Security_Exception If storing failes.
     * @return void
     */
    public function store()
    {
        // Check for a proper credentials
        if ($this->isValid() === false) {
            throw new Opus_Security_Exception('Credentials are invalid.');
        }

        // Check if there is a account with the same
        // loginname before creating a new record.
        if (is_null($this->getId()) === true) {
            $row = Opus_Account::fetchAccountRowByLogin($this->getLogin());
            if (is_null($row) === false) {
                throw new Opus_Security_Exception('Account with login name ' . $this->getLogin() . ' already exists.');
            }
        }
        // Now really store.
        try {
            return parent::store();
        } catch (Exception $ex) {
            $logger = Zend_Registry::get('Zend_Log');
            if (null !== $logger) {
                $message = "Unknown exception while storing account: ";
                $message .= $ex->getMessage();
                $logger->err(__METHOD__ . ': ' . $message);
            }

            $message = "Caught exception.  Please consult the server logfile.";
            throw new Opus_Security_Exception($message);
        }
    }

    /**
     * Helper method to fetch account-rows by login name.
     */
    private static function fetchAccountRowByLogin($login)
    {
        if (false === isset($login) or false === is_string($login)) {
            return;
        }

        $accounts = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $accounts->select()->where('login = ?', $login);
        return $accounts->fetchRow($select);
    }

    /**
     * Alternate constructor to fetch account-objects by login name.
     *
     * @return Opus_Account
     */
    public static function fetchAccountByLogin($login)
    {
        $row = self::fetchAccountRowByLogin($login);

        if (isset($row)) {
            return new self($row);
        }
    }

    /**
     * Validate the login before accepting the value.
     *
     * @param string $login Login name.
     * @throws Opus_Security_Exception Thrown if the login name is not valid.
     * @return Opus_Account Fluent interface.
     */
    public function setLogin($login)
    {
        $login = $this->_convertToScalar($login);
        $loginField = $this->getField('Login');
        if ($loginField->getValidator()->isValid($login) === false) {
            Zend_Registry::get('Zend_Log')->debug('Login not valid: ' . $login);
            throw new Opus_Security_Exception('Login name is empty or contains invalid characters.');
        }
        $loginField->setValue($login);
        return $this;
    }

    /**
     * Set a new password.  The password goes through the PHP sha1 hash
     * algorithm.
     *
     * @param string $password The new password to set.
     * @return Opus_Account Fluent interface.
     */
    public function setPassword($password)
    {
        $password = $this->_convertToScalar($password);
        $this->getField('Password')->setValue(sha1($password));
        return $this;
    }

    /**
     * The field "Password" only contains hashed passwords.  This method sets
     * the password directly without hashing it.  Helpful for migration.
     *
     * @param string $password The new password to set.
     * @return Opus_Account Fluent interface.
     */
    public function setPasswordDirectly($password)
    {
        $logger = Zend_Registry::get('Zend_Log');
        if (null !== $logger) {
            $message = "WARNING: Setting password directly for user '" . $this->getLogin() . "'.";
            $logger->warn(__METHOD__ . ': ' . $message);
            $message = "WARNING: Setting password directly should only be used when migrating!";
            $logger->warn(__METHOD__ . ': ' . $message);
        }

        $this->getField('Password')->setValue($password);
        return $this;
    }

    /**
     * Convert array parameter into scalar.
     *
     * The FormBuilder provides an array. The setValue method can handle it, but
     * the validation and the sha1 function throw an exception.
     *
     * @param $value
     * @return scalar
     */
    protected function _convertToScalar($value)
    {
        if (true === is_array($value) and 1 === count($value)) {
            $value = array_pop($value);
        } elseif (true === is_array($value) and 0 === count($value)) {
            $value = null;
        }

        return $value;
    }

    /**
     * Check if a given string is the correct password for this account.
     *
     * @param string $password Password.
     * @return boolean
     */
    public function isPasswordCorrect($password)
    {
        return ($this->getPassword() === sha1($password));
    }

    /**
     * For migration of old password hashes to new ones: Check if a given
     * string is the correct password for this account, but to another
     * hashing algorithm.
     *
     * @param string $password Password.
     * @return boolean
     */
    public function isPasswordCorrectOldHash($password)
    {
        if ($this->getPassword() === md5($password)) {
            return true;
        }

        return false;
    }

    /**
     * Returns long name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName()
    {
        return $this->getLogin();
    }

    public function getFullName()
    {
        $name = $this->getFirstName();

        $lastName = $this->getLastName();

        if (strlen($name) > 0 and strlen($lastName) > 0) {
            $name .= ' ';
        }

        $name .= $lastName;

        return $name;
    }
}
