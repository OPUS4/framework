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
 * @package     Opus
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
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Document extends Opus_Model_AbstractDb
{


    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Documents';

    /**
     * The document is the most complex Opus_Model. An Opus_Document_Builder is
     * used in the _init() function to construct an Opus_Document of a
     * certain type.
     *
     * @var Opus_Document_Builder
     */
    protected $_builder;

    /**
     * The type of the document.
     *
     * @var string|Opus_Document_Type
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
                'model' => 'Opus_Title',
                'options' => array('type' => 'main'),
                'fetch' => 'lazy'
            ),
            'TitleAbstract' => array(
                'model' => 'Opus_Title',
                'options' => array('type' => 'abstract'),
                'fetch' => 'lazy'
            ),
            'TitleParent' => array(
                'model' => 'Opus_Title',
                'options' => array('type' => 'parent'),
                'fetch' => 'lazy'
            ),
            'IdentifierIsbn' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'isbn'),
                'fetch' => 'lazy'
            ),
            'IdentifierUrn' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'urn')
            ),
            'IdentifierDoi' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'doi')
            ),
            'IdentifierHandle' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'handle')
            ),
            'IdentifierUrl' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'url')
            ),
            'IdentifierIssn' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'issn')
            ),
            'IdentifierStdDoi' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'std-doi')
            ),
            'IdentifierCrisLink' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'cris-link')
            ),
            'IdentifierSplashUrl' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'splash-url')
            ),
            'IdentifierOpus3' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'opus3-id')
            ),
            'ReferenceIsbn' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'isbn'),
                'fetch' => 'lazy'
            ),
            'ReferenceUrn' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'urn')
            ),
            'ReferenceDoi' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'doi')
            ),
            'ReferenceHandle' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'handle')
            ),
            'ReferenceUrl' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'url')
            ),
            'ReferenceIssn' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'issn')
            ),
            'ReferenceStdDoi' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'std-doi')
            ),
            'ReferenceCrisLink' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'cris-link')
            ),
            'ReferenceSplashUrl' => array(
                'model' => 'Opus_Reference',
                'options' => array('type' => 'splash-url')
            ),
            'Note' => array(
                'model' => 'Opus_Note',
                'fetch' => 'lazy'
            ),
            'Patent' => array(
                'model' => 'Opus_Patent',
                'fetch' => 'lazy'
            ),
            'Enrichment' => array(
                'model' => 'Opus_Enrichment',
                'fetch' => 'lazy'
            ),
            'Institute' => array(
                'model' => 'Opus_Institute',
                'through' => 'Opus_Model_Link_DocumentInstitute',
                'fetch' => 'lazy'
            ),
            'Licence' => array(
                'model' => 'Opus_Licence',
                'through' => 'Opus_Model_Dependent_Link_DocumentLicence',
                'fetch' => 'lazy'
            ),
            'PersonAdvisor' => array(
                'model' => 'Opus_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'advisor'),
                'fetch' => 'lazy'
            ),
            'PersonAuthor' => array(
                'model' => 'Opus_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'author'),
                'fetch' => 'lazy'
            ),
            'PersonContributor' => array(
                'model' => 'Opus_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'contributor'),
                'fetch' => 'lazy'
            ),
            'PersonEditor' => array(
                'model' => 'Opus_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'editor'),
                'fetch' => 'lazy'
            ),
            'PersonReferee' => array(
                'model' => 'Opus_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'referee'),
                'fetch' => 'lazy'
            ),
            'PersonOther' => array(
                'model' => 'Opus_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'other'),
                'fetch' => 'lazy'
            ),
            'PersonTranslator' => array(
                'model' => 'Opus_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'translator'),
                'fetch' => 'lazy'
            ),
            'SubjectSwd' => array(
                'model' => 'Opus_Subject',
                'options' => array('type' => 'swd'),
                'fetch' => 'lazy'
            ),
            'SubjectPsyndex' => array(
                'model' => 'Opus_Subject',
                'options' => array('type' => 'psyndex'),
                'fetch' => 'lazy'
            ),
            'SubjectUncontrolled' => array(
                'model' => 'Opus_Subject',
                'options' => array('type' => 'uncontrolled'),
                'fetch' => 'lazy'
            ),
            'File' => array(
                'model' => 'Opus_File',
                'fetch' => 'lazy'
            ),
        );

    /**
     * Fields that should not be displayed on a form.
     *
     * @var array  Defaults to array('File').
     */
    protected $_hiddenFields = array(
            'File',
            'Type',
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
        if (($id === null and $type === null) or ($id !== null and $type !== null)) {
            throw new InvalidArgumentException('Either id or type must be passed.');
        }
        if ($id === null and $type !== null) {
            $this->_type = $type;
            parent::__construct(null, new self::$_tableGatewayClass);
        } else {
            parent::__construct($id, new self::$_tableGatewayClass);
            $this->_type = $this->_primaryTableRow->type;
        }
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
                $this->_primaryTableRow->type = str_replace('_', ' ', $this->_type);
            } else if ($this->_type instanceof Opus_Document_Type) {
                $this->_builder = new Opus_Document_Builder($this->_type);
                $this->_primaryTableRow->type = str_replace('_', ' ', $this->_type->getName());
            } else {
                throw new Opus_Model_Exception('Unkown document type.');
            }
        } else if ($this->_type === null) {
            $this->_builder = new Opus_Document_Builder(new
                    Opus_Document_Type($this->_primaryTableRow->type));
        }

        // Add fields generated by the builder
        $this->_builder->addFieldsTo($this);

        // Initialize available languages
        if ($this->getField('Language') !== null) {
            if (Zend_Registry::isRegistered('Available_Languages') === true) {
                $this->getField('Language')
                    ->setDefault(Zend_Registry::get('Available_Languages'))
                    ->setSelection(true);
            }
        }

        // Initialize available licences
        if ($this->getField('Licence') !== null) {
            $licences = Opus_Licence::getAll();
            $this->getField('Licence')->setDefault($licences)
                ->setSelection(true);
        }

        // Add the document's type as a normal field
        $documentType = new Opus_Model_Field('Type');
        $documentType->setValue($this->_type);
        $this->addField($documentType);
    }

    /**
     * Store multiple languages as a comma seperated string.
     *
     * @return void
     */
    protected function _storeLanguage() {
        if ($this->_fields['Language']->getValue() !== null) {
            if ($this->_fields['Language']->hasMultipleValues()) {
                $result = implode(',', $this->_fields['Language']->getValue());
            } else {
                $result = $this->_fields['Language']->getValue();
            }
        } else {
            $result = null;
        }
        $this->_primaryTableRow->language = $result;
    }

    /**
     * Load multiple languages from a comma seperated string.
     *
     * @return array
     */
    protected function _fetchLanguage() {
        if (empty($this->_primaryTableRow->language) === false) {
            if ($this->_fields['Language']->hasMultipleValues()) {
                $result = explode(',', $this->_primaryTableRow->language);
            } else {
                $result = $this->_primaryTableRow->language;
            }
        } else {
            if ($this->_fields['Language']->hasMultipleValues()) {
                $result = array();
            } else {
                $result = null;
            }
        }
        return $result;
    }

    /**
     * FIXME: Set the document's type.
     *
     * @param  string|Opus_Document_Type $type The type of the document.
     * @return void
     */
    public function setType($type) {
        // TODO: Recreate Document on type change.
    }

    /**
     * Retrieve all Opus_Document instances from the database.
     *
     * @return array Array of Opus_Document objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Document', 'Opus_Db_Documents');
    }


    /**
     * Retrieve an array of all document titles associated with the corresponding
     * document id.
     *
     * @return array Associative array with title=>id entries.
     */
    public static function getAllDocumentTitles() {
        $table = new Opus_Db_DocumentTitleAbstracts();
        $select = $table->select()
            ->from($table, array('value', 'document_id'))
            ->where('type = ?', 'main');
        $rows = $table->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row->value] = $row->document_id;
        }
        return $result;
    }

    /**
     * Returns an array of all document ids.
     *
     * @return array Array of document ids.
     */
    public static function getAllIds() {
        $table = new Opus_Db_Documents();
        $select = $table->select()
            ->from($table, array('id'));
        $rows = $table->fetchAll($select)->toArray();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }
        return $ids;
    }

    /**
     * Adds the document to a collection.
     *
     * @param  int  $role Role of the collection.
     * @param  int  $id   Id of the collection
     * @return void
     */
    public function addToCollection($role_id, $collection_id) {
        $collection = new Opus_Collection($role_id, $collection_id);
        $collection->addEntry($this);
    }

    /**
     * Instantiates an Opus_Document from xml as delivered by the toXml()
     * method. Standard behaviour is overwritten due to the type parameter that
     * needs to be passed into the Opus_Document constructor.
     *
     * @param  string|DomDocument  $xml The xml representing the model.
     * @return Opus_Model_Abstract The Opus_Model derived from xml.
     */
    public static function fromXml($xml) {
        if ($xml instanceof DomDocument) {
            $domXml = $xml;
        } else if (is_string($xml)) {
            $domXml = new DomDocument('1.0', 'UTF-8');
            $domXml->loadXml($xml);
        } else {
            throw new Opus_Model_Exception('Either DomDocument or xml string must be passed.');
        }
        $type = $domXml->documentElement->getAttribute('Type');
        // Remove type attribute, which is only needed for document
        // construction.
        $domXml->documentElement->removeAttribute('Type');
        $document = new Opus_Document(null, $type);
        $result = Opus_Model_Abstract::_populateModelFromXml($document,
                $domXml->documentElement);
        return $result;
    }

    /**
     * Add URN identifer if no identifier has been added yet.
     *
     * @return void
     */
    protected function _storeIdentifierUrn() {
        $value = $this->_fields['IdentifierUrn']->getValue();
        if (true === empty($value)) {
            // TODO contructor values should be configurable
            $urn = new Opus_Identifier_Urn('swb', '14', 'opus');
            $urn_value = $urn->getUrn($this->getId());
            $urn_model = new Opus_Identifier();
            $urn_model->setValue($urn_value);
            $this->setIdentifierUrn($urn_model);
        }

        if (array_key_exists('options', $this->_externalFields['IdentifierUrn']) === true) {
            $options = $this->_externalFields['IdentifierUrn']['options'];
        } else {
            $options = null;
        }

        $this->_storeExternal($this->_fields['IdentifierUrn']->getValue(), $options);
    }

}
