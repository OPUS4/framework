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

    public function setUp() {
        parent::setUp();

        $this->_doc = new Opus_Document();
        $this->_doc->store();
    }


    public function testStoreAndRemoveEnrichmentKey() {
        $name = 'testkey';

        $enrichment_key = new Opus_EnrichmentKey();
        $enrichment_key->setName($name);
        $enrichment_key->store();

        $this->assertEquals(1, count(Opus_EnrichmentKey::getAll()));

        $enrichment_key->delete();
        $this->assertEquals(0, count(Opus_EnrichmentKey::getAll()));
    }


    public function testStoreDuplicateEnrichmentKey() {
        $name = 'testkey';

        $enrichment_key = new Opus_EnrichmentKey();
        $enrichment_key->setName($name);
        $enrichment_key->store();

        $enrichment_key = new Opus_EnrichmentKey();
        $enrichment_key->setName($name);

        try {
            $enrichment_key->store();
            $this->fail('Expecting Opus_Model_Exception, received none!');
        }
        catch (Exception $exc) {
            $this->assertEquals('Opus_Model_Exception', get_class($exc));
        }

        $this->assertEquals(1, count(Opus_EnrichmentKey::getAll()));
    }


    public function testRemoveEnrichmentKeyReferencedByDocument() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }

    public function testRenameEnrichmentKeyReferencedByDocument() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
          'This test has not been implemented yet.'
        );
    }


}