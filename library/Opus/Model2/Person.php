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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;
use Opus\Date;

use function array_fill_keys;
use function array_merge;
use function is_array;
use function is_string;
use function stristr;

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
 *
 * @ORM\Entity(repositoryClass="Opus\Db2\PersonRepository")
 * @ORM\Table(name="persons",
 *     indexes={
 *         @ORM\Index(name="last_name", columns={"last_name"})
 *     })
 *
 * @category Framework
 * @package  Opus
 */
class Person extends AbstractModel
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="academic_title", type="string", length=255, nullable="true")
     * @var string
     */
    private $academicTitle;

    /**
     * @ORM\Column(name="first_name", type="string", length=255, nullable="true")
     * @var string
     */
    private $firstName;

    /**
     * @ORM\Column(name="last_name", type="string", length=191, nullable="false")
     * @var string
     */
    private $lastName;

    /**
     * @ORM\Column(name="date_of_birth", type="opusDate", length=50, nullable="true")
     * @var Date
     */
    private $dateOfBirth;

    /**
     * @ORM\Column(name="place_of_birth", type="string", length=255, nullable="true")
     * @var string
     */
    private $placeOfBirth;

    /**
     * @ORM\Column(name="identifier_orcid", type="string", length=50, nullable="true")
     * @var string
     */
    private $identifierOrcid;

    /**
     * @ORM\Column(name="identifier_gnd", type="string", length=50, nullable="true")
     * @var string
     */
    private $identifierGnd;

    /**
     * @ORM\Column(name="identifier_misc", type="string", length=50, nullable="true")
     * @var string
     */
    private $identifierMisc;

    /**
     * @ORM\Column(name="email", type="string", length=191, nullable="true")
     * @var string
     */
    private $email;

    /**
     * @ORM\Column(name="opus_id", type="integer")
     * @var string
     */
    private $opusId = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getAcademicTitle()
    {
        return $this->academicTitle;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setAcademicTitle($title)
    {
        return $this->academicTitle = $title;
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     * @return $this
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     * @return $this
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
        return $this;
    }

    /**
     * @return Date
     */
    public function getDateOfBirth()
    {
        return $this->dateOfBirth;
    }

    /**
     * @param Date $dateOfBirth
     * @return $this
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
        return $this;
    }

    /**
     * @return string
     */
    public function getPlaceOfBirth()
    {
        return $this->placeOfBirth;
    }

    /**
     * @param string $placeOfBirth
     * @return $this
     */
    public function setPlaceOfBirth($placeOfBirth)
    {
        $this->placeOfBirth = $placeOfBirth;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifierOrcid()
    {
        return $this->identifierOrcid;
    }

    /**
     * @param string $identifierOrcid
     * @return $this
     */
    public function setIdentifierOrcid($identifierOrcid)
    {
        $this->identifierOrcid = $identifierOrcid;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifierGnd()
    {
        return $this->identifierGnd;
    }

    /**
     * @param string $identifierGnd
     * @return $this
     */
    public function setIdentifierGnd($identifierGnd)
    {
        $this->identifierGnd = $identifierGnd;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifierMisc()
    {
        return $this->identifierMisc;
    }

    /**
     * @param string $identifierMisc
     * @return $this
     */
    public function setIdentifierMisc($identifierMisc)
    {
        $this->identifierMisc = $identifierMisc;
        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     * @return $this
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getOpusId()
    {
        return $this->opusId;
    }

    /**
     * @param string $opusId
     * @return $this
     */
    public function setOpusId($opusId)
    {
        $this->opusId = $opusId;
        return $this;
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            'AcademicTitle',
            'FirstName',
            'LastName',
            'DateOfBirth',
            'PlaceOfBirth',
            'IdentifierOrcid',
            'IdentifierGnd',
            'IdentifierMisc',
            'Email',
            'OpusId'
        ];
    }

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
     */
    public function getDisplayName()
    {
        return $this->getName();
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

            $criteria = [];
            $criteria['LastName'] = $person->getLastName();
            $criteria['FirstName'] = $person->getFirstName();
            $criteria['IdentifierOrcid'] = $person->getIdentifierOrcid();
            $criteria['IdentifierGnd'] = $person->getIdentifierGnd();
            $criteria['IdentifierMisc'] = $person->getIdentifierMisc();
        }

        if (!is_array($criteria)) {
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

        foreach ($criteria as $fieldName => $criteriaValue) {
            $getField = 'get' . $fieldName;
            $value = $this->$getField();

            if (is_string($value)) {
                if (stristr($value, $criteriaValue) === false) {
                    return false;
                }
            } else {
                if ($value !== $criteriaValue) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getModelType()
    {
        return 'person';
    }

    /**
     * Retrieve all persons from the database.
     *
     * @return array|object[] Array of Person objects.
     */
    public static function getAll()
    {
        return self::getRepository()->findAll();
    }

    /**
     * Returns all persons in the database without duplicates.
     *
     * Every real person might be represented by several objects, one for each document.
     *
     * @param string|null $role
     * @param int $start
     * @param int $limit
     * @param string|null $filter
     * @return array
     */
    public static function getAllPersons($role = null, $start = 0, $limit = 0, $filter = null)
    {
        return self::getRepository()->getAllPersons($role, $start, $limit, $filter);
    }

    /**
     * Returns total count of persons for role and filter string.
     *
     * @param string|null $role
     * @param string|null $filter
     * @return mixed
     */
    public static function getAllPersonsCount($role = null, $filter = null)
    {
        return self::getRepository()->getAllPersonsCount($role, $filter);
    }

    /**
     * Fetches all documents associated to the person by a certain role.
     *
     * @param string $role The role that the person has for the documents.
     * @return array An array of Opus\Document
     */
    public function getDocumentsByRole($role)
    {
        return self::getRepository()->getDocumentsByRole($this, $role);
    }

    /**
     * Returns the ids for all linked documents.
     *
     * @param string|null $role
     * @return array|void
     */
    public function getDocumentIds($role = null)
    {
        return self::getRepository()->getDocumentIds($this, $role);
    }


    /**
     * Get a list of IDs for Persons that have the specified role for
     * certain documents.
     *
     * @param string $role Role name.
     * @return array List of Opus\Person Ids for Person models assigned to the specified Role.
     */
    public static function getAllIdsByRole($role)
    {
        return self::getRepository()->getAllIdsByRole($role);
    }

    /**
     * Returns roles for a person.
     *
     * TODO verify columns
     * TODO use object for person
     */
    public static function getPersonRoles($person)
    {
        return self::getRepository()->getPersonRoles($person);
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
     * @param array $person
     * @param string|null $state
     * @param string|null $role
     * @param string|null $sort
     * @param bool $order
     * @return array
     */
    public static function getPersonDocuments($person, $state = null, $role = null, $sort = null, $order = true)
    {
        return self::getRepository()->getPersonDocuments($person, $state, $role, $sort, $order);
    }

    /**
     * Returns the value of matching person objects.
     *
     * @param array $person
     * @return array
     */
    public static function getPersonValues($person)
    {
        return self::getRepository()->getPersonValues($person);
    }

    /**
     * Returns ids of person objects matching criteria and documents.
     *
     * TODO filter by role?
     *
     * @param array $person Criteria for matching persons
     * @param array|null $documents Array with ids of documents
     * @return array Array with IDs of persons
     */
    public static function getPersons($person, $documents = null)
    {
        return self::getRepository()->getPersons($person, $documents);
    }

    /**
     * @param array $person
     * @param array|null $documents
     * @return array
     */
    public static function getPersonsAndDocuments($person, $documents = null)
    {
        return self::getRepository()->getPersonsAndDocuments($person, $documents);
    }

    /**
     * @param array $personIds
     * @param array|null $documents
     * @return array
     */
    public static function getDocuments($personIds, $documents = null)
    {
        return self::getRepository()->getDocuments($personIds, $documents);
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
    public static function updateAll($person, $changes, $documents = null)
    {
        return self::getRepository()->updateAll($person, $changes, $documents);
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
     * @param array $person
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
}
