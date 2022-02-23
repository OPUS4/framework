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
 * TODO properly implement methods to get & set/store the collection's theme
 * TODO implement isNewRecord()?
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
     * TODO proper implementation
     *
     * @var string
     */
    private $theme;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

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
        if ($this->parent === $parent) {
            return $this;
        }

        if ($this->parent !== null) {
            $this->parent->removeChild($this);
        }

        $this->parent = $parent;

        if ($parent !== null) {
            $parent->addChild($this);
        }

        return $this;
    }

    /**
     * @param Collection $child
     * @return $this
     */
    protected function addChild($child)
    {
        // TODO DOCTRINE error handling, logging, exception?
        if ($child === null || $this->children->contains($child)) {
            return $this;
        }

        $this->children->add($child);
        $child->setParent($this);

        return $this;
    }

    /**
     * @param Collection $child
     * @return $this
     */
    protected function removeChild($child)
    {
        if (! $this->children->contains($child)) {
            return $this;
        }

        $this->children->removeElement($child);
        $child->setParent(null);

        return $this;
    }

    /**
     * Returns all child nodes. Always returns an array, even if the result set has zero or one element.
     *
     * @return self[]
     */
    public function getChildren()
    {
        // TODO DOCTRINE The $this->children property is currently unused but needed for ORM to specify the relationship

        // TODO DOCTRINE Is the $direct param (flag indicating whether only direct children should be retrieved) correct?
        $children = self::getRepository()->children($this, false, 'left');

        return $children ?: [];
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
     * TODO proper implementation
    // TODO return default theme if no theme is set
     *
     * @return string
     */
    public function getTheme()
    {
//        $theme = $this->theme;
//        return $theme ?: Config::get()->theme;

        return $this->theme;
    }

    /**
     * TODO proper implementation
     *
     * @param string $theme
     * @return $this
     */
    public function setTheme($theme)
    {
        $this->theme = $theme;

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
     * Adds the given Collection node (or a new Collection node if none is given) as the first child to this
     * Collection node.
     *
     * @param self|null $child (Optional) The Collection node that shall be added as the first child to this instance.
     * @return self The child collection.
     */
    public function addFirstChild($child = null)
    {
        if ($child === null) {
            $child = new Collection();
            $child->setRole($this->getRole());
        }

        $child->setParent($this);
        self::getRepository()->persistAsFirstChildOf($child, $this);

        return $child;
    }

    /**
     * Adds the given Collection node (or a new Collection node if none is given) as the last child to this
     * Collection node.
     *
     * @param self|null $child (Optional) The Collection node that shall be added as the last child to this instance.
     * @return self The child collection.
     */
    public function addLastChild($child = null)
    {
        if ($child === null) {
            $child = new Collection();
            $child->setRole($this->getRole());
        }

        $child->setParent($this);
        self::getRepository()->persistAsLastChildOf($child, $this);

        return $child;
    }

    /**
     * Adds the given Collection node (or a new Collection node if none is given) as the next sibling to this
     * Collection node.
     *
     * @param self|null $sibling (Optional) The Collection node that shall be added as the next sibling to this instance.
     * @return self The sibling collection.
     */
    public function addNextSibling($sibling = null)
    {
        if ($sibling === null) {
            $sibling = new Collection();
            $sibling->setRole($this->getRole());
        }

        $sibling->setParent($this->getParent());
        self::getRepository()->persistAsNextSiblingOf($sibling, $this);

        return $sibling;
    }

    /**
     * Adds the given Collection node (or a new Collection node if none is given) as the previous sibling to this
     * Collection node.
     *
     * @param self|null $sibling (Optional) The Collection node that shall be added as the previous sibling to this
     * instance.
     * @return self The sibling collection.
     */
    public function addPrevSibling($sibling = null)
    {
        if ($sibling === null) {
            $sibling = new Collection();
            $sibling->setRole($this->getRole());
        }

        $sibling->setParent($this->getParent());
        self::getRepository()->persistAsPrevSiblingOf($sibling, $this);

        return $sibling;
    }

    public function moveAfterNextSibling()
    {
        self::getRepository()->moveDown($this);
    }

    public function moveBeforePrevSibling()
    {
        self::getRepository()->moveUp($this);
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
