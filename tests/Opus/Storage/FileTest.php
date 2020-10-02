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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Storage_File.
 *
 * @package  Opus_File
 * @category Tests
 *
 * @group FileTest
 */
class Opus_Storage_FileTest extends TestCase
{

    private $__src_path = '';

    private $__dest_path = '';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();

        $this->__src_path = $path . '/src';
        mkdir($this->__src_path, 0777, true);

        $this->__dest_path = $path . '/dest';
        mkdir($this->__dest_path, 0777, true);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        Opus_Util_File::deleteDirectory($this->__src_path);
        Opus_Util_File::deleteDirectory($this->__dest_path);

        parent::tearDown();
    }

    /**
     * Tests using constructor without parameters.
     */
    public function testConstructorFail()
    {
        $this->setExpectedException('Opus_Storage_Exception');
        $storage = new Opus_Storage_File();
    }

    /**
     * Tests getting the working directory of a Opus_Storage_File object.
     */
    public function testGetWorkingDirectory()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir1');
        $this->assertEquals($this->__dest_path . DIRECTORY_SEPARATOR . 'subdir1'
                . DIRECTORY_SEPARATOR, $storage->getWorkingDirectory());
    }

    /**
     * Tests creating subdirectory.
     */
    public function testCreateSubdirectory()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir2');
        $storage->createSubdirectory();
        $this->assertTrue(is_dir($storage->getWorkingDirectory()));
        $storage->removeEmptyDirectory();
    }

    /**
     * Test copying external file into working directory.
     */
    public function testCopyExternalFile()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir3');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);
        $destination = 'copiedtest.txt';
        $storage->copyExternalFile($source, $destination);
        $this->assertTrue(is_file($storage->getWorkingDirectory()
                . 'copiedtest.txt'));
    }

    /**
     * Test renaming file.
     */
    public function testRenameFile()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir4');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);
        $destination = 'test.txt';
        $storage->copyExternalFile($source, $destination);
        $storage->renameFile('test.txt', 'renamedtest.txt');
        $this->assertTrue(is_file($storage->getWorkingDirectory()
                . 'renamedtest.txt'));
        $this->assertFalse(is_file($storage->getWorkingDirectory()
                . 'test.txt'));
    }

    /**
     * Test attempting to rename file that does not exist.
     */
    public function testRenameNonExistingFile()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir');
        $storage->createSubdirectory();
        $this->setExpectedException('Opus_Storage_FileNotFoundException');
        $storage->renameFile('test', 'test2');
    }

    /**
     * Test attempting to rename directory.
     */
    public function testRenameFileAttemptOnDirectory()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir');
        $storage->createSubdirectory();
        $path = $storage->getWorkingDirectory() . '/testdir';
        mkdir($path);
        $this->setExpectedException('Opus_Storage_Exception');
        $storage->renameFile('testdir', 'testdir2');
    }

    /**
     * Test deleting file.
     */
    public function testDeleteFile()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir5');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);
        $destination = 'test.txt';
        $storage->copyExternalFile($source, $destination);
        $storage->deleteFile($destination);
        $this->assertFalse(is_file($storage->getWorkingDirectory()
                . 'test.txt'));
    }

    /**
     * Test getting mime type from encoding for text file.
     */
    public function testGetFileMimeEncoding()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir5');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);

        $fh = fopen($source, 'w');

        if ($fh == false) {
            $this->fail("Unable to write file $source.");
        }

        $rand = rand(1, 100);
        for ($i = 0; $i < $rand; $i++) {
            fwrite($fh, "t");
        }

        fclose($fh);

        $destination = 'test.txt';
        $storage->copyExternalFile($source, $destination);
        $this->assertEquals('text/plain', $storage->getFileMimeEncoding($destination));
    }

    /**
     * Test getting mime type from file extension for text file.
     */
    public function testGetFileMimeTypeFromExtension()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir6');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);
        $destination = 'test.txt';
        $storage->copyExternalFile($source, $destination);
        $this->assertEquals('text/plain', $storage->getFileMimeTypeFromExtension($destination));
    }

    /**
     * Test getting mime type from file extension for PDF file.
     */
    public function testGetFileMimeTypeFromExtensionForPdf()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir6');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.pdf";
        touch($source);
        $destination = 'test.pdf';
        $storage->copyExternalFile($source, $destination);
        $this->assertEquals('application/pdf', $storage->getFileMimeTypeFromExtension($destination));
    }

    /**
     * Test getting mime type from file extension for Postscript file.
     */
    public function testGetFileMimeTypeFromExtensionForPostscript()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir6');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.ps";
        touch($source);
        $destination = 'test.ps';
        $storage->copyExternalFile($source, $destination);
        $this->assertEquals('application/postscript', $storage->getFileMimeTypeFromExtension($destination));
    }

    /**
     * Tests getting file size of empty file.
     */
    public function testGetFileSize()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir7');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);
        $destination = 'test.txt';
        $storage->copyExternalFile($source, $destination);
        $this->assertEquals(0, $storage->getFileSize($destination));
    }

    /**
     * Tests getting file size of file with size 10.
     */
    public function testGetFileSizeForNonEmptyFile()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir7');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);

                $fh = fopen($source, 'w');

        if ($fh == false) {
            $this->fail("Unable to write file $source.");
        }

        fwrite($fh, "1234567890");

        fclose($fh);

        $destination = 'test.txt';
        $storage->copyExternalFile($source, $destination);
        $this->assertEquals(10, $storage->getFileSize($destination));
    }

    /**
     * Tests removing an empty directory.
     */
    public function testRemoveEmptyDirectory()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir8');
        $storage->createSubdirectory();
        $this->assertTrue(is_dir($storage->getWorkingDirectory()));
        $this->assertTrue($storage->removeEmptyDirectory());
        $this->assertFalse(is_dir($storage->getWorkingDirectory()));
    }

    /**
     * Tests attempting to delete non-empty directory.
     */
    public function testFailedRemoveEmptyDirectory()
    {
        $storage = new Opus_Storage_File($this->__dest_path, 'subdir8');
        $storage->createSubdirectory();
        $source = $this->__src_path . '/' . "test.txt";
        touch($source);
        $destination = 'test.txt';
        $storage->copyExternalFile($source, $destination);
        $this->assertFalse($storage->removeEmptyDirectory());
        $this->assertTrue(is_dir($storage->getWorkingDirectory()));
    }
}
