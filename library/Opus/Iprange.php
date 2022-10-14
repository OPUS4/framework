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

use Opus\Common\IprangeInterface;
use Opus\Common\IprangeRepositoryInterface;
use Opus\Common\UserRole;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Validate_Hostname;
use Zend_Validate_NotEmpty;

use function ip2long;
use function long2ip;
use function sprintf;

/**
 * Domain model for iprange in the Opus framework
 *
 * phpcs:disable
 */
class Iprange extends AbstractDb implements IprangeInterface, IprangeRepositoryInterface
{
    /**
     * Specify then table gateway.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = Db\Ipranges::class;

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus\Db\Account table gateway.
     *
     * @see \Opus\Model\Abstract::$_externalFields
     *
     * @var array
     */
    protected $externalFields = [
        'Role' => [
            'model'   => \Opus\UserRole::class,
            'through' => Model\Dependent\Link\IprangeRole::class,
            'fetch'   => 'lazy',
        ],
    ];

    /**
     * Retrieve all Opus\Iprange instances from the database.
     *
     * @return array Array of Opus\Iprange objects.
     */
    public function getAll()
    {
        return self::getAllFrom(self::class, Db\Ipranges::class);
    }

    /**
     * Initialize model with the following fields:
     * - staringip
     * - endingip
     * - Name
     */
    protected function init()
    {
        $startingip = new Field('Startingip');
        $endingip   = new Field('Endingip');
        $name       = new Field('Name');
        $role       = new Field('Role');

        $startingip->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty())
                ->setValidator(new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_IP));
        $endingip->setMandatory(true)
                ->setValidator(new Zend_Validate_NotEmpty())
                ->setValidator(new Zend_Validate_Hostname(Zend_Validate_Hostname::ALLOW_IP));
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
        if ($this->fields['Startingip']->getValue() !== null) {
            $this->primaryTableRow->startingip = sprintf("%u", ip2long($this->fields['Startingip']->getValue()));
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
        if ($this->fields['Endingip']->getValue() !== null) {
            $this->primaryTableRow->endingip = sprintf("%u", ip2long($this->fields['Endingip']->getValue()));
        }
    }

    /**
     * Gets the starting ip address.
     *
     * @return string IPv4 address in Internet standard format (dotted string).
     */
    protected function _fetchStartingip()
    {
        if (empty($this->primaryTableRow->startingip) === false) {
            $result = long2ip($this->primaryTableRow->startingip);
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
        if (empty($this->primaryTableRow->endingip) === false) {
            $result = long2ip($this->primaryTableRow->endingip);
        } else {
            // FIXME: may conflict with \Zend_Validate_NotEmpty?
            $result = null;
        }
        return $result;
    }

    /**
     * Returns long name.
     */
    public function getDisplayName()
    {
        return $this->getName();
    }

    /**
     * @return string|null
     */
    public function getStartingIp()
    {
        return $this->__call('getStartingip', func_get_args());
    }

    /**
     * @param string $startingIp
     * @return $this
     */
    public function setStartingIp($startingIp)
    {
        return $this->__call('setStartingip', func_get_args());
    }

    /**
     * @return string|null
     */
    public function getEndingIp()
    {
        return $this->__call('getEndingip', func_get_args());
    }

    /**
     * @param string $endingIp
     * @return $this
     */
    public function setEndingIp($endingIp)
    {
        return $this->__call('setEndingip', func_get_args());
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName($name)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return UserRole[]
     */
    public function getRole()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param UserRole[] $role
     * @return $this
     */
    public function setRole($role)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
