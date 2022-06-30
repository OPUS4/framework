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
 * @copyright   Copyright (c) 2011-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus
 */

namespace OpusTest;

use DateTime;
use Opus\Collection;
use Opus\CollectionRole;
use Opus\Date;
use Opus\Db\DocumentXmlCache;
use Opus\Document;
use Opus\DocumentFinder\DefaultDocumentFinder;
use Opus\DocumentFinderInterface;
use Opus\Enrichment;
use Opus\File;
use Opus\Licence;
use Opus\Model\ModelException;
use Opus\Person;
use Opus\Security\SecurityException;
use Opus\Title;
use OpusTest\TestAsset\TestCase;

use function count;
use function date;
use function in_array;
use function rand;
use function strtotime;
use function time;

/**
 * Test cases for class Opus\DocumentFinde\DefaultDocumentFinder2.
 *
 * @package Opus
 * @category Tests
 * @group DocumentTest
 */
class DefaultDocumentFinderTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, [
            'documents',
            'persons',
            'link_persons_documents',
            'document_title_abstracts',
            'document_identifiers',
            'document_enrichments',
            'enrichmentkeys',
            'collections_roles',
            'collections',
            'link_documents_collections',
            'document_xml_cache',
        ]);
    }

    /**
     * @return DefaultDocumentFinder
     */
    private function createDocumentFinder()
    {
        return new DefaultDocumentFinder();
    }

    private function prepareDocuments()
    {
        $publishedDoc1 = Document::new();
        $publishedDoc1->setType("preprint")
                ->setServerState('published')
                ->setBelongsToBibliography(true)
                ->store();

        $title = $publishedDoc1->addTitleMain();
        $title->setValue('Title 1');
        $title->setLanguage('deu');

        $title = $publishedDoc1->addTitleMain();
        $title->setValue('Title 2');
        $title->setLanguage('eng');

        $publishedDoc1->store();

        $publishedDoc2 = Document::new();
        $publishedDoc2->setType("article")
                ->setServerState('published')
                ->store();

        $title = $publishedDoc2->addTitleMain();
        $title->setValue('A Title 1');
        $title->setLanguage('deu');

        $publishedDoc2->store();

        $unpublishedDoc1 = Document::new();
        $unpublishedDoc1->setType("doctoral_thesis")
                ->setServerState('unpublished')
                ->store();

        $person = new Person();
        $person->setLastName('B');
        $unpublishedDoc1->addPersonAuthor($person);

        $unpublishedDoc1->store();

        $unpublishedDoc2 = Document::new();
        $unpublishedDoc2->setType("preprint")
                ->setServerState('unpublished')
                ->store();

        $person = new Person();
        $person->setLastName('C');
        $unpublishedDoc2->addPersonAuthor($person);

        $person = new Person();
        $person->setLastName('A');
        $unpublishedDoc2->addPersonAuthor($person);

        $unpublishedDoc2->store();

        $deletedDoc1 = Document::new();
        $deletedDoc1->setType("article")
                ->setServerState('deleted')
                ->store();

        $deletedDoc2 = Document::new();
        $deletedDoc2->setType("doctoral_thesis")
                ->setServerState('deleted')
                ->store();
    }

    /**
     * @param int[]  $ids
     * @param string $state
     * @throws ModelException
     */
    private function checkServerState($ids, $state)
    {
        foreach ($ids as $id) {
            $doc = new Document($id);
            $this->assertEquals($state, $doc->getServerState());
        }
    }

    /**
     * Basic functionality
     */
    public function testCountOnEmptyDb()
    {
        $finder = $this->createDocumentFinder();
        $this->assertEquals(0, $finder->getCount());
    }

    /**
     * Basic functionality
     */
    public function testIdsOnEmptyDb()
    {
        $finder = $this->createDocumentFinder();
        $this->assertEquals([], $finder->getIds());
    }

    /**
     * Basic functionality
     */
    public function testAllEntriesNoConstraints()
    {
        $this->prepareDocuments();

        // published
        $finder = $this->createDocumentFinder();
        $this->assertEquals(6, $finder->getCount());
        $this->assertEquals(6, count($finder->getIds()));
    }

    /**
     * Basic functionality
     */
    public function testAllConstraints()
    {
        $this->markTestSkipped('TODO DOCTRINE DBAL Issue #129: Does this test even make sense?');

        // published
        $finder = $this->createDocumentFinder();
        $finder->setEnrichmentKeyExists('foobar')
               ->setEnrichmentKeyValue('foo', 'bar')
               ->setIdRange(1, 2)
               ->setIdRangeStart(2)
               ->setIdRangeEnd(1)
               ->setIdentifierTypeValue('opus-3', 23)
               ->setServerState('published')
               ->setServerStateInList(['published'])
               ->setType('fooprintbar')
               ->setTypeInList(['fooprintbar'])
               ->setServerDateModifiedRange('2010-01-01', '2000-01-01')
               ->setServerDatePublishedRange('1999-12-31', '1900-01-01')
               ->setIdSubset(null)
               ->setIdSubset([])
               ->setIdSubset([1])
               ->setIdSubset([-1])
               ->setIdSubset([1, 2])
               ->setIdSubset(['foo']);

        $this->assertEquals(0, $finder->getCount());
        $this->assertEquals(0, count($finder->getIds()));
    }

    public function testIdsByState()
    {
        $this->prepareDocuments();

        // published
        $finder = $this->createDocumentFinder();
        $finder->setServerState('published');
        $this->assertEquals(2, $finder->getCount());

        $publishedDocs = $finder->getIds();
        $this->assertEquals(2, count($publishedDocs));
        $this->checkServerState($publishedDocs, 'published');

        // unpublished
        $finder = $this->createDocumentFinder();
        $finder->setServerState('unpublished');
        $this->assertEquals(2, count($finder->getIds()));

        $unpublishedDocs = $finder->getIds();
        $this->assertEquals(2, count($unpublishedDocs));
        $this->checkServerState($unpublishedDocs, 'unpublished');

        // deleted
        $finder = $this->createDocumentFinder();
        $finder->setServerState('deleted');
        $this->assertEquals(2, count($finder->getIds()));

        $deletedDocs = $finder->getIds();
        $this->assertEquals(2, count($deletedDocs));
        $this->checkServerState($deletedDocs, 'deleted');
    }

    public function testSubsetOfDocumentIds()
    {
        for ($i = 0; $i < 10; $i++) {
            $document = Document::new();
            $document->setType('book');
            $title = $document->addTitleMain();
            $title->setValue('Title' . $i);
            $title->setLanguage('de');
            $document->store();
        }

        $finder = $this->createDocumentFinder();
        $finder->setDocumentIds([1, 3, 5, 7, 9]);
        $this->assertEquals([1, 3, 5, 7, 9], $finder->getIds());

        $finder = $this->createDocumentFinder();
        $finder->setDocumentIdRange(3, 7);
        $this->assertEquals([3, 4, 5, 6, 7], $finder->getIds());
    }

    public function testIdentifierExists()
    {
        $document = Document::new();
        $isbn     = $document->addIdentifierIsbn();
        $isbn->setValue('1234-1234-1234');
        $document->store();

        $document = Document::new();
        $issn     = $document->addIdentifierIssn();
        $issn->setValue('2345-2345-2345');
        $doi = $document->addIdentifierDoi();
        $doi->setValue('3576934857');
        $document->store();

        $document = Document::new();
        $doi      = $document->addIdentifierDoi();
        $doi->setValue('1234567890');
        $document->store();

        $document = Document::new();
        $issn     = $document->addIdentifierIssn();
        $issn->setValue('5678-5678-5678');
        $document->store();

        $finder = $this->createDocumentFinder();
        $finder->setIdentifierExists('issn');
        $this->assertEquals(2, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setIdentifierExists('issn');
        $finder->setIdentifierExists('doi');
        $this->assertEquals(1, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setIdentifierExists('isbn');
        $finder->setIdentifierExists('doi');
        $this->assertEquals(0, $finder->getCount());
    }

    public function testIdentifierValue()
    {
        $document = Document::new();
        $document->setType("article");

        $title = $document->addTitleMain();
        $title->setValue('Title');
        $title->setLanguage('de');

        $isbn = $document->addIdentifierIsbn();
        $isbn->setValue('111-111-111');

        $issn1 = $document->addIdentifierIssn();
        $issn1->setValue('1000-1000-1000');

        $issn2 = $document->addIdentifierIssn();
        $issn2->setValue('2000-2000-2000');

        $document->store();

        $finder = $this->createDocumentFinder();
        $finder->setIdentifierValue('isbn', '111-111-111');
        $this->assertEquals(1, count($finder->getIds()));

        $finder = $this->createDocumentFinder();
        $finder->setDocumentType('article');
        $finder->setIdentifierValue('issn', '123-123-123');
        $finder->setIdentifierValue('issn', '2000-2000-2000');
        $this->assertEquals(0, count($finder->getIds()));
    }

    public function testEnrichments()
    {
        $enrichment1 = new Enrichment();
        $enrichment1->setKeyName('enrichmentKey1');
        $enrichment1->setValue('enrichment-value1');

        $enrichment2 = new Enrichment();
        $enrichment2->setKeyName('enrichmentKey2');
        $enrichment2->setValue('enrichment-value2');

        $doc1 = Document::new();
        $doc1->addEnrichment($enrichment1);
        $doc1->addEnrichment($enrichment2);
        $doc1Id = $doc1->store();

        $enrichment3 = new Enrichment();
        $enrichment3->setKeyName('enrichmentKey1');
        $enrichment3->setValue('enrichment-value1');

        $doc2 = Document::new();
        $doc2->addEnrichment($enrichment3);
        $doc2->store();

        $finder = $this->createDocumentFinder();
        $finder->setEnrichmentExists('enrichmentKey1');
        $this->assertEquals(2, $finder->getCount());
        $finder->setEnrichmentExists('enrichmentKey2');
        $this->assertEquals(1, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setEnrichmentValue('enrichmentKey2', 'enrichment-value2');
        $this->assertEquals([$doc1Id], $finder->getIds());
    }

    public function testServerDatePublished()
    {
        $doc = Document::new();
        $doc->setServerDatePublished('2022-01-01');
        $doc->store();

        $doc = Document::new();
        $doc->setServerDatePublished('2021-10-20');
        $doc->store();

        $doc = Document::new();
        $doc->setServerDatePublished('2021-08-10');
        $doc->store();

        $doc = Document::new();
        $doc->setServerDatePublished('2021-07-08');
        $doc->store();

        $doc = Document::new();
        $doc->setServerDatePublished('2021-01-01');
        $doc->store();

        $finder = $this->createDocumentFinder();
        $finder->setServerDatePublishedBefore('2021-08-30');
        $this->assertEquals(3, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setServerDatePublishedRange('2021-07-01', '2021-10-30');
        $this->assertEquals(3, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $this->assertEquals(['2022', '2021'], $finder->getYearsPublished());
    }

    public function testServerDateModified()
    {
        $doc = Document::new();
        $id  = $doc->store();
        Document::setServerDateModifiedByIds(new Date('2022-01-01'), [$id]);

        $doc = Document::new();
        $id  = $doc->store();
        Document::setServerDateModifiedByIds(new Date('2021-10-20'), [$id]);

        $doc = Document::new();
        $id  = $doc->store();
        Document::setServerDateModifiedByIds(new Date('2021-08-10'), [$id]);

        $doc = Document::new();
        $id  = $doc->store();
        Document::setServerDateModifiedByIds(new Date('2021-07-08'), [$id]);

        $doc = Document::new();
        $id  = $doc->store();
        Document::setServerDateModifiedByIds(new Date('2021-01-01'), [$id]);

        $finder = $this->createDocumentFinder();
        $finder->setServerDateModifiedBefore('2021-08-30');
        $this->assertEquals(3, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setServerDateModifiedAfter('2021-08-01');
        $this->assertEquals(3, $finder->getCount());
    }

    public function testBelongsToBibliography()
    {
        $this->prepareDocuments();

        $finder = $this->createDocumentFinder();
        $finder->setServerState('published');
        $finder->setBelongsToBibliography(true);
        $this->assertEquals(1, $finder->getCount());
    }

    /**
     * Extended functionality: Grouping
     */
    public function testGroupedDocumentTypes()
    {
        $this->prepareDocuments();

        // all
        $finder = $this->createDocumentFinder();
        $types  = $finder->getDocumentTypes();
        $this->assertEquals(3, count($types));

        // published
        $finder = $this->createDocumentFinder();
        $finder->setServerState('published');
        $types = $finder->getDocumentTypes();
        $this->assertEquals(2, count($types));

        // unpublished
        $finder = $this->createDocumentFinder();
        $finder->setServerState('unpublished');
        $types = $finder->getDocumentTypes();
        $this->assertEquals(2, count($types));

        // deleted
        $finder = $this->createDocumentFinder();
        $finder->setServerState('deleted');
        $types = $finder->getDocumentTypes();
        $this->assertEquals(2, count($types));
    }

    /**
     * Extended functionality: Sorting
     */
    public function testSortByAuthorLastName()
    {
        $this->prepareDocuments();

        // By Author
        $finder = $this->createDocumentFinder();

        $finder->setOrder(DocumentFinderInterface::ORDER_AUTHOR);

        $docs = $finder->getIds();

        $this->assertEquals(6, count($docs));

        $this->assertEquals(4, $docs[4]);
        $this->assertEquals(3, $docs[5]);
    }

    public function testCollections()
    {
        $collectionRoles = [];
        $collections     = [];

        for ($i = 0; $i < 4; $i++) {
            $collectionRole = new CollectionRole();
            $collectionRole->setName("role-name-" . rand());
            $collectionRole->setOaiName("role-oainame-" . rand());
            $collectionRole->setVisible(1);
            $collectionRole->setVisibleBrowsingStart(1);
            $collectionRole->store();

            $collectionRoles[$i] = $collectionRole;

            $collection = $collectionRole->addRootCollection();
            $collection->setTheme('dummy');
            $collection->store();

            $collections[] = $collection;
        }

        $doc1 = Document::new();
        $doc1->addCollection($collections[1]);
        $doc1->store();

        $doc2 = Document::new();
        $doc2->addCollection($collections[0]);
        $doc2->store();

        $doc3 = Document::new();
        $doc3->setType('article');
        $doc3->addCollection($collections[2]);
        $doc3->addCollection($collections[1]);
        $doc3->store();

        $doc4 = Document::new();
        $doc4->setType('article');
        $doc4->addCollection($collections[2]);
        $doc4->store();

        $finder = $this->createDocumentFinder();
        $finder->setCollectionId($collections[2]->getId());
        $this->assertEquals(2, $finder->getCount());
        $finder->setCollectionId($collections[1]->getId());
        $this->assertEquals(1, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setDocumentType('article');
        $finder->setCollectionId($collections[1]->getId());
        $this->assertEquals(1, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setCollectionId($collectionRoles[1]->getId());
        $this->assertEquals(2, $finder->getCount());
        $finder->setCollectionId($collectionRoles[2]->getId());
        $this->assertEquals(1, $finder->getCount());

        $finder = $this->createDocumentFinder();
        $finder->setDocumentType('article');
        $finder->setCollectionId($collectionRoles[1]->getId());
        $this->assertEquals(1, $finder->getCount());
    }

    public function testNotInXmlCache()
    {
        $documentIds = [];

        for ($i = 0; $i < 4; $i++) {
            $doc             = Document::new();
            $documentIds[$i] = $doc->store();
        }

        $xmlCache = new DocumentXmlCache();
        $xmlCache->delete('document_id = ' . $documentIds[2]);

        $finder = $this->createDocumentFinder();
        $finder->setNotInXmlCache();

        $this->assertEquals(1, $finder->getCount());
    }

    public function testSortById()
    {
        $this->prepareDocuments();

        // By Id
        $finder = $this->createDocumentFinder();

        $finder->setOrder(DocumentFinderInterface::ORDER_ID);

        $docs = $finder->getIds();

        $this->assertEquals(6, count($docs));

        $lastDoc = $docs[0];

        foreach ($docs as $docId) {
            if ($lastDoc > $docId) {
                $this->fail('documents are not sorted by id');
            }
        }
    }

    /**
     * @throws ModelException
     * @throws SecurityException
     *
     * TODO the testdata for this text is not meaningfull
     */
    public function testSortByServerDatePublished()
    {
        $this->prepareDocuments();

        // By ServerDatePublished
        $finder = $this->createDocumentFinder();

        $finder->setOrder(DocumentFinderInterface::ORDER_SERVER_DATE_PUBLISHED);

        $docs = $finder->getIds();

        $this->assertEquals(6, count($docs));

        $lastDate = null;

        foreach ($docs as $docId) {
            $doc = new Document($docId);
            if ($lastDate === null) {
                $lastDate = $doc->getServerDatePublished();
            }

            if ($lastDate !== null && $lastDate->compare($doc->getServerDatePublished()) === 1) {
                $this->fail('documents are not sorted properly');
            }
        }
    }

    public function testSortByTitleMain()
    {
        $this->prepareDocuments();

        // By TitleMain
        $finder = $this->createDocumentFinder();

        $finder->setOrder(DocumentFinderInterface::ORDER_TITLE);

        $docs = $finder->getIds();

        $this->assertEquals(6, count($docs));

        // documents without title come first (0-3)
        $this->assertEquals(2, $docs[4]);
        $this->assertEquals(1, $docs[5]);
    }

    public function testSortByType()
    {
        $this->prepareDocuments();

        // By DocumentType
        $finder = $this->createDocumentFinder();

        $finder->setOrder(DocumentFinderInterface::ORDER_DOCUMENT_TYPE);

        $docs = $finder->getIds();

        $this->assertEquals(6, count($docs));

        $expectedOrder = [2, 5, 3, 6, 1, 4];

        foreach ($docs as $index => $docId) {
            if ((int) $docId !== $expectedOrder[$index]) {
                $this->fail('documents are not in expected order');
            }
        }
    }

    /**
     * test for added functionality setServerDateCreated[Before|After]()
     */
    public function testFindByDateCreated()
    {
        $this->markTestSkipped(
            'TODO DOCTRINE DBAL Issue #129: Function setServerDateCreatedAfter() and setServerDateCreatedBefore()'
            . ' are no part of the DocumentFinderInterface'
        );

        $this->prepareDocuments();
        $date = new Date();
        $date->setNow();
        $date->setDay(date('d') - 1);
        $date->setHour(date('H') - 1);

        $finder = $this->createDocumentFinder();
        $this->assertEquals(6, $finder->getCount());
        $finder->setServerDateCreatedAfter(date("Y-m-d", time() + (60 * 60 * 24)));
        $this->assertEquals(0, $finder->getCount());
        $finder = $this->createDocumentFinder();
        $finder->setServerDateCreatedAfter(date("Y-m-d", time() - (60 * 60 * 24)));
        $this->assertEquals(6, $finder->getCount());
        $finder = $this->createDocumentFinder();
        $finder->setServerDateCreatedBefore(date("Y-m-d", time() - (60 * 60 * 24)));
        $this->assertEquals(0, $finder->getCount());
        $finder = $this->createDocumentFinder();
        $finder->setServerDateCreatedBefore(date("Y-m-d", time() + (60 * 60 * 24)));
        $this->assertEquals(6, $finder->getCount());
    }

    public function testSetDependentModel()
    {
        $this->markTestSkipped('TODO DOCTRINE DBAL Issue #129: Function setDependentModel() is no part of the DocumentFinderInterface');

        $docIds   = [];
        $doc1     = Document::new();
        $docIds[] = $doc1->setType("article")
                ->setServerState('published')
                ->store();

        $doc2     = Document::new();
        $docIds[] = $doc2->setType("article")
                ->setServerState('unpublished')
                ->store();

        $doc3     = Document::new();
        $docIds[] = $doc3->setType("preprint")
                ->setServerState('unpublished')
                ->store();

        // test dependent model
        $title = $doc3->addTitleMain();
        $title->setValue('Ein deutscher Titel');
        $title->setLanguage('deu');
        $titleId = $title->store();

        $title        = new Title($titleId);
        $docfinder    = $this->createDocumentFinder();
        $resultDocIds = $docfinder->setDependentModel($title)->getIds();
        $this->assertEquals(1, count($resultDocIds), 'Excpected 1 ID in result');
        $this->assertTrue(in_array($doc3->getId(), $resultDocIds), 'Expected Document-ID in result set');
        $this->assertFalse(in_array($doc1->getId(), $resultDocIds), 'Expected Document-ID not in result set');
        $this->assertFalse(in_array($doc2->getId(), $resultDocIds), 'Expected Document-ID not in result set');

        // test linked model
        //person
        $author = new Person();
        $author->setFirstName('Karl');
        $author->setLastName('Tester');
        $author->setDateOfBirth('1857-11-26');
        $author->setPlaceOfBirth('Genf');

        $doc2->addPersonAuthor($author);
        $doc2->store();

        $docfinder    = $this->createDocumentFinder();
        $resultDocIds = $docfinder->setDependentModel($author)->getIds();
        $this->assertEquals(1, count($resultDocIds), 'Excpected 1 ID in result');
        $this->assertTrue(in_array($doc2->getId(), $resultDocIds), 'Expected Document-ID in result set');
        $this->assertFalse(in_array($doc1->getId(), $resultDocIds), 'Expected Document-ID not in result set');
        $this->assertFalse(in_array($doc3->getId(), $resultDocIds), 'Expected Document-ID not in result set');

        // licence
        $licence = new Licence();
        $licence->setNameLong('LongNameLicence');
        $licence->setLinkLicence('http://licence.link');
        $licenceId = $licence->store();
        $doc1->addLicence($licence);
        $doc1->store();

        $licence      = new Licence($licenceId);
        $docfinder    = $this->createDocumentFinder();
        $resultDocIds = $docfinder->setDependentModel($licence)->getIds();

        $this->assertEquals(1, count($resultDocIds), 'Excpected 1 ID in result');
        $this->assertTrue(in_array($doc1->getId(), $resultDocIds), 'Expected Document-ID in result set');
        $this->assertFalse(in_array($doc2->getId(), $resultDocIds), 'Expected Document-ID not in result set');
        $this->assertFalse(in_array($doc3->getId(), $resultDocIds), 'Expected Document-ID not in result set');

        $doc2->addLicence($licence);
        $doc2->store();

        $resultDocIds = $docfinder->getIds();

        $this->assertEquals(2, count($resultDocIds), 'Excpected 2 IDs in result');
        $this->assertTrue(in_array($doc1->getId(), $resultDocIds), 'Expected Document-ID in result set');
        $this->assertTrue(in_array($doc2->getId(), $resultDocIds), 'Expected Document-ID in result set');
        $this->assertFalse(in_array($doc3->getId(), $resultDocIds), 'Expected Document-ID not in result set');

        // collections (are implemented differently)
        $collectionRole = new CollectionRole();
        $collectionRole->setName("role-name-" . rand());
        $collectionRole->setOaiName("role-oainame-" . rand());
        $collectionRole->setVisible(1);
        $collectionRole->setVisibleBrowsingStart(1);
        $collectionRoleId = $collectionRole->store();

        $collection = $collectionRole->addRootCollection();
        $collection->setTheme('dummy');
        $collectionId = $collection->store();

        $doc1->addCollection($collection);
        $doc1->store();
        $doc3->addCollection($collection);
        $doc3->store();

        $collection   = new Collection($collectionId);
        $docfinder    = $this->createDocumentFinder();
        $resultDocIds = $docfinder->setDependentModel($collection)->getIds();

        $this->assertEquals(2, count($resultDocIds), 'Excpected 2 IDs in result');
        $this->assertTrue(in_array($doc1->getId(), $resultDocIds), 'Expected Document-ID in result set');
        $this->assertFalse(in_array($doc2->getId(), $resultDocIds), 'Expected Document-ID not in result set');
        $this->assertTrue(in_array($doc3->getId(), $resultDocIds), 'Expected Document-ID in result set');
    }

    public function testSetHasFilesVisibleInOai()
    {
        $visibleFileDoc = Document::new();
        $visibleFile    = new File();

        $visibleFile->setPathName('visible_file.txt');
        $visibleFile->setVisibleInOai(true);

        $visibleFileDoc->addFile($visibleFile);

        $invisibleFileDoc = Document::new();
        $invisibleFile    = new File();

        $invisibleFile->setPathName('invisible_file.txt');
        $invisibleFile->setVisibleInOai(false);

        $invisibleFileDoc->addFile($invisibleFile);

        $visibleFileDocId   = $visibleFileDoc->store();
        $invisibleFileDocId = $invisibleFileDoc->store();

        $mixedFileDoc = Document::new();
        $visibleFile  = new File();

        $visibleFile->setPathName('another_visible_file.txt');
        $visibleFile->setVisibleInOai(true);

        $invisibleFile = new File();

        $invisibleFile->setPathName('another_invisible_file.txt');
        $invisibleFile->setVisibleInOai(false);

        $mixedFileDoc->addFile($visibleFile);
        $mixedFileDoc->addFile($invisibleFile);

        $mixedFileDocId = $mixedFileDoc->store();

        $docfinder = $this->createDocumentFinder();
        $docfinder->setHasFilesVisibleInOai();
        $foundIds = $docfinder->getIds();

        $this->assertTrue(in_array($visibleFileDocId, $foundIds), 'Expected id of Document with visible file in OAI');
        $this->assertTrue(in_array($mixedFileDocId, $foundIds), 'Expected id of Document with visible and invisible file in OAI');
        $this->assertFalse(in_array($invisibleFileDocId, $foundIds), 'Expected no id of Document with invisible file in OAI');
    }

    public function testSetEmbargoDateBefore()
    {
        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-16');
        $doc1Id = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-14');
        $doc2Id = $doc->store();

        $docfinder = $this->createDocumentFinder();
        $docfinder->setEmbargoDateBefore('2016-10-15');
        $foundIds = $docfinder->getIds();

        $this->assertCount(1, $foundIds);
        $this->assertContains($doc2Id, $foundIds);
        $this->assertNotContains($doc1Id, $foundIds);
    }

    public function testSetEmbargoDateAfter()
    {
        $this->markTestSkipped('TODO DOCTRINE DBAL Issue #129: Function setEmbargoDateAfter() is no part of the DocumentFinderInterface');

        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-16');
        $doc1Id = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-14');
        $doc2Id = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-15');
        $doc3Id = $doc->store();

        $docfinder = $this->createDocumentFinder();
        $docfinder->setEmbargoDateAfter('2016-10-15');
        $foundIds = $docfinder->getIds();

        $this->assertCount(2, $foundIds);
        $this->assertContains($doc1Id, $foundIds);
        $this->assertContains($doc3Id, $foundIds);
        $this->assertNotContains($doc2Id, $foundIds);
    }

    public function testSetEmbargoDateRange()
    {
        $this->markTestSkipped('TODO DOCTRINE DBAL Issue #129: Function setEmbargoDateRange() is no part of the DocumentFinderInterface');

        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-16'); // not in range
        $doc1Id = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-13'); // not in range
        $doc2Id = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate('2016-10-14'); // in range
        $doc3Id = $doc->store();

        $docfinder = $this->createDocumentFinder();
        $docfinder->setEmbargoDateRange('2016-10-14', '2016-10-16');
        $foundIds = $docfinder->getIds();

        $this->assertCount(1, $foundIds);
        $this->assertContains($doc3Id, $foundIds);
        $this->assertNotContains($doc1Id, $foundIds);
        $this->assertNotContains($doc2Id, $foundIds);
    }

    /**
     * Tests from a perspective of two days in the future to avoid the need to manipulate ServerDateModified.
     */
    public function testFindDocumentsWithExpiredEmbargoDateForUpdatingServerDateModified()
    {
        $tomorrow         = date('Y-m-d', time() + (60 * 60 * 24));
        $dayaftertomorrow = date('Y-m-d', time() + (2 * 60 * 60 * 24));
        $today            = date('Y-m-d', time());
        $yesterday        = date('Y-m-d', time() - (60 * 60 * 24));

        $doc = Document::new();
        $doc->setEmbargoDate($dayaftertomorrow);
        $notExpiredId = $doc->store(); // not in result - not yet expired embargo

        $doc = Document::new();
        $doc->setEmbargoDate($yesterday);
        $expiredUpdatedId = $doc->store(); // not in result - expired and saved after expiration

        $doc = Document::new();
        $doc->setEmbargoDate($tomorrow);
        $expiredNotUpdatedId = $doc->store(); // in result -  expired and saved before expiration

        $docfinder = $this->createDocumentFinder();
        $docfinder->setEmbargoDateBefore($dayaftertomorrow);
        $docfinder->setNotModifiedAfterEmbargoDate();
        $foundIds = $docfinder->getIds();

        $this->assertContains($expiredNotUpdatedId, $foundIds);
        $this->assertNotContains($expiredUpdatedId, $foundIds);
        $this->assertNotContains($notExpiredId, $foundIds);
    }

    public function testSetEmbargoDateBeforeWithTime()
    {
        $now = new Date();
        $now->setNow();

        $past = new Date();
        $past->setDateTime(new DateTime(date('Y-m-d H:i:s', strtotime('-1 hour'))));

        $future = new Date();
        $future->setDateTime(new DateTime(date('Y-m-d H:i:s', strtotime('+1 hour'))));

        $doc = Document::new();
        $doc->setEmbargoDate($past);
        $pastId = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate($now);
        $nowId = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate($future);
        $futureId = $doc->store();

        $docfinder = $this->createDocumentFinder();
        $docfinder->setEmbargoDateBefore($now);
        $foundIds = $docfinder->getIds();

        $this->assertContains($pastId, $foundIds);
        $this->assertNotContains($nowId, $foundIds);
        $this->assertNotContains($futureId, $foundIds);
    }

    public function testFindDocumentsForXMetaDissPlus()
    {
        $today = date('Y-m-d', time());

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->setType('article');
        $publishedId = $doc->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->setType('periodical');
        $periodicalId = $doc->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->setType('article');
        $doc->setEmbargoDate($today); // today still in embargo until tomorrow
        $embargoedId = $doc->store();

        $doc = Document::new();
        $doc->setServerState('unpublished');
        $unpublishedId = $doc->store();

        $docfinder = $this->createDocumentFinder();

        $docfinder->setServerState('published');
        $docfinder->setDocumentType('article');
        $docfinder->setNotEmbargoedOn($today);

        $foundIds = $docfinder->getIds();

        $this->assertCount(1, $foundIds);
        $this->assertContains($publishedId, $foundIds);
    }
}
