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
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Test cases for class Opus_File.
 *
 * @group FileTest
 */
class Opus_FileTest extends TestCase
{

    protected $_src_path = '';

    protected $_dest_path = '';

    /**
     * Clear test tables and establish directories
     * for filesystem tests in /tmp.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . DIRECTORY_SEPARATOR . uniqid();

        $this->_src_path = $path . DIRECTORY_SEPARATOR . 'src';
        mkdir($this->_src_path, 0777, true);

        $this->_dest_path = $path . DIRECTORY_SEPARATOR . 'dest' . DIRECTORY_SEPARATOR;
        mkdir($this->_dest_path, 0777, true);
        mkdir($this->_dest_path . DIRECTORY_SEPARATOR . 'files', 0777, true);

        $config->merge(new Zend_Config([
            'workspacePath' => $this->_dest_path,
            'checksum' => [
                'maxVerificationSize' => 1,
            ],
        ]));
    }

    /**
     * Clear test tables and remove filesystem test directories.
     *
     * Roll back global configuration changes.
     *
     * @return void
     */
    public function tearDown()
    {
        Opus_Util_File::deleteDirectory($this->_src_path);
        Opus_Util_File::deleteDirectory($this->_dest_path);

        parent::tearDown();
    }

    /**
     *
     * @param string $filename
     * @return Opus_Document
     */
    private function _createDocumentWithFile($filename)
    {
        $filepath = $this->_src_path . DIRECTORY_SEPARATOR . $filename;
        touch($filepath);

        $doc = new Opus_Document;
        $file = $doc->addFile();

        $file->setTempFile($filepath);
        $file->setPathName('copied-' . $filename);
        $file->setLabel('Volltextdokument (PDF)');

        return $doc;
    }

    /**
     * Test if a valid Opus_File instance gets validated to be correct.
     *
     * @return void
     */
    public function testValidationIsCorrect()
    {
        $file = new Opus_File;
        $file->setPathName('23423432-3244.pdf');
        $file->setLabel('Volltextdokument (PDF)');

        $this->assertTrue($file->isValid(), 'File model should validate to true.');
    }

    /**
     * Test if validation failes when the files hash value is invalid.
     *
     * @return void
     */
    public function testValidationFailesOnInvalidHashValueModel()
    {
        $hash = new Opus_HashValues;
        $hash->setType('md5');
        $this->assertFalse($hash->isValid(), 'Hash model should validate to false.');

        $file = new Opus_File;
        $file->setPathName('23423432-3244.pdf');
        $file->setHashValue($hash);

        $this->assertFalse($file->isValid(), 'File model should validate to false.');

        // TODO: Check, why this test fails.
        $this->markTestSkipped('TODO: Check, why this test fails.');
        $this->assertTrue(
            array_key_exists('HashValue', $file->getValidationErrors()),
            'Missing validation errors for field HashValue.'
        );
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testFilesStoreDependent()
    {

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $id = $doc->store();

        $this->assertNotNull($file->getId(), "Storing file did not work out.");
        $this->assertEquals(
            $doc->getId(),
            $file->getParentId(),
            "ParentId does not match parent model."
        );

        $doc = new Opus_Document($id);
        $file = $doc->getFile(0);

        $this->assertInstanceOf('Opus_File', $file, "getFile has wrong type."); // TODO should use assertInstanceOf
        $this->assertEquals(
            $doc->getId(),
            $file->getParentId(),
            "ParentId does not match parent model."
        );

        $file->store();

        // File is empty.
        // TODO OPUSVIER-2503 unterschiedliche MIME-Typen für leere Dateien
        $this->assertTrue(in_array($file->getMimeType(), ['application/x-empty', 'inode/x-empty']));
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testFilesTemporaryAbsoluteSource()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $file->setTempFile($this->_src_path . DIRECTORY_SEPARATOR . 'foobar.pdf');
        $id = $doc->store();

        $this->assertFileExists(
            $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'copied-foobar.pdf',
            'File has not been copied.'
        );
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testFilesTemporaryRelativeSource()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $id = $doc->store();

        $expectedPath = $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'copied-foobar.pdf';
        $this->assertFileExists($expectedPath, 'File has not been copied.');
        $this->assertEquals($expectedPath, $file->getPath(), "Pathnames do not match.");
        $this->assertTrue($file->exists(), "File->exists should return true on saved files.");
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testFilesExistsAfterDelete()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $id = $doc->store();

        $expectedPath = $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'copied-foobar.pdf';
        $this->assertFileExists($expectedPath, 'File has not been copied.');
        $this->assertEquals($expectedPath, $file->getPath(), "Pathnames do not match.");
        $this->assertTrue($file->exists(), "File->exists should return true on saved files.");

        @unlink($file->getPath());
        $this->assertFalse($file->exists(), "File->exists should return false on deleted files.");
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testDoDeleteReallyDeletesFileAndWorkingDirectory()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $id = $doc->store();

        $workingDir = $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id;
        $expectedPath = $workingDir . DIRECTORY_SEPARATOR . 'copied-foobar.pdf';

        $this->assertFileExists($expectedPath, 'File has not been copied.');
        $this->assertEquals($expectedPath, $file->getPath(), "Pathnames do not match.");
        $this->assertTrue($file->exists(), "File->exists should return true on saved files.");

        $token = $file->delete();
        $this->assertNotNull($token, 'No deletion token returned.');
        $this->assertFileExists($expectedPath, 'File should still exist.');

        $token = $file->doDelete($token);
        $this->assertFileNotExists($expectedPath, 'File should be deleted.');
        $this->assertFalse(file_exists($workingDir));
        $this->assertFalse(is_dir($workingDir));
    }

    /**
     * Test if DeletionToken implementation as defined in Opus_Model_Dependent_Abstract
     * is provided by Opus_File.
     *
     * @return void
     */
    public function testDeleteCallReturnsDeletionTokenAndNotActuallyRemovesAFile()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $id = $doc->store();

        $token = $file->delete();

        $this->assertNotNull($token, 'No deletion token returned.');
        $this->assertFileExists(
            $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'copied-foobar.pdf',
            'File has been deleted.'
        );
    }

    /**
     * Test if file and Opus_File model can be deleted by setting the containing Opus_Document field to null.
     *
     * @return void
     */
    public function testFileGetsDeletedThroughDocumentModel()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $id = $doc->store();

        // Reload Opus_Document and Opus_File.
        $doc = new Opus_Document($id);
        $file = $doc->getFile(0);

        $doc->setFile(null);
        $this->assertFileExists(
            $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id .  DIRECTORY_SEPARATOR . 'copied-foobar.pdf',
            'File has been deleted before the model has been stored.'
        );

        $doc->store();
        $this->assertFileNotExists(
            $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'copied-foobar.pdf',
            'File has not been deleted after storing the model.'
        );
    }

    /**
     * Test if path settings for source and destination are loaded from the
     * application configuration.
     *
     * @return void
     */
    public function testIfPathSettingsGetLoadedFromConfiguration()
    {
        $this->markTestSkipped('Fix test for our Opus_File.');

        $file = new Opus_File;
        $this->assertEquals(
            $this->_src_path,
            realpath($file->getSourcePath()),
            'Wrong source path loaded from configuration.'
        );
        $this->assertEquals(
            $this->_dest_path,
            realpath($file->getDestinationPath()),
            'Wrong destination path loaded from configuration.'
        );
    }

    /**
     * Test if MimeType field is set withmime type of actual file
     * after storing the Opus_File model.
     *
     * @return void
     */
    public function testMimeTypeIsSetAfterStore()
    {

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $mimetype = mime_content_type($file->getPath());
        if (true === empty($mimetype)) {
            $mimetype = 'application/octet-stream';
        }

        $this->assertEquals(
            $file->getMimeType(),
            $mimetype,
            'Mime type is not set as expected.'
        );
    }

    /**
     * Test if a changed path name results to a rename of the file.
     *
     * @return void
     */
    public function testChangingPathNameRenamesFile()
    {
        $fileNameWrong = 'wrongName.pdf';
        $fileNameCorrect = 'correctName.pdf';

        $doc = $this->_createDocumentWithFile($fileNameWrong);
        $file = $doc->getFile(0);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $file = $doc->getFile(0); // get first file
        $file->setPathName($fileNameCorrect);
        $doc->store();

        $path = $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $docId . DIRECTORY_SEPARATOR;
        $this->assertFileExists(
            $path . $fileNameCorrect,
            'Expecting file renamed properly.'
        );

        $this->assertFileNotExists($path . $fileNameWrong, 'Expecting old file removed.');
    }

    /**
     * Test if a failed renaming attempt throws an exception
     * and not altered any data
     *
     * @return void
     */
    public function testIfRenamingFailedExceptionIsThrownAndNoDataIsChanged()
    {
        $fileNameWrong = 'wrongName.pdf';
        $fileNameCorrect = 'correctName.pdf';

        $doc = $this->_createDocumentWithFile($fileNameWrong);
        $file = $doc->getFile(0);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $file = $doc->getFile(0); // get first file
        $file->setPathName($fileNameCorrect);

        $path = dirname($file->getPath());
        try {
            @chmod($path, 0555);
            $doc->store();
            @chmod($path, 0777);

            $this->fail('Expected exception not thrown.');
        } catch (Opus\Model\Exception $e) {
            @chmod($path, 0777);
            $expectedMessage = 'Could not rename file from';
            $this->assertStringStartsWith($expectedMessage, $e->getMessage(), 'Caught wrong exception!');
        }
    }

    /**
     *
     *
     * @return void
     */
    public function testUpdateFileObjectDoesNotDeleteStoredFile()
    {

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $id = $doc->store();

        $file2 = new Opus_File($file->getId());
        $file2->setPathName('copied-foobar.pdf');
        $file2->setLabel('Volltextdokument (PDF) 2');

        $doc = new Opus_Document($id);
        $doc->setFile($file2);
        $doc->store();

        $this->assertFileExists(
            $this->_dest_path . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $id . DIRECTORY_SEPARATOR . 'copied-foobar.pdf',
            'File should not be deleted.'
        );
    }

    /**
     * Test if MimeType field is set withmime type of actual file
     * after storing the Opus_File model.
     *
     * @return void
     */
    public function testFileSizeIsSetAfterStore()
    {

        // Create zero file.
        $filename = $this->_src_path . DIRECTORY_SEPARATOR . 'foobar.txt';
        touch($filename);

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $this->assertEquals(
            $file->getFileSize(),
            0,
            'FileSize should be zero now.'
        );

        // Create random-sized file.
        $filename_nonzero = $this->_src_path . DIRECTORY_SEPARATOR . 'foobar-nonzero.txt';
        $fh = fopen($filename_nonzero, 'w');

        if ($fh == false) {
            $this->fail("Unable to write file $filename_nonzero.");
        }

        $rand = rand(1, 100);
        for ($i = 0; $i < $rand; $i++) {
            fwrite($fh, ".");
        }

        fclose($fh);


        $doc = new Opus_Document;
        $file = $doc->addFile();

        $file->setTempFile($filename_nonzero);
        $file->setPathName('copied-foobar-nonzero.txt');

        $doc->store();

        $this->assertEquals(
            $file->getFileSize(),
            $rand,
            'FileSize is not set as expected.'
        );
        $this->assertTrue(
            $file->getFileSize() >= 1,
            'FileSize should be bigger zero.'
        );
    }


    /**
     * Test if md5 hash value of empty file matches expected value.
     *
     * @return void
     */
    public function testHashValueOfEmptyFileAfterStore()
    {

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $actual_hash = $file->getRealHash('md5');
        $expected_hash = 'd41d8cd98f00b204e9800998ecf8427e';
        $this->assertEquals($expected_hash, $actual_hash);

        $this->assertTrue($file->canVerify());
        $this->assertTrue($file->verify('md5', $expected_hash));
        $this->assertTrue($file->verifyAll());
    }

    /**
     * Test if md5 hash value of empty file matches expected value.
     *
     * @return void
     */
    public function testHashValueOfModifiedFileAfterStore()
    {

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $expected_hash = 'd41d8cd98f00b204e9800998ecf8427e';
        $this->assertTrue($file->canVerify());
        $this->assertTrue($file->verify('md5', $expected_hash));
        $this->assertTrue($file->verifyAll());

        $fh = fopen($file->getPath(), 'w');
        if ($fh == false) {
            $this->fail("Unable to write file " . $file->getPath());
        }

        fwrite($fh, "foo");
        fclose($fh);

        // need new object because Opus_File caches calculated hash values
        $file = new Opus_File($file->getId());

        $this->assertFalse($file->verify('md5', $expected_hash));
        $this->assertFalse($file->verifyAll());
    }

    public function testHashValueCachedOnceCalculated()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $expected_hash = 'd41d8cd98f00b204e9800998ecf8427e';
        $this->assertTrue($file->canVerify());
        $this->assertTrue($file->verify('md5', $expected_hash));
        $this->assertTrue($file->verifyAll());

        $fh = fopen($file->getPath(), 'w');
        if ($fh == false) {
            $this->fail("Unable to write file " . $file->getPath());
        }

        fwrite($fh, "foo");
        fclose($fh);

        // file has been changed, but it is still the same Opus_File object and values have been cached
        $this->assertTrue($file->verify('md5', $expected_hash));
        $this->assertTrue($file->verifyAll());
    }

    /**
     * Test if md5 hash value of empty file matches expected value.
     *
     * @return void
     * TODO Ist der Test komplett?
     */
    public function testInvalidHashAlgorithmAfterStore()
    {

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $this->setExpectedException('Exception'); // TODO broken for PHPunit 3.6
        $actual_hash = $file->getRealHash('md23');
    }

    /**
     * Test if md5 hash value of empty file matches expected value.
     *
     * @return void
     */
    public function testDisabledVerifyInConfig()
    {

        Zend_Registry::set('Zend_Config', Zend_Registry::get('Zend_Config')->merge(
            new Zend_Config([
                'workspacePath' => $this->_dest_path,
                'checksum' => [
                    'maxVerificationSize' => 0,
                ],
            ])
        ));

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $this->assertFalse($file->canVerify());

        Zend_Registry::set('Zend_Config', Zend_Registry::get('Zend_Config')->merge(
            new Zend_Config([
                'workspacePath' => $this->_dest_path,
                'checksum' => [
                    'maxVerificationSize' => -1,
                ],
            ])
        ));

        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();

        $this->assertTrue($file->canVerify());
    }

    /**
     * Test exists() function for Opus_File.
     */
    public function testFileExists()
    {
        $doc = $this->_createDocumentWithFile("foobar.pdf");
        $file = $doc->getFile(0);
        $doc->store();
        $fileId = $doc->getFile(0)->getId();
        $this->assertNotNull($fileId);
        $file = new Opus_File($fileId);
        $this->assertTrue($file->exists());
    }

    /**
     * Test if added files with tempory path get moved to destination path target filename.
     *
     * @return void
     */
    public function testAddFilesTwiceDoesNotOverwrite()
    {
        $filename = 'foobar.pdf';
        $filepath = $this->_src_path . DIRECTORY_SEPARATOR . $filename;
        touch($filepath);

        $doc = new Opus_Document;

        $file = $doc->addFile();
        $file->setTempFile($filepath);
        $file->setPathName('copied-' . $filename);
        $file->setLabel('Volltextdokument-1 (PDF)');

        $file = $doc->addFile();
        $file->setTempFile($filepath);
        $file->setPathName('copied-' . $filename);
        $file->setLabel('Volltextdokument-2 (PDF)');

        $this->setExpectedException('Opus\Model\Exception');
        $doc->store();

        // This code is not reached if the expected exception is thrown
        foreach ($doc->getFile() as $file) {
            echo "file: " . $file->getPath() . "\n";
        }
    }

    /**
     * Testing if ParentId will be set for Opus_Model_Dependant_Abstract.
     */
    public function testSettingParentId()
    {
        $doc = new Opus_Document;
        $doc->store();
        $this->assertNotNull($doc->getId());

        $filename = "foobar.pdf";
        $filepath = $this->_src_path . DIRECTORY_SEPARATOR . $filename;
        touch($filepath);

        $file = $doc->addFile();
        $file->setTempFile($filepath);
        $file->setPathName('copied-' . $filename);
        $file->setLabel('Volltextdokument (PDF)');

        $this->assertEquals($doc->getId(), $file->getParentId());

        $file->store();
    }

    /**
     * Regression Test for OPUSVIER-1687.
     */
    public function testInvalidateDocumentCache()
    {
        $filename = "foobar.pdf";
        $filepath = $this->_src_path . DIRECTORY_SEPARATOR . $filename;

        touch($filepath);

        $doc = new Opus_Document();
        $doc->setType("article");
        $file = $doc->addFile();
        $file->setTempFile($filepath);
        $file->setPathName('copied-' . $filename);
        $file->setLabel('Volltextdokument (PDF)');

        $docId = $doc->store();
        $files = $doc->getFile();
        $fileId = $files[0]->getId();
        $file = new Opus_File($fileId);
        $xmlCache = new Opus_Model_Xml_Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $file->setLabel('Volltextdokument (geändert)');
        $file->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
        unlink($filepath);
    }

    /**
     * Tests file upload date.
     * OPUSVIER-3190.
     */
    public function testServerDateSubmittedSetForNewFiles()
    {
        $filepath = $this->createTestFile('foo.pdf');
        $file = new Opus_File();
        $file->setPathName(basename($filepath));
        $file->setTempFile($filepath);

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->addFile($file);

        $docId = $doc->store();

        $dateNow = new Opus_Date();
        $dateNow->setNow();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();

        $file = $files[0];

        $this->assertNotNull($file->getServerDateSubmitted());

        // Prüfen, ob aktuelle Zeit gesetzt wurde
        $now = $dateNow->getUnixTimestamp();

        $timeDiff = $now - $file->getServerDateSubmitted()->getUnixTimestamp();

        // The choice of a 5 second limit is arbitrary, 1 second difference was observed on CI-System
        $this->assertTrue($timeDiff < 5, "ServerDateSubmitted differs from NOW by $timeDiff seconds");
    }

    /**
     * ServerDateSubmitted should not alter with changes in file properties.
     * OPUSVIER-3190.
     */
    public function testServerDateSubmittedNotChangedOnStore()
    {
        $filepath = $this->createTestFile('test.file');
        $file = new Opus_File();
        $file->setPathName(basename($filepath));
        $file->setTempFile($filepath);

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->addFile($file);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();
        $earlierDate = $files[0]->getServerDateSubmitted()->__toString();

        sleep(2);
        $files[0]->setComment(rand());
        $doc->store();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();
        $this->assertEquals($files[0]->getServerDateSubmitted()->__toString(), $earlierDate);
    }

    public function testServerDateSubmittedNotSetForOldFiles()
    {
        $filepath = $this->createTestFile('test.file');
        $file = new Opus_File();
        $file->setPathName(basename($filepath));
        $file->setTempFile($filepath);

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->addFile($file);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();

        $this->assertNotNull($files[0]->getServerDateSubmitted());

        $files[0]->setServerDateSubmitted(null);
        $doc->store();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();
        $this->assertNull($files[0]->getServerDateSubmitted(), 'ServerDateSubmitted should not be set for old files.');
    }

    public function testSortOrderField()
    {
        $filepath = $this->createTestFile('foo.pdf');
        $file = new Opus_File();
        $file->setPathName(basename($filepath));
        $file->setTempFile($filepath);
        $file->setSortOrder(1);

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->addFile($file);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();

        $this->assertEquals($files[0]->getSortOrder(), 1);
    }

    private function createTestFile($filename)
    {
        $config = Zend_Registry::get('Zend_Config');
        if (! isset($config->workspacePath)) {
            throw new Exception("config key 'workspacePath' not defined in config file");
        }

        $path = $config->workspacePath . DIRECTORY_SEPARATOR . uniqid();
        mkdir($path, 0777, true);
        $filepath = $path . DIRECTORY_SEPARATOR . $filename;
        touch($filepath);
        $this->assertTrue(is_readable($filepath));
        return $filepath;
    }

    public function testStoreDocumentWithFileAndOaiVisibleFalse()
    {
        $filePath = $this->createTestFile('test.txt');

        $file = new Opus_File();
        $file->setPathName(basename($filePath));
        $file->setVisibleInOai(false);
        $file->setTempFile($filePath);

        $doc = new Opus_Document();
        $doc->addFile($file);

        $docId = $doc->store();
    }

    /**
     * Testing return values for GetVisibleInOai.
     *
     * When value false is stored it gets converted into an empty string. The framework therefore converts boolean
     * values into integers first.
     */
    public function testSetGetVisibleInOai()
    {
        $filePath = $this->createTestFile('test.txt');

        $file = new Opus_File();
        $file->setPathName(basename($filePath));
        $file->setTempFile($filePath);

        $doc = new Opus_Document();

        $file->setVisibleInOai(0);
        $this->assertEquals(0, $file->getVisibleInOai());
        $this->assertEquals(false, $file->getVisibleInOai());

        $file->setVisibleInOai(1);
        $this->assertEquals(1, $file->getVisibleInOai());

        $file->setVisibleInOai(true);
        $this->assertEquals(true, $file->getVisibleInOai());

        $doc->addFile($file);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $files = $doc->getFile();
        $file = $files[0];

        if ($file->getVisibleInOai()) {
        } else {
            $this->fail('Did not recognize value true.');
        }

        // $this->assertTrue($file->getVisibleInOai()); // return value is string '1'
        $this->assertEquals(1, $file->getVisibleInOai());
        $this->assertEquals(true, $file->getVisibleInOai());

        $file->setVisibleInOai(false);
        $this->assertEquals(false, $file->getVisibleInOai());

        $doc->store();

        $doc = new Opus_Document($docId);

        $files = $doc->getFile();
        $file = $files[0];

        if ($file->getVisibleInOai()) {
            $this->fail('Did not recognize value false.');
        }

        // $this->assertFalse($file->getVisibleInOai()); // return value is string '0'
        $this->assertEquals(0, $file->getVisibleInOai());
        // $this->assertEquals(false, $file->getVisibleInOai()); // return value is string '0'
    }

    public function testVisibleInOaiDefaultNotConfigured()
    {
        $filePath = $this->createTestFile('test.txt');

        $file = new Opus_File();
        $file->setPathName(basename($filePath));
        $file->setTempFile($filePath);

        $doc = new Opus_Document();

        $doc->addFile($file);
        $doc = new Opus_Document($doc->store()); // reload stored document

        $file = $doc->getFile(0);

        $this->assertInstanceOf('Opus_File', $file);
        $this->assertEquals(1, $file->getVisibleInOai());
    }

    public function testVisibleInOaiDefaultConfigurable()
    {
        Zend_Registry::get('Zend_Config')->merge(new Zend_Config([
            'files' => ['visibleInOaiDefault' => 0]
        ]));

        $filePath = $this->createTestFile('test.txt');

        $file = new Opus_File();
        $file->setPathName(basename($filePath));
        $file->setTempFile($filePath);

        $doc = new Opus_Document();

        $doc->addFile($file);

        $doc = new Opus_Document($doc->store()); // reload stored document

        $file = $doc->getFile(0);

        $this->assertInstanceOf('Opus_File', $file);
        $this->assertEquals(0, $file->getVisibleInOai());

        Zend_Registry::get('Zend_Config')->merge(new Zend_Config([
            'files' => ['visibleInOaiDefault' => 1]
        ]));

        $filePath = $this->createTestFile('test.txt');

        $file = new Opus_File();
        $file->setPathName(basename($filePath));
        $file->setTempFile($filePath);

        $doc = new Opus_Document();

        $doc->addFile($file);

        $doc = new Opus_Document($doc->store()); // reload stored document

        $file = $doc->getFile(0);

        $this->assertInstanceOf('Opus_File', $file);
        $this->assertEquals(1, $file->getVisibleInOai());
    }
}
