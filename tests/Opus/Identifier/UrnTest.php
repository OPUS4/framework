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
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Identifier
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Frank Niebling <niebling@slub-dresden.de>
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 */

namespace OpusTest\Identifier;

use Opus\Identifier\Urn;
use OpusTest\TestAsset\TestCase;

/**
 * Test cases for URN and check digit generation.
 *
 * @category    Tests
 * @package     Opus\Identifier
 * @group       UrnTest
 */
class UrnTest extends TestCase
{
    /**
     * Overwrite parent methods.
     */
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    /**
     * Test data provider
     *
     * @return array Array containing document identifier, URN and check digit pairs.
     */
    public function provider()
    {
        return [
            ['8765', 'urn:nbn:de:swb:14-opus-8765', '0'],
            ['1913', 'urn:nbn:de:swb:14-opus-1913', '1'],
            ['6543', 'urn:nbn:de:swb:14-opus-6543', '2'],
            ['1234', 'urn:nbn:de:swb:14-opus-1234', '3'],
            ['7000', 'urn:nbn:de:swb:14-opus-7000', '4'],
            ['4567', 'urn:nbn:de:swb:14-opus-4567', '5'],
            ['4028', 'urn:nbn:de:swb:14-opus-4028', '6'],
            ['3456', 'urn:nbn:de:swb:14-opus-3456', '7'],
            ['4711', 'urn:nbn:de:swb:14-opus-4711', '8'],
            ['2345', 'urn:nbn:de:swb:14-opus-2345', '9'],
        ];
    }

    /**
     * Test data provider for bad input values.
     *
     * @return array Array containing invalid namespace identifier pairs.
     */
    public function badProvider()
    {
        return [
            // test invalid nids
            // containing unallowed characters.
            ['ERROR!', 'de:swb:14-opus', 'Used invalid namespace identifier. See RFC 2141.'],
            // beginning with a hyphen.
            ['-abc', 'de:swb:14-opus', 'Used invalid namespace identifier. See RFC 2141.'],
            // being to short.
            ['a', 'de:swb:14-opus', 'Used invalid namespace identifier. See RFC 2141.'],
            // empty string
            ['', 'de:swb:14-opus', 'Used invalid namespace identifier. See RFC 2141.'],
            // null object
            [null, 'de:swb:14-opus', 'Used invalid namespace identifier. See RFC 2141.'],
            //test invalid nss
            // containing spaces
            ['nbn', 'a b', 'Used invalid namespace specific string. See RFC 2141.'],
            // containing german umlauts.
            ['nbn', 'bäh', 'Used invalid namespace specific string. See RFC 2141.'],
            // empty string
            ['nbn', '', 'Used invalid namespace specific string. See RFC 2141.'],
            // null object.
            ['nbn', null, 'Used invalid namespace specific string. See RFC 2141.'],
        ];
    }

    /**
     * Test data provider for a bad document identifier input value.
     *
     * @return array Array containing invalid document identifier.
     */
    public function badIdProvider()
    {
        return [
            ['!ERROR!', 'Used invalid arguments for document id.'],
        ];
    }

    /**
     * Test if a valid URN is generated.
     *
     * @param string $documentId Identifier of the Document.
     * @param string $urn         A full qualified and valid URN.
     * @param string $checkdigit  Check digit valid for the given URN.
     * @dataProvider provider
     */
    public function testUrn($documentId, $urn, $checkdigit)
    {
        $identifier = new Urn('nbn', 'de:swb:14-opus');
        $generated  = $identifier->getUrn($documentId);
        $this->assertEquals($urn . $checkdigit, $generated, 'Generated URN is not valid.');
    }

    /**
     * Test if a valid check digit is generated
     *
     * @param string $documentId Identifier of the Document.
     * @param string $urn         A full qualified and valid URN.
     * @param string $checkdigit  Check digit valid for the given URN.
     * @dataProvider provider
     */
    public function testCheckDigit($documentId, $urn, $checkdigit)
    {
        $identifier = new Urn('nbn', 'de:swb:14-opus');
        $generated  = $identifier->getCheckDigit($documentId);
        $this->assertEquals($checkdigit, $generated, 'Generated check digit is not valid.');
    }

    /**
     * Test if illegal identifier values raise exceptions.
     *
     * @param string $nid First subnamespace identifier part of the URN.
     * @param string $nss Namespace specific string part of the URN.
     * @param string $msg Message on failing test.
     * @dataProvider badProvider
     */
    public function testInitializeWithInvalidValues($nid, $nss, $msg)
    {
        $this->expectException('InvalidArgumentException', $msg);
        $identifier = new Urn($nid, $nss);
    }

    /**
     * Test if illegal document identifier value raises an exception on calling urn generator.
     *
     * @param string $documentId Identifier of the Document.
     * @param string $msg         Message on failing test.
     * @dataProvider badIdProvider
     */
    public function testCallUrnGeneratorWithInvalidValue($documentId, $msg)
    {
        $this->expectException('InvalidArgumentException', $msg);
        $identifier = new Urn('nbn', 'de:swb:14-opus');
        $generated  = $identifier->getUrn($documentId);
    }

    /**
     * Test if illegal document identifier value raises an exception on calling check digit generator.
     *
     * @param string $documentId Identifier of the Document.
     * @param string $msg         Message on failing test.
     * @dataProvider badIdProvider
     */
    public function testCallCheckDigitGeneratorWithInvalidValue($documentId, $msg)
    {
        $this->expectException('InvalidArgumentException', $msg);
        $identifier = new Urn('nbn', 'de:swb:14-opus');
        $generated  = $identifier->getCheckDigit($documentId);
    }
}
