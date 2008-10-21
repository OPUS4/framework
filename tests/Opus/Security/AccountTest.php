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
 * Test case for Opus_Security_Account. 
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
     * Test if a new password is required after creating a new account.
     *
     * @return void
     */
    public function testNewPasswordRequiredAfterCreation() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        $this->assertTrue($account->isNewPasswordRequired(), 'New password should be required after creation of a new account.'); 
    }
    
    /**
     * Test if the login name given when creating the object can be retrieved.
     *
     * @return void
     */
    public function testLoginNameIsSetAfterCreation() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        $this->assertEquals('bob', $account->getLogin(), 'Login returned is not correct.');
    }
    
    
    /**
     * Test if a database record has been added after creation of an account.
     *
     * @return void
     */
    public function testRecordExistsAfterCreation() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        $rowset = $this->_accounts->fetchAll();
        $this->assertGreaterThan(0, $rowset->count(), 'Accounts table is still empty after creation.');
    }

    /**
     * Test if a database record has been added after creation of an account.
     *
     * @return void
     */
    public function testCreatingTwoAccountsWithSameLoginThrowsException() {
        $this->setExpectedException('Opus_Security_Exception');
        $account1 = Opus_Security_Account::create('bob', 'useruser');
        $account2 = Opus_Security_Account::create('bob', 'useruseruser');
    }
    
  
    /**
     * Test if a password can be validated correct after it has been set.
     *
     * @return void
     */
    public function testPasswordIsValidWhenNewPasswordWasSet() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        $account->setPassword('useruser', 'bobbob');
        $this->assertFalse($account->isNewPasswordRequired(), 'No new password should be required after creation of a new account.');
        $this->assertTrue($account->isPasswordCorrect('bobbob'), 'Password should be validated correct.');
    }
    
    
    /**
     * Test if attempt to remove an account that does not exist is an
     * idempotent operation.
     *
     * @return void
     */
    public function testRemovingIsIdempotent() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        // First call shall remove the account. 
        $account->remove('bob');
        // Second call shall not fail.
        $account->remove('bob');
    }
    
    /**
     * Test if a created account can be found. 
     *
     * @return void
     */
    public function testFindCreatedAccount() {
        $account1 = Opus_Security_Account::create('bob', 'useruser');
        $account2 = Opus_Security_Account::create('peter', 'useruser');
        
        $find1 = Opus_Security_Account::find('bob');
        $find2 = Opus_Security_Account::find('peter');
        
        $this->assertEquals($account1->getLogin(), $find1->getLogin(), 'Find returned wrong account object.');
        $this->assertEquals($account2->getLogin(), $find2->getLogin(), 'Find returned wrong account object.');
    }
    
    /**
     * Test if an account is not found any longer after removing it.
     *
     * @return void
     */
    public function testAccountIsNotFoundAfterRemove() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        Opus_Security_Account::remove('bob');
        $found = Opus_Security_Account::find('bob');
        $this->assertNull($found, 'Removing failed.');
    }
    
    /**
     * Test if password change operation causes a change of the database record.
     *
     * @return void
     */
    public function testChangingPasswordIsReflectedInTheDatabase() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        $account->setPassword('useruser','bobbob');
        
        $account2 = Opus_Security_Account::find('bob');
        $this->assertTrue($account2->isPasswordCorrect('bobbob'), 'Password has not been changed.');
    }

    /**
     * Test if login name change operation causes a change of the database record.
     *
     * @return void
     */
    public function testChangingLoginIsReflectedInTheDatabase() {
        $account = Opus_Security_Account::create('bob', 'useruser');
        $account->setLogin('useruser','bobbob');
        
        $account2 = Opus_Security_Account::find('bobbob');
        $this->assertNotNull($account2, 'Account cannot be found anymore after changing login name.');
    }
    
    /**
     * Test if attempt to alter the password but not providing the current password
     * fails with exception.
     *
     * @return void
     */
    public function testAlterPasswordWithWrongCredentialsThrowsException() {
        $this->setExpectedException('Opus_Security_Exception');
        $account = Opus_Security_Account::create('bob', 'useruser');
        $account->setPassword('xxWRONGxx','bobbob');
    }

    /**
     * Test if attempt to alter the login name but not providing the current password
     * fails with exception.
     *
     * @return void
     */
    public function testAlterLoginWithWrongCredentialsThrowsException() {
        $this->setExpectedException('Opus_Security_Exception');
        $account = Opus_Security_Account::create('bob', 'useruser');
        $account->setLogin('xxWRONGxx','bobbob');
    }
    

}