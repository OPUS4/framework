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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Doi;

use Opus\Common\Account;
use Opus\Doi\UserRecipientProvider;
use Opus\UserRole;
use OpusTest\TestAsset\TestCase;

class UserRecipientProviderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, [
            'accounts',
            'user_roles',
            'access_modules',
            'link_accounts_roles',
        ]);
    }

    public function testGetRecipients()
    {
        $role = new UserRole();
        $role->setName('DOI');
        $role->appendAccessModule('resource_doi_notification');
        $role->store();

        $account = Account::new();
        $account->addRole($role);
        $account->setFirstName('John');
        $account->setLastName('Doe');
        $account->setEmail('john@localhost');
        $account->setLogin('john');
        $account->setPassword('123456');
        $account->store();

        // Account without name
        $account = Account::new();
        $account->addRole($role);
        $account->setEmail('jane@localhost');
        $account->setLogin('jane');
        $account->setPassword('123456');
        $account->store();

        // Account without permission
        $account = Account::new();
        $account->setLogin('tom');
        $account->setPassword('123456');
        $account->store();

        // Account without email will not be included
        $account = Account::new();
        $account->addRole($role);
        $account->setFirstName('Paul');
        $account->setLastName('Miller');
        $account->setLogin('paul');
        $account->setPassword('123456');
        $account->store();

        $provider = new UserRecipientProvider();

        $recipients = $provider->getRecipients();

        $this->assertCount(2, $recipients);

        $this->assertEquals([
            ['name' => 'John Doe', 'address' => 'john@localhost'],
            ['name' => 'jane', 'address' => 'jane@localhost'],
        ], $recipients);
    }

    public function testGetRecipientsFilterAccountsWithoutEmail()
    {
        $role = new UserRole();
        $role->setName('DOI');
        $role->appendAccessModule('doi_notification');
        $role->store();

        $account = Account::new();
        $account->addRole($role);
        $account->setFirstName('Paul');
        $account->setLastName('Miller');
        $account->setLogin('paul');
        $account->setPassword('123456');
        $account->store();

        $provider = new UserRecipientProvider();

        $recipients = $provider->getRecipients();

        $this->assertInternalType('array', $recipients);
        $this->assertCount(0, $recipients);
    }

    public function testGetRecipientsNone()
    {
        $provider = new UserRecipientProvider();

        $recipients = $provider->getRecipients();

        $this->assertInternalType('array', $recipients);
        $this->assertCount(0, $recipients);
    }
}
