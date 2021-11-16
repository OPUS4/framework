<?php

/*
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
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Db2\Database;
use Opus\Model\DbException;
use Opus\Model2\Account;
use Opus\Model2\UserRole;
use OpusTest\TestAsset\TestCase;

use function count;

/**
 * Unit tests for Opus\Account operations.
 */
class AccountTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(true, ['accounts', 'user_roles', 'link_accounts_roles']);

        $account = new Account();
        $account->setLogin('dummy');
        $account->setPassword('dummypassword');
        $account->store();
    }

    /**
     * Test creating a new account.
     */
    public function testCreateAccount()
    {
        $account = new Account();
        $account->setLogin('dummy2');
        $account->setPassword('dummypassword');
        $account->store();

        $account = Account::fetchByLogin('dummy2');

        $this->assertNotNull($account);
        $this->assertEquals('dummy2', $account->getLogin());
    }

    /**
     * Test double-create account.
     */
    public function testDoubleCreateAccount()
    {
        $account = new Account();
        $account->setLogin('dummy3');
        $account->setPassword('dummypassword');
        $account->store();

        $account = new Account();
        $account->setLogin('dummy3');
        $account->setPassword('dummypassword');

        $this->setExpectedException(DbException::class);
        $account->store();
    }

    /**
     * @depends testCreateAccount
     */
    public function testDeleteAccount()
    {
        $accountRepository = Database::getEntityManager()->getRepository(Account::class);
        $account           = $accountRepository->findOneBy(['login' => 'dummy']);

        $this->assertNotNull($account);

        $account->delete();

        $account = Account::fetchByLogin('dummy');

        $this->assertNull($account);
    }

    // TODO: reimplement testAddRoleToAccount() & testSetRoleOfAccount() when UserRole has been converted to ORM
//    /**
//     * Test adding a role to an account.
//     */
//    public function testAddRoleToAccount()
//    {
//        $account = new Account(null, null, 'dummy');
//
//        $role = new UserRole();
//        $role->setName('role1');
//        $role->store();
//
//        $account->addRole($role);
//        $account->store();
//
//        $account = new Account(null, null, 'dummy');
//
//        $roles = $account->getRole();
//
//        $this->assertNotNull($roles);
//        $this->assertEquals('role1', $roles[0]->getName());
//    }
//
//    /**
//     * Test setting the roles of an account.
//     */
//    public function testSetRoleOfAccount()
//    {
//        $account = new Account(null, null, 'dummy');
//
//        $role = new UserRole();
//        $role->setName('role1');
//        $role->store();
//
//        $roles = [$role];
//
//        $account->setRole($roles);
//        $account->store();
//
//        $account = new Account(null, null, 'dummy');
//
//        $roles = $account->getRole();
//
//        $this->assertNotNull($roles);
//        $this->assertEquals('role1', $roles[0]->getName());
//    }

    public function testPasswordIsCorrect()
    {
        $account = new Account();
        $account->setLogin('dummy4');
        $account->setPassword('dummypassword');
        $account->store();

        $isPasswordCorrect = $account->isPasswordCorrect('dummypassword');

        $this->assertTrue($isPasswordCorrect, 'Password is not "dummypassword"');
    }

    public function testGetAll()
    {
        $allAccounts = Account::getAll();

        $this->assertEquals(1, count($allAccounts));
    }

    public function testGetFullName()
    {
        $account = new Account();
        $account->setFirstName('John');
        $account->setLastName('Doe');

        $this->assertEquals('John Doe', $account->getFullName());
    }

    public function testGetFullNameWithoutFirstName()
    {
        $account = new Account();
        $account->setLastName('Doe');

        $this->assertEquals('Doe', $account->getFullName());
    }

    public function testGetFullNameWithoutLastName()
    {
        $account = new Account();
        $account->setFirstName('John');

        $this->assertEquals('John', $account->getFullName());
    }

    public function testGetFullNameEmpty()
    {
        $account = new Account();

        $this->assertNotNull($account->getFullName());
        $this->assertEquals('', $account->getFullName());
    }

    public function testAddUserRole()
    {
        $userRole = new UserRole();
        $userRole->setName('unit-test');
        $userRole->store();

        $account = new Account();
        $account->setLogin('dummy5');
        $account->setPassword('dummypassword');
        $account->addUserRole($userRole);
        $account->store();

        $accountRepository = Database::getEntityManager()->getRepository(Account::class);
        $account           = $accountRepository->findOneBy(['login' => 'dummy5']);
        $userRoles         = $account->getUserRoles();

        $this->assertEquals(1, count($userRoles));

        $userRole = $userRoles->first();

        $this->assertInstanceOf(UserRole::class, $userRole);
        $this->assertEquals('unit-test', $userRole->getName());
    }

    public function testRemoveUserRole()
    {
        $userRole = new UserRole();
        $userRole->setName('unit-test');
        $userRole->store();

        $userRole2 = new UserRole();
        $userRole2->setName('unit-test2');
        $userRole2->store();

        $account = new Account();
        $account->setLogin('dummy6');
        $account->setPassword('dummypassword');
        $account->addUserRole($userRole);
        $account->addUserRole($userRole2);
        $account->store();

        $accountRepository = Database::getEntityManager()->getRepository(Account::class);
        $account           = $accountRepository->findOneBy(['login' => 'dummy6']);
        $userRoles         = $account->getUserRoles();

        $this->assertEquals(2, count($userRoles));

        $account->removeUserRole($userRole);
        $account->store();

        $accountRepository = Database::getEntityManager()->getRepository(Account::class);
        $account           = $accountRepository->findOneBy(['login' => 'dummy6']);
        $userRoles         = $account->getUserRoles();

        $this->assertEquals(1, count($userRoles));

        $userRole2 = $userRoles->first();

        $this->assertEquals('unit-test2', $userRole2->getName());
    }
}
