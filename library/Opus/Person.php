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
use Opus\Db\LinkPersonsDocuments;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Db_Table;
use Zend_Validate_EmailAddress;
use Zend_Validate_NotEmpty;

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
 */
class Person extends AbstractDb implements PersonInterface
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
