<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Opus\Db2\CollectionRoleRepository")
 * @ORM\Table(name="collections_roles")
 *
 * TODO add more properties & functions from Opus\CollectionRole
 */
class CollectionRole extends AbstractModel
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
     * @ORM\Column(type="string", length=191, unique=true)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\OneToOne(targetEntity="Collection", mappedBy="role")
     *
     * @var Collection
     */
    private $rootCollection;

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
     * @return Collection
     */
    public function getRootCollection()
    {
        return $this->rootCollection;
    }

    /**
     * @param Collection $rootCollection
     * @return $this
     */
    public function setRootCollection($rootCollection)
    {
        $this->rootCollection = $rootCollection;

        return $this;
    }

    /**
     * Retrieve all CollectionRole instances from the database.
     *
     * @return self[]
     */
    public static function getAll()
    {
        return self::getRepository()->getAll();
    }

    /**
     * Retrieves an existing CollectionRole instance by name. Returns
     * null if name is null *or* if nothing was found.
     *
     * @param  string|null $name
     * @return self|null
     */
    public static function fetchByName($name = null)
    {
        return self::getRepository()->fetchByName($name);
    }

    /**
     * Returns the relevant properties of the class.
     *
     * @return array
     */
    protected static function describe()
    {
        return ['Name'];
    }
}
