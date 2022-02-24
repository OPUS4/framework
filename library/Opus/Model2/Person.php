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
use Opus\Db\LinkPersonsDocuments;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Opus\Model\ModelException;
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
 * @category    Framework
 * @package     Opus
 *
 * phpcs:disable
 *
 * @ORM\Entity(repositoryClass="Opus\Db2\PersonRepository")
 * @ORM\Table(name="persons",
 *     indexes={
 *         @ORM\Index(name="last_name", columns={"last_name"})
 *     })
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
    private $title;

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
    private $opusId;

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
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
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
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
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
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
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
     */
    public function setDateOfBirth($dateOfBirth)
    {
        $this->dateOfBirth = $dateOfBirth;
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
     */
    public function setPlaceOfBirth($placeOfBirth)
    {
        $this->placeOfBirth = $placeOfBirth;
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
     */
    public function setIdentifierOrcid($identifierOrcid)
    {
        $this->identifierOrcid = $identifierOrcid;
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
     */
    public function setIdentifierGnd($identifierGnd)
    {
        $this->identifierGnd = $identifierGnd;
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
     */
    public function setIdentifierMisc($identifierMisc)
    {
        $this->identifierMisc = $identifierMisc;
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
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
     */
    public function setOpusId($opusId)
    {
        $this->opusId = $opusId;
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            'Title',
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
}
