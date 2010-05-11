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
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
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
     * Holds path to working directory.
     * TODO: hardcoded path!
     *
     * @var string
     */
    private $__path = '../workspace/files/';

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
    protected static $_tableGatewayClass  = 'Opus_Db_DocumentFiles';

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
            'TempFile' => array(),
            'HashValue' => array(
                'model' => 'Opus_HashValues'
            ),
        );

    /**
     * The file models hidden fields for not showing inside form builder.
     *
     * @var array
     * @see Opus_Model_Abstract::$_internalFields
     */
    protected $_internalFields = array(
            'FileSize',
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
        $documentid = new Opus_Model_Field('DocumentId');
        $documentid->setMandatory(true)
            ->setValidator(new Zend_Validate_Int());

        $filepathname = new Opus_Model_Field('PathName');
        $filepathname->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $filesortorder = new Opus_Model_Field('SortOrder');
        $filelabel = new Opus_Model_Field('Label');
        $filetype = new Opus_Model_Field('FileType');
        $mimetype = new Opus_Model_Field('MimeType');

        $filelanguage = new Opus_Model_Field('Language');
        if (Zend_Registry::isRegistered('Available_Languages') === true) {
            $filelanguage->setDefault(Zend_Registry::get('Available_Languages'));
        }
        $filelanguage->setSelection(true);

        $tempfile = new Opus_Model_Field('TempFile');

        $filesize = new Opus_Model_Field('FileSize');
        $filesize->setMandatory(true);

        $hashvalue = new Opus_Model_Field('HashValue');
        $hashvalue->setMandatory(true)
            ->setMultiplicity('*');

        $role = new Opus_Model_Field('AccessPermission');
        $role->setMultiplicity('*');
        $role->setDefault(Opus_Role::getAll());
        $role->setSelection(true);

        $this->addField($role);
        $this->addField($filepathname)
            ->addField($filesortorder)
            ->addField($filelabel)
            ->addField($filetype)
            ->addField($mimetype)
            ->addField($filelanguage)
            ->addField($tempfile)
            ->addField($filesize)
            ->addField($documentid)
            ->addField($hashvalue)
            ->addField($role);
    }

    /**
     * checks if the file exists physically
     * 
     * @return boolean false if the file does not exist, true if it exists
     */
    public function exists() {
    	if (file_exists($this->__path . $this->getDocumentId() . '/' . $this->getPathName()) === true) {
    		return true;
    	}
    	return false;
    }

    /**
     * Copy the uploaded file to it's final destination.
     *
     * @throws Opus_Model_Exception Thrown if moving or copying failed.
     * @return void
     */
    protected function _storeTempFile() {
        if (is_null($this->getTempFile()) === true) {
            return;
        }

        $hashtypes = array('md5', 'sha512');

        //FIXME: Hard coded path!
        $path = $this->__path . $this->getDocumentId();
        if (file_exists($path) === false) {
            mkdir($path, 0777, true);
        }

        foreach ($hashtypes as $type) {
            $hash = new Opus_HashValues();
            $hash->setType($type);
            $hash->setValue(hash_file($type, $this->getTempFile()));
            $this->addHashValue($hash);
        }

        if (true === file_exists($path . '/' . $this->getPathName())) {
            $i = 0;
            $fileName = $this->getPathName();
            while (true === file_exists($path . '/' . $fileName)) {
                $info = pathinfo($path . '/' . $this->getPathName());
                $fileName =  basename($this->getPathName(), '.' .  $info['extension']) . '_' . $i++ . '.' . $info['extension'];
            }
            $this->setPathName($fileName);
        }

        if (true === is_uploaded_file($this->getTempFile())) {
            $copyResult = move_uploaded_file($this->getTempFile(), $path . '/' . $this->getPathName());
        } else {
            $copyResult = copy($this->getTempFile(), $path . '/' . $this->getPathName());
        }
        if ($copyResult === false) {
            throw new Opus_Model_Exception('Error saving file.');
        }
    }

    /**
     * Store the file size.
     *
     * @return void
     */
    protected function _storeFileSize() {
        if (true === $this->_isNewRecord) {
            // Common workaround for php limitation (2 / 4 GB file size)
            // look at http://de.php.net/manual/en/function.filesize.php
            // more inforamtion
            $file_size = sprintf('%u', @filesize($this->getTempFile()));
            $this->_primaryTableRow->file_size = $file_size;
        }
    }

    /**
     * Get the path to the temporary file.
     *
     * @return string Filename
     */
    protected function _fetchTempFile() {
        return $this->_fields['TempFile']->getValue();
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
        $path = $this->__path . $this->getDocumentId();
        $result = unlink($path . '/' . $this->getPathName());
        // Delete directory if empty.
        if (0 === count(glob($path . '/*'))) {
            rmdir($path);
        }
        // cleanup index
        $config = Zend_Registry::get('Zend_Config');
        $searchEngine = $config->searchengine->engine;
        if (empty($searchEngine) === true) {
            $searchEngine = 'Lucene';
        }
        // Reindex
        $engineclass = 'Opus_Search_Index_'.$searchEngine.'_Indexer';
        $indexer = new $engineclass();
        $indexer->removeFileFromEntryIndex($this);
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
        $path = $this->__path . $this->getDocumentId();
        $completePath = $path . '/' . $this->getPathName();
        return hash_file($type, $completePath);
    }

    /**
     * Perform a verification on a checksum
     *
     * @return boolean true if the checksum is valid, false if not
     */
    public function verify($type, $value = null) {
        if ($value === null) {
            $hashes = $this->getHashValue();
            foreach ($hashes as $hash) {
                if ($type === $hash->getType()) {
                    $value = $hash->getValue();
                }
            }
        }
        if ($this->getRealHash($type) === $value) return true;
        return false;
    }

    /**
     * Perform a verification on all checksums
     *
     * @return array boolean values of all checksums: true (valid) or false (invalid)
     */
    public function verifyAll() {
        $hashes = $this->getHashValue();
        $return = array();
        foreach ($hashes as $hash) {
            $type = $hash->getType();
            $value = $hash->getValue();
            $return[$type] = $this->verify($type, $value);
        }
        return $return;
    }

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
    		$returnVerifyFilesize = $maxVerifyFilesize*1024;
    	}
    	if (stristr($maxVerifyFilesize, 'm') !== false) {
    		$maxVerifyFilesize = str_replace('m', '', strtolower($maxVerifyFilesize));
    		$returnVerifyFilesize = $maxVerifyFilesize*1024*1024;
    	}
    	if (stristr($maxVerifyFilesize, 'g') !== false) {
    		$maxVerifyFilesize = str_replace('g', '', strtolower($maxVerifyFilesize));
    		$returnVerifyFilesize = $maxVerifyFilesize*1024*1024*1024;
    	}

        return $returnVerifyFilesize;
    }

    /**
     * Check if this file should perform live checksum verification
     *
     * @return boolean True if verification can get performed
     */
    public function canVerify() {
    	$path = $this->__path . $this->getDocumentId();
        $completePath = $path . '/' . $this->getPathName();
    	if ($this->getMaxVerifyFilesize() === 'u' || $this->getMaxVerifyFilesize() > fileSize($completePath)) {
    		return true;
    	}
        return false;
    }
}
