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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_DocumentFinder.
 *
 * @package Opus
 * @category Tests
 *
 * @group DocumentTest
 *
 */
class Opus_DocumentFinderTest extends TestCase {

    private static function prepareDocuments() {
        $publishedDoc1 = new Opus_Document();
        $publishedDoc1->setType("preprint")
                ->setServerState('published')
                ->store();

        $publishedDoc2 = new Opus_Document();
        $publishedDoc2->setType("article")
                ->setServerState('published')
                ->store();

        $unpublishedDoc1 = new Opus_Document();
        $unpublishedDoc1->setType("doctoral_thesis")
                ->setServerState('unpublished')
                ->store();

        $unpublishedDoc2 = new Opus_Document();
        $unpublishedDoc2->setType("preprint")
                ->setServerState('unpublished')
                ->store();

        $deletedDoc1 = new Opus_Document();
        $deletedDoc1->setType("article")
                ->setServerState('deleted')
                ->store();

        $deletedDoc2 = new Opus_Document();
        $deletedDoc2->setType("doctoral_thesis")
                ->setServerState('deleted')
                ->store();
    }

    private function checkServerState($ids, $state) {
        foreach ($ids AS $id) {
            $doc = new Opus_Document($id);
            $this->assertEquals($state, $doc->getServerState());
        }
    }

    /**
     * Basic functionality
     *
     * @return void
     */
    public function testCountOnEmptyDb() {
        $finder = new Opus_DocumentFinder();
        $this->assertEquals(0, $finder->count());
    }

    /**
     * Basic functionality
     *
     * @return void
     */
    public function testIdsOnEmptyDb() {
        $finder = new Opus_DocumentFinder();
        $this->assertEquals(array(), $finder->ids());
    }

    /**
     * Basic functionality
     *
     * @return void
     */
    public function testAllEntriesNoConstraints() {
        $this->prepareDocuments();

        // published
        $finder = new Opus_DocumentFinder();
        $this->assertEquals(6, $finder->count());
        $this->assertEquals(6, count($finder->ids()));
    }

    /**
     * Basic functionality
     *
     * @return void
     */
    public function testAllConstraints() {
        // published
        $finder = new Opus_DocumentFinder();
        $finder->setEnrichmentKeyExists('foobar')
               ->setEnrichmentKeyValue('foo', 'bar')
               ->setIdRange(1, 2)
               ->setIdRangeStart(2)
               ->setIdRangeEnd(1)
               ->setIdentifierTypeValue('opus-3', 23)
               ->setServerState('published')
               ->setServerStateInList(array('published'))
               ->setType('fooprintbar')
               ->setTypeInList(array('fooprintbar'))
               ->setServerDateModifiedRange('2010-01-01', '2000-01-01')
               ->setServerDatePublishedRange('1999-12-31', '1900-01-01')
               ->setIdSubset(null)
               ->setIdSubset(array())
               ->setIdSubset(array(1))
               ->setIdSubset(array(-1))
               ->setIdSubset(array(1, 2))
               ->setIdSubset(array('foo'));
                
        $this->assertEquals(0, $finder->count());
        $this->assertEquals(0, count($finder->ids()));
    }

    /**
     * Basic functionality
     *
     * @return void
     */
    public function testIdsByState() {
        $this->prepareDocuments();

        // published
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('published');
        $this->assertEquals(2, $finder->count());

        $publishedDocs = $finder->ids();
        $this->assertEquals(2, count($publishedDocs));
        $this->checkServerState($publishedDocs, 'published');

        // unpublished
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('unpublished');
        $this->assertEquals(2, count($finder->ids()));

        $unpublishedDocs = $finder->ids();
        $this->assertEquals(2, count($unpublishedDocs));
        $this->checkServerState($unpublishedDocs, 'unpublished');

        // deleted
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('deleted');
        $this->assertEquals(2, count($finder->ids()));

        $deletedDocs = $finder->ids();
        $this->assertEquals(2, count($deletedDocs));
        $this->checkServerState($deletedDocs, 'deleted');
    }

    /**
     * Extended functionality: Grouping
     *
     * @return void
     */
    public function testGroupedDocumentTypes() {
        $this->prepareDocuments();

        // all
        $finder = new Opus_DocumentFinder();
        $types = $finder->groupedTypes();
        $this->assertEquals(3, count($types));

        // published
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('published');
        $types = $finder->groupedTypes();
        $this->assertEquals(2, count($types));

        // unpublished
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('unpublished');
        $types = $finder->groupedTypes();
        $this->assertEquals(2, count($types));

        // deleted
        $finder = new Opus_DocumentFinder();
        $finder->setServerState('deleted');
        $types = $finder->groupedTypes();
        $this->assertEquals(2, count($types));
    }

    /**
     * Extended functionality: Sorting
     *
     * @return void
     */
    public function testCountsAfterSort() {
        $this->prepareDocuments();

        // By Author
        $finder = new Opus_DocumentFinder();
        $finder->orderByAuthorLastname();
        $this->assertEquals(6, count($finder->ids()));

        // By Id
        $finder = new Opus_DocumentFinder();
        $finder->orderById();
        $this->assertEquals(6, count($finder->ids()));

        // By ServerDatePublished
        $finder = new Opus_DocumentFinder();
        $finder->orderByServerDatePublished();
        $this->assertEquals(6, count($finder->ids()));

        // By TitleMain
        $finder = new Opus_DocumentFinder();
        $finder->orderByTitleMain();
        $this->assertEquals(6, count($finder->ids()));

        // By DocumentType
        $finder = new Opus_DocumentFinder();
        $finder->orderByType();
        $this->assertEquals(6, count($finder->ids()));
    }
}
