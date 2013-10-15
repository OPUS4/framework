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
 * @package     Opus_File
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_File_Plugin_DefaultAccessTest.
 *
 * @package Opus_File
 * @category Tests
 *
 * @group FileTest
 */
class Opus_File_Plugin_DefaultAccessTest extends TestCase {

    protected function setUp() {
        parent::setUp();

        $guestRole = new Opus_UserRole();
        $guestRole->setName('guest');
        $guestRole->store();
    }

    public function testPostStoreIgnoreBadModel() {
        $plugin = new Opus_File_Plugin_DefaultAccess();

        $logger = new Opus_File_Plugin_DefaultAccessTest_LoggerMock();

        $plugin->setLogger($logger);
        $plugin->postStore(new Opus_Document());

        $messages = $logger->getMessages();

        $this->assertEquals(1, count($messages));
        $this->assertContains('#1 argument must be instance of Opus_File', $messages[0]);
    }

    public function testPostStoreIgnoreOldModel() {
        $guestRole = Opus_UserRole::fetchByName('guest');
        $list_before = $guestRole->listAccessFiles();

        $oldFile = new Opus_File_Plugin_DefaultAccessTest_FileMock(false); // alte Datei
        $object = new Opus_File_Plugin_DefaultAccess();
        $object->postStore($oldFile);

        $list_after = $guestRole->listAccessFiles();
        $this->assertEquals(count($list_before), count($list_after),
                'File access list counts should not have changed.');
        $this->assertEquals($list_before, $list_after,
                'File access lists should not have changed.');
    }

    public function testPostStoreSkipIfGuestRoleNotExists() {
        $guestRole = Opus_UserRole::fetchByName('guest');
        $guestRole->delete();

        $object = new Opus_File_Plugin_DefaultAccess();
        $logger = new Opus_File_Plugin_DefaultAccessTest_LoggerMock();
        $object->setLogger($logger);

        $file = new Opus_File_Plugin_DefaultAccessTest_FileMock(true); // neue Datei
        $object->postStore($file);

        $messages = $logger->getMessages();

        $this->assertEquals(1, count($messages));
        $this->assertContains('"guest" role does not exist!', $messages[0]);
    }

    public function testPostStoreAddsGuestToNewModel() {
        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();

        $guestRole = Opus_UserRole::fetchByName('guest');
        $list = $guestRole->listAccessFiles();
        $this->assertEquals(0, count($list));

        $doc = new Opus_Document();
        $file = $doc->addFile();
        $file->setPathName($path);
        $doc->store(); // beim Speichern wird *guest* hinzugefügt
        $modelId = $doc->getId();

        $doc = new Opus_Document($modelId);
        $file = $doc->getFile(0);
        $this->assertTrue(!empty($file));

        $fileId = $file->getId();

        $guestRole = Opus_UserRole::fetchByName('guest');
        $list = $guestRole->listAccessFiles();
        $this->assertContains($fileId, $list);
    }

    public function testGetLogger() {
        $plugin = new Opus_File_Plugin_DefaultAccess();

        $logger = $plugin->getLogger();

        $this->assertInstanceOf('Zend_Log', $logger);
    }

    public function testSetLogger() {
        $plugin = new Opus_File_Plugin_DefaultAccess();

        $logger = new Opus_File_Plugin_DefaultAccessTest_LoggerMock();

        $plugin->setLogger($logger);

        $this->assertEquals($logger, $plugin->getLogger());
    }

}

class Opus_File_Plugin_DefaultAccessTest_LoggerMock {

    private $messages = array();

    public function err($message) {
        $this->_messages[] = $message;
    }

    public function clear() {
        $this->_messages = array();
    }

    public function getMessages() {
        return $this->_messages;
    }

}

class Opus_File_Plugin_DefaultAccessTest_FileMock extends Opus_File {

    private $_newRecord;

    private $_fileId;

    public function __construct($newRecord = false) {
        $this->_newRecord = $newRecord;
    }

    public function isNewRecord() {
        return $this->_newRecord;
    }

    public function getId() {
        $this->_fileId;
    }

    public function setId($fileId) {
        $this->_fileId = $fileId;
    }

}
