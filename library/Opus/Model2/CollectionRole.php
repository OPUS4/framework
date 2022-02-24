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
 * TODO add database property hide_empty_collections
 * TODO add more functions from Opus\CollectionRole
 * TODO implement isNewRecord()?
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
     * @ORM\Column(name="oai_name", type="string", length=191, unique=true)
     *
     * @var string
     */
    private $oaiName;

    /**
     * @ORM\Column(type="integer", options={"unsigned":true, "default":0})
     *
     * @var int
     */
    protected $position = 0;

    /**
     * @ORM\Column(type="smallint", options={"unsigned":true, "default":1})
     *
     * @var int
     */
    protected $visible = 1;

    /**
     * @ORM\Column(name="visible_browsing_start", type="smallint", options={"unsigned":true, "default":1})
     *
     * @var int
     */
    protected $visibleBrowsingStart = 1;

    /**
     * @ORM\Column(name="display_browsing", type="string", length=512, nullable=true)
     *
     * @var string
     */
    private $displayBrowsing;

    /**
     * @ORM\Column(name="visible_frontdoor", type="smallint", options={"unsigned":true, "default":0})
     *
     * @var int
     */
    protected $visibleFrontdoor = 0;

    /**
     * @ORM\Column(name="display_frontdoor", type="string", length=512, nullable=true)
     *
     * @var string
     */
    private $displayFrontdoor;

    /**
     * @ORM\Column(name="visible_oai", type="smallint", options={"unsigned":true, "default":0})
     *
     * @var int
     */
    protected $visibleOai = 0;

    /**
     * @ORM\Column(name="is_classification", type="smallint", options={"unsigned":true, "default":0})
     *
     * @var int
     */
    protected $isClassification = 0;

    /**
     * @ORM\Column(name="assign_root", type="smallint", options={"unsigned":true, "default":0})
     *
     * @var int
     */
    protected $assignRoot = 0;

    /**
     * @ORM\Column(name="assign_leaves_only", type="smallint", options={"unsigned":true, "default":0})
     *
     * @var int
     */
    protected $assignLeavesOnly = 0;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     *
     * @var string
     */
    private $language;

    // TODO DOCTRINE Test that if this collection role gets stored, the associated root collection will also get stored
    /**
     * @ORM\OneToOne(targetEntity="Collection", mappedBy="role", cascade={"persist"})
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
     * @return string
     */
    public function getOaiName()
    {
        return $this->oaiName;
    }

    /**
     * @param string $oaiName
     * @return $this
     */
    public function setOaiName($oaiName)
    {
        $this->oaiName = $oaiName;

        return $this;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     * @return $this
     */
    public function setPosition($position)
    {
        $this->position = $position;

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
    public function getVisibleBrowsingStart()
    {
        return $this->visibleBrowsingStart;
    }

    /**
     * @param int $visibleBrowsingStart
     * @return $this
     */
    public function setVisibleBrowsingStart($visibleBrowsingStart)
    {
        $this->visibleBrowsingStart = $visibleBrowsingStart;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayBrowsing()
    {
        return $this->displayBrowsing;
    }

    /**
     * @param string $displayBrowsing
     * @return $this
     */
    public function setDisplayBrowsing($displayBrowsing)
    {
        $this->displayBrowsing = $displayBrowsing;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisibleFrontdoor()
    {
        return $this->visibleFrontdoor;
    }

    /**
     * @param int $visibleFrontdoor
     * @return $this
     */
    public function setVisibleFrontdoor($visibleFrontdoor)
    {
        $this->visibleFrontdoor = $visibleFrontdoor;

        return $this;
    }

    /**
     * @return string
     */
    public function getDisplayFrontdoor()
    {
        return $this->displayFrontdoor;
    }

    /**
     * @param string $displayFrontdoor
     * @return $this
     */
    public function setDisplayFrontdoor($displayFrontdoor)
    {
        $this->displayFrontdoor = $displayFrontdoor;

        return $this;
    }

    /**
     * @return int
     */
    public function getVisibleOai()
    {
        return $this->visibleOai;
    }

    /**
     * @param int $visibleOai
     * @return $this
     */
    public function setVisibleOai($visibleOai)
    {
        $this->visibleOai = $visibleOai;

        return $this;
    }

    /**
     * @return int
     */
    public function getIsClassification()
    {
        return $this->isClassification;
    }

    /**
     * @param int $isClassification
     * @return $this
     */
    public function setIsClassification($isClassification)
    {
        $this->isClassification = $isClassification;

        return $this;
    }

    /**
     * @return int
     */
    public function getAssignRoot()
    {
        return $this->assignRoot;
    }

    /**
     * @param int $assignRoot
     * @return $this
     */
    public function setAssignRoot($assignRoot)
    {
        $this->assignRoot = $assignRoot;

        return $this;
    }

    /**
     * @return int
     */
    public function getAssignLeavesOnly()
    {
        return $this->assignLeavesOnly;
    }

    /**
     * @param int $assignLeavesOnly
     * @return $this
     */
    public function setAssignLeavesOnly($assignLeavesOnly)
    {
        $this->assignLeavesOnly = $assignLeavesOnly;

        return $this;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

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
     * Adds the given Collection node (or a new Collection node if none is given) as the root collection of
     * this CollectionRole instance.
     *
     * @param Collection|null $root (Optional) The Collection node that shall be added as the root collection
     * of this instance.
     * @return Collection The root collection of this instance.
     */
    public function addRootCollection($root = null)
    {
        // TODO DOCTRINE: ensure that this function has the same effect as Opus\CollectionRole->addRootCollection()

        if ($root === null) {
            $root = new Collection();
        }

        // TODO DOCTRINE Do we also need to ensure that role is set correctly for an existing $root?
        $root->setRole($this);
        $this->setRootCollection($root);

        return $root;
    }

    /**
     * Returns the relevant properties of the class.
     *
     * @return array
     */
    protected static function describe()
    {
        return ['Name', 'OaiName', 'Position', 'Visible', 'VisibleBrowsingStart', 'DisplayBrowsing', 'VisibleFrontdoor',
            'DisplayFrontdoor', 'VisibleOai', 'IsClassification', 'AssignRoot', 'AssignLeavesOnly', 'Language',
            'RootCollection'];
    }
}
