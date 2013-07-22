<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Test
 * @package     Opus_SolrSearch
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2010-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test indexing.
 *
 * @group SearchIndexIndexerTests
 */
class Opus_SolrSearch_Index_IndexerTest extends TestCase {

    /**
     * @var Opus_SolrSearch_Index_Indexer
     */
    protected $indexer;

    /**
     * @var int
     */
    protected $document_id;

    /**
     * @var string
     */
    protected $files_dir;

    /**
     * @var Zend_Config
     */
    protected $config;

    /**
     * @var Opus_Document
     */
    protected $nullDoc;

    /**
     * Valid document data.
     *
     * @var array  An array of arrays of arrays. Each 'inner' array must be an
     * associative array that represents valid document data.
     */
    protected static $_validDocumentData = array(
        'Type' => 'article',
        'Language' => 'deu',
        'ContributingCorporation' => 'Contributing, Inc.',
        'CreatingCorporation' => 'Creating, Inc.',
        'ThesisDateAccepted' => '1901-01-01',
        'Edition' => 2,
        'Issue' => 3,
        'Volume' => 1,
        'PageFirst' => 1,
        'PageLast' => 297,
        'PageNumber' => 297,
        'CompletedYear' => 1960,
        'CompletedDate' => '1901-01-01',
    );

    /**
     * Valid document data provider
     *
     * @return array
     */
    public static function validDocumentDataProvider() {
        return self::$_validDocumentData;
    }

    /**
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $this->config = Zend_Registry::get('Zend_Config');
        $this->files_dir = $this->config->workspacePath . DIRECTORY_SEPARATOR . "files";

	$this->indexer = new Opus_SolrSearch_Index_Indexer();

        $document = new Opus_Document();
        foreach (self::$_validDocumentData as $fieldname => $value) {
            $callname = 'set' . $fieldname;
            $document->$callname($value);
        }
        $document->store();
        $this->document_id = $document->getId();
    }

    /**
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        parent::tearDown();
        $this->rollbackConfigChanges();
        $this->indexer = new Opus_SolrSearch_Index_Indexer();
        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        // remove test documents under tests/workspace/files/$document_id
        $dirname = $this->files_dir . DIRECTORY_SEPARATOR .$this->document_id;
        if (is_dir($dirname) && is_readable($dirname)) {
            foreach (glob($dirname . "/*") as $filename) {
                if (is_readable($filename)) {
                    unlink($filename);
                }
            }
            rmdir($dirname);
        }
    }

    public function testMissingConfigParamSearchEngine_Index_Host() {
        // manipulate configuration so that searchengine.index.host is missing
        $config = new Zend_Config(array());
        Zend_Registry::set('Zend_Config', $config);

        $this->setExpectedException('Opus_SolrSearch_Index_InvalidConfigurationException');
        new Opus_SolrSearch_Index_Indexer();

    }

    public function testMissingConfigParamSearchEngine_Index_Port() {
        // manipulate configuration so that searchengine.index.port is missing
        $config = new Zend_Config(array(
            'searchengine' => array(
                'index' => array(
                    'host' => 'examplehost'))), true);
        Zend_Registry::set('Zend_Config', $config);

        $this->setExpectedException('Opus_SolrSearch_Index_InvalidConfigurationException');
        new Opus_SolrSearch_Index_Indexer();
    }

    public function testMissingConfigParamSearchEngine_Index_App() {
        // manipulate configuration so that searchengine.index.app is missing
        $config = new Zend_Config(array(
            'searchengine' => array(
                'index' => array(
                    'host' => 'examplehost',
                    'port' => 'exampleport'))), true);
        Zend_Registry::set('Zend_Config', $config);

        $this->setExpectedException('Opus_SolrSearch_Index_InvalidConfigurationException');
        new Opus_SolrSearch_Index_Indexer();
    }

    public function testMissingConfigParamLogPrepareXml() {
        // manipulate configuration so that log.prepare.xml is missing
        $config = new Zend_Config(array(
            'searchengine' => array(
                'index' => array(
                    'host' => $this->config->searchengine->index->host,
                    'port' => $this->config->searchengine->index->port,
                    'app'  => $this->config->searchengine->index->app
                ),
                'extract' => array(
                    'host' => $this->config->searchengine->extract->host,
                    'port' => $this->config->searchengine->extract->port,
                    'app'  => $this->config->searchengine->extract->app
                )
            ),
        ), true);
        Zend_Registry::set('Zend_Config', $config);
        $this->_addOneDocumentToIndex();
    }

    public function testEmptyConfiguration() {
        // manipulate configuration so that searchengine.index.app is empty
        $config = new Zend_Config(array(
            'searchengine' => array(
                'index' => array(
                    'host' => 'examplehost',
                    'port' => 'exampleport',
                    'app'  => ''))), true);
        Zend_Registry::set('Zend_Config', $config);
        
        $this->setExpectedException('Opus_SolrSearch_Index_InvalidConfigurationException');
        new Opus_SolrSearch_Index_Indexer();
    }

    public function testInvalidConfiguration() {
        // manipulate configuration so that searchengine.index.app is invalid
        $config = new Zend_Config(array(
            'searchengine' => array(
                'index' => array(
                    'host' => 'examplehost',
                    'port' => 'exampleport',
                    'app'  => 'this_solr_instance_name_does_not_exist'))), true);
        Zend_Registry::set('Zend_Config', $config);
                
        $this->setExpectedException('Opus_SolrSearch_Index_InvalidConfigurationException');
        new Opus_SolrSearch_Index_Indexer();
    }

    public function testPrepareAndOutputXML() {
        $config = new Zend_Config(array(
            'searchengine' => array(
                'index' => array(
                    'host' => $this->config->searchengine->index->host,
                    'port' => $this->config->searchengine->index->port,
                    'app'  => $this->config->searchengine->index->app
                ),
                'extract' => array(
                    'host' => $this->config->searchengine->extract->host,
                    'port' => $this->config->searchengine->extract->port,
                    'app'  => $this->config->searchengine->extract->app
                )
            ),
            'log' => array(
                'prepare' => array(
                    'xml' => true
                )
            )
        ), true);
        Zend_Registry::set('Zend_Config', $config);
        $this->_addOneDocumentToIndex();
    }

    public function testDeleteAllDocsInConstructor() {
        $this->_addOneDocumentToIndex();        
        $this->indexer = new Opus_SolrSearch_Index_Indexer(true);
        $this->assertEquals(0, $this->_getNumberOfIndexDocs());

        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        $this->_addOneDocumentToIndex();
        $this->indexer = new Opus_SolrSearch_Index_Indexer(false);
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());

        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        $this->_addOneDocumentToIndex();
        $this->indexer = new Opus_SolrSearch_Index_Indexer();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testAddDocumentToEmptyIndex() {
        $this->_addOneDocumentToIndex();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testRemoveDocumentFromIndex() {
        $this->_addOneDocumentToIndex();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
        $document = new Opus_Document($this->document_id);
        $this->indexer->removeDocumentFromEntryIndex($document);
        $this->indexer->commit();
        $this->assertEquals(0, $this->_getNumberOfIndexDocs());
    }

    public function testRemoveNullFromIndex() {
        $this->_addOneDocumentToIndex();
        $this->setExpectedException('InvalidArgumentException');
        $this->indexer->removeDocumentFromEntryIndex(null);
    }

    public function testDeleteAllDocsFromNonEmptyIndex() {
        $this->_addOneDocumentToIndex();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        $this->assertEquals(0, $this->_getNumberOfIndexDocs());
    }

    public function testDeleteAllDocsFromEmptyIndex() {
        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        $this->assertEquals(0, $this->_getNumberOfIndexDocs());
    }

    public function testDeleteDocsByMatchingQuery() {
        $this->_addOneDocumentToIndex();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
        $queryString = 'id:' . $this->document_id;
        $this->indexer->deleteDocsByQuery($queryString);
        $this->indexer->commit();
        $this->assertEquals(0, $this->_getNumberOfIndexDocs());
    }

    public function testDeleteDocsByNonMatchingQuery() {
        $this->_addOneDocumentToIndex();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
        $nonExistentDocID = $this->document_id + 1;
        $queryString = 'id:' . $nonExistentDocID;
        $this->indexer->deleteDocsByQuery($queryString);
        $this->indexer->commit();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testDeleteDocsByInvalidQuery() {
        $this->setExpectedException('Opus_SolrSearch_Index_Exception');
        $this->indexer->deleteDocsByQuery('id:');        
    }

    public function testCommit() {
        $this->indexer->commit();
    }

    public function testOptimize() {
        $this->indexer->optimize();
    }

    public function testFulltextExtractionPdf() {
        $this->_addFileToDocument('test.pdf', 'PDF fulltext');
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }
    
    public function testFulltextExtractionPostscript() {
        $this->_addFileToDocument('test.ps', 'PS fulltext');
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testFulltextExtractionHtml() {
        $this->_addFileToDocument('test.html', 'HTML fulltext');
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testFulltextExtractionXhtml() {
        $this->_addFileToDocument('test.xhtml', 'XHTML fulltext');
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testFulltextExtractionText() {
        $this->_addFileToDocument('test.txt', 'TXT fulltext');
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testFulltextExtractionWithNonExistentFile() {
        $doc = new Opus_Document($this->document_id);

        $file = $doc->addFile();
        $file->setPathName('nonexistent.pdf');
        $file->setLabel('non-existent PDF fulltext');

        $doc->store();

        $this->indexer->addDocumentToEntryIndex($doc);
        $this->indexer->commit();
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testFulltextExtractionWithNonSupportedMimeType() {
        $this->_addFileToDocument('test.odt', 'ODT fulltext');
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());
    }

    public function testFulltextExtractionByContentForPdf() {
        $this->_addFileToDocument('test.pdf', 'PDF fulltext');
        $this->assertEquals(1, $this->_searchTestFulltext());
    }

    public function testFulltextExtractionByContentForPostscript() {
        $this->markTestIncomplete();
        $this->_addFileToDocument('test.ps', 'PS fulltext');
        $this->assertEquals(1, $this->_searchTestFulltext());
    }

    public function testFulltextExtractionByContentForText() {
        $this->_addFileToDocument('test.txt', 'TXT fulltext');
        $this->assertEquals(1, $this->_searchTestFulltext());
    }

    public function testFulltextExtractionByContentForHtml() {
        $this->_addFileToDocument('test.html', 'HTML fulltext');
        $this->assertEquals(1, $this->_searchTestFulltext());
    }

    public function testFulltextExtractionByContentForXhtml() {
        $this->_addFileToDocument('test.xhtml', 'XHTML fulltext');
        $this->assertEquals(1, $this->_searchTestFulltext());
    }

    private function _getNumberOfIndexDocs() {
        $searcher = new Opus_SolrSearch_Searcher();
        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::SIMPLE);
        $query->setCatchAll("*:*");
        $query->disableEscaping();
        return $searcher->search($query)->getNumberOfHits();
    }

    private function _searchTestFulltext() {
        $searcher = new Opus_SolrSearch_Searcher();
        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::SIMPLE);
        $query->setCatchAll('Lorem');
        $query->disableEscaping();
        return $searcher->search($query)->getNumberOfHits();
    }

    private function _addOneDocumentToIndex() {
        $document = new Opus_Document($this->document_id);
        $this->indexer->addDocumentToEntryIndex($document);
        $this->indexer->commit();
    }

    /**
     *
     * @param string $filename
     * @param string $label
     */
    private function _addFileToDocument($filename, $label) {
        $doc = new Opus_Document($this->document_id);
        $file = $doc->addFile();
        $file->setTempFile('fulltexts/' . $filename);
        $file->setPathName($filename);
        $file->setLabel($label);
        $file->setVisibleInFrontdoor('1');

        $doc->store();

        $this->indexer->addDocumentToEntryIndex($doc);
        $this->indexer->commit();
    }

    private function rollbackConfigChanges() {
        Zend_Registry::set('Zend_Config', $this->config);
    }

    public function testAttachFulltextToNull() {
        // apply a hack to be able to test a private method directly
        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('attachFulltextToXml');
        $method->setAccessible(true);
        $indexer = new Opus_SolrSearch_Index_Indexer();
        $method->invokeArgs($indexer, array(new DomDocument(), null, 1));
    }

    public function testAddNullDocumentToIndex() {
        $this->markTestSkipped('passing null is not allowed: throws PHP Catchable fatal error');
        $this->indexer->addDocumentToEntryIndex(null);
        $this->setExpectedException('InvalidArgumentException');
    }

    public function testRemoveNullDocumentFromIndex() {
        $this->markTestSkipped('passing null is not allowed: throws PHP Catchable fatal error');
        $this->indexer->addDocumentToEntryIndex(null);
        $this->setExpectedException('InvalidArgumentException');
    }

    /**
     * Regression test for OPUSVIER-2240
     */
    public function testIndexDocumentWithMultipleTitleMainInSameLanguage() {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->setLanguage('eng');
        $title = new Opus_Title();
        $title->setValue('foo');
        $title->setLanguage('eng');
        $doc->addTitleMain($title);
        $title = new Opus_Title();
        $title->setValue('bar');
        $title->setLanguage('eng');
        $doc->addTitleMain($title);
        $doc->store();
        $exception = null;
        try {
            $this->indexer->addDocumentToEntryIndex($doc);
        }
        catch (Exception $e) {
            $exception = $e;
        }
        $this->assertNotNull($exception);
        $this->assertType('Opus_SolrSearch_Index_Exception', $exception);
        $doc->deletePermanent();
    }

    /**
     * Regression test for OPUSVIER-2240
     */
    public function testIndexDocumentWithMultipleAbstractsInSameLanguage() {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->setLanguage('eng');
        $title = new Opus_Title();
        $title->setValue('foo');
        $title->setLanguage('eng');
        $doc->addTitleAbstract($title);
        $title = new Opus_Title();
        $title->setValue('bar');
        $title->setLanguage('eng');
        $doc->addTitleAbstract($title);
        $doc->store();
        $exception = null;
        try {
            $this->indexer->addDocumentToEntryIndex($doc);
        }
        catch (Exception $e) {
            $exception = $e;
        }
        $this->assertNotNull($exception);
        $this->assertType('Opus_SolrSearch_Index_Exception', $exception);
        $doc->deletePermanent();
    }

    /**
     * Regression test for OPUSVIER-2240
     */
    public function testIndexDocumentWithUnknownIndexField() {
        $xml = new DOMDocument();
        $xml->loadXML(
                '<add>
                  <doc>
                    <field name="id">987654321</field>
                    <field name="year"/>
                    <field name="language">de</field>
                    <field name="author_sort"/>
                    <field name="has_fulltext">false</field>
                    <field name="doctype">article</field>
                    <field name="belongs_to_bibliography">false</field>
                    <field name="xyz_unknown_field">foo</field>
                  </doc>
                </add>');
        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('sendSolrXmlToServer');
        $method->setAccessible(true);
        
        try {
            $method->invoke ($this->indexer, $xml);
            $this->fail('expected exception of type Opus_SolrSearch_Index_Exception');
        }
        catch (Exception $e) {
            $this->assertType('Opus_SolrSearch_Index_Exception', $e);
        }
    }

    /**
     * Regression test for OPUSVIER-2417
     */
    public function testFulltextVisibilityIsConsideredInFacetForFrontdoorVisibleFulltext() {
        $doc = new Opus_Document($this->document_id);
        $doc->setServerState('published');
        $doc->setLanguage('eng');
        $file = $doc->addFile();
        $file->setTempFile('fulltexts' . DIRECTORY_SEPARATOR . 'test.pdf');
        $file->setPathName('test.pdf');
        $file->setVisibleInFrontdoor('1');
        $doc->store();

        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getSolrXmlDocument');
        $method->setAccessible(true);

        $xml = $method->invoke ($this->indexer, $doc);
	$xmlString = $xml->saveXML();
        $this->assertContains('<field name="has_fulltext">true</field>', $xmlString);
        $this->assertNotContains('<field name="has_fulltext">false</field>', $xmlString);
	$this->assertContains('<field name="fulltext_id_success">' . $file->getId() . ':' . $file->getRealHash('md5') . '</field>', $xmlString);
	$this->assertNotContains('<field name="fulltext_id_failure">', $xmlString);
	
        $path = $this->files_dir . DIRECTORY_SEPARATOR . $doc->getId();
	unlink($path . DIRECTORY_SEPARATOR . 'test.pdf');
	rmdir($path);
    }

    /**
     * Regression test for OPUSVIER-2417
     */
    public function testFulltextVisibilityIsConsideredInFacetForFrontdoorInvisibleFulltext() {
        $doc = new Opus_Document($this->document_id);
        $doc->setServerState('published');
        $doc->setLanguage('eng');
        $file = $doc->addFile();
        $file->setTempFile('fulltexts' . DIRECTORY_SEPARATOR . 'test.pdf');
        $file->setPathName('test.pdf');
        $file->setVisibleInFrontdoor('0');
        $doc->store();

        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getSolrXmlDocument');
        $method->setAccessible(true);

        $xml = $method->invoke ($this->indexer, $doc);
	$xmlString = $xml->saveXML();
        $this->assertContains('<field name="has_fulltext">false</field>', $xmlString);
        $this->assertNotContains('<field name="has_fulltext">true</field>', $xmlString);
	$this->assertNotContains('<field name="fulltext_id_success">', $xmlString);
	$this->assertNotContains('<field name="fulltext_id_failure">', $xmlString);

        $path = $this->files_dir . DIRECTORY_SEPARATOR . $doc->getId();
        unlink($path . DIRECTORY_SEPARATOR . 'test.pdf');
        rmdir($path);
    }

    /**
     * Regression test for OPUSVIER-2417
     */
    public function testFulltextVisibilityIsNotConsideredInFacet() {
        $doc = new Opus_Document($this->document_id);
        $doc->setServerState('published');
        $doc->setLanguage('eng');
        $doc->store();

        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getSolrXmlDocument');
        $method->setAccessible(true);

        $xml = $method->invoke ($this->indexer, $doc);
	$xmlString = $xml->saveXML();
        $this->assertContains('<field name="has_fulltext">false</field>', $xmlString);
        $this->assertNotContains('<field name="has_fulltext">true</field>', $xmlString);
	$this->assertNotContains('<field name="fulltext_id_success">', $xmlString);
	$this->assertNotContains('<field name="fulltext_id_failure">', $xmlString);
    }

    public function testHandlingOfNonExtractableFulltext() {
        $doc = new Opus_Document($this->document_id);
        $doc->setServerState('published');
        $doc->setLanguage('eng');
        $file = $doc->addFile();
        $file->setTempFile('fulltexts' . DIRECTORY_SEPARATOR . 'test-invalid.pdf');
        $file->setPathName('test-invalid.pdf');
        $file->setVisibleInFrontdoor('1');
        $doc->store();

        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getSolrXmlDocument');
        $method->setAccessible(true);

        $xml = $method->invoke ($this->indexer, $doc);
	$xmlString = $xml->saveXML();
        $this->assertContains('<field name="has_fulltext">true</field>', $xmlString);
        $this->assertNotContains('<field name="has_fulltext">false</field>', $xmlString);
	$this->assertContains('<field name="fulltext_id_failure">' . $file->getId() . ':' . $file->getRealHash('md5') . '</field>', $xmlString);
	$this->assertNotContains('<field name="fulltext_id_success">', $xmlString);

        $path = $this->files_dir . DIRECTORY_SEPARATOR . $doc->getId();
	unlink($path . DIRECTORY_SEPARATOR . 'test-invalid.pdf');
	rmdir($path);
    }
    
    /**
     * test changed return value (fluent interface)
     */
    public function testFluentInterface() {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->setLanguage('eng');
        $doc->store();
        $indexer = new Opus_SolrSearch_Index_Indexer();
        $result = null;
        $result = $indexer->addDocumentToEntryIndex($doc);
        $this->assertTrue($result instanceof Opus_SolrSearch_Index_Indexer, 'Expected instance of Opus_SolrSearch_Index_Indexer');
        $result = null;
        $result = $indexer->removeDocumentFromEntryIndex($doc);
        $this->assertTrue($result instanceof Opus_SolrSearch_Index_Indexer, 'Expected instance of Opus_SolrSearch_Index_Indexer');
        $result = null;
        $result = $indexer->removeDocumentFromEntryIndexById($doc->getId());
        $this->assertTrue($result instanceof Opus_SolrSearch_Index_Indexer, 'Expected instance of Opus_SolrSearch_Index_Indexer');
    }

    public function testIndexIsUpdatedSynchronouslyInSyncMode() {
        // manipulate configuration: enable sync mode (which is the default)
        $config = new Zend_Config(array(
            'runjobs' => array(
                'asynchronous' => 0)), true);
        $config->merge(Zend_Registry::get('Zend_Config'));
        Zend_Registry::set('Zend_Config', $config);

        $this->performDocumentExistsInIndexChecks();
    }

    public function testIndexIsUpdatedSynchronouslyInAsyncMode() {
        $this->markTestSkipped('Asynchronous index update is the expected behaviour in asynchronous mode so far.');
        // manipulate configuration: enable async mode
        $config = new Zend_Config(array(
            'runjobs' => array(
                'asynchronous' => 1)), true);
        $config->merge(Zend_Registry::get('Zend_Config'));
        Zend_Registry::set('Zend_Config', $config);

        $this->performDocumentExistsInIndexChecks();
    }

    private function performDocumentExistsInIndexChecks() {
        // check that document with id $this->document_is is NOT in Solr index
        $this->assertFalse($this->isTestDocumentInSearchIndex(), 'check #1 failed');

        $doc = new Opus_Document($this->document_id);
        $doc->setServerState('published');
        $doc->store();

        // check that document with id $this->document_is is in Solr index
        $this->assertTrue($this->isTestDocumentInSearchIndex(), 'check #2 failed');

        $doc = new Opus_Document($this->document_id);
        $doc->setServerState('unpublished');
        $doc->store();

        // check that document with id $this->document_is is NOT in Solr index
        $this->assertFalse($this->isTestDocumentInSearchIndex(), 'check #3 failed');

        $doc = new Opus_Document($this->document_id);
        $doc->setServerState('published');
        $doc->store();

        // check that document with id $this->document_is is in Solr index
        $this->assertTrue($this->isTestDocumentInSearchIndex(), 'check #4 failed');

        $resultList = $this->catchAll();
        $doc = new Opus_Document($this->document_id);
        $serverDateModified1 = $doc->getServerDateModified()->getUnixTimestamp();
        $this->assertEquals($serverDateModified1, $resultList[0]->getServerDateModified());

        sleep(1);

        $doc = new Opus_Document($this->document_id);
        $doc->setLanguage('eng');
        $doc->store();

        // check that document with id $this->document_is is in Solr index
        $this->assertTrue($this->isTestDocumentInSearchIndex(), 'check #5 failed');

        $resultList = $this->catchAll();
        $doc = new Opus_Document($this->document_id);
        $serverDateModified2 = $doc->getServerDateModified()->getUnixTimestamp();
        $this->assertEquals($serverDateModified2, $resultList[0]->getServerDateModified());
        $this->assertTrue($serverDateModified1 < $serverDateModified2);

        $doc = new Opus_Document($this->document_id);
        $doc->deletePermanent();

        // check that document with id $this->document_is is NOT in Solr index
        $this->assertFalse($this->isTestDocumentInSearchIndex(), 'check #6 failed');
    }

    private function isTestDocumentInSearchIndex() {
        $resultList = $this->catchAll();
        return (count($resultList) == 1) && ($this->document_id == $resultList[0]->getId());
    }

    private function catchAll() {
        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::SIMPLE);
        $query->setCatchAll('*:*');
        $searcher = new Opus_SolrSearch_Searcher();
        $results = $searcher->search($query);
        $resultList = $results->getResults();
        return $resultList;
    }
    
    public function testGetCachedFileNamePositiveCase() {
        $file = $this->createTestFile();
        
        // apply a hack to be able to test a private method directly
        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getCachedFileName');
        $method->setAccessible(true);
        $indexer = new Opus_SolrSearch_Index_Indexer();
        $result = $method->invokeArgs($indexer, array($file));
        $this->assertNotNull($result);
                
        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . DIRECTORY_SEPARATOR;                
        $this->assertEquals($path . 'cache/solr_cache---901736df3fbc807121c46f9eaed8ff28-ff4ef4245da5b09786e3d3de8b430292fa081984db272d2b13ed404b45353d28.txt', $result);
        
        $this->removeTestFile($file);
    }
    
    public function testGetCachedFileNameNegativeCase() {
        $file = $this->createTestFile();
        
        $this->removeTestFile($file);
        
        // apply a hack to be able to test a private method directly
        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getCachedFileName');
        $method->setAccessible(true);
        $indexer = new Opus_SolrSearch_Index_Indexer();
        $result = $method->invokeArgs($indexer, array($file));
        $this->assertNull($result);        
    }
    
    public function testGetFulltextHashPositiveCase() {
        $file = $this->createTestFile();
        
        // apply a hack to be able to test a private method directly
        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getFulltextHash');
        $method->setAccessible(true);
        $indexer = new Opus_SolrSearch_Index_Indexer();
        $result = $method->invokeArgs($indexer, array($file));

        $this->assertEquals('1:901736df3fbc807121c46f9eaed8ff28', $result);
        
        $this->removeTestFile($file);        
    }
    
    public function testGetFulltextHashNegativeCase() {
        $file = $this->createTestFile();
        
        $this->removeTestFile($file);
        
        // apply a hack to be able to test a private method directly
        $class = new ReflectionClass('Opus_SolrSearch_Index_Indexer');
        $method = $class->getMethod('getFulltextHash');
        $method->setAccessible(true);
        $indexer = new Opus_SolrSearch_Index_Indexer();
        $result = $method->invokeArgs($indexer, array($file));

        $this->assertEquals('1:', $result);
    }
    
    private function createTestFile() {
        $doc = new Opus_Document;
        $doc->store();
        
        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . DIRECTORY_SEPARATOR;        
        
        $testfile = $path . 'files/' . $doc->getId() . '/test.txt';
        if (file_exists($testfile)) {
            unlink($testfile);
        }        
        
        $file = $doc->addFile();        
        $file->setTempFile($path . '../fulltexts/test.txt');
        $file->setPathName('test.txt');
        $file->setLabel('foobarbaz');
        
        $doc->store();
        return $file;
    }
    
    private function removeTestFile($file) {
        unlink($file->getPath());
    }    

}

