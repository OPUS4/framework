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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * A simple authentication adapter using the Opus_Account mechanism.
 *
 * @category    Framework
 * @package     Opus_Security
 */
class Opus_Security_AuthAdapter implements Zend_Auth_Adapter_Interface
{

    /**
     * Holds the login name.
     *
     * @var string
     */
    protected $_login;

    /**
     * Holds the password.
     *
     * @var string
     */
    protected $_password;

    /**
     * Holds an actual Opus_Account implementation.
     *
     * @var Opus_Account
     */
    protected $_account = null;

    /**
     * Set the credential values for authentication.
     *
     * @param string $login    Login or account name .
     * @param string $password Account password.
     * @throws Zend_Auth_Adapter_Exception If given credentials are invalid.
     * @return Opus_Security_AuthAdapter Fluent interface.
     */
    public function setCredentials($login, $password)
    {
        if ((is_string($login) === false) or (is_string($password) === false)) {
            throw new Zend_Auth_Adapter_Exception('Credentials are not strings.');
        }
        if (empty($login) === true) {
            throw new Zend_Auth_Adapter_Exception('No login name or account name given.');
        }
        if (empty($password) === true) {
            throw new Zend_Auth_Adapter_Exception('No password given.');
        }
        $this->_login = $login;
        $this->_password = $password;
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
            $account = new Opus_Account(null, null, $this->_login);
        } catch (Exception $ex) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND,
                $this->_login,
                ['auth_error_invalid_credentials']
            );
        }

        // Check if password is correcct, but for old hashes.  Neede for
        // migrating md5-hashed passwords to SHA1-hashes.
        if ($account->isPasswordCorrectOldHash($this->_password) === true) {
            Zend_Registry::get('Zend_Log')->warn('Migrating old password-hash for user: ' . $this->_login);
            $account->setPassword($this->_password)->store();
            $account = new Opus_Account(null, null, $this->_login);
        }

        // Check the password
        $pass = $account->isPasswordCorrect($this->_password);
        if ($pass === true) {
            return new Zend_Auth_Result(
                Zend_Auth_Result::SUCCESS,
                $this->_login,
                ['auth_login_success']
            );
        }

        return new Zend_Auth_Result(
            Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID,
            $this->_login,
            ['auth_error_invalid_credentials']
        );
    }
}
