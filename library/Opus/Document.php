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
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
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
     * The workflow of the document.
     *
     * @var string
     */
    protected $_workflow = null;

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
                'model' => 'Opus_Abstract',
                'options' => array('type' => 'abstract'),
                'fetch' => 'lazy'
            ),
            'TitleParent' => array(
                'model' => 'Opus_Title',
                'options' => array('type' => 'parent'),
                'fetch' => 'lazy'
            ),
            'TitleSub' => array(
                'model' => 'Opus_Title',
                'options' => array('type' => 'sub'),
                'fetch' => 'lazy'
            ),
            'TitleAdditional' => array(
                'model' => 'Opus_Title',
                'options' => array('type' => 'additional'),
                'fetch' => 'lazy'
            ),
            'IdentifierUuid' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'uuid'),
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
            'IdentifierOpac' => array(
                'model' => 'Opus_Identifier',
                'options' => array('type' => 'opac-id')
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
                'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
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
                'options' => array('language' => 'deu', 'type' => 'swd'),
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
            'Collection' => array(
                'model' => 'Opus_Collection',
                'fetch' => 'lazy'
            ),
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
    public function __construct($id = null, $type = null, $workflow = null) {
        if (($id === null and $type === null) or
            ($id !== null and $type !== null) or
            (true == is_string($type) and true === is_null($workflow))) {
            throw new InvalidArgumentException('Either id or type object or type and workflow name must be passed.');
        }
        if ($id === null and $type !== null) {
            $this->_type = $type;
            $this->_workflow = $workflow;
            parent::__construct(null, new self::$_tableGatewayClass);
        } else {
            parent::__construct($id, new self::$_tableGatewayClass);
            $this->_type = $this->_primaryTableRow->type;
            $this->_workflow = $this->_primaryTableRow->workflow;
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
                $this->_builder = new Opus_Document_Builder(new Opus_Document_Type($this->_type, $this->_workflow));
                $this->_primaryTableRow->type = $this->_type;
                $this->_primaryTableRow->workflow = $this->_workflow;
            } else if ($this->_type instanceof Opus_Document_Type) {
                $this->_builder = new Opus_Document_Builder($this->_type);
                $this->_primaryTableRow->type = $this->_type->getName();
                $this->_primaryTableRow->workflow = $this->_type->getWorkflow();
            } else {
                throw new Opus_Model_Exception('Unkown document type.');
            }
        } else if ($this->_type === null) {
            $this->_builder = new Opus_Document_Builder(new
                    Opus_Document_Type($this->_primaryTableRow->type, $this->_primaryTableRow->workflow));
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

        // Add the document's type as a selection
        $documentType = new Opus_Model_Field('Type');
        $doctypes = Opus_Document_Type::getAvailableTypeNames($this->_workflow);
        $doctypeList = array();
        // transfer the type list given by Opus_Document_Type::getAvailableTypeNames()
        // into a list with associated index key names
        foreach($doctypes as $dt) {
            $doctypeList[$dt] = $dt;
        }
        $documentType->setDefault($doctypeList)
                ->setSelection(true);
        $documentType->setValue($this->_type);
        $this->addField($documentType);

        // Add the document's workflow as a selection
        $workFlow = new Opus_Model_Field('Workflow');
        $workFlowList = array();
        foreach (Opus_Document_Type::getAvailableWorkflows() as $wf) {
            $workFlowList[$wf] = $wf;
        }
        $workFlow->setDefault($workFlowList)->setSelection(true);
        $workFlow->setValue($this->_workflow);
        $this->addField($workFlow);

        // Add the server (publication) state as a field
        $serverState = new Opus_Model_Field('ServerState');
        $serverState->setDefault(array('unpublished' => 'unpublished', 'published' => 'published', 'deleted' => 'deleted'));
        $serverState->setSelection(true);
        $this->addField($serverState);

        // Add the server modification date as a field
        $serverDateModified = new Opus_Model_Field('ServerDateModified');
        $this->addField($serverDateModified);

        // Add the server publication date as a field
        $serverDatePublished = new Opus_Model_Field('ServerDatePublished');
        $this->addField($serverDatePublished);

        // Initialize available date fields and set up date validator
        // if the particular field is present
        $dateFields = array(
            'DateAccepted', 'CompletedDate', 'PublishedDate',
            'ServerDateModified', 'ServerDatePublished',
            'ServerDateUnlocking', 'ServerDateValid');
        foreach ($dateFields as $fieldName) {
            $field = $this->_getField($fieldName);
            if (null !== $field ) {
                $field->setValidator(new Zend_Validate_Date);
            }
        }

        // Add UUID field to be used as an external identifier.
        $uuidField = new Opus_Model_Field('IdentifierUuid');
        $uuidField->setMultiplicity(1);
        $this->addField($uuidField);

        // Add collection field.
        $collectionField = new Opus_Model_Field('Collection');
        $collectionField->setMultiplicity('*');
        $this->addField($collectionField);
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
     * Overwrite setter, type is immutable.
     *
     * @param  string|Opus_Document_Type $type The type of the document.
     * @return void
     */
    public function setType($type) {
    }

    /**
     * Overwrite setter, workflow is immutable.
     *
     * @param  string $workFlow The workflow of the document.
     * @return void
     */
    public function setWorkflow($workflow) {
    }

    /**
     * Retrieve all Opus_Document instances from the database.
     *
     * @return array Array of Opus_Document objects.
     */
    public static function getAll(array $ids = null) {
        return self::getAllFrom('Opus_Document', 'Opus_Db_Documents', $ids);
    }

    /**
     * Returns all document that are in a specific server (publication) state.
     *
     * @param  string  $state The state to check for.
     * @throws Opus_Model_Exception Thrown if an unknown state is encountered.
     * @return array The list of documents in the specified state.
     */
    public static function getAllByState($state) {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $rows = $table->fetchAll($table->select()->where('server_state = ?', $state));
        $result = array();
        foreach ($rows as $row) {
            $result[] = new Opus_Document($row);
        }
        return $result;
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @return array Associative array with id=>array(titles) entries.
     *
     * TODO: replace more flexible *and* more efficent getAllDocumentIdsByStateSorted(...)
     */
    public static function getAllDocumentTitlesByState($state) {
        $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from(array('d' => 'documents'),
                    array())
            ->join(array('t' => 'document_title_abstracts'),
                    't.document_id = d.id')
            ->where('d.server_state = ?', $state)
            ->where('t.type = ?', 'main');
        $rows = $db->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['document_id']][] = $row['value'];
        }

        // Check if there are documents without title
        // FIXME: This statement is obsolete, if we use a left-join between
        // FIXME: (documents, document_title_abstracts).  Fetching *all*
        // FIXME: entries from documents, just toi make this check is not
        // FIXME: reasonable!
        $select = $db->select()
            ->from('documents')
            ->where('server_state = ?', $state);
        $rows = $db->fetchAll($select);

        foreach ($rows as $row) {
            if (array_key_exists($row['id'], $result) === false) {
                $result[$row['id']][] = 'No title specified for ID ' . $row['id'];
            }
        }

        return $result;
    }

    /**
     * Retrieve an array of all document indices of a document in a certain server
     * (publication) state.  The list can be sorted by the given sort keys.
     *
     * @return array Array with IDs returned by database.  No additional information.
     *
     * TODO: Limit number of hits
     */
     public static function getAllDocumentIdsByStateSorted($state, $sort_options) {
         $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from(array('d' => 'documents'),
                    array('id', 'published_date AS date', 'server_date_published'))
            ->joinLeft(array('t' => 'document_title_abstracts'),
                    't.document_id = d.id',
                    array('t.value AS title'))
            ->join(array('p' => 'persons'),
                    NULL,
                    array('last_name', 'first_name'))
            ->joinLeft(array('pd' => 'link_persons_documents'),
                   'pd.document_id = d.id and pd.person_id = p.id')
            ->where('d.server_state = ?', $state)
            ->group('d.id');
         /* without authors $select = $db->select()
             ->from(array('d' => 'documents'), array('id', 'published_date AS date', 'server_date_published'))
             ->joinLeft(array('t' => 'document_title_abstracts'), 't.document_id = d.id', array('t.value AS title'))
             ->where('t.type = ?', 'main')
             ->where('d.server_state = ?', $state)
             ->group('d.id');
*/
         if (is_array($sort_options)) {
            foreach ($sort_options as $sort_order => $sort_reverse) {
               if (is_null($sort_reverse)) {
                  $sort_reverse = false;
               }

               $select = $select->order( "$sort_order " . ($sort_reverse === true ? 'DESC' : 'ASC') );
            }
         }

         $result = array();
         $rows = $db->fetchAll($select);

         foreach ($rows as $row) {
            $result[] = $row['id'];
         }

         return $result;
    }

    /**
     * Retrieve an array of all document indices of a document in a certain server
     * (publication) state.  The list can be sorted by the given sort keys.
     *
     * @return array Array with IDs returned by database.  No additional information.
     *
     * TODO: Limit number of hits
     */
     public static function getAllDocumentIdsSorted($sort_options) {
         $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from(array('d' => 'documents'),
                    array('id', 'published_date AS date', 'server_date_published'))
            ->joinLeft(array('t' => 'document_title_abstracts'),
                    't.document_id = d.id',
                    array('t.value AS title'))
            ->join(array('p' => 'persons'),
                    NULL,
                    array('last_name', 'first_name'))
            ->joinLeft(array('pd' => 'link_persons_documents'),
                   'pd.document_id = d.id and pd.person_id = p.id')
            ->group('d.id');
         /* without authors $select = $db->select()
             ->from(array('d' => 'documents'), array('id', 'published_date AS date', 'server_date_published'))
             ->joinLeft(array('t' => 'document_title_abstracts'), 't.document_id = d.id', array('t.value AS title'))
             ->where('t.type = ?', 'main')
             ->where('d.server_state = ?', $state)
             ->group('d.id');
*/
         if (is_array($sort_options)) {
            foreach ($sort_options as $sort_order => $sort_reverse) {
               if (is_null($sort_reverse)) {
                  $sort_reverse = false;
               }

               $select = $select->order( "$sort_order " . ($sort_reverse === true ? 'DESC' : 'ASC') );
            }
         }

         $result = array();
         $rows = $db->fetchAll($select);

         foreach ($rows as $row) {
            $result[] = $row['id'];
         }

         return $result;
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @return array Associative array with id=>array(titles) entries.
     */
    public static function getAllDocumentsByDoctypeByState($state) {
        $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from(array('d' => 'documents'),
                    array('id', 'type'))
            ->where('d.server_state = ?', $state);
        $rows = $db->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row['type'];
        }

        return $result;
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @return array Associative array with id=>array(titles) entries.
     */
    public static function getAllDocumentsByDoctype() {
        $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from(array('d' => 'documents'),
                    array('id', 'type'));
        $rows = $db->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['id']] = $row['type'];
        }

        return $result;
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     * This array is sorted by authors (first one only)
     *
     * @return array Associative array with id=>array(titles) entries.
     */
    public static function getAllDocumentAuthorsByState($state) {
        $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from(array('d' => 'documents'),
                    array('id'))
            ->join(array('t' => 'document_title_abstracts'),
                    't.document_id = d.id',
                    array('value'))
            ->join(array('p' => 'persons'),
                    NULL,
                    array('last_name', 'first_name'))
            ->join(array('pd' => 'link_persons_documents'),
                   'pd.document_id = d.id and pd.person_id = p.id')
            ->where('d.server_state = ?', $state)
            ->where('t.type = ?', 'main')
            ->where('pd.role = ?', 'author')
            ->group('d.id')
            ->order('p.last_name');
        $rows = $db->fetchAll($select);


        $result = array();
        foreach ($rows as $row) {
            $result[$row['document_id']][] = $row['value'];
        }

        // Check if there are documents without title
        // FIXME: This statement is obsolete, if we use a left-join between
        // FIXME: (documents, document_title_abstracts).  Fetching *all*
        // FIXME: entries from documents, just toi make this check is not
        // FIXME: reasonable!
        $select = $db->select()
            ->from('documents')
            ->where('server_state = ?', $state);
        $rows = $db->fetchAll($select);

        foreach ($rows as $row) {
            if (array_key_exists($row['id'], $result) === false) {
                $result[$row['id']][] = 'No title specified for ID ' . $row['id'];
            }
        }

        return $result;
    }

    /**
     * Retrieve an array of all document titles associated with the corresponding
     * document id.
     *
     * @return array Associative array with id=>arary(titles) entries.
     */
    public static function getAllDocumentAuthors() {
        $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from(array('d' => 'documents'),
                    array('id'))
            ->join(array('t' => 'document_title_abstracts'),
                    't.document_id = d.id',
                    array('value'))
            ->join(array('p' => 'persons'),
                    NULL,
                    array('last_name', 'first_name'))
            ->join(array('pd' => 'link_persons_documents'),
                   'pd.document_id = d.id and pd.person_id = p.id')
            ->where('t.type = ?', 'main')
            ->where('pd.role = ?', 'author')
            ->group('d.id')
            ->order('p.last_name');
        $rows = $db->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row['document_id']][] = $row['value'];
        }

        // Check for further results without title
        // FIXME: This statement is obsolete, if we use a left-join between
        // FIXME: (documents, document_title_abstracts).  Fetching *all*
        // FIXME: entries from documents, just toi make this check is not
        // FIXME: reasonable!
        $select = $db->select()
            ->from('documents');
        $rows = $db->fetchAll($select);

        foreach ($rows as $row) {
            if (array_key_exists($row['id'], $result) === false) {
                $result[$row['id']][] = 'No title specified for ID ' . $row['id'];
            }
        }
        return $result;
    }

    /**
     * Retrieve an array of all document titles associated with the corresponding
     * document id.
     *
     * @return array Associative array with id=>arary(titles) entries.
     */
    public static function getAllDocumentTitles() {
        $table = new Opus_Db_DocumentTitleAbstracts();
        $select = $table->select()
            ->from($table, array('value', 'document_id'))
            ->where('type = ?', 'main');
        $rows = $table->fetchAll($select);

        $result = array();
        foreach ($rows as $row) {
            $result[$row->document_id][] = $row->value;
        }

        // Check for further results without title
        // FIXME: This statement is obsolete, if we use a left-join between
        // FIXME: (documents, document_title_abstracts).  Fetching *all*
        // FIXME: entries from documents, just toi make this check is not
        // FIXME: reasonable!
        $table = new Opus_Db_Documents();
        $rows = $table->fetchAll();

        foreach ($rows as $row) {
            if (array_key_exists($row->id, $result) === false) {
                $result[$row->id][] = 'No title specified for ID ' . $row->id;
            }
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
     * Returns an array of latest document ids.
     *
     * @return array Array of document ids.
     */
    public static function getLatestIds($num = 10) {
        $db = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass)->getAdapter();
        $select = $db->select()
            ->from('documents', array('id'))
            ->where('server_state = ?', 'published')
            ->order('server_date_published DESC');
        $rows = $db->fetchAll($select);

        $ids = array();
        // limit does not work properly?!
        // so lets take a for-counter...
        for ($n= 0; $n < $num; $n++) {
        	$ids[] = $rows[$n]['id'];
        }

        return $ids;
    }

    /**
     * Returns the earliest date (server_date_published) of all documents.
     *
     * @return int
     */
    public static function getEarliestPublicationDate() {
        $table = new Opus_Db_Documents();
        $select = $table->select()->from($table, 'min(server_date_published)');
        $timestamp = $table->fetchRow($select)->toArray();
        return $timestamp['min(server_date_published)'];
    }

    /**
     * Returns an array of ids for all document of the specified type.
     *
     * @param  string  $typename The name of the document type.
     * @return array Array of document ids.
     */
    public static function getIdsForDocType($typename) {
        $table = new Opus_Db_Documents();
        $select = $table->select()
            ->from($table, array('id'))->where('type = ?', $typename);
        $rows = $table->fetchAll($select)->toArray();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }
        return $ids;
    }

    /**
     * Returns an array of ids for all documents published between two dates.
     *
     * @param  string  $from    (Optional) The earliest publication date to include.
     * @param  string  $until   (Optional) The latest publication date to include.
     * @return array Array of document ids.
     */
    public static function getIdsForDateRange($from = null, $until = null) {
        try {
            if (true === is_null($from)) {
                $from = new Zend_Date(self::getEarliestPublicationDate());
            } else {
                $from = new Zend_Date($from);
            }
        } catch (Exception $e) {
            throw new Exception('Invalid date string supplied: ' . $from);
        }
        try {
            if (true === is_null($until)) {
                $until = new Zend_Date;
            } else {
                $until = new Zend_Date($until);
            }
        } catch (Exception $e) {
            throw new Exception('Invalid date string supplied: ' . $until);
        }

        if (true === $from->equals($until)) {
            $searchRange = 'LIKE "' . $from->toString('yyyy-MM-dd') . '%"';
        } else {
            // TODO FIXME
            //
            // For some strange reason a between does not include the
            // latest day. E.g. if until date is 2009-05-10 then the
            // result does not include data sets with 2009-05-10 only newer dates.
            //
            // If we add one day then is result as expected but maybe wrong?
            //
            // Between range looks like $from < $until and not $from <= $until
            $until->addDay(1);
            $searchRange = 'BETWEEN "' . $from->toString('yyyy-MM-dd') . '%" AND "' . $until->toString('yyyy-MM-dd') . '%"';
        }

        $table = new Opus_Db_Documents();
        // TODO server date publish really needed ?
        // because server date modified is in any case setted to latest change date
        $select = $table->select()
            ->from($table, array('id'))
            ->where('server_date_published ' . $searchRange)
            ->orWhere('server_date_modified ' . $searchRange);

        $rows = $table->fetchAll($select)->toArray();
        $ids = array();
        foreach ($rows as $row) {
            $ids[] = $row['id'];
        }
        return $ids;
    }

    /**
     * Use custom fetching supplied by Opus_Collection_Information
     *
     * @return array An array of Opus_Collection objects.
     */
    protected function _fetchCollection() {
        if (false === $this->isNewRecord()) {
            $collections = array();
            $coll_ids = Opus_Collection_Information::getAllDocumentCollectionIDs($this->getId());
            foreach ($coll_ids as $role) {
                $roles_id = $role['roles_id'];
                foreach ($role['collections_id'] as $index => $collection) {
                    $collections_id = $collection;
                    $collections[] = new Opus_Collection($collections_id, $roles_id);
                }
            }
            return $collections;
        }
    }

    /**
     * Instantiates an Opus_Document from xml as delivered by the toXml()
     * method. Standard behaviour is overwritten due to the type parameter that
     * needs to be passed into the Opus_Document constructor.
     *
     * @param  string|DomDocument  $xml The xml representing the model.
     * @param  Opus_Model_Xml      $customDeserializer (Optional) Specify a custom deserializer object.
     *                                                 Please note that the construction attributes setting
     *                                                 will be overwritten.
     * @return Opus_Model_Abstract The Opus_Model derived from xml.
     */
    public static function fromXml($xml, Opus_Model_Xml $customDeserializer = null) {
        if (null === $customDeserializer) {
            $deserializer = new Opus_Model_Xml;
        } else {
            $deserializer = $customDeserializer;
        }
        $deserializer->setConstructionAttributesMap(array('Opus_Document' => array(null, 'Type', 'Workflow')));
        return parent::fromXml($xml, $deserializer);
    }

    /**
     * Add URN identifer if no identifier has been added yet.
     *
     * @return void
     */
    protected function _storeIdentifierUrn() {
        $identifierUrn = $this->getField('IdentifierUrn')->getValue();

        if (false === is_array($identifierUrn)) {
            $identifiers = array($identifierUrn);
        } else {
            $identifiers = $identifierUrn;
        }

        $set = true;
        foreach ($identifiers as $identifier) {
            if (true === ($identifier instanceof Opus_Identifier)) {
                $tmp = $identifier->getValue();
                if (false === empty($tmp)) {
                    $set = false;
                }
            } else if (false === empty($identifier)) {
                $set = false;
            }
        }

        if (true === $set) {
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

    /**
     * Add UUID identifier if none has been added.
     *
     * @return void
     */
    protected function _storeIdentifierUuid() {
        if (true === is_null($this->_fields['IdentifierUuid']->getValue())) {
            $uuid_model = new Opus_Identifier;
            $uuid_model->setValue(Opus_Identifier_UUID::generate());
            $this->setIdentifierUuid($uuid_model);
        }
        if (array_key_exists('options', $this->_externalFields['IdentifierUuid']) === true) {
            $options = $this->_externalFields['IdentifierUuid']['options'];
        } else {
            $options = null;
        }
        $this->_storeExternal($this->_fields['IdentifierUuid']->getValue(), $options);
    }

    /**
     * Set document server state to unpublished if new record or
     * no value is set.
     *
     * @param string $value Server state of document.
     * @return void
     */
    protected  function _storeServerState($value) {
        if (true === empty($value)) {
            $value = 'unpublished';
            $this->setServerState($value);
        }
        $this->_primaryTableRow->server_state = $value;
    }

    /**
     * Remove the model instance from the database.
     * This only means: set state to deleted
     *
     * @return void
     */
    public function delete() {
   		$config = Zend_Registry::get('Zend_Config');

        $searchEngine = $config->searchengine->engine;
        if (empty($searchEngine) === true) {
            $searchEngine = 'Lucene';
	    }
        $engineclass = 'Opus_Search_Index_'.$searchEngine.'_Indexer';
        // Remove from index
        $indexer = new $engineclass();
        $indexer->removeDocumentFromEntryIndex($this);
        $this->setServerState('deleted');
        $this->store();
    }

    /**
     * Remove the model instance from the database.
     *
     * @see    Opus_Model_AbstractDb::delete()
     * @return void
     */
    public function deletePermanent() {
   		$config = Zend_Registry::get('Zend_Config');

        $searchEngine = $config->searchengine->engine;
        if (empty($searchEngine) === true) {
            $searchEngine = 'Lucene';
	    }
        $engineclass = 'Opus_Search_Index_'.$searchEngine.'_Indexer';
        // Remove from index
        $indexer = new $engineclass();
        $indexer->removeDocumentFromEntryIndex($this);
        parent::delete();
    }

    /**
     * Provide read access to internal type field.
     *
     * @return string
     */
    public function getType() {
        return $this->_getField('Type')->getValue();
    }

    /**
     * Set internal fields ServerDatePublished and ServerDateModified.
     *
     * @return mixed Anything else then null will cancel the storage process.
     */
    protected function _preStore() {
        parent::_preStore();
        $now = date('Y-m-d');
        if (true === $this->isNewRecord()) {
            if (null === $this->getServerDatePublished()) {
                $this->setServerDatePublished($now);
            }
        }
        $this->setServerDateModified($now);
    }

    /**
     * Returns an array of document ids based on restrictions from
     * an OAI request.
     *
     * restriction array should contain keys and values
     *
     * - ServerState: a list of document states
     * - Type: a list of document types
     * - Date: an array with keys and values:
     * -- from: given from date format (YYYY-MM-DD)
     * -- until: given until date format until (YYYY-MM-DD)
     *
     * example call:
     * Opus_Document::getIdsOfOaiRequest('ServerState' => array('published'),
     *                                   'Type' => array('article'),
     *                                   'Date' => array(
     *                                      'from' => '2009-11-11'
     *                                      )
     *                                   );
     *
     * @param array $restriction
     * @return array
     */
    public static function getIdsOfOaiRequest(array $restriction) {

        $table = new Opus_Db_Documents();
        $select = $table->select()->from($table, array('id'));

        // add server state restrictions
        if (true === array_key_exists('ServerState', $restriction) and
            true === is_array($restriction['ServerState'])) {
            $stateRestr = array();
            foreach ($restriction['ServerState'] as $state) {
                $stateRestr[] = 'server_state = "' . $state . '"';
            }
            $stateWhere = implode(' OR ', $stateRestr);
            $select->where($stateWhere);
        }

        // add possible type / set restrictions
        if (true === array_key_exists('Type', $restriction) and
            true === is_array($restriction['Type'])) {
            $typeRestr = array();
            foreach ($restriction['Type'] as $pubType) {
                $typeRestr[] = 'type = "' . $pubType . '"';
            }
            $typeWhere = implode(' OR ', $typeRestr);
            $select->where($typeWhere);
        }

        // date restrictions
        if (true === array_key_exists('Date', $restriction) and
            true === is_array($restriction['Date'])) {

            if (false === array_key_exists('from', $restriction['Date'])) {
                $from = new Zend_Date(self::getEarliestPublicationDate());
            } else {
                $from = new Zend_Date($restriction['Date']['from']);
            }

            if (false === array_key_exists('until', $restriction['Date'])) {
                $until = new Zend_Date;
            } else {
                $until = new Zend_Date($restriction['Date']['until']);
            }

            if (true === $from->equals($until)) {
                $searchRange = 'LIKE "' . $from->toString('yyyy-MM-dd') . '%"';
            } else {
                // TODO FIXME
                //
                // For some strange reason a between does not include the
                // latest day. E.g. if until date is 2009-05-10 then the
                // result does not include data sets with 2009-05-10 only newer dates.
                //
                // If we add one day then is result as expected but maybe wrong?
                //
                // Between range looks like $from < $until and not $from <= $until
                $until->addDay(1);
                $searchRange = 'BETWEEN "' . $from->toString('yyyy-MM-dd') . '%" AND "' . $until->toString('yyyy-MM-dd') . '%"';
            }

            $dateWhere = 'server_date_published ' . $searchRange . ' OR server_date_modified ' . $searchRange;
            $select->where($dateWhere);
        }
        $rows = $table->fetchAll($select)->toArray();

        $result = array();
        foreach ($rows as $row) {
            $result[] = $row['id'];
        }

        return $result;
    }

}
