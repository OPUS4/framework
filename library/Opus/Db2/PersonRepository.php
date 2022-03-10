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
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Opus\Date;
use Opus\Document;
use Opus\Model\ModelException;
use Opus\Model2\Person;

use function array_fill_keys;
use function array_key_exists;
use function array_map;
use function array_merge;
use function array_push;
use function array_search;
use function array_unique;
use function count;
use function implode;
use function in_array;
use function is_array;
use function lcfirst;
use function property_exists;
use function strlen;
use function trim;

/**
 * Database specific class for Person functions.
 *
 * This class keeps the database (Doctrine) specific code out of the model class.
 */
class PersonRepository extends EntityRepository
{
    /**
     * Constructs select statement for getting all persons matching criteria.
     *
     * @param  string|null $role
     * @param  string|null $filter
     * @return QueryBuilder
     */
    public function getAllPersonsSelect($role = null, $filter = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $columns = ['last_name', 'first_name', 'identifier_orcid', 'identifier_gnd', 'identifier_misc'];

        $trimmedColumns = implode(
            ',',
            array_map(function ($value) {
                return "trim($value) as $value";
            }, $columns)
        );

        $groupColumns = implode(
            ',',
            array_map(function ($value) {
                return "trim($value)";
            }, $columns)
        );

        $identityColumns = implode(',', $columns);

        $subSelect = $conn->createQueryBuilder()
            ->select($trimmedColumns)
            ->from('persons', 'pe');

        if ($role !== null) {
            $subSelect->join('pe', 'link_persons_documents', 'link', 'pe.id = link.person_id')
                ->where('link.role = :role')
                ->setParameter('role', $role);
        }

        if ($filter !== null) {
            $subSelect->andWhere('last_name LIKE :lastName OR first_name LIKE :firstName')
            ->setParameter('lastName', '%' . $filter . '%')
            ->setParameter('firstName', '%' . $filter . '%');
        }

        // result still contains name duplicates because of leading spaces -> group trimmed result
        $select = $conn->createQueryBuilder()
            ->select($identityColumns)
            ->from('(' . $subSelect->getSQL() . ') as p')
            ->groupBy($groupColumns);

        if ($subSelect->getParameters()) {
            $select->setParameters($subSelect->getParameters());
        }

        return $select;
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
     */
    public function getAllPersons($role = null, $start = 0, $limit = 0, $filter = null)
    {
        $select = $this->getAllPersonsSelect($role, $filter);

        if ($start !== 0 || $limit !== 0) {
            $select->setFirstResult($start)
                ->setMaxResults($limit);
        }

        $select->orderBy('trim(last_name)')
            ->addOrderBy('trim(first_name)');

         return $select->execute()->fetchAllAssociative();
    }

    /**
     * Returns total count of persons for role and filter string.
     *
     * @param string|null $role
     * @param string|null $filter
     * @return mixed
     */
    public function getAllPersonsCount($role = null, $filter = null)
    {
        $subSelect = $this->getAllPersonsSelect($role, $filter);

        $conn   = $this->getEntityManager()->getConnection();
        $select = $conn->createQueryBuilder()
            ->select('count(*) as num')
            ->from('(' . $subSelect->getSQL() . ') as pc');

        if ($subSelect->getParameters()) {
            $select->setParameters($subSelect->getParameters());
        }

        return $select->execute()->fetchOne();
    }

    /**
     * Fetches all documents associated to the person by a certain role.
     *
     * @param Person $person The ID of the desired person.
     * @param string $role The role that the person has for the documents.
     * @return array An array of Opus\Document
     */
    public function getDocumentsByRole($person, $role)
    {
        if ($person->getId() === 0) {
            return [];
        }

        $conn         = $this->getEntityManager()->getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder->select('d.id')
            ->from('documents', 'd')
            ->join('d', 'link_persons_documents', 'link', 'd.id = link.document_id')
            ->where('link.role = :role')
            ->andWhere('link.person_id = :personId')
            ->distinct()
            ->setParameter('role', $role)
            ->setParameter('personId', $person->getId());

        $documents = [];

        $result = $select->execute()->fetchFirstColumn();

        foreach ($result as $id) {
            $documents[] = Document::get($id);
        }

        return $documents;
    }

    /**
     * Returns the ids for all linked documents.
     *
     * @param Person      $person
     * @param string|null $role
     * @return array
     */
    public function getDocumentIds($person, $role = null)
    {
        if ($person->getId() === 0) {
            // TODO do more?
            return [];
        }

        $conn         = $this->getEntityManager()->getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $select = $queryBuilder->select('distinct(document_id)')
            ->from('link_persons_documents')
            ->where('person_id = :personId')
            ->setParameter('personId', $person->getId());

        if ($role !== null) {
            $select->andWhere('role = :role')
                ->setParameter('role', $role);
        }

        return $select->execute()->fetchFirstColumn();
    }

    /**
     * Get a list of IDs for Persons that have the specified role for
     * certain documents.
     *
     * @param  string $role Role name.
     * @return array List of Opus\Person Ids for Person models assigned to the specified Role.
     */
    public function getAllIdsByRole($role)
    {
        $conn = $this->getEntityManager()->getConnection();

        $select = $conn->createQueryBuilder()
            ->select('person_id')
            ->from('link_persons_documents')
            ->where('role = :role')
            ->setParameter('role', $role);

        return $select->execute()->fetchFirstColumn();
    }

    /**
     * Returns roles for a person.
     *
     * TODO verify columns
     * TODO use object for person
     *
     * @param  array $person
     * @return mixed
     */
    public function getPersonRoles($person)
    {
        $conn = $this->getEntityManager()->getConnection();

        $select = $conn->createQueryBuilder()
            ->select('link.role, count(link.document_id) as documents')
            ->from('link_persons_documents', 'link')
            ->join('link', 'persons', 'p', 'link.person_id = p.id')
            ->groupBy('link.role');

        self::addWherePerson($select, $person);

        return $select->execute()->fetchAllAssociative();
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
     * @param array       $person
     * @param string|null $state
     * @param string|null $role
     * @param string|null $sort
     * @param bool        $order
     * @return array
     */
    public function getPersonDocuments($person, $state = null, $role = null, $sort = null, $order = true)
    {
        $conn = $this->getEntityManager()->getConnection();

        $select = $conn->createQueryBuilder()
            ->select('distinct(d.id)')
            ->from('documents', 'd')
            ->join('d', 'link_persons_documents', 'link', 'link.document_id = d.id')
            ->join('link', 'persons', 'p', 'link.person_id = p.id');

        self::addWherePerson($select, $person);

        if (
            $state !== null && in_array(
                $state,
                ['published', 'unpublished', 'inprogress', 'audited', 'restricted', 'deleted']
            )
        ) {
            $select->andWhere('d.server_state = :state')->setParameter('state', $state);
        }

        if (
            $role !== null && in_array(
                $role,
                ['author', 'editor', 'contributor', 'referee', 'advisor', 'other', 'translator', 'submitter']
            )
        ) {
            $select->andWhere('link.role = :role')->setParameter('role', $role);
        }

        if ($sort !== null && in_array($sort, ['id', 'title', 'publicationDate', 'docType', 'author'])) {
            switch ($sort) {
                case 'id':
                    $select->orderBy('d.id' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'title':
                    $select->select('d.id, t.value');
                    $select->join(
                        'd',
                        'document_title_abstracts',
                        't',
                        't.document_id = d.id'
                    );
                    $select->orderBy('t.value' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'publicationDate':
                    $select->select('d.id, d.server_date_published');
                    $select->orderBy('d.server_date_published' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'docType':
                    $select->select('d.id, d.type');
                    $select->orderBy('d.type' . ($order ? ' ASC' : ' DESC'));
                    break;
                case 'author':
                    $select->select('d.id, p.last_name');
                    $select->orderBy('p.last_name' . ($order ? ' ASC' : ' DESC'));
                    break;
            }
        }

        $documents = $select->execute()->fetchFirstColumn();

        $documents = array_unique($documents); // just in case (TODO sorting by title creates duplicates)

        return $documents;
    }

    /**
     * Returns the value of matching person objects.
     *
     * @param array $person
     * @return array|null
     */
    public function getPersonValues($person)
    {
        $conn = $this->getEntityManager()->getConnection();

        $result = null;

        $select = $conn->createQueryBuilder()
            ->select('*')
            ->from('persons', 'p');

        self::addWherePerson($select, $person);

        $result = $select->execute();

        if ($result->rowCount() === 0) {
            return null;
        }

        $data = $result->fetchAllAssociative();

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
        $conn = $this->getEntityManager()->getConnection();

        $select = $conn->createQueryBuilder()
            ->select('distinct(p.id)')
            ->from('persons', 'p');

        // TODO handle single document id value
        if ($documents !== null && is_array($documents) && count($documents) > 0) {
            $select->join('p', 'link_persons_documents', 'link', 'link.person_id = p.id');
            $select->where('link.document_id IN (:documents)')
                ->setParameter('documents', $documents, Connection::PARAM_INT_ARRAY);
        }

        self::addWherePerson($select, $person);

        return $select->execute()->fetchFirstColumn();
    }

    /**
     * @param array      $person
     * @param array|null $documents
     * @return array
     */
    public function getPersonsAndDocuments($person, $documents = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $select = $conn->createQueryBuilder()
            ->select('link.person_id', 'link.document_id')
            ->from('persons', 'p')
            ->join('p', 'link_persons_documents', 'link', 'link.person_id = p.id');

        if ($documents !== null) {
            $select->where('link.document_id IN (:documents)')
                ->setParameter('documents', $documents, Connection::PARAM_INT_ARRAY);
        }

        self::addWherePerson($select, $person);

        return $select->execute()->fetchAllAssociative();
    }

    /**
     * @param array      $personIds
     * @param array|null $documents
     * @return array
     */
    public function getDocuments($personIds, $documents = null)
    {
        $conn = $this->getEntityManager()->getConnection();

        $select = $conn->createQueryBuilder()
            ->select('distinct(link.document_id)')
            ->from('persons', 'p')
            ->join('p', 'link_persons_documents', 'link', 'link.person_id = p.id')
            ->where('link.person_id IN (:personIds)')
            ->setParameter('personIds', $personIds, Connection::PARAM_INT_ARRAY);

        if ($documents !== null && count($documents) > 0) {
            $select->andWhere('link.document_id IN (:documents)')
            ->setParameter('documents', $documents, Connection::PARAM_INT_ARRAY);
        }

        return $select->execute()->fetchFirstColumn();
    }

    /**
     * @param QueryBuilder $select
     * @param array        $person
     *
     * TODO review
     *
     * This currently adds criteria to match any person with matching values for the specified columns.
     *
     * TODO shoudln't it be an exact match for columns that are empty (only null/empty matches)?
     *
     * NOTE: Function updateAll does not use this, because there does not seem to be a way to hand over
     * an update object like for the select.
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
            if (strlen(trim($value)) > 0) {
                $select->andWhere("trim(p.$column) = :$column")
                    ->setParameter($column, trim($value));
            } else {
                $select->andWhere("p.$column IS NULL");
            }
        }
    }

    /**
     * Updates select columns of matching persons.
     *
     * Optionally the scope can be limited to specified set of documents.
     *
     * @param array      $person Criteria for matching persons
     * @param array      $changes Map of column names and new values
     * @param array|null $documents Array with document Ids
     *
     * TODO update ServerDateModified for modified documents (How?)
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

        foreach ($changes as $name => $value) {
            if (! property_exists(Person::class, lcfirst($name))) {
                // TODO use
                throw new ModelException("unknown field '$name' for update");
            } else {
                if ($value !== null) {
                    $changes[$name] = trim($value);
                } else {
                    $changes[$name] = null;
                }
            }
        }

        $personIds   = self::getPersons($person, $documents);
        $documentIds = self::getDocuments($personIds, $documents);

        if (! empty($personIds)) {
            $queryBuilder = $this->getEntityManager()->createQueryBuilder();
            $update       = $queryBuilder->update(Person::class, 'p');

            foreach ($changes as $key => $value) {
                $update->set('p.' . lcfirst($key), ':' . $key)
                    ->setParameter($key, $value);
            }

            $update->where('p.id IN (:personIds)')
                ->setParameter('personIds', $personIds, Connection::PARAM_INT_ARRAY);

            $update->getQuery()->execute();

            $this->getEntityManager()->clear(Person::class);

            if (! empty($documentIds)) {
                $date = new Date();
                $date->setNow();

                Document::setServerDateModifiedByIds($date, $documentIds);
            }
        }
    }
}
