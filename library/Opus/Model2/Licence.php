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
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model2;

use Doctrine\ORM\Mapping as ORM;
use Opus\DocumentFinder;
use Opus\DocumentFinder\DocumentFinderException;

use function count;

/**
 * Domain model for licences in the Opus framework
 *
 * @uses        \Opus\Model2\AbstractModel
 *
 * TODO validation - Language, LinkLicence and NameLong are mandatory
 *
 * @ORM\Entity(repositoryClass="Opus\Db2\LicenceRepository")
 * @ORM\Table(name="document_licences",
 *     indexes={
 *         @ORM\Index(name="name", columns={"name"}),
 *         @ORM\Index(name="name_2", columns={"name"})
 *     })
 *
 * @category    Framework
 * @package     Opus
 */
class Licence extends AbstractModel
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * TODO is it possible to force an ID, not a generated value, for instance for importing the master data?
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\Column(name="active", type="boolean")
     *
     * @var bool
     */
    private $active = true;

    /**
     * @ORM\Column(name="comment_internal", type="text", length=16777215, nullable="true")
     *
     * @var string
     */
    private $commentInternal;

    /**
     * @ORM\Column(name="desc_markup", type="text", length=16777215, nullable="true")
     *
     * @var string
     */
    private $descMarkup;

    /**
     * @ORM\Column(name="desc_text", type="text", length=16777215, nullable="true")
     *
     * @var string
     */
    private $descText;

    /**
     * @ORM\Column(name="language", type="string", length=3, options={"fixed" = true}, nullable="true")
     *
     * @var string
     */
    private $language;

    /**
     * @ORM\Column(name="link_licence", type="text", length=16777215)
     *
     * @var string
     */
    private $linkLicence;

    /**
     * @ORM\Column(name="link_logo", type="text", length=16777215, nullable="true")
     *
     * @var string
     */
    private $linkLogo;

    /**
     * @ORM\Column(name="link_sign", type="text", length=16777215, nullable="true")
     *
     * @var string
     */
    private $linkSign;

    /**
     * @ORM\Column(name="mime_type", type="string", length=30, nullable="true")
     *
     * @var string
     */
    private $mimeType;

    /**
     * @ORM\Column(name="name", type="string", length=191, nullable="true")
     *
     * @var string
     */
    private $name;

    /**
     * @ORM\Column(name="name_long", type="string", length=255)
     *
     * @var string
     */
    private $nameLong;

    /**
     * @ORM\Column(name="pod_allowed", type="boolean")
     *
     * @var bool
     */
    private $podAllowed = false;

    /**
     * @ORM\Column(name="sort_order", type="smallint")
     *
     * @var int
     */
    private $sortOrder = 0;

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
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     * @return $this
     */
    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return string
     */
    public function getCommentInternal()
    {
        return $this->commentInternal;
    }

    /**
     * @param string $commentInternal
     * @return $this
     */
    public function setCommentInternal($commentInternal)
    {
        $this->commentInternal = $commentInternal;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescMarkup()
    {
        return $this->descMarkup;
    }

    /**
     * @param string $descMarkup
     * @return $this
     */
    public function setDescMarkup($descMarkup)
    {
        $this->descMarkup = $descMarkup;
        return $this;
    }

    /**
     * @return string
     */
    public function getDescText()
    {
        return $this->descText;
    }

    /**
     * @param string $descText
     * @return $this
     */
    public function setDescText($descText)
    {
        $this->descText = $descText;
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
     * @return string
     */
    public function getLinkLicence()
    {
        return $this->linkLicence;
    }

    /**
     * @param string $linkLicence
     * @return $this
     */
    public function setLinkLicence($linkLicence)
    {
        $this->linkLicence = $linkLicence;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkLogo()
    {
        return $this->linkLogo;
    }

    /**
     * @param string $linkLogo
     * @return $this
     */
    public function setLinkLogo($linkLogo)
    {
        $this->linkLogo = $linkLogo;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkSign()
    {
        return $this->linkSign;
    }

    /**
     * @param string $linkSign
     * @return $this
     */
    public function setLinkSign($linkSign)
    {
        $this->linkSign = $linkSign;
        return $this;
    }

    /**
     * @return string
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param string $mimeType
     * @return $this
     */
    public function setMimeType($mimeType)
    {
        $this->mimeType = $mimeType;
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
    public function getNameLong()
    {
        return $this->nameLong;
    }

    /**
     * @param string $nameLong
     * @return $this
     */
    public function setNameLong($nameLong)
    {
        $this->nameLong = $nameLong;
        return $this;
    }

    /**
     * @return bool
     */
    public function getPodAllowed()
    {
        return $this->podAllowed;
    }

    /**
     * @param bool $podAllowed
     * @return $this
     */
    public function setPodAllowed($podAllowed)
    {
        $this->podAllowed = $podAllowed;
        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * Returns long name.
     *
     * @return string
     */
    public function getDisplayName()
    {
        return $this->getNameLong();
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    protected static function describe()
    {
        return [
            'Active',
            'CommentInternal',
            'DescMarkup',
            'DescText',
            'Language',
            'LinkLicence',
            'LinkLogo',
            'LinkSign',
            'MimeType',
            'Name',
            'NameLong',
            'PodAllowed',
            'SortOrder',
        ];
    }

    /**
     * Retrieve all Opus\Model2\Licence instances from the database.
     *
     * @return array Array of Opus\Model2\Licence objects.
     */
    public static function getAll()
    {
        return self::getRepository()->getAll();
    }

    /**
     * Fetch licence with matching name.
     *
     * @param string $name Short name of licence
     * @return self|null
     */
    public static function fetchByName($name)
    {
        return self::getRepository()->fetchByName($name);
    }

    /**
     * Checks if licence is used by documents.
     *
     * @return bool true if licence is used, false if not
     */
    public function isUsed()
    {
        return $this->getDocumentCount() > 0;
    }

    /**
     * Determines number of documents using this licence.
     *
     * @return int Number of documents
     * @throws DocumentFinderException
     */
    public function getDocumentCount()
    {
        $finder = new DocumentFinder();
        $finder->setDependentModel($this);
        return count($finder->ids());
    }
}
