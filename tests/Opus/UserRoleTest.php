<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2010-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Document;
use Opus\UserRole;
use OpusTest\TestAsset\TestCase;

/**
 * Test cases for Opus\UserRole.
 *
 * @package Opus
 * @category Tests
 * @group RoleTests
 */
class UserRoleTest extends TestCase
{

    protected function setUp()
    {
        parent::setUp();

        $ur = new UserRole();
        $ur->setName('unit-test');
        $ur->store();
    }

    public function testGetAll()
    {
        $all_roles = UserRole::getAll();
        $this->assertEquals(1, count($all_roles));
    }

    public function testFetchByNameReturnsNullIfNoneExists()
    {
        $ur = UserRole::fetchByName(null);
        $this->assertNull($ur);

        $ur = UserRole::fetchByName('empty');
        $this->assertNull($ur);
    }

    public function testFetchByNameSuccessIfExists()
    {
        $ur = UserRole::fetchByName('unit-test');
        $this->assertInstanceOf('Opus\UserRole', $ur);
        $this->assertEquals('unit-test', $ur->getName());
    }

    public function testGetDisplayName()
    {
        $ur = UserRole::fetchByName('unit-test');
        $display_name = $ur->getDisplayName();
        $this->assertTrue(is_string($display_name), 'DisplayName is not a string');
        $this->assertTrue(strlen($display_name) > 0, 'DisplayName is an empty string');
    }

    public function testListAccessDocuments()
    {
        $ur = UserRole::fetchByName('unit-test');
        $list_empty = $ur->listAccessDocuments();
        $this->assertEquals(0, count($list_empty));
    }

    /**
     * @expectedException \Zend_Db_Statement_Exception
     */
    public function testAppendAccessDocumentThrowsExceptionForUnknownDokument()
    {
        $ur = UserRole::fetchByName('unit-test');

        $ur->appendAccessDocument(1)->store();
    }

    public function testAppendAccessDocumentAppendExistingIgnored()
    {
        $ur = UserRole::fetchByName('unit-test');

        $doc = new Document();
        $docId = $doc->store();

        $ur->appendAccessDocument($docId)->store();
        $list_all = $ur->listAccessDocuments();
        $this->assertEquals(1, count($list_all));
        $this->assertEquals([$docId], $list_all);

        $ur->appendAccessDocument($docId)->store();
        $list_all = $ur->listAccessDocuments();
        $this->assertEquals(1, count($list_all));
        $this->assertEquals([$docId], $list_all);
    }

    public function testAccessDocumentsInsertRemove()
    {
        $ur = UserRole::fetchByName('unit-test');

        try {
            $ur->appendAccessDocument(1)->store();
            $this->fail('Expecting exception on non-existent document.');
        } catch (\Zend_Db_Statement_Exception $e) {
        }

        $d = new Document();
        $docId = $d->store();

        $ur->appendAccessDocument($docId)->store();
        $list_all = $ur->listAccessDocuments();
        $this->assertEquals(1, count($list_all));
        $this->assertEquals([$docId], $list_all);

        $ur->removeAccessDocument($docId)->store();
        $list_empty = $ur->listAccessDocuments();
        $this->assertEquals(0, count($list_empty));

        $ur->removeAccessDocument($docId)->store();
    }

    public function testListAccessFiles()
    {
        $ur = UserRole::fetchByName('unit-test');
        $list_empty = $ur->listAccessFiles();
        $this->assertEquals(0, count($list_empty));
    }

    public function testListAccessModules()
    {
        $ur = UserRole::fetchByName('unit-test');
        $list_empty = $ur->listAccessModules();
        $this->assertEquals(0, count($list_empty));
    }

    public function testAccessModulesInsertRemove()
    {
        $ur = UserRole::fetchByName('unit-test');
        $ur->appendAccessModule('oai')->store();

        $list_all = $ur->listAccessModules();
        $this->assertEquals(1, count($list_all));
        $this->assertEquals(['oai'], $list_all);

        $ur->removeAccessModule('oai')->store();
        $list_empty = $ur->listAccessModules();
        $this->assertEquals(0, count($list_empty));

        $ur->removeAccessModule('oai')->store();
    }

    public function testGetAllAccountIdsEmpty()
    {
        $ur = UserRole::fetchByName('unit-test');
        $list_empty = $ur->getAllAccountIds();
        $this->assertEquals(0, count($list_empty));
    }

    public function testGetAllAccountNamesEmpty()
    {
        $ur = UserRole::fetchByName('unit-test');
        $list_empty = $ur->getAllAccountNames();
        $this->assertEquals(0, count($list_empty));
    }
}
