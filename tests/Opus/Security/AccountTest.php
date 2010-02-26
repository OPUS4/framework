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
 * @package     Opus_Security
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test case for Opus_Account.
 *
 * @category    Tests
 * @package     Opus_Security
 *
 * @group       AccountTest
 */
class Opus_Security_AccountTest extends PHPUnit_Framework_TestCase {

    /**
     * Table adapter to accounts table.
     *
     * @var Zend_Db_Table
     */
    protected $_accounts = null;

    /**
     * Set up table adapter.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('accounts');
        $this->_accounts = new Opus_Db_Accounts();
    }

    /**
     * Test if the table is initially empty.
     *
     * @return void
     */
    public function testTableIsInitiallyEmpty() {
        $rowset = $this->_accounts->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'Accounts table is not empty no test begin.');
    }


    /**
     * Test if creating a new account on a clean database works.
     *
     * @return void
     */
    public function testCreate() {
        $account = new Opus_Account;
    }

    /**
     * Test setting of login and password.
     *
     * @return void
     */
    public function testSetCredentials() {
        $account = new Opus_Account;
        $account->setLogin('bob')
            ->setPassword('secret');
    }


    /**
     * Test if the login name given when creating the object can be retrieved.
     *
     * @return void
     */
    public function testLoginNameIsSetAfterCreation() {
        $account = new Opus_Account;
        $account->setLogin('bob');
        $this->assertEquals('bob', $account->getLogin(), 'Login returned is not correct.');
    }


    /**
     * Test if a database record has been added after creation of an account.
     *
     * @return void
     */
    public function testRecordExistsAfterCreation() {
        $account = new Opus_Account;
        $account->setLogin('bob');
        $account->store();
        $rowset = $this->_accounts->fetchAll();
        $this->assertGreaterThan(0, $rowset->count(), 'Accounts table is still empty after creation.');
    }

    /**
     * Creating accounts with equal login name should fail with an exception.
     *
     * @return void
     */
    public function testCreatingAccountsWithSameLoginThrowsException() {
        $account1 = new Opus_Account;
        $account1->setLogin('bob');
        $account2 = new Opus_Account;
        $account2->setLogin('bob');

        $account1->store();
        $this->setExpectedException('Opus_Security_Exception');
        $account2->store();
    }

    /**
      * Attempt to store an account without a given login name
      * should throw an exception.
      *
      * @return void
      */
     public function testCreateAndStoreWithoutLoginThrowsException() {
         $account = new Opus_Account;
         $this->setExpectedException('Opus_Security_Exception');
         $account->store();
     }

    /**
     * Ensure that the stored password is SHA1 hashed.
     *
     * @return void
     */
    public function testPasswordIsSha1Hashed() {
        $account = new Opus_Account;
        $account->setLogin('bob')
            ->setPassword('secret');
        $this->assertEquals(sha1('secret'), $account->getPassword(), 'Password hash is invalid.');
    }

    /**
     * Test if a created account can be found.
     *
     * @return void
     */
    public function testFindCreatedAccount() {
        $account1 = new Opus_Account;
        $account1->setLogin('bob')->setPassword('bobbob')->store();
        $account2 = new Opus_Account('bob');
        $this->assertEquals($account1->getLogin(), $account2->getLogin(), 'Found wrong account object.');
    }


    /**
     * Test if an exception is thrown when attempt to change a login name
     * to a name that is already used by another account.
     *
     * @return void
     */
    public function testChangeLoginNameToAlreadyExistingNameThrowsException() {
        $bob = new Opus_Account;
        $bob->setLogin('bob')->setPassword('secret')->store();

        $dave = new Opus_Account;
        $dave->setLogin('dave')->setPassword('secret')->store();

        $this->setExpectedException('Opus_Security_Exception');
        $dave->setLogin('bob')->store();
    }

    /**
     * Test if quotes in login names can only contain alphanumeric characters.
     *
     * @return void
     */
    public function testNonAlphaNumericLoginsGetRejected() {
        $dave = new Opus_Account;
        $this->setExpectedException('Opus_Security_Exception');
        $dave->setLogin('#~$??!');
    }

    /**
     * Test if an account can be retrieved by passing the login name.
     *
     * @return void
     */
    public function testRetrieveAccountByLoginName() {
        $bob = new Opus_Account;
        $bob->setLogin('bob')->setPassword('secret')->store();

        $result = new Opus_Account('bob');
        $this->assertEquals($bob->getId(), $result->getId(), 'Retrieved account does not match stored account.');
    }

    /**
     * Test if retrieving an account with a unknown login name throws exception.
     *
     * @return void
     */
    public function testRetrieveAccountByWrongLoginNameThrowsException() {
        $bob = new Opus_Account;
        $bob->setLogin('bob')->setPassword('secret')->store();

        $this->setExpectedException('Opus_Security_Exception');
        $result = new Opus_Account('bobby');
    }

}
