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

    /**
     * @var Opus_Document
    */
    private $_doc;

     /**
     * @var Opus_EnrichmentKey
     */
    private $_enrichmentkey;


    /**
     * @var Opus_EnrichmentKey
     */
    private $_anotherenrichmentkey;

    public function setUp() {
        parent::setUp();

        $this->_enrichmentkey = new Opus_EnrichmentKey();
        $this->_enrichmentkey->setName('valid');
        $this->_enrichmentkey->store();

        $this->_anotherenrichmentkey = new Opus_EnrichmentKey();
        $this->_anotherenrichmentkey->setName('anothervalid');
        $this->_anotherenrichmentkey->store();

        $this->_doc = new Opus_Document();
        $this->_doc->addEnrichment()->setKeyName('valid')->setValue('value');
        $this->_doc->store();
    }

     /* CREATE */
    public function testStoreEnrichment() {
        $key = 'anothervalid';
        $value = 'anothervalue';

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->_doc->store();

        $doc = new Opus_Document( $this->_doc->getId() );
        $this->assertEquals(2, count($doc->getEnrichment()));
    }

    public function testStoreEqualKeyEnrichment() {
        $key = 'valid';
        $value = 'value2';

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->_doc->store();

        $doc = new Opus_Document( $this->_doc->getId() );
        $this->assertEquals(2, count($doc->getEnrichment()));
    }

    public function testStoreEqualValueEnrichment() {
        $key = 'anothervalid';
        $value = 'value';

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->_doc->store();

        $doc = new Opus_Document( $this->_doc->getId() );
        $this->assertEquals(2, count($doc->getEnrichment()));
    }

    public function testStoreEqualEnrichment() {
        $key = 'valid';
        $value = 'value';

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->setExpectedException('Opus_Model_Exception');
        $this->_doc->store();

        $doc = new Opus_Document( $this->_doc->getId() );
        $this->assertEquals(1, count($doc->getEnrichment()));
    }


    public function testStoreEnrichmentWithInvalidKey() {
        $key = 'invalid';
        $value = 'foo';

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->setExpectedException('Opus_Model_Exception');
        $this->_doc->store();

        $doc = new Opus_Document( $this->_doc->getId() );
        $this->assertEquals(1, count($doc->getEnrichment()));
    }

    public function testStoreEnrichmentWithoutValue() {
        $key = 'valid';

        $this->_doc->addEnrichment()->setKeyName($key);
        $this->setExpectedException('Opus_Model_Exception');
        $this->_doc->store();

        $doc = new Opus_Document( $this->_doc->getId() );
        $this->assertEquals(1, count($doc->getEnrichment()));
    }


    /* READ */
    public function testLoadEnrichment() {
        $doc = new Opus_Document( $this->_doc->getId() );
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $this->assertEquals('valid', $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals('value', $enrichment->getValue(), 'Loaded other value, then stored.');
    }

     /**
     * Stores and loads a document with an enrichment and asserts
     * enrichment key and enrichment value stays same.
     */
    public function testStoreAndLoadSimpleEnrichment() {
        $this->markTestSkipped(
              "Replaced By 'testStoreEnrichment' and 'testLoadEnrichment'."
        );

        $key = 'valid';
        $value = 'foo';

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->_doc->store();

        // reload document
        $doc = new Opus_Document($this->_doc->getId());
        // get array of enrichments
        $enrichments = $doc->getEnrichment();
        $this->assertEquals(1, count($enrichments),
                'Stored one enrichment, loaded more or less enrichments.');

        // get the enrichment to assert key and value
        $enrichment = $enrichments[0];
        $this->assertEquals($key, $enrichment->getKeyName(),
                'Loaded other key, then stored.');
        $this->assertEquals($value, $enrichment->getValue(),
                'Loaded other value, then stored.');
    }

    public function testStoreAndLoadEqualKeyEnrichments() {
        $this->markTestSkipped(
              "Replaced By 'testStoreEqualKeyEnrichment' and 'testLoadEnrichment'."
        );


        $key = 'valid';
        $values = array('foo', 'bar');

        foreach ($values as $v) {
            $this->_doc->addEnrichment()->setKeyName($key)->setValue($v);
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
            $this->assertEquals($key, $e->getKeyName(),
                    'Loaded other key, then stored.');
            $loadedValues[] = $e->getValue();
        }
        foreach ($values as $v) {
            $this->assertTrue(in_array($v, $loadedValues),
                    'Unabled to find a previously stored enrichment value!');
        }
    }

    public function testStoreAndLoadSimpleEnrichments() {

        $this->markTestSkipped(
              "Replaced By 'testStoreEqualValueEnrichment' and 'testLoadEnrichment'."
        );

        $keys = array('valid', 'anothervalid');
        $value = 'foo';

        foreach ($keys as $key) {
            $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        }
        $this->_doc->store();

        // reload document
        $doc = new Opus_Document($this->_doc->getId());        
        $enrichments = $doc->getEnrichment();
        $this->assertEquals(count($keys), count($enrichments),
                'Stored one enrichment, loaded more or less enrichments.');

        $enrichmentKeys = array();
        foreach ($enrichments as $enrichment) {
            $this->assertEquals($value, $enrichment->getValue(), 'Loaded other value, then stored.');
            array_push($enrichmentKeys, $enrichment->getKeyName());
        }
        
        foreach ($keys as $key) {
            $this->assertTrue(in_array($key, $enrichmentKeys), "enrichment key $key does not exists");
        }
    }

    public function testStoreAndLoadEqualEnrichments() {

        $this->markTestSkipped(
              "Replaced By 'testStoreEqualEnrichment'."
        );

        $key = 'valid';
        $value = 'foo';
        $count = 5;

        for ($i = 0; $i < $count; $i++) {
            $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
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
            $this->assertEquals($key, $e->getKeyName(),
                    'Loaded other key, then stored.');
            $this->assertEquals($value, $e->getValue(),
                    'Loaded other value, then stored.');
        }
    }

    public function testStoreIdenticalEnrichments() {

        $this->markTestSkipped(
              "Replaced By 'testStoreEqualEnrichment' and 'testLoadEnrichment'."
        );

        
        $key = 'valid';
        $value = 'foo';

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->_doc->store();

        $this->_doc->addEnrichment()->setKeyName($key)->setValue($value);
        $this->setExpectedException('Opus_Model_Exception');
        $this->_doc->store();

        //reload document
        $doc = new Opus_Document($this->_doc->getId());
        //get array of enrichments
        $enrichments = $doc->getEnrichment();
        $this->assertEquals(2, count($enrichments),
                'Count of stored and loaded enrichments differ!');

        // compare enrichment values
        foreach ($enrichments as $e) {
            $this->assertEquals($key, $e->getKeyName(),
                    'Loaded other key, then stored.');
            $this->assertEquals($value, $e->getValue(),
                    'Loaded other value, then stored.');
        }
    }

}