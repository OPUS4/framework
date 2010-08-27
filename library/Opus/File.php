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
     * Holds path of destination.
     *
     * @var string
     */
    private $_destinationPath = '../workspace/files/';

    /**
     * Holds path of source.
     *
     * @var string
     */
    private $_sourcePath = null;

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
        'AccessPermission' => array(
            'model' => 'Opus_Role',
            'through' => 'Opus_Model_Dependent_Link_FileRole',
            'options' => array('privilege' => 'readFile'),
            'fetch' => 'lazy'
        ),
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

        $hashvalue = new Opus_Model_Field('HashValue');
        $hashvalue->setMandatory(true)
                ->setMultiplicity('*');

        $role = new Opus_Model_Field('AccessPermission');
        $role->setMultiplicity('*');
        $role->setDefault(Opus_Role::getAll());
        $role->setSelection(true);

        $document_id = new Opus_Model_Field('DocumentId');

        $this->addField($filepathname)
                ->addField($filelabel)
                ->addField($mimetype)
                ->addField($filelanguage)
                ->addField($tempfile)
                ->addField($filesize)
                ->addField($visible_in_frontdoor)
                ->addField($hashvalue)
                ->addField($role)
                ->addField($document_id);
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
        return $this->getDestinationPath() . $this->getParentId() . DIRECTORY_SEPARATOR . $this->getPathName();
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

        $tempFile = $this->getTempFile();
        $destinationPath = $this->getDestinationPath() . $this->getParentId();
        $target = $this->getPath();

        if (false === empty($tempFile)) {
            // add source path if temp file does not have path information
            if (false === file_exists($tempFile)) {
                $tempFile = $this->getSourcePath() . $tempFile;
            }

            if (false === file_exists($tempFile)) {
                throw new Exception("File $tempFile does not exist.");
            }

            // set file size
            $file_size = sprintf('%u', @filesize($tempFile));
            $this->setFileSize($file_size);

            // set mime type
            $mimetype = mime_content_type($tempFile);
            $this->setMimeType($mimetype);

            if (file_exists($destinationPath) === false) {
                $mkdirResult = mkdir($destinationPath, 0755, true);
                if (!$mkdirResult) {
                    $message = "Error creating directory '$destinationPath'.";
                    $this->getLogger()->err($message);
                    throw new Exception($message);
                }
            }

            $copyResult = copy($tempFile, $target);
            if ($copyResult === false) {
                $message = "Error copying file '" . $this->getTempFile() . "' to '$target'";
                $this->getLogger()->err($message);
                throw new Exception($message);
            }

            // TODO: Hotfix for upload bug.  Cannot create hash, if file is deleted.
            if (false) {
                if (true === is_uploaded_file($tempFile)) {
                    if (@unlink($tempFile) === false) {
                        $message = "Error removing temp file " . $this->getTempFile();
                        $this->getLogger()->err($message);
                    }
                }
            }

            // create and append hash values
            $this->_createHashValues();
        }

        // Rename file, if the stored name changed on existing record.  Rename
        // only already stored files.
        // TODO: Move rename logic to _storePathName() method.
        if (false === $this->isNewRecord() && $this->getField('PathName')->isModified()) {
            $storedPathName = $this->_primaryTableRow->path_name;

            if (!empty($storedPathName)) {
                $oldName = $destinationPath . DIRECTORY_SEPARATOR . $storedPathName;
                $result = @rename($oldName, $target);
                if (false === $result) {
                    throw new Exception('Could not rename file from "' . $oldName . '" to "' . $target . '"!');
                }
            }
        }

        return;

    }

    /**
     * Copy the uploaded file to it's final destination.
     *
     * @throws Opus_Model_Exception Thrown if moving or copying failed.
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
        $path = $this->getDestinationPath() . $this->getParentId();

        $result = false;
        if ($this->exists()) {
            $result = @unlink($this->getPath());
        }
        else {
            $message = 'Cannot remove file ' . $this->getPath() . ' (cwd: ' . getcwd() . ')';
            $this->getLogger()->warn($message);
        }

        // Delete directory.  If not empty, it will fail but suppress errors.
        @rmdir($path);

        // cleanup index
        $config = Zend_Registry::get('Zend_Config');
        $searchEngine = $config->searchengine->engine;
        if (empty($searchEngine) === true) {
            $searchEngine = 'Lucene';
        }

        // TODO: Disabled index update when not running Zend_Lucene.
        if ($searchEngine === 'Lucene') {
            // Reindex
            $engineclass = 'Opus_Search_Index_' . $searchEngine . '_Indexer';
            $indexer = new $engineclass();
            try {
                $indexer->removeFileFromEntryIndex($this);
            }
            catch (Exception $e) {
                throw $e;
            }

            if ($result === false) {
                $message = 'Cannot remove file ' . $this->getPath() . ' (cwd: ' . getcwd() . ')';
                $this->getLogger()->err($message);
                throw new Exception($message, '403');
            }
        }
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
        $this->setMimeType($info['type']);
        $this->setTempFile($info['tmp_name']);
    }

    /**
     * Get the hash value of the file
     *
     * @param string $type Type of the hash value
     * @return string hash value
     */
    public function getRealHash($type) {
        return hash_file($type, $this->getPath());
    }

    /**
     * Perform a verification on a checksum
     *
     * @return boolean true if the checksum is valid, false if not
     */
//    public function verify($type, $value = null) {
//        throw new Exception("Method seems to be unused.  Remove.");
//
//        if (!empty($value) and $this->getRealHash($type) === $value)
//            return true;
//
//        return false;
//    }

    /**
     * Perform a verification on all checksums
     *
     * @return array boolean values of all checksums: true (valid) or false (invalid)
     */
//    public function verifyAll() {
//        throw new Exception("Method seems to be unused.  Remove.");
//
//        $hashes = $this->getHashValue();
//        $return = array();
//        foreach ($hashes as $hash) {
//            $type = $hash->getType();
//            $value = $hash->getValue();
//            $return[$type] = $this->verify($type, $value);
//        }
//        return $return;
//    }

    /**
     * Gets the verification file size limit from configuration
     *
     * @return int limit to that files should get verified
     */
    private function getMaxVerifyFilesize() {
        $config = Zend_Registry::get('Zend_Config');

        $maxVerifyFilesize = $config->checksum->maxVerificationSize;
        $maxVerifyFilesize = str_replace(' ', '', strtolower($maxVerifyFilesize));
        $returnVerifyFilesize = $maxVerifyFilesize;
        if (stristr($maxVerifyFilesize, 'k') !== false) {
            $maxVerifyFilesize = str_replace('k', '', strtolower($maxVerifyFilesize));
            $returnVerifyFilesize = $maxVerifyFilesize * 1024;
        }
        if (stristr($maxVerifyFilesize, 'm') !== false) {
            $maxVerifyFilesize = str_replace('m', '', strtolower($maxVerifyFilesize));
            $returnVerifyFilesize = $maxVerifyFilesize * 1024 * 1024;
        }
        if (stristr($maxVerifyFilesize, 'g') !== false) {
            $maxVerifyFilesize = str_replace('g', '', strtolower($maxVerifyFilesize));
            $returnVerifyFilesize = $maxVerifyFilesize * 1024 * 1024 * 1024;
        }

        return $returnVerifyFilesize;
    }

    /**
     * Check if this file should perform live checksum verification
     *
     * @return boolean True if verification can get performed
     */
    public function canVerify() {
        $maxVerifyFilesize = $this->getMaxVerifyFilesize();
        if ($maxVerifyFilesize === 'u' || $maxVerifyFilesize > fileSize($this->getPath())) {
            return true;
        }
        return false;
    }

    /**
     * Returns current destination path.
     *
     * @return string
     */
    private function getDestinationPath() {
        return $this->_destinationPath;
    }

    /**
     * Returns current source path.
     *
     * @return string
     */
    public function getSourcePath() {
        return $this->_sourcePath;
    }

    /**
     * Set new destination path.
     *
     * @param string $sourcePath New directory path for destination files.
     * @throws InvalidArgumentException Thrown if directory is not valid.
     * @return void
     */
    public function setDestinationPath($destinationPath) {
        $destinationPath = $this->_addMissingDirectorySeparator($destinationPath);
        if (false === is_dir($destinationPath)) {
            throw new InvalidArgumentException('"' . $destinationPath . '" is not a valid directory.');
        }
        $this->_destinationPath = $destinationPath;
    }

    /**
     * Set new source path.
     *
     * @param string $sourcePath New directory path for source files.
     * @throws InvalidArgumentException Thrown if directory is not valid.
     * @return void
     */
    public function setSourcePath($sourcePath) {
        $sourcePath = $this->_addMissingDirectorySeparator($sourcePath);
        if (false === is_dir($sourcePath)) {
            throw new InvalidArgumentException('"' . $sourcePath . '" is not a valid directory.');
        }
        $this->_sourcePath = $sourcePath;
    }

    /**
     * Adds to a given path a directory separator if not set.
     *
     * @param string $path Path with or withour directory separator.
     * @return string Path with directory separator.
     */
    private function _addMissingDirectorySeparator($path) {
        if (false === empty($path)) {
            // TODO: if (DIRECTORY_SEPARATOR !== $path[mb_strlen($path) - 1]) {
            if (DIRECTORY_SEPARATOR !== $path[strlen($path) - 1]) {
                $path .= DIRECTORY_SEPARATOR;
            }
        }
        return $path;
    }

    /**
     * Create hash value model objects from original file.
     *
     * @return void
     */
    private function _createHashValues() {
        $hashtypes = array('md5', 'sha512');
        $hashs = array();
        $tempFile = $this->getTempFile();
        if (false === file_exists($tempFile)) {
            $tempFile = $this->getSourcePath() . $tempFile;
        }
        if (false === file_exists($tempFile)) {
            throw new Exception("File $tempFile does not exist.");
        }
        foreach ($hashtypes as $type) {
            $hash = new Opus_HashValues();
            $hash->setType($type);
            $hash_string = hash_file($type, $tempFile);

            if (empty ($hash_string)) {
                throw new Exception("Empty HASH for file $tempFile.");
            }

            $hash->setValue($hash_string);
            $hashs[] = $hash;
        }
        $this->setHashValue($hashs);
    }

}
