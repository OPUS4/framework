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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Security;

use Opus\Common\Account;
use Opus\Common\AccountInterface;
use Opus\Common\Log;
use Opus\Common\Security\SecurityException;
use Zend_Auth_Adapter_Exception;
use Zend_Auth_Adapter_Interface;
use Zend_Auth_Result;

use function is_string;

/**
 * A simple authentication adapter using the Opus\Account mechanism.
 */
class AuthAdapter implements Zend_Auth_Adapter_Interface
{
    /**
     * Holds the login name.
     *
     * @var string
     */
    protected $login;

    /**
     * Holds the password.
     *
     * @var string
     */
    protected $password;

    /**
     * Holds an actual Opus\Account implementation.
     *
     * @var AccountInterface
     */
    protected $account;

    /**
     * Set the credential values for authentication.
     *
     * @param string $login    Login or account name .
     * @param string $password Account password.
     * @throws Zend_Auth_Adapter_Exception If given credentials are invalid.
     * @return $this Fluent interface.
     */
    public function setCredentials($login, $password)
    {
        if ((is_string($login) === false) || (is_string($password) === false)) {
            throw new Zend_Auth_Adapter_Exception('Credentials are not strings.');
        }
        if (empty($login) === true) {
            throw new Zend_Auth_Adapter_Exception('No login name or account name given.');
        }
        if (empty($password) === true) {
            throw new Zend_Auth_Adapter_Exception('No password given.');
        }
        $this->login    = $login;
        $this->password = $password;
        return $this;
    }

    /**
     * Performs an authentication attempt
     *
     * @throws Zend_Auth_Adapter_Exception If authentication cannot be performed.
     * @return Zend_Auth_Result
     */
    public function authenticate()
    {
        // Try to get the account information
        try {
            $account = Account::fetchAccountByLogin($this->login);
        } catch (SecurityException $ex) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                $this->login,
                ['auth_error_invalid_credentials']
            );
        }

        // Check if password is correcct, but for old hashes.  Neede for
        // migrating md5-hashed passwords to SHA1-hashes.
        if ($account->isPasswordCorrectOldHash($this->password) === true) {
            Log::get()->warn('Migrating old password-hash for user: ' . $this->login);
            $account->setPassword($this->password)->store();
            $account = Account::fetchAccountByLogin($this->login);
        }

        // Check the password
        $pass = $account->isPasswordCorrect($this->password);
        if ($pass === true) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::SUCCESS,
                $this->login,
                ['auth_login_success']
            );
        }

        return new Zend_Auth_Result(
            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
            $this->login,
            ['auth_error_invalid_credentials']
        );
    }
}
