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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Common\Date;
use Opus\Common\Model\ModelException;
use Opus\Common\PersonInterface;
use Opus\Common\PersonRepositoryInterface;
use Opus\Common\Repository;
use Opus\Db\LinkPersonsDocuments;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Db_Expr;
use Zend_Db_Select;
use Zend_Db_Table;
use Zend_Validate_EmailAddress;
use Zend_Validate_NotEmpty;

use function array_fill_keys;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_push;
use function array_search;
use function array_unique;
use function count;
use function in_array;
use function is_array;
use function is_string;
use function stristr;
use function strlen;
use function trim;

/**
 * Domain model for persons in the Opus framework
 *
 * The class includes a number of static methods for querying or updating the database. These functions are used for
 * the management of persons in OPUS 4. They will be expanded and maybe moved later as the development of the
 * management functions continues. Some of them might be replaced by code that uses the search index.
 *
 * At this point a real "person" is represented by all the objects matching the identification of that person. For
 * the identification the following fields are used.
 *
 * - LastName
 * - FirstName
 * - IdentifierOrcid
 * - IdentifierGnd
 * - IdentifierMisc
 *
 * TODO Currently a mix of field and column names is used. That should be fixed.
 *
 * So a person is specified by providing values for the five identity fields. If not all values are specified, null is
 * assumed and used for matching.
 *
 * A person object with the same name, but no identifier is not the same person as an object that does have an
 * identifier. So if for a person only a last name is specified, persons with a first name or any kind of id are not
 * considered the same person.
 *
 * TODO in next steps of development LastName and FirstName will become irrelevant and only identifier will be used
 *
 * There are currently no mechanisms to handle different writing of the name of a person or aliases.
 *
 * Two people with the same name can be distinguished by using an identifier.
 *
 * TODO use OPUS-ID for people without external identifier
 *
 * phpcs:disable
 */
class Person extends AbstractDb implements PersonInterface, PersonRepositoryInterface
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\Persons::class;

    /**
     * Plugins to load
     *
     * @var array
     */
    public function getDefaultPlugins()
    {
        return [
            Model\Plugin\InvalidateDocumentCache::class,
        ];
    }

    /**
     * Initialize model with the following fields:
     * - AcademicTitle
     * - Email
     * - FirstName
     * - LastName
     */
    protected function init()
    {
        $title = new Field('AcademicTitle');

        $firstName = new Field('FirstName');

        $lastName = new Field('LastName');
        $lastName->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $dateOfBirth = new Field('DateOfBirth');
        $dateOfBirth->setValueModelClass(Date::class);

        $placeOfBirth = new Field('PlaceOfBirth');

        $email = new Field('Email');
        $email->setValidator(new Zend_Validate_EmailAddress());

        $opusId          = new Field('OpusId');
        $identifierOrcid = new Field('IdentifierOrcid');
        $identifierGnd   = new Field('IdentifierGnd');
        $identifierMisc  = new Field('IdentifierMisc');

        $this->addField($title)
            ->addField($firstName)
            ->addField($lastName)
            ->addField($dateOfBirth)
            ->addField($placeOfBirth)
            ->addField($identifierOrcid)
            ->addField($identifierGnd)
            ->addField($identifierMisc)
            ->addField($email)
            ->addField($opusId);
    }

    /**
     * Get uniform representation of names.
     *
     * @return string
     */
    public function getName()
    {
        $firstName = $this->getFirstName();

        if ($firstName !== null) {
            return $this->getLastName() . ', ' . $firstName;
        } else {
            return $this->getLastName();
        }
    }

    /**
     * Returns name.
     *
     * @see \Opus\Model\AbstractModel#getDisplayName()
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * Fetches all documents associated to the person by a certain role.
     *
     * @param string $role The role that the person has for the documents.
     * @return array An array of Opus\Document
     */
    public function getDocumentsByRole($role)
    {
        // $documentsLinkTable = new Opus\Db\LinkPersonsDocuments();
        $documentsLinkTable = TableGateway::getInstance(LinkPersonsDocuments::class);
        $documentsTable     = TableGateway::getInstance(Db\Documents::class);
        $documents          = [];
        $select             = $documentsLinkTable->select();
        $select->where('role=?', $role);
        foreach (
            $this->primaryTableRow->findManyToManyRowset(
                $documentsTable,
                $documentsLinkTable,
                null,
                null,
                $select
            ) as $document
        ) {
            $documents[] = Document::get($document->id);
        }
        return $documents;
    }

    /**
     * Returns the ids for all linked documents.
     *
     * @return array|void
     */
    public function getDocumentIds($role = null)
    {
        if ($this->isNewRecord()) {
        // TODO do more?
            return;
        }

        $database = Zend_Db_Table::getDefaultAdapter();

        $documentsLinkTable = TableGateway::getInstance(LinkPersonsDocuments::class);

        $select = $documentsLinkTable->select()
            ->from('link_persons_documents', 'distinct(document_id)')
            ->where('person_id = ?', $this->getId());

        if ($role !== null) {
            $select->where('role = ?', $role);
        }

        return $database->fetchCol($select);
    }

    /**
     * Get a list of IDs for Persons that have the specified role for
     * certain documents.
     *
     * @param string $role Role name.
     * @return array List of Opus\Person Ids for Person models assigned to the specified Role.
     */
    public function getAllIdsByRole($role)
    {
        // $documentsLinkTable = new Opus\Db\LinkPersonsDocuments();
        $documentsLinkTable = TableGateway::getInstance(LinkPersonsDocuments::class);
        $tablename          = $documentsLinkTable->info(Zend_Db_Table::NAME);
        $db                 = $documentsLinkTable->getAdapter();
        $select             = $db->select()->from($tablename, ['person_id'])
            ->where('role = ? ', $role);
        $personIds          = $documentsLinkTable->getAdapter()->fetchCol($select);

        if ($personIds === null) {
            $personIds = [];
        }

        return $personIds;
    }

    /**
     * Retrieve all Opus\Person instances from the database.
     *
     * @return array Array of Opus\Person objects.
     */
    public function getAll()
    {
        return self::getAllFrom(self::class, Db\Persons::class);
    }

    /**
     * Returns all persons in the database without duplicates.
     *
     * Every real person might be represented by several objects, one for each document.
     *
     * @return array
     *
     * TODO return objects ?
     */
    public function getAllPersons($role = null, $start = 0, $limit = 0, $filter = null)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $select = self::getAllPersonsSelect($role, $filter);

        if ($start !== 0 || $limit !== 0) {
            $select->limit($limit, $start);
        }

        $select->order(['trim(last_name)', 'trim(first_name)']);

        $result = $table->fetchAll($select);

        return $result->toArray();
    }

    /**
     * Returns total count of persons for role and filter string.
     *
     * @param null $role
     * @param null $filter
     * @return mixed
     */
    public function getAllPersonsCount($role = null, $filter = null)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $select = self::getAllPersonsSelect($role, $filter);

        $countSelect = $table->select()
            ->from(new Zend_Db_Expr("($select)"), 'count(*) as num')
            ->setIntegrityCheck(false);

        $result = $table->fetchRow($countSelect);

        return $result['num'];
    }

    /**
     * Constructs select statement for getting all persons matching criteria.
     *
     * @param null $role
     * @param null $filter
     * @return Zend_Db_Select
     *
     * TODO should be protected, or?
     */
    public static function getAllPersonsSelect($role = null, $filter = null)
    {
        $database = Zend_Db_Table::getDefaultAdapter();

        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $result = null;

        $identityColumns = ['last_name', 'first_name', 'identifier_orcid', 'identifier_gnd', 'identifier_misc'];

        $trimmedColumns = array_map(function ($value) {
            return "trim($value) as $value";
        }, $identityColumns);

        $groupColumns = array_map(function ($value) {
            return "trim($value)";
        }, $identityColumns);

        $select = $table->select()
            ->from(
                ['p' => 'persons'],
                $trimmedColumns
            );

        if ($role !== null) {
            $documentsLinkTable = TableGateway::getInstance(LinkPersonsDocuments::class);

            $select->join(
                ['link' => $documentsLinkTable->info(Zend_Db_Table::NAME)],
                'p.id = link.person_id',
                []
            );

            $select->where($database->quoteInto('link.role = ?', $role));
        }

        if ($filter !== null) {
            $select->where('last_name LIKE ? OR first_name LIKE ?', "%$filter%", "%$filter%");
        }

        // result still contains name duplicates because of leading spaces -> group trimmed result
        return $table->select()
            ->from(new Zend_Db_Expr("($select)"), $identityColumns)
            ->group($groupColumns)
            ->setIntegrityCheck(false);
    }

    /**
     * Returns roles for a person.
     *
     * TODO verify columns
     * TODO use object for person
     */
    public function getPersonRoles($person)
    {
        $documentsLinkTable = TableGateway::getInstance(LinkPersonsDocuments::class);

        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $select = $documentsLinkTable->select()
            ->from(
                ['link' => $documentsLinkTable->info(Zend_Db_Table::NAME)],
                ['link.role', 'documents' => 'count(link.document_id)']
            )->join(
                ['p' => 'persons'],
                'link.person_id = p.id',
                []
            )->group(
                ['link.role']
            );

        self::addWherePerson($select, $person);

        $result = $table->fetchAll($select);

        return $result->toArray();
    }

    /**
     * Returns document for person.
     *
     * The $person parameter is an array of columns and values that determine which person is meant.
     *
     * - first name
     * - last name
     * - identifier_orcid
     * - identifier_gnd
     * - identifier_misc
     *
     * @param $person array
     * @return array
     */
    public function getPersonDocuments($person, $state = null, $role = null, $sort = null, $order = true)
    {
        $documentsTable = TableGateway::getInstance(Db\Documents::class);

        $select = $documentsTable->select()
            ->from(
                ['d' => $documentsTable->info(Zend_Db_Table::NAME)],
                ['distinct(d.id)']
            )->join(
                ['link' => 'link_persons_documents'],
                'link.document_id = d.id',
                []
            )->join(
                ['p' => 'persons'],
                'link.person_id = p.id',
                []
            );

        self::addWherePerson($select, $person);

        if (
            $state !== null && in_array(
                $state,
                ['published', 'unpublished', 'inprogress', 'audited', 'restricted', 'deleted']
            )
        ) {
            $select->where('d.server_state = ?', $state);
        }

        if (
            $role !== null && in_array(
                $role,
                ['author', 'editor', 'contributor', 'referee', 'advisor', 'other', 'translator', 'submitter']
            )
        ) {
            $select->where('link.role = ?', $role);
        }

        if ($sort !== null && in_array($sort, ['id', 'title', 'publicationDate', 'docType', 'author'])) {
            switch ($sort) {
                case 'id':
                    $select->order('d.id' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'title':
                    $select->setIntegrityCheck(false);
                    $select->join(
                        ['t' => 'document_title_abstracts'],
                        't.document_id = d.id',
                        []
                    );

                    $select->columns(['d.id', 't.value']);
                    $select->order('t.value' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'publicationDate':
                    $select->columns(['d.id', 'd.server_date_published']);
                    $select->order('d.server_date_published' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'docType':
                    $select->columns(['d.id', 'd.type']);
                    $select->order('d.type' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'author':
                    $select->setIntegrityCheck(false);
                    $select->columns(['d.id', 'p.last_name']);
                    $select->order('p.last_name' . ($order ? ' ASC' : ' DESC'));
                    break;
            }
        }

        $documents = $documentsTable->getAdapter()->fetchCol($select);

        $documents = array_values(array_unique($documents)); // just in case (TODO sorting by title creates duplicates)

        return $documents;
    }

    /**
     * Returns the value of matching person objects.
     *
     * @param $person
     * @return array
     */
    public function getPersonValues($person)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $result = null;

        $select = $table->select()->from(['p' => 'persons']);

        self::addWherePerson($select, $person);

        $rows = $table->fetchAll($select);

        if (count($rows) === 0) {
            return null;
        }

        $data = $rows->toArray();

        $merged = [];

        foreach ($data as $personId => $values) {
            foreach ($values as $key => $value) {
                if (array_key_exists($key, $merged)) {
                    $allValues = $merged[$key];

                    if (is_array($allValues)) {
                        if (array_search($value, $allValues) === false) {
                            array_push($merged[$key], $value);
                        }
                    } else {
                        if ($value !== $allValues) {
                            $merged[$key] = [];
                            array_push($merged[$key], $allValues, $value);
                        }
                    }
                } else {
                    $merged[$key] = $value;
                }
            }
        }

        return $merged;
    }

    /**
     * Returns ids of person objects matching criteria and documents.
     *
     * TODO filter by role?
     *
     * @param array      $person Criteria for matching persons
     * @param array|null $documents Array with ids of documents
     * @return array Array with IDs of persons
     */
    public function getPersons($person, $documents = null)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $database = $table->getAdapter();

        $select = $table->select()->from(
            ['p' => 'persons'],
            ['distinct(p.id)']
        );

        // TODO handle single document id value
        if ($documents !== null && is_array($documents) && count($documents) > 0) {
            $select->join(
                ['link' => 'link_persons_documents'],
                'link.person_id = p.id',
                []
            );

            $select->where('link.document_id IN (?)', $documents);
        }

        self::addWherePerson($select, $person);

        return $database->fetchCol($select);
    }

    public function getPersonsAndDocuments($person, $documents = null)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $database = $table->getAdapter();

        $select = $table->select()->from(
            ['p' => 'persons'],
            []
        )->join(
            ['link' => 'link_persons_documents'],
            'link.person_id = p.id',
            []
        )->columns(
            ['link.person_id', 'link.document_id']
        );

        $select->setIntegrityCheck(false);

        if ($documents !== null) {
            $select->where('link.document_id IN (?)', $documents);
        }

        self::addWherePerson($select, $person);

        return $database->fetchAll($select);
    }

    /**
     * Updates select columns of matching persons.
     *
     * Optionally the scope can be limited to specified set of documents.
     *
     * @param array $person Criteria for matching persons
     * @param array $changes Map of column names and new values
     * @param null                                       $documents Array with document Ids
     *
     *                                       TODO update ServerDateModified for modified documents (How?)
     */
    public function updateAll($person, $changes, $documents = null)
    {
        if (empty($person)) {
            // TODO do logging?
            return;
        }

        if (empty($changes)) {
            // TODO do logging?
            return;
        }

        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $database = $table->getAdapter();

        $model = new Person();

        $trimmed = [];

        foreach ($changes as $name => $value) {
            if ($model->getField($name) === null) {
                // TODO use
                throw new ModelException("unknown field '$name' for update");
            } else {
                if ($value !== null) {
                    $trimmed[$name] = trim($value);
                } else {
                    $trimmed[$name] = null;
                }
            }
        }

        $changes = self::convertChanges($trimmed);

        $personIds   = self::getPersons($person, $documents);
        $documentIds = self::getDocuments($personIds, $documents);

        if (! empty($personIds)) {
            $table->update($changes, [
                $database->quoteInto('id IN (?)', $personIds),
            ]);

            if (! empty($documentIds)) {
                $date = new Date();
                $date->setNow();

                Repository::getInstance()->getModelRepository(Document::class)->setServerDateModifiedForDocuments(
                    $date,
                    $documentIds
                );
            }
        }
    }

    public function getDocuments($personIds, $documents = null)
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);

        $database = $table->getAdapter();

        $select = $table->select()->from(
            ['p' => 'persons'],
            []
        )->join(
            ['link' => 'link_persons_documents'],
            'link.person_id = p.id',
            []
        )->columns(
            ['distinct(link.document_id)']
        )->where('link.person_id IN (?)', $personIds);

        $select->setIntegrityCheck(false);

        if ($documents !== null && count($documents) > 0) {
            $select->where('link.document_id IN (?)', $documents);
        }

        return $database->fetchCol($select);
    }

    /**
     * Converts map with field names into array with column names.
     *
     * @param array $changes Map of field names and values
     * @return array Map of column names and values
     */
    public static function convertChanges($changes)
    {
        $columnChanges = [];

        foreach ($changes as $fieldName => $value) {
            $column = self::convertFieldnameToColumn($fieldName);

            $columnChanges[$column] = $value;
        }

        return $columnChanges;
    }

    /**
     * Convert array with column names into array with field names.
     *
     * @param $person
     * @return array
     */
    public static function convertToFieldNames($person)
    {
        $values = [];

        foreach ($person as $column => $value) {
            $fieldName = self::convertColumnToFieldname($column);

            $values[$fieldName] = $value;
        }

        return $values;
    }

    /**
     * Checks if person matches criteria.
     *
     * @param $criteria
     * @return bool
     *
     * TODO refactor
     */
    public function matches($criteria)
    {
        if ($criteria instanceof Person) {
            $person = $criteria;

            $criteria                    = [];
            $criteria['LastName']        = $person->getLastName();
            $criteria['FirstName']       = $person->getFirstName();
            $criteria['IdentifierOrcid'] = $person->getIdentifierOrcid();
            $criteria['IdentifierGnd']   = $person->getIdentifierGnd();
            $criteria['IdentifierMisc']  = $person->getIdentifierMisc();
        }

        if (! is_array($criteria)) {
            // TODO do some logging
            return false;
        }

        $defaults = array_fill_keys([
            'LastName',
            'FirstName',
            'IdentifierOrcid',
            'IdentifierGnd',
            'IdentifierMisc',
        ], null);
        $criteria = array_merge($defaults, $criteria);

        foreach ($criteria as $fieldName => $critValue) {
            $value = $this->getField($fieldName)->getValue();

            if (is_string($value) && $critValue !== null) {
                if (stristr($value, $critValue) === false) {
                    return false;
                }
            } else {
                if ($value !== $critValue) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param $select
     * @param $person
     *
     * TODO review
     *
     * This currently adds criteria to match any person with matching values for the specified columns.
     *
     * TODO shoudln't it be an exact match for columns that are empty (only null/empty matches)?
     *
     * NOTE: Function updateAll does not use this, because there does not seem to be a way to hand over
     * an update object like for the select. TODO maybe different with ZF2+
     */
    protected static function addWherePerson($select, $person)
    {
        $defaults = array_fill_keys([
            'last_name',
            'first_name',
            'identifier_orcid',
            'identifier_gnd',
            'identifier_misc',
        ], null);
        $person   = array_merge($defaults, $person);

        foreach ($person as $column => $value) {
            if (strlen(trim($value ?? '')) > 0) {
                $select->where("trim(p.$column) = ?", trim($value));
            } else {
                $select->where("p.$column IS NULL");
            }
        }
    }

    public function getModelType()
    {
        return 'person';
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getFirstName()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $firstName
     * @return $this
     * @throws ModelException
     */
    public function setFirstName($firstName)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getLastName()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $lastName
     * @return $this
     * @throws ModelException
     */
    public function setLastName($lastName)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getAcademicTitle()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $academicTitle
     * @return $this
     * @throws ModelException
     */
    public function setAcademicTitle($academicTitle)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return Date
     * @throws ModelException
     */
    public function getDateOfBirth()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param Date $dateOfBirth
     * @return $this
     * @throws ModelException
     */
    public function setDateOfBirth($dateOfBirth)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getPlaceOfBirth()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $placeOfBirth
     * @return $this
     * @throws ModelException
     */
    public function setPlaceOfBirth($placeOfBirth)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getEmail()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $email
     * @return $this
     * @throws ModelException
     */
    public function setEmail($email)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getOpusId()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $opusId
     * @return $this
     * @throws ModelException
     */
    public function setOpusId($opusId)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getIdentifierOrcid()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $orcid
     * @return $this
     * @throws ModelException
     */
    public function setIdentifierOrcid($orcid)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getIdentifierGnd()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $identifier
     * @return $this
     * @throws ModelException
     */
    public function setIdentifierGnd($gndId)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     * @throws ModelException
     */
    public function getIdentifierMisc()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $identifier
     * @return $this
     * @throws ModelException
     */
    public function setIdentifierMisc($identifier)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
