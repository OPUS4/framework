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
 * Test cases for class Opus_File_Hash.
 *
 * @category    Tests
 * @package     Opus_File
 *
 * @group       OpusFileHashTest
 */
class Opus_File_HashTest extends PHPUnit_Framework_TestCase {

    /**
     * Holds directory information
     *
     * @var string
     */
    protected $tmp_dir = null;

    /**
     * Holds document id.
     *
     * @var integer
     */
    protected $doc_id = null;

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
        TestHelper::clearTable('file_hashvalues');
        TestHelper::clearTable('document_files');
        TestHelper::clearTable('documents');
        $documents = new Opus_Db_Documents();
        $server_date = new Zend_Date(Zend_Date::now());
        $document_data = array(
            'completed_year' => 2008,
            'document_type' => 'article',
            'reviewed' => 'open',
            'server_date_published' => $server_date->getIso()
            );
        $this->doc_id = (int) $documents->insert($document_data);
    }

    /**
     * Clean up test environment
     *
     * @return void
     */
    public function tearDown() {
        $this->rm_recursive($this->tmp_dir);
    }

    /**
     * Test if a hash generator can be initialised.
     *
     * @return void
     */
    public function testInitHash() {
        $hash = new Opus_File_Hash(Opus_File_Storage::getInstance($this->tmp_dir));
    }

    /**
     * Test to store a hash value
     *
     * @return void
     */
    public function testStoreHash() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $count_pre = (int) $dba->query('SELECT COUNT(*) FROM file_hashvalues')->fetchColumn(0);
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => $this->doc_id,
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
        $hash = new Opus_File_Hash($storage);
        $hash->generate($id, 'md5');
        $count_post = (int) $dba->query('SELECT COUNT(*) FROM file_hashvalues')->fetchColumn(0);
        $this->assertGreaterThan($count_pre, $count_post, 'No new records in database.');
    }

    /**
     * Test if an invalid identifier would be accepted.
     *
     * @return void
     */
    public function testStoreHashWithInvalidIdentifier() {
        $hash = new Opus_File_Hash(Opus_File_Storage::getInstance($this->tmp_dir));
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $hash->generate('1', 'md5');
    }

    /**
     * Test if an invalid hash method would be accepted.
     *
     * @return void
     */
    public function testStoreHashWithInvalidHashMethod() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => $this->doc_id,
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
        $hash = new Opus_File_Hash($storage);
        $this->setExpectedException('InvalidArgumentException', 'Non supported hash function requested.');
        $hash->generate($id, '');
    }

    /**
     * Test to generate a hash value for a non-existing file.
     *
     * @return void
     */
    public function testStoreHashWithoutFile() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => $this->doc_id,
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
        $hash = new Opus_File_Hash($storage);
        unlink($this->tmp_dir
               . DIRECTORY_SEPARATOR
               . $fileInformation['publishYear']
               . DIRECTORY_SEPARATOR
               . $this->doc_id
               . DIRECTORY_SEPARATOR
               . $fileInformation['fileName']);
        $this->setExpectedException('Opus_File_Exception', 'File for hashing not found.');
        $hash->generate($id, 'md5');
    }

    /**
     * Test of verifying stored and latest hash sums of a file.
     *
     * @return void
     */
    public function testHashVerify() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => $this->doc_id,
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
        $hash = new Opus_File_Hash($storage);
        $hash->generate($id, 'md5');
        $this->assertEquals(true, $hash->verify($id, 'md5'));
    }

    /**
     * Test if verifying method accept a invalid identifier
     *
     * @return void
     */
    public function testHashVerifyWithInvalidIdentifier() {
        $hash = new Opus_File_Hash(Opus_File_Storage::getInstance($this->tmp_dir));
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $hash->verify('WRONG', 'sha1');
    }

    /**
     * Test verifying of a hash sum of a changed file.
     *
     * @return void
     */
    public function testHashVerifyWithWrongChecksum() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => $this->doc_id,
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
        $hash = new Opus_File_Hash($storage);
        $hash->generate($id, 'md5');
        file_put_contents($this->tmp_dir . DIRECTORY_SEPARATOR . $storage->getPath($id), 'asdfasdf');
        $this->assertEquals(false, $hash->verify($id, 'md5'));
    }

    /**
     * Test for retrieving a empty hash value from database through for example wrong method
     *
     * @return void
     */
    public function testHashVerifyWithWrongMethod() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => $this->doc_id,
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
        $hash = new Opus_File_Hash($storage);
        $hash->generate($id, 'md5');
        $this->setExpectedException('Opus_File_Exception', 'Could not retrieve necessary meta data.');
        $hash->verify($id, 'sha1');
    }

    /**
     * Test of available hash methods
     *
     * @return void
     */
    public function testHashGetHashMethods() {
        $hash = new Opus_File_Hash(Opus_File_Storage::getInstance($this->tmp_dir));
        $this->assertNotNull($hash->getHashMethods());
    }

    /**
     * Test for retrieving hash values from the database.
     *
     * @return void
     */
    public function testRetrieveHashValue() {
        $tempfilename = tempnam($this->tmp_dir, 'OPUS_');
        $storage = Opus_File_Storage::getInstance($this->tmp_dir);
        $fileInformation = array(
            'sourcePath' => $tempfilename,
            'documentId' => $this->doc_id,
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
        $hash = new Opus_File_Hash($storage);
        $hash->generate($id, 'md5');
        $this->assertNotNull($hash->get($id));
    }

    /**
     * Test for a valid identifier on retrieving a hash value.
     *
     * @return void
     */
    public function testRetrieveHashValueWithInvalidIdentifier() {
        $hash = new Opus_File_Hash(Opus_File_Storage::getInstance($this->tmp_dir));
        $this->setExpectedException('InvalidArgumentException', 'Identifier is not an integer value.');
        $hash->get('WRONG');
    }
}
