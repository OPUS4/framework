<?php

/*
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
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;

/**
 * Domain model for DnbInstitute in the Opus framework
 *
 * @uses  \Opus\Model2\AbstractModel
 *
 * @ORM\Entity(repositoryClass="Opus\Db2\DnbInstituteRepository")
 *
 * @ORM\Table(name="dnb_institutes",
 *     indexes={
 *         @ORM\Index(name="name", columns={"name", "department"})
 *     })
 */
class DnbInstitute extends AbstractModel
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
     * @ORM\Column(name="name", type="string", length=191)
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="department", type="string", length=191, nullable=true)
     * @var string
     */
    private $department;

    /**
     * @ORM\Column(name="address", type="text", length=16777215, nullable=true)
     * @var string
     */
    private $address;

    /**
     * @ORM\Column(name="city", type="string", length=255)
     * @var string
     */
    private $city;

    /**
     * @ORM\Column(name="phone", type="string", length=255, nullable=true)
     * @var string
     */
    private $phone;

    /**
     * @ORM\Column(name="dnb_contact_id", type="string", length=255, nullable=true)
     * @var string
     */
    private $dnbContactId;

    /**
     * @ORM\Column(name="is_grantor", type="boolean")
     * @var bool
     */
    private $isGrantor = false;

    /**
     * @ORM\Column(name="is_publisher", type="boolean")
     * @var bool
     */
    private $isPublisher = false;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getDepartment()
    {
        return $this->department;
    }

    /**
     * @param string $department
     * @return $this
     */
    public function setDepartment($department)
    {
        $this->department = $department;
        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param string $address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     * @return $this
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
        return $this;
    }

    /**
     * @return string
     */
    public function getDnbContactId()
    {
        return $this->dnbContactId;
    }

    /**
     * @param string $dnbContactId
     * @return $this
     */
    public function setDnbContactId($dnbContactId)
    {
        $this->dnbContactId = $dnbContactId;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsGrantor()
    {
        return $this->isGrantor;
    }

    /**
     * @param bool $isGrantor
     * @return $this
     */
    public function setIsGrantor($isGrantor)
    {
        $this->isGrantor = $isGrantor;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsPublisher()
    {
        return $this->isPublisher;
    }

    /**
     * @param bool $isPublisher
     * @return $this
     */
    public function setIsPublisher($isPublisher)
    {
        $this->isPublisher = $isPublisher;
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
            'Name',
            'Department',
            'Address',
            'City',
            'Phone',
            'DnbContactId',
            'IsGrantor',
            'IsPublisher',
        ];
    }

    /**
     * Retrieve all Opus\DnbInstitute instances from the database.
     *
     * @return array Array of Opus\Model2\DnbInstitute objects.
     */
    public static function getAll()
    {
        return self::getRepository()->findAll();
    }

    /**
     * Returns a list of organisational units that act as (thesis) grantors.
     *
     * @return array A list of Opus\DnbInstitutes that act as grantors.
     */
    public static function getGrantors()
    {
        return self::getRepository()->getGrantors();
    }

    /**
     * Returns a list of organisational units that act as (thesis) publishers.
     *
     * @return array A list of Opus\DnbInstitutes that act as publishers.
     */
    public static function getPublishers()
    {
        return self::getRepository()->getPublishers();
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
     * Returns name.
     *
     * @see \Opus\Model\Abstract#getDisplayName()
     */
    public function getDisplayName()
    {
        $departmentName = $this->getDepartment();
        return $this->getName() . (empty($departmentName) ? '' : ', ' . $departmentName);
    }

    /**
     * Checks if DNB institute is used by any document.
     */
    public function isUsed()
    {
        return self::getRepository()->isUsed($this->getId());
    }
}
