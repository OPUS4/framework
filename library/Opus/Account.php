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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Exception;
use Opus\Common\AccountInterface;
use Opus\Common\AccountRepositoryInterface;
use Opus\Common\Log;
use Opus\Common\Model\ModelException;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Opus\Security\SecurityException;
use Zend_Db_Table_Abstract;
use Zend_Db_Table_Row;
use Zend_Validate;
use Zend_Validate_EmailAddress;
use Zend_Validate_Regex;

use function array_pop;
use function count;
use function is_array;
use function is_string;
use function md5;
use function sha1;
use function strlen;

/**
 * Domain model for accounts in the Opus framework
 */
class Account extends AbstractDb implements AccountInterface, AccountRepositoryInterface
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\Accounts::class;

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus\Db\Account table gateway.
     *
     * @var array
     */
    protected $externalFields = [
        'Role' => [
            'model'   => UserRole::class,
            'through' => Model\Dependent\Link\AccountRole::class,
            'fetch'   => 'lazy',
        ],
    ];

    /**
     * Retrieve all Opus\Account instances from the database.
     *
     * @return array Array of Opus\Account objects.
     */
    public function getAll()
    {
        return self::getAllFrom(self::class, Db\Accounts::class);
    }

    /**
     * Override to allow retrieving an account either by id or by the unique login name.
     * If neither id nor login are specified a new persistant instance gets created which
     * got idts id set as soon as it is stored via a call to _store().
     *
     * @param null|int|Zend_Db_Table_Row  $id (Optional) (Id of) Existing database row.
     * @param null|Zend_Db_Table_Abstract $tableGatewayModel (Optional) Opus\Db model to fetch table row from.
     * @param null|string                 $login (Optional) Login of existing record.
     * @throws ModelException     Thrown if passed id is invalid or login and id are specified.
     */
    public function __construct($id = null, ?Zend_Db_Table_Abstract $tableGatewayModel = null, $login = null)
    {
        if ($login !== null && false === empty($login)) {
            if ($id !== null && false === empty($id)) {
                 throw new ModelException('Login and id of an account are specified, specify either id or login.');
            }
            $id = self::fetchAccountRowByLogin($login);
            if (! isset($id)) {
                throw new SecurityException('An account with the login name ' . $login . ' cannot be found.');
            }
        }
        parent::__construct($id, $tableGatewayModel);
    }

    /**
     * Initialize model with the following fields:
     * - Username
     * - Password
     */
    protected function init()
    {
        $login          = new Field('Login');
        $loginValidator = new Zend_Validate();

        // NOTE: Validation is also defined in Application_Form_Element_Login
        $loginValidator->addValidator(new Zend_Validate_Regex('/^[A-Za-z0-9@._-]+$/'));
        $login->setValidator($loginValidator)->setMandatory(true);

        $password = new Field('Password');
        $password->setMandatory(true);

        $email          = new Field('Email');
        $emailValidator = new Zend_Validate();
        $emailValidator->addValidator(new Zend_Validate_EmailAddress());
        $email->setMandatory(true);

        $firstName = new Field('FirstName');
        $lastName  = new Field('LastName');

        $role = new Field('Role');
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
     * @throws SecurityException If storing failes.
     * @return mixed
     */
    public function store()
    {
        // Check for a proper credentials
        if ($this->isValid() === false) {
            throw new SecurityException('Credentials are invalid.');
        }

        // Check if there is a account with the same
        // loginname before creating a new record.
        if ($this->getId() === null) {
            $row = self::fetchAccountRowByLogin($this->getLogin());
            if ($row !== null) {
                throw new SecurityException('Account with login name ' . $this->getLogin() . ' already exists.');
            }
        }
        // Now really store.
        try {
            return parent::store();
        } catch (Exception $ex) {
            $logger = Log::get();
            if (null !== $logger) {
                $message  = "Unknown exception while storing account: ";
                $message .= $ex->getMessage();
                $logger->err(__METHOD__ . ': ' . $message);
            }

            $message = "Caught exception.  Please consult the server logfile.";
            throw new SecurityException($message);
        }
    }

    /**
     * Helper method to fetch account-rows by login name.
     *
     * @param string $login
     * @return Zend_Db_Table_Row|null
     */
    private static function fetchAccountRowByLogin($login)
    {
        if (false === isset($login) || false === is_string($login)) {
            return null;
        }

        $accounts = TableGateway::getInstance(self::$tableGatewayClass);
        $select   = $accounts->select()->where('login = ?', $login);
        return $accounts->fetchRow($select);
    }

    /**
     * Alternate constructor to fetch account-objects by login name.
     *
     * @param string $login
     * @return AccountInterface
     */
    public function fetchAccountByLogin($login)
    {
        $row = self::fetchAccountRowByLogin($login);

        if ($row !== null) {
            return new self($row);
        } else {
            throw new SecurityException("Account with login name '$login' not found.");
        }
    }

    /**
     * Validate the login before accepting the value.
     *
     * @param string $login Login name.
     * @throws SecurityException Thrown if the login name is not valid.
     * @return $this Fluent interface.
     */
    public function setLogin($login)
    {
        $login      = $this->convertToScalar($login);
        $loginField = $this->getField('Login');
        if ($loginField->getValidator()->isValid($login) === false) {
            Log::get()->debug('Login not valid: ' . $login);
            throw new SecurityException('Login name is empty or contains invalid characters.');
        }
        $loginField->setValue($login);
        return $this;
    }

    /**
     * Set a new password.  The password goes through the PHP sha1 hash
     * algorithm.
     *
     * @param string $password The new password to set.
     * @return $this Fluent interface.
     */
    public function setPassword($password)
    {
        $password = $this->convertToScalar($password);
        $this->getField('Password')->setValue(sha1($password));
        return $this;
    }

    /**
     * The field "Password" only contains hashed passwords.  This method sets
     * the password directly without hashing it.  Helpful for migration.
     *
     * @param string $password The new password to set.
     * @return $this Fluent interface.
     */
    public function setPasswordDirectly($password)
    {
        $logger = Log::get();
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
     * @param string|array $value
     * @return mixed
     */
    protected function convertToScalar($value)
    {
        if (true === is_array($value) && 1 === count($value)) {
            $value = array_pop($value);
        } elseif (true === is_array($value) && 0 === count($value)) {
            $value = null;
        }

        return $value;
    }

    /**
     * Check if a given string is the correct password for this account.
     *
     * @param string $password Password.
     * @return bool
     */
    public function isPasswordCorrect($password)
    {
        return $this->getPassword() === sha1($password);
    }

    /**
     * For migration of old password hashes to new ones: Check if a given
     * string is the correct password for this account, but to another
     * hashing algorithm.
     *
     * @param string $password Password.
     * @return bool
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
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getLogin();
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        $name = $this->getFirstName();

        $lastName = $this->getLastName();

        if (strlen($name) > 0 && strlen($lastName) > 0) {
            $name .= ' ';
        }

        $name .= $lastName;

        return $name;
    }
}
