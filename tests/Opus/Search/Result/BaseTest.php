<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Application
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2016, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Search_Result_BaseTest extends SimpleTestCase {

    public function testAddFacetYearInverted() {
        $model = new Opus_Search_Result_Base();

        $model->addFacet('year_inverted', '65212:2013', 3);

        $facet = $model->getFacet('year');

        $this->assertCount(1, $facet);

        $value = $facet[0];

        $this->assertNotNull($value);
        $this->assertInstanceOf('Opus_Search_Result_Facet', $value);
        $this->assertEquals('2013', $value->getText());
        $this->assertEquals(3, $value->getCount());
    }

    public function testAddFacetYear() {
        $model = new Opus_Search_Result_Base();

        $model->addFacet('year', '2013', 7);

        $facet = $model->getFacet('year');

        $this->assertCount(1, $facet);

        $value = $facet[0];

        $this->assertNotNull($value);
        $this->assertInstanceOf('Opus_Search_Result_Facet', $value);
        $this->assertEquals('2013', $value->getText());
        $this->assertEquals(7, $value->getCount());
    }

    public function testAddMultipleFacetValues() {
        $model = new Opus_Search_Result_Base();

        $model->addFacet('author', 'John', 3);
        $model->addFacet('author', 'Jane', 5);

        $values = $model->getFacet('author');

        $this->assertCount(2, $values);

        $john = $values[0];
        $this->assertNotNull($john);
        $this->assertInstanceOf('Opus_Search_Result_Facet', $john);
        $this->assertEquals('John', $john->getText());
        $this->assertEquals(3, $john->getCount());

        $jane = $values[1];
        $this->assertNotNull($jane);
        $this->assertInstanceOf('Opus_Search_Result_Facet', $jane);
        $this->assertEquals('Jane', $jane->getText());
        $this->assertEquals(5, $jane->getCount());
    }

}
