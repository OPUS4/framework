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
 * @category    Framework
 * @package     Opus_File
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Generate, retrieve and verify a certain hash value for a specific file
 *
 * @category Framework
 * @package  Opus_File
 */
class Opus_File_Hash {

    /**
     * Contains supported hash functions.
     * Key contains method name, value contains array with id number and php function name.
     *
     * @var array
     */
    private static $hash_methods = array(
        'md5' => 1,
        'sha1' => 2,
        'sha256' => 3
        );

    /**
     * Holds the storage information.
     *
     * @var Opus_Storage_File
     */
    protected $storage = null;

    /**
     * Initialise the file hash component.
     *
     * @param Opus_File_Storage $storage Connection to current repository.
     * @throws Opus_File_Exception Thrown on error.
     */
    public function __construct(Opus_File_Storage $storage) {
        $this->storage = $storage;
    }

    /**
     * Generates a hash value for a give file and method.
     *
     * @param string $filename Hold file name
     * @param string $method   Hold method name
     * @throws InvalidArgumentException Thrown on invalid method name.
     * @throws Opus_File_Exception      Thrown if hash generation failured.
     * @return array
     */
    private function generate_hash($filename, $method) {
        if (array_key_exists($method, self::$hash_methods) === false) {
                throw new InvalidArgumentException('Non supported hash function requested.');
        }
        if (file_exists($filename) === false) {
            throw new Opus_File_Exception('File for hashing not found.');
        }
        $hashvalue = hash_file($method, $filename);
        $result = array(
                'hashvalue_id' => self::$hash_methods[$method],
                'hashvalue' => $hashvalue
                );
        return $result;
    }

    /**
     * Generate a hash value for a given file
     *
     * @param integer $fileId Identifier of file record.
     * @param string  $method Method name of hash function.
     * @throws InvalidArgumentException Thrown on wrong argument.
     * @return void
     */
    public function generate($fileId, $method) {
        if (is_int($fileId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        // Get file name
        $filedata = $this->storage->getRepositoryPath()
                  . DIRECTORY_SEPARATOR
                  . $this->storage->getPath($fileId);
        // Generate hash values
        $hash_data = $this->generate_hash($filedata, $method);
        $hashdb_data = array(
            'file_hashvalues_id' => $hash_data['hashvalue_id'],
            'document_files_id' => $fileId,
            'hash_type' => $method,
            'hash_value' => $hash_data['hashvalue']
            );
        // Wrote hash meta values to database
        $hashdb = new Opus_File_HashvaluesModel();
        $hashdb->insert($hashdb_data);
    }

    /**
     * Returns all hash values for a specific file
     *
     * @param integer $fileId Identifier of file record.
     * @throws InvalidArgumentException Thrown on wrong argument.
     * @return array
     */
    public function get($fileId) {
        if (is_int($fileId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        $hashdb = new Opus_File_HashvaluesModel();
        $select = $hashdb->select()->where('document_files_id = ?' , $fileId);
        $result = $hashdb->fetchAll($select)->toArray();
        return $result;
    }

    /**
     * Check a hash value of a specific file
     *
     * @param integer $fileId Identifier of file record.
     * @param string  $method Method name of hash function.
     * @throws InvalidArgumentException Thrown on invalid argument.
     * @throws Opus_File_Exception      Thrown on unexpected data.
     * @return boolean
     */
    public function verify($fileId, $method) {
        if (is_int($fileId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        // Get file name
        $filedata = $this->storage->getRepositoryPath()
                  . DIRECTORY_SEPARATOR
                  . $this->storage->getPath($fileId);
        // Generate hash values
        $hash_data = $this->generate_hash($filedata, $method);
        $hashdb = new Opus_File_HashvaluesModel();
        // Get hash information from database
        $row = $hashdb->find($hash_data['hashvalue_id'], $fileId);
        if ($row->count() === 0) {
            throw new Opus_File_Exception('Could not retrieve necessary meta data.');
        }
        if ($row->current()->hash_value === $hash_data['hashvalue']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Return all supported hash functions of this class.
     *
     * @return array
     */
    public function getHashMethods() {
        return array_keys(self::$hash_methods);
    }

}