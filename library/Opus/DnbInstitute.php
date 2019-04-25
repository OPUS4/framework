<?php
/*
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
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for DnbInstitute in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 *
 * @method void setName(string $name)
 * @method string getName()
 *
 * @method void setDepartment(string $department)
 * @method string getDepartment()
 *
 * @method void setAddress(string $address)
 * @method string getAddress()
 *
 * @method void setCity(string $city)
 * @method string getCity()
 *
 * @method void setPhone(string $phone)
 * @method string getPhone()
 *
 * @method void setDnbContactId(string $contactId)
 * @method string getDnbContactId()
 *
 * @method void setIsGrantor(boolean $isGrantor)
 * @method boolean getIsGrantor()
 *
 * @method void setIsPublisher(boolean $isPublisher)
 * @method boolean getIsPublisher()
 */
class Opus_DnbInstitute extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_DnbInstitutes';

    /**
     * Retrieve all Opus_DnbInstitute instances from the database.
     *
     * @return array Array of Opus_DnbInstitute objects.
     */
    public static function getAll()
    {
        return self::getAllFrom('Opus_DnbInstitute', 'Opus_Db_DnbInstitutes');
    }

    /**
     * Returns a list of organisational units that act as (thesis) grantors.
     *
     * @return array A list of Opus_DnbInstitutes that act as grantors.
     */
    public static function getGrantors()
    {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_DnbInstitutes');
        $select = $table->select()
                ->where('is_grantor = ?', 1);

        $rows = $table->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new Opus_DnbInstitute($row);
        }
        return $result;
    }

    /**
     * Returns a list of organisational units that act as (thesis) publishers.
     *
     * @return array A list of Opus_DnbInstitutes that act as publishers.
     */
    public static function getPublishers()
    {
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_DnbInstitutes');
        $select = $table->select()
                ->where('is_publisher = ?', 1);

        $rows = $table->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new Opus_DnbInstitute($row);
        }
        return $result;
    }

    /**
     * Plugins to load
     *
     * @var array
     */
    public function getDefaultPlugins()
    {
        return [
            'Opus_Model_Plugin_InvalidateDocumentCache'
        ];
    }

    /**
     * Initialize model with the following fields:
     * - name
     * - address
     * - city
     * - phone
     * - dnbContactId
     * - is_grantor
     *
     * @return void
     */
    protected function _init()
    {
        $name = new Opus_Model_Field('Name');
        $name->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $department = new Opus_Model_Field('Department');

        $address = new Opus_Model_Field('Address');

        $city = new Opus_Model_Field('City');
        $city->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $phone = new Opus_Model_Field('Phone');

        $dnbContactId = new Opus_Model_Field('DnbContactId');

        $isGrantor = new Opus_Model_Field('IsGrantor');
        $isGrantor->setCheckbox(true);

        $isPublisher = new Opus_Model_Field('IsPublisher');
        $isPublisher->setCheckbox(true);

        $this->addField($name)
            ->addField($department)
            ->addField($address)
            ->addField($city)
            ->addField($phone)
            ->addField($dnbContactId)
            ->addField($isGrantor)
            ->addField($isPublisher);
    }

    /**
     * Returns name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName()
    {
       $departmentName = $this->getDepartment();
       return $this->getName().(empty($departmentName) ? '' : ', '.$departmentName);
    }

    /**
     * Checks if DNB institute is used by any document.
     */
    public function isUsed()
    {
        $table = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $database = $table->getAdapter();

        $select = $database->select()
            ->from('link_documents_dnb_institutes')
            ->where('dnb_institute_id = ?', $this->getId());

        $rows = $database->fetchOne($select);

        return $rows !== false;
    }
}
