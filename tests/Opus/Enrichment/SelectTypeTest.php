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
 * @package     Opus_Enrichment
 * @author      Sascha Szott <opus-development@saschaszott.de>
 * @copyright   Copyright (c) 2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Enrichment_SelectTypeTest extends TestCase
{

    public function testSetOptionsFromStringWithUnixLinebreaks()
    {
        $selectType = new Opus_Enrichment_SelectType();

        $selectType->setOptionsFromString("1\n2\n3");

        $json = $selectType->getOptions();
        $this->assertEquals('{"values":["1","2","3"]}', $json);

        $values = $selectType->getValues();

        $this->assertEquals(3, count($values));
        $this->assertEquals("1", $values[0]);
        $this->assertEquals("2", $values[1]);
        $this->assertEquals("3", $values[2]);

        $this->assertEquals("1\n2\n3", $selectType->getOptionsAsString());
    }

    public function testSetOptionFromStringsWithWindowsLinebreaks()
    {
        $selectType = new Opus_Enrichment_SelectType();

        $selectType->setOptionsFromString("1\r\n2\r\n3");

        $json = $selectType->getOptions();
        $this->assertEquals('{"values":["1","2","3"]}', $json);

        $values = $selectType->getValues();

        $this->assertEquals(3, count($values));
        $this->assertEquals("1", $values[0]);
        $this->assertEquals("2", $values[1]);
        $this->assertEquals("3", $values[2]);

        $this->assertEquals("1\n2\n3", $selectType->getOptionsAsString());
    }

    public function testSetOptionsFromStringWitEmptyValue()
    {
        $selectType = new Opus_Enrichment_SelectType();

        $selectType->setOptionsFromString("");

        $this->assertNull($selectType->getOptions());
        $this->assertNull($selectType->getValues());
        $this->assertNull($selectType->getOptionsAsString());
    }

    public function testSetOptions()
    {
        $selectType = new Opus_Enrichment_SelectType();

        $valuesAsJson = '{"values":["foo","bar","baz"]}';

        $selectType->setOptions($valuesAsJson);

        $this->assertEquals($valuesAsJson, $selectType->getOptions());
        $this->assertEquals("foo\nbar\nbaz", $selectType->getOptionsAsString());
    }


    public function testGetOptions()
    {
        $selectType = new Opus_Enrichment_SelectType();

        $this->assertNull($selectType->getOptions());

        $selectType->setValues(array("1", "2", "3"));
        $json = $selectType->getOptions();

        $this->assertEquals('{"values":["1","2","3"]}', $json);
    }

    public function testGetOptionProperties()
    {
        $selectType = new Opus_Enrichment_SelectType();
        $props = $selectType->getOptionProperties();
        $this->assertEquals(array('values'), $props);
    }

    public function testGetFormElementName()
    {
        $selectType = new Opus_Enrichment_SelectType();
        $this->assertEquals('Select', $selectType->getFormElementName());
    }

}
