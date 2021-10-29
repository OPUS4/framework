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

namespace Opus;

use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Validate_NotEmpty;

/**
 * Domain model for DnbInstitute in the Opus framework
 *
 * @uses        \Opus\Model\Abstract
 *
 * @category    Framework
 * @package     Opus
 * @method void setName(string $name)
 * @method string getName()
 * @method void setDepartment(string $department)
 * @method string getDepartment()
 * @method void setAddress(string $address)
 * @method string getAddress()
 * @method void setCity(string $city)
 * @method string getCity()
 * @method void setPhone(string $phone)
 * @method string getPhone()
 * @method void setDnbContactId(string $contactId)
 * @method string getDnbContactId()
 * @method void setIsGrantor(boolean $isGrantor)
 * @method boolean getIsGrantor()
 * @method void setIsPublisher(boolean $isPublisher)
 * @method boolean getIsPublisher()
 *
 * phpcs:disable
 */
class DnbInstitute extends AbstractDb
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\DnbInstitutes::class;

    /**
     * Retrieve all Opus\DnbInstitute instances from the database.
     *
     * @return array Array of Opus\DnbInstitute objects.
     */
    public static function getAll()
    {
        return self::getAllFrom(self::class, Db\DnbInstitutes::class);
    }

    /**
     * Returns a list of organisational units that act as (thesis) grantors.
     *
     * @return array A list of Opus\DnbInstitutes that act as grantors.
     */
    public static function getGrantors()
    {
        $table  = TableGateway::getInstance(Db\DnbInstitutes::class);
        $select = $table->select()
                ->where('is_grantor = ?', 1);

        $rows   = $table->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new DnbInstitute($row);
        }
        return $result;
    }

    /**
     * Returns a list of organisational units that act as (thesis) publishers.
     *
     * @return array A list of Opus\DnbInstitutes that act as publishers.
     */
    public static function getPublishers()
    {
        $table  = TableGateway::getInstance(Db\DnbInstitutes::class);
        $select = $table->select()
                ->where('is_publisher = ?', 1);

        $rows   = $table->fetchAll($select);
        $result = [];
        foreach ($rows as $row) {
            $result[] = new DnbInstitute($row);
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
            Model\Plugin\InvalidateDocumentCache::class,
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
     */
    protected function init()
    {
        $name = new Field('Name');
        $name->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $department = new Field('Department');

        $address = new Field('Address');

        $city = new Field('City');
        $city->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty());

        $phone = new Field('Phone');

        $dnbContactId = new Field('DnbContactId');

        $isGrantor = new Field('IsGrantor');
        $isGrantor->setCheckbox(true);

        $isPublisher = new Field('IsPublisher');
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
     * @see \Opus\Model\Abstract#getDisplayName()
     */
    public function getDisplayName()
    {
        $departmentName = $this->getDepartment();
        return $this->getName() . (empty($departmentName) ? '' : ', ' . $departmentName);
    }

    /**
     * Checks if DNB institute is used by any document.
     */
    public function isUsed()
    {
        $table    = TableGateway::getInstance(self::$tableGatewayClass);
        $database = $table->getAdapter();

        $select = $database->select()
            ->from('link_documents_dnb_institutes')
            ->where('dnb_institute_id = ?', $this->getId());

        $rows = $database->fetchOne($select);

        return $rows !== false;
    }
}
