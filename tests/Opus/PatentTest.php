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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_PatentTest extends TestCase
{

    public function testToArray()
    {
        $patent = new Opus_Patent();
        $patent->setYearApplied(2017);
        $patent->setNumber('A23');
        $patent->setCountries('Germany, France');
        $patent->setApplication('A wonderful new invention.');
        $patent->setDateGranted(new Opus_Date('2018-02-21'));

        $data = $patent->toArray();

        $this->assertEquals([
            'YearApplied' => 2017,
            'Countries' => 'Germany, France',
            'Application' => 'A wonderful new invention.',
            'Number' => 'A23',
            'DateGranted' => [
                'Year' => 2018,
                'Month' => 2,
                'Day' => 21,
                'Hour' => null,
                'Minute' => null,
                'Second' => null,
                'Timezone' => null,
                'UnixTimestamp' => 1519171200,
            ]
        ], $data);
    }

    public function testFromArray()
    {
        $patent = Opus_Patent::fromArray([
            'YearApplied' => 2015,
            'Countries' => 'Spain',
            'Application' => 'New gadget.',
            'Number' => '487',
            'DateGranted' => '2017-08-27'
        ]);

        $this->assertNotNull($patent);
        $this->assertInstanceOf('Opus_Patent', $patent);

        $this->assertEquals(2015, $patent->getYearApplied());
        $this->assertEquals('Spain', $patent->getCountries());
        $this->assertEquals( 'New gadget.', $patent->getApplication());
        $this->assertEquals('487', $patent->getNumber());
        $this->assertEquals('2017-08-27', $patent->getDateGranted()->__toString());
    }

    public function testUpdateFromArray()
    {
        $patent = new Opus_Patent();

        $patent->updateFromArray([
            'YearApplied' => 2015,
            'Countries' => 'Spain',
            'Application' => 'New gadget.',
            'Number' => '487',
            'DateGranted' => '2017-08-27'
        ]);

        $this->assertNotNull($patent);
        $this->assertInstanceOf('Opus_Patent', $patent);

        $this->assertEquals(2015, $patent->getYearApplied());
        $this->assertEquals('Spain', $patent->getCountries());
        $this->assertEquals( 'New gadget.', $patent->getApplication());
        $this->assertEquals('487', $patent->getNumber());
        $this->assertEquals('2017-08-27', $patent->getDateGranted()->__toString());
    }
}
