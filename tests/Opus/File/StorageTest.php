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
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_File_Storage.
 *
 * @category    Tests
 * @package     Opus_File
 *
 * @group       OpusFileStorageTest
 */
class Opus_File_StorageTest extends PHPUnit_Framework_TestCase {

    /**
     * Holds directory information
     *
     * @var string
     */
    protected $tmp_dir = null;

    /**
     * Delete recurisvely directories and files.
     *
     * @param string $filepath Contains path for deleting.
     * @throws Exception Thrown if deletion of a file or directory is not possible.
     * @return boolean
     */
    private function rm_recursive($filepath) {
        if ((is_dir($filepath) === true) and (is_link($filepath) === false)) {
            $dh = opendir($filepath);
            if ($dh !== false) {
                while (($sf = readdir($dh)) !== false) {
                    if ($sf === '.' or $sf === '..') {
                        continue;
                    }
                    if ($this->rm_recursive($filepath . DIRECTORY_SEPARATOR . $sf) === false) {
                        throw new Exception($filepath . DIRECTORY_SEPARATOR . $sf . ' could not be deleted.');
                    }
                }
                closedir($dh);
            }
            return @rmdir($filepath);
        }
        return @unlink($filepath);
    }

    /**
     * Setup test environment
     *
     * @return void
     */
    public function setUp() {
        if (empty($_ENV['TMP']) === false) {
            $this->tmp_dir = $_ENV['TMP'];
        } else if (empty($_ENV['TEMP']) === false) {
            $this->tmp_dir = $_ENV['TEMP'];
        } else {
            $this->tmp_dir = '/tmp';
        }
        $this->tmp_dir .= DIRECTORY_SEPARATOR . 'Opus_Test';
        $this->rm_recursive($this->tmp_dir);
        mkdir($this->tmp_dir);
        TestHelper::clearTable('document_files');
    }

    /**
     * Cleanup test environment
     *
     * @return void
     */
    public function tearDown() {
        $this->rm_recursive($this->tmp_dir);
    }

    /**
     * Test if a repository can be initialised.
     *
     * @return void
     */
    public function testInitStorage() {
        $path = $this->tmp_dir;
        $this->assertEquals(true, Opus_File_Storage::getInstance($path) instanceof Opus_File_Storage);
    }

    /**
     * Test if a repository without or wrong parameter could be instantiated
     *
     * @return void
     */
    public function testInitStorageWithoutDirectory() {
        $this->setExpectedException('InvalidArgumentException', 'Path value is empty.');
        Opus_File_Storage::getInstance(null);
    }

    /**
     * Test if a repository can be initialised with an invalid directory.
     *
     * @return void
     */
    public function testInitStorageWithInvalidDirectory() {
        $path = $this->tmp_dir . DIRECTORY_SEPARATOR . 'Opus';
        $this->setExpectedException('Opus_File_Exception', 'Committed value is not a valid directory name.');
        $this->assertEquals(true, Opus_File_Storage::getInstance($path) instanceof Opus_File_Storage);
    }

    /**
     * Test if directory is writeable
     *
     * @return void
     */
    public function testInitStorageWithReadOnlyDirectory() {
        $readonlydir = $this->tmp_dir . DIRECTORY_SEPARATOR . 'Readonly';
        // Create a readonly directory
        mkdir($readonlydir, 0554, true);
        $this->setExpectedException('Opus_File_Exception', 'Repository directory is not writable.');
        $storage = Opus_File_Storage::getInstance($readonlydir);
    }

    /**
     * Try to store with an empty array.
     *
     * @return void
     */
    public function testStoringWithEmptyArray() {
        $fileInformation = array();
        $this->setExpectedException('InvalidArgumentException', 'Array with file information is empty.');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
    }

    /**
     * Contains several test cases for wrong array keys.
     *
     * @return array
     */
    public function providerBadArrayKeys() {
        return
            array(
                array(
                    array(
                        'documentId' => 1,
                        'fileName' => 'e.pdf',
                        'sortOrder' => 1,
                        'publishYear' => 2008,
                        'label' => 'Test',
                        'type' => 'application/pdf',
                        'language' => 'english',
                        'mimeType' => 'pdf'
                    ),
                    'sourcePath does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'fileName' => 'e.pdf',
                        'sortOrder' => 1,
                        'publishYear' => 2008,
                        'label' => 'Test',
                        'type' => 'application/pdf',
                        'language' => 'english',
                        'mimeType' => 'pdf'
                    ),
                    'documentId does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'documentId' => 1,
                        'sortOrder' => 1,
                        'publishYear' => 2008,
                        'label' => 'Test',
                        'type' => 'application/pdf',
                        'language' => 'english',
                        'mimeType' => 'pdf'
                    ),
                    'fileName does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'documentId' => 1,
                        'fileName' => 'e.pdf',
                        'publishYear' => 2008,
                        'label' => 'Test',
                        'type' => 'application/pdf',
                        'language' => 'english',
                        'mimeType' => 'pdf'
                    ),
                    'sortOrder does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'documentId' => 1,
                        'fileName' => 'e.pdf',
                        'sortOrder' => 1,
                        'label' => 'Test',
                        'type' => 'application/pdf',
                        'language' => 'english',
                        'mimeType' => 'pdf'
                    ),
                    'publishYear does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'documentId' => 1,
                        'fileName' => 'e.pdf',
                        'sortOrder' => 1,
                        'publishYear' => 2008,
                        'type' => 'application/pdf',
                        'language' => 'english',
                        'mimeType' => 'pdf'
                    ),
                    'label does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'documentId' => 1,
                        'fileName' => 'e.pdf',
                        'sortOrder' => 1,
                        'publishYear' => 2008,
                        'label' => 'Test',
                        'language' => 'english',
                        'mimeType' => 'pdf'
                    ),
                    'type does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'documentId' => 1,
                        'fileName' => 'e.pdf',
                        'sortOrder' => 1,
                        'publishYear' => 2008,
                        'label' => 'Test',
                        'type' => 'application/pdf',
                        'mimeType' => 'pdf'
                    ),
                    'language does not exists.'
                ),
                array(
                    array(
                        'sourcePath' => '/tmp/test-data',
                        'documentId' => 1,
                        'fileName' => 'e.pdf',
                        'sortOrder' => 1,
                        'publishYear' => 2008,
                        'label' => 'Test',
                        'type' => 'application/pdf',
                        'language' => 'english'
                    ),
                    'mimeType does not exists.'
                )
            );
    }

    /**
     * Test if keys a correct.
     *
     * @param array  $fileInformation Contains an array with invalid keys
     * @param string $msg             Error message
     * @return void
     * @dataProvider providerBadArrayKeys
     */
    public function testStoringWithInvalidArrayKeys(array $fileInformation, $msg) {
        $this->setExpectedException('InvalidArgumentException', $msg);
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
    }

    /**
     * Contains several test cases
     *
     * @return array
     */
    public function providerBadArrayInformation() {
        return array(
            array(
                'sourcePath' => '',
                'documentId' => '',
                'fileName' => '',
                'sortOrder' => '',
                'publishYear' => '',
                'label' => '',
                'type' => '',
                'language' => '',
                'mimeType' => '',
                'sourcePath is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => '',
                'fileName' => '',
                'sortOrder' => '',
                'publishYear' => '',
                'label' => '',
                'type' => '',
                'language' => '',
                'mimeType' => '',
                'documentId is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => '1',
                'fileName' => '',
                'sortOrder' => '',
                'publishYear' => '',
                'label' => '',
                'type' => '',
                'language' => '',
                'mimeType' => '',
                'fileName is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => 1,
                'fileName' => 'e.pdf',
                'sortOrder' => '',
                'publishYear' => '',
                'label' => '',
                'type' => '',
                'language' => '',
                'mimeType' => '',
                'sortOrder is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => 1,
                'fileName' => 'e.pdf',
                'sortOrder' => 1,
                'publishYear' => '',
                'label' => '',
                'type' => '',
                'language' => '',
                'mimeType' => '',
                'publishYear is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => 1,
                'fileName' => 'e.pdf',
                'sortOrder' => 1,
                'publishYear' => 2008,
                'label' => '',
                'type' => '',
                'language' => '',
                'mimeType' => '',
                'label is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => 1,
                'fileName' => 'e.pdf',
                'sortOrder' => 1,
                'publishYear' => 2008,
                'label' => 'Test',
                'type' => '',
                'language' => '',
                'mimeType' => '',
                'type is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => 1,
                'fileName' => 'e.pdf',
                'sortOrder' => 1,
                'publishYear' => 2008,
                'label' => 'Test',
                'type' => 'application/pdf',
                'language' => '',
                'mimeType' => '',
                'language is empty.'
                ),
            array(
                'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test',
                'documentId' => 1,
                'fileName' => 'e.pdf',
                'sortOrder' => 1,
                'publishYear' => 2008,
                'label' => 'Test',
                'type' => 'application/pdf',
                'language' => 'english',
                'mimeType' => '',
                'mimeType is empty.'
                )
            );
    }

    /**
     * Serveral tests for checking array values
     *
     * @param string $sourcePath  Contain path to source file
     * @param string $documentId  Contain document identifier
     * @param string $fileName    Contain new file name
     * @param string $sortOrder   Sort order
     * @param string $publishYear Year of publishing
     * @param string $label       Comment for file
     * @param string $type        File type
     * @param string $language    Language of file
     * @param string $mimetype    Mimetype of file
     * @param string $msg         Contain compair message
     * @return void
     * @dataProvider providerBadArrayInformation
     */
    public function testStoringWithInvalidInformations($sourcePath, $documentId, $fileName, $sortOrder, $publishYear, $label, $type, $language, $mimetype, $msg) {
        $fileInformation = array(
            'sourcePath' => $sourcePath,
            'documentId' => $documentId,
            'fileName' => $fileName,
            'sortOrder' => $sortOrder,
            'publishYear' => $publishYear,
            'label' => $label,
            'type' => $type,
            'language' => $language,
            'mimeType' => $mimetype
            );
        $this->setExpectedException('InvalidArgumentException', $msg);
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
    }

    /**
     * Test if uploaded file a really uploaded file
     *
     * @return void
     */
    public function testStoringWithFileNotExists() {
        $fileInformation = array(
            'sourcePath' => $this->tmp_dir . DIRECTORY_SEPARATOR . 'test' . rand(0, 128) . '.pdf',
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        $this->setExpectedException('Opus_File_Exception', 'Import file does not exists.');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
    }

    /**
     * Test for proper file permissions
     *
     * @return void
     */
    public function testStoringWithInvalidFilePermission() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        chmod($tempfilename, 0444);
        $this->setExpectedException('Opus_File_Exception', 'Import file is not writeable.');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
        unlink($tempfilename);
    }

    /**
     * Test for an non-integer documentId value
     *
     * @return void
     */
    public function testStoringWithInvalidDocumentId() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => '1',
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        $this->setExpectedException('InvalidArgumentException', 'documentId is not an integer value.');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
        unlink($tempfilename);
    }

    /**
     * Test for an non-integer sortOrder value
     *
     * @return void
     */
    public function testStoringWithInvalidSortOrder() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => '1',
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        $this->setExpectedException('InvalidArgumentException', 'sortOrder is not an integer value.');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
        unlink($tempfilename);
    }

    /**
     * Test for an non-integer publishYear value
     *
     * @return void
     */
    public function testStoringWithInvalidPublishYear() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => '2008',
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        $this->setExpectedException('InvalidArgumentException', 'publishYear is not an integer value.');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
        unlink($tempfilename);
    }

    /**
     * Tried to store a file where parent directory is read only.
     *
     * @return void
     */
    public function testStoringWithReadonlyDestinationDirectory() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        mkdir($this->tmp_dir . DIRECTORY_SEPARATOR . $fileInformation['publishYear'], 0555, true);
        $this->setExpectedException('Opus_File_Exception', 'Error during inserting meta data or file movement: Could not create destination directory.');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $storage->store($fileInformation);
        unlink($tempfilename);
    }


    /**
     * Test for storing data
     *
     * @return void
     */
    public function testStoringData() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $count_pre = (int) $dba->query('SELECT COUNT(*) FROM document_files')->fetchColumn(0);
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        file_put_contents($tempfilename, 'blablub');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $id = $storage->store($fileInformation);
        $count_post = (int) $dba->query('SELECT COUNT(*) FROM document_files')->fetchColumn(0);
        $this->assertGreaterThan($count_pre, $count_post, 'No new records in database.');
    }

    /**
     * Test to remove inserted data
     *
     * @return void
     */
    public function testRemovingData() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        file_put_contents($tempfilename, 'blablub');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $id = $storage->store($fileInformation);
        $storage->remove($id);
        $this->assertFileNotExists($this->tmp_dir
                . DIRECTORY_SEPARATOR
                . $fileInformation['publishYear']
                . DIRECTORY_SEPARATOR
                . $fileInformation['documentId']
                . DIRECTORY_SEPARATOR
                . $fileInformation['fileName']);
    }

    /**
     * Test to remove inserted data with invalid identifier
     *
     * @return void
     */
    public function testRemovingDataWithInvalidIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $storage->remove('test');
    }

    /**
     * Test to remove inserted data with non-existing identifier
     *
     * @return void
     */
    public function testRemovingDataWithNonExistingIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('Opus_File_Exception', 'Informations about specific entry not found.');
        $storage->remove(0);
    }

    /**
     * Tried to remove a database entry without existing file.
     *
     * @return void
     */
    public function testRemovingDataWithoutExistingFile() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        file_put_contents($tempfilename, 'blablub');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $id = $storage->store($fileInformation);
        unlink($this->tmp_dir . DIRECTORY_SEPARATOR . $fileInformation['publishYear'] . DIRECTORY_SEPARATOR . $fileInformation['documentId'] . DIRECTORY_SEPARATOR . $fileInformation['fileName']);
        $this->setExpectedException('Opus_File_Exception', 'Error during deleting meta data or file: unlink(/tmp/Opus_Test/2008/1/e.pdf): No such file or directory');
        $storage->remove($id);
    }

    /**
     * Test to get file path
     *
     * @return void
     */
    public function testGetPath() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        file_put_contents($tempfilename, 'blablub');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $id = $storage->store($fileInformation);
        $this->assertEquals(
            $fileInformation['publishYear']
            . DIRECTORY_SEPARATOR
            . $fileInformation['documentId']
            . DIRECTORY_SEPARATOR
            . $fileInformation['fileName'], $storage->getPath($id));
    }

    /**
     * Test to get file path with invalid identifier
     *
     * @return void
     */
    public function testGetPathWithInvalidIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $data = $storage->getPath('0');
    }

    /**
     * Test to get file path with non-existing identifier
     *
     * @return void
     */
    public function testGetPathWithNonExistingIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('Opus_File_Exception', 'Could not found any data to specific entry.');
        $data = $storage->getPath(0);
    }

    /**
     * Test to all file identifiers
     *
     * @return void
     */
    public function testGetAllFileIdentifiers() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e.pdf',
            'sortOrder' => 1,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        file_put_contents($tempfilename, 'blablub');
        $id = $storage->store($fileInformation);
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => 1,
            'fileName' => 'e2.pdf',
            'sortOrder' => 2,
            'publishYear' => 2008,
            'label' => 'Test',
            'type' => 'application/pdf',
            'language' => 'english',
            'mimeType' => 'pdf'
            );
        file_put_contents($tempfilename, 'blabluba');
        $id = $storage->store($fileInformation);
        $this->assertEquals(2, count($storage->getAllFileIds(1)));
    }

    /**
     * Test to get all file idenitifiers with invalid document identifier
     *
     * @return void
     */
    public function testGetAllFileIdsWithInvalidIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $data = $storage->getAllFileIds('0');
    }

    /**
     * Test to get all file idenitifiers with non-existing document identifier
     *
     * @return void
     */
    public function testGetAllFileIdsWithNonExistingIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->assertEquals(0, count($storage->getAllFileIds(0)));
    }

    public function testCheckRepositoryPath() {
        $this->assertEquals($this->tmp_dir,  Opus_File_Storage::getInstance($this->tmp_dir)->getRepositoryPath());
    }
}
