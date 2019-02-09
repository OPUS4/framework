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

class Opus_Enrichment_RegexTypeTest extends TestCase
{

    public function testSetOptionsFromStringWithValidRegex()
    {
        $regex = "^.*$";

        $regexType = new Opus_Enrichment_RegexType();
        $regexType->setOptionsFromString($regex);

        $json = $regexType->getOptions();
        $this->assertEquals('{"regex":"' . $regex . '"}', $json);

        $this->assertEquals($regex, $regexType->getRegex());

        $this->assertEquals($regex, $regexType->getOptionsAsString());
    }


    public function testSetOptionsFromStringWithInvalidRegex()
    {
        // this regex is intentionally invalid
        $regex = "[";

        $regexType = new Opus_Enrichment_RegexType();
        $regexType->setOptionsFromString($regex);

        $this->assertNull($regexType->getOptions());

        $this->assertNull($regexType->getRegex());

        $this->assertNull($regexType->getOptionsAsString());
    }

    public function testSetOptionsFromStringWithNullArgument()
    {
        $regexType = new Opus_Enrichment_RegexType();
        $regexType->setOptionsFromString(null);

        $this->assertNull($regexType->getOptions());

        $this->assertNull($regexType->getRegex());

        $this->assertNull($regexType->getOptionsAsString());
    }

    public function testSetOptions()
    {
        $regexType = new Opus_Enrichment_RegexType();

        $regexAsJson = '{"regex":"^foo.*$"}';

        $regexType->setOptions($regexAsJson);

        $this->assertEquals($regexAsJson, $regexType->getOptions());
        $this->assertEquals("^foo.*$", $regexType->getOptionsAsString());
    }

    public function testGetOptions()
    {
        $regexType = new Opus_Enrichment_RegexType();

        $json = $regexType->getOptions();
        $this->assertNull($json);

        $regexType->setRegex("^.*$");
        $json = $regexType->getOptions();
        $this->assertEquals('{"regex":"^.*$"}', $json);
    }

    public function testGetOptionProperties()
    {
        $regexType = new Opus_Enrichment_RegexType();
        $props = $regexType->getOptionProperties();
        $this->assertEquals(array('regex'), $props);
    }

    public function testGetFormElementName()
    {
        $regexType = new Opus_Enrichment_RegexType();
        $this->assertEquals('Text', $regexType->getFormElementName());
    }

}
