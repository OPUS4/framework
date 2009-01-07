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
class Opus_Model_Dependent_File extends Opus_Model_DependentAbstract {

    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'documents_id';

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected $_tableGatewayClass  = 'Opus_Db_DocumentFiles';

    /**
     * TODO: description.
     *
     * @var mixed  Defaults to array(        'TempFile' => null        ). 
     */
    protected $_externalFields = array(
        'TempFile' => array(null),
    );

    /**
     * Initialize model with the following fields:
     * - FilePathName
     * - FileSortOrder
     * - FileLabel
     * - FileType
     * - MimeType
     * - FileLanguage
     *
     * @return void
     */
    protected function _init() {
        $documentsid = new Opus_Model_Field('DocumentsId');
        $filepathname = new Opus_Model_Field('FilePathName');
        $filesortorder = new Opus_Model_Field('FileSortOrder');
        $filelabel = new Opus_Model_Field('FileLabel');
        $filetype = new Opus_Model_Field('FileType');
        $mimetype = new Opus_Model_Field('MimeType');
        $filelanguage = new Opus_Model_Field('FileLanguage');
        $tempfile = new Opus_Model_Field('TempFile');

        $this->addField($filepathname)
            ->addField($filesortorder)
            ->addField($filelabel)
            ->addField($filetype)
            ->addField($mimetype)
            ->addField($filelanguage)
            ->addField($tempfile)
            ->addField($documentsid);
    }

    /**
     * Copy the uploaded file to it's final destination.
     *
     * @return void
     */
    protected function _storeTempFile() {
        //TODO: Move temp file to repository.
        $path = '../tmp/' . date('Y') . '/' . $this->getDocumentsId();
        if (file_exists($path) === false) {
            mkdir($path, 0777, true);
        }
        print_r($path);
        copy($this->getTempFile(), $path . '/' . $this->getFilePathName());
    }

    /**
     * Get the path to the temporary file.
     *
     * @return string Filename
     */
    protected function _fetchTempFile() {
        return $this->getTempFile();
    }

    /**
     * Populate fields from array.
     *
     * @param  array  $info An associative array containing file metadata.
     * @return void
     */
    public function setFromPost($info) {
        // TODO: populate all fields
        $this->setFilePathName($info['name']);
        $this->setMimeType($info['type']);
        $this->setTempFile($info['tmp_name']);
    }

    /**
     * Hold repository path value.
     *
     * @var string
     */
    //protected $repositoryPath = dirname(__FILE__) . '/repo';

    /**
     * Remove a file specified by the given identifier.
     *
     * @param integer $fileId Identifier of file record.
     * @throws InvalidArgumentException Thrown on invalid identifier argument.
     * @throws Opus_File_Exception If removing failed for any reason.
     * @return void
     */
    public function remove($fileId) {
        if (is_int($fileId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        $filedb = new Opus_Db_DocumentFiles();
        $rows = $filedb->find($fileId)->current();
        if (empty($rows) === true) {
            throw new Opus_File_Exception('Informations about specific entry not found.');
        }
        $filedb->getAdapter()->beginTransaction();
        try {
            $where = $filedb->getAdapter()->quoteInto('document_files_id = ?', $fileId);
            $filedb->delete($where);
            // Try to delete the file
            $destfile = $this->repositoryPath . DIRECTORY_SEPARATOR . $rows->file_path_name;
            // unlink throws an exception on failure
            // not documented in php manuals until 01.09.2008
            unlink($destfile);
            $filedb->getAdapter()->commit();
        } catch (Exception $e) {
            // Something is going wrong, restore old data
            $filedb->getAdapter()->rollBack();
            throw new Opus_File_Exception('Error during deleting meta data or file: ' . $e->getMessage());
        }

    }

    /**
     * Get path to file in repository.
     *
     * @param integer $fileId Identifier of file record.
     * @throws InvalidArgumentException Thrown on invalid identifier argument.
     * @throws Opus_File_Exception Thrown on error.
     * @return string Full qualified path to repository file. Empty if file is not existent.
     */
    public function getPath($fileId) {
        if (is_int($fileId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        $filedb = new Opus_Db_DocumentFiles();
        $rows = $filedb->find($fileId)->current();
        if (empty($rows) === true) {
            throw new Opus_File_Exception('Could not found any data to specific entry.');
        }
        $result = $rows->file_path_name;
        return $result;
    }

    /**
     * Retrieve all identifiers of associated files by passing a document identifier.
     *
     * @param integer $documentId Document identifier.
     * @throws InvalidArgumentException Thrown on invalid identifier argument.
     * @return array Set of file identifiers for a specified documen.
     */
    public function getAllFileIds($documentId) {
        if (is_int($documentId) === false) {
            throw new InvalidArgumentException('Identifier is not an integer value.');
        }
        $result = array();
        $filedb = new Opus_Db_DocumentFiles();
        $select = $filedb->select()->where('documents_id = ?', $documentId);
        $results = $filedb->fetchAll($select);
        foreach ($results as $key => $value) {
            $result[] = $value->document_files_id;
        }
        return $result;
    }

    /**
     * Return path to repository
     *
     * @return string
     */
    public function getRepositoryPath() {
        return $this->repositoryPath;
    }
}
