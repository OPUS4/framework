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
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2017, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Opus\Model\AbstractDb;
use Opus\Model\Field;

/**
 * Domain model for iprange in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        \Opus\Model\Abstract
 */
class Iprange extends AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus\Db\Ipranges';

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus\Db\Account table gateway.
     *
     * @var array
     * @see \Opus\Model\Abstract::$_externalFields
     */
    protected $_externalFields = [
            'Role' => [
                'model' => 'Opus\UserRole',
                'through' => 'Opus\Model\Dependent\Link\IprangeRole',
                'fetch' => 'lazy'
            ],
    ];

    /**
     * Retrieve all Opus\Iprange instances from the database.
     *
     * @return array Array of Opus\Iprange objects.
     */
    public static function getAll()
    {
        return self::getAllFrom('Opus\Iprange', 'Opus\Db\Ipranges');
    }

    /**
     * Initialize model with the following fields:
     * - staringip
     * - endingip
     * - Name
     *
     * @return void
     */
    protected function _init()
    {
        $startingip = new Field('Startingip');
        $endingip = new Field('Endingip');
        $name = new Field('Name');
        $role = new Field('Role');

        $startingip->setMandatory(true)
                ->setValidator(new \Zend_Validate_NotEmpty())
                ->setValidator(new \Zend_Validate_Hostname(\Zend_Validate_Hostname::ALLOW_IP));
        $endingip->setMandatory(true)
                ->setValidator(new \Zend_Validate_NotEmpty())
                ->setValidator(new \Zend_Validate_Hostname(\Zend_Validate_Hostname::ALLOW_IP));
        $role->setMultiplicity('*');

        $this->addField($startingip)
                ->addField($endingip)
                ->addField($name)
                ->addField($role);
    }

    /**
     * Store the starting ip address.
     *
     * @return void.
     */
    protected function _storeStartingip()
    {
        // \Zend_Validate_NotEmpty ensures that this field can not be stored without value.
        if ($this->_fields['Startingip']->getValue() !== null) {
            $this->_primaryTableRow->startingip = sprintf("%u", ip2long($this->_fields['Startingip']->getValue()));
        }
    }

    /**
     * Store the ending ip address.
     *
     * @return void.
     */
    protected function _storeEndingip()
    {
        // \Zend_Validate_NotEmpty ensures that this field can not be stored without value.
        if ($this->_fields['Endingip']->getValue() !== null) {
            $this->_primaryTableRow->endingip = sprintf("%u", ip2long($this->_fields['Endingip']->getValue()));
        }
    }

    /**
     * Gets the starting ip address.
     *
     * @return string IPv4 address in Internet standard format (dotted string).
     */
    protected function _fetchStartingip()
    {
        if (empty($this->_primaryTableRow->startingip) === false) {
            $result = long2ip($this->_primaryTableRow->startingip);
        } else {
            // FIXME: may conflict with \Zend_Validate_NotEmpty?
            $result = null;
        }
        return $result;
    }

    /**
     * Gets the ending ip address.
     *
     * @return string IPv4 address in Internet standard format (dotted string).
     */
    protected function _fetchEndingip()
    {
        if (empty($this->_primaryTableRow->endingip) === false) {
            $result = long2ip($this->_primaryTableRow->endingip);
        } else {
            // FIXME: may conflict with \Zend_Validate_NotEmpty?
            $result = null;
        }
        return $result;
    }

    /**
     * Returns long name.
     *
     * @see \Opus\Model\Abstract#getDisplayName()
     */
    public function getDisplayName()
    {
        return $this->getName();
    }
}
