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
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\UserRole;
use Opus\Common\UserRoleInterface;
use Opus\Document;
use OpusTest\TestAsset\TestCase;
use Zend_Db_Statement_Exception;

use function count;
use function is_string;
use function strlen;

/**
 * Test cases for Opus\UserRole.
 */
class UserRoleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, ['user_roles', 'documents', 'access_documents']);

        $ur = UserRole::new();
        $ur->setName('unit-test');
        $ur->store();
    }

    public function testGetAll()
    {
        $allRoles = UserRole::getAll();
        $this->assertEquals(1, count($allRoles));
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
        $this->assertInstanceOf(UserRoleInterface::class, $ur);
        $this->assertEquals('unit-test', $ur->getName());
    }

    public function testGetDisplayName()
    {
        $ur          = UserRole::fetchByName('unit-test');
        $displayName = $ur->getDisplayName();
        $this->assertTrue(is_string($displayName), 'DisplayName is not a string');
        $this->assertTrue(strlen($displayName) > 0, 'DisplayName is an empty string');
    }

    public function testListAccessDocuments()
    {
        $ur        = UserRole::fetchByName('unit-test');
        $listEmpty = $ur->listAccessDocuments();
        $this->assertEquals(0, count($listEmpty));
    }

    public function testAppendAccessDocumentThrowsExceptionForUnknownDokument()
    {
        $ur = UserRole::fetchByName('unit-test');

        $this->expectException(Zend_Db_Statement_Exception::class);

        $ur->appendAccessDocument(1)->store();
    }

    public function testAppendAccessDocumentAppendExistingIgnored()
    {
        $ur = UserRole::fetchByName('unit-test');

        $doc   = Document::new();
        $docId = $doc->store();

        $ur->appendAccessDocument($docId)->store();
        $listAll = $ur->listAccessDocuments();
        $this->assertEquals(1, count($listAll));
        $this->assertEquals([$docId], $listAll);

        $ur->appendAccessDocument($docId)->store();
        $listAll = $ur->listAccessDocuments();
        $this->assertEquals(1, count($listAll));
        $this->assertEquals([$docId], $listAll);
    }

    public function testAccessDocumentsInsertRemove()
    {
        $ur = UserRole::fetchByName('unit-test');

        try {
            $ur->appendAccessDocument(1)->store();
            $this->fail('Expecting exception on non-existent document.');
        } catch (Zend_Db_Statement_Exception $e) {
        }

        $d     = Document::new();
        $docId = $d->store();

        $ur->appendAccessDocument($docId)->store();
        $listAll = $ur->listAccessDocuments();
        $this->assertEquals(1, count($listAll));
        $this->assertEquals([$docId], $listAll);

        $ur->removeAccessDocument($docId)->store();
        $listEmpty = $ur->listAccessDocuments();
        $this->assertEquals(0, count($listEmpty));

        $ur->removeAccessDocument($docId)->store();
    }

    public function testListAccessFiles()
    {
        $ur        = UserRole::fetchByName('unit-test');
        $listEmpty = $ur->listAccessFiles();
        $this->assertEquals(0, count($listEmpty));
    }

    public function testListAccessModules()
    {
        $ur        = UserRole::fetchByName('unit-test');
        $listEmpty = $ur->listAccessModules();
        $this->assertEquals(0, count($listEmpty));
    }

    public function testAccessModulesInsertRemove()
    {
        $ur = UserRole::fetchByName('unit-test');
        $ur->appendAccessModule('oai')->store();

        $listAll = $ur->listAccessModules();
        $this->assertEquals(1, count($listAll));
        $this->assertEquals(['oai'], $listAll);

        $ur->removeAccessModule('oai')->store();
        $listEmpty = $ur->listAccessModules();
        $this->assertEquals(0, count($listEmpty));

        $ur->removeAccessModule('oai')->store();
    }

    public function testGetAllAccountIdsEmpty()
    {
        $ur        = UserRole::fetchByName('unit-test');
        $listEmpty = $ur->getAllAccountIds();
        $this->assertEquals(0, count($listEmpty));
    }

    public function testGetAllAccountNamesEmpty()
    {
        $ur        = UserRole::fetchByName('unit-test');
        $listEmpty = $ur->getAllAccountNames();
        $this->assertEquals(0, count($listEmpty));
    }
}
