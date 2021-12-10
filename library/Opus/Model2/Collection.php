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

use Doctrine\Common\Collections\Collection as ORMCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="Opus\Db2\CollectionRepository")
 * @ORM\Table(name="collections")
 *
 * @Gedmo\Tree(type="nested")
 *
 * TODO add more properties & functions from Opus\Collection
 */
class Collection extends AbstractModel
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
     * @ORM\Column(name="left_id", type="integer")
     *
     * @Gedmo\TreeLeft
     *
     * @var int
     */
    private $left;

    /**
     * @ORM\Column(name="right_id", type="integer")
     *
     * @Gedmo\TreeRight
     *
     * @var int
     */
    private $right;

    /**
     * @ORM\Column(name="parent_id", type="integer")
     *
     * @var int
     */
    private $parentId;

    /**
     * @ORM\ManyToOne(targetEntity="Collection", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE", nullable=true)
     *
     * @Gedmo\TreeParent
     *
     * @var self|null
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="Collection", mappedBy="parent")
     * @ORM\OrderBy({"left" = "ASC"})
     *
     * @var ORMCollection|self[]
     */
    private $children;

    /**
     * @ORM\Column(name="role_id", type="integer")
     *
     * @var int
     */
    private $roleId;

    /**
     * @ORM\OneToOne(targetEntity="CollectionRole", inversedBy="rootCollection")
     * @ORM\JoinColumn(name="role_id", referencedColumnName="id")
     *
     * @var CollectionRole
     */
    private $role;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $number;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="oai_subset", type="string", length=255, nullable=true)
     *
     * @var string
     */
    private $oaiSubset;

    /**
     * @ORM\Column(type="smallint", options={"unsigned":true, "default":1})
     *
     * @var int
     */
    protected $visible = 1;

    /**
     * @ORM\Column(name="visible_publish", type="smallint", options={"unsigned":true, "default":1})
     *
     * @var int
     */
    protected $visiblePublish = 1;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getLeft()
    {
        return $this->left;
    }

    /**
     * @return int
     */
    public function getRight()
    {
        return $this->right;
    }

    /**
     * @return int
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * @return self|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param self|null $parent
     * @return $this
     */
    public function setParent($parent)
    {
        $this->parent   = $parent;
        $this->parentId = $parent->getId();

        return $this;
    }

    /**
     * @return ORMCollection|self[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @return int
     */
    public function getRoleId()
    {
        return $this->roleId;
    }

    /**
     * @return CollectionRole
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * @param CollectionRole $role
     * @return $this
     */
    public function setRole($role)
    {
        $this->role   = $role;
        $this->roleId = $role->getId();

        return $this;
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
     * @return $this
     */
    public function setNumber($number)
    {
        $this->number = $number;

        return $this;
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
    public function getOaiSubset()
    {
        return $this->oaiSubset;
    }

    /**
     * @param string $oaiSubset
     * @return $this
     */
    public function setOaiSubset($oaiSubset)
    {
        $this->oaiSubset = $oaiSubset;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * @param int $visible
     * @return $this
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisiblePublish()
    {
        return $this->visiblePublish;
    }

    /**
     * @param int $visiblePublish
     * @return $this
     */
    public function setVisiblePublish($visiblePublish)
    {
        $this->visiblePublish = $visiblePublish;

        return $this;
    }

    /**
     * Retrieve all Collection instances from the database.
     *
     * @return self[]
     */
    public static function getAll()
    {
        return self::getRepository()->getAll();
    }

    /**
     * Returns all Collection nodes with the given role ID. Always returns an array, even if the
     * result set has zero or one element.
     *
     * @param  int  $roleId The ID of the tree structure whose Collection nodes shall be returned.
     * @param  bool $sortResults (Optional) If true sort results by left ID.
     * @return self[]
     */
    public static function fetchCollectionsByRoleId($roleId, $sortResults = false)
    {
        return self::getRepository()->fetchCollectionsByRoleId($roleId, $sortResults);
    }

    /**
     * Returns all Collection nodes with the given role ID & name. Always returns an array, even if
     * the result set has zero or one element.
     *
     * @param  int    $roleId The ID of the tree structure whose Collection nodes shall be returned.
     * @param  string $name
     * @return self[]
     */
    public static function fetchCollectionsByRoleName($roleId, $name)
    {
        return self::getRepository()->fetchCollectionsByRoleName($roleId, $name);
    }

    /**
     * Returns all child nodes of the Collection node with given ID.
     *
     * @param  int  $parentId The ID of the node whose children shall be returned.
     * @param  bool $sortResults (Optional) If true sort results by left ID.
     * @return self[]
     */
    public static function fetchChildrenByParentId($parentId, $sortResults = false)
    {
        return self::getRepository()->fetchChildrenByParentId($parentId, $sortResults);
    }

    /**
     * Returns the relevant properties of the class.
     *
     * @return array
     */
    protected static function describe()
    {
        return ['Number', 'Name', 'OaiSubset'];
    }
}
