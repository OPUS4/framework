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
 * @copyright   Copyright (c) 2010-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\File
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 */

namespace OpusTest\File\Plugin;

use Opus\Config;
use Opus\Document;
use Opus\File\Plugin\DefaultAccess;
use Opus\UserRole;
use OpusTest\TestAsset\FileMock;
use OpusTest\TestAsset\LoggerMock;
use OpusTest\TestAsset\TestCase;

use function count;
use function uniqid;

/**
 * Test cases for class Opus\File\Plugin\DefaultAccessTest.
 *
 * @package Opus\File
 * @category Tests
 * @group FileTest
 */
class DefaultAccessTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->clearTables(false, ['user_roles', 'documents', 'document_files']);

        $guestRole = new UserRole();
        $guestRole->setName('guest');
        $guestRole->store();

        $userRole = new UserRole();
        $userRole->setName('user');
        $userRole->store();
    }

    public function testPostStoreIgnoreBadModel()
    {
        $plugin = new DefaultAccess();

        $logger = new LoggerMock();

        $plugin->setLogger($logger);
        $plugin->postStore(new Document());

        $messages = $logger->getMessages();

        $this->assertEquals(1, count($messages));
        $this->assertContains('#1 argument must be instance of Opus\File', $messages[0]);
    }

    public function testPostStoreIgnoreOldModel()
    {
        $guestRole  = UserRole::fetchByName('guest');
        $listBefore = $guestRole->listAccessFiles();

        $oldFile = new FileMock(false); // alte Datei
        $object  = new DefaultAccess();
        $object->postStore($oldFile);

        $listAfter = $guestRole->listAccessFiles();
        $this->assertEquals(
            count($listBefore),
            count($listAfter),
            'File access list counts should not have changed.'
        );
        $this->assertEquals(
            $listBefore,
            $listAfter,
            'File access lists should not have changed.'
        );
    }

    public function testPostStoreSkipIfGuestRoleNotExists()
    {
        $guestRole = UserRole::fetchByName('guest');
        $guestRole->delete();

        $object = new DefaultAccess();
        $logger = new LoggerMock();
        $object->setLogger($logger);

        $file = new FileMock(true); // neue Datei
        $object->postStore($file);

        $messages = $logger->getMessages();

        $this->assertEquals(1, count($messages));
        $this->assertContains('\'guest\' role does not exist!', $messages[0]);
    }

    /**
     * Wenn der Name leer ist, wird keine Role hinzugefügt und keine Meldung ausgegeben.
     */
    public function testPostStoreAddNoRoleToNewModel()
    {
        $config                                           = Config::get();
        $config->securityPolicy->files->defaultAccessRole = '';

        $userRole = UserRole::fetchByName('user');
        $userRole->delete();

        $guestRole = UserRole::fetchByName('guest');
        $guestRole->delete();

        $object = new DefaultAccess();
        $logger = new LoggerMock();
        $object->setLogger($logger);

        $file = new FileMock(true); // neue Datei
        $object->postStore($file);

        $messages = $logger->getMessages();

        $this->assertEquals(0, count($messages));
    }

    public function testPostStoreAddsGuestToNewModel()
    {
        $config = Config::get();
        $path   = $config->workspacePath . '/' . uniqid();

        $guestRole = UserRole::fetchByName('guest');
        $list      = $guestRole->listAccessFiles();
        $this->assertEquals(0, count($list));

        $doc  = new Document();
        $file = $doc->addFile();
        $file->setPathName($path);
        $doc->store(); // beim Speichern wird *guest* hinzugefügt
        $modelId = $doc->getId();

        $doc  = new Document($modelId);
        $file = $doc->getFile(0);
        $this->assertTrue(! empty($file));

        $fileId = $file->getId();

        $guestRole = UserRole::fetchByName('guest');
        $list      = $guestRole->listAccessFiles();
        $this->assertContains($fileId, $list);
    }

    public function testPostStoreAddConfiguredRoleToNewModel()
    {
        $config                                           = Config::get();
        $path                                             = $config->workspacePath . '/' . uniqid();
        $config->securityPolicy->files->defaultAccessRole = 'user';

        $userRole = UserRole::fetchByName('user');
        $list     = $userRole->listAccessFiles();
        $this->assertEquals(0, count($list));

        $doc  = new Document();
        $file = $doc->addFile();
        $file->setPathName($path);
        $doc->store(); // beim Speichern wird *guest* hinzugefügt
        $modelId = $doc->getId();

        $doc  = new Document($modelId);
        $file = $doc->getFile(0);
        $this->assertTrue(! empty($file));

        $fileId = $file->getId();

        $userRole = UserRole::fetchByName('user');
        $list     = $userRole->listAccessFiles();
        $this->assertContains($fileId, $list, 'File was not added to role \'user\'');
    }

    public function testGetLogger()
    {
        $plugin = new DefaultAccess();

        $logger = $plugin->getLogger();

        $this->assertInstanceOf('Zend_Log', $logger);
    }

    public function testSetLogger()
    {
        $plugin = new DefaultAccess();

        $logger = new LoggerMock();

        $plugin->setLogger($logger);

        $this->assertEquals($logger, $plugin->getLogger());
    }
}
