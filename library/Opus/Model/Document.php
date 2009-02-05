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
    protected static $_tableGatewayClass = 'Opus_Db_Documents';

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
                'model' => 'Opus_Model_Dependent_Title',
                'options' => array('title_abstract_type' => 'main'),
                'fetch' => 'lazy'
            ),
            'TitleAbstract' => array(
                'model' => 'Opus_Model_Dependent_Abstract',
                'options' => array('title_abstract_type' => 'abstract'),
                'fetch' => 'lazy'
            ),
            'TitleParent' => array(
                'model' => 'Opus_Model_Dependent_Parent',
                'options' => array('title_abstract_type' => 'parent'),
                'fetch' => 'lazy'
            ),
            'Isbn' => array(
                'model' => 'Opus_Model_Dependent_Identifier',
                'options' => array('identifier_type' => 'isbn'),
                'fetch' => 'lazy'
            ),
            'Urn' => array(
                'model' => 'Opus_Model_Dependent_Identifier',
                'options' => array('identifier_type' => 'urn')
            ),
            'Note' => array(
                'model' => 'Opus_Model_Dependent_Note',
                'fetch' => 'lazy'
            ),
            'Patent' => array(
                'model' => 'Opus_Model_Dependent_Patent',
                'fetch' => 'lazy'
            ),
            'Enrichment' => array(
                'model' => 'Opus_Model_Dependent_Enrichment',
                'fetch' => 'lazy'
            ),
            'Institute' => array(
                'model' => 'Opus_Model_Link_DocumentInstitute',
                'fetch' => 'lazy'
            ),
            'Licence' => array(
                'model' => 'Opus_Model_Licence',
                'through' => 'Opus_Model_Dependent_Link_DocumentLicence',
                'fetch' => 'lazy'
            ),
            'PersonAdvisor' => array(
                'model' => 'Opus_Model_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'advisor'),
                'fetch' => 'lazy'
            ),
            'PersonAuthor' => array(
                'model' => 'Opus_Model_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'author'),
                'fetch' => 'lazy'
            ),
            'PersonContributor' => array(
                'model' => 'Opus_Model_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'contributor'),
                'fetch' => 'lazy'
            ),
            'PersonEditor' => array(
                'model' => 'Opus_Model_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'editor'),
                'fetch' => 'lazy'
            ),
            'PersonReferee' => array(
                'model' => 'Opus_Model_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'referee'),
                'fetch' => 'lazy'
            ),
            'PersonOther' => array(
                'model' => 'Opus_Model_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'other'),
                'fetch' => 'lazy'
            ),
            'PersonTranslator' => array(
                'model' => 'Opus_Model_Person',
                'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                'options'  => array('role' => 'translator'),
                'fetch' => 'lazy'
            ),
            'SubjectSwd' => array(
                'model' => 'Opus_Model_Dependent_Subject',
                'options' => array('subject_type' => 'swd'),
                'fetch' => 'lazy'
            ),
            'File' => array(
                'model' => 'Opus_Model_Dependent_File',
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
        if (($id === null and $type === null) or ($id !== null and $type !== null)) {
            throw new InvalidArgumentException('Either id or type must be passed.');
        }
        if ($id === null and $type !== null) {
            $this->_type = $type;
            parent::__construct(null, new self::$_tableGatewayClass);
        } else {
            parent::__construct($id, new self::$_tableGatewayClass);
            $this->_type = $this->_primaryTableRow->document_type;
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
                $this->_primaryTableRow->document_type = str_replace('_', ' ', $this->_type);
            } else if ($this->_type instanceof Opus_Document_Type) {
                $this->_builder = new Opus_Document_Builder($this->_type);
                $this->_primaryTableRow->document_type = str_replace('_', ' ', $this->_type->getName());
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
            $licences = Opus_Model_Licence::getAll();
            $this->getField('Licence')->setDefault($licences)
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
     * Retrieve all Opus_Model_Document instances from the database.
     *
     * @return array Array of Opus_Model_Document objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Model_Document', 'Opus_Db_Documents');
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
            ->from($table, array('title_abstract_value', 'documents_id'))
            ->where('title_abstract_type = ?', 'main');
        $rows = $table->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row->title_abstract_value] = $row->documents_id;
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
            ->from($table, array('documents_id'));
        $rows = $table->fetchAll($select)->toArray();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row['documents_id'];
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
        $collection = new Opus_Model_Collection($role_id, $collection_id);
        $collection->addEntry($this);
    }

}
