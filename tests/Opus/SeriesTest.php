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
 * @package     Opus
 * @author      Sascha Szott <szott@zib.de>
 * @author      Susanne Gottwald <gottwald@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_SeriesTest extends TestCase {

    /**
     * Test if a document series can be retrieved by getAll().
     *
     */
    public function testCreateRetrieveAndDeleteSeries() {
        $this->assertEquals(0, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');
        
        $numberOfSetsToCreate = 3;
        $ids = array();
        for ($i = 0; $i < $numberOfSetsToCreate; $i++) {
            $set = new Opus_Series();
            $set->setTitle('New document set ' . $i);
            $set->store();
            array_push($ids, $set->getId());
        }

        $this->assertEquals($numberOfSetsToCreate, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');

        // cleanup
        foreach ($ids as $id) {
            $s = new Opus_Series($id);
            $s->delete();
        }

        $this->assertEquals(0, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');
    }

    public function testAssignSeriesToDocumentWithoutNumber() {
        $d = new Opus_Document();
        $d->store();

        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document($d->getId());
        $d->addSeries($s);

        // Regression test for OPUSVIER-2033
        try {
            $d->store();
            $this->fail("Expecting exception.");
        }
        catch (Opus_Model_Exception $ome) {
            // Nothing.
        }

        $this->assertEquals(1, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');

        // cleanup
        $s->delete();
    }

    public function testLinkSeriesInvalidWithoutNumber() {
        $d = new Opus_Document();
        $d->store();

        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document($d->getId());
        $ls = $d->addSeries($s);

        $this->assertTrue($s->isValid(), 'series should be valid');
        $this->assertFalse($ls->isValid());

        $this->setExpectedException('Opus_Model_Exception');
        $d->store();
    }

    public function testAssignSeriesToDocumentWithNumber() {
        $d = new Opus_Document();
        $d->store();
        
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $this->assertEquals(1, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');

        $d = new Opus_Document($d->getId());
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $d = new Opus_Document($d->getId());
        $this->assertEquals(1, count($d->getSeries()));
        $series = $d->getSeries();
        $s = $series[0];
        $this->assertEquals('foo', $s->getTitle());
        $this->assertEquals('1', $s->getNumber());
       
        // cleanup        
        $d->deletePermanent();
        $s->delete();
    }


    /**
     * 
     * "CRUD-completness tests on Opus_Series"
     *
     */
    
    public function testCreateSeriesWithoutTitle() {
        $s = new Opus_Series();
        $this->setExpectedException('Opus_Model_Exception');
        $s->store();
    }

    public function testCreateSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getTitle() === 'foo');
    }

    public function testUpdateSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $s = new Opus_Series($s->getId());
        $s->setTitle('bar');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getTitle() === 'bar');
    }

    public function testDeleteSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $id = $s->getId();
        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getTitle() === 'foo');

        $s->delete();
        
        $this->setExpectedException('Opus_Model_NotFoundException');
        $s = new Opus_Series($id);
    }

    
    /**
     *
     * tests in conjunction with class Opus_Model_Dependent_Link_DocumentSeries
     * 
     */

    public function testAssignDocumentToSeriesTwice() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($s)->setNumber('2');

        $this->setExpectedException('Opus_Model_Exception');
        $d->store();       
    }

    public function testAssignDocumentToMultipleSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $t = new Opus_Series();
        $t->setTitle('bar');
        $t->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($t)->setNumber('2');
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue(count($series) === 2);
       
        $this->assertTrue($series[0]->getTitle() === 'foo');
        $this->assertTrue($series[0]->getNumber() === '1');

        $this->assertTrue($series[1]->getTitle() === 'bar');
        $this->assertTrue($series[1]->getNumber() === '2');
    }


    public function testDeleteReferencedSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $this->assertTrue(count($d->getSeries()) === 1);

        $s->delete();

        $d = new Opus_Document($d->getId());
        $this->assertTrue(count($d->getSeries()) === 0);
    }

    public function testDeleteAllSeriesAssignments() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $this->assertTrue(count($d->getSeries()) === 1);

        $d->setSeries(null);
        $d->store();

        $d = new Opus_Document($d->getId());
        $this->assertTrue(count($d->getSeries()) === 0);
    }

    public function testDeleteOneSeriesAssignment() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $t = new Opus_Series();
        $t->setTitle('bar');
        $t->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($t)->setNumber('2');
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue(count($series) === 2);
        array_pop($series);
        $d->setSeries($series);
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue(count($series) === 1);
        $this->assertEquals('foo', $series[0]->getTitle());
        $this->assertEquals('1', $series[0]->getNumber());
    }

    public function testGetAll() {
        $ids = array();

        $s = new Opus_Series();
        $s->setTitle('c');
        $s->store();
        array_push($ids, $s->getId());

        $this->assertEquals(1, count(Opus_Series::getAll()));
        $series = Opus_Series::getAll();
        $this->assertEquals($series[0]->getId(), $ids[0]);

        $s = new Opus_Series();
        $s->setTitle('a');
        $s->store();
        array_push($ids, $s->getId());

        $this->assertEquals(2, count(Opus_Series::getAll()));
        $series = Opus_Series::getAll();
        $this->assertEquals($series[0]->getId(), $ids[0]);
        $this->assertEquals($series[1]->getId(), $ids[1]);

        $s = new Opus_Series();
        $s->setTitle('b');
        $s->store();
        array_push($ids, $s->getId());

        $this->assertEquals(3, count(Opus_Series::getAll()));
        $series = Opus_Series::getAll();
        $this->assertEquals($series[0]->getId(), $ids[0]);
        $this->assertEquals($series[1]->getId(), $ids[1]);
        $this->assertEquals($series[2]->getId(), $ids[2]);
    }

    public function testAssignVisibleStatus() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getVisible() == '1');

        $s = new Opus_Series($s->getId());
        $s->setVisible('0');
        $s->store();
        $this->assertTrue($s->getVisible() == '0');

        $s = new Opus_Series($s->getId());
        $s->setVisible('1');
        $s->store();
        $this->assertTrue($s->getVisible() == '1');

        $s->delete();
    }

    public function testAssignSortOrder() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getSortOrder() == '0');
        
        $s->setSortOrder('10');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getSortOrder() == '10');

        $s->delete();
    }

    public function testGetAllSeriesInSortedOrder() {
        $testValues = array( 3, 1, 2, 5, 4, 0 );

        foreach ($testValues as $value) {
            $s = new Opus_Series();
            $s->setTitle($value);
            $s->setSortOrder($value);
            $s->store();
        }

        $series = Opus_Series::getAllSortedBySortKey();
        $this->assertEquals(6, count($series));

        for ($i = 0; $i < count($series); $i++) {
            $this->assertEquals($i, $series[$i]->getSortOrder());
        }
    }

    public function testGetMaxSortKey() {
        $testValues = array( 3, 1, 2, 5, 4, 0, 10 );

        foreach ($testValues as $value) {
            $s = new Opus_Series();
            $s->setTitle($value);
            $s->setSortOrder($value);
            $s->store();
        }

        $this->assertTrue(Opus_Series::getMaxSortKey() == 10);
    }

    public function testGetMaxSortKeyInEmptyTable() {
        $this->assertTrue(Opus_Series::getMaxSortKey() == 0);
    }

    /**
     * Regression test for OPUSVIER-2258
     */
    public function testAssignDocumentsToMultipleSeriesWithSameNumber() {
        $d = new Opus_Document();
        $d->store();

        $s = new Opus_Series();
        $s->setTitle('a');
        $s->store();

        $d->addSeries($s)->setNumber(1);
        $d->store();

        $s = new Opus_Series();
        $s->setTitle('b');
        $s->store();

        $d->addSeries($s)->setNumber(1);
        $d->store();

        $d = new Opus_Document($d->getId());
        $this->assertTrue(count($d->getSeries()) == 2);
    }

    /**
     * Regression test for OPUSVIER-2258
     */
    public function testAssignSeriesNumberTwice() {
        $s = new Opus_Series();
        $s->setTitle('test');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');

        $this->setExpectedException('Opus_Model_DbConstrainViolationException');
        $d->store();
    }

    public function testAssignDocSortOrderForDocuments() {
        $s = new Opus_Series();
        $s->setTitle('test');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(1, count($series));
        $this->assertEquals('1', $series[0]->getNumber());
        $this->assertEquals(0, $series[0]->getDocSortOrder());

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('2')->setDocSortOrder(1);
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(1, count($series));
        $this->assertEquals('2', $series[0]->getNumber());
        $this->assertEquals(1, $series[0]->getDocSortOrder());
    }

    public function testGetDocumentIds() {
        $s = new Opus_Series();
        $s->setTitle('test');
        $s->store();

        $d1 = new Opus_Document();
        $d1->addSeries($s)->setNumber('I')->setDocSortOrder('1');
        $d1->store();

        $d2 = new Opus_Document();
        $d2->addSeries($s)->setNumber('II')->setDocSortOrder('2');
        $d2->store();

        
        $s = new Opus_Series($s->getId());
        $ids = $s->getDocumentIds();                
        $this->assertEquals(2, count($ids));
        $this->assertEquals($d1->getId(), $ids[0]);
        $this->assertEquals($d2->getId(), $ids[1]);        
    }

    public function testGetDocumentIdsForEmptySeries() {
        $s = new Opus_Series();
        $s->setTitle('test');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertEquals(0, count($s->getDocumentIds()));
    }

    public function testDocumentIdsSortedBySortKey() {
        $s = new Opus_Series();
        $s->setTitle('test');
        $s->store();

        $d1 = new Opus_Document();
        $d1->addSeries($s)->setNumber('I')->setDocSortOrder(1);
        $d1->store();

        $d2 = new Opus_Document();
        $d2->addSeries($s)->setNumber('II')->setDocSortOrder(2);
        $d2->store();

        $s = new Opus_Series($s->getId());
        $ids = $s->getDocumentIdsSortedBySortKey();
        $this->assertEquals(2, count($ids));
        $this->assertEquals($d1->getId(), $ids[1]);
        $this->assertEquals($d2->getId(), $ids[0]);
    }

    public function testDocumentIdsSortedBySortKeyForEmptySeries() {
        $s = new Opus_Series();
        $s->setTitle('test');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertEquals(0, count($s->getDocumentIdsSortedBySortKey()));
    }

    public function testIsNumberAvailableForEmptySeries() {
        $s = new Opus_Series();
        $s->setTitle('test');
        $s->store();

        $this->assertTrue($s->isNumberAvailable('foo'));

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('foo');
        $d->store();

        $this->assertFalse($s->isNumberAvailable('foo'));
        $this->assertTrue($s->isNumberAvailable('bar'));

        $d = new Opus_Document($d->getId());
        $d->setSeries(array());
        $d->store();

        $this->assertTrue($s->isNumberAvailable('foo'));
    }

    public function testGetNumberOfAssociatedDocumentsForEmptySeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $this->assertTrue($s->getNumOfAssociatedDocuments() === 0);
    }

    public function testGetNumberOfAssociatedDocuments() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('123');
        $d->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('456');
        $d->store();

        $this->assertTrue($s->getNumOfAssociatedDocuments() === 2);
    }

    public function testGetNumberOfAssociatedPublishedDocumentsForEmptySeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $this->assertTrue($s->getNumOfAssociatedPublishedDocuments() === 0);
    }

    public function testGetNumberOfAssociatedPublishedDocuments() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d1 = new Opus_Document();
        $d1->addSeries($s)->setNumber('123');
        $d1->store();

        $d2 = new Opus_Document();
        $d2->addSeries($s)->setNumber('456');
        $d2->store();

        $this->assertTrue($s->getNumOfAssociatedPublishedDocuments() === 0);

        $d1->setServerState('published');
        $d1->store();

        $this->assertTrue($s->getNumOfAssociatedPublishedDocuments() === 1);

        $d2->setServerState('published');
        $d2->store();

        $this->assertTrue($s->getNumOfAssociatedPublishedDocuments() === 2);

        $d2->delete();
        $this->assertTrue($s->getNumOfAssociatedPublishedDocuments() === 1);

        $d1->setServerState('inprogress');
        $d1->store();
        $this->assertTrue($s->getNumOfAssociatedPublishedDocuments() === 0);
    }
    
    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache() {

        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $doc = new Opus_Document();
        $doc->addSeries($s)->setNumber('123');
        $docId = $doc->store();

        $xmlCache = new Opus_Model_Xml_Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $s->setTitle('bar');
        $s->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }

    public function testGetDocumentForNumber() {
        $series = new Opus_Series();
        $series->setTitle('foo');
        $series->store();

        $doc = new Opus_Document();
        $doc->addSeries($series)->setNumber('III');
        $docId = $doc->store();

        $this->assertEquals($docId, $series->getDocumentIdForNumber('III'));

        $doc->deletePermanent();

        $this->assertNull($series->getDocumentIdForNumber('III'));
    }


}
