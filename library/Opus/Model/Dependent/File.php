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
            'TempFile' => array(null),
            'HashValue' => array(
                'model' => 'Opus_Model_Dependent_HashValues'
            ),
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
        $documentsid = new Opus_Model_Field('DocumentId');
        $filepathname = new Opus_Model_Field('PathName');
        $filesortorder = new Opus_Model_Field('SortOrder');
        $filelabel = new Opus_Model_Field('Label');
        $filetype = new Opus_Model_Field('FileType');
        $mimetype = new Opus_Model_Field('MimeType');
        $filelanguage = new Opus_Model_Field('Language');
        $filelanguage->setDefault(Zend_Registry::get('Available_Languages'))
            ->setSelection(true);
        $tempfile = new Opus_Model_Field('TempFile');
        $hashvalue = new Opus_Model_Field('HashValue');

        $this->addField($filepathname)
            ->addField($filesortorder)
            ->addField($filelabel)
            ->addField($filetype)
            ->addField($mimetype)
            ->addField($filelanguage)
            ->addField($tempfile)
            ->addField($documentsid)
            ->addField($hashvalue);
    }

    /**
     * Copy the uploaded file to it's final destination.
     *
     * @return void
     */
    protected function _storeTempFile() {
        //FIXME: Hard coded path!
        $path = '../workspace/tmp/' . date('Y') . '/' . $this->getDocumentId();
        if (file_exists($path) === false) {
            mkdir($path, 0777, true);
        }
        $hash = $this->addHashValue();
        $hash->setType('md5');
        $hash->setValue(hash_file('md5', $this->getTempFile()));
        move_uploaded_file($this->getTempFile(), $path . '/' . $this->getPathName());
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
     * @param  array $info An associative array containing file metadata.
     * @return void
     */
    public function setFromPost(array $info) {
        // TODO: populate all fields
        $this->setPathName($info['name']);
        $this->setMimeType($info['type']);
        $this->setTempFile($info['tmp_name']);
    }

}
