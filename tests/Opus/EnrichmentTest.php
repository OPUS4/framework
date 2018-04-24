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
        $this->_doc->addEnrichment()->setKeyName('anothervalid')->setValue('anothervalue');
        $this->_doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), array('valid', 'anothervalid'), array('value', 'anothervalue'));
    }

    public function testStoreEqualKeyEnrichment() {
        $this->_doc->addEnrichment()->setKeyName('valid')->setValue('value2');
        $this->_doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), array('valid', 'valid'), array('value', 'value2'));
    }

    public function testStoreEqualValueEnrichment() {
        $this->_doc->addEnrichment()->setKeyName('anothervalid')->setValue('value');
        $this->_doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), array('valid', 'anothervalid'), array('value', 'value'));
    }

    public function testStoreDuplicateEnrichment() {
        $this->_doc->addEnrichment()->setKeyName('valid')->setValue('value');
        $this->_doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));

        $expectedEnrichment = array('KeyName' => 'valid', 'Value' => 'value');
        $this->assertEquals($doc->getEnrichment(0)->toArray(), $expectedEnrichment);
        $this->assertEquals($doc->getEnrichment(1)->toArray(), $expectedEnrichment);
    }

    public function testStoreEnrichmentWithInvalidKey() {
        $this->_doc->addEnrichment()->setKeyName('invalid')->setValue('foo');
        $this->setExpectedException('Opus\Model\Exception');
        $this->_doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEquals(1, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), array('valid'), array('value'));
    }

    public function testStoreEnrichmentWithoutValue() {
        $this->_doc->addEnrichment()->setKeyName('valid');
        $this->setExpectedException('Opus\Model\Exception');
        $this->_doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEquals(1, count($doc->getEnrichment()));
        $this->assertKeysAndValues($doc->getEnrichment(), array('valid'), array('value'));
    }

    /* READ */
    public function testLoadEnrichmentFromDocument() {
        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $this->assertEquals('valid', $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals('value', $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testLoadEnrichmentById() {
        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];

        $enrichment = new Opus_Enrichment($enrichment->getId());
        $this->assertEquals('valid', $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals('value', $enrichment->getValue(), 'Loaded other value, then stored.');
    }


    /* UPDATE */
    public function testUpdateEnrichment() {
        $newkey = 'anothervalid';
        $newval = 'anothervalue';

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $enrichment->setKeyName($newkey);
        $enrichment->setValue($newval);
        $doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $this->assertEquals($newkey, $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals($newval, $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testUpdateEnrichmentKeyOnly() {
        $newkey = 'anothervalid';

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $enrichment->setKeyName($newkey);
        $oldValue = $enrichment->getValue();
        $doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $this->assertEquals($newkey, $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals($oldValue, $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testUpdateEnrichmentValueOnly() {
        $newval = 'newvalue';

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $enrichment->setValue($newval);
        $doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $this->assertEquals('valid', $enrichment->getKeyName(), 'Loaded other key, then stored.');
        $this->assertEquals($newval, $enrichment->getValue(), 'Loaded other value, then stored.');
    }

    public function testUpdateEnrichmentInvalidKey() {
        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $enrichment = $enrichments[0];
        $enrichment->setKeyName('invalid');

        $this->setExpectedException('Opus\Model\Exception');
        $doc->store();
    }

    public function testUpdateEnrichmentSetDuplicateValue() {
        $doc = new Opus_Document($this->_doc->getId());

        // add another enrichment to document
        $doc->addEnrichment()->setKeyName('valid')->setValue('newvalue');
        $doc->store();

        // set duplicate value
        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertTrue(count($enrichments) == 2);

        $enrichment = $enrichments[1];
        $this->assertTrue($enrichment->getValue() == 'newvalue');
        $enrichment->setValue('value');
        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
    }

    public function testUpdateEnrichmentSetDuplicateKeyName() {
        $doc = new Opus_Document($this->_doc->getId());

        // add another enrichment to document
        $doc->addEnrichment()->setKeyName('anothervalid')->setValue('value');
        $doc->store();

        // set duplicate key
        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertTrue(count($enrichments) == 2);

        $enrichment = $enrichments[1];
        $this->assertTrue($enrichment->getKeyName() == 'anothervalid');
        $enrichment->setKeyName('valid');
        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $this->assertEquals(2, count($doc->getEnrichment()));
    }


    /* DELETE */
    public function testDeleteEnrichment() {
        $this->_doc->addEnrichment()->setKeyName('valid')->setValue('anothervalue');
        $this->_doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $enrichments = $doc->getEnrichment();
        $this->assertEquals(2, count($doc->getEnrichment()));

        unset($enrichments[0]);
        $doc->setEnrichment($enrichments);
        $doc->store();

        $doc = new Opus_Document($this->_doc->getId());
        $this->assertEquals(1, count($doc->getEnrichment()));
    }


    private function assertKeysAndValues($enrichments, $expectedKeys, $expectedValues) {
        $keys = array();
        $values = array();
        foreach ($enrichments as $enrichment) {
            array_push($keys, $enrichment->getKeyName());
            array_push($values, $enrichment->getValue());
        }
        // check if arrays contain the same elements
        $this->assertTrue(count($expectedKeys) == count(array_intersect($keys, $expectedKeys)));
        $this->assertTrue(count($expectedValues) == count(array_intersect($values, $expectedValues)));
    }

}