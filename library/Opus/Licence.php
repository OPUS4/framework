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
 */

namespace Opus;

use Opus\Common\Config;
use Opus\Common\LicenceInterface;
use Opus\Common\LicenceRepositoryInterface;
use Opus\Db\TableGateway;
use Opus\DocumentFinder\DocumentFinderException;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Validate_NotEmpty;

use function count;

/**
 * Domain model for licences in the Opus framework
 *
 * phpcs:disable
 */
class Licence extends AbstractDb implements LicenceInterface, LicenceRepositoryInterface
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\DocumentLicences::class;

    /**
     * Retrieve all Opus\Licence instances from the database.
     *
     * @return array Array of Opus\Licence objects.
     */
    public function getAll()
    {
        return self::getAllFrom(self::class, Db\DocumentLicences::class, null, 'sort_order');
    }

    /**
     * Fetch licence with matching name.
     *
     * @return Licence
     */
    public function fetchByName($name)
    {
        $licences = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $licences->select()->where('name = ?', $name);
        $row = $licences->fetchRow($select);

        if (isset($row)) {
            return new Licence($row);
        }

        return null;
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
     * Initialize model with the following fields:
     * - Active
     * - CommentInternal
     * - DescMarkup
     * - DescText
     * - Language
     * - LinkLicence
     * - LinkLogo
     * - LinkSign
     * - MimeType
     * - Name
     * - NameLong
     * - PodAllowed
     * - SortOrder
     */
    protected function init()
    {
        $active = new Field('Active');
        $active->setCheckbox(true);

        $commentInternal = new Field('CommentInternal');
        $commentInternal->setTextarea(true);

        $descMarkup = new Field('DescMarkup');
        $descMarkup->setTextarea(true);
        $descText = new Field('DescText');
        $descText->setTextarea(true);

        $licenceLanguage = new Field('Language');
        $availableLanguages = Config::getInstance()->getAvailableLanguages();
        if ($availableLanguages !== null) {
            $licenceLanguage->setDefault($availableLanguages);
        }
        $licenceLanguage->setSelection(true);
        $licenceLanguage->setMandatory(true);

        $linkLicence = new Field('LinkLicence');
        $linkLicence->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $linkLogo = new Field('LinkLogo');
        $linkSign = new Field('LinkSign');
        $mimeType = new Field('MimeType');

        $name = new Field('Name');

        $nameLong = new Field('NameLong');
        $nameLong->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $sortOrder = new Field('SortOrder');

        $podAllowed = new Field('PodAllowed');
        $podAllowed->setCheckbox(true);

        $this->addField($active)
            ->addField($commentInternal)
            ->addField($descMarkup)
            ->addField($descText)
            ->addField($licenceLanguage)
            ->addField($linkLicence)
            ->addField($linkLogo)
            ->addField($linkSign)
            ->addField($mimeType)
            ->addField($name)
            ->addField($nameLong)
            ->addField($sortOrder)
            ->addField($podAllowed);
    }

    /**
     * Returns long name.
     *
     * @see \Opus\Model\Abstract#getDisplayName()
     */
    public function getDisplayName()
    {
        return $this->getNameLong();
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

    public function getActive()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setActive($active)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getCommentInternal()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setCommentInternal($comment)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getLinkLicence()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setLinkLicence($link)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getLinkLogo()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setLinkLogo($link)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getLinkSign()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setLinkSign($link)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getMimeType()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setMimeType($mimeType)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getDescMarkup()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setDescMarkup($descriptionMarkup)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getDescText()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setDescText($description)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getLanguage()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setLanguage($lang)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getName()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setName($name)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getNameLong()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setNameLong($nameLong)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getSortOrder()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setSortOrder($position)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getPodAllowed()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setPodAllowed($allowed)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
