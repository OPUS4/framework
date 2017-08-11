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
 * @package     Opus_SolrSearch
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2017, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_SolrSearch_QueryTest extends TestCase
{

    public function testEscape()
    {
        $query = new Opus_SolrSearch_Query();

        $this->assertEquals('test', $query->escape('test'));
        $this->assertEquals('test\[\]', $query->escape('test[]'));

        $this->assertEquals('Joh"n"', $query->escape('Joh"n')); // TODO why? Does it make sense?
        $this->assertEquals('J"oh"n', $query->escape('J"oh"n'));

        $this->assertEquals('Plus\+Test', $query->escape('Plus+Test'));

        $this->assertEquals('\(1\+1\)\:2', $query->escape('(1+1):2'));
    }

    public function testEscapeIgnore()
    {
        $query = new Opus_SolrSearch_Query();

        $this->assertEquals('test*', $query->escape('test*'));
        $this->assertEquals('test?', $query->escape('test?'));
    }

    public function testEscapeEscapes()
    {
        $query = new Opus_SolrSearch_Query();

        $this->assertEquals('Plus\\\\\\+Test', $query->escape('Plus\+Test')); // TODO does it make sense?

        $this->assertEquals('Double\\\\\\+Escape\\\\\\+Test', $query->escape('Double\+Escape\+Test'));
    }

    public function testEscapeNotWithinQuotes()
    {
        $query = new Opus_SolrSearch_Query();

        $this->assertEquals('"te+st"\+', $query->escape('"te+st"+'));
    }

    public function testEscapeAddQuoteAtEndIfUnevenQuotesIncludingEscapedQuotes()
    {
        $query = new Opus_SolrSearch_Query();

        $this->assertEquals('j\\\\"ohn+D\"oe"', $query->escape('j\"ohn+D\"oe'));
        $this->assertEquals('D\\\\"oe, J\"ane"', $query->escape('D\"oe, J\"ane'));
    }

    public function testEscapeUnevenQuotes()
    {
        $query = new Opus_SolrSearch_Query();

        $this->assertEquals('"test"', $query->escape('"test'));
        $this->assertEquals('test""', $query->escape('test"'));
        $this->assertEquals('"test"', $query->escape('"test"'));
        $this->assertEquals('"te"st""', $query->escape('"te"st"'));
        $this->assertEquals('"te\"st"""', $query->escape('"te\"st"'));
        $this->assertEquals('te\\\\"st"', $query->escape('te\"st')); // TODO this probably does not make sense
    }

    public function testLowercaseWildcardQuery()
    {
        $query = new Opus_SolrSearch_Query();

        $this->assertEquals('TeSt', $query->lowercaseWildcardQuery('TeSt'));
        $this->assertEquals('te?t', $query->lowercaseWildcardQuery('TE?t'));
        $this->assertEquals('test*', $query->lowercaseWildcardQuery('TeSt*'));
    }

}