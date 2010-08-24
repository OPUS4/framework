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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test search indexing.
 *
 * @category   Test
 * @package    Opus_Search
 *
 * @group SearchIndexIndexerTests
 */
class Opus_Search_Index_Solr_IndexerTest extends TestCase {

    /**
     * @var Opus_Search_Index_Solr_Indexer
     */
    protected $indexer;
    /**
     * @var int
     */
    protected $document_id;
    /**
     * Valid document data.
     *
     * @var array  An array of arrays of arrays. Each 'inner' array must be an
     * associative array that represents valid document data.
     */
    protected static $_validDocumentData = array(
        'Type' => 'article',
        'Language' => 'de',
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
        'ServerDateUnlocking' => '2008-12-01',
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
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();
        $this->indexer = new Opus_Search_Index_Solr_Indexer();
        $this->indexer->deleteAllDocs();
        $this->indexer->commit();

        $document = new Opus_Document();
        foreach (self::$_validDocumentData as $fieldname => $value) {
            $callname = 'set' . $fieldname;
            $document->$callname($value);
        }
        $document->store();
        $this->document_id = $document->getId();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        parent::tearDown();
        $this->indexer = new Opus_Search_Index_Solr_Indexer();
        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        // remove test documents under tests/workspace/files/$document_id
        $dirname = "workspace/files/" . $this->document_id;
        if (file_exists($dirname)) {
            foreach (glob($dirname . "/*") as $filename) {
                unlink($filename);
            }
            rmdir($dirname);
        }
    }

    public function testDeleteAllDocsInConstructor() {
        $this->_addOneDocumentToIndex();
        $this->indexer = new Opus_Search_Index_Solr_Indexer(true);
        $this->assertEquals(0, $this->_getNumberOfIndexDocs());

        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        $this->_addOneDocumentToIndex();
        $this->indexer = new Opus_Search_Index_Solr_Indexer(false);
        $this->assertEquals(1, $this->_getNumberOfIndexDocs());

        $this->indexer->deleteAllDocs();
        $this->indexer->commit();
        $this->_addOneDocumentToIndex();
        $this->indexer = new Opus_Search_Index_Solr_Indexer();
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
        $this->setExpectedException('Opus_Search_Index_Solr_Exception');
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
        $file->setDestinationPath('workspace/files');
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
        return $searcher->search($query)->getNumberOfHits();
    }

    private function _searchTestFulltext() {
        $searcher = new Opus_SolrSearch_Searcher();
        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::SIMPLE);
        $query->setCatchAll('Lorem');
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
        $file->setSourcePath('fulltexts');
        $file->setTempFile($filename);
        $file->setDestinationPath('workspace/files');
        $file->setPathName($filename);
        $file->setLabel($label);

        $doc->store();

        $this->indexer->addDocumentToEntryIndex($doc);
        $this->indexer->commit();
    }

}
?>
