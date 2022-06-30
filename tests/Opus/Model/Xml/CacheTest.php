<?php

/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @copyright   Copyright (c) 2009-2019
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model\Xml;

use DateInterval;
use DateTime;
use DOMDocument;
use Opus\Common\Model\ModelException;
use Opus\Date;
use Opus\Db\DocumentXmlCache;
use Opus\Document;
use Opus\Licence;
use Opus\Model\Xml\Cache;
use Opus\Person;
use Opus\TitleAbstract;
use OpusTest\TestAsset\TestCase;

use function array_key_exists;
use function count;
use function fclose;
use function filesize;
use function fopen;
use function fread;
use function libxml_use_internal_errors;
use function mt_rand;
use function rand;

/**
 * Search Hit model class.
 *
 * @category    Framework
 * @package     Opus\Model\Xml
 */
class CacheTest extends TestCase
{
    /**
     * Holds generated cache entries for verifying.
     *
     * @var array
     */
    private $allEntries = [];

    /**
     * Defines how many cache entries should be genereated and / or available
     *
     * @var int
     */
    private $maxEntries = 5;

    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, ['document_xml_cache', 'documents', 'document_title_abstracts']);
    }

    /**
     * Fill cache with some "random" data
     */
    private function fillCache()
    {
        // initial test setup
        $table = new DocumentXmlCache();
        for ($i = 0; $i < $this->maxEntries; $i++) {
            $dateTime           = (new DateTime())->add(new DateInterval('PT' . rand(1, 59) . 'S'));
            $data               = [
                'document_id'          => $i + 1,
                'server_date_modified' => (new Date($dateTime))->getIso(),
                'xml_version'          => $i % 2 ? 1 : 2,
                'xml_data'             => '<Opus><Opus_Document><Foo/></Opus_Document></Opus>',
            ];
            $this->allEntries[] = $data;
            $table->insert($data);
        }
    }

    /**
     * Returns a random data set.
     *
     * @return array
     */
    private function getRandomDataSet()
    {
        do {
            $testId = mt_rand(0, $this->maxEntries);
        } while (false === array_key_exists($testId, $this->allEntries));

        return $this->allEntries[$testId];
    }

    /**
     * Test if an empty cache is empty.
     */
    public function testCacheInitiallyEmpty()
    {
        $cache        = new Cache();
        $cacheEntries = $cache->getAllEntries();

        $this->assertEquals([], $cacheEntries, 'Xml cache is not empty.');
    }

    /**
     * Test if a given set of data are properly returned.
     */
    public function testGetAllEntriesReturnsAllCacheEntries()
    {
        // initial test setup
        $this->fillCache();

        $cache        = new Cache();
        $cacheEntries = $cache->getAllEntries();

        $this->assertEquals(
            $this->maxEntries,
            count($cacheEntries),
            'Expecting ' . $this->maxEntries . ' inside cache.'
        );
        $this->assertEquals($this->allEntries, $cacheEntries, 'Getting unexpected cache entries.');
    }

    public function testHasValidEntryReturnsTrueOnCacheHit()
    {
        // initial test setup
        $this->fillCache();
        $dataSet = $this->getRandomDataSet();

        $cache      = new Cache();
        $validEntry = $cache->hasValidEntry(
            $dataSet['document_id'],
            $dataSet['xml_version'],
            $dataSet['server_date_modified']
        );

        $this->assertTrue($validEntry, 'Expecting a cache hit.');
    }

    public function testHasValidEntryReturnsFalseOnMissedCacheHitWithEmptyCache()
    {
        $cache        = new Cache();
        $invalidEntry = $cache->hasValidEntry(
            0,
            2,
            Date::getNow()->getIso()
        );

        $this->assertFalse($invalidEntry, 'Expecting not a cache hit.');
    }

    public function testHasValidEntryReturnsFalseOnMissedCacheHitWithFilledCache()
    {
        // initial test setup
        $this->fillCache();

        $cache        = new Cache();
        $maxEntries   = $this->maxEntries;
        $invalidEntry = $cache->hasValidEntry(
            $maxEntries++,
            2,
            Date::getNow()->getIso()
        );

        $this->assertFalse($invalidEntry, 'Expecting not a cache hit.');
    }

    public function testGetReturnsXmlDataByValidDocumentIdAndValidXmlVersion()
    {
        // initial test setup
        $this->fillCache();
        $dataSet = $this->getRandomDataSet();

        $cache     = new Cache();
        $cachedDom = $cache->get($dataSet['document_id'], $dataSet['xml_version']);

        $this->assertNotNull($cachedDom, 'Expecting a not empty DOMDocument');
        $opusElement = $cachedDom->getElementsByTagName('Opus')->item(0);
        $this->assertEquals($dataSet['xml_data'], $cachedDom->saveXML($opusElement), 'Expecting same xml data.');
    }

    /**
     * @return array
     */
    public function invalidCombinationOfIdAndVersion()
    {
        return [
            [['document_id' => 0, 'xml_version' => 4]],
            [['document_id' => 1, 'xml_version' => 0]],
            [['document_id' => -1, 'xml_version' => 'baz']],
        ];
    }

    /**
     * @param array $dataSet
     * @dataProvider invalidCombinationOfIdAndVersion
     */
    public function testGetReturnsEmptyXmlByInvalidDataOnEmptyCache($dataSet)
    {
        $cache  = new Cache();
        $result = $cache->get($dataSet['document_id'], $dataSet['xml_version']);

        $expectedXml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $this->assertEquals($expectedXml, $result->saveXML(), 'Expecting empty DOMDocument.');
    }

    /**
     * @param array $dataSet
     * @dataProvider invalidCombinationOfIdAndVersion
     */
    public function testGetReturnsEmptyXmlByInvalidDataOnFilledCache($dataSet)
    {
        // initial test setup
        $this->fillCache();

        $cache  = new Cache();
        $result = $cache->get($dataSet['document_id'], $dataSet['xml_version']);

        $expectedXml = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $this->assertEquals($expectedXml, $result->saveXML(), 'Expecting empty DOMDocument.');
    }

    public function testGetWithValidXML()
    {
        $doc = new Document();
        $doc->store();
        $cache = new Cache();
        $dom   = $cache->get($doc->getId(), '1.0');
        $this->assertNotNull($dom);
    }

    public function testGetWithInvalidXML()
    {
        $doc      = new Document();
        $abstract = new TitleAbstract();
        $abstract->setLanguage('eng');
        $handle   = fopen(APPLICATION_PATH . '/tests/fulltexts/bad_abstract.txt', "rb");
        $contents = fread($handle, filesize(APPLICATION_PATH . '/tests/fulltexts/bad_abstract.txt'));
        $abstract->setValue($contents);
        fclose($handle);
        $doc->setTitleAbstract($abstract);
        $doc->store();

        // need to be set: otherwise PHPUnit will throw an error
        $tmp = libxml_use_internal_errors(true);

        $cache = new Cache();
        $dom   = null;
        try {
            $dom = $cache->get($doc->getId(), '1.0');
        } catch (ModelException $e) {
            $this->assertNotNull($e);
            return;
        }
        $this->assertNotNull($dom);

        // undo changes
        libxml_use_internal_errors($tmp);
    }

    public function testPuttingDataInCache()
    {
        $documentId         = 1;
        $xmlVersion         = 2;
        $serverDateModified = Date::getNow()->getIso();
        $dom                = new DOMDocument('1.0', 'utf-8');
        $opus               = $dom->createElement('Opus');
        $dom->appendChild($opus);
        $opusDocument = $dom->createElement('Opus_Document');
        $opus->appendChild($opusDocument);

        $table       = new DocumentXmlCache();
        $beforeInput = $table->fetchAll()->count();

        $cache = new Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
        );
        $afterInput = $table->fetchAll()->count();

        $this->assertEquals($beforeInput + 1, $afterInput, 'Expecting one new cache entry.');
        $this->assertTrue(
            $cache->hasValidEntry($documentId, $xmlVersion, $serverDateModified),
            'Could not verify cache entry.'
        );
        $this->assertEquals(
            $dom->saveXML(),
            $cache->get($documentId, $xmlVersion)->saveXML(),
            'Cached xml data differ from given data.'
        );
    }

    public function testRemoveCacheEntry()
    {
        $this->fillCache();
        $dataSet            = $this->getRandomDataSet();
        $documentId         = $dataSet['document_id'];
        $xmlVersion         = $dataSet['xml_version'];
        $serverDateModified = $dataSet['server_date_modified'];

        $table        = new DocumentXmlCache();
        $beforeRemove = $table->fetchAll()->count();

        $cache  = new Cache();
        $result = $cache->remove($documentId, $xmlVersion);

        $afterRemove = $table->fetchAll()->count();

        $this->assertTrue($result, 'Remove call returned false instead of true.');
        $this->assertEquals($beforeRemove, $afterRemove + 1, 'Expecting one cache entry are removed.');
        $this->assertFalse(
            $cache->hasValidEntry($documentId, $xmlVersion, $serverDateModified),
            'Expecting right cache entry is removed.'
        );
    }

    /**
     * TODO does not work for regular database user
     */
    public function testClearCache()
    {
        $this->fillCache();
        $cache = new Cache();
        $table = new DocumentXmlCache();

        $beforeRemove = $table->fetchAll()->count();
        $cache->clear();
        $afterRemove = $table->fetchAll()->count();

        $this->assertEquals($this->maxEntries, $beforeRemove);
        $this->assertEquals(0, $afterRemove);
    }

    public function testRemoveAllEntriesWhereDocumentId()
    {
        $this->fillCache();
        $dataSet            = $this->getRandomDataSet();
        $documentId         = $dataSet['document_id'];
        $xmlVersion         = $dataSet['xml_version'];
        $serverDateModified = $dataSet['server_date_modified'];

        $table        = new DocumentXmlCache();
        $beforeRemove = $table->fetchAll()->count();

        $cache = new Cache();
        $cache->remove($documentId);

        $afterRemove = $table->fetchAll()->count();

        $this->assertEquals($beforeRemove, $afterRemove + 1, 'Expecting one cache entry are removed.');
        $this->assertTrue(
            ! $cache->hasCacheEntry($documentId, $xmlVersion),
            'Expecting all cache entries (version $xmlVersion) have been removed.'
        );
        $this->assertTrue(
            ! $cache->hasCacheEntry($documentId, 1),
            'Expecting all cache entries (version 1) have been removed.'
        );
        $this->assertTrue(
            ! $cache->hasCacheEntry($documentId, 2),
            'Expecting all cache entries (version 2) have been removed.'
        );
    }

    public function testIfADocumentIsCached()
    {
        $this->fillCache();
        $dataSet    = $this->getRandomDataSet();
        $documentId = $dataSet['document_id'];
        $xmlVersion = $dataSet['xml_version'];

        $cache = new Cache();

        $this->assertTrue($cache->hasCacheEntry($documentId, $xmlVersion), 'Expected cache entry.');
    }

    public function testIfADocumentIsNotCached()
    {
        $documentId = mt_rand(1, 100);
        $xmlVersion = mt_rand(1, 2);

        $cache = new Cache();
        $this->assertFalse($cache->hasCacheEntry($documentId, $xmlVersion), 'Expected no cache entry.');
    }

    public function testPuttingSameDataSecondTimeDoesNotChangeCache()
    {
        $documentId         = 1;
        $xmlVersion         = 2;
        $serverDateModified = Date::getNow()->getIso();
        $dom                = new DOMDocument('1.0', 'utf-8');
        $opus               = $dom->createElement('Opus');
        $dom->appendChild($opus);
        $opusDocument = $dom->createElement('Opus_Document');
        $opus->appendChild($opusDocument);

        $cache = new Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
        );

        $table           = new DocumentXmlCache();
        $beforeSecondPut = $table->fetchAll()->count();

        $cache = new Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
        );

        $afterSecondPut = $table->fetchAll()->count();

        $this->assertEquals($beforeSecondPut, $afterSecondPut, 'Expecting no new cache entry.');
    }

    public function testPuttingSDataSecondTimeDoesChangeCache()
    {
        $documentId         = 1;
        $xmlVersion         = 2;
        $serverDateModified = Date::getNow()->getIso();
        $dom                = new DOMDocument('1.0', 'utf-8');
        $opus               = $dom->createElement('Opus');
        $dom->appendChild($opus);
        $opusDocument = $dom->createElement('Opus_Document');
        $opus->appendChild($opusDocument);

        $cache = new Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
        );

        $table           = new DocumentXmlCache();
        $beforeSecondPut = $table->fetchAll()->count();

        $dateTime           = (new DateTime())->add(new DateInterval('PT' . mt_rand(1, 59) . 'S'));
        $serverDateModified = (new Date($dateTime))->getIso();
        $subElement         = $dom->createElement('SubElement');
        $opusDocument->appendChild($subElement);
        $cache = new Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
        );

        $afterSecondPut = $table->fetchAll()->count();

        $this->assertEquals($beforeSecondPut, $afterSecondPut, 'Expecting no new cache entry.');
        $this->assertTrue(
            $cache->hasValidEntry($documentId, $xmlVersion, $serverDateModified),
            'Expecting cache entry has new data.'
        );
        $this->assertEquals($dom->saveXML(), $cache->get($documentId, $xmlVersion)->saveXML(), '');
    }

    /**
     * This test checks if the cache is updated, if after creating a document, it is instantiated again to add an
     * author. The cache is updated if there is a sleep (see below) between storing and instantiation, but it does
     * not work without the sleep line. See OPUSVIER-3392.
     */
    public function testCacheUpdatedForAddingPersonRightAfterStore()
    {
        $doc = new Document();
        $doc->setType('doctoral_thesis');
        $doc->setLanguage('deu');
        $doc->setServerState('published');
        $doc->setPublishedYear(2014);

        $title = $doc->addTitleMain();
        $title->setValue('Test Dokument');
        $title->setLanguage('deu');

        $docId = $doc->store();

        // sleep(1); // works with sleep

        $doc = new Document($docId);

        $person = new Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $doc->addPersonAuthor($person);

        $doc->store();

        $table = new DocumentXmlCache();
        $rows  = $table->fetchAll();

        $this->assertEquals(1, count($rows));

        $row = $rows->current();

        $xmlData = $row->xml_data;

        $this->assertContains('John', $xmlData, 'Cache should contain author.');
    }

    public function testRemoveAllEntriesForDependentModel()
    {
        $licence = new Licence();
        $licence->setNameLong('Test Licence');
        $licence->setLinkLicence('http://www.example.org');
        $licence->store();

        $doc = new Document();
        $doc->setType('article');
        $doc->setLanguage('deu');
        $doc->addLicence($licence);
        $docId = $doc->store();

        $cache = new Cache();

        $this->assertNotNull($cache->getData($docId, '1.0'));

        $cache->removeAllEntriesForDependentModel($licence);

        $this->assertNull($cache->getData($docId, '1.0'));
    }
}
