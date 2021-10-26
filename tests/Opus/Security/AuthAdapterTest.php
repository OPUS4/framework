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
 * @category    Tests
 * @package     Opus\Security
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Security;

use Opus\Account;
use Opus\Security\AuthAdapter;
use OpusTest\TestAsset\TestCase;

/**
 * Test case for Opus\Security\AuthAdapter class.
 *
 * @category    Tests
 * @package     Opus\Security
 *
 * @group       AuthAdapterTest
 */
class AuthAdapterTest extends TestCase
{

    /**
     * Holds the authentication adapter instance.
     *
     * @var AuthAdapter
     */
    protected $_auth_adapter = null;


    /**
     * Returns an array with invalid credentials.
     *
     * @return Array Invalid credentials and\Zend_Auth_Result error code.
     */
    public function invalidCredentialsDataProvider()
    {
        return [
            ['bob', 'wrong_password',\Zend_Auth_Result::FAILURE_CREDENTIAL_INVALID],
            ['bobby', 'secret',\Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND],
            ['bobby', 'wrong',\Zend_Auth_Result::FAILURE_IDENTITY_NOT_FOUND]
        ];
    }

    /**
     * Set up test account.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, ['accounts']);

        $bob = new Account();
        $bob->setLogin('bob')->setPassword('secret')->store();

        $this->_auth_adapter = new AuthAdapter();
    }


    /**
     * Test if a successful authentication can be performed.
     *
     * @return void
     */
    public function testSettingEmptyCredentialsThrowsException()
    {
        $this->setExpectedException('Zend_Auth_Adapter_Exception');
        $this->_auth_adapter->setCredentials('', null);
    }

    /**
     * Test if a successful authentication can be performed.
     *
     * @return void
     */
    public function testSuccessfulAuthentication()
    {
        $this->_auth_adapter->setCredentials('bob', 'secret');
        $result = $this->_auth_adapter->authenticate();
        $this->assertNotNull($result, 'Authentication result should not be null.');
        $this->assertInstanceOf('Zend_Auth_Result', $result, 'Authentication result should be of type\Zend_Auth_Result.');
        $this->assertEquals($result->getCode(), \Zend_Auth_Result::SUCCESS, 'Authentication should be successful.');
    }

    /**
     * Test if given invalid credentials failes.
     *
     * @param string  $login    Login credentials.
     * @param string  $password Password credentials.
     * @param integer $code     Expected\Zend_Auth_Result code.
     * @return void
     *
     * @dataProvider invalidCredentialsDataProvider
     */
    public function testFailingAuthentication($login, $password, $code)
    {
        $this->_auth_adapter->setCredentials($login, $password);
        $result = $this->_auth_adapter->authenticate();
        $this->assertNotNull($result, 'Authentication result should not be null.');
        $this->assertInstanceOf('Zend_Auth_Result', $result, 'Authentication result should be of type\Zend_Auth_Result.');
        $this->assertEquals($result->getCode(), $code, 'Authentication should not be successful.');
    }
}
