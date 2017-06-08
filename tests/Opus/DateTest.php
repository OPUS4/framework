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
 * @version     $Id$
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
    public function testCreateWithoutArgument() {
        $od = new Opus_Date;
        $this->assertFalse($od->isValid(), 'Opus_Date object should not be valid!');
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
        $this->assertTrue($od->isValid(), 'Date should be valid!');

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
        $od = new Opus_Date('1972-11-10');
        $this->assertEquals(1972, (int) $od->getYear(), 'Year values dont match.');
        $this->assertEquals(11, (int) $od->getMonth(), 'Month values dont match.');
        $this->assertEquals(10, (int) $od->getDay(), 'Day values dont match.');
        $this->assertTrue($od->isValid(), 'Opus_Date should be valid!');
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
        $this->assertTrue($od->isValid(), 'Opus_Date should be valid!');
    }

    /**
     * Test creation by passing Opus_Date as constructor argument.
     *
     * @return void
     */
    public function testCreateWithOpusDateConstructionArgument() {
        $now = new Opus_Date;
        $now->setNow();
        $od = new Opus_Date($now);
        $this->assertEquals($od->getYear(), $now->getYear(), 'Year values dont match.');
        $this->assertEquals($od->getMonth(), $now->getMonth(), 'Month values dont match.');
        $this->assertEquals($od->getDay(), $now->getDay(), 'Day values dont match.');
        $this->assertTrue($od->isValid(), 'Opus_Date should be valid!');
    }

    /**
     * Test creation by passing DateTime as constructor argument.
     *
     * @return void
     */
    public function testCreateWithDateTimeConstructionArgument() {
        $now = new DateTime;
        $od = new Opus_Date($now);
        $this->assertEquals($od->getYear(), $now->format('Y'), 'Year values dont match.');
        $this->assertEquals($od->getMonth(), $now->format('m'), 'Month values dont match.');
        $this->assertEquals($od->getDay(), $now->format('d'), 'Day values dont match.');
        $this->assertTrue($od->isValid(), 'Opus_Date should be valid!');
    }

    /**
     * Test creation by passing modified DateTime as constructor argument
     *  (10 minutes in the past).
     *
     * @return void
     */
    public function testCreateWithModifiedDateTimeConstructionArgument() {
        $past = new DateTime;
        $past->sub(new DateInterval('PT10M'));
        $od = new Opus_Date($past);

        $this->assertEquals($od->getYear(), $past->format('Y'), 'Year values dont match.');
        $this->assertEquals($od->getMonth(), $past->format('m'), 'Month values dont match.');
        $this->assertEquals($od->getDay(), $past->format('d'), 'Day values dont match.');

        $this->assertEquals($od->getHour(), $past->format('H'), 'Hour values dont match.');
        $this->assertEquals($od->getMinute(), $past->format('i'), 'Minute values dont match.');
        $this->assertEquals($od->getSecond(), $past->format('s'), 'Second values dont match.');

        $this->assertEquals($od->getUnixTimestamp(), $past->getTimestamp(), 'Unix timestamp does not match');
        $this->assertTrue($od->isValid(), 'Opus_Date should be valid!');
    }

    /**
     * Test creation by passing an *invalid* string constructor argument.
     *
     * @return void
     */
    public function testCreateWithTooLongYearStringConstructionArgumentShouldBeInvalid() {
        $od = new Opus_Date("1234567-12-12T11:11:11Z");
        $this->assertFalse($od->isValid(), 'Opus_Date object should be INVALID!');
    }

    /**
     * Test creation by passing an *invalid* string constructor argument.
     *
     * @return void
     */
    public function testCreateWithShortYearStringConstructionArgumentShouldBeValid() {
        $od = new Opus_Date("10-12-12T11:11:11Z");
        $this->assertTrue($od->isValid(), 'Opus_Date object should be valid!');
    }

    /**
     * Test if Opus_Date swaps month/year when locale == en
     *
     * @return void
     */
    function testIfParsingOfIsoDateSwapsDayAndMonth() {
        $locale = new Zend_Locale("en");
        Zend_Registry::set('Zend_Locale', $locale);
        $date = new Opus_Date('2010-06-04T02:36:53Z');

        $this->assertEquals(4, $date->getDay());
        $this->assertEquals(6, $date->getMonth());
    }

    /**
     * Test if setNow really sets now.
     *
     * @return void
     */
    function testSetNow() {
        $date = new Opus_Date();
        $date->setNow();

        $this->assertEquals(date('Y'), $date->getYear());
        $this->assertEquals(date('m'), $date->getMonth());
        $this->assertEquals(date('d'), $date->getDay());
        $this->assertTrue($date->isValid(), 'Opus_Date should be valid after setNow!');
    }

    /**
     * Test if setNow really sets now.
     *
     * @return void
     */
    function testSetNowToStringIsValid() {
        $date = new Opus_Date();
        $date->setNow();

        $this->assertEquals(date('Y'), $date->getYear());
        $this->assertEquals(date('m'), $date->getMonth());
        $this->assertEquals(date('d'), $date->getDay());

        $dateString = $date->__toString();
        $dateReload = new Opus_Date($dateString);
        $this->assertEquals($date->getYear(), $dateReload->getYear());
        $this->assertEquals($date->getMonth(), $dateReload->getMonth());
        $this->assertEquals($date->getDay(), $dateReload->getDay());

    }

    /**
     * Test if converting from-to string is invariant.
     *
     * @return void
     */
    function testFromStringToStringIsInvariant() {
        $date = new Opus_Date();
        $date->setFromString('2010-06-04T22:36:53Z');

        $this->assertEquals(2010, $date->getYear());
        $this->assertEquals(06, $date->getMonth());
        $this->assertEquals(04, $date->getDay());

        $this->assertEquals(22, $date->getHour());
        $this->assertEquals(36, $date->getMinute());
        $this->assertEquals(53, $date->getSecond());

        $this->assertEquals('2010-06-04T22:36:53Z', "$date");
    }

    /**
     * Test if converting from-to string is invariant.
     *
     * @return void
     */
    function testFromDateOnlyStringToStringIsInvariant() {
        $date = new Opus_Date();
        $date->setFromString('2010-06-04');

        $this->assertEquals(2010, $date->getYear());
        $this->assertEquals(06, $date->getMonth());
        $this->assertEquals(04, $date->getDay());

        $this->assertEquals('2010-06-04', "$date");
    }

    /**
     * Test if converting from-to string is invariant.
     *
     * @return void
     */
    function testFromStringToStringKeepsTimeZone() {
        $date = new Opus_Date();
        $date->setFromString('2010-06-04T22:36:53+2:3');

        $this->assertEquals(2010, $date->getYear());
        $this->assertEquals(06, $date->getMonth());
        $this->assertEquals(04, $date->getDay());

        $this->assertEquals(22, $date->getHour());
        $this->assertEquals(36, $date->getMinute());
        $this->assertEquals(53, $date->getSecond());

        $this->assertEquals('+02:03', $date->getTimezone());
    }

    /**
     * Test padding of integers in string output.
     *
     * @return void
     */
    public function testStringOutputPadding() {
        $date = new Opus_Date();

        $date->setYear(2013);
        $date->setMonth(7);
        $date->setDay(9);

        $date->setHour(1);
        $date->setMinute(2);
        $date->setSecond(3);
        $date->setTimezone('Z');

        $this->assertEquals('2013-07-09T01:02:03Z', "$date");
    }

    /**
     * Test if setFromString() handles broken dates correctly.
     *
     * @return void
     */
    function testSetFromStringErrorHandling() {

        $invalidStrings = array(
            '',
            null,
            '2010',
            '2011-12-bla',
            '01.01.2010',
            '2011-12-12T23:59:59',
            '2011-12-12X99:99:99Z',
        );
        foreach ($invalidStrings AS $invalidString) {
            try {
                $date = new Opus_Date();
                $date->setFromString($invalidString);
                $this->fail("Missing expected InvalidArgumentException for invalid string '{$invalidString}'.");
            }
            catch (InvalidArgumentException $e) {
                // OK.
            }
        }

    }

    /**
     * @return void
     */
    function testSetTimezone() {
        $timeZoneStrings = array(
            '2011-12-12'                => null,
            '2011-12-12T23:59:59Z'      => 'Z',
            '2011-12-12T23:59:59UTC'    => 'Z',
            '2011-12-12T23:59:59+0'     => 'Z',
            '2011-12-12T23:59:59+00'    => 'Z',
            '2011-12-12T23:59:59+0000'  => 'Z',
            '2011-12-12T23:59:59+00:00' => 'Z',
            '2011-12-12T23:59:59EST'    => '-05:00',
        );
        foreach ($timeZoneStrings AS $timeString => $timeZone) {
            $date = new Opus_Date($timeString);
            $this->assertEquals($timeZone, $date->getTimezone());
        }
    }

    /**
     * TODO Test may fail because to much time passed between setNow and Zend_Date construction.
     */
    function testZendDateOutput() {
        $date = new Opus_Date();
        $date->setNow();
        $dateZend = new Opus_Date(new Zend_Date());

        $this->assertEquals($date->__toString(), $dateZend->__toString());
    }

    function testGetUnixTimestamp()
    {
        $date = new Opus_Date();
        $date->setNow();

        $timestamp = $date->getUnixTimestamp();

        $this->assertEquals(
            $date->getDateTime()->format('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', $timestamp)
        );
    }

    function testGetUnixTimestampForCustomDate()
    {
        $date = new Opus_Date('2012-10-17');

        $timestamp = $date->getUnixTimestamp();

        $this->assertEquals(
            $date->getDateTime()->format('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', $timestamp)
        );
    }

}
