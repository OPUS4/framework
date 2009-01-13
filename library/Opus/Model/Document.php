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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for documents in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Abstract
 */
class Opus_Model_Document extends Opus_Model_Abstract
{


    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected $_tableGatewayClass = 'Opus_Db_Documents';

    /**
     * The document is the most complex Opus_Model. An Opus_Document_Builder is
     * used in the _init() function to construct an Opus_Model_Document of a
     * certain type.
     *
     * @var Opus_Document_Builder
     */
    protected $_builder;

    /**
     * The type of the document.
     *
     * @var string
     */
    protected $_type = null;

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Documents table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
            'TitleMain' => array(
                'model' => 'Opus_Model_Dependent_Title',
                'options' => array('title_abstract_type' => 'main')
            ),
            'TitleAbstract' => array(
                'model' => 'Opus_Model_Dependent_Abstract',
                'options' => array('title_abstract_type' => 'abstract')
            ),
            'TitleParent' => array(
                'model' => 'Opus_Model_Dependent_Parent',
                'options' => array('title_abstract_type' => 'parent')
            ),
            'Isbn' => array(
                'model' => 'Opus_Model_Dependent_Isbn',
                'options' => array('identifier_type' => 'isbn')
            ),
            'Note' => array(
                'model' => 'Opus_Model_Dependent_Note',
            ),
            'Patent' => array(
                'model' => 'Opus_Model_Dependent_Patent',
            ),
            'Enrichment' => array(
                'model' => 'Opus_Model_Dependent_Enrichment',
            ),
            'Institute' => array(
                'model' => 'Opus_Model_Link_DocumentInstitute',
            ),
            'Licence' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentLicence',
            ),
            'PersonAdvisor' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'advisor')
            ),
            'PersonAuthor' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'author')
            ),
            'PersonContributor' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'contributor')
            ),
            'PersonEditor' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'editor')
            ),
            'PersonReferee' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'referee')
            ),
            'PersonOther' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'other')
            ),
            'PersonTranslator' => array(
                'model' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'translator')
            ),
            'SubjectSwd' => array(
                'model' => 'Opus_Model_Dependent_Subject',
                'options' => array('subject_type' => 'swd')
            ),
            'File' => array(
                'model' => 'Opus_Model_Dependent_File',
            ),
        );

    /**
     * Fields that should not be displayed on a form.
     *
     * @var array  Defaults to array('File').
     */
    protected $_hiddenFields = array(
            'File',
            'DocumentType',
        );

    /**
     * Constructor.
     *
     * @param  integer|string $id   (Optional) Id an existing document.
     * @param  string         $type (Optional) Type of a new document.
     * @see    Opus_Model_Abstract::__construct()
     * @see    $_builder
     * @throws InvalidArgumentException         Thrown if id and type are passed.
     * @throws Opus_Model_Exception             Thrown invalid type is passed.
     */
    public function __construct($id = null, $type = null) {
        if ($id === null and $type === null) {
            throw new InvalidArgumentException('Either id or type must be passed.');
        }
        if ($id === null and $type !== null) {
            $this->_type = $type;
        }
        parent::__construct($id, new $this->_tableGatewayClass);
    }

    /**
     * Initialize the document's fields. Due to a variety of different document types, an
     * Opus_Document_Builder is used. The language field needs special treatment to initialize the
     * default values.
     *
     * @return void
     */
    protected function _init() {
        if ($this->getId() === null) {
            if (is_string($this->_type) === true) {
                $this->_builder = new Opus_Document_Builder(new Opus_Document_Type($this->_type));
            } else if ($this->_type instanceof Opus_Document_Type) {
                $this->_builder = new Opus_Document_Builder($this->_type);
            } else {
                throw new Opus_Model_Exception('Unkown document type.');
            }
        } else if ($this->_type === null) {
            $this->_builder = new Opus_Document_Builder(new
                    Opus_Document_Type($this->_primaryTableRow->document_type));
        }

        // Add fields generated by the builder
        $this->_builder->addFieldsTo($this);

        // Initialize available languages
        if ($this->getField('Language') !== null) {
            $this->getField('Language')->setDefault(Zend_Registry::get('Available_Languages'))
                ->setSelection(true);
        }

        // Initialize available licences
        if ($this->getField('Licence') !== null) {
            $licences = new Opus_Db_DocumentLicences;
            $licences = $licences->fetchAll();
            $licenceLinkModels = array();
            foreach ($licences as $licence) {
                $licenceLinkModel = new Opus_Model_Dependent_Link_DocumentLicence;
                $licenceLinkModel->setModel(new Opus_Model_Licence($licence->licences_id));
                $licenceLinkModels[] = $licenceLinkModel;
            }
            $this->getField('Licence')->setDefault($licenceLinkModels)
                ->setSelection(true);
        }
    }

    /**
     * Get the document's type.
     *
     * @return string|Opus_Document_Type The type of the document.
     */
    public function getDocumentType() {
        return $this->_type;
    }

    /**
     * Set the document's type.
     *
     * @param  string|Opus_Document_Type $type The type of the document.
     * @return void
     */
    public function setDocumentType($type) {
        $this->_type = $type;
        // TODO: Recreate Document on type change.
    }

}
