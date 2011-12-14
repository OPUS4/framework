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
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: EnrichmentKeyTest.php 8424 2011-12-12 12:50:32Z gmaiwald $
 */

/**
 * Test cases for class Opus_EnrichmentKeyTest .
 *
 * @package Opus
 * @category Tests
 *
 */
class Opus_EnrichmentKeyTest extends TestCase {

    /**
     * @var Opus_Document
    */
    private $_doc;

     /**
     * @var Opus_EnrichmentKey
     */
    private $unreferencedEnrichmentKey;


    /**
     * @var Opus_EnrichmentKey
     */
    private $referencedEnrichmentKey;

    public function setUp() {
        parent::setUp();

        $this->unreferencedEnrichmentKey = new Opus_EnrichmentKey();
        $this->unreferencedEnrichmentKey->setName('foo');
        $this->unreferencedEnrichmentKey->store();

        $this->referencedEnrichmentKey = new Opus_EnrichmentKey();
        $this->referencedEnrichmentKey->setName('bar');
        $this->referencedEnrichmentKey->store();

        $this->_doc = new Opus_Document();
        $this->_doc->addEnrichment()->setKeyName('bar')->setValue('value');
        $this->_doc->store();
    }

    /* CREATE */
    public function testStoreEnrichmentKey() {
        $ek = new Opus_EnrichmentKey();
        $ek->setName('baz');
        $ek->store();

        $ek = new Opus_EnrichmentKey('baz');
        $this->assertNotNull($ek);
        $this->assertEquals('baz', $ek->getName());
        $this->assertEquals(3, count(Opus_EnrichmentKey::getAll()));
    }

    public function testStoreEqualEnrichmentKey() {
        $ek = new Opus_EnrichmentKey();
        $ek->setName('foo');
        $this->setExpectedException('Opus_Model_Exception');
        $ek->store();
        $this->assertEquals(2, count(Opus_EnrichmentKey::getAll()));
    }

    public function testStoreEmptyEnrichmentKey() {
        $ek = new Opus_EnrichmentKey();
        $ek->setName('');
        $this->setExpectedException('Opus_Model_Exception');
        $ek->store();
        $this->assertEquals(2, count(Opus_EnrichmentKey::getAll()));
    }

    public function testStoryUnsetEnrichmentKey() {
        $ek = new Opus_EnrichmentKey();
        $this->setExpectedException('Opus_Model_Exception');
        $ek->store();
        $this->assertEquals(2, count(Opus_EnrichmentKey::getAll()));
    }

    
    /* DELETE */
    public function testDeleteEnrichmentKey() {
        $this->unreferencedEnrichmentKey->delete();
        $this->assertEquals(1, count(Opus_EnrichmentKey::getAll()));
    }

    public function testDeleteReferencedEnrichmentKey() {
        $this->setExpectedException('Opus_Model_Exception');
        $this->referencedEnrichmentKey->delete();
        $this->assertEquals(2, count(Opus_EnrichmentKey::getAll()));;
    }
    
    /* READ */
    public function testReadEnrichmentKey() {
        foreach (array('foo', 'bar') as $name) {
            $ek = new Opus_EnrichmentKey($name);
            $this->assertEquals($name, $ek->getName());
        }
    }

    /* UPDATE */
    public function testUpdateUnreferencedEnrichmentKey() {
        $this->unreferencedEnrichmentKey->setName('baz');
        $this->unreferencedEnrichmentKey->store();
        $this->assertEquals('baz', $this->unreferencedEnrichmentKey->getName());
    }

    public function testUpdateReferencedEnrichmentKey() {
        $this->referencedEnrichmentKey->setName('baz');
        $this->setExpectedException('Opus_Model_Exception');
        $this->referencedEnrichmentKey->store();
    }

    /* METHODS */
     public function testGetDisplayName() {
        $name = $this->unreferencedEnrichmentKey->getName();
        $displayName = $this->unreferencedEnrichmentKey->getDisplayName();
        $this->assertEquals($name, $displayName);
    }


}