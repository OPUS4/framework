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
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Person extends Opus_Model_AbstractDb {

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Persons';

    /**
     * Plugins to load
     *
     * @var array
     */
    protected $_plugins = array(
        'Opus_Model_Plugin_InvalidateDocumentCache' => null,
    );

    /**
     * Initialize model with the following fields:
     * - AcademicTitle
     * - Email
     * - FirstName
     * - LastName
     *
     * @return void
     */
    protected function _init() {
        $title = new Opus_Model_Field('AcademicTitle');

        $firstName = new Opus_Model_Field('FirstName');

        $lastName = new Opus_Model_Field('LastName');
        $lastName->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $dateOfBirth = new Opus_Model_Field('DateOfBirth');
        $dateOfBirth->setValueModelClass('Opus_Date');

        $placeOfBirth = new Opus_Model_Field('PlaceOfBirth');

        $email = new Opus_Model_Field('Email');
        $email->setValidator(new Zend_Validate_EmailAddress());

        $opusId = new Opus_Model_Field('OpusId');
        $identifierOrcid = new Opus_Model_Field('IdentifierOrcid');
        $identifierGnd = new Opus_Model_Field('IdentifierGnd');
        $identifierMisc = new Opus_Model_Field('IdentifierMisc');

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
    public function getName() {
        $firstName = $this->getFirstName();

        if ($firstName !== null) {
            return $this->getLastName() . ', ' . $firstName; 
        }
        else {
            return $this->getLastName();
        }
    }

    /**
     * Returns name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getName();
    }

    /**
     * Fetches all documents associated to the person by a certain role.
     *
     * @param string $role The role that the person has for the documents.
     * @return array An array of Opus_Document
     */
    public function getDocumentsByRole($role) {
        // $documentsLinkTable = new Opus_Db_LinkPersonsDocuments();
        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_LinkPersonsDocuments');
        $documentsTable = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');
        $documents = array();
        $select = $documentsLinkTable->select();
        $select->where('role=?', $role);
        foreach ($this->_primaryTableRow->findManyToManyRowset(
            $documentsTable,
            $documentsLinkTable, null, null, $select
        ) as $document) {
            $documents[] = new Opus_Document($document->id);
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
        if ($this->isNewRecord())
        {
            // TODO do more?
            return;
        }

        $database = Zend_Db_Table::getDefaultAdapter();

        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_LinkPersonsDocuments');

        $select = $documentsLinkTable->select()
            ->from('link_persons_documents', 'distinct(document_id)')
            ->where('person_id = ?', $this->getId());

        if (!is_null($role))
        {
            $select->where('role = ?', $role);
        }

        $documentIds = $database->fetchCol($select);

        return $documentIds;
    }

    /**
     * Get a list of IDs for Persons that have the specified role for
     * certain documents.
     *
     * @param string $role Role name.
     * @return array List of Opus_Person Ids for Person models assigned to the specified Role.
     */
    public static function getAllIdsByRole($role) {
        // $documentsLinkTable = new Opus_Db_LinkPersonsDocuments();
        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_LinkPersonsDocuments');
        $tablename = $documentsLinkTable->info(Zend_Db_Table::NAME);
        $db = $documentsLinkTable->getAdapter();
        $select = $db->select()->from($tablename, array('person_id'))
            ->where('role = ? ', $role);
        $personIds = $documentsLinkTable->getAdapter()->fetchCol($select);

        if (is_null($personIds) === true) {
            $personIds = array();
        }

        return $personIds;
    }

    /**
     * Retrieve all Opus_Person instances from the database.
     *
     * @return array Array of Opus_Person objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Person', 'Opus_Db_Persons');
    }

    /**
     * Returns all persons in the database without duplicates.
     *
     * Every real person might be represented by several objects, one for each document.
     *
     * @return array
     *
     * TODO return objects ?
     *
     */
    public static function getAllPersons($role = null, $start = 0, $limit = 0, $filter = null)
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $select = self::getAllPersonsSelect($role, $filter);

        if ($start !== 0 || $limit !== 0)
        {
            $select->limit($limit, $start);
        }

        $select->order(array('trim(last_name)', 'trim(first_name)'));

        $result = $table->fetchAll($select);

        return $result->toArray();
    }

    /**
     * Returns total count of persons for role and filter string.
     * @param null $role
     * @param null $filter
     * @return mixed
     */
    public static function getAllPersonsCount($role = null, $filter = null)
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $select = self::getAllPersonsSelect($role, $filter);

        $countSelect = $table->select()
            ->from(new Zend_Db_Expr("($select)"), 'count(*) as num')
            ->setIntegrityCheck(false);

        $result = $table->fetchRow($countSelect);

        return $result['num'];
    }

    /**
     * Constructs select statement for getting all persons matching criteria.
     * @param null $role
     * @param null $filter
     * @return Zend_Db_Select
     */
    public static function getAllPersonsSelect($role = null, $filter = null)
    {
        $database = Zend_Db_Table::getDefaultAdapter();

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $result = null;

        $identityColumns = array('last_name', 'first_name', 'identifier_orcid', 'identifier_gnd', 'identifier_misc');

        $trimmedColumns = array_map(function($value)
        {
            return "trim($value) as $value";
        }, $identityColumns);

        $select = $table->select()
            ->from(
                array('p' => 'persons'),
                $trimmedColumns
            )->group(
                $identityColumns
            );

        if (!is_null($role))
        {
            $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_LinkPersonsDocuments');

            $select->join(
                array('link' => $documentsLinkTable->info(Zend_Db_Table::NAME)),
                'p.id = link.person_id',
                array()
            );

            $select->where($database->quoteInto('link.role = ?', $role));
        }

        if (!is_null($filter))
        {
            $select->where('last_name LIKE ? OR first_name LIKE ?', "%$filter%", "%$filter%");
        }

        // result still contains name duplicates because of leading spaces -> group trimmed result
        $mergedSelect = $table->select()
            ->from(new Zend_Db_Expr("($select)"), $identityColumns)
            ->group($identityColumns)
            ->setIntegrityCheck(false);

        return $mergedSelect;
    }

    /**
     * Returns roles for a person.
     *
     * TODO verify columns
     * TODO use object for person
     */
    public static function getPersonRoles($person)
    {
        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_LinkPersonsDocuments');

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $select = $documentsLinkTable->select()
            ->from(
                array('link' => $documentsLinkTable->info(Zend_Db_Table::NAME)),
                array('link.role', 'documents' => 'count(link.document_id)')
            )->join(
                array('p' => 'persons'),
                'link.person_id = p.id',
                array()
            )->group(
                array('link.role')
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
    public static function getPersonDocuments($person, $state = null, $role = null, $sort = null, $order = true)
    {
        $database = Zend_Db_Table::getDefaultAdapter();

        $documentsTable = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');

        $select = $documentsTable->select()
            ->from(
                array('d' => $documentsTable->info(Zend_Db_Table::NAME)),
                array('distinct(d.id)')
            )->join(
                array('link' => 'link_persons_documents'),
                'link.document_id = d.id',
                array()
            )->join(
                array('p' => 'persons'),
                'link.person_id = p.id',
                array()
            );

        self::addWherePerson($select, $person);

        if (!is_null($state) && in_array($state,
                array('published', 'unpublished', 'inprogress', 'audited', 'restricted', 'deleted')
            ))
        {
            $select->where('d.server_state = ?', $state);
        }

        if (!is_null($role) && in_array($role,
                array('author', 'editor', 'contributor', 'referee', 'advisor', 'other', 'translator', 'submitter')
            ))
        {
            $select->where('link.role = ?', $role);
        }

        if (!is_null($sort) and in_array($sort, array('id', 'title', 'publicationDate', 'docType', 'author')))
        {
            switch ($sort)
            {
                case 'id':
                    $select->order('d.id' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'title':
                    $select->setIntegrityCheck(false);
                    $select->join(array('t' => 'document_title_abstracts'),
                        't.document_id = d.id',
                        array());

                    $select->columns(array('d.id', 't.value'));
                    $select->order('t.value' . (($order) ? ' ASC' : ' DESC'));
                    break;
                case 'publicationDate':
                    $select->columns(array('d.id', 'd.server_date_published'));
                    $select->order('d.server_date_published' . (($order) ? ' ASC' : ' DESC'));
                    break;
                case 'docType':
                    $select->columns(array('d.id', 'd.type'));
                    $select->order('d.type' . (($order) ? ' ASC' : ' DESC'));
                    break;
                case 'author':
                    $select->setIntegrityCheck(false);
                    $select->columns(array('d.id', 'p.last_name'));
                    $select->order('p.last_name' . ($order ? ' ASC' : ' DESC'));
                    break;
            }
        }

        $documents = $documentsTable->getAdapter()->fetchCol($select);

        $documents = array_unique($documents); // just in case (TODO sorting by title creates duplicates)

        return $documents;
    }

    /**
     * Returns the value of matching person objects.
     *
     * @param $person
     * @return array
     */
    public static function getPersonValues($person)
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $result = null;

        $select = $table->select()->from(array('p' => 'persons'));

        self::addWherePerson($select, $person);

        $rows = $table->fetchAll($select);

        if (count($rows) === 0)
        {
            return null;
        }

        $data = $rows->toArray();

        $merged = array();

        foreach ($data as $personId => $values)
        {
            foreach ($values as $key => $value)
            {
                if (array_key_exists($key, $merged))
                {
                    $allValues = $merged[$key];

                    if (is_array($allValues))
                    {
                        if (array_search($value, $allValues) === false)
                        {
                            array_push($merged[$key], $value);
                        }
                    }
                    else
                    {
                        if ($value !== $allValues)
                        {
                            $merged[$key] = array();
                            array_push($merged[$key], $allValues, $value);
                        }
                    }
                }
                else
                {
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
     * @param $person Criteria for matching persons
     * @param null $documents Array with ids of documents
     * @return array Array with IDs of persons
     */
    public static function getPersons($person, $documents = null)
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $database = $table->getAdapter();

        $select = $table->select()->from(
            array('p' => 'persons'),
            array('distinct(p.id)')
        );

        if (!is_null($documents))
        {
            $select->join(
                array('link' => 'link_persons_documents'),
                'link.person_id = p.id',
                array()
            );

            $select->where('link.document_id IN (?)', $documents);
        }

        self::addWherePerson($select, $person);

        $persons = $database->fetchCol($select);

        return $persons;
    }

    public static function getPersonsAndDocuments($person, $documents = null)
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $database = $table->getAdapter();

        $select = $table->select()->from(
            array('p' => 'persons'),
            array()
        )->join(
            array('link' => 'link_persons_documents'),
            'link.person_id = p.id',
            array()
        )->columns(
            array('link.person_id', 'link.document_id')
        );

        $select->setIntegrityCheck(false);

        if (!is_null($documents))
        {
            $select->where('link.document_id IN (?)', $documents);
        }

        self::addWherePerson($select, $person);

        $persons = $database->fetchAll($select);

        return $persons;
    }

    /**
     * Updates select columns of matching persons.
     *
     * Optionally the scope can be limited to specified set of documents.
     *
     * @param $person Criteria for matching persons
     * @param $changes Map of column names and new values
     * @param null $documents Array with document Ids
     *
     * TODO update ServerDateModified for modified documents (How?)
     */
    public static function updateAll($person, $changes, $documents = null)
    {
        if (empty($person))
        {
            // TODO do logging?
            return;
        }

        if (empty($changes))
        {
            // TODO do logging?
            return;
        }

        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $database = $table->getAdapter();

        $model = new Opus_Person();

        $trimmed = array();

        foreach ($changes as $name => $value)
        {
            if (is_null($model->getField($name)))
            {
                // TODO use
                throw new Opus_Model_Exception("unknown field '$name' for update");
            }
            else
            {
                if (!is_null($value))
                {
                    $trimmed[$name] = trim($value);
                }
                else {
                    $trimmed[$name] = null;
                }
            }
        }

        $changes = self::convertChanges($trimmed);

        $personIds = self::getPersons($person, $documents);
        $documentIds = self::getDocuments($personIds, $documents);

        if (!empty($personIds))
        {
            $table->update($changes, array(
                $database->quoteInto('id IN (?)', $personIds)
            ));

            if (!empty($documentIds))
            {
                $date = new Opus_Date();
                $date->setNow();

                Opus_Document::setServerDateModifiedByIds($date, $documentIds);
            }
        }
    }

    public static function getDocuments($personIds, $documents = null)
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);

        $database = $table->getAdapter();

        $select = $table->select()->from(
            array('p' => 'persons'),
            array()
        )->join(
            array('link' => 'link_persons_documents'),
            'link.person_id = p.id',
            array()
        )->columns(
            array('distinct(link.document_id)')
        )->where('link.person_id IN (?)', $personIds);

        $select->setIntegrityCheck(false);

        if (!is_null($documents) && count($documents) > 0)
        {
            $select->where('link.document_id IN (?)', $documents);
        }

        $documentIds = $database->fetchCol($select);

        return $documentIds;

    }

    /**
     * Converts map with field names into array with column names.
     *
     * @param $changes Map of field names and values
     * @return array Map of column names and values
     */
    public static function convertChanges($changes)
    {
        $columnChanges = array();

        foreach ($changes as $fieldName => $value)
        {
            $column = self::convertFieldnameToColumn($fieldName);

            $columnChanges[$column] = $value;
        }

        return $columnChanges;
    }

    /**
     * Convert array with column names into array with field names.
     * @param $person
     * @return array
     */
    public static function convertToFieldNames($person)
    {
        $values = array();

        foreach ($person as $column => $value)
        {
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
        if (!is_array($criteria))
        {
            // TODO do some logging
            return false;
        }

        $defaults = array_fill_keys(array(
            'LastName', 'FirstName', 'IdentifierOrcid', 'IdentifierGnd', 'IdentifierMisc'
        ), null);
        $criteria = array_merge($defaults, $criteria);

        foreach ($criteria as $fieldName => $critValue) {
            $value = $this->getField($fieldName)->getValue();

            if (is_string($value))
            {
                if (stristr($value, $critValue) === FALSE)
                {
                    return false;
                }
            }
            else
            {
                if ($value !== $critValue)
                {
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
        $defaults = array_fill_keys(array(
            'last_name', 'first_name', 'identifier_orcid', 'identifier_gnd', 'identifier_misc'
        ), null);
        $person = array_merge($defaults, $person);

        foreach ($person as $column => $value)
        {
            if (strlen(trim($value)) > 0)
            {
                $select->where("trim(p.$column) = ?", trim($value));
            }
            else
            {
                $select->where("p.$column IS NULL");
            }
        }
    }

}
