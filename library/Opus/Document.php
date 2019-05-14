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
 * @author      Michael Lang <lang@zib.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Simone Finkbeiner <simone.finkbeiner@ub.uni-stuttgart.de>
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2014-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for documents in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 *
 * The following are the magic methods for the simple fields of Opus_Document.
 *
 * @method void setCompletedDate(Opus_Date $date)
 * @method Opus_Date getCompletedDate()
 *
 * @method void setCompletedYear(integer $year)
 * @method integer getCompletedYear()
 *
 * @method void setContributingCorporation(string $value)
 * @method string getContributingCorporation()
 *
 * @method void setCreatingCorporation(string $value)
 * @method string getCreatingCorporation()
 *
 * @method void setThesisDateAccepted(Opus_Date $date)
 * @method Opus_Date getThesisDateAccepted()
 *
 * @method void setThesisYearAccepted(integer $year)
 * @method integer getThesisYearAccepted()
 *
 * @method void setEdition(string $value)
 * @method string getEdition()
 *
 * @method void setEmbargoDate(Opus_Date $date)
 * @method Opus_Date getEmbargoDate()
 *
 * @method void setIssue(string $issue)
 * @method string getIssue()
 *
 * @method void setLanguage(string $lang)
 * @method string getLanguage()
 *
 * @method void setPageFirst(string $pageFirst)
 * @method string getPageFirst()
 *
 * @method void setPageLast(string $pageLast)
 * @method string getPageLast()
 *
 * @method void setPageNumber(string $pageNumber)
 * @method string getPageNumber()
 *
 * @method void setPublishedDate(Opus_Date $date)
 * @method Opus_Date getPublishedDate()
 *
 * @method void setPublishedYear(integer $year)
 * @method integer getPublishedYear()
 *
 * @method void setPublisherName(string $name)
 * @method string getPublisherName()
 *
 * @method void setPublisherPlace(string $place)
 * @method string getPublisherPlace()
 *
 * @method void setPublicationState(string $state)
 * @method string getPublicationState()
 *
 * @method void setServerDateCreated(Opus_Date $date)
 * @method Opus_Date getServerDateCreated()
 *
 * @method void setServerDateModified(Opus_Date $date)
 * @method Opus_Date getServerDateModified()
 *
 * @method void setServerDatePublished(Opus_Date $date)
 * @method Opus_Date getServerDatePublished()
 *
 * @method void setServerDateDeleted(Opus_Date $date)
 * @method Opus_Date getServerDateDeleted()
 *
 * @method void setServerState(string $state)
 * @method string getServerState()
 *
 * @method void setType(string $type)
 * @method string getType()
 *
 * @method void setVolume(string $volume)
 * @method string getVolume()
 *
 * @method void setBelongsToBibliography(boolean $bibliography)
 * @method boolean getBelongsToBibliography()
 *
 * Methods for complex fields.
 *
 * @method Opus_Note addNote()
 * @method void setNote(Opus_Note[] $notes)
 * @method Opus_Note[] getNote()
 *
 * @method Opus_Patent addPatent()
 * @method void setPatent(Opus_Patent[] $patents)
 * @method Opus_Patent[] getPatent()
 *
 * @method Opus_Title addTitleMain()
 * @method Opus_Title[] getTitleMain()
 * @method void setTitleMain(Opus_Title[] $titles)
 *
 * @method Opus_Title addTitleParent()
 * @method Opus_Title[] getTitleParent()
 * @method void setTitleParent(Opus_Title[] $titles)
 *
 * @method Opus_Title addTitleSub()
 * @method Opus_Title[] getTitleSub()
 * @method void setTitleSub(Opus_Title[] $titles)
 *
 * @method Opus_Title addTitleAdditional()
 * @method Opus_Title[] getTitleAdditional()
 * @method void setTitleAdditional(Opus_Title[] $titles)
 *
 * @method Opus_TitleAbstract addTitleAbstract()
 * @method Opus_TitleAbstract[] getTitleAbstract()
 * @method void setTitleAbstract(Opus_TitleAbstract[] $abstracts)
 *
 * @method Opus_Subject addSubject(Opus_Subject[] $subject = null)
 * @method Opus_Subject[] getSubject()
 * @method void setSubject(Opus_Subject[] $subjects)
 *
 * @method Opus_Model_Dependent_Link_DocumentDnbInstitute addThesisGrantor(Opus_DnbInstitute $institute)
 * @method Opus_Model_Dependent_Link_DocumentDnbInstitute[] getThesisGrantor()
 * @method void setThesisGrantor(Opus_DnbInstitute[] $institutes)
 *
 * @method Opus_DnbInstitute addThesisPublisher(Opus_DnbInstitute $institute)
 * @method Opus_Model_Dependent_Link_DocumentDnbInstitute[] getThesisPublisher()
 * @method void setThesisPublisher(Opus_DnbInstitute[] $institutes)
 *
 * @method Opus_Enrichment addEnrichment(Opus_Enrichment $enrichment = null)
 * @method void setEnrichment(Opus_Enrichment[] $enrichments)
 *
 * TODO correct?
 * @method void addCollection(Opus_Collection $collection)
 * @method Opus_Collection[] getCollection()
 * @method void setCollection(Opus_Collection[] $collections)
 *
 * TODO correct?
 * @method void addSeries(Opus_Series $series)
 * @method Opus_Series[] getSeries()
 * @method void setSeries(Opus_Series[] $series)
 *
 * @method Opus_Identifier addIdentifier(Opus_Identifier $identifier = null)
 * @method void setIdentifier(Opus_Identifier[] $identifiers)
 * @method Opus_Identifier[] getIdentifier()
 *
 * @method Opus_Reference addReference(Opus_Reference $reference = null)
 * @method void setReference(Opus_Reference[] $references)
 * @method Opus_Reference[] getReference()
 *
 * @method Opus_Model_Dependent_Link_DocumentPerson addPerson(Opus_Person $person)
 * @method void setPerson(Opus_Model_Dependent_Link_DocumentPerson[] $persons)
 * @method Opus_Model_Dependent_Link_DocumentPerson[] getPerson()
 */
class Opus_Document extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Documents';

    private static $defaultPlugins = null;

    /**
     * Plugins to load
     *
     * WARN: order of plugins is NOT(!) arbitrary, e.g., Index plugin must come
     * before XmlCache plugin
     *
     * (The Index plugin forces, as a side effect, a cache rebuild in case of a
     * cache miss since the index procedure consumes the XML representation of
     * the document. The subsequent call of the XmlCache plugin will operate on
     * the refreshed cache. Since we expect a cache hit, no further operations
     * are performed in this case.
     *
     * If we execute the XmlCache plugin first, a double reindexing will occur
     * in case of a cache miss. The cache rebuilding will issue a reindex
     * operation as a side effect. A subsequent call of the Index plugin issues
     * a second call of the reindex operation which is obsolete.)
     */
    public function getDefaultPlugins()
    {
        if (is_null(self::$defaultPlugins)) {
            $config = Zend_Registry::get('Zend_Config'); // use function

            if (isset($config->model->plugins->document)) {
                $plugins = $config->model->plugins->document;
                self::$defaultPlugins = $plugins->toArray();
            } else {
                self::$defaultPlugins = [
                    'Opus_Document_Plugin_Index',
                    'Opus_Document_Plugin_XmlCache',
                    'Opus_Document_Plugin_IdentifierUrn',
                    'Opus_Document_Plugin_IdentifierDoi'
                ];
            }
        }

        return self::$defaultPlugins;
    }

    public function setDefaultPlugins($plugins)
    {
        self::$defaultPlugins = $plugins;
    }

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Documents table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = [
        'TitleMain' => [
            'model' => 'Opus_Title',
            'options' => ['type' => 'main'],
            'fetch' => 'lazy'
        ],
        'TitleAbstract' => [
            'model' => 'Opus_TitleAbstract',
            'options' => ['type' => 'abstract'],
            'fetch' => 'lazy'
        ],
        'TitleParent' => [
            'model' => 'Opus_Title',
            'options' => ['type' => 'parent'],
            'fetch' => 'lazy'
        ],
        'TitleSub' => [
            'model' => 'Opus_Title',
            'options' => ['type' => 'sub'],
            'fetch' => 'lazy'
        ],
        'TitleAdditional' => [
            'model' => 'Opus_Title',
            'options' => ['type' => 'additional'],
            'fetch' => 'lazy'
        ],
        'Identifier' => [
            'model' => 'Opus_Identifier',
            'fetch' => 'lazy'
        ],
        'IdentifierOld' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'old'],
            'fetch' => 'lazy'
        ],
        'IdentifierSerial' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'serial'],
            'fetch' => 'lazy'
        ],
        'IdentifierUuid' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'uuid'],
            'fetch' => 'lazy'
        ],
        'IdentifierIsbn' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'isbn'],
            'fetch' => 'lazy'
        ],
        'IdentifierUrn' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'urn']
        ],
        'IdentifierDoi' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'doi']
        ],
        'IdentifierHandle' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'handle']
        ],
        'IdentifierUrl' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'url']
        ],
        'IdentifierIssn' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'issn']
        ],
        'IdentifierStdDoi' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'std-doi']
        ],
        'IdentifierCrisLink' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'cris-link']
        ],
        'IdentifierSplashUrl' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'splash-url']
        ],
        'IdentifierOpus3' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'opus3-id']
        ],
        'IdentifierOpac' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'opac-id']
        ],
        'Reference' => [
            'model' => 'Opus_Reference',
            'fetch' => 'lazy'
        ],
        'IdentifierArxiv' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'arxiv']
        ],
        'IdentifierPubmed' => [
            'model' => 'Opus_Identifier',
            'options' => ['type' => 'pmid']
        ],
        'ReferenceIsbn' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'isbn'],
            'fetch' => 'lazy'
        ],
        'ReferenceUrn' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'urn']
        ],
        'ReferenceDoi' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'doi']
        ],
        'ReferenceHandle' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'handle']
        ],
        'ReferenceUrl' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'url']
        ],
        'ReferenceIssn' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'issn']
        ],
        'ReferenceStdDoi' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'std-doi']
        ],
        'ReferenceCrisLink' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'cris-link']
        ],
        'ReferenceSplashUrl' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'splash-url']
        ],
        'ReferenceOpus4' => [
            'model' => 'Opus_Reference',
            'options' => ['type' => 'opus4-id']
        ],
        'Note' => [
            'model' => 'Opus_Note',
            'fetch' => 'lazy'
        ],
        'Patent' => [
            'model' => 'Opus_Patent',
            'fetch' => 'lazy'
        ],
        'Enrichment' => [
            'model' => 'Opus_Enrichment',
            'fetch' => 'lazy'
        ],
        'Licence' => [
            'model' => 'Opus_Licence',
            'through' => 'Opus_Model_Dependent_Link_DocumentLicence',
            'fetch' => 'lazy'
        ],
        'Person' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonAdvisor' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'advisor'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonAuthor' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'author'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonContributor' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'contributor'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonEditor' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'editor'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonReferee' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'referee'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonOther' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'other'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonTranslator' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'translator'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'PersonSubmitter' => [
            'model' => 'Opus_Person',
            'through' => 'Opus_Model_Dependent_Link_DocumentPerson',
                            'options'  => ['role' => 'submitter'],
                            'sort_order' => ['sort_order' => 'ASC'],   // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch' => 'lazy'
        ],
        'Series' => [
            'model' => 'Opus_Series',
            'through' => 'Opus_Model_Dependent_Link_DocumentSeries',
            'fetch' => 'lazy'
        ],
        'Subject' => [
            'model' => 'Opus_Subject',
            'fetch' => 'lazy'
        ],
        'File' => [
            'model' => 'Opus_File',
            'fetch' => 'lazy'
        ],

        'Collection' => [
            'model' => 'Opus_Collection',
            'fetch' => 'lazy'
        ],

        'ThesisPublisher' => [
            'model' => 'Opus_DnbInstitute',
            'through' => 'Opus_Model_Dependent_Link_DocumentDnbInstitute',
            'options' => ['role' => 'publisher'],
            'addprimarykey' => ['publisher'],
            'fetch' => 'lazy'
        ],

        'ThesisGrantor' => [
            'model' => 'Opus_DnbInstitute',
            'through' => 'Opus_Model_Dependent_Link_DocumentDnbInstitute',
            'options' => ['role' => 'grantor'],
            'addprimarykey' => ['grantor'],
            'fetch' => 'lazy'
        ]
    ];

    /**
     * Initialize the document's fields.  The language field needs special
     * treatment to initialize the default values.
     *
     * @return void
     */
    protected function _init()
    {
        $fields = array(
            "BelongsToBibliography",
            "CompletedDate", "CompletedYear",
            "ContributingCorporation",
            "CreatingCorporation",
            "ThesisDateAccepted", "ThesisYearAccepted",
            "Edition",
            "EmbargoDate",
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
            "Volume"
        );

        foreach ($fields as $fieldname) {
            if (isset($this->_externalFields[$fieldname])) {
                throw new Exception("Field $fieldname exists in _externalFields");
            }

            $field = new Opus_Model_Field($fieldname);
            $this->addField($field);
        }

        foreach (array_keys($this->_externalFields) AS $fieldname) {
            $field = new Opus_Model_Field($fieldname);
            $field->setMultiplicity('*');
            $this->addField($field);
        }

        // Initialize available date fields and set up date validator
        // if the particular field is present
        $dateFields = array(
            'ThesisDateAccepted', 'CompletedDate', 'PublishedDate',
            'ServerDateCreated',
            'ServerDateModified', 'ServerDatePublished', 'ServerDateDeleted', 'EmbargoDate');
        foreach ($dateFields as $fieldName) {
            $this->getField($fieldName)
                    ->setValueModelClass('Opus_Date');
        }

        $this->initFieldOptionsForDisplayAndValidation();
    }

    public function initFieldOptionsForDisplayAndValidation() {
        // Initialize available languages
        if (Zend_Registry::isRegistered('Available_Languages') === true) {
            $this->getField('Language')
                    ->setDefault(Zend_Registry::get('Available_Languages'));
        }
        $this->getField('Language')->setSelection(true);

        // Type field should be shown as drop-down.
        // TODO: ->setDefault( somehow::getAvailableDocumentTypes() )
        $this->getField('Type')
                ->setSelection(true);

        // Bibliography field is boolean, so make it a checkbox
        $this->getField('BelongsToBibliography')
                ->setCheckbox(true);

        // Initialize available licences
        $this->getField('Licence')
                ->setSelection(true);

        // Add the server (publication) state as a field
        $this->getField('ServerState')
                ->setDefault(
                    array(
                    'unpublished' => 'unpublished',
                    'published' => 'published',
                    'deleted' => 'deleted',
                    'restricted' => 'restricted',
                    'audited' => 'audited',
                    'inprogress' => 'inprogress')
                )
                ->setSelection(true);

        // Add the allowed values for publication_state column
        $this->getField('PublicationState')
                ->setDefault(
                    array(
                    'draft' => 'draft',
                    'accepted' => 'accepted',
                    'submitted' => 'submitted',
                    'published' => 'published',
                    'updated'=> 'updated')
                )
                ->setSelection(true);

        // Initialize available publishers
        $this->getField('ThesisPublisher')
                ->setSelection(true);

        // Initialize available grantors
        $this->getField('ThesisGrantor')
                ->setSelection(true);
    }

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
            }
            else {
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
            }
            else {
                $result = $this->_primaryTableRow->language;
            }
        }
        else {
            if ($this->_fields['Language']->hasMultipleValues()) {
                $result = array();
            }
            else {
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
        return self::getAll($finder->ids());
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string $sortReverse Optional indicator for order: 1 = descending; else ascending order. Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByDoctype($sortReverse = '0') {
        return self::getAllDocumentsByDoctypeByState(null, $sortReverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string $state    Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByDoctypeByState($state, $sortReverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByType($sortReverse != 1);
        return $finder->ids();
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string  $state Document state to select, defaults to "published", returning all states if set to NULL.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByPubDate($sortReverse = '0') {
        return self::getAllDocumentsByPubDateByState(null, $sortReverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string  $state Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string  $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByPubDateByState($state, $sortReverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByServerDatePublished($sortReverse != 1);
        return $finder->ids();
    }

    /**
     * Retrieve an array of all document titles associated with the corresponding
     * document id.
     *
     * @param  string  $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByAuthors($sortReverse = '0') {
        return self::getAllDocumentsByAuthorsByState(null, $sortReverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     * This array is sorted by authors (first one only)
     *
     * @param  string  $state Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string  $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByAuthorsByState($state, $sortReverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByAuthorLastname($sortReverse != 1);
        return $finder->ids();
    }

    /**
     * Retrieve an array of all document titles associated with the corresponding
     * document id.
     *
     * @param  string $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByTitles($sortReverse = '0') {
        return self::getAllDocumentsByTitlesByState(null, $sortReverse);
    }

    /**
     * Retrieve an array of all document titles of a document in a certain server
     * (publication) state associated with the corresponding document id.
     *
     * @param  string $state Document state to select, defaults to "published", returning all states if set to NULL.
     * @param  string $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array array with all ids of the entries in the desired order.
     *
     * @deprecated
     */
    public static function getAllDocumentsByTitlesByState($state, $sortReverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderByTitleMain($sortReverse != 1);
        return $finder->ids();
    }

    /**
     * Returns an array of all document ids.
     *
     * @param  string  $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array Array of document ids.
     *
     * @deprecated
     */
    public static function getAllIds($sortReverse = '0') {
        return self::getAllIdsByState(null, $sortReverse);
    }

    /**
     * Returns all document that are in a specific server (publication) state.
     *
     * @param string $state Document state to select, defaults to "published", returning all states if set to NULL.
     * @param string $sortReverse Optional indicator for order: 1 = descending; else ascending order.  Defaults to 0.
     * @return array The list of documents in the specified state.
     *
     * @deprecated
     */
    public static function getAllIdsByState($state = 'published', $sortReverse = '0') {
        $finder = new Opus_DocumentFinder();
        if (isset($state)) {
            $finder->setServerState($state);
        }
        $finder->orderById($sortReverse != 1);
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
            }
            else {
                $from = new Zend_Date($from);
            }
        } catch (Exception $e) {
            throw new Exception('Invalid date string supplied: ' . $from);
        }
        try {
            if (true === is_null($until)) {
                $until = new Zend_Date;
            }
            else {
                $until = new Zend_Date($until);
            }
        } catch (Exception $e) {
            throw new Exception('Invalid date string supplied: ' . $until);
        }

        $searchRange = null;
        if (true === $from->equals($until)) {
            $searchRange = 'LIKE "' . $from->toString('yyyy-MM-dd') . '%"';
        }
        else {
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
            $searchRange = 'BETWEEN "' . $from->toString('yyyy-MM-dd') . '%" AND "' . $until->toString('yyyy-MM-dd')
                . '%"';
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
     * Bulk update of ServerDateModified for documents matching selection
     *
     * @param Opus_Date $date Opus_Date-Object holding the date to be set
     * @param array $ids array of document ids
     */
    public static function setServerDateModifiedByIds($date, $ids) {
        // Update wird nur ausgeführt, wenn IDs übergeben werden
        if (is_null($ids) || count($ids) == 0) {
            return;
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);


        $where = $table->getAdapter()->quoteInto('id IN (?)', $ids);

        try {
            $table->update(array('server_date_modified' => "$date"), $where);
        } catch (Exception $e) {
            $logger = Zend_Registry::get('Zend_Log');
            if (!is_null($logger)) {
                $logger->err(__METHOD__ . ' ' . $e);
            }
        }
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
                    $urnValue = $urn->getUrn($this->getId());
                    $urnModel = new Opus_Identifier();
                    $urnModel->setValue($urnValue);
                    $this->setIdentifierUrn($urnModel);
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
            }
            else if (!empty($identifier)) {
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
            $uuidModel = new Opus_Identifier;
            $uuidModel->setValue(Opus_Identifier_UUID::generate());
            $this->setIdentifierUuid($uuidModel);
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
     * Sets document to state deleted.
     *
     * Documents are not deleted from database like other model objects. Calling
     * deletePermanent removes a document from the database.
     */
    public function delete() {
        $this->callPluginMethod('preDelete');

        $this->setServerState('deleted');
        $this->store();

        $this->callPluginMethod('postDelete', $this->getId());
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
     * Returns title in document language.
     * @return Opus_Title
     *
     * TODO could be done using the database directly, but Opus_Title would still have to instantiated
     */
    public function getMainTitle($language = null)
    {
        $titles = $this->getTitleMain();

        return $this->_findTitleForLanguage($titles, $language);
    }

    /**
     * Returns the main abstract of the document.
     *
     * @param null $language
     * @return Opus_TitleAbstract
     */
    public function getMainAbstract($language = null)
    {
        $titles = $this->getTitleAbstract();

        return $this->_findTitleForLanguage($titles, $language);
    }

    /**
     * Finds the title for the language or abstract in array.
     *
     * @param $titles Array of titles or abstracts
     * @param $language Language string like 'deu'
     * @return Opus_Title|Opus_TitleAbstract
     */
    protected function _findTitleForLanguage($titles, $language)
    {
        $docLanguage = $this->getLanguage();

        if (is_null($language))
        {
            $language = $docLanguage;
        }

        if (count($titles) > 0)
        {
            if (!is_null($language))
            {
                $titleInDocLang = null;

                foreach ($titles as $title)
                {
                    $titleLanguage = $title->getLanguage();

                    if ($language === $titleLanguage)
                    {
                        return $title;
                    }
                    else if ($docLanguage == $titleLanguage)
                    {
                        $titleInDocLang = $title;
                    }
                }

                // if available return title in document language
                if (!is_null($titleInDocLang))
                {
                    return $titleInDocLang;
                }
            }

            // if no title in document language ist found use first title
            return $titles[0];
        }

        return null;
    }

    /*
     * If param is set, the Opus_File-object on position 'param' is returned. It is equal to the file-id.
     * If no parameter is provided, an array with all files of the document is sorted and returned.
     * The array is sorted ascending according to the sortOrder and the fileId, see compareFiles().
     *
     * Overwrites getFile()-method
     *
     * @return Opus_File[]
     */
    public function getFile($param = null) {
        if (is_null($param)) {
            $files = parent::getFile();
            usort($files, array($this, 'compareFiles'));
            return $files;
        }
        else {
            // return Opus_File-Object
            return parent::getFile($param);
        }

    }

    public function compareFiles($a, $b) {
        if ($a->getSortOrder() == $b->getSortOrder()) {
            return ($a->getId() < $b->getId()) ? -1 : 1;
        }
        return ($a->getSortOrder() < $b->getSortOrder()) ? -1 : 1;
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
            if (is_null($this->getServerDateCreated())) {
                $this->setServerDateCreated($date);
            }
        }
        $this->setServerDateModified($date);

        if (true === $this->isNewRecord() || true === $this->isModified()) {
            // Initially set ServerDatePublished if ServerState == 'published'
            if ($this->getServerState() === 'published') {
                if (is_null($this->getServerDatePublished())) {
                    $this->setServerDatePublished($date);
                }
            }
        }

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
        $logger->info($this->getDisplayName() . ": $message");
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
            switch ($fieldname) {
                case 'BelongsToBibliography':
                    $field->setValue(0);
                    break;
                default:
                    $field->setValue(null);
            }
        }
        return $this;
    }

    /**
     * Compares EmbargoDate with parameter or system time.
     *
     * @param Opus_Date $now
     * @return bool true - if embargo date has passed; false - if not
     */
    public function hasEmbargoPassed($now = null) {
        $embargoDate = $this->getEmbargoDate();

        if (is_null($embargoDate)) {
            return true;
        }
        if (is_null($now)) {
            $now = new Opus_Date();
            $now->setNow();
        }
        // Embargo has passed on the day after the specified date
        $embargoDate->setHour(23);
        $embargoDate->setMinute(59);
        $embargoDate->setSecond(59);
        $embargoDate->setTimezone('Z');
        return ($embargoDate->compare($now) == -1);
    }

    /**
     * Only consider files which are visible in frontdoor.
     *
     * @return bool|void
     */
    public function hasFulltext()
    {
        $files = $this->getFile();

        $files = array_filter($files, function ($file)
        {
            return $file->getVisibleInFrontdoor() === '1';
        });

        return count($files) > 0;
    }

    /**
     * Checks if document is marked as open access.
     *
     * Currently the document has to be assigned to the open access collection.
     *
     * TODO support different mechanisms implemented in separate classes
     *
     * @return bool
     * @throws Exception
     */
    public function isOpenAccess()
    {
        $docId = $this->getId();

        // can only be open access if it has been stored
        if (is_null($docId))
        {
            return false;
        }

        $role = Opus_CollectionRole::fetchByName('open_access');
        $collection = $role->getCollectionByOaiSubset('open_access');

        if (!is_null($collection))
        {
            return $collection->holdsDocumentById($this->getId());
        }
        else {
            return false;
        }
    }

    /**
     * @param null $key Index or key name
     * @return mixed|null
     * @throws Opus_Model_Exception
     * @throws Opus_Security_Exception
     */
    public function getEnrichment($key = null)
    {
        if (is_null($key) || is_numeric($key)) {
            return $this->__call('getEnrichment', [$key]);
        } else {
            $enrichments = $this->__call('getEnrichment', []);

            $matches = array_filter($enrichments, function ($enrichment) use ($key) {
                return $enrichment->getKeyName() == $key;
            });

            switch (count($matches)) {
                case 0:
                    return null;

                case 1:
                    return reset($matches); // get first element in array

                default:
                    return $matches;
            }
        }
    }

    /**
     * Returns the value of an enrichment key
     *
     * @param $key Name of enrichment
     * @return mixed
     * @throws Opus_Model_Exception If the enrichment key does not exist
     * @throws Opus_Security_Exception
     */
    public function getEnrichmentValue($key)
    {
        $enrichment = $this->getEnrichment($key);

        if (!is_null($enrichment)) {
            if (is_array($enrichment)) {
                return array_map(function($value) {
                    return $value->getValue();
                }, $enrichment);
            } else {
                return $enrichment->getValue();
            }
        }
        else {
            $enrichmentKey = Opus_EnrichmentKey::fetchByName($key);

            if (is_null($enrichmentKey)) {
                throw new Opus_Model_Exception('unknown enrichment key');
            } else {
                return null;
            }
        }
    }

    /**
     * Disconnects object from database and stores it as new document.
     *
     * @return mixed
     * @throws Opus_Model_Exception
     *
     * TODO no idea how to do this properly
     * TODO not fully implemented yet
     *
     * @deprecated not implemented yet
     */
    public function storeAsNew() {
        $this->resetDatabaseEntry();

        foreach ($this->_fields as $field) {
            $field->setModified(true);
        }

        foreach ($this->_externalFields as $fieldName => $fieldInfo) {
            $field = $this->getField($fieldName);
            $field->setModified(true);

            $values = $field->getValue();

            foreach ($values as $value) {
                $value->resetDatabaseEntry();
            }
        }

        return $this->store();
    }

    /**
     * Create a new object with the same metadata.
     *
     * All child objects are copied as well. The copy can then be modified and stored without affecting the original
     * object.
     *
     * @return Opus_Document
     * @throws Opus_Model_Exception
     *
     * TODO track copying in enrichment (?) - do it externally to this function
     * TODO not fully implemented yet
     *
     * @deprecated not implemented yet
     */
    public function getCopy() {
        $document = new Opus_Document();

        foreach($this->_fields as $fieldName => $field) {
            $document->getField($fieldName)->setValue($field->getValue());
            // TODO handle simple values
            // TODO handle complex values -> create new objects
            // TODO handle complex values with link objects
        }

        return $document;
    }
}
