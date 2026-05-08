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
 * @copyright   Copyright (c) 2026, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\UserRole;
use Opus\SecurityStorage;
use OpusTest\TestAsset\TestCase;

/**
 * TODO What would be meaningful and useful tests for this class?
 */
class SecurityStorageTest extends TestCase
{
    private int $roleId1;

    private int $roleId2;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, ['user_roles', 'access_modules']);

        $role = UserRole::new();
        $role->setName('testrole1');
        $role->appendAccessModule('resources_languages');
        $role->appendAccessModule('resources_collections');
        $role->appendAccessModule('admin');
        $this->roleId1 = $role->store();

        $role = UserRole::new();
        $role->setName('testrole2');
        $role->appendAccessModule('resources_languages');
        $this->roleId2 = $role->store();
    }

    public function testRemoveResource()
    {
        $role1 = UserRole::get($this->roleId1);
        $role2 = UserRole::get($this->roleId2);

        $resources = $role1->listAccessModules();
        $this->assertCount(3, $resources);
        $this->assertEqualsCanonicalizing([
            'resources_languages',
            'resources_collections',
            'admin',
        ], $resources);

        $this->assertEqualsCanonicalizing([
            'resources_languages',
        ], $role2->listAccessModules());

        $security = new SecurityStorage();
        $security->removeResource('resources_languages');

        $resources = $role1->listAccessModules();
        $this->assertCount(2, $resources);
        $this->assertEqualsCanonicalizing([
            'resources_collections',
            'admin',
        ], $resources);

        $this->assertEqualsCanonicalizing([], $role2->listAccessModules());
    }
}
