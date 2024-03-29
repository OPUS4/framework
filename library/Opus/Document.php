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
 * @copyright   Copyright (c) 2014, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Exception;
use Opus\Common\Collection;
use Opus\Common\CollectionRole;
use Opus\Common\Config;
use Opus\Common\Date;
use Opus\Common\DocumentInterface;
use Opus\Common\EnrichmentKey;
use Opus\Common\Identifier;
use Opus\Common\IdentifierInterface;
use Opus\Common\Model\DocumentLifecycleListener;
use Opus\Common\Model\ModelException;
use Opus\Common\ServerStateConstantsInterface;
use Opus\Common\Storage\FileNotFoundException;
use Opus\Document\DocumentException;
use Opus\Identifier\Urn;
use Opus\Identifier\UUID;
use Opus\Model\AbstractDb;
use Opus\Model\Dependent\Link\DocumentDnbInstitute;
use Opus\Model\Dependent\Link\DocumentPerson;
use Opus\Model\Field;

use function array_filter;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_walk;
use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function is_numeric;
use function is_object;
use function reset;
use function strtolower;
use function substr;
use function usort;

/**
 * Domain model for documents in the Opus framework
 *
 * @uses        \Opus\Model\AbstractModel
 *
 * The following are the magic methods for the simple fields of Opus\Document.
 *
 * @category    Framework
 * @package     Opus
 * @method static Document new()
 * @method static Document get(int $docId)
 * @method void setCompletedDate(Date $date)
 * @method Date getCompletedDate()
 * @method void setCompletedYear(integer $year)
 * @method integer getCompletedYear()
 * @method void setContributingCorporation(string $value)
 * @method string getContributingCorporation()
 * @method void setCreatingCorporation(string $value)
 * @method string getCreatingCorporation()
 * @method void setThesisDateAccepted(Date $date)
 * @method Date getThesisDateAccepted()
 * @method void setThesisYearAccepted(integer $year)
 * @method integer getThesisYearAccepted()
 * @method void setEdition(string $value)
 * @method string getEdition()
 * @method void setEmbargoDate(Date $date)
 * @method Date getEmbargoDate()
 * @method void setIssue(string $issue)
 * @method string getIssue()
 * @method void setLanguage(string $lang)
 * @method string getLanguage()
 * @method void setPageFirst(string $pageFirst)
 * @method string getPageFirst()
 * @method void setPageLast(string $pageLast)
 * @method string getPageLast()
 * @method void setPageNumber(string $pageNumber)
 * @method string getPageNumber()
 * @method void setArticleNumber(string $articleNumber)
 * @method string getArticleNumber()
 * @method void setPublishedDate(Date $date)
 * @method Date getPublishedDate()
 * @method void setPublishedYear(integer $year)
 * @method integer getPublishedYear()
 * @method void setPublisherName(string $name)
 * @method string getPublisherName()
 * @method void setPublisherPlace(string $place)
 * @method string getPublisherPlace()
 * @method void setPublicationState(string $state)
 * @method string getPublicationState()
 * @method void setServerDateCreated(Date|string $date)
 * @method Date getServerDateCreated()
 * @method void setServerDateModified(Date $date)
 * @method Date getServerDateModified()
 * @method void setServerDatePublished(Date|string $date)
 * @method Date getServerDatePublished()
 * @method void setServerDateDeleted(Date $date)
 * @method Date getServerDateDeleted()
 * @method string getServerState()
 * @method void setType(string $type)
 * @method string getType()
 * @method void setVolume(string $volume)
 * @method string getVolume()
 * @method void setBelongsToBibliography(boolean $bibliography)
 * @method boolean getBelongsToBibliography()
 *
 * Methods for complex fields.
 * @method Note addNote()
 * @method void setNote(Note[] $notes)
 * @method Note[] getNote()
 * @method Patent addPatent()
 * @method void setPatent(Patent[] $patents)
 * @method Patent[] getPatent()
 * @method Title addTitleMain()
 * @method Title[] getTitleMain()
 * @method void setTitleMain(Title[] $titles)
 * @method Title addTitleParent()
 * @method Title[] getTitleParent()
 * @method void setTitleParent(Title[] $titles)
 * @method Title addTitleSub()
 * @method Title[] getTitleSub()
 * @method void setTitleSub(Title[] $titles)
 * @method Title addTitleAdditional()
 * @method Title[] getTitleAdditional()
 * @method void setTitleAdditional(Title[] $titles)
 * @method TitleAbstract addTitleAbstract()
 * @method TitleAbstract[] getTitleAbstract()
 * @method void setTitleAbstract(TitleAbstract[] $abstracts)
 * @method Subject addSubject(Subject[] $subject = null)
 * @method Subject[] getSubject()
 * @method void setSubject(Subject[] $subjects)
 * @method DocumentDnbInstitute addThesisGrantor(DnbInstitute $institute)
 * @method DocumentDnbInstitute[] getThesisGrantor()
 * @method void setThesisGrantor(DnbInstitute[] $institutes)
 * @method DnbInstitute addThesisPublisher(DnbInstitute $institute)
 * @method DocumentDnbInstitute[] getThesisPublisher()
 * @method void setThesisPublisher(DnbInstitute[] $institutes)
 * @method Enrichment addEnrichment(Enrichment $enrichment = null)
 * @method void setEnrichment(Enrichment[] $enrichments)
 *
 * TODO correct?
 * @method void addCollection(Collection $collection)
 * @method Collection[] getCollection()
 * @method void setCollection(Collection[] $collections)
 *
 * TODO correct?
 * @method void addSeries(Series $series)
 * @method Series[] getSeries()
 * @method void setSeries(Series[] $series)
 * @method Identifier addIdentifier(Identifier $identifier = null)
 * @method void setIdentifier(Identifier[] $identifiers)
 * @method Identifier[] getIdentifier()
 * @method Reference addReference(Reference $reference = null)
 * @method void setReference(Reference[] $references)
 * @method Reference[] getReference()
 * @method DocumentPerson addPerson(Person $person)
 * @method void setPerson(DocumentPerson[] $persons)
 * @method DocumentPerson[] getPerson()
 * @method File addFile()
 *
 * phpcs:disable
 */
class Document extends AbstractDb implements DocumentInterface, ServerStateConstantsInterface
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\Documents::class;

    /**
     * Zeigt an, ob der Wert von serverState verändert wurde. Nur in diesem Fall werden Plugins,
     * die das Interface \Opus\Model\Plugin\ServerStateChangeListenerInterface implementieren, ausgeführt.
     *
     * @var bool
     */
    private $serverStateChanged = false;

    /**
     * sofern der Wert von serverState geändert wurde, wird in dieser
     * Variable der in der Datenbank abgespeicherte Wert als Referenz gehalten
     *
     * @var string
     */
    private $oldServerState;

    private static $defaultPlugins;

    /**
     * @var DocumentLifecycleListener
     */
    private $documentLifecycleListener = null;

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
        if (self::$defaultPlugins === null) {
            $config = Config::get(); // use function

            if (isset($config->model->plugins->document)) {
                $plugins              = $config->model->plugins->document;
                self::$defaultPlugins = $plugins->toArray();
            } else {
                self::$defaultPlugins = [
                    Document\Plugin\XmlCache::class,
                    Document\Plugin\IdentifierUrn::class,
                    Document\Plugin\IdentifierDoi::class,
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
     * Opus\Db\Documents table gateway.
     *
     * @see \Opus\Model\Abstract::$_externalFields
     *
     * @var array
     */
    protected $externalFields = [
        'TitleMain'          => [
            'model'   => Title::class,
            'options' => ['type' => 'main'],
            'fetch'   => 'lazy',
        ],
        'TitleAbstract'      => [
            'model'   => TitleAbstract::class,
            'options' => ['type' => 'abstract'],
            'fetch'   => 'lazy',
        ],
        'TitleParent'        => [
            'model'   => Title::class,
            'options' => ['type' => 'parent'],
            'fetch'   => 'lazy',
        ],
        'TitleSub'           => [
            'model'   => Title::class,
            'options' => ['type' => 'sub'],
            'fetch'   => 'lazy',
        ],
        'TitleAdditional'    => [
            'model'   => Title::class,
            'options' => ['type' => 'additional'],
            'fetch'   => 'lazy',
        ],
        'Identifier'         => [
            'model' => \Opus\Identifier::class,
            'fetch' => 'lazy',
        ],
        'Reference'          => [
            'model' => Reference::class,
            'fetch' => 'lazy',
        ],
        'ReferenceIsbn'      => [
            'model'   => Reference::class,
            'options' => ['type' => 'isbn'],
            'fetch'   => 'lazy',
        ],
        'ReferenceUrn'       => [
            'model'   => Reference::class,
            'options' => ['type' => 'urn'],
        ],
        'ReferenceDoi'       => [
            'model'   => Reference::class,
            'options' => ['type' => 'doi'],
        ],
        'ReferenceHandle'    => [
            'model'   => Reference::class,
            'options' => ['type' => 'handle'],
        ],
        'ReferenceUrl'       => [
            'model'   => Reference::class,
            'options' => ['type' => 'url'],
        ],
        'ReferenceIssn'      => [
            'model'   => Reference::class,
            'options' => ['type' => 'issn'],
        ],
        'ReferenceStdDoi'    => [
            'model'   => Reference::class,
            'options' => ['type' => 'std-doi'],
        ],
        'ReferenceCrisLink'  => [
            'model'   => Reference::class,
            'options' => ['type' => 'cris-link'],
        ],
        'ReferenceSplashUrl' => [
            'model'   => Reference::class,
            'options' => ['type' => 'splash-url'],
        ],
        'ReferenceOpus4'     => [
            'model'   => Reference::class,
            'options' => ['type' => 'opus4-id'],
        ],
        'Note'               => [
            'model' => Note::class,
            'fetch' => 'lazy',
        ],
        'Patent'             => [
            'model' => Patent::class,
            'fetch' => 'lazy',
        ],
        'Enrichment'         => [
            'model' => Enrichment::class,
            'fetch' => 'lazy',
        ],
        'Licence'            => [
            'model'   => Licence::class,
            'through' => Model\Dependent\Link\DocumentLicence::class,
            'fetch'   => 'lazy',
        ],
        'Person'             => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonAdvisor'      => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'advisor'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonAuthor'       => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'author'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonContributor'  => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'contributor'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonEditor'       => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'editor'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonReferee'      => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'referee'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonOther'        => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'other'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonTranslator'   => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'translator'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'PersonSubmitter'    => [
            'model'      => Person::class,
            'through'    => DocumentPerson::class,
            'options'    => ['role' => 'submitter'],
            'sort_order' => ['sort_order' => 'ASC'], // <-- We need a sorted authors list.
            'sort_field' => 'SortOrder',
            'fetch'      => 'lazy',
        ],
        'Series'             => [
            'model'   => Series::class,
            'through' => Model\Dependent\Link\DocumentSeries::class,
            'fetch'   => 'lazy',
        ],
        'Subject'            => [
            'model' => Subject::class,
            'fetch' => 'lazy',
        ],
        'File'               => [
            'model' => File::class,
            'fetch' => 'lazy',
        ],
        'Collection'         => [
            'model' => \Opus\Collection::class,
            'fetch' => 'lazy',
        ],
        'ThesisPublisher'    => [
            'model'         => DnbInstitute::class,
            'through'       => DocumentDnbInstitute::class,
            'options'       => ['role' => 'publisher'],
            'addprimarykey' => ['publisher'],
            'fetch'         => 'lazy',
        ],
        'ThesisGrantor'      => [
            'model'         => DnbInstitute::class,
            'through'       => DocumentDnbInstitute::class,
            'options'       => ['role' => 'grantor'],
            'addprimarykey' => ['grantor'],
            'fetch'         => 'lazy',
        ],
    ];

    /**
     * Initialize the document's fields.  The language field needs special
     * treatment to initialize the default values.
     */
    protected function init()
    {
        $this->documentLifecycleListener = new DocumentLifecycleListener();

        $fields = [
            'BelongsToBibliography',
            'CompletedDate',
            'CompletedYear',
            'ContributingCorporation',
            'CreatingCorporation',
            'ThesisDateAccepted',
            'ThesisYearAccepted',
            'Edition',
            'EmbargoDate',
            'Issue',
            'Language',
            'PageFirst',
            'PageLast',
            'PageNumber',
            'ArticleNumber',
            'PublishedDate',
            'PublishedYear',
            'PublisherName',
            'PublisherPlace',
            'PublicationState',
            'ServerDateCreated',
            'ServerDateModified',
            'ServerDatePublished',
            'ServerDateDeleted',
            'ServerState',
            'Type',
            'Volume',
        ];

        // create internal fields
        foreach ($fields as $fieldname) {
            if (isset($this->externalFields[$fieldname])) {
                throw new Exception("Field $fieldname exists in _externalFields");
            }

            $field = new Field($fieldname);
            $this->addField($field);
        }

        // create external fields
        foreach (array_keys($this->externalFields) as $fieldname) {
            $field = new Field($fieldname);
            $field->setMultiplicity('*');
            $this->addField($field);
        }

        // Initialize available date fields and set up date validator
        // if the particular field is present
        $dateFields = [
            'ThesisDateAccepted',
            'CompletedDate',
            'PublishedDate',
            'ServerDateCreated',
            'ServerDateModified',
            'ServerDatePublished',
            'ServerDateDeleted',
            'EmbargoDate',
        ];
        foreach ($dateFields as $fieldName) {
            $this->getField($fieldName)->setValueModelClass(Date::class);
        }

        $this->initFieldOptionsForDisplayAndValidation();
    }

    public function initFieldOptionsForDisplayAndValidation()
    {
        // Initialize available languages
        $availableLanguages = Config::getInstance()->getAvailableLanguages();
        if ($availableLanguages !== null) {
            $this->getField('Language')->setDefault($availableLanguages);
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
                ->setDefault([
                    'unpublished' => 'unpublished',
                    'published'   => 'published',
                    'deleted'     => 'deleted',
                    'restricted'  => 'restricted',
                    'audited'     => 'audited',
                    'inprogress'  => 'inprogress',
                ])
                ->setSelection(true);

        // Add the allowed values for publication_state column
        $this->getField('PublicationState')
                ->setDefault([
                    'draft'     => 'draft',
                    'accepted'  => 'accepted',
                    'submitted' => 'submitted',
                    'published' => 'published',
                    'updated'   => 'updated',
                ])
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
     */
    protected function _storeLanguage()
    {
        $result = null;
        if ($this->fields['Language']->getValue() !== null) {
            if ($this->fields['Language']->hasMultipleValues()) {
                $result = implode(',', $this->fields['Language']->getValue());
            } else {
                $result = $this->fields['Language']->getValue();
            }
        }
        $this->primaryTableRow->language = $result;
    }

    /**
     * Load multiple languages from a comma seperated string.
     *
     * @return array
     */
    protected function _fetchLanguage()
    {
        $result = null;
        if (empty($this->primaryTableRow->language) === false) {
            if ($this->fields['Language']->hasMultipleValues()) {
                $result = explode(',', $this->primaryTableRow->language);
            } else {
                $result = $this->primaryTableRow->language;
            }
        } else {
            if ($this->fields['Language']->hasMultipleValues()) {
                $result = [];
            } else {
                $result = null;
            }
        }
        return $result;
    }

    /**
     * Retrieve all Opus\Document instances from the database.
     *
     * @deprecated
     *
     * @return array Array of Opus\Document objects.
     */
    public static function getAll(?array $ids = null)
    {
        return self::getAllFrom(self::class, Db\Documents::class, $ids);
    }

    /**
     * Fetch all Opus\Collection objects for this document.
     *
     * @return array An array of Opus\Collection objects.
     */
    protected function _fetchCollection()
    {
        $collections = [];

        if (! $this->isNewRecord()) {
            $ids = Collection::fetchCollectionIdsByDocumentId($this->getId());

            foreach ($ids as $id) {
                $collection    = Collection::get($id);
                $collections[] = $collection;
            }
        }

        return $collections;
    }

    /**
     * Store all Opus\Collection objects for this document.
     */
    protected function _storeCollection($collections)
    {
        if ($this->getId() === null) {
            return;
        }

        Collection::unlinkCollectionsByDocumentId($this->getId());

        foreach ($collections as $collection) {
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
     */
    protected function _storeIdentifierUrn($identifiers)
    {
        if (! is_array($identifiers)) {
            $identifiers = [$identifiers];
        }

        if ($this->isIdentifierSet($identifiers)) {
            // get constructor values from configuration file
            // if nothing has been configured there, do not build an URN!
            // at least the first two values MUST be set
            $config = Config::get();

            if (isset($config) && is_object($config->urn)) {
                $nid = $config->urn->nid;
                $nss = $config->urn->nss;

                if (! empty($nid) && ! empty($nss)) {
                    $urn      = new Urn($nid, $nss);
                    $urnValue = $urn->getUrn($this->getId());
                    $urnModel = Identifier::new();
                    $urnModel->setValue($urnValue);
                    $this->setIdentifierUrn($urnModel);
                }
            }
        }
    }

    protected function _storeIdentifier($identifiers)
    {
        foreach ($identifiers as $identifier) {
            switch ($identifier->getType()) {
                case 'urn':
                    $this->_storeIdentifierUrn($identifiers);
                    break;
                case 'uuid':
                    $this->_storeIdentifierUuid($identifiers);
                    break;
                default:
            }
        }
        $options = null;
        if (array_key_exists('options', $this->externalFields['Identifier'])) {
            $options = $this->externalFields['Identifier']['options'];
        }
        $this->_storeExternal($this->fields['Identifier']->getValue(), $options);
    }

    private function isIdentifierSet($identifiers)
    {
        foreach ($identifiers as $identifier) {
            if ($identifier instanceof IdentifierInterface) {
                $tmp = $identifier->getValue();
                if (! empty($tmp)) {
                    return false;
                }
            } elseif (! empty($identifier)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Add UUID identifier if none has been added.
     */
    protected function _storeIdentifierUuid($value)
    {
        if ($value === null) {
            $uuidModel = Identifier::new();
            $uuidModel->setValue(UUID::generate());
            $this->setIdentifierUuid($uuidModel);
        }
    }

    /**
     * Set document server state to unpublished if new record or
     * no value is set.
     *
     * @param string $value Server state of document.
     */
    protected function _storeServerState($value)
    {
        if (true === empty($value)) {
            $value = self::STATE_UNPUBLISHED;
            $this->setServerState($value);
        }
        $this->primaryTableRow->server_state = $value;
    }

    /**
     * Wenn das Dokument noch nicht in der DB gespeichert wurde, liefert der erste
     * Aufruf von getServerState() den Wert null. In diesem Fall liegt immer eine
     * Änderung des Wertes von serverState vor. Der zuletzt gesetzte Wert von serverState
     * "gewinnt". Andernfalls wird der Wert von getServerState beim ersten Aufruf
     * als Referenz gespeichert. Bei jeder Änderung von serverState wird der neue
     * Wert mit dem gespeicherten Referenz verglichen, um festzustellen, ob es eine
     * Änderung von serverState gegeben hat.
     *
     * @param $serverState
     * @return mixed
     */
    public function setServerState($serverState)
    {
        if ($this->oldServerState === null && ! $this->serverStateChanged) {
            // erste Änderung des Wertes von serverState
            $this->oldServerState = $this->getServerState();
        }

        // Wert wurde bereits durch einen vorhergehenden Methodenaufruf geändert
        // um festzustellen, ob es eine Änderung gab, erfolgt der Vergleich des
        // übergebenen Wert mit dem zuvor zwischengespeicherten Referenzwert
        $this->serverStateChanged = $serverState !== $this->oldServerState;

        return parent::setServerState($serverState);
    }

    /**
     * Changes state of document to deleted.
     *
     * TODO review this function and eliminate once it is clear, that "delete" is simply used like any other state
     *      change
     */
    public function deleteDocument()
    {
        $this->setServerState(self::STATE_DELETED);
        $this->store();
    }

    /**
     * Sets document to state deleted.
     *
     * Documents are not deleted from database like other model objects. Calling
     * delete removes a document from the database.
     *
     * TODO call deleteDocument in this function to trigger state change plugins?
     */
    public function delete()
    {
        $this->setServerState(self::STATE_DELETED); // TODO triggers handling of DOI deletion - better way?

        $this->deleteFiles(); // TODO is this really necessary?
        parent::delete();
    }

    /**
     * Deletes all document files.
     *
     * @throws ModelException
     *
     * TODO this will trigger InvalidateDocumentCache for every file deleted
     */
    public function deleteFiles()
    {
        $files = $this->getFile();

        foreach ($files as $file) {
            try {
                $file->doDelete($file->delete());
            } catch (FileNotFoundException $osfnfe) {
                // if the file was not found (permanent deletion will still succeed)
                $this->log($osfnfe->getMessage());
            }
        }
    }

    /**
     * Returns title in document language.
     *
     * @return Title
     *
     * TODO could be done using the database directly, but Opus\Title would still have to instantiated
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
     * @return TitleAbstract
     */
    public function getMainAbstract($language = null)
    {
        $titles = $this->getTitleAbstract();

        return $this->_findTitleForLanguage($titles, $language);
    }

    /**
     * Finds the title for the language or abstract in array.
     *
     * @param $titles array Titles or abstracts
     * @param $language Language string like 'deu'
     * @return Title|TitleAbstract
     */
    protected function _findTitleForLanguage($titles, $language)
    {
        $docLanguage = $this->getLanguage();

        if ($language === null) {
            $language = $docLanguage;
        }

        if (count($titles) > 0) {
            if ($language !== null) {
                $titleInDocLang = null;

                foreach ($titles as $title) {
                    $titleLanguage = $title->getLanguage();

                    if ($language === $titleLanguage) {
                        return $title;
                    } elseif ($docLanguage === $titleLanguage) {
                        $titleInDocLang = $title;
                    }
                }

                // if available return title in document language
                if ($titleInDocLang !== null) {
                    return $titleInDocLang;
                }
            }

            // if no title in document language ist found use first title
            return $titles[0];
        }

        return null;
    }

    /*
     * If param is set, the Opus\File-object on position 'param' is returned. It is equal to the file-id.
     * If no parameter is provided, an array with all files of the document is sorted and returned.
     * The array is sorted ascending according to the sortOrder and the fileId, see compareFiles().
     *
     * Overwrites getFile()-method
     *
     * @return File[]
     */
    public function getFile($param = null)
    {
        if ($param === null) {
            $files = parent::getFile();
            usort($files, [$this, 'compareFiles']);
            return $files;
        } else {
            // return Opus\File-Object
            return parent::getFile($param);
        }
    }

    public function compareFiles($a, $b)
    {
        if ($a->getSortOrder() === $b->getSortOrder()) {
            return $a->getId() < $b->getId() ? -1 : 1;
        }
        return $a->getSortOrder() < $b->getSortOrder() ? -1 : 1;
    }

    /**
     * Set internal fields ServerDatePublished and ServerDateModified.
     *
     * @return mixed Anything else then null will cancel the storage process.
     */
    protected function _preStore()
    {
        $result = parent::_preStore();

        if ($this->documentLifecycleListener !== null) {
            $this->documentLifecycleListener->preStore($this);
        }

        return $result;
    }

    /**
     * Log document errors.  Prefixes every log entry with document id.
     *
     * @param string $message
     *
     * TODO rename function to log(
     */
    protected function logger($message)
    {
        $logger = Log::get();
        $logger->info($this->getDisplayName() . ": $message");
    }

    /**
     * Erase all document fields, which are passed in $fieldnames array.
     *
     * @param array $fieldnames
     * @return $this Provide fluent interface.
     * @throws DocumentException If a given field does no exist.
     */
    public function deleteFields($fieldnames)
    {
        foreach ($fieldnames as $fieldname) {
            $field = $this->_getField($fieldname);
            if ($field === null) {
                throw new DocumentException("Cannot delete field $fieldname: Does not exist?");
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
     * @param null|Date $now
     * @return bool true - if embargo date has passed; false - if not
     */
    public function hasEmbargoPassed($now = null)
    {
        $embargoDate = $this->getEmbargoDate();

        if ($embargoDate === null) {
            return true;
        }
        if ($now === null) {
            $now = new Date();
            $now->setNow();
        }
        // Embargo has passed on the day after the specified date
        $embargoDate->setHour(23);
        $embargoDate->setMinute(59);
        $embargoDate->setSecond(59);
        $embargoDate->setTimezone($now->getTimezone());

        return $embargoDate->compare($now) === -1;
    }

    /**
     * Only consider files which are visible in frontdoor.
     *
     * @return bool
     */
    public function hasFulltext()
    {
        $files = $this->getFile();

        if (! is_array($files)) {
            return false;
        }

        foreach ($files as $file) {
            if ($file->getVisibleInFrontdoor()) {
                return true;
            }
        }

        return false;
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
        if ($docId === null) {
            return false;
        }

        $role       = CollectionRole::fetchByName('open_access');
        $collection = $role->getCollectionByOaiSubset('open_access');

        if ($collection !== null) {
            return $collection->holdsDocumentById($this->getId());
        } else {
            return false;
        }
    }

    /**
     * @param null $key Index or key name
     * @return mixed|null
     * @throws ModelException
     * @throws SecurityException
     */
    public function getEnrichment($key = null)
    {
        if ($key === null || is_numeric($key)) {
            return $this->__call('getEnrichment', [$key]);
        } else {
            $enrichments = $this->__call('getEnrichment', []);

            $matches = array_filter($enrichments, function ($enrichment) use ($key) {
                return $enrichment->getKeyName() === $key;
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
     * @param $key string Name of enrichment
     * @return mixed
     * @throws ModelException If the enrichment key does not exist
     * @throws SecurityException
     */
    public function getEnrichmentValue($key)
    {
        $enrichment = $this->getEnrichment($key);

        if ($enrichment !== null) {
            if (is_array($enrichment)) {
                return array_map(function ($value) {
                    return $value->getValue();
                }, $enrichment);
            } else {
                return $enrichment->getValue();
            }
        } else {
            $enrichmentKey = EnrichmentKey::fetchByName($key);

            if ($enrichmentKey === null) {
                throw new ModelException('unknown enrichment key');
            } else {
                return null;
            }
        }
    }

    public function getEnrichmentValues()
    {
        $enrichments = $this->getEnrichment();

        if ($enrichments === null) {
            return [];
        }

        if (! is_array($enrichments)) {
            $enrichments = [$enrichments];
        }

        $values = [];

        foreach ($enrichments as $enrichment) {
            $values[$enrichment->getKeyName()] = $enrichment->getValue();
        }

        return $values;
    }

    /**
     * Disconnects object from database and stores it as new document.
     *
     * @deprecated not implemented yet
     *
     * @return mixed
     * @throws ModelException
     *
     * TODO no idea how to do this properly
     * TODO not fully implemented yet
     */
    public function storeAsNew()
    {
        $this->resetDatabaseEntry();

        foreach ($this->fields as $field) {
            $field->setModified(true);
        }

        foreach ($this->externalFields as $fieldName => $fieldInfo) {
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
     * @deprecated not implemented yet
     *
     * @return Document
     * @throws ModelException
     *
     * TODO track copying in enrichment (?) - do it externally to this function
     * TODO not fully implemented yet
     */
    public function getCopy()
    {
        $document = new Document();

        foreach ($this->fields as $fieldName => $field) {
            $document->getField($fieldName)->setValue($field->getValue());
            // TODO handle simple values
            // TODO handle complex values -> create new objects
            // TODO handle complex values with link objects
        }

        return $document;
    }

    public function getServerStateChanged()
    {
        return $this->serverStateChanged;
    }

    public function __call($name, array $arguments)
    {
        $accessor  = substr($name, 0, 3);
        $fieldname = substr($name, 3);

        if (! in_array($accessor, ['set', 'get', 'add'])) {
            return parent::__call($name, $arguments);
        }

        if (substr($fieldname, 0, 10) !== 'Identifier' || $fieldname === 'Identifier') {
            return parent::__call($name, $arguments);
        } else {
            $type = Identifier::getTypeForFieldname($fieldname);

            if (count($arguments) > 0) {
                $argument = $arguments[0];
            } else {
                $argument = null;
            }

            switch ($accessor) {
                case 'add':
                    return $this->addIdentifierForType($type, $argument);

                case 'get':
                    return $this->getIdentifierByType($type, $argument);

                case 'set':
                    return $this->setIdentifiersForType($type, $argument);

                default:
                    return parent::__call($name, $arguments);
            }
        }
    }

    /**
     * @param $type
     * @param null $identifier
     * @return null
     *
     * TODO handle error cases
     */
    public function addIdentifierForType($type, $identifier = null)
    {
        if ($identifier instanceof IdentifierInterface) {
            $identifier->setType($type);
            parent::addIdentifier($identifier);
        } else {
            $identifier = parent::addIdentifier($identifier);
            $identifier->setType($type);
        }

        return $identifier;
    }

    public function setIdentifiersForType($type, $new)
    {
        $all = $this->getIdentifier();

        $type = strtolower($type);

        // remove old value with matching type
        $all = array_filter($all, function ($value) use ($type) {
            return $value->getType() !== $type;
        });

        if (! is_array($new)) {
            $new = [$new];
        }

        array_walk($new, function ($value) use ($type) {
            $value->setType($type);
        });

        return $this->setIdentifier(array_merge($all, $new));
    }

    public function getIdentifierByType($type, $index = null)
    {
        $identifier = $this->getIdentifier();

        $values = array_filter($identifier, function ($value) use ($type) {
            return $value->getType() === strtolower($type);
        });

        $values = array_values($values);

        // use Opus\Model\Field for value handling
        $filteredField = new Field('FilteredIdentifier');
        $filteredField->setMultiplicity('*');
        $filteredField->setValue($values);

        return $filteredField->getValue($index);
    }

    public function getModelType()
    {
        return 'document';
    }

    /**
     * @param DocumentLifecycleListener $listener
     *
     * TODO LAMINAS temporary hack to start separating database code from workflow event handling (business logic)
     */
    public function setLifecycleListener($listener)
    {
        $this->documentLifecycleListener = $listener;
    }

    /**
     * @param string      $value
     * @param string|null $type
     * @param bool        $caseSensitive
     * @return bool
     */
    public function hasSubject($value, $type = null, $caseSensitive = false)
    {
        $subjects = $this->getSubject();

        foreach($subjects as $subject) {
            if ($type !== null && $subject->getType() !== $type) {
                continue;
            }

            if ($caseSensitive) {
                if (strcmp($value, $subject->getValue()) === 0) {
                    return true;
                }
            } else {
                if (strcasecmp($value, $subject->getValue()) === 0) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param string      $value
     * @param string|null $type
     * @param bool        $caseSensitive
     */
    public function removeSubject($value, $type = null, $caseSensitive = false)
    {
        $subjects = $this->getSubject();

        $remainingSubjects = [];

        foreach($subjects as $subject) {
            if ($type !== null && $subject->getType() !== $type) {
                $remainingSubjects[] = $subject;
                continue;
            }

            if ($caseSensitive) {
                if (strcmp($value, $subject->getValue()) !== 0) {
                    $remainingSubjects[] = $subject;
                }
            } else {
                if (strcasecmp($value, $subject->getValue()) !== 0) {
                    $remainingSubjects[] = $subject;
                }
            }
        }

        $this->setSubject($remainingSubjects);
    }
}
