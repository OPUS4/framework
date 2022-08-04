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

namespace OpusTest;

use Opus\Common\Date;
use Opus\Document;
use OpusTest\TestAsset\TestCase;
use Zend_Locale;

use function date_default_timezone_get;
use function strtotime;

/**
 * Test cases for class Opus\Common\Date.
 *
 * TODO LAMINAS delete this class, Date has been moved to Common
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

    public function testSetUnixTimestampWithLocalTimestamp()
    {
        $timezone = date_default_timezone_get();

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

    /**
     * TODO LAMINAS PublishedDate should not be stored with time
     */
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

    /**
     * TODO LAMINAS PublishedDate should not be stored with time
     */
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
