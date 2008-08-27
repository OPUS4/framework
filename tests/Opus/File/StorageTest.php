<?php
/**
 *
 */


/**
 * Test cases for class Opus_File_Storage.
 *
 * @category Tests
 * @package  Opus_File
 *
 * @group OpusFileStorageTest
 */
class Opus_File_StorageTest extends PHPUnit_Framework_TestCase {

    protected $tmp_dir = null;

    private function rm_recursive($filepath) {
        if ((is_dir($filepath) === true) and (is_link($filepath) === false)) {
            if ($dh = opendir($filepath)) {
                while (($sf = readdir($dh)) !== false) {
                    if ($sf === '.' or $sf === '..') {
                        continue;
                    }
                    if (! $this->rm_recursive($filepath . DIRECTORY_SEPARATOR . $sf)) {
                        throw new Exception($filepath . DIRECTORY_SEPARATOR . $sf . ' could not be deleted.');
                    }
                }
                closedir($dh);
            }
            return @rmdir($filepath);
        }
        return @unlink($filepath);
    }

    public function setUp() {
        $this->tmp_dir = '/tmp/Opus_Test';
        $this->rm_recursive($this->tmp_dir);
        mkdir($this->tmp_dir);
        TestHelper::clearTable('document_files');
    }

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
        $path = $this->tmp_dir . '/Opus';
        $this->setExpectedException('Opus_File_Exception', 'Committed value is not a valid directory name.');
        $this->assertEquals(true, Opus_File_Storage::getInstance($path) instanceof Opus_File_Storage);
    }

    /**
     * Test if directory is writeable
     *
     */
    public function testInitStorageWithReadOnlyDirectory() {
        $readonlydir = $this->tmp_dir . '/Readonly';
        // Create a readonly directory
        mkdir($readonlydir, 0554, true);
        $this->setExpectedException('Opus_File_Exception', 'Repository directory is not writable.');
        $storage = Opus_File_Storage::getInstance($readonlydir);
    }

    /**
     * Try to store with an empty array.
     *
     */
    public function testStoringWithEmptyArray() {
        $fileInformation = array();
        $this->setExpectedException('InvalidArgumentException', 'Array with file information is empty.');
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
                'sourcePath' => '/tmp/test',
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
                'sourcePath' => '/tmp/test',
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
                'sourcePath' => '/tmp/test',
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
                'sourcePath' => '/tmp/test',
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
                'sourcePath' => '/tmp/test',
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
                'sourcePath' => '/tmp/test',
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
                'sourcePath' => '/tmp/test',
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
                'sourcePath' => '/tmp/test',
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
     */
    public function testStoringWithFileNotExists() {
        $fileInformation = array(
            'sourcePath' => '/tmp/test' . rand(0, 128) . '.pdf',
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
     * Test for storing data
     *
     */
    public function testStoringData() {
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
        $this->assertGreaterThan(1, $storage->store($fileInformation));
    }

    /**
     * Test to remove inserted data
     *
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
        $this->assertFileNotExists($this->tmp_dir . '/2008/1/e.pdf');
    }

    /**
     * Test to remove inserted data with invalid identifier
     *
     */
    public function testRemovingDataWithInvalidIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $storage->remove('test');
    }

    /**
     * Test to remove inserted data with non-existing identifier
     *
     */
    public function testRemovingDataWithNonExistingIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('Opus_File_Exception', 'Informations about specific entry not found.');
        $storage->remove(0);
    }

    /**
     * Test to get file path
     *
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
        $this->assertEquals('2008/1/e.pdf', $storage->getPath($id));
    }

    /**
     * Test to get file path with invalid identifier
     *
     */
    public function testGetPathWithInvalidIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $data = $storage->getPath('0');
    }

    /**
     * Test to get file path with non-existing identifier
     *
     */
    public function testGetPathWithNonExistingIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('Opus_File_Exception', 'Could not found any data to specific entry.');
        $data = $storage->getPath(0);
    }

    /**
     * Test to all file identifiers
     *
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
     */
    public function testGetAllFileIdsWithInvalidIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $data = $storage->getAllFileIds('0');
    }

    /**
     * Test to get all file idenitifiers with non-existing document identifier
     *
     */
    public function testGetAllFileIdsWithNonExistingIdentifier() {
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $this->assertEquals(0, count($storage->getAllFileIds(0)));
    }
}
