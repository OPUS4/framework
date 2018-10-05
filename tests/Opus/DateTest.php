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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
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

    /**
     * This might not make sense, but is the old behaviour.
     */
    function testGetUnixTimestampForCustomDate()
    {
        $date = new Opus_Date('2012-10-17');

        $timestamp = $date->getUnixTimestamp();

        $this->assertEquals(
            $date->getDateTime()->format('Y-m-d H:i:s'),
            gmdate('Y-m-d H:i:s', $timestamp)
        );
    }

    function testUpdateFromArray()
    {
        $date = new Opus_Date();

        $date->updateFromArray([
            'Year' => 2018,
            'Month' => 5,
            'Day' => 11,
            'Hour' => 22,
            'Minute' => 35,
            'Second' => 11,
            'Timezone' => '+01:00'
        ]);

        $dateTime = $date->getDateTime();

        $this->assertEquals('2018-05-11 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('+01:00', $date->getTimezone());
    }

    function testUpdateFromArrayWithStrings()
    {
        $date = new Opus_Date();

        $date->updateFromArray([
            'Year' => '2018',
            'Month' => '5',
            'Day' => '11',
            'Hour' => '22',
            'Minute' => '35',
            'Second' => '11',
            'Timezone' => '+01:00'
        ]);

        $dateTime = $date->getDateTime();

        $this->assertEquals('2018-05-11 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('+01:00', $date->getTimezone());

    }

    function testUpdateFromArrayWithoutTime()
    {
        $date = new Opus_Date();

        $date->updateFromArray([
            'Year' => '2018',
            'Month' => '5',
            'Day' => '11'
        ]);

        $dateTime = $date->getDateTime();

        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s', '2018-05-11T00:00:00');

        $this->assertEquals('2018-05-11 00:00:00', date_format($dateTime, 'Y-m-d H:i:s'));
    }

    function testUpdateFromArrayResetsOtherFields()
    {
        $date = new Opus_Date();

        $date->updateFromArray([
            'Year' => 2018,
            'Month' => 5,
            'Day' => 11,
            'Hour' => 22,
            'Minute' => 35,
            'Second' => 11,
            'Timezone' => '+01:00'
        ]);

        $dateTime = $date->getDateTime();

        $this->assertEquals('2018-05-11 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('+01:00', $date->getTimezone());

        $date->updateFromArray([
            'Year' => 2012,
            'Month' => 12,
            'Day' => 1
        ]);

        $dateTime = $date->getDateTime();

        $this->assertNotEquals('2012-12-01 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('2012-12-01 00:00:00', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('', $date->getTimezone());
        $this->assertEquals(1354320000, $date->getUnixTimestamp());
    }

    function testUpdateFromArraySimple()
    {
        $date = new Opus_Date();

        $date->updateFromArray('2012-12-01');

        $dateTime = $date->getDateTime();

        $this->assertEquals('2012-12-01 00:00:00', date_format($dateTime, 'Y-m-d H:i:s'));
        // $this->assertEquals('', $date->getTimezone());
        $this->assertEquals(1354320000, $date->getUnixTimestamp());
    }

    function testUpdateFromArraySimpleLongForm()
    {
        $date = new Opus_Date();

        $date->updateFromArray('2010-06-04T22:36:53Z');

        $dateTime = $date->getDateTime();

        $this->assertEquals('2010-06-04 22:36:53', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('Z', $date->getTimezone());
        $this->assertEquals(1275691013, $date->getUnixTimestamp());
    }

    function testUpdateFromArraySimpleUnixTimestamp()
    {
        $date = new Opus_Date();

        $date->updateFromArray(1275691013);

        $this->assertEquals('2010-06-04T22:36:53Z', $date->__toString());
        $this->assertEquals('Z', $date->getTimezone());
        $this->assertEquals(1275691013, $date->getUnixTimestamp());
    }

    /**
     * UnixTimestamp is read-only and will not be set from array.
     */
    function testUpdateFromArrayWithUnixTimestamp() {
        $date = new Opus_Date();

        $date->updateFromArray([
            'Year' => '2018',
            'Month' => '05',
            'Day' => '07',
            'UnixTimestamp' => 1275691013
        ]);

        $this->assertTrue($date->isValid());
        $this->assertTrue($date->isDateOnly());
        $this->assertEquals('2018-05-07', $date->__toString());
        $this->assertNotEquals(1275691013, $date->getUnixTimestamp());
        $this->assertEquals(1525651200, $date->getUnixTimestamp());
    }

    function testUpdateUnixTimestamp()
    {
        $date = new Opus_Date('2018-10-14');

        $this->assertEquals('2018-10-14', date_format($date->getDateTime(), 'Y-m-d'));

        $timestamp = $date->getUnixTimestamp();

        $date->setYear(2017);

        $this->assertEquals('2017-10-14', date_format($date->getDateTime(), 'Y-m-d'));
        $this->assertNotEquals($timestamp, $date->getUnixTimestamp(), 'Field UnixTimestamp was not updated.');
        $this->assertEquals('1507939200', $date->getUnixTimestamp());
    }

    function testCompareSame()
    {
        $dateStr = '2018-10-14';

        $date = new Opus_Date($dateStr);

        $this->assertEquals(0, $date->compare($date));
        $this->assertEquals(0, $date->compare(new Opus_Date($dateStr)));
    }

    function testCompareSameWithTime()
    {
        $dateStr = '2018-10-14T15:31:12Z';
        $date = new Opus_Date($dateStr);

        $this->assertEquals(0, $date->compare($date));
        $this->assertEquals(0, $date->compare(new Opus_Date($dateStr)));
    }

    function testCompareSameWithTimezone()
    {
        $dateStr = '2018-10-14T15:31:12Z';
        $dateStr2 = '2018-10-14T17:31:12+02:00';

        // both timestamps describe the same universal time
        $this->assertEquals(strtotime($dateStr), strtotime($dateStr2));

        $date = new Opus_Date($dateStr);

        $this->assertEquals(0, $date->compare($date));
        $this->assertEquals(0, $date->compare(new Opus_Date($dateStr2)));
    }

    function testCompareLess()
    {
        $date = new Opus_Date('2018-10-14');
        $date2 = new Opus_Date('2018-10-15');

        $this->assertEquals(-1, $date->compare($date2));
    }

    function testCompareLessWithTime()
    {
        $date = new Opus_Date('2018-10-14T09:34:11Z');
        $date2 = new Opus_Date('2018-10-14T09:34:12Z');

        $this->assertEquals(-1, $date->compare($date2));
    }

    function testCompareLessWithTimezone()
    {
        $date = new Opus_Date('2018-10-14T10:34:11+02:00');
        $date2 = new Opus_Date('2018-10-14T09:34:11Z');

        $this->assertEquals(-1, $date->compare($date2));
    }

    function testCompareLarger()
    {
        $date = new Opus_Date('2018-10-14');
        $date2 = new Opus_Date('2018-10-15');

        $this->assertEquals(1, $date2->compare($date));
    }

    function testCompareLargerWithTime()
    {
        $date = new Opus_Date('2018-10-14T00:00:10Z');
        $date2 = new Opus_Date('2018-10-14T00:00:11Z');

        $this->assertEquals(1, $date2->compare($date));
    }

    function testCompareLargerWithTimezone()
    {
        $date = new Opus_Date('2018-10-14T00:00:10+02:00');
        $date2 = new Opus_Date('2018-10-13T23:00:11Z');

        $this->assertEquals(1, $date2->compare($date));
    }

    function testCreateWithTimezone()
    {
        $date = new Opus_Date('2018-10-14T00:00:10+02:00');

        $this->assertTrue($date->isValid());

        $this->assertEquals(2018, $date->getYear());
        $this->assertEquals(10, $date->getMonth());
        $this->assertEquals(14, $date->getDay());
        $this->assertEquals(0, $date->getHour());
        $this->assertEquals(0, $date->getMinute());
        $this->assertEquals(10, $date->getSecond());
        $this->assertEquals('+02:00', $date->getTimezone());
        $this->assertEquals( 1539468010, $date->getUnixTimestamp());
    }

    /**
     * Not supporting names for timezone probably makes sense in the long run. The meaning of "+02:00" will never
     * change, however the timezone for Europe/Berlin can depend on social, political and other influences.
     */
    function testCreateWithTimezoneNameNotSupported()
    {
        $date = new Opus_Date('2018-10-14T00:00:10Europe/Berlin');

        $this->assertFalse($date->isValid());
    }

    function testCompareWithNull() {
        $date = new Opus_Date('2018-10-14');

        $this->assertEquals(1, $date->compare(null));
    }

    /**
     * @expectedException Opus_Model_Exception
     * @expectedExceptionMessage Cannot compare Zend_Config with Opus_Date object.
     */
    function testCompareWithOtherObjectType() {
        $date = new Opus_Date('2018-10-14');

        $date->compare(new Zend_Config([]));
    }

    function testToArrayWithTimestamp()
    {
        $date = new Opus_Date();

        $date->setTimestamp(1534284000); // interpret als UTC (Z)

        $this->assertEquals([
            'Year' => '2018',
            'Month' => '08',
            'Day' => '14',
            'Hour' => '22',
            'Minute' => '00',
            'Second' => '00',
            'Timezone' => 'Z',
            'UnixTimestamp' => 1534284000
        ], $date->toArray());
    }

    function testToArray()
    {
        $date = new Opus_Date();

        $date->setYear('2018');
        $date->setMonth('08');
        $date->setDay('15');

        $this->assertEquals([
            'Year' => '2018',
            'Month' => '08',
            'Day' => '15',
            'Hour' => null,
            'Minute' => null,
            'Second' => null,
            'Timezone' => null,
            'UnixTimestamp' => 1534291200
        ], $date->toArray());
    }

    function testGetDateTimeForEmptyDate()
    {
        $date = new Opus_Date();

        $this->assertFalse($date->isValid());
        $this->assertNull($date->getDateTime());
    }

    function testSetUnixTimestampWithLocalTimestamp()
    {
        $timestamp = strtotime('2018-10-15');

        $date = new Opus_Date();

        $date->setTimestamp($timestamp);

        $this->assertEquals([
            'Year' => '2018',
            'Month' => '10',
            'Day' => '14',
            'Hour' => '22',
            'Minute' => '00',
            'Second' => '00',
            'Timezone' => 'Z',
            'UnixTimestamp' => 1539554400
        ], $date->toArray());
    }

    public function testCompareFullWithDateOnly()
    {
        $date = new Opus_Date('2018-10-20T00:00:00Z');
        $time = new Opus_Date('2018-10-19T23:59:59Z');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Opus_Date('2018-10-20');
        $time = new Opus_Date('2018-10-19T23:59:59Z');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Opus_Date('2018-10-20');
        $time = new Opus_Date('2018-10-19T23:59:59Z');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));
    }

    public function testCompareFullWithDateOnlyWithDifferentTimezone()
    {
        $date = new Opus_Date('2018-10-20T00:00:00+02:00');
        $time = new Opus_Date('2018-10-19T23:59:59+02:00');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Opus_Date('2018-10-20');
        $time = new Opus_Date('2018-10-20T01:59:59+02:00');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Opus_Date('2018-10-20');
        $time = new Opus_Date('2018-10-20T01:59:59+02:00');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Opus_Date('2018-10-20');
        $time = new Opus_Date('2018-10-20T02:00:00+02:00');

        $this->assertEquals(0, $date->compare($time));
    }

    public function testGetDateTimeDateOnlyWithTimezone()
    {
        $date = new Opus_Date('2018-10-20');

        $dateTime = $date->getDateTime();

        $this->assertNotNull($dateTime);
        $this->assertEquals(new DateTimeZone(date_default_timezone_get()), $dateTime->getTimezone());
        $this->assertEquals(1539986400,$dateTime->getTimestamp());

        $dateTimeUtc = $date->getDateTime('Z');

        $this->assertNotNull($dateTimeUtc);
        $this->assertEquals(new DateTimeZone('Z'), $dateTimeUtc->getTimezone());

        $this->assertNotEquals($dateTime->getTimestamp(), $dateTimeUtc->getTimestamp());
        $this->assertEquals(1539993600,$dateTimeUtc->getTimestamp());
    }

    public function testGetDateTimeWithTimezone()
    {
        $date = new Opus_Date('2018-10-20T00:00:00+02:00');

        $dateTime = $date->getDateTime();

        $this->assertNotNull($dateTime);
        $this->assertEquals(new DateTimeZone('+02:00'), $dateTime->getTimezone());
        $this->assertEquals(1539986400,$dateTime->getTimestamp());

        // if Opus_Date was created with a time zone -> changing it should not change the timestamp
        $dateTimeUtc = $date->getDateTime('Z');

        $this->assertNotNull($dateTimeUtc);
        $this->assertEquals(new DateTimeZone('Z'), $dateTimeUtc->getTimezone());

        $this->assertEquals($dateTime->getTimestamp(), $dateTimeUtc->getTimestamp());
        $this->assertEquals(1539986400,$dateTimeUtc->getTimestamp());
    }

    public function testGetTimestampUsesLocalTimezone()
    {
        $date1 = new Opus_Date('2018-10-15');
        $date2 = new Opus_Date('2018-10-14T22:00:00Z');
        $date3 = new Opus_Date('2018-10-15T00:00:00+02:00');

        $this->assertEquals('2018-10-15', $date1->__toString());
        $this->assertEquals('2018-10-14T22:00:00Z', $date2->__toString());
        $this->assertEquals('2018-10-15T00:00:00+02:00', $date3->__toString());

        // the timestamps are all the same because the local time zone is used when nothing is specified
        $this->assertEquals(1539561600, $date1->getTimestamp());
        $this->assertEquals(1539554400, $date2->getTimestamp());
        $this->assertEquals(1539554400, $date3->getTimestamp());

        // for comparing UTC (Z) is used
        $this->assertEquals(1, $date1->compare($date2));
        $this->assertEquals( 0, $date2->compare($date3));
        $this->assertEquals( -1, $date3->compare($date1));
    }

    public function testGetNow()
    {
        $now = Opus_Date::getNow();

        $dateTime = new DateTime();

        // don't compare seconds because timestamps will differ slightly
        $expected = $dateTime->format('Y-m-d\TH:i');

        $this->assertStringStartsWith($expected, $now->__toString());
    }
}
