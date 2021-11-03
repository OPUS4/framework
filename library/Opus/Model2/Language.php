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
 *
 * @category    Framework
 * @package     Opus\Db2
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="languages")
 */
class Language
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(type="string", name="part2_b", length=3, options={"fixed" = true})
     * @var string
     */
    private $part2B;

    /**
     * @ORM\Column(type="string", name="part2_t", length=3, options={"fixed" = true})
     * @var string
     */
    private $part2T;

    /**
     * @ORM\Column(type="string", length=2, options={"fixed" = true})
     * @var string
     */
    private $part1;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('I','M','S')")
     * @var string
     */
    private $scope;

    /**
     * @ORM\Column(type="string", columnDefinition="ENUM('A','C','E','H','L','S')")
     * @var string
     */
    private $type;

    /**
     * @ORM\Column(type="string", name="ref_name", length=150)
     * @var string
     */
    private $refName;

    /**
     * @ORM\Column(type="string", length=150)
     * @var string
     */
    private $comment;

    /**
     * @ORM\Column(type="smallint")
     * @var int
     */
    private $active = 0;

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
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getPart2B()
    {
        return $this->part2B;
    }

    /**
     * @param string $part2B
     */
    public function setPart2B(string $part2B)
    {
        $this->part2B = $part2B;
    }

    /**
     * @return string
     */
    public function getPart2T()
    {
        return $this->part2T;
    }

    /**
     * @param string $part2T
     */
    public function setPart2T(string $part2T)
    {
        $this->part2T = $part2T;
    }

    /**
     * @return string
     */
    public function getPart1()
    {
        return $this->part1;
    }

    /**
     * @param string $part1
     */
    public function setPart1(string $part1)
    {
        $this->part1 = $part1;
    }

    /**
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }

    /**
     * @param string $scope
     */
    public function setScope(string $scope)
    {
        $this->scope = $scope;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getRefName()
    {
        return $this->refName;
    }

    /**
     * @param string $refName
     */
    public function setRefName(string $refName)
    {
        $this->refName = $refName;
    }

    /**
     * @return string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment)
    {
        $this->comment = $comment;
    }

    /**
     * @return int
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param int $active
     */
    public function setActive(int $active)
    {
        $this->active = $active;
    }
}
