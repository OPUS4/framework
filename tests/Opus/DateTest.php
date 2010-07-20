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
 * @category    Tests
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: DateTest.php 4921 2009-12-21 14:03:11Z claussni $
 */

/**
 * Test cases for class Opus_Date.
 *
 * @package Opus
 * @category Tests
 *
 * @group DateTest
 */
class Opus_DateTest extends TestCase {

    protected $_locale_backup;

    /**
     * Prepare german locale setup.
     *
     */
    public function setUp() {
        $this->_locale_backup = Zend_Registry::get('Zend_Locale');
        Zend_Registry::set('Zend_Locale', new Zend_Locale('de'));
    }
    
    /**
     * Restore previously set locale
     *
     */
    public function tearDown() {
        Zend_Registry::set('Zend_Locale', $this->_locale_backup);
    }

    /**
     * Test creation of a Opus_Date model.
     *
     * @return void
     */
    public function testCreate() {
        $od = new Opus_Date;
    }   
 
    /**
     * Test if a valid Zend_Date object can be created.
     *
     * @return void
     */   
    public function testGetZendDate() {
        $od = new Opus_Date;
        $od->setYear(2005)
            ->setMonth(10)
            ->setDay(24);
        $zd = $od->getZendDate();
        
        $this->assertNotNull($zd, 'Object expected.');
        $this->assertTrue($zd instanceof Zend_Date, 'Returned object is not Zend_Date.');
    }
    
    /**
     * Test creation by passing string as constructor argument.
     *
     * @return void
     */
    public function testCreateWithStringConstructionArgument() {
        $od = new Opus_Date('10.11.1972');
        $this->assertEquals(1972, (int) $od->getYear(), 'Year values dont match.');        
        $this->assertEquals(11, (int) $od->getMonth(), 'Month values dont match.');        
        $this->assertEquals(10, (int) $od->getDay(), 'Day values dont match.');        
    }

    /**
     * Test creation by passing Zend_Date as constructor argument.
     *
     * @return void
     */
    public function testCreateWithZendDateConstructionArgument() {
        $now = new Zend_Date;
        $od = new Opus_Date($now);
        $this->assertEquals($od->getYear(), $now->get(Zend_Date::YEAR), 'Year values dont match.');        
        $this->assertEquals($od->getMonth(), $now->get(Zend_Date::MONTH), 'Month values dont match.');        
        $this->assertEquals($od->getDay(), $now->get(Zend_Date::DAY), 'Day values dont match.');        
    }

}
