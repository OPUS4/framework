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
 * Hash values for files are cached in the object. If a new object is created the hash values will be calculated again.
 * The hashes are for instance used to generate a filename for the text extraction cache. This causes a lot a hash
 * calculations. Therefore caching the hashes improves performance. The risk of a file being changed during the
 * existence of an Opus_File object is small.
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Abstract
 *
 * @method string getVisibleInFrontdoor() retrieves value of field VisibleInFrontDoor
 * @method string getMimeType() retrieves value of field MimeType
 */
class Opus_File extends Opus_Model_Dependent_Abstract
{

    /**
     * Plugins to load
     *
     * @var array
     */
    public function getDefaultPlugins()
    {
        return [
            'Opus_File_Plugin_DefaultAccess',
            'Opus_Model_Plugin_InvalidateDocumentCache'
        ];
    }

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
    protected $_externalFields = [
        'TempFile' => [],
        'HashValue' => [
            'model' => 'Opus_HashValues'
        ],
    ];

    private $_hashValues = [];

    /**
     * Initialize model with the following fields:
     * - PathName
     * - Label
     * - FileType
     * - MimeType
     * - Language
     *
     * @return void
     */
    protected function _init()
    {
        $filepathname = new Opus_Model_Field('PathName');
        $filepathname->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $filelabel = new Opus_Model_Field('Label');
        $filecomment = new Opus_Model_Field('Comment');
        $mimetype = new Opus_Model_Field('MimeType');

        $filelanguage = new Opus_Model_Field('Language');
        if (Zend_Registry::isRegistered('Available_Languages') === true) {
            $filelanguage->setDefault(Zend_Registry::get('Available_Languages'));
        }
        $filelanguage->setSelection(true);

        $tempfile = new Opus_Model_Field('TempFile');

        $serverDateSubmitted = new Opus_Model_Field('ServerDateSubmitted');
        $serverDateSubmitted->setValueModelClass('Opus_Date');

        $sortOrder = new Opus_Model_Field('SortOrder');

        $filesize = new Opus_Model_Field('FileSize');
        $filesize->setMandatory(true);

        $visibleInFrontdoor = new Opus_Model_Field('VisibleInFrontdoor');
        $visibleInOai = new Opus_Model_Field('VisibleInOai');

        $hashvalue = new Opus_Model_Field('HashValue');
        $hashvalue->setMandatory(true)
                ->setMultiplicity('*');

        $this->addField($filepathname)
                ->addField($filelabel)
                ->addField($filecomment)
                ->addField($mimetype)
                ->addField($filelanguage)
                ->addField($tempfile)
                ->addField($filesize)
                ->addField($visibleInFrontdoor)
                ->addField($visibleInOai)
                ->addField($hashvalue)
                ->addField($serverDateSubmitted)
                ->addField($sortOrder);
    }

    public static function fetchByDocIdPathName($docId, $pathName)
    {
        $files = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $files->select()
                ->where('document_id = ?', $docId)
                ->where('path_name = ?', $pathName);
        $row = $files->fetchRow($select);

        if (! is_null($row)) {
            return new Opus_File($row);
        }
        return null;
    }

    /**
     * Prepare and return Opus_Storage_File object for filesystem manipulation.
     *
     * @return Opus_Storage_File Storage object.
     */
    private function getStorage()
    {
        if (! is_null($this->_storage)) {
            return $this->_storage;
        }

        if (is_null($this->getParentId())) {
            throw new Opus\Model\Exception('ParentId is not set!');
        }

        $config = Zend_Registry::get('Zend_Config');
        $filesPath = $config->workspacePath . DIRECTORY_SEPARATOR . "files";
        $this->_storage = new Opus_Storage_File($filesPath, $this->getParentId());

        return $this->_storage;
    }

    /**
     * checks if the file exists physically
     *
     * @return boolean false if the file does not exist, true if it exists
     */
    public function exists()
    {
        return file_exists($this->getPath());
    }

    /**
     * checks if the file is readable (and exists)
     *
     * @return boolean true if the file is readable, otherwise false
     */
    public function isReadable()
    {
        return is_readable($this->getPath());
    }

    /**
     * Get full path of destination file.
     */
    public function getPath()
    {
        return $this->getStorage()->getWorkingDirectory() . $this->getPathName();
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
    protected function _preStore()
    {
        $result = parent::_preStore();

        if (isset($result)) {
            return $result;
        }

        $target = $this->getPathName();

        $tempFile = $this->getTempFile();
        if (false === empty($tempFile)) {
            $this->getStorage()->createSubdirectory();
            $this->getStorage()->copyExternalFile($tempFile, $target);

            // set file size
            $fileSize = $this->getStorage()->getFileSize($target);
            $this->setFileSize($fileSize);

            // set mime type
            $mimetype = $this->getStorage()->getFileMimeEncoding($target);
            $this->setMimeType($mimetype);

            // create and append hash values
            $this->_createHashValues();
        }

        // Rename file, if the stored name changed on existing record.  Rename
        // only already stored files.
        // TODO: Move rename logic to _storePathName() method.
        if (false === $this->isNewRecord() && $this->getField('PathName')->isModified()) {
            $storedFileName = $this->_primaryTableRow->path_name;

            if (! empty($storedFileName)) {
                // $oldName = $this->getStorage()->getWorkingDirectory() . $storedFileName;
                $result = $this->getStorage()->renameFile($storedFileName, $target);
            }
        }

        if ($this->isNewRecord()) {
            $dateNow = new Opus_Date();
            $dateNow->setNow();
            $this->setServerDateSubmitted($dateNow);
        }

        return;
    }

    /**
     * Copy the uploaded file to it's final destination.
     *
     * @return void
     */
    protected function _storeTempFile()
    {
        return;
    }

    /**
     * Get the path to the temporary file.
     *
     * @return string Filename
     */
    protected function _fetchTempFile()
    {
        return;
    }

    /**
     * Deletes a file from filespace and if directory are empty it will be deleted too.
     *
     * @see    library/Opus/Model/Opus_Model_AbstractDb#doDelete()
     * @throws Opus_Storage_Exception if not a file, or empty directory could not be deleted
     * @throws Opus_Storage_FileNotFoundException  if file does not exist
     * @throws Opus_Storage_FileAccessException if file could not be deleted
     * @return void
     */
    public function doDelete($token)
    {
        parent::doDelete($token);
        $this->getStorage()->deleteFile($this->getPathName());

        // TODO: Check return value of removeEmptyDirectory()?
        $this->getStorage()->removeEmptyDirectory();
    }

    /**
     * Populate fields from array.
     *
     * @param  array $info An associative array containing file metadata.
     * @return void
     */
    public function setFromPost(array $info)
    {
        // TODO: populate all fields
        $this->setPathName($info['name']);
        // $this->setMimeType($info['type']);
        $this->setTempFile($info['tmp_name']);
        $this->setLabel($info['name']);
    }

    /**
     * Get the hash value of the file
     *
     * @param string $type Type of the hash value, @see hash_file();
     * @return string hash value
     *
     * TODO gets called too often and does not cache values
     */
    public function getRealHash($type)
    {
        if (array_key_exists($type, $this->_hashValues)) {
            return $this->_hashValues[$type];
        }

        $hash = @hash_file($type, $this->getPath());

        $this->_hashValues[$type] = $hash;

        if (empty($hash)) {
            throw new Exception("Empty HASH for file '" . $this->getPath() . "'");
        }

        return $hash;
    }

    /**
     * Perform a verification on a checksum
     *
     * TODO throws Exception in case hash computation is not possible
     *      (e.g., if referenced file is missing in file system)
     *
     * @return boolean true if the checksum is valid, false if not
     */
    public function verify($type, $value = null)
    {
        if (! empty($value) and $this->getRealHash($type) === $value) {
            return true;
        }

        return false;
    }

    /**
     * Perform a verification on all checksums
     *
     * @return boolean true (all value) or false (at least one hash invalid)
     */
    public function verifyAll()
    {
        foreach ($this->getHashValue() as $hash) {
            if (! $this->verify($hash->getType(), $hash->getValue())) {
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
    public function canVerify()
    {
        $config = Zend_Registry::get('Zend_Config');

        $maxVerifyFilesize = -1;
        if (isset($config->checksum->maxVerificationSize)) {
            $maxVerifyFilesize = 1024 * 1024 * (int) $config->checksum->maxVerificationSize;
        }

        if (($maxVerifyFilesize < 0) or
                ($this->getStorage()->getFileSize($this->getPathName()) < $maxVerifyFilesize)) {
            return true;
        }

        return false;
    }

    /**
     * Create hash value model objects from original file.
     *
     * TODO throws Exception in case hash computation is not possible
     *      (e.g., if referenced file is missing in file system)
     *
     * @return void
     */
    private function _createHashValues()
    {
        $hashtypes = ['md5', 'sha512'];
        $hashs = [];

        foreach ($hashtypes as $type) {
            $hash = new Opus_HashValues();
            $hash->setType($type);
            $hashString = $this->getRealHash($type);

            $hash->setValue($hashString);
            $hashs[] = $hash;
        }
        $this->setHashValue($hashs);
    }
}
