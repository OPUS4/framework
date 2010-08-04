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
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: FileTest.php 5540 2010-04-22 15:52:39Z gerhardt $
 */

/**
 * Test cases for class Opus_File.
 *
 * @package Opus
 * @category Tests
 *
 * @group FileTest
 */
class Opus_FileTest extends TestCase {


    protected $_src_path = '';

    protected $_dest_path = '';

    protected $_config_backup = null;

    /**
     * Clear test tables and establish directories
     * for filesystem tests in /tmp.
     *
     * Backup a copy of current application configuration and
     * set a new Zend_Config instance globally.
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();

        $this->_config_backup = Zend_Registry::get('Zend_Config');

        // TODO: Replace by path relative to working directory
        $path = '/tmp/opus4-test/' . uniqid();

        $this->_src_path = $path . '/src';
        mkdir($this->_src_path, 0777, true);

        $this->_dest_path = $path . '/dest';
        mkdir($this->_dest_path, 0777, true);
    }

    /**
     * Clear test tables and remove filesystem test directories.
     *
     * Roll back global configuration changes.
     *
     * @return void
     */
    public function tearDown() {
        // $this->_deleteDirectory('/tmp/opus4-test');

        parent::tearDown();
    }
    
    /**
     * Remove a directory and its entries recursivly.
     *
     * @param string $dir Directory to delete.
     * @return bool Result of rmdir() call.
     */
    private function _deleteDirectory($dir) {
        if (false === file_exists($dir)) {
            return true;
        }
        if (false === is_dir($dir)) {
            return unlink($dir);
        }
        foreach (scandir($dir) as $item) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (false === $this->_deleteDirectory($dir.DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }
        return rmdir($dir);
    }

    /**
     * Test if a valid Opus_File instance gets validated to be correct.
     *
     * @return void
     */
    public function testValidationIsCorrect() {
        $file = new Opus_File;
        $file->setPathName('23423432-3244.pdf');
        $file->setLabel('Volltextdokument (PDF)');
        $file->setMimeType('application/x-pdf');

        $this->assertTrue($file->isValid(), 'File model should validate to true.');
    }

    /**
     * Test if validation failes when the files hash value is invalid.
     *
     * @return void
     */
    public function testValidationFailesOnInvalidHashValueModel() {
        $hash = new Opus_HashValues;
        $hash->setType('md5');
        $this->assertFalse($hash->isValid(), 'Hash model should validate to false.');

        $file = new Opus_File;
        $file->setPathName('23423432-3244.pdf');
        $file->setHashValue($hash);

        $this->assertFalse($file->isValid(), 'File model should validate to false.');

        // TODO: Check, why this test fails.
        $this->markTestIncomplete('TODO: Check, why this test fails.');
        $this->assertTrue(array_key_exists('HashValue', $file->getValidationErrors()),
            'Missing validation errors for field HashValue.');
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testFilesTemporarySourcePathGetCopiedToDestinationDirectoryAndAreNamedProperly() {
        $doc = new Opus_Document;

        touch($this->_src_path . '/foobar.pdf');

        $file = new Opus_File;
        $file->setPathName('foobar-copied.pdf');
        $file->setLabel('Volltextdokument (PDF)');
        $file->setMimeType('application/x-pdf');
        $file->setDestinationPath($this->_dest_path);
        $file->setTempFile($this->_src_path . '/foobar.pdf');

        $doc->setFile($file);
        $id = $doc->store();

        $this->assertFileExists($this->_dest_path . "/$id/foobar-copied.pdf", 'File has not been copied.');
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testFilesTemporaryRelativeSourcePathGetCopiedToDestinationDirectoryAndAreNamedProperly() {
        $doc = new Opus_Document;

        touch($this->_src_path . '/foobar.pdf');

        $file = new Opus_File;
        $file->setPathName('foobar-copied.pdf');
        $file->setLabel('Volltextdokument (PDF)');
        $file->setMimeType('application/x-pdf');
        $file->setDestinationPath($this->_dest_path);
        $file->setSourcePath($this->_src_path);
        $file->setTempFile('foobar.pdf');

        $doc->setFile($file);
        $id = $doc->store();

        $this->assertFileExists($this->_dest_path . "/$id/foobar-copied.pdf", 'File has not been copied.');
    }

    /**
     * Test if DeletionToken implementation as defined in Opus_Model_Dependent_Abstract
     * is provided by Opus_File.
     *
     * @return void
     */
    public function testDeleteCallReturnsDeletionTokenAndNotActuallyRemovesAFile() {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $doc = new Opus_Document;
        touch($this->_src_path . '/foobar.pdf');
        $file = new Opus_File;
        $file->setPathName('foobar-copied.pdf');
        $file->setLabel('Volltextdokument (PDF)');
        $file->setMimeType('application/x-pdf');
        $file->setDestinationPath($this->_dest_path);
        $file->setTempFile($this->_src_path . '/foobar.pdf');
        $doc->setFile($file);
        $id = $doc->store();

        $token = $file->delete();

        $this->assertNotNull($token, 'No deletion token returned.');
        $this->assertFileExists($this->_dest_path . "/$id/foobar-copied.pdf", 'File has been deleted.');
    }

    /**
     * Test if file and Opus_File model can be deleted by setting the containing Opus_Document field to null.
     *
     * @return void
     */
    public function testFileGetsDeletedThroughDocumentModel() {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $doc = new Opus_Document;
        touch($this->_src_path . '/foobar.pdf');
        $file = new Opus_File;
        $file->setPathName('foobar-copied.pdf');
        $file->setLabel('Volltextdokument (PDF)');
        $file->setMimeType('application/x-pdf');
        $file->setDestinationPath($this->_dest_path);
        $file->setTempFile($this->_src_path . '/foobar.pdf');
        $doc->setFile($file);
        $id = $doc->store();

        $doc = new Opus_Document($id);
        $doc->setFile(null);
        $this->assertFileExists($this->_dest_path . "/$id/foobar-copied.pdf", 'File has been deleted before the model has been stored.');

        $doc->store();
        $this->assertFileNotExists($this->_dest_path . "/$id/foobar-copied.pdf", 'File has not been deleted after storing the model.');
    }

    /**
     * Test if path settings for source and destination are loaded from the
     * application configuration.
     *
     * @return void
     */
    public function testIfPathSettingsGetLoadedFromConfiguration() {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $file = new Opus_File;
        $this->assertEquals($this->_src_path, realpath($file->getSourcePath()), 'Wrong source path loaded from configuration.');
        $this->assertEquals($this->_dest_path, realpath($file->getDestinationPath()),'Wrong destination path loaded from configuration.');
    }

    /**
     * Test if MimeType field is set withmime type of actual file
     * after storing the Opus_File model.
     *
     * @return void
     */   
    public function testMimeTypeIsSetAfterStore() {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $filename = $this->_src_path . '/foobar.txt';
        touch($filename);
        $mimetype = mime_content_type($filename);

        if (true === empty($mimetype)) {
            $mimetype = 'application/octet-stream';
        }

        $doc = new Opus_Document;
        $file = new Opus_File;
        $file->setPathName('foobar-copied.txt');
        $file->setDestinationPath($this->_dest_path);
        $file->setTempFile($filename);
        $doc->setFile($file);
        $id = $doc->store();

        $this->assertEquals($file->getMimeType(), $mimetype, 'Mime type is not set as expected.');
    }

    /**
     * Test if a changed path name results to a rename of the file.
     *
     * @return void
     */
    public function testChangingPathNameRenamesFile() {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $fileNameWrong = 'wrongName.pdf';
        $fileNameCorrect = 'correctName.pdf';

        touch($this->_src_path . DIRECTORY_SEPARATOR . 'wrongName2.pdf');

        $file = new Opus_File;
        $file->setTempFile('wrongName2.pdf');
        $file->setPathName($fileNameWrong);

        $doc = new Opus_Document;
        $doc->setFile($file);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $file = $doc->getFile(0); // get first file
        $file->setPathName($fileNameCorrect);
        $doc->store();

        $path = $this->_dest_path . DIRECTORY_SEPARATOR . $docId . DIRECTORY_SEPARATOR;

        $this->assertFileExists($path . $fileNameCorrect, 'Expecting file renamed proberly.');
        $this->assertFileNotExists($path . $fileNameWrong, 'Expecting old file removed.');
    }

    /**
     * Test if a failed renaming attempt throws an exception
     * and not altered any data
     *
     * @return void
     */
    public function testIfRenamingFailedExceptionIsThrownAndNoDataIsChanged() {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $fileNameWrong = 'wrongName.pdf';
        $fileNameCorrect = 'correctName.pdf';

        touch($this->_src_path . DIRECTORY_SEPARATOR . 'wrongName2.pdf');

        $file = new Opus_File;
        $file->setTempFile('wrongName2.pdf');
        $file->setPathName($fileNameWrong);

        $doc = new Opus_Document;
        $doc->setFile($file);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $file = $doc->getFile(0); // get first file
        // set wrong path to trigger error
        $file->setDestinationPath($this->_src_path . DIRECTORY_SEPARATOR);
        $file->setPathName($fileNameCorrect);

        try {
            $doc->store();
        } catch (Opus_Model_Exception $ope) {
            $expectedMessage = 'Could not rename file from';
            $this->assertStringStartsWith($expectedMessage, $ope->getMessage(), 'Catched wrong exception!');
            return;
        } catch (Exception $ex) {
            $this->fail('Expected exception not thrown.');
        }

        $this->fail('It should be an exception thrown.');
        
        $doc = new Opus_Document($docId);
        $file = $doc->getFile(0);
        $this->asserEquals($fileNameWrong, $file->getPathName(), 'Content of PathName should not be altered!');
    }

    /**
     *
     *
     * @return void
     */
    public function testUpdateFileObjectDoesNotDeleteStoredFile() {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $doc = new Opus_Document;

        touch($this->_src_path . '/foobar.pdf');

        $file = new Opus_File;
        $file->setPathName('foobar-copied.pdf');
        $file->setLabel('Volltextdokument (PDF)');
        $file->setMimeType('application/x-pdf');
        $file->setDestinationPath($this->_dest_path);
        $file->setTempFile($this->_src_path . '/foobar.pdf');

        $doc->setFile($file);
        $id = $doc->store();
        $fileId = $file->getId();

        $file2 = new Opus_File($fileId);
        $file2->setPathName('foobar-copied.pdf');
        $file2->setLabel('Volltextdokument (PDF) 2');
        $file2->setMimeType('application/x-pdf');

        $doc = new Opus_Document($id);
        $doc->setFile($file2);
        $doc->store();

        $this->assertFileExists($this->_dest_path . "/$id/foobar-copied.pdf", 'File should not be deleted.');

    }

}

