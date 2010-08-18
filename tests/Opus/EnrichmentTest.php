<?php
/*
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
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for Opus_Enrichment.
 *
 * @package Opus
 * @category Tests
 * @group EnrichmentTests
 */
class Opus_EnrichmentTest extends TestCase {

    private $_doc;

    public function setUp() {
        parent::setUp();

        $this->_doc = new Opus_Document();
        $this->_doc->store();
    }

    public function tearDown() {
        $this->_doc->delete();

        parent::tearDown();
    }

    /**
     * Stores and loads a document with an enrichment and asserts
     * enrichment key and enrichment value stays same.
     */
    public function testStoreAndLoadSimpleEnrichment() {
        $key = 'hallo';
        $value = 'welt';

        $this->_doc->addEnrichment()->setKey($key)->setValue($value);
        $this->_doc->store();

        // reload document
        $doc = new Opus_Document($this->_doc->getId());
        // get array of enrichments
        $enrichments = $doc->getEnrichment();
        $this->assertEquals(1, count($enrichments),
                'Stored one enrichment, loaded more or less enrichments.');

        // get the enrichment to assert key and value
        $enrichment = $enrichments[0];
        $this->assertEquals($key, $enrichment->getKey(),
                'Loaded other key, then stored.');
        $this->assertEquals($value, $enrichment->getValue(),
                'Loaded other value, then stored.');
    }

    public function testStoreAndLoadEqualKeyEnrichemts() {
        $key = 'MSC';
        $values = array('11B05', '42A05');

        foreach ($values as $v) {
            $this->_doc->addEnrichment()->setKey($key)->setValue($v);
        }
        $this->_doc->store();

        //reload document
        $doc = new Opus_Document($this->_doc->getId());
        //get array of enrichments
        $enrichments = $doc->getEnrichment();
        $this->assertEquals(count($values), count($enrichments),
                'Count of stored and loaded enrichments differ!');

        // compare enrichment values
        $loadedValues = array();
        foreach ($enrichments as $e) {
            $this->assertEquals($key, $e->getKey(),
                    'Loaded other key, then stored.');
            $loadedValues[] = $e->getValue();
        }
        foreach ($values as $v) {
            $this->assertTrue(in_array($v, $loadedValues),
                    'Unabled to find a previously stored enrichment value!');
        }
    }

        public function testStoreAndLoadEqualEnrichemts() {
        $key = '0';
        $value = '1';
        $count = 5;

        for ($i = 0; $i < $count; $i++) {
            $this->_doc->addEnrichment()->setKey($key)->setValue($value);
        }
        $this->_doc->store();

        //reload document
        $doc = new Opus_Document($this->_doc->getId());
        //get array of enrichments
        $enrichments = $doc->getEnrichment();
        $this->assertEquals($count, count($enrichments),
                'Count of stored and loaded enrichments differ!');

        // compare enrichment keys and values
        foreach ($enrichments as $e) {
            $this->assertEquals($key, $e->getKey(),
                    'Loaded other key, then stored.');
            $this->assertEquals($value, $e->getValue(),
                    'Loaded other value, then stored.');
        }
    }
}