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
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_SolrSearch_SearcherTest extends TestCase {

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
        
        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::LATEST_DOCS);
        $query->setRows($rows);
        $searcher = new Opus_SolrSearch_Searcher();
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

        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::LATEST_DOCS);
        $query->setRows(1);
        $searcher = new Opus_SolrSearch_Searcher();
        $results = $searcher->search($query);
        
        $this->assertEquals(1, count($results));
        $result = $results->getResults();        
        $this->assertEquals($serverDateModified, $result[0]->getServerDateModified());
    }

    public function testIndexFieldServerDateModifiedIsCorrectAfterModification() {
        $doc = new Opus_Document();
        $doc->setLanguage('deu');
        $doc->setServerState('published');
        $doc->store();
        $id = $doc->getId();

        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::LATEST_DOCS);
        $query->setRows(1);
        $searcher = new Opus_SolrSearch_Searcher();
        $results = $searcher->search($query);
        $this->assertEquals(1, count($results));
        $result = $results->getResults();

        sleep(1);

        $doc = new Opus_Document($id);
        $doc->setLanguage('eng');
        $doc->store();

        $doc = new Opus_Document($id);
        $serverDateModified = $doc->getServerDateModified()->getUnixTimestamp();

        $this->assertTrue($serverDateModified > $result[0]->getServerDateModified());
    }

    public function testIndexFieldServerDateModifiedForDependentModelChanges() {
        $role = new Opus_CollectionRole();
        $role->setName('foobar-name');
        $role->setOaiName('foobar-oainame');
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $root = new Opus_Collection($root->getId());
        $root->setVisible(0);
        $root->store();

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $serverDateModified1 = $doc->getServerDateModified()->getUnixTimestamp();

        $result = $this->searchDocumentsAssignedToCollection($root->getId());
        $this->assertEquals(0, count($result));

        sleep(1);

        $doc = new Opus_Document();
        $doc->addCollection($root);
        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $serverDateModified2 = $doc->getServerDateModified()->getUnixTimestamp();

        $result = $this->searchDocumentsAssignedToCollection($root->getId());
        $this->assertEquals(1, count($result));

        sleep(1);

        $root = new Opus_Collection($root->getId());
        $root->setVisible(1);
        $root->store();

        $doc = new Opus_Document($doc->getId());
        $serverDateModified3 = $doc->getServerDateModified()->getUnixTimestamp();

        $result = $this->searchDocumentsAssignedToCollection($root->getId());
        $this->assertEquals(1, count($result));

        sleep(1);

        $root->delete();

        // document in search index was not updated: connection between document $doc
        // and collection $root is still present in search index
        $result = $this->searchDocumentsAssignedToCollection($root->getId());
        $this->assertEquals(1, count($result), 'Deletion of Collection was not propagated to Solr index');

        $doc = new Opus_Document($doc->getId());
        $serverDateModified4 = $doc->getServerDateModified()->getUnixTimestamp();

        sleep(1);

        // force rebuild of cache entry for current Opus_Document: cache removal
        // was issued by deletion of collection $root
        $xmlModel = new Opus_Model_Xml();
        $doc = new Opus_Document($doc->getId());
        $xmlModel->setModel($doc);
        $xmlModel->excludeEmptyFields();
        $xmlModel->setStrategy(new Opus_Model_Xml_Version1);
        $xmlModel->setXmlCache(new Opus_Model_Xml_Cache);
        $xmlModel->getDomDocument();

        // connection between document $doc and collection $root does not longer
        // exist in search index
        $result = $this->searchDocumentsAssignedToCollection($root->getId());
        $this->assertEquals(0, count($result));

        $doc = new Opus_Document($doc->getId());
        $serverDateModified5 = $doc->getServerDateModified()->getUnixTimestamp();

        $this->assertTrue($serverDateModified1 < $serverDateModified2);
        $this->assertTrue($serverDateModified2 < $serverDateModified3, 'Visibility Change of Collection was not observed by Document');
        $this->assertTrue($serverDateModified3 < $serverDateModified4, 'Deletion of Collection was not observed by Document');
        $this->assertTrue($serverDateModified4 == $serverDateModified5, 'Document and its dependet models were not changed: server_date_modified should not change');
    }

    private function searchDocumentsAssignedToCollection($collId) {
        $query = new Opus_SolrSearch_Query(Opus_SolrSearch_Query::SIMPLE);
        $query->setCatchAll('*:*');
        $query->addFilterQuery('collection_ids', $collId);
        $searcher = new Opus_SolrSearch_Searcher();
        $results = $searcher->search($query);
        return $results->getResults();
    }

}

