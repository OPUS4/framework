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
 * @package     Opus_Search
 * @author      Thomas Urban <thomas.urban@cepharum.de>
 * @copyright   Copyright (c) 2010-2015, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test indexing.
 *
 */
class Opus_Search_Common_IndexerTest extends TestCase {

	/**
	 * @var Opus_Search_Indexing
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
		'Type'                    => 'article',
		'Language'                => 'deu',
		'ContributingCorporation' => 'Contributing, Inc.',
		'CreatingCorporation'     => 'Creating, Inc.',
		'ThesisDateAccepted'      => '1901-01-01',
		'Edition'                 => 2,
		'Issue'                   => 3,
		'Volume'                  => 1,
		'PageFirst'               => 1,
		'PageLast'                => 297,
		'PageNumber'              => 297,
		'CompletedYear'           => 1960,
		'CompletedDate'           => '1901-01-01',
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

		$this->files_dir = Opus_Config::get()->workspacePath . '/files';

		$this->indexer = Opus_Search_Service::selectIndexingService();

		$document = new Opus_Document();
		foreach ( self::$_validDocumentData as $fieldname => $value ) {
			$callname = 'set' . $fieldname;
			$document->$callname( $value );
		}
		$document->store();
		$this->document_id = $document->getId();
	}

	/**
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		// remove test documents under tests/workspace/files/$document_id
		$dirname = $this->files_dir . DIRECTORY_SEPARATOR . $this->document_id;
		if ( is_dir( $dirname ) && is_readable( $dirname ) ) {
			foreach ( glob( $dirname . "/*" ) as $filename ) {
				if ( is_readable( $filename ) ) {
					unlink( $filename );
				}
			}
			rmdir( $dirname );
		}

		parent::tearDown();
	}

	/**
	 * @expectedException Opus_Search_InvalidConfigurationException
	 */
	public function testMissingConfigParamSearchEngine_Index_Host() {
		$this->markTestSkipped( 'not supported in Opus_Search API due to passing configuration to 3rd-party library for processing' );

		$this->adjustConfiguration( array(), function ( Zend_Config $config ) {
			unset(
				$config->searchengine->solr->default->service->index->endpoint->primary->host,
				$config->searchengine->solr->default->service->default->endpoint->primary->host
			);

			return $config;
		} );

		Opus_Search_Service::selectIndexingService()
		                   ->removeAllDocumentsFromIndex();
	}

	/**
	 * @expectedException Opus_Search_InvalidConfigurationException
	 */
	public function testMissingConfigParamSearchEngine_Index_Port() {
		$this->markTestSkipped( 'not supported in Opus_Search API due to passing configuration to 3rd-party library for processing' );

		$this->adjustConfiguration( array(), function ( Zend_Config $config ) {
			unset(
				$config->searchengine->solr->default->service->index->endpoint->primary->port,
				$config->searchengine->solr->default->service->default->endpoint->primary->port
			);

			return $config;
		} );

		Opus_Search_Service::selectIndexingService()
		                   ->removeAllDocumentsFromIndex();
	}

	/**
	 * @expectedException Opus_Search_InvalidConfigurationException
	 */
	public function testMissingConfigParamSearchEngine_Index_Path() {
		$this->markTestSkipped( 'not supported in Opus_Search API due to passing configuration to 3rd-party library for processing' );

		$this->adjustConfiguration( array(), function ( Zend_Config $config ) {
			unset(
				$config->searchengine->solr->default->service->index->endpoint->primary->path,
				$config->searchengine->solr->default->service->default->endpoint->primary->path
			);

			return $config;
		} );

		Opus_Search_Service::selectIndexingService()
		                   ->removeAllDocumentsFromIndex();
	}

	public function testMissingConfigParamLogPrepareXml() {
		$this->adjustConfiguration( array(), function ( Zend_Config $config ) {
			unset( $config->log->prepare->xml );

			return $config;
		} );

		$this->_addOneDocumentToIndex();
	}

	/**
	 * @expectedException Opus_Search_InvalidConfigurationException
	 */
	public function testEmptyConfiguration() {
		$this->markTestSkipped( 'not supported in Opus_Search API due to passing configuration to 3rd-party library for processing' );

		$this->adjustConfiguration( array(
            'searchengine' => array( 'solr' => array( 'default' => array( 'service' => array( 'index' => array( 'endpoint' => array( 'primary' => array( 'path' => '' ) ) ) ) ) ) )
        ) );

		Opus_Search_Service::selectIndexingService()
		                   ->removeAllDocumentsFromIndex();
	}

	/**
	 * @expectedException Opus_Search_InvalidConfigurationException
	 */
	public function testInvalidConfiguration() {
		$this->markTestSkipped( 'not supported in Opus_Search API due to passing configuration to 3rd-party library for processing' );

		$this->adjustConfiguration( array(
            'searchengine' => array(
                'solr' => array(
                    'default' => array(
                        'service' => array(
                            'index' => array(
	                            'endpoint' => array(
		                            'primary' => array(
			                            'host' => 'examplehost',
			                            'port' => 'exampleport',
			                            'path' => 'this_solr_instance_name_does_not_exist'
		                            )
	                            )
                            )
                        )
                    )
                )
            )
        ) );

		Opus_Search_Service::selectIndexingService()
		                   ->removeAllDocumentsFromIndex();
	}

	public function testPrepareAndOutputXML() {
		$this->adjustConfiguration( array(
			                            'log' => array( 'prepare' => array( 'xml' => true ) )
		                            ) );

		$this->_addOneDocumentToIndex();
	}

	public function testAddDocumentToEmptyIndex() {
		$this->_addOneDocumentToIndex();
		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testRemoveDocumentFromIndex() {
		$this->_addOneDocumentToIndex();

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );

		$document = new Opus_Document( $this->document_id );
		$this->indexer->removeDocumentsFromIndex( $document );

		$this->assertEquals( 0, $this->_getNumberOfIndexDocs() );
	}

	public function testRemoveDocumentArrayFromIndex() {
		$this->_addOneDocumentToIndex();

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );

		$document = new Opus_Document( $this->document_id );
		$this->indexer->removeDocumentsFromIndex( array( $document ) );

		$this->assertEquals( 0, $this->_getNumberOfIndexDocs() );
	}

	public function testRemoveDocumentByIdFromIndex() {
		$this->_addOneDocumentToIndex();

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );

		$this->indexer->removeDocumentsFromIndexById( $this->document_id );

		$this->assertEquals( 0, $this->_getNumberOfIndexDocs() );
	}

	public function testRemoveDocumentArrayByIdFromIndex() {
		$this->_addOneDocumentToIndex();

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );

		$this->indexer->removeDocumentsFromIndexById( array( $this->document_id ) );

		$this->assertEquals( 0, $this->_getNumberOfIndexDocs() );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRemoveNullFromIndex() {
		$this->_addOneDocumentToIndex();

		$this->indexer->removeDocumentsFromIndex( null );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 */
	public function testRemoveMissingDocumentFromIndex() {
		$this->_addOneDocumentToIndex();

		$this->indexer->removeDocumentsFromIndex();
	}

	public function testDeleteAllDocsFromNonEmptyIndex() {
		$this->_addOneDocumentToIndex();
		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );

		$this->indexer->removeAllDocumentsFromIndex();

		$this->assertEquals( 0, $this->_getNumberOfIndexDocs() );
	}

	public function testDeleteAllDocsFromEmptyIndex() {
		$this->indexer->removeAllDocumentsFromIndex();

		$this->assertEquals( 0, $this->_getNumberOfIndexDocs() );
	}

	public function testDeleteDocsByMatchingQuery() {
		$this->markTestSkipped( 'not supported by Opus_Search API' );

		/*		$this->_addOneDocumentToIndex();

				$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );

				$queryString = 'id:' . $this->document_id;
				$this->indexer->deleteDocsByQuery( $queryString );
				$this->indexer->commit();

				$this->assertEquals( 0, $this->_getNumberOfIndexDocs() );*/
	}

	public function testDeleteDocsByNonMatchingQuery() {
		$this->markTestSkipped( 'not supported by Opus_Search API' );

		/*		$this->_addOneDocumentToIndex();

				$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );

				$nonExistentDocID = $this->document_id + 1;
				$queryString      = 'id:' . $nonExistentDocID;
				$this->indexer->deleteDocsByQuery( $queryString );
				$this->indexer->commit();

				$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );*/
	}

	public function testDeleteDocsByInvalidQuery() {
		$this->markTestSkipped( 'not supported by Opus_Search API' );

		/*		$this->setExpectedException( 'Opus_SolrSearch_Index_Exception' );
				$this->indexer->deleteDocsByQuery( 'id:' );*/
	}

	public function testCommit() {
		$this->markTestSkipped( 'not supported by Opus_Search API' );

		/*		$this->indexer->commit();*/
	}

	public function testOptimize() {
		$this->markTestSkipped( 'not supported by Opus_Search API' );

		/*		$this->indexer->optimize();*/
	}

	public function testFulltextExtractionPdf() {
		$this->_addFileToDocument( 'test.pdf', 'PDF fulltext' );

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testFulltextExtractionPostscript() {
		$this->_addFileToDocument( 'test.ps', 'PS fulltext' );

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testFulltextExtractionHtml() {
		$this->_addFileToDocument( 'test.html', 'HTML fulltext' );

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testFulltextExtractionXhtml() {
		$this->_addFileToDocument( 'test.xhtml', 'XHTML fulltext' );

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testFulltextExtractionText() {
		$this->_addFileToDocument( 'test.txt', 'TXT fulltext' );

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testFulltextExtractionWithNonExistentFile() {
		$doc = new Opus_Document( $this->document_id );

		$file = $doc->addFile();
		$file->setPathName( 'nonexistent.pdf' );
		$file->setLabel( 'non-existent PDF fulltext' );

		$doc->store();

		$this->indexer->addDocumentsToIndex( $doc );

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testFulltextExtractionWithNonSupportedMimeType() {
		$this->_addFileToDocument( 'test.odt', 'ODT fulltext' );

		$this->assertEquals( 1, $this->_getNumberOfIndexDocs() );
	}

	public function testFulltextExtractionByContentForPdf() {
		$this->_addFileToDocument( 'test.pdf', 'PDF fulltext' );

		$this->assertEquals( 1, $this->_searchTestFulltext() );
	}

	public function testFulltextExtractionByContentForPostscript() {
		$this->markTestIncomplete();

		$this->_addFileToDocument( 'test.ps', 'PS fulltext' );

		$this->assertEquals( 1, $this->_searchTestFulltext() );
	}

	public function testFulltextExtractionByContentForText() {
		$this->_addFileToDocument( 'test.txt', 'TXT fulltext' );

		$this->assertEquals( 1, $this->_searchTestFulltext() );
	}

	public function testFulltextExtractionByContentForHtml() {
		$this->_addFileToDocument( 'test.html', 'HTML fulltext' );

		$this->assertEquals( 1, $this->_searchTestFulltext() );
	}

	public function testFulltextExtractionByContentForXhtml() {
		$this->_addFileToDocument( 'test.xhtml', 'XHTML fulltext' );

		$this->assertEquals( 1, $this->_searchTestFulltext() );
	}

	private function _getNumberOfIndexDocs() {
		$search = Opus_Search_Service::selectSearchingService();

		return $search->customSearch( $search->createQuery() )
		              ->getAllMatchesCount();
	}

	private function _searchTestFulltext() {
		$search = Opus_Search_Service::selectSearchingService();
		$query  = $search->createQuery()->setFilter(
			$search->createFilter()
			       ->addFilter( Opus_Search_Filter_Simple::createCatchAll( 'Lorem' ) )
		);

		return $search->customSearch( $query )->getAllMatchesCount();
	}

	private function _addOneDocumentToIndex() {
		$document = new Opus_Document( $this->document_id );
		$this->indexer->addDocumentsToIndex( $document );
	}

	/**
	 *
	 * @param string $filename
	 * @param string $label
	 */
	private function _addFileToDocument( $filename, $label ) {
		$doc  = new Opus_Document( $this->document_id );
		$file = $doc->addFile();
		$file->setTempFile( APPLICATION_PATH . '/tests/fulltexts/' . $filename );
		$file->setPathName( $filename );
		$file->setLabel( $label );
		$file->setVisibleInFrontdoor( '1' );

		$doc->store();

		$this->indexer->addDocumentsToIndex( $doc );
	}

	public function testAttachFulltextToNull() {
		$this->markTestSkipped( 'not supported in Opus_Search API' );

		// TODO is this intended behaviour of a unit test?
		// apply a hack to be able to test a private method directly
		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'attachFulltextToXml' );
		$method->setAccessible( true );

		$indexer = new Opus_SolrSearch_Index_Indexer();
		$method->invokeArgs( $indexer, array( new DomDocument(), null, 1 ) );
	}

	/**
	 * Regression test for OPUSVIER-2240
	 */
	public function testIndexDocumentWithMultipleTitleMainInSameLanguage() {
		$doc = new Opus_Document();
		$doc->setServerState( 'published' );
		$doc->setLanguage( 'eng' );

		$title = new Opus_Title();
		$title->setValue( 'foo' );
		$title->setLanguage( 'eng' );
		$doc->addTitleMain( $title );

		$title = new Opus_Title();
		$title->setValue( 'bar' );
		$title->setLanguage( 'eng' );
		$doc->addTitleMain( $title );

		$doc->store();

		$exception = null;
		try {
			$this->indexer->addDocumentsToIndex( $doc );
		}
		catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotNull( $exception );
		$this->assertInstanceOf( 'Opus_Search_InvalidQueryException', $exception );

		$doc->deletePermanent();
	}

	/**
	 * Regression test for OPUSVIER-2240
	 */
	public function testIndexDocumentWithMultipleAbstractsInSameLanguage() {
		$doc = new Opus_Document();
		$doc->setServerState( 'published' );
		$doc->setLanguage( 'eng' );

		$title = new Opus_Title();
		$title->setValue( 'foo' );
		$title->setLanguage( 'eng' );
		$doc->addTitleAbstract( $title );

		$title = new Opus_Title();
		$title->setValue( 'bar' );
		$title->setLanguage( 'eng' );
		$doc->addTitleAbstract( $title );

		$doc->store();

		$exception = null;
		try {
			$this->indexer->addDocumentsToIndex( $doc );
		}
		catch ( Exception $e ) {
			$exception = $e;
		}

		$this->assertNotNull( $exception );
		$this->assertInstanceOf( 'Opus_Search_InvalidQueryException', $exception );

		$doc->deletePermanent();
	}

	/**
	 * Regression test for OPUSVIER-2240
	 *
	 * @expectedException Opus_SolrSearch_Index_Exception
	 */
	public function testIndexDocumentWithUnknownIndexField() {
		$this->markTestSkipped( 'not supported in Opus_Search API' );

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
                </add>' );

		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'sendSolrXmlToServer' );
		$method->setAccessible( true );

		$method->invoke( $this->indexer, $xml );
	}

	/**
	 * Regression test for OPUSVIER-2417
	 */
	public function testFulltextVisibilityIsConsideredInFacetForFrontdoorVisibleFulltext() {
		$this->markTestSkipped( 'not supported in Opus_Search API' );

/*		$doc = new Opus_Document( $this->document_id );
		$doc->setServerState( 'published' );
		$doc->setLanguage( 'eng' );

		$file = $doc->addFile();
		$file->setTempFile( APPLICATION_PATH . '/tests/fulltexts' . DIRECTORY_SEPARATOR . 'test.pdf' );
		$file->setPathName( 'test.pdf' );
		$file->setVisibleInFrontdoor( '1' );

		$doc->store();

		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getSolrXmlDocument' );
		$method->setAccessible( true );

		$xml       = $method->invoke( $this->indexer, $doc );
		$xmlString = $xml->saveXML();
		$this->assertContains( '<field name="has_fulltext">true</field>', $xmlString );
		$this->assertNotContains( '<field name="has_fulltext">false</field>', $xmlString );
		$this->assertContains( '<field name="fulltext_id_success">' . $file->getId() . ':' . $file->getRealHash( 'md5' ) . '</field>', $xmlString );
		$this->assertNotContains( '<field name="fulltext_id_failure">', $xmlString );

		$path = $this->files_dir . DIRECTORY_SEPARATOR . $doc->getId();
		unlink( $path . DIRECTORY_SEPARATOR . 'test.pdf' );
		rmdir( $path );*/
	}

	/**
	 * Regression test for OPUSVIER-2417
	 */
	public function testFulltextVisibilityIsConsideredInFacetForFrontdoorInvisibleFulltext() {
		$this->markTestSkipped( 'not supported in Opus_Search API' );

/*		$doc = new Opus_Document( $this->document_id );
		$doc->setServerState( 'published' );
		$doc->setLanguage( 'eng' );
		$file = $doc->addFile();
		$file->setTempFile( APPLICATION_PATH . '/tests/fulltexts' . DIRECTORY_SEPARATOR . 'test.pdf' );
		$file->setPathName( 'test.pdf' );
		$file->setVisibleInFrontdoor( '0' );
		$doc->store();

		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getSolrXmlDocument' );
		$method->setAccessible( true );

		$xml       = $method->invoke( $this->indexer, $doc );
		$xmlString = $xml->saveXML();
		$this->assertContains( '<field name="has_fulltext">false</field>', $xmlString );
		$this->assertNotContains( '<field name="has_fulltext">true</field>', $xmlString );
		$this->assertNotContains( '<field name="fulltext_id_success">', $xmlString );
		$this->assertNotContains( '<field name="fulltext_id_failure">', $xmlString );

		$path = $this->files_dir . DIRECTORY_SEPARATOR . $doc->getId();
		unlink( $path . DIRECTORY_SEPARATOR . 'test.pdf' );
		rmdir( $path );*/
	}

	/**
	 * Regression test for OPUSVIER-2417
	 */
	public function testFulltextVisibilityIsNotConsideredInFacet() {
		$this->markTestSkipped( 'not supported in Opus_Search API' );

/*		$doc = new Opus_Document( $this->document_id );
		$doc->setServerState( 'published' );
		$doc->setLanguage( 'eng' );
		$doc->store();

		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getSolrXmlDocument' );
		$method->setAccessible( true );

		$xml       = $method->invoke( $this->indexer, $doc );
		$xmlString = $xml->saveXML();
		$this->assertContains( '<field name="has_fulltext">false</field>', $xmlString );
		$this->assertNotContains( '<field name="has_fulltext">true</field>', $xmlString );
		$this->assertNotContains( '<field name="fulltext_id_success">', $xmlString );
		$this->assertNotContains( '<field name="fulltext_id_failure">', $xmlString );*/
	}

	public function testHandlingOfNonExtractableFulltext() {
		$this->markTestSkipped( 'not supported in Opus_Search API' );

/*		$doc = new Opus_Document( $this->document_id );
		$doc->setServerState( 'published' );
		$doc->setLanguage( 'eng' );

		$file = $doc->addFile();
		$file->setTempFile( APPLICATION_PATH . '/tests/fulltexts' . DIRECTORY_SEPARATOR . 'test-invalid.pdf' );
		$file->setPathName( 'test-invalid.pdf' );
		$file->setVisibleInFrontdoor( '1' );

		$doc->store();

		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getSolrXmlDocument' );
		$method->setAccessible( true );

		$xml       = $method->invoke( $this->indexer, $doc );
		$xmlString = $xml->saveXML();
		$this->assertContains( '<field name="has_fulltext">true</field>', $xmlString );
		$this->assertNotContains( '<field name="has_fulltext">false</field>', $xmlString );
		$this->assertContains( '<field name="fulltext_id_failure">' . $file->getId() . ':' . $file->getRealHash( 'md5' ) . '</field>', $xmlString );
		$this->assertNotContains( '<field name="fulltext_id_success">', $xmlString );

		$path = $this->files_dir . DIRECTORY_SEPARATOR . $doc->getId();
		unlink( $path . DIRECTORY_SEPARATOR . 'test-invalid.pdf' );
		rmdir( $path );*/
	}

	/**
	 * test changed return value (fluent interface)
	 */
	public function testFluentInterface() {
		$doc = new Opus_Document();
		$doc->setServerState( 'published' );
		$doc->setLanguage( 'eng' );
		$doc->store();

		$indexer = Opus_Search_Service::selectIndexingService();

		$result = $indexer->addDocumentsToIndex( $doc );

		$this->assertTrue( $result instanceof Opus_Search_Indexing, 'Expected instance of Opus_Search_Indexing' );

		$result = $indexer->removeDocumentsFromIndex( $doc );

		$this->assertTrue( $result instanceof Opus_Search_Indexing, 'Expected instance of Opus_Search_Indexing' );

		$result = $indexer->removeDocumentsFromIndexById( $doc->getId() );

		$this->assertTrue( $result instanceof Opus_Search_Indexing, 'Expected instance of Opus_Search_Indexing' );
	}

	public function testIndexIsUpdatedSynchronouslyInSyncMode() {
		$this->adjustConfiguration( array(
			'runjobs' => array( 'asynchronous' => 0 )
        ) );

		$this->performDocumentExistsInIndexChecks();
	}

	public function testIndexIsUpdatedSynchronouslyInAsyncMode() {
		$this->markTestSkipped( 'Asynchronous index update is the expected behaviour in asynchronous mode so far.' );

		$this->adjustConfiguration( array(
			'runjobs' => array( 'asynchronous' => 1 )
	    ) );

		$this->performDocumentExistsInIndexChecks();
	}

	private function performDocumentExistsInIndexChecks() {
		// check that document with id $this->document_id is NOT in Solr index
		$this->assertFalse( $this->isTestDocumentInSearchIndex(), 'check #1 failed' );

		$doc = new Opus_Document( $this->document_id );
		$doc->setServerState( 'published' );
		$doc->store();

		// check that document with id $this->document_id is in Solr index
		$this->assertTrue( $this->isTestDocumentInSearchIndex(), 'check #2 failed' );

		$doc = new Opus_Document( $this->document_id );
		$doc->setServerState( 'unpublished' );
		$doc->store();

		// check that document with id $this->document_id is NOT in Solr index
		$this->assertFalse( $this->isTestDocumentInSearchIndex(), 'check #3 failed' );

		$doc = new Opus_Document( $this->document_id );
		$doc->setServerState( 'published' );
		$doc->store();

		// check that document with id $this->document_id is in Solr index
		$this->assertTrue( $this->isTestDocumentInSearchIndex(), 'check #4 failed' );

		$resultList          = $this->catchAll();
		$doc                 = new Opus_Document( $this->document_id );
		$serverDateModified1 = $doc->getServerDateModified()
		                           ->getUnixTimestamp();

		$this->assertEquals( $serverDateModified1, $resultList[0]->getServerDateModified()->getUnixTimestamp() );

		sleep( 1 );

		$doc = new Opus_Document( $this->document_id );
		$doc->setLanguage( 'eng' );
		$doc->store();

		// check that document with id $this->document_id is in Solr index
		$this->assertTrue( $this->isTestDocumentInSearchIndex(), 'check #5 failed' );

		$resultList          = $this->catchAll();
		$doc                 = new Opus_Document( $this->document_id );
		$serverDateModified2 = $doc->getServerDateModified()
		                           ->getUnixTimestamp();

		$this->assertEquals( $serverDateModified2, $resultList[0]->getServerDateModified()->getUnixTimestamp() );
		$this->assertTrue( $serverDateModified1 < $serverDateModified2 );

		$doc = new Opus_Document( $this->document_id );
		$doc->deletePermanent();

		// check that document with id $this->document_id is NOT in Solr index
		$this->assertFalse( $this->isTestDocumentInSearchIndex(), 'check #6 failed' );
	}

	private function isTestDocumentInSearchIndex() {
		$resultList = $this->catchAll();

		return ( count( $resultList ) == 1 ) && ( $this->document_id == $resultList[0]->getId() );
	}

	private function catchAll() {
		$search = Opus_Search_Service::selectSearchingService();

		return $search->customSearch( $search->createQuery() )->getResults();
	}

	public function testGetCachedFileNamePositiveCase() {
		$this->markTestSkipped( 'to be implemented on Opus_Search_FulltextFileCache' );

		$file = $this->createTestFile();

		// apply a hack to be able to test a private method directly
		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getCachedFileName' );
		$method->setAccessible( true );

		$indexer = new Opus_SolrSearch_Index_Indexer();
		$result  = $method->invokeArgs( $indexer, array( $file ) );
		$this->assertNotNull( $result );

		$path   = Opus_Config::get()->workspacePath;
		$this->assertEquals( $path . '/cache/solr_cache---901736df3fbc807121c46f9eaed8ff28-ff4ef4245da5b09786e3d3de8b430292fa081984db272d2b13ed404b45353d28.txt', $result );

		$this->removeTestFile( $file );
	}

	public function testGetCachedFileNameNegativeCase() {
		$this->markTestSkipped( 'to be implemented on Opus_Search_FulltextFileCache' );

		$file = $this->createTestFile();

		$this->removeTestFile( $file );

		// apply a hack to be able to test a private method directly
		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getCachedFileName' );
		$method->setAccessible( true );

		$indexer = new Opus_SolrSearch_Index_Indexer();
		$result  = $method->invokeArgs( $indexer, array( $file ) );
		$this->assertNull( $result );
	}

	public function testGetFulltextHashPositiveCase() {
		$this->markTestSkipped( 'to be implemented on Opus_Search_FulltextFileCache' );

		$file = $this->createTestFile();

		// apply a hack to be able to test a private method directly
		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getFulltextHash' );
		$method->setAccessible( true );

		$indexer = new Opus_SolrSearch_Index_Indexer();
		$result  = $method->invokeArgs( $indexer, array( $file ) );

		$this->assertEquals( '1:901736df3fbc807121c46f9eaed8ff28', $result );

		$this->removeTestFile( $file );
	}

	public function testGetFulltextHashNegativeCase() {
		$this->markTestSkipped( 'to be implemented on Opus_Search_FulltextFileCache' );

		$file = $this->createTestFile();

		$this->removeTestFile( $file );

		// apply a hack to be able to test a private method directly
		$class  = new ReflectionClass( 'Opus_SolrSearch_Index_Indexer' );
		$method = $class->getMethod( 'getFulltextHash' );

		$method->setAccessible( true );
		$indexer = new Opus_SolrSearch_Index_Indexer();
		$result  = $method->invokeArgs( $indexer, array( $file ) );

		$this->assertEquals( '1:', $result );
	}

	private function createTestFile() {
		$doc = new Opus_Document;
		$doc->store();

		$path = Opus_Config::get()->workspacePath;

		$testfile = $path . '/files/' . $doc->getId() . '/test.txt';
		if ( file_exists( $testfile ) ) {
			unlink( $testfile );
		}

		$file = $doc->addFile();
		$file->setTempFile( $path . '../fulltexts/test.txt' );
		$file->setPathName( 'test.txt' );
		$file->setLabel( 'foobarbaz' );

		$doc->store();

		return $file;
	}

	private function removeTestFile( $file ) {
		unlink( $file->getPath() );
	}

}

