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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Opus\Db2\UserRoleRepository")
 * @ORM\Table(name="user_roles")
 *
 * TODO Decide how to handle access documents/files/modules
 * TODO Check whether it's really necessary to specify the inverse relationship of Account->role (i.e.,
 *      UserRole->accounts & its supporting methods getAccounts(), setAccounts(), addAccount() & removeAccount()).
 */
class UserRole extends AbstractModel
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
     * @ORM\Column(type="string", length=100, unique=true)
     *
     * @var string
     *
     * TODO field is mandatory
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity="Account", mappedBy="role")
     *
     * @var Collection|Account[]
     */
    private $accounts;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
    }

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
     * @return Collection|Account[]
     */
    public function getAccounts()
    {
        return $this->accounts;
    }

    /**
     * @param Collection|Account[] $accounts
     * @return $this
     */
    public function setAccounts($accounts)
    {
        $this->accounts = $accounts;

        return $this;
    }

    /**
     * @param Account $account
     * @return $this
     */
    public function addAccount($account)
    {
        // TODO error handling, logging, exception?
        if ($account === null || $this->accounts->contains($account)) {
            return $this;
        }

        $this->accounts->add($account);
        $account->addRole($this);

        return $this;
    }

    /**
     * @param Account $account
     */
    public function removeAccount($account)
    {
        if (! $this->accounts->contains($account)) {
            return;
        }

        $this->accounts->removeElement($account);
        $account->removeRole($this);
    }

    /**
     * Retrieve all UserRole instances from the database.
     *
     * @return self[]
     */
    public static function getAll()
    {
        return self::getRepository()->getAll();
    }

    /**
     * Retrieves an existing UserRole instance by name. Returns
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
     * Returns name.
     *
     * @see \Opus\Model\AbstractModel#getDisplayName()

     * @return string
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * Get a list of all account IDs for the current role instance.
     *
     * @return int[]
     */
    public function getAllAccountIds()
    {
        return self::getRepository()->getAllAccountIds($this->getId());
    }

    /**
     * Get a list of all account login names for the current role instance.
     *
     * @return string[]
     */
    public function getAllAccountNames()
    {
        return self::getRepository()->getAllAccountNames($this->getId());
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return ['Name'];
    }
}