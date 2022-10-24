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

namespace OpusTest\Security;

use Opus\Common\Account;
use Opus\Common\Security\SecurityException;
use Opus\Db\Accounts;
use Opus\Db\TableGateway;
use OpusTest\TestAsset\TestCase;

use function sha1;

/**
 * Test case for Opus\Account.
 */
class AccountTest extends TestCase
{
    /**
     * Table adapter to accounts table.
     *
     * @var\Zend_Db_Table
     */
    protected $accounts;

    /**
     * Set up table adapter.
     */
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, ['accounts']);

        $this->accounts = TableGateway::getInstance(Accounts::class);
    }

    /**
     * Test if the table is initially empty.
     */
    public function testTableIsInitiallyEmpty()
    {
        $rowset = $this->accounts->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'Accounts table is not empty no test begin.');
    }

    /**
     * Test if creating a new account on a clean database works.
     *
     * @doesNotPerformAssertions
     */
    public function testCreate()
    {
        $account = Account::new();
    }

    /**
     * Test setting of login and password.
     *
     * @doesNotPerformAssertions
     */
    public function testSetCredentials()
    {
        $account = Account::new();
        $account->setLogin('bob')
            ->setPassword('secret');
    }

    /**
     * Test if the login name given when creating the object can be retrieved.
     */
    public function testLoginNameIsSetAfterCreation()
    {
        $account = Account::new();
        $account->setLogin('bob');
        $this->assertEquals('bob', $account->getLogin(), 'Login returned is not correct.');
    }

    /**
     * Test if a database record has been added after creation of an account.
     */
    public function testRecordExistsAfterCreation()
    {
        $account = Account::new();
        $account->setLogin('bob');
        $account->setPassword('testpwd');
        $account->store();
        $rowset = $this->accounts->fetchAll();
        $this->assertGreaterThan(0, $rowset->count(), 'Accounts table is still empty after creation.');
    }

    /**
     * Creating accounts with equal login name should fail with an exception.
     */
    public function testCreatingAccountsWithSameLoginThrowsException()
    {
        $account1 = Account::new();
        $account1->setLogin('bob');
        $account1->setPassword('testpwd');
        $account2 = Account::new();
        $account2->setLogin('bob');
        $account2->setPassword('testpwd2');

        $account1->store();
        $this->expectException(SecurityException::class);
        $account2->store();
    }

    /**
     * Attempt to store an account without a given login name
     * should throw an exception.
     */
    public function testCreateAndStoreWithoutLoginThrowsException()
    {
        $account = Account::new();
        $this->expectException(SecurityException::class);
        $account->store();
    }

    /**
     * Ensure that the stored password is SHA1 hashed.
     */
    public function testPasswordIsSha1Hashed()
    {
        $account = Account::new();
        $account->setLogin('bob')
            ->setPassword('secret');
        $this->assertEquals(sha1('secret'), $account->getPassword(), 'Password hash is invalid.');
    }

    /**
     * Test if a created account can be found.
     */
    public function testFindCreatedAccount()
    {
        $account1 = Account::new();
        $account1->setLogin('bob')->setPassword('bobbob')->store();
        $account2 = Account::fetchAccountByLogin('bob');
        $this->assertEquals($account1->getLogin(), $account2->getLogin(), 'Found wrong account object.');
    }

    /**
     * Test if an exception is thrown when attempt to change a login name
     * to a name that is already used by another account.
     */
    public function testChangeLoginNameToAlreadyExistingNameThrowsException()
    {
        $bob = Account::new();
        $bob->setLogin('bob')->setPassword('secret')->store();

        $dave = Account::new();
        $dave->setLogin('dave')->setPassword('secret')->store();

        $this->expectException(SecurityException::class);
        $dave->setLogin('bob')->store();
    }

    /**
     * Test if quotes in login names can only contain alphanumeric characters.
     */
    public function testNonAlphaNumericLoginsGetRejected()
    {
        $dave = Account::new();
        $this->expectException(SecurityException::class);
        $dave->setLogin('#~$??!');
    }

    /**
     * Test if an account can be retrieved by passing the login name.
     */
    public function testRetrieveAccountByLoginName()
    {
        $bob = Account::new();
        $bob->setLogin('bob')->setPassword('secret')->store();

        $result = Account::fetchAccountByLogin('bob');
        $this->assertEquals($bob->getId(), $result->getId(), 'Retrieved account does not match stored account.');
    }

    /**
     * Test if retrieving an account with a unknown login name throws exception.
     */
    public function testRetrieveAccountByWrongLoginNameThrowsException()
    {
        $bob = Account::new();
        $bob->setLogin('bob')->setPassword('secret')->store();

        $this->expectException(SecurityException::class);
        Account::fetchAccountByLogin('bobby');
    }
}
