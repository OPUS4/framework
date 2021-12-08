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
 *
 * @category    Framework
 * @package     Opus
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;
use Opus\Date;

/**
 * Domain model for patents in the Opus framework
 *
 * @uses        \Opus\Model2\AbstractModel
 *
 * @category    Framework
 * @package     Opus
 *
 * @ORM\Entity
 * @ORM\Table(name="document_patents")
 */
class Patent extends AbstractModel
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    private $id;

    // TODO: Mapping as one-to-many relation with a document
    // The @var type should then be changed to Model2\Document instead of int
    /**
     * @ORM\Column(name="document_id", type="integer")
     *
     * @var int
     */
    private $document;

    /**
     * @ORM\Column(name="countries", type="text")
     *
     * @var string
     */
    private $countries;

    /**
     * @ORM\Column(name="date_granted", type="opusDate")
     *
     * @var Date
     */
    private $dateGranted;

    /**
     * @ORM\Column(name="number", type="string", length=255)
     *
     * @var string
     */
    private $number;

   /**
    * @ORM\Column(type="integer", name="year_applied", nullable=true, options={"unsigned":true})
    *
    * @var int
    */
    private $yearApplied;

    /**
     * @ORM\Column(name="application", type="text")
     *
     * @var string
     */
    private $application;

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
     * @return int
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @param int $document
     */
    public function setDocument($document)
    {
        $this->document = $document;
    }

    /**
     * @return string
     */
    public function getCountries()
    {
        return $this->countries;
    }

    /**
     * @param string $countries
     */
    public function setCountries($countries)
    {
        $this->countries = $countries;
    }

    /**
     * @return Date
     */
    public function getDateGranted()
    {
        return $this->dateGranted;
    }

    /**
     * @param Date|string|array $dateGranted
     */
    public function setDateGranted($dateGranted)
    {
        if ($dateGranted instanceof Date) {
            $this->dateGranted = $dateGranted;
        } elseif (is_array($dateGranted)) {
            // TODO: Do we realy need this? In case 'Patent'->fromArray() will be called
            // with an array returned by 'Patent'->toArray(), like in PatentTest testToArray(),
            // it could be helpfull.
            $this->dateGranted = Date::fromArray($dateGranted);
        } else {
            $this->dateGranted = new Date($dateGranted);
        }
    }

    /**
     * @return string
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * @param string $number
     */
    public function setNumber($number)
    {
        $this->number = $number;
    }

    /**
     * @return int
     */
    public function getYearApplied()
    {
        return $this->yearApplied;
    }

    /**
     * @param int $yearApplied
     */
    public function setYearApplied($yearApplied)
    {
        $this->yearApplied = $yearApplied;
    }

    /**
     * @return string
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @param string $application
     */
    public function setApplication($application)
    {
        $this->application = $application;
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            'Countries',
            'DateGranted',
            'Number',
            'YearApplied',
            'Application'
        ];
    }


}
