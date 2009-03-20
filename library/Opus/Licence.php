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
class Opus_Licence extends Opus_Model_AbstractDbSecure
{

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
        return self::getAllFrom('Opus_Licence', 'Opus_Db_DocumentLicences');
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
     * - NameLong
     * - PodAllowed
     * - SortOrder
     *
     * @return void
     */
    protected function _init() {
        $active = new Opus_Model_Field('Active');
        $active->setCheckbox(true);
        $comment_internal = new Opus_Model_Field('CommentInternal');
        $desc_markup = new Opus_Model_Field('DescMarkup');
        $desc_text = new Opus_Model_Field('DescText');
        $licence_language = new Opus_Model_Field('Language');
        $licence_language->setDefault(Zend_Registry::get('Available_Languages'))
            ->setSelection(true);
        $link_licence = new Opus_Model_Field('LinkLicence');
        $link_logo = new Opus_Model_Field('LinkLogo');
        $link_sign = new Opus_Model_Field('LinkSign');
        $mime_type = new Opus_Model_Field('MimeType');
        $name_long = new Opus_Model_Field('NameLong');
        $sort_order = new Opus_Model_Field('SortOrder');
        $pod_allowed = new Opus_Model_Field('PodAllowed');
        $pod_allowed->setCheckbox(true);

        $this->addField($active)
            ->addField($comment_internal)
            ->addField($desc_markup)
            ->addField($desc_text)
            ->addField($licence_language)
            ->addField($link_licence)
            ->addField($link_logo)
            ->addField($link_sign)
            ->addField($mime_type)
            ->addField($name_long)
            ->addField($sort_order)
            ->addField($pod_allowed);
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
