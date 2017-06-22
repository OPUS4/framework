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
 * @category    Framework
 * @package     Opus
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for licences in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Licence extends Opus_Model_AbstractDb {

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_DocumentLicences';

    /**
     * Retrieve all Opus_Licence instances from the database.
     *
     * @return array Array of Opus_Licence objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Licence', 'Opus_Db_DocumentLicences', null, 'sort_order');
    }

    /**
     * Fetch licence with matching name.
     * @return Opus_Licence
     */
    public static function fetchByName($name)
    {
        $licences = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $licences->select()->where('name = ?', $name);
        $row = $licences->fetchRow($select);

        if (isset($row))
        {
            return new Opus_Licence($row);
        }

        return null;
    }

    /**
     * Plugins to load
     *
     * @var array
     */
    protected $_plugins = array(
        'Opus_Model_Plugin_InvalidateDocumentCache' => null,
    );

    
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
     *
     * @return void
     */
    protected function _init() {
        $active = new Opus_Model_Field('Active');
        $active->setCheckbox(true);
        
        $commentInternal = new Opus_Model_Field('CommentInternal');
        $commentInternal->setTextarea(true);
        
        $descMarkup = new Opus_Model_Field('DescMarkup');
        $descMarkup->setTextarea(true);
        $descText = new Opus_Model_Field('DescText');
        $descText->setTextarea(true);
        
        $licenceLanguage = new Opus_Model_Field('Language');
        if (Zend_Registry::isRegistered('Available_Languages') === true) {
            $licenceLanguage->setDefault(Zend_Registry::get('Available_Languages'));
        }
        $licenceLanguage->setSelection(true);
        $licenceLanguage->setMandatory(true);

        $linkLicence = new Opus_Model_Field('LinkLicence');
        $linkLicence->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        
        $linkLogo = new Opus_Model_Field('LinkLogo');
        $linkSign = new Opus_Model_Field('LinkSign');
        $mimeType = new Opus_Model_Field('MimeType');

        $name = new Opus_Model_Field('Name');

        $nameLong = new Opus_Model_Field('NameLong');
        $nameLong->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        
        $sortOrder = new Opus_Model_Field('SortOrder');
        
        $podAllowed = new Opus_Model_Field('PodAllowed');
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
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getNameLong();
    }

}
