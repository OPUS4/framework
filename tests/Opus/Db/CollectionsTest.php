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
 * @copyright   Copyright (c) 2024, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Db;

use Opus\Common\Collection;
use Opus\Common\CollectionRole;
use Opus\Db\Collections;
use OpusTest\TestAsset\TestCase;

class CollectionsTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, [
            'collections',
            'collections_roles',
        ]);
    }

    public function testIsVisible()
    {
        $role = CollectionRole::new();
        $role->setName('role-name');
        $role->setOaiName('role-oai-name');
        $root = $role->addRootCollection();

        $col1 = $root->addLastChild();
        $col1->setName('col1');

        $col2 = $col1->addLastChild();
        $col2->setName('col2');

        $role->store();

        $collections = new Collections();

        $this->assertFalse($root->getVisible());

        // Reload collections to update all fields from database
        $root = Collection::get($root->getId());
        $col1 = Collection::get($col1->getId());
        $col2 = Collection::get($col2->getId());

        $this->assertTrue($root->getVisible());
        $this->assertTrue($col1->getVisible());
        $this->assertTrue($col2->getVisible());
        $this->assertTrue($collections->isVisible($col2->getId()));

        $root->setVisible(false);
        $root->store();
        $col1->setVisible(false);
        $col1->store();
        $col2->setVisible(false);
        $col2->store();

        $this->assertFalse($collections->isVisible($col2->getId()));

        $root->setVisible(false);
        $root->store();
        $col1->setVisible(true);
        $col1->store();
        $col2->setVisible(true);
        $col2->store();

        $this->assertFalse($collections->isVisible($col2->getId()));

        $root->setVisible(true);
        $root->store();
        $col1->setVisible(false);
        $col1->store();
        $col2->setVisible(true);
        $col2->store();

        $this->assertFalse($collections->isVisible($col2->getId()));

        $root->setVisible(true);
        $root->store();
        $col1->setVisible(true);
        $col1->store();
        $col2->setVisible(true);
        $col2->store();

        $col3 = $root->addLastChild();
        $col3->setVisible(false);
        $root->store();

        $this->assertTrue($collections->isVisible($col2->getId()));
    }
}
