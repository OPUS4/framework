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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest;

use DateInterval;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Opus\Common\Model\ModelException;
use Opus\Date;
use Opus\Document;
use OpusTest\TestAsset\TestCase;
use Zend_Config;
use Zend_Locale;

use function date;
use function date_default_timezone_get;
use function date_format;
use function gmdate;
use function strtotime;

/**
 * Test cases for class Opus\Date.
 *
 * @package Opus
 * @category Tests
 * @group DateTest
 */
class DateTest extends TestCase
{
    protected $localeBackup;

    /**
     * Prepare german locale setup.
     */
    public function setUp()
    {
        parent::setUp();
        Zend_Locale::setDefault('de');
    }

    /**
     * Test creation of a Opus\Date model.
     */
    public function testCreateWithoutArgument()
    {
        $od = new Date();
        $this->assertFalse($od->isValid(), 'Opus\Date object should not be valid!');
    }

    /**
     * Test creation by passing string as constructor argument.
     */
    public function testCreateWithStringConstructionArgument()
    {
        $od = new Date('1972-11-10');
        $this->assertEquals(1972, (int) $od->getYear(), 'Year values dont match.');
        $this->assertEquals(11, (int) $od->getMonth(), 'Month values dont match.');
        $this->assertEquals(10, (int) $od->getDay(), 'Day values dont match.');
        $this->assertTrue($od->isValid(), 'Opus\Date should be valid!');
    }

    /**
     * Test creation by passing Opus\Date as constructor argument.
     */
    public function testCreateWithOpusDateConstructionArgument()
    {
        $now = new Date();
        $now->setNow();
        $od = new Date($now);
        $this->assertEquals($od->getYear(), $now->getYear(), 'Year values dont match.');
        $this->assertEquals($od->getMonth(), $now->getMonth(), 'Month values dont match.');
        $this->assertEquals($od->getDay(), $now->getDay(), 'Day values dont match.');
        $this->assertTrue($od->isValid(), 'Opus\Date should be valid!');
    }

    /**
     * Test creation by passing DateTime as constructor argument.
     */
    public function testCreateWithDateTimeConstructionArgument()
    {
        $now = new DateTime();
        $od  = new Date($now);
        $this->assertEquals($od->getYear(), $now->format('Y'), 'Year values dont match.');
        $this->assertEquals($od->getMonth(), $now->format('m'), 'Month values dont match.');
        $this->assertEquals($od->getDay(), $now->format('d'), 'Day values dont match.');
        $this->assertTrue($od->isValid(), 'Opus\Date should be valid!');
    }

    /**
     * Test creation by passing modified DateTime as constructor argument
     *  (10 minutes in the past).
     */
    public function testCreateWithModifiedDateTimeConstructionArgument()
    {
        $past = new DateTime();
        $past->sub(new DateInterval('PT10M'));
        $od = new Date($past);

        $this->assertEquals($od->getYear(), $past->format('Y'), 'Year values dont match.');
        $this->assertEquals($od->getMonth(), $past->format('m'), 'Month values dont match.');
        $this->assertEquals($od->getDay(), $past->format('d'), 'Day values dont match.');

        $this->assertEquals($od->getHour(), $past->format('H'), 'Hour values dont match.');
        $this->assertEquals($od->getMinute(), $past->format('i'), 'Minute values dont match.');
        $this->assertEquals($od->getSecond(), $past->format('s'), 'Second values dont match.');

        $this->assertEquals($od->getUnixTimestamp(), $past->getTimestamp(), 'Unix timestamp does not match');
        $this->assertTrue($od->isValid(), 'Opus\Date should be valid!');
    }

    /**
     * Test creation by passing an *invalid* string constructor argument.
     */
    public function testCreateWithTooLongYearStringConstructionArgumentShouldBeInvalid()
    {
        $od = new Date("1234567-12-12T11:11:11Z");
        $this->assertFalse($od->isValid(), 'Opus\Date object should be INVALID!');
    }

    /**
     * Test creation by passing an *invalid* string constructor argument.
     */
    public function testCreateWithShortYearStringConstructionArgumentShouldBeValid()
    {
        $od = new Date("10-12-12T11:11:11Z");
        $this->assertTrue($od->isValid(), 'Opus\Date object should be valid!');
    }

    /**
     * Test if Opus\Date swaps month/year when locale===en
     */
    public function testIfParsingOfIsoDateSwapsDayAndMonth()
    {
        $locale = new Zend_Locale("en");
        Zend_Locale::setDefault($locale);
        $date = new Date('2010-06-04T02:36:53Z');

        $this->assertEquals(4, $date->getDay());
        $this->assertEquals(6, $date->getMonth());
    }

    /**
     * Test if setNow really sets now.
     */
    public function testSetNow()
    {
        $date = new Date();
        $date->setNow();

        $this->assertEquals(date('Y'), $date->getYear());
        $this->assertEquals(date('m'), $date->getMonth());
        $this->assertEquals(date('d'), $date->getDay());
        $this->assertTrue($date->isValid(), 'Opus\Date should be valid after setNow!');
    }

    /**
     * Test if setNow really sets now.
     */
    public function testSetNowToStringIsValid()
    {
        $date = new Date();
        $date->setNow();

        $this->assertEquals(date('Y'), $date->getYear());
        $this->assertEquals(date('m'), $date->getMonth());
        $this->assertEquals(date('d'), $date->getDay());

        $dateString = $date->__toString();
        $dateReload = new Date($dateString);
        $this->assertEquals($date->getYear(), $dateReload->getYear());
        $this->assertEquals($date->getMonth(), $dateReload->getMonth());
        $this->assertEquals($date->getDay(), $dateReload->getDay());
    }

    /**
     * Test if converting from-to string is invariant.
     */
    public function testFromStringToStringIsInvariant()
    {
        $date = new Date();
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
     */
    public function testFromDateOnlyStringToStringIsInvariant()
    {
        $date = new Date();
        $date->setFromString('2010-06-04');

        $this->assertEquals(2010, $date->getYear());
        $this->assertEquals(06, $date->getMonth());
        $this->assertEquals(04, $date->getDay());

        $this->assertEquals('2010-06-04', "$date");
    }

    /**
     * Test if converting from-to string is invariant.
     */
    public function testFromStringToStringKeepsTimeZone()
    {
        $date = new Date();
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
     */
    public function testStringOutputPadding()
    {
        $date = new Date();

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
     */
    public function testSetFromStringErrorHandling()
    {
        $invalidStrings = [
            '',
            null,
            '2010',
            '2011-12-bla',
            '01.01.2010',
            '2011-12-12T23:59:59',
            '2011-12-12X99:99:99Z',
        ];
        foreach ($invalidStrings as $invalidString) {
            try {
                $date = new Date();
                $date->setFromString($invalidString);
                $this->fail("Missing expected InvalidArgumentException for invalid string '{$invalidString}'.");
            } catch (InvalidArgumentException $e) {
                // OK.
            }
        }
    }

    public function testSetTimezone()
    {
        $timeZoneStrings = [
            '2011-12-12'                => null,
            '2011-12-12T23:59:59Z'      => 'Z',
            '2011-12-12T23:59:59UTC'    => 'Z',
            '2011-12-12T23:59:59+0'     => 'Z',
            '2011-12-12T23:59:59+00'    => 'Z',
            '2011-12-12T23:59:59+0000'  => 'Z',
            '2011-12-12T23:59:59+00:00' => 'Z',
            '2011-12-12T23:59:59EST'    => '-05:00',
            '2011-12-12T23:59:59+02:00' => '+02:00',
        ];
        foreach ($timeZoneStrings as $timeString => $timeZone) {
            $date = new Date($timeString);
            $this->assertEquals($timeZone, $date->getTimezone());
        }
    }

    /**
     * TODO What are we trying to test here?
     */
    public function testSetNowOutput()
    {
        $date = new Date();
        $date->setNow();

        $dateTime = $date->getDateTime();

        $date2 = new Date($dateTime);

        $this->assertEquals($date->__toString(), $date2->__toString());
    }

    public function testGetUnixTimestamp()
    {
        $date = new Date();
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
    public function testGetUnixTimestampForCustomDate()
    {
        $date = new Date('2012-10-17');

        $timestamp = $date->getUnixTimestamp();

        $this->assertEquals(
            $date->getDateTime()->format('Y-m-d H:i:s'),
            gmdate('Y-m-d H:i:s', $timestamp)
        );
    }

    public function testUpdateFromArray()
    {
        $date = new Date();

        $date->updateFromArray([
            'Year'     => 2018,
            'Month'    => 5,
            'Day'      => 11,
            'Hour'     => 22,
            'Minute'   => 35,
            'Second'   => 11,
            'Timezone' => '+01:00',
        ]);

        $dateTime = $date->getDateTime();

        $this->assertEquals('2018-05-11 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('+01:00', $date->getTimezone());
    }

    public function testUpdateFromArrayWithStrings()
    {
        $date = new Date();

        $date->updateFromArray([
            'Year'     => '2018',
            'Month'    => '5',
            'Day'      => '11',
            'Hour'     => '22',
            'Minute'   => '35',
            'Second'   => '11',
            'Timezone' => '+01:00',
        ]);

        $dateTime = $date->getDateTime();

        $this->assertEquals('2018-05-11 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('+01:00', $date->getTimezone());
    }

    public function testUpdateFromArrayWithoutTime()
    {
        $date = new Date();

        $date->updateFromArray([
            'Year'  => '2018',
            'Month' => '5',
            'Day'   => '11',
        ]);

        $dateTime = $date->getDateTime();

        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:s', '2018-05-11T00:00:00');

        $this->assertEquals('2018-05-11 00:00:00', date_format($dateTime, 'Y-m-d H:i:s'));
    }

    public function testUpdateFromArrayResetsOtherFields()
    {
        $date = new Date();

        $date->updateFromArray([
            'Year'     => 2018,
            'Month'    => 5,
            'Day'      => 11,
            'Hour'     => 22,
            'Minute'   => 35,
            'Second'   => 11,
            'Timezone' => '+01:00',
        ]);

        $dateTime = $date->getDateTime();

        $this->assertEquals('2018-05-11 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('+01:00', $date->getTimezone());

        $date->updateFromArray([
            'Year'  => 2012,
            'Month' => 12,
            'Day'   => 1,
        ]);

        $dateTime = $date->getDateTime();

        $this->assertNotEquals('2012-12-01 22:35:11', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('2012-12-01 00:00:00', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('', $date->getTimezone());
        $this->assertEquals(1354320000, $date->getUnixTimestamp());
    }

    public function testUpdateFromArraySimple()
    {
        $date = new Date();

        $date->updateFromArray('2012-12-01');

        $dateTime = $date->getDateTime();

        $this->assertEquals('2012-12-01 00:00:00', date_format($dateTime, 'Y-m-d H:i:s'));
        // $this->assertEquals('', $date->getTimezone());
        $this->assertEquals(1354320000, $date->getUnixTimestamp());
    }

    public function testUpdateFromArraySimpleLongForm()
    {
        $date = new Date();

        $date->updateFromArray('2010-06-04T22:36:53Z');

        $dateTime = $date->getDateTime();

        $this->assertEquals('2010-06-04 22:36:53', date_format($dateTime, 'Y-m-d H:i:s'));
        $this->assertEquals('Z', $date->getTimezone());
        $this->assertEquals(1275691013, $date->getUnixTimestamp());
    }

    public function testUpdateFromArraySimpleUnixTimestamp()
    {
        $date = new Date();

        $date->updateFromArray(1275691013);

        $this->assertEquals('2010-06-04T22:36:53Z', $date->__toString());
        $this->assertEquals('Z', $date->getTimezone());
        $this->assertEquals(1275691013, $date->getUnixTimestamp());
    }

    /**
     * UnixTimestamp is read-only and will not be set from array.
     */
    public function testUpdateFromArrayWithUnixTimestamp()
    {
        $date = new Date();

        $date->updateFromArray([
            'Year'          => '2018',
            'Month'         => '05',
            'Day'           => '07',
            'UnixTimestamp' => 1275691013,
        ]);

        $this->assertTrue($date->isValid());
        $this->assertTrue($date->isDateOnly());
        $this->assertEquals('2018-05-07', $date->__toString());
        $this->assertNotEquals(1275691013, $date->getUnixTimestamp());
        $this->assertEquals(1525651200, $date->getUnixTimestamp());
    }

    public function testUpdateUnixTimestamp()
    {
        $date = new Date('2018-10-14');

        $this->assertEquals('2018-10-14', date_format($date->getDateTime(), 'Y-m-d'));

        $timestamp = $date->getUnixTimestamp();

        $date->setYear(2017);

        $this->assertEquals('2017-10-14', date_format($date->getDateTime(), 'Y-m-d'));
        $this->assertNotEquals($timestamp, $date->getUnixTimestamp(), 'Field UnixTimestamp was not updated.');
        $this->assertEquals('1507939200', $date->getUnixTimestamp());
    }

    public function testCompareSame()
    {
        $dateStr = '2018-10-14';

        $date = new Date($dateStr);

        $this->assertEquals(0, $date->compare($date));
        $this->assertEquals(0, $date->compare(new Date($dateStr)));
    }

    public function testCompareSameWithTime()
    {
        $dateStr = '2018-10-14T15:31:12Z';
        $date    = new Date($dateStr);

        $this->assertEquals(0, $date->compare($date));
        $this->assertEquals(0, $date->compare(new Date($dateStr)));
    }

    public function testCompareSameWithTimezone()
    {
        $dateStr  = '2018-10-14T15:31:12Z';
        $dateStr2 = '2018-10-14T17:31:12+02:00';

        // both timestamps describe the same universal time
        $this->assertEquals(strtotime($dateStr), strtotime($dateStr2));

        $date = new Date($dateStr);

        $this->assertEquals(0, $date->compare($date));
        $this->assertEquals(0, $date->compare(new Date($dateStr2)));
    }

    public function testCompareLess()
    {
        $date  = new Date('2018-10-14');
        $date2 = new Date('2018-10-15');

        $this->assertEquals(-1, $date->compare($date2));
    }

    public function testCompareLessWithTime()
    {
        $date  = new Date('2018-10-14T09:34:11Z');
        $date2 = new Date('2018-10-14T09:34:12Z');

        $this->assertEquals(-1, $date->compare($date2));
    }

    public function testCompareLessWithTimezone()
    {
        $date  = new Date('2018-10-14T10:34:11+02:00');
        $date2 = new Date('2018-10-14T09:34:11Z');

        $this->assertEquals(-1, $date->compare($date2));
    }

    public function testCompareLarger()
    {
        $date  = new Date('2018-10-14');
        $date2 = new Date('2018-10-15');

        $this->assertEquals(1, $date2->compare($date));
    }

    public function testCompareLargerWithTime()
    {
        $date  = new Date('2018-10-14T00:00:10Z');
        $date2 = new Date('2018-10-14T00:00:11Z');

        $this->assertEquals(1, $date2->compare($date));
    }

    public function testCompareLargerWithTimezone()
    {
        $date  = new Date('2018-10-14T00:00:10+02:00');
        $date2 = new Date('2018-10-13T23:00:11Z');

        $this->assertEquals(1, $date2->compare($date));
    }

    public function testCreateWithTimezone()
    {
        $date = new Date('2018-10-14T00:00:10+02:00');

        $this->assertTrue($date->isValid());

        $this->assertEquals(2018, $date->getYear());
        $this->assertEquals(10, $date->getMonth());
        $this->assertEquals(14, $date->getDay());
        $this->assertEquals(0, $date->getHour());
        $this->assertEquals(0, $date->getMinute());
        $this->assertEquals(10, $date->getSecond());
        $this->assertEquals('+02:00', $date->getTimezone());
        $this->assertEquals(1539468010, $date->getUnixTimestamp());
    }

    /**
     * Not supporting names for timezone probably makes sense in the long run. The meaning of "+02:00" will never
     * change, however the timezone for Europe/Berlin can depend on social, political and other influences.
     */
    public function testCreateWithTimezoneNameNotSupported()
    {
        $date = new Date('2018-10-14T00:00:10Europe/Berlin');

        $this->assertFalse($date->isValid());
    }

    public function testCompareWithNull()
    {
        $date = new Date('2018-10-14');

        $this->assertEquals(1, $date->compare(null));
    }

    public function testCompareWithOtherObjectType()
    {
        $date = new Date('2018-10-14');

        $this->setExpectedException(ModelException::class, 'Cannot compare Zend_Config with Opus\Date object.');

        $date->compare(new Zend_Config([]));
    }

    public function testToArrayWithTimestamp()
    {
        $date = new Date();

        $date->setTimestamp(1534284000); // interpret als UTC (Z)

        $this->assertEquals([
            'Year'          => '2018',
            'Month'         => '08',
            'Day'           => '14',
            'Hour'          => '22',
            'Minute'        => '00',
            'Second'        => '00',
            'Timezone'      => 'Z',
            'UnixTimestamp' => 1534284000,
        ], $date->toArray());
    }

    public function testToArray()
    {
        $date = new Date();

        $date->setYear('2018');
        $date->setMonth('08');
        $date->setDay('15');

        $this->assertEquals([
            'Year'          => '2018',
            'Month'         => '08',
            'Day'           => '15',
            'Hour'          => null,
            'Minute'        => null,
            'Second'        => null,
            'Timezone'      => null,
            'UnixTimestamp' => 1534291200,
        ], $date->toArray());
    }

    public function testGetDateTimeForEmptyDate()
    {
        $date = new Date();

        $this->assertFalse($date->isValid());
        $this->assertNull($date->getDateTime());
    }

    public function testSetUnixTimestampWithLocalTimestamp()
    {
        $timestamp = strtotime('2018-10-15');

        $date = new Date();

        $date->setTimestamp($timestamp);

        $this->assertEquals([
            'Year'          => '2018',
            'Month'         => '10',
            'Day'           => '14',
            'Hour'          => '22',
            'Minute'        => '00',
            'Second'        => '00',
            'Timezone'      => 'Z',
            'UnixTimestamp' => 1539554400,
        ], $date->toArray());
    }

    public function testCompareFullWithDateOnly()
    {
        $date = new Date('2018-10-20T00:00:00Z');
        $time = new Date('2018-10-19T23:59:59Z');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Date('2018-10-20');
        $time = new Date('2018-10-19T23:59:59Z');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Date('2018-10-20');
        $time = new Date('2018-10-19T23:59:59Z');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));
    }

    public function testCompareFullWithDateOnlyWithDifferentTimezone()
    {
        $date = new Date('2018-10-20T00:00:00+02:00');
        $time = new Date('2018-10-19T23:59:59+02:00');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Date('2018-10-20');
        $time = new Date('2018-10-20T01:59:59+02:00');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Date('2018-10-20');
        $time = new Date('2018-10-20T01:59:59+02:00');

        $this->assertEquals(1, $date->compare($time));
        $this->assertEquals(-1, $time->compare($date));

        $date = new Date('2018-10-20');
        $time = new Date('2018-10-20T02:00:00+02:00');

        $this->assertEquals(0, $date->compare($time));
    }

    public function testGetDateTimeDateOnlyWithTimezone()
    {
        $date = new Date('2018-10-20');

        $dateTime = $date->getDateTime();

        $this->assertNotNull($dateTime);
        $this->assertEquals(new DateTimeZone(date_default_timezone_get()), $dateTime->getTimezone());
        $this->assertEquals(1539986400, $dateTime->getTimestamp());

        $dateTimeUtc = $date->getDateTime('Z');

        $this->assertNotNull($dateTimeUtc);
        $this->assertEquals(new DateTimeZone('Z'), $dateTimeUtc->getTimezone());

        $this->assertNotEquals($dateTime->getTimestamp(), $dateTimeUtc->getTimestamp());
        $this->assertEquals(1539993600, $dateTimeUtc->getTimestamp());
    }

    public function testGetDateTimeWithTimezone()
    {
        $date = new Date('2018-10-20T00:00:00+02:00');

        $dateTime = $date->getDateTime();

        $this->assertNotNull($dateTime);
        $this->assertEquals(new DateTimeZone('+02:00'), $dateTime->getTimezone());
        $this->assertEquals(1539986400, $dateTime->getTimestamp());

        // if Opus\Date was created with a time zone -> changing it should not change the timestamp
        $dateTimeUtc = $date->getDateTime('Z');

        $this->assertNotNull($dateTimeUtc);
        $this->assertEquals(new DateTimeZone('UTC'), $dateTimeUtc->getTimezone());

        $this->assertEquals($dateTime->getTimestamp(), $dateTimeUtc->getTimestamp());
        $this->assertEquals(1539986400, $dateTimeUtc->getTimestamp());
    }

    public function testGetTimestampUsesLocalTimezone()
    {
        $date1 = new Date('2018-10-15');
        $date2 = new Date('2018-10-14T22:00:00Z');
        $date3 = new Date('2018-10-15T00:00:00+02:00');

        $this->assertEquals('2018-10-15', $date1->__toString());
        $this->assertEquals('2018-10-14T22:00:00Z', $date2->__toString());
        $this->assertEquals('2018-10-15T00:00:00+02:00', $date3->__toString());

        // the timestamps are all the same because the local time zone is used when nothing is specified
        $this->assertEquals(1539561600, $date1->getTimestamp());
        $this->assertEquals(1539554400, $date2->getTimestamp());
        $this->assertEquals(1539554400, $date3->getTimestamp());

        // for comparing UTC (Z) is used
        $this->assertEquals(1, $date1->compare($date2));
        $this->assertEquals(0, $date2->compare($date3));
        $this->assertEquals(-1, $date3->compare($date1));
    }

    public function testGetNow()
    {
        $now = Date::getNow();

        $dateTime = new DateTime();

        // don't compare seconds because timestamps will differ slightly
        $expected = $dateTime->format('Y-m-d\TH:i');

        $this->assertStringStartsWith($expected, $now->__toString());
    }

    public function testUseUtcInsteadOfZ()
    {
        $date = new Date('2018-10-20T00:00:00+02:00');

        $date->setTimezone('Z');

        $dateTime = $date->getDateTime('Z');

        $this->assertEquals(new DateTimeZone('UTC'), $dateTime->getTimezone());
    }

    public function testStoringDateWithTime()
    {
        $date = new Date('2018-10-20T14:31:12+02:00');

        $doc = new Document();

        $doc->setPublishedDate($date);

        $doc = new Document($doc->store());

        $dateLoaded = $doc->getPublishedDate();

        $this->assertEquals(0, $date->compare($dateLoaded));
        $this->assertEquals('2018-10-20T14:31:12+02:00', $dateLoaded->__toString());
    }

    public function testStoringDateWithTimezoneZ()
    {
        $date = new Date('2018-10-20T14:31:12Z');

        $doc = new Document();

        $doc->setPublishedDate($date);

        $doc = new Document($doc->store());

        $dateLoaded = $doc->getPublishedDate();

        $this->assertEquals(0, $date->compare($dateLoaded));
        $this->assertEquals('2018-10-20T14:31:12Z', $dateLoaded->__toString());
    }
}
