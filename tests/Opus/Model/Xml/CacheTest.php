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
 * @category    Framework
 * @package     Opus_Model_Xml
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Search Hit model class.
 *
 * @category    Framework
 * @package     Opus_Model_Xml
 */
class Opus_Model_Xml_CacheTest extends TestCase {

    /**
     * Holds generated cache entries for verifying.
     *
     * @var array
     */
    private $_allEntries = array();

    /**
     * Defines how many cache entries should be genereated and / or available
     *
     * @var int
     */
    private $_maxEntries = 5;

    /**
     * Fill cache with some "random" data
     *
     * @return void
     */
    private function _fillCache() {
        // initial test setup
        $table = new Opus_Db_DocumentXmlCache;
        for ($i = 0; $i < $this->_maxEntries; $i++) {
            $data = array(
                'document_id' => $i + 1,
                'server_date_modified' => Zend_Date::now()->addSecond(rand(1, 59))->getIso(),
                'xml_version' => $i % 2 ? 1 : 2,
                'xml_data' => '<Opus><Opus_Document><Foo/></Opus_Document></Opus>',
            );
            $this->_allEntries[] = $data;
            $table->insert($data);
        }
    }

    /**
     * Returns a random data set.
     *
     * @return array
     */
    private function _getRandomDataSet() {
        do {
            $testId = mt_rand(0, $this->_maxEntries);
        } while (false === array_key_exists($testId, $this->_allEntries));

        $dataSet = $this->_allEntries[$testId];
        return $dataSet;
    }

    /**
     * Test if an empty cache is empty.
     *
     * @return void
     */
    public function testCacheInitiallyEmpty() {
        $cache = new Opus_Model_Xml_Cache();
        $cacheEntries = $cache->getAllEntries();

        $this->assertEquals(array(), $cacheEntries, 'Xml cache is not empty.');
    }

    /**
     * Test if a given set of data are properly returned.
     *
     * @return void
     */
    public function testGetAllEntriesReturnsAllCacheEntries() {
        // initial test setup
        $this->_fillCache();

        $cache = new Opus_Model_Xml_Cache();
        $cacheEntries = $cache->getAllEntries();

        $this->assertEquals($this->_maxEntries, count($cacheEntries), 'Expecting ' . $this->_maxEntries . ' inside cache.');
        $this->assertEquals($this->_allEntries, $cacheEntries, 'Getting unexpected cache entries.');
    }

    /**
     *
     *
     * @return void
     */
    public function testHasValidEntryReturnsTrueOnCacheHit() {
        // initial test setup
        $this->_fillCache();
        $dataSet = $this->_getRandomDataSet();

        $cache = new Opus_Model_Xml_Cache();
        $validEntry = $cache->hasValidEntry(
            $dataSet['document_id'],
            $dataSet['xml_version'],
            $dataSet['server_date_modified']
            );

        $this->assertTrue($validEntry, 'Expecting a cache hit.');
    }

    /**
     *
     *
     * @return void
     */
    public function testHasValidEntryReturnsFalseOnMissedCacheHitWithEmptyCache() {

        $cache = new Opus_Model_Xml_Cache;
        $invalidEntry = $cache->hasValidEntry(0, 2, Zend_Date::now()->getIso());

        $this->assertFalse($invalidEntry, 'Expecting not a cache hit.');
    }

    /**
     *
     *
     * @return void
     */
    public function testHasValidEntryReturnsFalseOnMissedCacheHitWithFilledCache() {
        // initial test setup
        $this->_fillCache();

        $cache = new Opus_Model_Xml_Cache;
        $maxEntries = $this->_maxEntries;
        $invalidEntry = $cache->hasValidEntry($maxEntries++, 2, Zend_Date::now()->getIso());

        $this->assertFalse($invalidEntry, 'Expecting not a cache hit.');
    }

    /**
     *
     *
     * @return void
     */
    public function testGetReturnsXmlDataByValidDocumentIdAndValidXmlVersion() {
        // initial test setup
        $this->_fillCache();
        $dataSet = $this->_getRandomDataSet();

        $cache = new Opus_Model_Xml_Cache;
        $cachedDom = $cache->get($dataSet['document_id'], $dataSet['xml_version']);

        $this->assertNotNull($cachedDom, 'Expecting a not empty DOMDocument');
        $opusElement = $cachedDom->getElementsByTagName('Opus')->item(0);
        $this->assertEquals($dataSet['xml_data'], $cachedDom->saveXML($opusElement), 'Expecting same xml data.');
    }

    /**
     *
     *
     * @return array
     */
    public function invalidCombinationOfIdAndVersion() {
        return array(
            array(array('document_id' => 0, 'xml_version' => 4)),
            array(array('document_id' => 1, 'xml_version' => 0)),
            array(array('document_id' => -1, 'xml_version' => 'baz')),
        );
    }

    /**
     *
     *
     * @dataProvider invalidCombinationOfIdAndVersion
     * @return void
     */
    public function testGetReturnsEmptyXmlByInvalidDataOnEmptyCache($dataSet) {

        $cache = new Opus_Model_Xml_Cache;
        $result = $cache->get($dataSet['document_id'], $dataSet['xml_version']);

        $expectedXML = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $this->assertEquals($expectedXML, $result->saveXML(), 'Expecting empty DOMDocument.');
    }

    /**
     *
     *
     * @dataProvider invalidCombinationOfIdAndVersion
     * @return void
     */
    public function testGetReturnsEmptyXmlByInvalidDataOnFilledCache($dataSet) {
        // initial test setup
        $this->_fillCache();

        $cache = new Opus_Model_Xml_Cache;
        $result = $cache->get($dataSet['document_id'], $dataSet['xml_version']);

        $expectedXML = '<?xml version="1.0" encoding="utf-8"?>' . "\n";
        $this->assertEquals($expectedXML, $result->saveXML(), 'Expecting empty DOMDocument.');
    }

    public function testGetWithValidXML() {
        $doc = new Opus_Document();
        $doc->store();
        $cache = new Opus_Model_Xml_Cache;
        $dom = $cache->get($doc->getId(), '1.0');
        $this->assertNotNull($dom);
    }

    public function testGetWithInvalidXML() {
        $doc = new Opus_Document();
        $abstract = new Opus_TitleAbstract();
        $abstract->setLanguage('eng');
        $handle = fopen(APPLICATION_PATH . '/tests/fulltexts/bad_abstract.txt', "rb");
        $contents = fread($handle, filesize(APPLICATION_PATH . '/tests/fulltexts/bad_abstract.txt'));
        $abstract->setValue($contents);
        fclose($handle);
        $doc->setTitleAbstract($abstract);
        $doc->store();

        // need to be set: otherwise PHPUnit will throw an error        
        $tmp = libxml_use_internal_errors(true);
        
        $cache = new Opus_Model_Xml_Cache;
        $dom = null;
        try {
            $dom = $cache->get($doc->getId(), '1.0');
        }
        catch (Opus_Model_Exception $e) {
            $this->assertNotNull($e);
            return;
        }
        $this->assertNotNull($dom);
        
        // undo changes
        libxml_use_internal_errors($tmp);
    }

    /**
     *
     *
     * @return void
     */
    public function testPuttingDataInCache() {

        $documentId = 1;
        $xmlVersion = 2;
        $serverDateModified = Zend_Date::now()->getIso();
        $dom = new DOMDocument('1.0', 'utf-8');
        $opus = $dom->createElement('Opus');
        $dom->appendChild($opus);
        $opusDocument = $dom->createElement('Opus_Document');
        $opus->appendChild($opusDocument);
        
        $table = new Opus_Db_DocumentXmlCache();
        $beforeInput = $table->fetchAll()->count();

        $cache = new Opus_Model_Xml_Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
            );
        
        $afterInput = $table->fetchAll()->count();
        
        $this->assertEquals($beforeInput + 1, $afterInput, 'Expecting one new cache entry.');
        $this->assertTrue($cache->hasValidEntry($documentId, $xmlVersion, $serverDateModified), 'Could not verify cache entry.');
        $this->assertEquals($dom->saveXML(), $cache->get($documentId, $xmlVersion)->saveXML(), 'Cached xml data differ from given data.');
    }

    /**
     *
     *
     * @return void
     */
    public function testRemoveCacheEntry() {

        $this->_fillCache();
        $dataSet = $this->_getRandomDataSet();
        $documentId = $dataSet['document_id'];
        $xmlVersion = $dataSet['xml_version'];
        $serverDateModified = $dataSet['server_date_modified'];

        $table = new Opus_Db_DocumentXmlCache();
        $beforeRemove = $table->fetchAll()->count();

        $cache = new Opus_Model_Xml_Cache();
        $result = $cache->remove($documentId, $xmlVersion);

        $afterRemove = $table->fetchAll()->count();

        $this->assertTrue($result, 'Remove call returned false instead of true.');
        $this->assertEquals($beforeRemove, $afterRemove + 1, 'Expecting one cache entry are removed.');
        $this->assertFalse($cache->hasValidEntry($documentId, $xmlVersion, $serverDateModified), 'Expecting right cache entry is removed.');
    }

    /**
     * TODO does not work for regular database user
     */
    public function testClearCache()
    {
        $this->_fillCache();
        $cache = new Opus_Model_Xml_Cache();
        $table = new Opus_Db_DocumentXmlCache();

        $beforeRemove = $table->fetchAll()->count();
        $cache->removeAllEntries();
        $afterRemove = $table->fetchAll()->count();

        $this->assertEquals($this->_maxEntries, $beforeRemove);
        $this->assertEquals(0, $afterRemove);
    }

    /**
     *
     *
     * @return void
     */
    public function testRemoveAllEntriesWhereDocumentId() {

        $this->_fillCache();
        $dataSet = $this->_getRandomDataSet();
        $documentId = $dataSet['document_id'];
        $xmlVersion = $dataSet['xml_version'];
        $serverDateModified = $dataSet['server_date_modified'];

        $table = new Opus_Db_DocumentXmlCache();
        $beforeRemove = $table->fetchAll()->count();

        $cache = new Opus_Model_Xml_Cache();
        $cache->removeAllEntriesWhereDocumentId($documentId);

        $afterRemove = $table->fetchAll()->count();

        $this->assertEquals($beforeRemove, $afterRemove + 1, 'Expecting one cache entry are removed.');
        $this->assertTrue(!$cache->hasCacheEntry($documentId, $xmlVersion),
                'Expecting all cache entries (version $xmlVersion) have been removed.');
        $this->assertTrue(!$cache->hasCacheEntry($documentId, 1),
                'Expecting all cache entries (version 1) have been removed.');
        $this->assertTrue(!$cache->hasCacheEntry($documentId, 2),
                'Expecting all cache entries (version 2) have been removed.');
    }

    /**
     *
     *
     * @return void
     */
    public function testIfADocumentIsCached() {

        $this->_fillCache();
        $dataSet = $this->_getRandomDataSet();
        $documentId = $dataSet['document_id'];
        $xmlVersion = $dataSet['xml_version'];
        
        $cache = new Opus_Model_Xml_Cache();
        
        $this->assertTrue($cache->hasCacheEntry($documentId, $xmlVersion), 'Expected cache entry.');
    }

    /**
     *
     *
     * @return void
     */
    public function testIfADocumentIsNotCached() {

        $documentId = mt_rand(1, 100);
        $xmlVersion = mt_rand(1, 2);

        $cache = new Opus_Model_Xml_Cache();
        $this->assertFalse($cache->hasCacheEntry($documentId, $xmlVersion), 'Expected no cache entry.');
    }

    /**
     *
     *
     * @return void
     */
    public function testPuttingSameDataSecondTimeDoesNotChangeCache() {

        $documentId = 1;
        $xmlVersion = 2;
        $serverDateModified = Zend_Date::now()->getIso();
        $dom = new DOMDocument('1.0', 'utf-8');
        $opus = $dom->createElement('Opus');
        $dom->appendChild($opus);
        $opusDocument = $dom->createElement('Opus_Document');
        $opus->appendChild($opusDocument);

        $cache = new Opus_Model_Xml_Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
            );

        $table = new Opus_Db_DocumentXmlCache();
        $beforeSecondPut = $table->fetchAll()->count();

        $cache = new Opus_Model_Xml_Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
            );
        
        $afterSecondPut = $table->fetchAll()->count();
        
        $this->assertEquals($beforeSecondPut, $afterSecondPut, 'Expecting no new cache entry.');
    }

    /**
     *
     *
     * @return void
     */
    public function testPuttingSDataSecondTimeDoesChangeCache() {

        $documentId = 1;
        $xmlVersion = 2;
        $serverDateModified = Zend_Date::now()->getIso();
        $dom = new DOMDocument('1.0', 'utf-8');
        $opus = $dom->createElement('Opus');
        $dom->appendChild($opus);
        $opusDocument = $dom->createElement('Opus_Document');
        $opus->appendChild($opusDocument);

        $cache = new Opus_Model_Xml_Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
            );

        $table = new Opus_Db_DocumentXmlCache();
        $beforeSecondPut = $table->fetchAll()->count();

        $serverDateModified = Zend_Date::now()->addSecond(mt_rand(1, 59))->getIso();
        $subElement = $dom->createElement('SubElement');
        $opusDocument->appendChild($subElement);
        $cache = new Opus_Model_Xml_Cache();
        $cache->put(
            $documentId,
            $xmlVersion,
            $serverDateModified,
            $dom
            );
        
        $afterSecondPut = $table->fetchAll()->count();
        
        $this->assertEquals($beforeSecondPut, $afterSecondPut, 'Expecting no new cache entry.');
        $this->assertTrue($cache->hasValidEntry($documentId, $xmlVersion, $serverDateModified), 'Expecting cache entry has new data.');
        $this->assertEquals($dom->saveXML(), $cache->get($documentId, $xmlVersion)->saveXML(), '');
    }

    /**
     * This test checks if the cache is updated, if after creating a document, it is instantiated again to add an
     * author. The cache is updated if there is a sleep (see below) between storing and instantiation, but it does
     * not work without the sleep line. See OPUSVIER-3392.
     */
    public function testCacheUpdatedForAddingPersonRightAfterStore() {
        $doc = new Opus_Document();
        $doc->setType('doctoral_thesis');
        $doc->setLanguage('deu');
        $doc->setServerState('published');
        $doc->setPublishedYear(2014);

        $title = $doc->addTitleMain();
        $title->setValue('Test Dokument');
        $title->setLanguage('deu');

        $docId = $doc->store();

        // sleep(1); // works with sleep

        $doc = new Opus_Document($docId);

        $person = new Opus_Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $doc->addPersonAuthor($person);

        $doc->store();

        $table = new Opus_Db_DocumentXmlCache();
        $rows = $table->fetchAll();

        $this->assertEquals(1, count($rows));

        $row = $rows->current();

        $xmlData = $row->xml_data;

        $this->assertContains('John', $xmlData, 'Cache should contain author.');
    }

    public function testRemoveAllEntriesForDependentModel()
    {
        $licence = new Opus_Licence();
        $licence->setNameLong('Test Licence');
        $licence->setLinkLicence('http://www.example.org');
        $licence->store();

        $doc = new Opus_Document();
        $doc->setType('article');
        $doc->setLanguage('deu');
        $doc->addLicence($licence);
        $docId = $doc->store();

        $cache = new Opus_Model_Xml_Cache();

        $this->assertNotNull($cache->getData($docId, '1.0'));

        $cache->removeAllEntriesForDependentModel($licence);

        $this->assertNull($cache->getData($docId, '1.0'));
    }

}

