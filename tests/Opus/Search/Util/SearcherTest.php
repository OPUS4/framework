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
 * @package     Opus_SolrSearch
 * @author      Sascha Szott <szott@zib.de>
 * @author      Michael Lang <lang@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2015, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Search_Util_SearcherTest extends TestCase {

    public function tearDown() {
        $this->clearFiles();

        parent::tearDown();
    }

    public function testLatestDocumentsQuery() {
        $rows = 5;
        $ids = array();
        for ($i = 0; $i < $rows; $i++) {
            $document = new Opus_Document();
            $document->setServerState('published');
            $document->store();
            sleep(1);
            array_push($ids, $document->getId());
        }

        $query = new Opus_Search_Util_Query(Opus_Search_Util_Query::LATEST_DOCS);
        $query->setRows($rows);
        $searcher = new Opus_Search_Util_Searcher();
        $results = $searcher->search($query);

        $i = $rows - 1;
        foreach ($results->getResults() as $result) {
            $this->assertEquals($ids[$i], $result->getId());
            $i--;
        }
        $this->assertEquals(-1, $i);
    }

    public function testIndexFieldServerDateModifiedIsPresent() {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->store();

        $id = $doc->getId();
        $doc = new Opus_Document($id);
        $serverDateModified = $doc->getServerDateModified()->getUnixTimestamp();

        $query = new Opus_Search_Util_Query(Opus_Search_Util_Query::LATEST_DOCS);
        $query->setRows(1);
        $searcher = new Opus_Search_Util_Searcher();
        $results = $searcher->search($query);

        $this->assertEquals(1, count($results));
        $result = $results->getResults();
        $this->assertEquals($serverDateModified, $result[0]->getServerDateModified()->getUnixTimestamp());
    }

    public function testIndexFieldServerDateModifiedIsCorrectAfterModification() {
        $doc = new Opus_Document();
        $doc->setLanguage('deu');
        $doc->setServerState('published');
        $doc->store();
        $id = $doc->getId();

        $query = new Opus_Search_Util_Query(Opus_Search_Util_Query::LATEST_DOCS);
        $query->setRows(1);
        $searcher = new Opus_Search_Util_Searcher();
        $results = $searcher->search($query);
        $this->assertEquals(1, count($results));
        $result = $results->getResults();

        sleep(1);

        $doc = new Opus_Document($id);
        $doc->setLanguage('eng');
        $doc->store();

        $doc = new Opus_Document($id);
        $serverDateModified = $doc->getServerDateModified()->getUnixTimestamp();

        $this->assertTrue($serverDateModified > $result[0]->getServerDateModified()->getUnixTimestamp());
    }

    /**
     * Das Reindexing wird erst durch die Aktualisierung des Caches getriggert.
     */
    public function testReindexingIsTriggeredInCaseOfDependentModelChanges() {
        $role = new Opus_CollectionRole();
        $role->setName('foobar-name');
        $role->setOaiName('foobar-oainame');
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $collId = $root->getId();

        $root = new Opus_Collection($collId);
        $root->setVisible(0);
        $root->store();

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $docId = $doc->store();

        $result = $this->searchDocumentsAssignedToCollection($collId);
        $this->assertEquals(0, count($result));
        $this->assertEquals(0, count($doc->getCollection()), "Document $docId was already assigned to a collection");

        sleep(1);

        $doc = new Opus_Document($docId);
        $doc->addCollection($root);
        $doc->store();

        $result = $this->searchDocumentsAssignedToCollection($collId);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, count($doc->getCollection()), "Document $docId is not assigned to collection $collId");
        $serverDateModified1 = $result[0]->getServerDateModified();

        sleep(1);

        $root = new Opus_Collection($collId);
        $root->setVisible(1);
        $root->store();

        $result = $this->searchDocumentsAssignedToCollection($collId);
        $this->assertEquals(1, count($result));
        $this->assertEquals(1, count($doc->getCollection()), "Document $docId is not assigned to collection $collId");

        $serverDateModified2 = $result[0]->getServerDateModified();
        $this->assertTrue($serverDateModified1 == $serverDateModified2);

        sleep(1);

        $root->delete();
        $doc = new Opus_Document($docId);

        // document in search index was not updated: connection between document $doc
        // and collection $root is still present in search index
        $result = $this->searchDocumentsAssignedToCollection($collId);
        $this->assertEquals(1, count($result), "Deletion of Collection $collId was propagated to Solr index");
        $this->assertEquals(0, count($doc->getCollection()), "Document $docId is still assigned to collection $collId");

        $serverDateModified3 = $result[0]->getServerDateModified();
        $this->assertTrue($serverDateModified2 == $serverDateModified3);

        sleep(1);

        // force rebuild of cache entry for current Opus_Document: cache removal
        // was issued by deletion of collection $root
        // side effect of cache rebuild: document will be updated in search index
        $xmlModel = new Opus_Model_Xml();
        $doc = new Opus_Document($docId);
        $xmlModel->setModel($doc);
        $xmlModel->excludeEmptyFields();
        $xmlModel->setStrategy(new Opus_Model_Xml_Version1);
        $xmlModel->setXmlCache(new Opus_Model_Xml_Cache);
        $xmlModel->getDomDocument();

        // connection between document $doc and collection $root does not longer
        // exist in search index
        $result = $this->searchDocumentsAssignedToCollection($collId);
        $this->assertEquals(0, count($result));

        $result = $this->searchDocumentsAssignedToCollection();
        $this->assertEquals(1, count($result));

        $serverDateModified4 = $result[0]->getServerDateModified();
        $this->assertTrue($serverDateModified3 < $serverDateModified4);
    }

    public function testServerDateModifiedIsUpdatedForDependentModelChanges() {
        $role = new Opus_CollectionRole();
        $role->setName('foobar-name');
        $role->setOaiName('foobar-oainame');
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $collId = $root->getId();

        $root = new Opus_Collection($collId);
        $root->setVisible(0);
        $root->store();

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertEquals(0, count($doc->getCollection()), "Document $docId was already assigned to collection $collId");
        $serverDateModified1 = $doc->getServerDateModified()->getUnixTimestamp();

        sleep(1);

        $doc = new Opus_Document($docId);
        $doc->addCollection($root);
        $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertEquals(1, count($doc->getCollection()), "Document $docId is not assigned to collection $collId");
        $serverDateModified2 = $doc->getServerDateModified()->getUnixTimestamp();
        $this->assertTrue($serverDateModified1 < $serverDateModified2);

        sleep(1);

        $root = new Opus_Collection($collId);
        $root->setVisible(1);
        $root->store();

        $doc = new Opus_Document($docId);
        $this->assertEquals(1, count($doc->getCollection()), "Document $docId is not assigned to collection $collId");
        $serverDateModified3 = $doc->getServerDateModified()->getUnixTimestamp();

        sleep(1);

        $root->delete();

        $doc = new Opus_Document($docId);
        $this->assertEquals(0, count($doc->getCollection()), "Document $docId is still assigned to collection $collId");
        $serverDateModified4 = $doc->getServerDateModified()->getUnixTimestamp();
        $this->assertTrue($serverDateModified3 < $serverDateModified4, 'Deletion of Collection was not observed by Document');

        sleep(1);

        // force rebuild of cache entry for current Opus_Document: cache removal
        // was issued by deletion of collection $root
        $xmlModel = new Opus_Model_Xml();
        $doc = new Opus_Document($docId);
        $xmlModel->setModel($doc);
        $xmlModel->excludeEmptyFields();
        $xmlModel->setStrategy(new Opus_Model_Xml_Version1);
        $xmlModel->setXmlCache(new Opus_Model_Xml_Cache);
        $xmlModel->getDomDocument();

        $doc = new Opus_Document($docId);
        $serverDateModified5 = $doc->getServerDateModified()->getUnixTimestamp();
        $this->assertTrue($serverDateModified4 == $serverDateModified5, 'Document and its dependet models were not changed: server_date_modified should not change');
    }

    private function searchDocumentsAssignedToCollection($collId = null) {
        $query = new Opus_Search_Util_Query(Opus_Search_Util_Query::SIMPLE);
        $query->setCatchAll('*:*');
        if (!is_null($collId)) {
            $query->addFilterQuery('collection_ids', $collId);
        }
        $searcher = new Opus_Search_Util_Searcher();
        $results = $searcher->search($query);
        return $results->getResults();
    }

    public function testFulltextFieldsForValidPDFFulltext() {
        $fileName = 'test.pdf';
        $id = $this->createDocWithFulltext($fileName);

        $result = $this->getSearchResultForFulltextTests();

        $success = $result->getFulltextIDsSuccess();

        $doc = new Opus_Document($id);
        $file = $doc->getFile();
        $value = $file[0]->getId() . ':' . $file[0]->getRealHash('md5');
        $this->removeFiles($id, $fileName);

        $this->assertEquals(1, count($success));
        $this->assertEquals($value, $success[0]);

        $failure = $result->getFulltextIDsFailure();
        $this->assertEquals(0, count($failure));
    }

    public function testFulltextFieldsForInvalidPDFFulltext() {
        $fileName = 'test-invalid.pdf';
        $id = $this->createDocWithFulltext($fileName);

        $result = $this->getSearchResultForFulltextTests();

        $failure = $result->getFulltextIDsFailure();

        $doc = new Opus_Document($id);
        $file = $doc->getFile();
        $value = $file[0]->getId() . ':' . $file[0]->getRealHash('md5');
        $this->removeFiles($id, $fileName);

        $this->assertEquals(1, count($failure));
        $this->assertEquals($value, $failure[0]);

        $success = $result->getFulltextIDsSuccess();
        $this->assertEquals(0, count($success));
    }

    /**
     * TODO fix cleanup
     */
    public function testFulltextFieldsForValidAndInvalidPDFFulltexts() {
        $fileName1 = 'test.pdf';
        $fileName2 = 'test-invalid.pdf';
        $id = $this->createDocWithFulltext($fileName1, $fileName2);

        $result = $this->getSearchResultForFulltextTests();

        $success = $result->getFulltextIDsSuccess();
        $this->assertEquals(1, count($success));

        $failure = $result->getFulltextIDsFailure();
        $this->assertEquals(1, count($failure));

        $doc = new Opus_Document($id);
        $file = $doc->getFile();
        $value = $file[0]->getId() . ':' . $file[0]->getRealHash('md5');
        $this->assertEquals($value, $success[0]);

        $value = $file[1]->getId() . ':' . $file[1]->getRealHash('md5');
        $this->assertEquals($value, $failure[0]);

        $this->removeFiles($id, $fileName1, $fileName2);
    }

    public function testFulltextFieldsForTwoValidDFFulltexts() {
        $fileName1 = 'test.pdf';
        $fileName2 = 'test.txt';
        $id = $this->createDocWithFulltext($fileName1, $fileName2);

        $result = $this->getSearchResultForFulltextTests();

        $success = $result->getFulltextIDsSuccess();
        $failure = $result->getFulltextIDsFailure();

        $doc = new Opus_Document($id);
        $file = $doc->getFile();
        $valueFile1 = $file[0]->getId() . ':' . $file[0]->getRealHash('md5');
        $valueFile2 = $file[1]->getId() . ':' . $file[1]->getRealHash('md5');
        $this->removeFiles($id, $fileName1, $fileName2);

        $this->assertEquals(2, count($success));
        $this->assertEquals(0, count($failure));
        $this->assertEquals($valueFile1, $success[0]);
        $this->assertEquals($valueFile2, $success[1]);

    }

    public function testGetDefaultRows() {
        $rows = Opus_Search_Util_Query::getDefaultRows();
        $config = Zend_Registry::get('Zend_Config');
        if (isset($config->searchengine->solr->numberOfDefaultSearchResults)) {
            $this->assertTrue($rows == $config->searchengine->solr->numberOfDefaultSearchResults);
        }
        else {
            $this->assertTrue($rows == Opus_Search_Util_Query::DEFAULT_ROWS);
        }

    }

    private function createDocWithFulltext($fulltext1, $fulltext2 = null) {
        $doc = new Opus_Document();
        $doc->setServerState('published');

        $fulltextDir = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR .
            'fulltexts' . DIRECTORY_SEPARATOR;

        $file = $doc->addFile();
        $file->setTempFile($fulltextDir . $fulltext1);
        $file->setPathName($fulltext1);
        $file->setLabel($fulltext1);
        $file->setVisibleInFrontdoor('1');
        $doc->store();

        if (!is_null($fulltext2)) {
            $doc = new Opus_Document($doc->getId());
            $file = $doc->addFile();
            $file->setTempFile($fulltextDir . $fulltext2);
            $file->setPathName($fulltext2);
            $file->setLabel($fulltext2);
            $file->setVisibleInFrontdoor('1');
            $doc->store();
        }

        return $doc->getId();
    }

    private function removeFiles($docId, $fulltext1, $fulltext2 = null) {
        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $docId;
        unlink($path . DIRECTORY_SEPARATOR . $fulltext1);
        if (!is_null($fulltext2)) {
            unlink($path . DIRECTORY_SEPARATOR . $fulltext2);
        }
        rmdir($path);
    }

    private function getSearchResultForFulltextTests() {
        $query = new Opus_Search_Util_Query(Opus_Search_Util_Query::SIMPLE);
        $query->setCatchAll('*:*');
        $searcher = new Opus_Search_Util_Searcher();
        $results = $searcher->search($query)->getResults();
        $this->assertEquals(1, count($results));
        return $results[0];
    }

}

