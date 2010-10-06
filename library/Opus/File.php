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
 * @package     Opus_Model
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for files in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Abstract
 */
class Opus_File extends Opus_Model_Dependent_Abstract {

    /**
     * Holds storage object.
     *
     * @var Opus_Storage_File
     */
    private $_storage;
    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'document_id';
    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_DocumentFiles';
    /**
     * The file models external fields, i.e. those not mapped directly to the
     * Opus_Db_DocumentFiles table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
        'HashValue' => array(
            'model' => 'Opus_HashValues'
        ),
    );

    /**
     * Initialize model with the following fields:
     * - PathName
     * - SortOrder
     * - Label
     * - FileType
     * - MimeType
     * - Language
     *
     * @return void
     */
    protected function _init() {
        $filepathname = new Opus_Model_Field('PathName');
        $filepathname->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $filelabel = new Opus_Model_Field('Label');
        $mimetype = new Opus_Model_Field('MimeType');

        $filelanguage = new Opus_Model_Field('Language');
        if (Zend_Registry::isRegistered('Available_Languages') === true) {
            $filelanguage->setDefault(Zend_Registry::get('Available_Languages'));
        }
        $filelanguage->setSelection(true);

        $tempfile = new Opus_Model_Field('TempFile');

        $filesize = new Opus_Model_Field('FileSize');
        $filesize->setMandatory(true);

        $visible_in_frontdoor = new Opus_Model_Field('VisibleInFrontdoor');
        $visible_in_oai = new Opus_Model_Field('VisibleInOai');

        $hashvalue = new Opus_Model_Field('HashValue');
        $hashvalue->setMandatory(true)
                ->setMultiplicity('*');

        $document_id = new Opus_Model_Field('DocumentId');

        $this->addField($filepathname)
                ->addField($filelabel)
                ->addField($mimetype)
                ->addField($filelanguage)
                ->addField($tempfile)
                ->addField($filesize)
                ->addField($visible_in_frontdoor)
                ->addField($visible_in_oai)
                ->addField($hashvalue)
                ->addField($document_id);

        $config = Zend_Registry::get('Zend_Config');
        $workspaceFiles = $config->workspacePath . "/files";
        $this->_storage = new Opus_Storage_File($workspaceFiles);
    }

    /**
     * checks if the file exists physically
     * 
     * @return boolean false if the file does not exist, true if it exists
     */
    public function exists() {
        return file_exists($this->getPath());

    }

    /**
     * Get full path of destination file.
     */
    public function getPath() {
        $storageDirectory = $this->_storage->getWorkingDirectory();
        return $storageDirectory . $this->getParentId() . DIRECTORY_SEPARATOR . $this->getPathName();

    }

    /**
     * Copy the uploaded file to it's final destination.
     *
     * Moves or copies uploaded file depending on whether it has been
     * uploaded by PHP process or was privided via filesystem directly.
     *
     * Determine and set file mime type.
     *
     * @see Opus_Model_AbstractDb::_preStore()
     */
    protected function _preStore() {
        $result = parent::_preStore();

        if (isset($result)) {
            return $result;
        }

        $documentPath = $this->getParentId() . DIRECTORY_SEPARATOR;
        $target = $documentPath . $this->getPathName();

        $tempFile = $this->getTempFile();
        if (false === empty($tempFile)) {

            $this->_storage->createSubdirectory($documentPath);
            $this->_storage->copyExternalFile($tempFile, $target);

            // set file size
            $file_size = $this->_storage->getFileSize($target);
            $this->setFileSize($file_size);

            // set mime type
            $mimetype = $this->_storage->getFileMimeEncoding($target);
            $this->setMimeType($mimetype);

            // create and append hash values
            $this->_createHashValues();
        }

        // Rename file, if the stored name changed on existing record.  Rename
        // only already stored files.
        // TODO: Move rename logic to _storePathName() method.
        if (false === $this->isNewRecord() && $this->getField('PathName')->isModified()) {
            $storedPathName = $this->_primaryTableRow->path_name;

            if (!empty($storedPathName)) {
                $oldName = $documentPath . $storedPathName;
                $result = $this->_storage->renameFile($oldName, $target);
            }
        }

        return;

    }

    /**
     * Copy the uploaded file to it's final destination.
     *
     * @return void
     */
    protected function _storeTempFile() {
        return;

    }

    /**
     * Get the path to the temporary file.
     *
     * @return string Filename
     */
    protected function _fetchTempFile() {
        return;

    }

    /**
     * Deletes a file from filespace and if directory are empty it will be deleted too.
     *
     * @see    library/Opus/Model/Opus_Model_AbstractDb#doDelete()
     * @throws Exception Thrown if deleting of file failed.
     * @return void
     */
    public function doDelete($token) {
        parent::doDelete($token);
        $documentPath = $this->getParentId() . DIRECTORY_SEPARATOR;
        $target = $documentPath . $this->getPathName();

        $this->_storage->deleteFile($target);

    }

    /**
     * Populate fields from array.
     *
     * @param  array $info An associative array containing file metadata.
     * @return void
     */
    public function setFromPost(array $info) {
        // TODO: populate all fields
        $this->setPathName($info['name']);
        // $this->setMimeType($info['type']);
        $this->setTempFile($info['tmp_name']);
        $this->setLabel($info['name']);

    }

    /**
     * Get the hash value of the file
     *
     * @param string $type Type of the hash value
     * @return string hash value
     */
    public function getRealHash($type) {
        $hash = @hash_file($type, $this->getPath());

        if (empty($hash)) {
            throw new Exception("Empty HASH for file '" . $this->getPath() . "'");
        }

        return $hash;

    }

    /**
     * Perform a verification on a checksum
     *
     * @return boolean true if the checksum is valid, false if not
     */
    public function verify($type, $value = null) {
        if (!empty($value) and $this->getRealHash($type) === $value)
            return true;

        return false;

    }

    /**
     * Perform a verification on all checksums
     *
     * @return boolean true (all value) or false (at least one hash invalid)
     */
    public function verifyAll() {
        foreach ($this->getHashValue() as $hash) {
            if (!$this->verify($hash->getType(), $hash->getValue())) {
                return false;
            }
        }
        return true;

    }

    /**
     * Check if this file should perform live checksum verification
     *
     * @return boolean True if verification can get performed
     */
    public function canVerify() {
        $config = Zend_Registry::get('Zend_Config');

        $maxVerifyFilesize = -1;
        if (isset($config->checksum->maxVerificationSize)) {
            $maxVerifyFilesize = 1024 * 1024 * (int) $config->checksum->maxVerificationSize;
        }

        if ($maxVerifyFilesize < 0 || $maxVerifyFilesize > fileSize($this->getPath())) {
            return true;
        }

        return false;

    }

    /**
     * Create hash value model objects from original file.
     *
     * @return void
     */
    private function _createHashValues() {
        $hashtypes = array('md5', 'sha512');
        $hashs = array();

        foreach ($hashtypes as $type) {
            $hash = new Opus_HashValues();
            $hash->setType($type);
            $hash_string = $this->getRealHash($type);

            $hash->setValue($hash_string);
            $hashs[] = $hash;
        }
        $this->setHashValue($hashs);

    }

}
