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
 * @author      Simone Finkbeiner <simone.finkbeiner@ub.uni-stuttgart.de>
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
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
class Opus_Document extends Opus_Model_AbstractDb {


    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Documents';

    /**
     * Plugins to load
     *
     * @var array
     */
    protected $_plugins = array(
        'Opus_Document_Plugin_Index' => null,
        'Opus_Document_Plugin_XmlCache' => null,
        'Opus_Document_Plugin_SequenceNumber' => null,
        'Opus_Document_Plugin_IdentifierUrn' => null
    );

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
                            'model' => 'Opus_TitleAbstract',
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
            'Identifier' => array(
                            'model' => 'Opus_Identifier',
                            'fetch' => 'lazy'
            ),
            'IdentifierOld' => array(
                            'model' => 'Opus_Identifier',
                            'options' => array('type' => 'old'),
                            'fetch' => 'lazy'
            ),
            'IdentifierSerial' => array(
                            'model' => 'Opus_Identifier',
                            'options' => array('type' => 'serial'),
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
            'Reference' => array(
                           'model' => 'Opus_Reference',
                           'fetch' => 'lazy'
            ),
            'IdentifierArxiv' => array(
                            'model' => 'Opus_Identifier',
                            'options' => array('type' => 'arxiv')
            ),
            'IdentifierPubmed' => array(
                            'model' => 'Opus_Identifier',
                            'options' => array('type' => 'pmid')
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
            'ReferenceOpus4' => array(
                            'model' => 'Opus_Reference',
                            'options' => array('type' => 'opus4-id')
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
            'Licence' => array(
                            'model' => 'Opus_Licence',
                            'through' => 'Opus_Model_Dependent_Link_DocumentLicence',
                            'fetch' => 'lazy'
            ),
            'Person' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonAdvisor' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'advisor'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonAuthor' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'author'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonContributor' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'contributor'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonEditor' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'editor'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonReferee' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'referee'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonOther' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'other'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonTranslator' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'translator'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'PersonSubmitter' => array(
                            'model' => 'Opus_Person',
                            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => array('role' => 'submitter'),
                            'sort_order' => array('sort_order' => 'ASC'),   // <-- We need a sorted authors list.
                            'sort_field' => 'SortOrder',
                            'fetch' => 'lazy'
            ),
            'Series' => array(
                            'model' => 'Opus_Series',
                            'through' => 'Opus_Model_Dependent_Link_DocumentSeries',      
                            'fetch' => 'lazy'
            ),
            'Subject' => array(
                            'model' => 'Opus_Subject',
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

            'ThesisPublisher' => array(
                            'model' => 'Opus_DnbInstitute',
                            'through' => 'Opus_Model_Dependent_Link_DocumentDnbInstitute',
                            'options' => array('role' => 'publisher'),
                            'addprimarykey' => array('publisher'),
                            'fetch' => 'lazy'
            ),

            'ThesisGrantor' => array(
                            'model' => 'Opus_DnbInstitute',
                            'through' => 'Opus_Model_Dependent_Link_DocumentDnbInstitute',
                            'options' => array('role' => 'grantor'),
                            'addprimarykey' => array('grantor'),
                            'fetch' => 'lazy'
            ),
    );

    /**
     * Initialize the document's fields.  The language field needs special
     * treatment to initialize the default values.
     *
     * @return void
     */
    protected function _init() {
        $fields = array(
            "CompletedDate", "CompletedYear",
            "ContributingCorporation",
            "CreatingCorporation",
            "ThesisDateAccepted", "ThesisYearAccepted",
            "Edition",
            "Issue",
            "Language",
            "PageFirst", "PageLast", "PageNumber",
            "PublishedDate", "PublishedYear",
            "PublisherName",  "PublisherPlace",
            "PublicationState",
            "ServerDateCreated",
            "ServerDateModified",
            "ServerDatePublished",
            "ServerDateDeleted",
            "ServerState",
            "Type",
            "Volume",
            "BelongsToBibliography",
        );

        foreach ($fields as $fieldname) {
            if (isset($this->_externalFields[$fieldname])) {
                throw new Exception( "Field $fieldname exists in _externalFields" );
            }

            $field = new Opus_Model_Field($fieldname);
            $this->addField($field);
        }

        foreach (array_keys($this->_externalFields) AS $fieldname) {
            $field = new Opus_Model_Field($fieldname);
            $field->setMultiplicity('*');
            $this->addField($field);
        }

        // Initialize available languages
        if (Zend_Registry::isRegistered('Available_Languages') === true) {
            $this->getField('Language')
                    ->setDefault(Zend_Registry::get('Available_Languages'))
                    ->setSelection(true);
        }

        // Type field should be shown as drop-down.
        // TODO: ->setDefault( somehow::getAvailableDocumentTypes() )
        $this->getField('Type')
                ->setSelection(true);

        // Bibliography field is boolean, so make it a checkbox
        $this->getField('BelongsToBibliography')
                ->setCheckbox(true);

        // Initialize available licences
        $licences = Opus_Licence::getAll();
        $this->getField('Licence')
                ->setDefault($licences)
                ->setSelection(true);

        // Add the server (publication) state as a field
        $this->getField('ServerState')
                ->setDefault(array(
                    'unpublished' => 'unpublished',
                    'published' => 'published',
                    'deleted' => 'deleted',
                    'restricted' => 'restricted',
                    'audited' => 'audited',
                    'inprogress' => 'inprogress'))
                ->setSelection(true);

        // Add the allowed values for publication_state column
        $this->getField('PublicationState')
                ->setDefault(array(
                    'draft' => 'draft',
                    'accepted' => 'accepted',
                    'submitted' => 'submitted',
                    'published' => 'published',
                    'updated'=> 'updated'))
                ->setSelection(true);

        // Initialize available date fields and set up date validator
        // if the particular field is present
        $dateFields = array(
            'ThesisDateAccepted', 'CompletedDate', 'PublishedDate',
            'ServerDateCreated',
            'ServerDateModified', 'ServerDatePublished', 'ServerDateDeleted');
        foreach ($dateFields as $fieldName) {
            $this->getField($fieldName)
                    ->setValueModelClass('Opus_Date');
        }

        // Initialize available publishers
        $publishers = Opus_DnbInstitute::getPublishers();
        $this->getField('ThesisPublisher')
                ->setDefault($publishers)
                ->setSelection(true);

        // Initialize available grantors
        $grantors = Opus_DnbInstitute::getGrantors();
        $this->getField('ThesisGrantor')
                ->setDefault($grantors)
                ->setSelection(true);

        $obsoleteSubjects = array(
            'SubjectSwd',
            'SubjectPsyndex',
            'SubjectUncontrolled',
            'SubjectMSC',
            'SubjectDDC',
            );

        foreach ($obsoleteSubjects AS $dummySubjectField) {
            $this->addField(new Opus_Model_Field($dummySubjectField));
        }
    }

    protected function _fetchSubjectSwd() { return; }
    protected function _fetchSubjectPsyndex() { return; }
    protected function _fetchSubjectUncontrolled() { return; }
    protected function _fetchSubjectMSC() { return; }
    protected function _fetchSubjectDDC() { return; }

    /**
     * Store multiple languages as a comma seperated string.
     *
     * @return void
     */
    protected function _storeLanguage() {
        $result = null;
        if ($this->_fields['Language']->getValue() !== null) {
            if ($this->_fields['Language']->hasMultipleValues()) {
                $result = implode(',', $this->_fields['Language']->getValue());
            } else {
                $result = $this->_fields['Language']->getValue();
            }
        }
        $this->_primaryTableRow->language = $result;
    }

    /**
     * Load multiple languages from a comma seperated string.
     *
     * @return array
     */
    protected function _fetchLanguage() {
        $result = null;
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
     * Retrieve all Opus_Document instances from the database.
     *
     * @return array Array of Opus_Document objects.
     *
     * @deprecated
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
     *
     * @deprecated
     */
    public static function getAllByState($state) {
        $finder = new Opus_DocumentFinder();
        $finder->setServerState($state);
        return self::getAll( $finder->ids() );
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByDoctype($sort_reverse = '0') {
        return self::getAllDocumentsByDoctypeByState(null, $sort_reverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string  $state        Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByDoctypeByState($state, $sort_reverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByType($sort_reverse != 1);
        return $finder->ids();
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string  $state        Document state to select, defaults to "published", returning all states if set to NULL.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByPubDate($sort_reverse = '0') {
        return self::getAllDocumentsByPubDateByState(null, $sort_reverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string  $state        Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByPubDateByState($state, $sort_reverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByServerDatePublished($sort_reverse != 1);
        return $finder->ids();
    }

    /**
     * Retrieve an array of all document titles associated with the corresponding
     * document id.
     *
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByAuthors($sort_reverse = '0') {
        return self::getAllDocumentsByAuthorsByState(null, $sort_reverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     * This array is sorted by authors (first one only)
     *
     * @param  string  $state        Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByAuthorsByState($state, $sort_reverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByAuthorLastname($sort_reverse != 1);
        return $finder->ids();
    }

    /**
     * Retrieve an array of all document titles associated with the corresponding
     * document id.
     *
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByTitles($sort_reverse = '0') {
        return self::getAllDocumentsByTitlesByState(null, $sort_reverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string  $state        Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByTitlesByState($state, $sort_reverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByTitleMain($sort_reverse != 1);
        return $finder->ids();
    }

    /**
     * Returns an array of all document ids.
     *
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array Array of document ids.
     *
     * @deprecated
     */
    public static function getAllIds($sort_reverse = '0') {
        return self::getAllIdsByState(null, $sort_reverse);
    }

    /**
     * Returns all document that are in a specific server (publication) state.
     *
     * @param  string  $state        Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string  $sort_reverse Optional indicator for list order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array The list of documents in the specified state.
     *
     * @deprecated
     */
    public static function getAllIdsByState($state = 'published', $sort_reverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderById($sort_reverse != 1);
        return $finder->ids();
    }

    /**
     * Retrieve an array of all document_id titles associated with the given
     * (identifier, value)
     *
     * @param string $value value of the identifer that should be queried in DB
     * @param string [$type] optional string describing the type of identifier (default is urn)
     * @return array array with all ids of the entries.
     *
     * @deprecated
     */
    public static function getDocumentByIdentifier($value, $type = 'urn') {
        $finder = new Opus_DocumentFinder();
        $finder->setIdentifierTypeValue($type, $value);
        return $finder->ids();
    }

    /**
     * Returns all documents that are in publication state and whose ids are within the given range.
     * Used by SolrIndexBuilder.
     *
     * @param int $start The smallest document id to be considered.
     * @param int $end The largest document id to be considered.
     * @return array The list of document ids within the given range.
     *
     * @deprecated
     */
    public static function getAllPublishedIds($start, $end) {
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('published');

        if (isset($start)) {
            $finder->setIdRangeStart($start);
        }

        if (isset($end)) {
            $finder->setIdRangeEnd($end);
        }

        return $finder->ids();
    }

    /**
     * Returns the earliest date (server_date_published) of all documents.
     *
     * @return string|null /^\d{4}-\d{2}-\d{2}$/ on success, null otherwise
     *
     * @deprecated
     */
    public static function getEarliestPublicationDate() {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');
        $select = $table->select()->from($table, 'min(server_date_published) AS min_date')
                ->where('server_date_published IS NOT NULL')
                ->where('TRIM(server_date_published) != \'\'');
        $timestamp = $table->fetchRow($select)->toArray();

        if (!isset($timestamp['min_date'])) {
            return null;
        }

        $matches = array();
        if (preg_match("/^(\d{4}-\d{2}-\d{2})T/", $timestamp['min_date'], $matches) > 0) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Returns an array of ids for all document of the specified type.
     *
     * @param  string  $typename The name of the document type.
     * @return array Array of document ids.
     *
     * @deprecated
     */
    public static function getIdsForDocType($typename) {
        $finder = new Opus_DocumentFinder();
        $finder->setType($typename);
        return $finder->ids();
    }

    /**
     * Returns an array of ids for all documents published between two dates.
     *
     * @param  string  $from    (Optional) The earliest publication date to include.
     * @param  string  $until   (Optional) The latest publication date to include.
     * @return array Array of document ids.
     *
     * @deprecated
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

        $searchRange = null;
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

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');
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
     * Fetch all Opus_Collection objects for this document.
     *
     * @return array An array of Opus_Collection objects.
     */
    protected function _fetchCollection() {
        $collections = array();

        if (false === is_null($this->isNewRecord())) {
            $ids = Opus_Collection::fetchCollectionIdsByDocumentId($this->getId());

            foreach ($ids as $id) {
                $collection = new Opus_Collection($id);
                $collections[] = $collection;
            }
        }

        return $collections;
    }

    /**
     * Store all Opus_Collection objects for this document.
     *
     * @return void
     */
    protected function _storeCollection($collections) {
        if (true === is_null($this->getId())) {
            return;
        }

        Opus_Collection::unlinkCollectionsByDocumentId($this->getId());

        foreach ($collections AS $collection) {
            if ($collection->isNewRecord()) {
                $collection->store();
            }

            if ($collection->holdsDocumentById($this->getId())) {
                continue;
            }
            $collection->linkDocumentById($this->getId());
        }
    }

    /**
     * Add URN identifer if no identifier has been added yet.
     *
     * @return void
     */
    protected function _storeIdentifierUrn($identifiers) {
        if (!is_array($identifiers)) {
            $identifiers = array($identifiers);
        }

        if ($this->isIdentifierSet($identifiers)) {
            // get constructor values from configuration file
            // if nothing has been configured there, do not build an URN!
            // at least the first two values MUST be set
            $config = Zend_Registry::get('Zend_Config');

            if (isset($config) && is_object($config->urn)) {
                $nid = $config->urn->nid;
                $nss = $config->urn->nss;

                if (!empty($nid) && !empty($nss)) {
                    $urn = new Opus_Identifier_Urn($nid, $nss);
                    $urn_value = $urn->getUrn($this->getId());
                    $urn_model = new Opus_Identifier();
                    $urn_model->setValue($urn_value);
                    $this->setIdentifierUrn($urn_model);
                }
            }
        }

        $options = null;
        if (array_key_exists('options', $this->_externalFields['IdentifierUrn'])) {
            $options = $this->_externalFields['IdentifierUrn']['options'];
        }
        $this->_storeExternal($this->_fields['IdentifierUrn']->getValue(), $options);
    }

    private function isIdentifierSet($identifiers) {
        foreach ($identifiers as $identifier) {
            if ($identifier instanceof Opus_Identifier) {
                $tmp = $identifier->getValue();
                if (!empty($tmp)) {
                    return false;
                }
            } else if (!empty($identifier)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add UUID identifier if none has been added.
     *
     * @return void
     */
    protected function _storeIdentifierUuid($value) {
        if (true === is_null($value)) {
            $uuid_model = new Opus_Identifier;
            $uuid_model->setValue(Opus_Identifier_UUID::generate());
            $this->setIdentifierUuid($uuid_model);
        }

        $options = null;
        if (array_key_exists('options', $this->_externalFields['IdentifierUuid']) === true) {
            $options = $this->_externalFields['IdentifierUuid']['options'];
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
        // De-fatalize Search Index errors.
        try {
            // Remove from index
            $indexer = new Opus_SolrSearch_Index_Indexer();
            $indexer->removeDocumentFromEntryIndex($this);
        }
        catch (Exception $e) {
            $this->logger("removeDocumentFromIndex failed: " . $e->getMessage());
        }
       
        $this->setServerState('deleted');
        $this->store();        
    }

    /**
     * Remove the model instance from the database.
     *
     * @see    Opus_Model_AbstractDb::delete()
     * @return void
     *
     * TODO: Only remove if document does not have an URN/DOI!
     */
    public function deletePermanent() {        
        $this->delete();

        // remove all files permanently       
        $files = $this->getFile();

        foreach ($files as $file) {
            try {
                $file->doDelete($file->delete());
            }
            catch (Opus_Storage_FileNotFoundException $osfnfe) {
                // if the file was not found (permant delete still succeeds)
                $this->logger($osfnfe->getMessage());
            }
        }
       
        parent::delete();
    }

    /**
     * Set internal fields ServerDatePublished and ServerDateModified.
     *
     * @return mixed Anything else then null will cancel the storage process.
     */
    protected function _preStore() {
        $result = parent::_preStore();

        $date = new Opus_Date();
        $date->setNow();
        if (true === $this->isNewRecord()) {
            if (is_null( $this->getServerDateCreated() )) {
                $this->setServerDateCreated($date);
            }
            if (null === $this->getServerDatePublished()) {
                $this->setServerDatePublished($date);
            }
        }
        $this->setServerDateModified($date);

        return $result;
    }

    /**
     * Fetch a list of all available document types.
     *
     * @deprecated
     */
    public static function fetchDocumentTypes() {
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('published');
        return $finder->groupedTypes();
    }

    /**
     * Log document errors.  Prefixes every log entry with document id.
     *
     * @param string $message
     */
    protected function logger($message) {
        $registry = Zend_Registry::getInstance();
        $logger = $registry->get('Zend_Log');
        $logger->info( $this->getDisplayName() . ": $message" );
    }

    /**
     * Erase all document fields, which are passed in $fieldnames array.
     * 
     * @param array $fieldnames
     * @return Opus_Document Provide fluent interface.
     *
     * @throws Opus_Document_Exception If a given field does no exist.
     */
    public function deleteFields($fieldnames) {
        foreach ($fieldnames AS $fieldname) {
            $field = $this->_getField($fieldname);
            if (is_null($field)) {
                throw new Opus_Document_Exception("Cannot delete field $fieldname: Does not exist?");
            }
            $field->setValue(null);
        }
        return $this;
    }

}
