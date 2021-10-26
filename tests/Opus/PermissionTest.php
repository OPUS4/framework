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
 * @category    Tests
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Account;
use Opus\Permission;
use Opus\UserRole;
use OpusTest\TestAsset\TestCase;

class PermissionTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, [
            'user_roles',
            'accounts',
            'access_modules',
            'link_accounts_roles'
        ]);

        $this->setUpDatabase();
    }

    protected function setUpDatabase()
    {
        // TODO setup accounts, roles, permissions
        $role = new UserRole();
        $role->setName('DOI');
        $role->appendAccessModule('admin');
        $role->appendAccessModule('resource_doi_notification');
        $role->store();

        $account = new Account();
        $account->setLastName('Doe');
        $account->addRole($role);
        $account->setLogin('john');
        $account->setPassword('blabla');
        $account->store();

        $role = new UserRole();
        $role->setName('Manager');
        $role->appendAccessModule('sword');
        $role->appendAccessModule('resource_doi_notification');
        $role->store();

        $account = new Account();
        $account->setLastName('Muster');
        $account->addRole($role);
        $account->setLogin('jane');
        $account->setPassword('fubar');
        $account->store();

        $role = new UserRole();
        $role->setName('LicenceManager');
        $role->appendAccessModule('licences');
        $role->store();

        $account = new Account();
        $account->setLastName('Schmidt');
        $account->addRole($role);
        $account->setLogin('jeff');
        $account->setPassword('123456');
        $account->store();
    }

    public function testGetAccounts()
    {
        $accounts = Permission::getAccounts('admin');

        $this->assertCount(1, $accounts);

        $account = $accounts[0];

        $this->assertInstanceOf('Opus\Account', $account);
        $this->assertEquals('john', $account->getLogin());
    }

    public function testGetAccountsUnknownPermission()
    {
        $accounts = Permission::getAccounts('unknown');

        $this->assertCount(0, $accounts);
    }

    public function testGetAccountsThroughTwoRoles()
    {
        $accounts = Permission::getAccounts('resource_doi_notification');

        $this->assertCount(2, $accounts);

        $expectedAccounts = ['john' => 'john', 'jane' => 'jane'];

        foreach ($accounts as $account) {
            $this->assertInstanceOf('Opus\Account', $account);
            $login = $account->getLogin();
            $this->assertContains($login, $expectedAccounts);
            unset($expectedAccounts[$login]); // every account just once
        }
    }

    public function testGetAccountsWithNull()
    {
        $accounts = Permission::getAccounts(null);

        $this->assertCount(0, $accounts);
    }
}
