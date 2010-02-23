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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for iprange in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Iprange extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Ipranges';

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Account table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     */
    protected $_externalFields = array(
            'Role' => array(
                'model' => 'Opus_Role',
                'through' => 'Opus_Model_Dependent_Link_IprangeRole',
                'fetch' => 'lazy'
            ),
    );

    /**
     * Retrieve all Opus_Iprange instances from the database.
     *
     * @return array Array of Opus_Iprange objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Iprange', 'Opus_Db_Ipranges');
    }

    /**
     * Initialize model with the following fields:
     * - Ip1byte1
     * - Ip1byte2
     * - Ip1byte3
     * - Ip1byte4
     * - Ip2byte1
     * - Ip2byte2
     * - Ip2byte3
     * - Ip2byte4
     * - Name
     *
     * @return void
     */
    protected function _init() {
        $ip1byte1 = new Opus_Model_Field('Ip1byte1');
        $ip1byte2 = new Opus_Model_Field('Ip1byte2');
        $ip1byte3 = new Opus_Model_Field('Ip1byte3');
        $ip1byte4 = new Opus_Model_Field('Ip1byte4');
        $ip2byte1 = new Opus_Model_Field('Ip2byte1');
        $ip2byte2 = new Opus_Model_Field('Ip2byte2');
        $ip2byte3 = new Opus_Model_Field('Ip2byte3');
        $ip2byte4 = new Opus_Model_Field('Ip2byte4');
        $name = new Opus_Model_Field('Name');
    	$role = new Opus_Model_Field('Role');
        
        $ip1byte1->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $ip1byte2->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $ip1byte3->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $ip1byte4->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $ip2byte1->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $ip2byte2->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $ip2byte3->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $ip2byte4->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
		$role->setMultiplicity('*')
			->setDefault(Opus_Role::getAll())
			->setSelection(true);
        
        $this->addField($ip1byte1)
            ->addField($ip1byte2)
            ->addField($ip1byte3)
            ->addField($ip1byte4)
            ->addField($ip2byte1)
            ->addField($ip2byte2)
            ->addField($ip2byte3)
            ->addField($ip2byte4)
            ->addField($name)
			->addField($role);
    }

    /**
     * Returns long name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getName();
    }

}
