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
 *
 * @category    Framework
 * @package     Opus
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus;

use Opus\Common\Config;
use Opus\Db\TableGateway;
use Opus\DocumentFinder\DocumentFinderException;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Validate_NotEmpty;

use function count;

/**
 * Domain model for licences in the Opus framework
 *
 * @uses        \Opus\Model\Abstract
 *
 * @category    Framework
 * @package     Opus
 * @method void setActive(boolean $active)
 * @method boolean getActive()
 * @method void setCommentInternal(string $comment)
 * @method string getCommentInternal
 * @method void setDescMarkup(string $markup)
 * @method string getDescMarkup()
 * @method void setDescText(string $description)
 * @method string getDescText()
 * @method void setLanguage(string $lang)
 * @method string getLanguage()
 * @method void setLinkLicence(string $url)
 * @method string getLinkLicence()
 * @method void setLinkLogo(string $url)
 * @method string getLinkLogo()
 * @method void setLinkSign(string $url)
 * @method string getLinkSign()
 * @method void setMimeType(string $mimeType)
 * @method string getMimeType()
 * @method void setName(string $name)
 * @method string getName()
 * @method void setNameLong(string $longName)
 * @method string getNameLong()
 * @method void setSortOrder(integer $position)
 * @method integer getSortOrder()
 * @method void setPodAllowed(boolean $allowed)
 * @method boolean getPodAllowed()
 *
 * phpcs:disable
 */
class Licence extends AbstractDb
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
    public static function getAll()
    {
        return self::getAllFrom(self::class, Db\DocumentLicences::class, null, 'sort_order');
    }

    /**
     * Fetch licence with matching name.
     *
     * @return Licence
     */
    public static function fetchByName($name)
    {
        $licences = TableGateway::getInstance(self::$tableGatewayClass);
        $select   = $licences->select()->where('name = ?', $name);
        $row      = $licences->fetchRow($select);

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

        $licenceLanguage    = new Field('Language');
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
}
