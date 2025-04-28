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
 * @copyright   Copyright (c) 2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Collection;
use Opus\Common\CollectionRole;
use Opus\Common\Document;
use OpusTest\TestAsset\TestCase;

use function count;

class CollectionRepositoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(true, ['collections_roles', 'collections']);
    }

    public function testFind()
    {
        $role = CollectionRole::new();
        $role->setName('testRole');
        $role->setOaiName('oaiTestRole');
        $root = $role->addRootCollection();

        $children   = [];
        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag');
        $children[count($children) - 1]->setNumber('test');

        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 2');
        $children[count($children) - 1]->setNumber('test2');

        $role->store();

        $result = Collection::find('eintrag');

        $this->assertCount(2, $result);

        $result = Collection::find('eintrag 2');

        $this->assertCount(1, $result);

        $col1 = $result[0];

        $this->assertCount(4, $col1);
        $this->assertArrayHasKey('Id', $col1);
        $this->assertArrayHasKey('RoleId', $col1);
        $this->assertArrayHasKey('Name', $col1);
        $this->assertArrayHasKey('Number', $col1);
    }

    public function testFindInRoles()
    {
        $role1 = CollectionRole::new();
        $role1->setName('TestRole1');
        $role1->setOaiName('TestRole1Oai');

        $col1 = $role1->addRootCollection();
        $col1->setName('TestCol1');

        $role1->store();

        $role2 = CollectionRole::new();
        $role2->setName('TestRole2');
        $role2->setOaiName('TestRole2Oai');

        $col2 = $role2->addRootCollection();
        $col2->setName('TestCol2');

        $role2->store();

        $this->assertCount(2, Collection::find('TestCol'));
        $this->assertCount(1, Collection::find('TestCol', $role1->getId()));
        $this->assertCount(2, Collection::find('TestCol', [$role1->getId(), $role2->getId()]));
    }

    public function testFetchCollectionIdsByDocumentId()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('OaiTestRole');
        $root = $role->addRootCollection();

        $col1 = Collection::new();
        $col1->setName('Col1');
        $root->addLastChild($col1);

        $col2 = Collection::new();
        $col2->setName('Col2');
        $root->addLastChild($col2);

        $role->store();

        $doc = Document::new();
        $doc->addCollection($col1);
        $doc->addCollection($col2);
        $doc->store();

        $repository = Collection::getModelRepository();

        $colIds = $repository->fetchCollectionIdsByDocumentId($doc->getId());

        $this->assertCount(2, $colIds);
        $this->assertContains($col1->getId(), $colIds);
        $this->assertContains($col2->getId(), $colIds);
    }
}
