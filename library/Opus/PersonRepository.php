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
 * @copyright   Copyright (c) 2025, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Common\Date;
use Opus\Common\Model\ModelException;
use Opus\Common\PersonRepositoryInterface;
use Opus\Common\Repository;
use Opus\Db\LinkPersonsDocuments;
use Opus\Db\TableGateway;
use Zend_Db_Expr;
use Zend_Db_Select;
use Zend_Db_Select_Exception;
use Zend_Db_Table;

use function array_fill_keys;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_push;
use function array_search;
use function array_unique;
use function array_values;
use function count;
use function in_array;
use function is_array;
use function strlen;
use function trim;

class PersonRepository implements PersonRepositoryInterface
{
    /** @var string TableGateway class for 'persons' table TODO use constant? */
    protected static $personTableClass = Db\Persons::class;

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
     * Returns all persons in the database without duplicates.
     *
     * Every real person might be represented by several objects, one for each document.
     *
     * @param string|null $role
     * @param int         $start
     * @param int         $limit
     * @param string|null $filter
     * @return array
     *
     * TODO return objects ?
     */
    public function getAllPersons($role = null, $start = 0, $limit = 0, $filter = null)
    {
        $table = TableGateway::getInstance(self::$personTableClass);

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
     * @param null|string $role
     * @param null|string $filter
     * @return mixed
     */
    public function getAllPersonsCount($role = null, $filter = null)
    {
        $table = TableGateway::getInstance(self::$personTableClass);

        $select = self::getAllPersonsSelect($role, $filter);

        $countSelect = $table->select()
            ->from(new Zend_Db_Expr("($select)"), 'count(*) as num')
            ->setIntegrityCheck(false);

        $result = $table->fetchRow($countSelect);

        return $result['num'];
    }

    /**
     * Returns roles for a person.
     *
     * @param string[] $person
     * @return array
     *
     * TODO verify columns
     * TODO use object for person
     */
    public function getPersonRoles($person)
    {
        $documentsLinkTable = TableGateway::getInstance(LinkPersonsDocuments::class);

        $table = TableGateway::getInstance(self::$personTableClass);

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
     * @param string[]    $person
     * @param string|null $state
     * @param string|null $role
     * @param string|null $sort
     * @param bool        $order
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
     * @param string[] $person
     * @return array|null
     */
    public function getPersonValues($person)
    {
        $table = TableGateway::getInstance(self::$personTableClass);

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
        $table = TableGateway::getInstance(self::$personTableClass);

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

    /**
     * @param string[]   $person
     * @param int[]|null $documents
     * @return array|null
     * @throws Zend_Db_Select_Exception
     */
    public function getPersonsAndDocuments($person, $documents = null)
    {
        $table = TableGateway::getInstance(self::$personTableClass);

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
     * @param array      $person Criteria for matching persons
     * @param array      $changes Map of column names and new values
     * @param null|array $documents Array with document Ids
     *
     * TODO update ServerDateModified for modified documents (How?)
     */
    public function updateAll($person, $changes, $documents = null)
    {
        if (empty($person)) {
            // TODO logging?
            return;
        }

        if (empty($changes)) {
            // TODO logging?
            return;
        }

        $table = TableGateway::getInstance(self::$personTableClass);

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

    /**
     * @param int[]      $personIds
     * @param int[]|null $documents
     * @return array|int[]
     * @throws Zend_Db_Select_Exception
     */
    public function getDocuments($personIds, $documents = null)
    {
        $table = TableGateway::getInstance(self::$personTableClass);

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

        $person = Person::new();

        foreach ($changes as $fieldName => $value) {
            // TODO convertFieldnameToColumn should not be part of Person-class
            $column = $person::convertFieldnameToColumn($fieldName);

            $columnChanges[$column] = $value;
        }

        return $columnChanges;
    }

    /**
     * @param Zend_Db_Select $select
     * @param string[]       $person
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

    /**
     * Constructs select statement for getting all persons matching criteria.
     *
     * @param null|string $role
     * @param null|string $filter
     * @return Zend_Db_Select
     *
     * TODO should be protected, or?
     */
    public static function getAllPersonsSelect($role = null, $filter = null)
    {
        $database = Zend_Db_Table::getDefaultAdapter();

        $table = TableGateway::getInstance(self::$personTableClass);

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
     * Returns all unique ORCID values from the database.
     *
     * TODO Can this be separated from the core functionality, like and extension of a "PersonRepository"?
     *
     * @return string[]
     */
    public function getAllUniqueIdentifierOrcid()
    {
        $table    = TableGateway::getInstance(self::$personTableClass);
        $database = $table->getAdapter();
        $select   = $table->select()->from($table, 'identifier_orcid')
            ->where('identifier_orcid IS NOT NULL')
            ->distinct();

        return $database->fetchCol($select);
    }

    /**
     * Return all ORCID IDs with document and person database IDs.
     *
     * @return array
     */
    public function getAllIdentifierOrcid()
    {
        $table    = TableGateway::getInstance(self::$personTableClass);
        $database = $table->getAdapter();
        $select   = $table->select();

        $select->from(
            ['p' => 'persons'],
            []
        )->join(
            ['link' => 'link_persons_documents'],
            'link.person_id = p.id',
            []
        )->columns(
            ['link.document_id AS documentId', 'link.person_id AS personId', 'p.identifier_orcid AS orcidId']
        )->where('identifier_orcid IS NOT NULL');

        $select->setIntegrityCheck(false);

        return $database->fetchAll($select);
    }

    /**
     * Removes URL Prefix from OCRiD IDs.
     */
    public function normalizeOrcidValues()
    {
        $database = TableGateway::getInstance(self::$personTableClass)->getAdapter();

        $sql = <<<SQL
UPDATE persons SET identifier_orcid = REPLACE(UPPER(identifier_orcid), 'HTTPS://ORCID.ORG/', '') 
               WHERE UPPER(identifier_orcid) LIKE 'HTTPS://ORCID.ORG/%'
SQL;

        $database->query($sql);

        $sql = <<<SQL
UPDATE persons SET identifier_orcid = REPLACE(UPPER(identifier_orcid), 'HTTP://ORCID.ORG/', '') 
               WHERE UPPER(identifier_orcid) LIKE 'HTTP://ORCID.ORG/%'
SQL;

        $database->query($sql);
    }

    /**
     * @param bool $keepPersonsWithIdentifiers
     */
    public function deleteOrphanedPersons($keepPersonsWithIdentifiers = false)
    {
        $database = TableGateway::getInstance(self::$personTableClass)->getAdapter();

        if ($keepPersonsWithIdentifiers) {
            $sql = <<<SQL
DELETE FROM persons 
       WHERE id NOT IN (SELECT DISTINCT(person_id) FROM link_persons_documents)
         AND identifier_orcid IS NULL
         AND identifier_gnd IS NULL
         AND identifier_misc IS NULL
SQL;
        } else {
            $sql = <<<SQL
DELETE FROM persons WHERE id NOT IN (SELECT DISTINCT(person_id) FROM link_persons_documents) 
SQL;
        }

        $database->query($sql);
    }

    /**
     * @return int
     */
    public function getOrphanedPersonsCount()
    {
        $database = TableGateway::getInstance(self::$personTableClass)->getAdapter();

        $sql = <<<SQL
SELECT COUNT(id) FROM persons WHERE id NOT IN (SELECT DISTINCT(person_id) FROM link_persons_documents)
SQL;

        return $database->fetchOne($sql);
    }

    /**
     * @param string $oldOrcid
     * @param string $newOrcid
     */
    public function replaceOrcid($oldOrcid, $newOrcid)
    {
        $database = TableGateway::getInstance(self::$personTableClass)->getAdapter();

        $quotedNewOrcid = $database->quote($newOrcid);
        $quotedOldOrcid = $database->quote($oldOrcid);

        $sql = <<<SQL
UPDATE persons SET identifier_orcid = $quotedNewOrcid WHERE identifier_orcid = $quotedOldOrcid; 
SQL;

        $database->query($sql);
    }
}
