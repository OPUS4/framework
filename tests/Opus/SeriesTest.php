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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Config;
use Opus\Common\Document;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\NotFoundException;
use Opus\Common\Series;
use Opus\Common\SeriesInterface;
use Opus\Model\Xml\Cache;
use OpusTest\TestAsset\TestCase;
use Zend_Config;

use function array_pop;
use function array_push;
use function count;
use function sleep;

class SeriesTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, [
            'documents',
            'document_series',
            'link_documents_series',
        ]);
    }

    /**
     * Test if a document series can be retrieved by getAll().
     */
    public function testCreateRetrieveAndDeleteSeries()
    {
        $this->assertEquals(0, count(Series::getModelRepository()->getAll()), 'Wrong number of objects retrieved.');

        $numberOfSetsToCreate = 3;
        $ids                  = [];
        for ($i = 0; $i < $numberOfSetsToCreate; $i++) {
            $set = Series::new();
            $set->setTitle('New document set ' . $i);
            $set->store();
            array_push($ids, $set->getId());
        }

        $this->assertEquals(
            $numberOfSetsToCreate,
            count(Series::getAll()),
            'Wrong number of objects retrieved.'
        );

        // cleanup
        foreach ($ids as $id) {
            $s = Series::get($id);
            $s->delete();
        }

        $this->assertEquals(0, count(Series::getAll()), 'Wrong number of objects retrieved.');
    }

    public function testAssignSeriesToDocumentWithoutNumber()
    {
        $d = Document::new();
        $d->store();

        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $d = Document::get($d->getId());
        $d->addSeries($s);

        // Regression test for OPUSVIER-2033
        try {
            $d->store();
            $this->fail("Expecting exception.");
        } catch (ModelException $ome) {
            // Nothing.
        }

        $this->assertEquals(1, count(Series::getModelRepository()->getAll()), 'Wrong number of objects retrieved.');

        // cleanup
        $s->delete();
    }

    public function testLinkSeriesInvalidWithoutNumber()
    {
        $d = Document::new();
        $d->store();

        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $d  = Document::get($d->getId());
        $ls = $d->addSeries($s);

        $this->assertTrue($s->isValid(), 'series should be valid');
        $this->assertFalse($ls->isValid());

        $this->expectException(ModelException::class);
        $d->store();
    }

    public function testAssignSeriesToDocumentWithNumber()
    {
        $doc = Document::new();
        $doc->store();

        $series = Series::new();
        $series->setTitle('foo');
        $series->store();

        $this->assertEquals(1, count(Series::getModelRepository()->getAll()), 'Wrong number of objects retrieved.');

        $doc = Document::get($doc->getId());
        $doc->addSeries($series)->setNumber('1');
        $doc->store();

        $doc = Document::get($doc->getId());
        $this->assertEquals(1, count($doc->getSeries()));
        $series = $doc->getSeries();
        $series = $series[0];
        $this->assertEquals('foo', $series->getTitle());
        $this->assertEquals('1', $series->getNumber());

        // cleanup
        $doc->delete();
        $series->delete();
    }

    /**
     * "CRUD-completness tests on Opus\Series"
     */
    public function testCreateSeriesWithoutTitle()
    {
        $s = Series::new();
        $this->expectException(ModelException::class);
        $s->store();
    }

    public function testCreateSeries()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $s = Series::get($s->getId());
        $this->assertTrue($s->getTitle() === 'foo');
    }

    public function testUpdateSeries()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $s = Series::get($s->getId());
        $s->setTitle('bar');
        $s->store();

        $s = Series::get($s->getId());
        $this->assertTrue($s->getTitle() === 'bar');
    }

    public function testDeleteSeries()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $id = $s->getId();
        $s  = Series::get($s->getId());
        $this->assertTrue($s->getTitle() === 'foo');

        $s->delete();

        $this->expectException(NotFoundException::class);
        $s = Series::get($id);
    }

    /**
     * Tests in conjunction with class Opus\Model\Dependent\Link\DocumentSeries
     */
    public function testAssignDocumentToSeriesTwice()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($s)->setNumber('2');

        $this->expectException(ModelException::class);
        $d->store();
    }

    public function testAssignDocumentToMultipleSeries()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $t = Series::new();
        $t->setTitle('bar');
        $t->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($t)->setNumber('2');
        $d->store();

        $d      = Document::get($d->getId());
        $series = $d->getSeries();
        $this->assertTrue(count($series) === 2);

        $this->assertTrue($series[0]->getTitle() === 'foo');
        $this->assertTrue($series[0]->getNumber() === '1');

        $this->assertTrue($series[1]->getTitle() === 'bar');
        $this->assertTrue($series[1]->getNumber() === '2');
    }

    public function testDeleteReferencedSeries()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $this->assertTrue(count($d->getSeries()) === 1);

        $s->delete();

        $d = Document::get($d->getId());
        $this->assertTrue(count($d->getSeries()) === 0);
    }

    public function testDeleteAllSeriesAssignments()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $this->assertTrue(count($d->getSeries()) === 1);

        $d->setSeries(null);
        $d->store();

        $d = Document::get($d->getId());
        $this->assertTrue(count($d->getSeries()) === 0);
    }

    public function testDeleteOneSeriesAssignment()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $t = Series::new();
        $t->setTitle('bar');
        $t->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($t)->setNumber('2');
        $d->store();

        $d      = Document::get($d->getId());
        $series = $d->getSeries();
        $this->assertTrue(count($series) === 2);
        array_pop($series);
        $d->setSeries($series);
        $d->store();

        $d      = Document::get($d->getId());
        $series = $d->getSeries();
        $this->assertTrue(count($series) === 1);
        $this->assertEquals('foo', $series[0]->getTitle());
        $this->assertEquals('1', $series[0]->getNumber());
    }

    public function testGetAll()
    {
        $ids = [];

        $s = Series::new();
        $s->setTitle('c');
        $s->store();
        array_push($ids, $s->getId());

        $this->assertEquals(1, count(Series::getModelRepository()->getAll()));
        $series = Series::getAll();
        $this->assertEquals($series[0]->getId(), $ids[0]);

        $s = Series::new();
        $s->setTitle('a');
        $s->store();
        array_push($ids, $s->getId());

        $this->assertEquals(2, count(Series::getModelRepository()->getAll()));
        $series = Series::getAll();
        $this->assertEquals($series[0]->getId(), $ids[0]);
        $this->assertEquals($series[1]->getId(), $ids[1]);

        $s = Series::new();
        $s->setTitle('b');
        $s->store();
        array_push($ids, $s->getId());

        $this->assertEquals(3, count(Series::getModelRepository()->getAll()));
        $series = Series::getAll();
        $this->assertEquals($series[0]->getId(), $ids[0]);
        $this->assertEquals($series[1]->getId(), $ids[1]);
        $this->assertEquals($series[2]->getId(), $ids[2]);
    }

    public function testGetAllSortedByTitle()
    {
        Config::get()->merge(new Zend_Config([
            'series' => ['sortByTitle' => self::CONFIG_VALUE_TRUE],
        ]));

        $series = Series::new();
        $series->setTitle('c');
        $series->store();

        $series = Series::new();
        $series->setTitle('a');
        $series->store();

        $series = Series::new();
        $series->setTitle('b');
        $series->store();

        $allSeries = Series::getModelRepository()->getAll();

        $this->assertEquals(3, count($allSeries));
        $this->assertEquals('a', $allSeries[0]->getTitle());
        $this->assertEquals('b', $allSeries[1]->getTitle());
        $this->assertEquals('c', $allSeries[2]->getTitle());
    }

    public function testAssignVisibleStatus()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $s = Series::get($s->getId());
        $this->assertTrue($s->getVisible() === '1');

        $s = Series::get($s->getId());
        $s->setVisible('0');
        $s->store();
        $this->assertTrue($s->getVisible() === '0');

        $s = Series::get($s->getId());
        $s->setVisible('1');
        $s->store();
        $this->assertTrue($s->getVisible() === '1');

        $s->delete();
    }

    public function testAssignSortOrder()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $s = Series::get($s->getId());
        $this->assertTrue($s->getSortOrder() === '0');

        $s->setSortOrder('10');
        $s->store();

        $s = Series::get($s->getId());
        $this->assertTrue($s->getSortOrder() === '10');

        $s->delete();
    }

    public function testGetAllSeriesInSortedOrder()
    {
        $testValues = [3, 1, 2, 5, 4, 0];

        foreach ($testValues as $value) {
            $s = Series::new();
            $s->setTitle($value);
            $s->setSortOrder($value);
            $s->store();
        }

        $series = Series::getModelRepository()->getAllSortedBySortKey();
        $this->assertEquals(6, count($series));

        for ($i = 0; $i < count($series); $i++) {
            $this->assertEquals($i, $series[$i]->getSortOrder());
        }
    }

    public function testGetAllSortedBySortKeyOverriddenToSortByTitle()
    {
        Config::get()->merge(new Zend_Config([
            'series' => ['sortByTitle' => self::CONFIG_VALUE_TRUE],
        ]));

        $testValues = [3, 1, 2, 5, 4, 0];

        foreach ($testValues as $value) {
            $s = Series::new();
            $s->setTitle($value);
            $s->setSortOrder(5 - $value); // reverse order
            $s->store();
        }

        $allSeries = Series::getModelRepository()->getAllSortedBySortKey();

        $this->assertEquals(6, count($allSeries));

        foreach ($allSeries as $index => $series) {
            $this->assertEquals($index, $series->getTitle());
        }
    }

    public function testGetMaxSortKey()
    {
        $testValues = [3, 1, 2, 5, 4, 0, 10];

        foreach ($testValues as $value) {
            $s = Series::new();
            $s->setTitle($value);
            $s->setSortOrder($value);
            $s->store();
        }

        $this->assertEquals(10, Series::getModelRepository()->getMaxSortKey());
    }

    public function testGetMaxSortKeyInEmptyTable()
    {
        $this->assertTrue(Series::getMaxSortKey() === 0);
    }

    /**
     * Regression test for OPUSVIER-2258
     */
    public function testAssignDocumentsToMultipleSeriesWithSameNumber()
    {
        $d = Document::new();
        $d->store();

        $s = Series::new();
        $s->setTitle('a');
        $s->store();

        $d->addSeries($s)->setNumber(1);
        $d->store();

        $s = Series::new();
        $s->setTitle('b');
        $s->store();

        $d->addSeries($s)->setNumber(1);
        $d->store();

        $d = Document::get($d->getId());
        $this->assertTrue(count($d->getSeries()) === 2);
    }

    /**
     * Regression test for OPUSVIER-2258
     */
    public function testAssignSeriesNumberTwice()
    {
        $s = Series::new();
        $s->setTitle('test');
        $s->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $docId1 = $d->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $docId2 = $d->store();

        $doc1 = Document::get($docId1);
        $doc2 = Document::get($docId2);

        $seriesLink1 = $doc1->getSeries(0);
        $seriesLink2 = $doc2->getSeries(0);

        $this->assertNotNull($seriesLink1);
        $this->assertNotNull($seriesLink2);
        $this->assertEquals($seriesLink1->getNumber(), $seriesLink2->getNumber());
    }

    public function testAssignDocSortOrderForDocuments()
    {
        $s = Series::new();
        $s->setTitle('test');
        $s->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $d      = Document::get($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(1, count($series));
        $this->assertEquals('1', $series[0]->getNumber());
        $this->assertEquals(0, $series[0]->getDocSortOrder());

        $d = Document::new();
        $d->addSeries($s)->setNumber('2')->setDocSortOrder(1);
        $d->store();

        $d      = Document::get($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(1, count($series));
        $this->assertEquals('2', $series[0]->getNumber());
        $this->assertEquals(1, $series[0]->getDocSortOrder());
    }

    public function testGetDocumentIds()
    {
        $s = Series::new();
        $s->setTitle('test');
        $s->store();

        $d1 = Document::new();
        $d1->addSeries($s)->setNumber('I')->setDocSortOrder('1');
        $d1->store();

        $d2 = Document::new();
        $d2->addSeries($s)->setNumber('II')->setDocSortOrder('2');
        $d2->store();

        $s   = Series::get($s->getId());
        $ids = $s->getDocumentIds();
        $this->assertEquals(2, count($ids));
        $this->assertEquals($d1->getId(), $ids[0]);
        $this->assertEquals($d2->getId(), $ids[1]);
    }

    public function testGetDocumentIdsForEmptySeries()
    {
        $s = Series::new();
        $s->setTitle('test');
        $s->store();

        $s = Series::get($s->getId());
        $this->assertEquals(0, count($s->getDocumentIds()));
    }

    public function testDocumentIdsSortedBySortKey()
    {
        $s = Series::new();
        $s->setTitle('test');
        $s->store();

        $d1 = Document::new();
        $d1->addSeries($s)->setNumber('I')->setDocSortOrder(1);
        $d1->store();

        $d2 = Document::new();
        $d2->addSeries($s)->setNumber('II')->setDocSortOrder(2);
        $d2->store();

        $s   = Series::get($s->getId());
        $ids = $s->getDocumentIdsSortedBySortKey();
        $this->assertEquals(2, count($ids));
        $this->assertEquals($d1->getId(), $ids[1]);
        $this->assertEquals($d2->getId(), $ids[0]);
    }

    public function testDocumentIdsSortedBySortKeyForEmptySeries()
    {
        $s = Series::new();
        $s->setTitle('test');
        $s->store();

        $s = Series::get($s->getId());
        $this->assertEquals(0, count($s->getDocumentIdsSortedBySortKey()));
    }

    public function testIsNumberAvailableForEmptySeries()
    {
        $s = Series::new();
        $s->setTitle('test');
        $s->store();

        $this->assertTrue($s->isNumberAvailable('foo'));

        $d = Document::new();
        $d->addSeries($s)->setNumber('foo');
        $d->store();

        $this->assertFalse($s->isNumberAvailable('foo'));
        $this->assertTrue($s->isNumberAvailable('bar'));

        $d = Document::get($d->getId());
        $d->setSeries([]);
        $d->store();

        $this->assertTrue($s->isNumberAvailable('foo'));
    }

    public function testGetNumberOfAssociatedDocumentsForEmptySeries()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $this->assertTrue($s->getNumOfAssociatedDocuments() === 0);
    }

    public function testGetNumberOfAssociatedDocuments()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('123');
        $d->store();

        $d = Document::new();
        $d->addSeries($s)->setNumber('456');
        $d->store();

        $this->assertTrue($s->getNumOfAssociatedDocuments() === 2);
    }

    public function testGetNumberOfAssociatedPublishedDocumentsForEmptySeries()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $this->assertTrue($s->getNumOfAssociatedPublishedDocuments() === 0);
    }

    public function testGetNumberOfAssociatedPublishedDocuments()
    {
        $series = Series::new();
        $series->setTitle('foo');
        $series->store();

        $doc1 = Document::new();
        $doc1->addSeries($series)->setNumber('123');
        $doc1->store();

        $doc2 = Document::new();
        $doc2->addSeries($series)->setNumber('456');
        $doc2->store();

        $this->assertTrue($series->getNumOfAssociatedPublishedDocuments() === 0);

        $doc1->setServerState(Document::STATE_PUBLISHED);
        $doc1->store();

        $this->assertTrue($series->getNumOfAssociatedPublishedDocuments() === 1);

        $doc2->setServerState(Document::STATE_PUBLISHED);
        $doc2->store();

        $this->assertTrue($series->getNumOfAssociatedPublishedDocuments() === 2);

        $doc2->deleteDocument();
        $this->assertTrue($series->getNumOfAssociatedPublishedDocuments() === 1);

        $doc1->setServerState(Document::STATE_INPROGRESS);
        $doc1->store();
        $this->assertTrue($series->getNumOfAssociatedPublishedDocuments() === 0);
    }

    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache()
    {
        $s = Series::new();
        $s->setTitle('foo');
        $s->store();

        $doc = Document::new();
        $doc->addSeries($s)->setNumber('123');
        $docId = $doc->store();

        $xmlCache = new Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $s->setTitle('bar');
        $s->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }

    public function testGetDocumentForNumber()
    {
        $series = Series::new();
        $series->setTitle('foo');
        $series->store();

        $doc = Document::new();
        $doc->addSeries($series)->setNumber('III');
        $docId = $doc->store();

        $this->assertEquals($docId, $series->getDocumentIdForNumber('III'));

        $doc->delete();

        $this->assertNull($series->getDocumentIdForNumber('III'));
    }

    public function testDocumentServerDateModifiedNotUpdatedWithConfiguredFields()
    {
        $series = Series::new();
        $series->setTitle('foo');
        $series->store();

        $document = Document::new();
        $document->addSeries($series)->setNumber(5);
        $docId = $document->store();

        $serverDateModified = $document->getServerDateModified();
        sleep(1);
        $series->setSortOrder($series->getSortOrder() + 1);
        $series->store();

        $docReloaded = Document::get($docId);

        $this->assertEquals(
            (string) $serverDateModified,
            (string) $docReloaded->getServerDateModified(),
            'Expected no difference in server date modified.'
        );
    }

    public function testGetDisplayName()
    {
        $series = Series::new();
        $series->setTitle('TestTitle');
        $seriesId = $series->store();

        $series = Series::get($seriesId);

        $this->assertEquals('TestTitle', $series->getDisplayName());
    }

    public function testToArray()
    {
        $series = Series::new();

        $series->setTitle('Schriftenreihe');
        $series->setInfobox('Beschreibung');
        $series->setVisible(1);
        $series->setSortOrder(2);

        $data = $series->toArray();

        $this->assertEquals([
            'Title'     => 'Schriftenreihe',
            'Infobox'   => 'Beschreibung',
            'Visible'   => 1,
            'SortOrder' => 2,
        ], $data);
    }

    public function testFromArray()
    {
        $series = Series::fromArray([
            'Title'     => 'Schriftenreihe',
            'Infobox'   => 'Beschreibung',
            'Visible'   => 1,
            'SortOrder' => 2,
        ]);

        $this->assertNotNull($series);
        $this->assertInstanceOf(SeriesInterface::class, $series);

        $this->assertEquals('Schriftenreihe', $series->getTitle());
        $this->assertEquals('Beschreibung', $series->getInfobox());
        $this->assertEquals(1, $series->getVisible());
        $this->assertEquals(2, $series->getSortOrder());
    }

    public function testUpdateFromArray()
    {
        $series = Series::new();

        $series->updateFromArray([
            'Title'     => 'Schriftenreihe',
            'Infobox'   => 'Beschreibung',
            'Visible'   => 1,
            'SortOrder' => 2,
        ]);

        $this->assertNotNull($series);
        $this->assertInstanceOf(SeriesInterface::class, $series);

        $this->assertEquals('Schriftenreihe', $series->getTitle());
        $this->assertEquals('Beschreibung', $series->getInfobox());
        $this->assertEquals(1, $series->getVisible());
        $this->assertEquals(2, $series->getSortOrder());
    }
}
