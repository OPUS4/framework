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
 * @package     Opus_Util
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Util_ConsistencyCheckTest extends TestCase {

    private $doc;

    private $docId;

    private $indexHost;

    public function setUp() {
        parent::setUp();

	    $this->doc = new Opus_Document();
        $this->doc->setServerState('published');
        $this->docId = $this->doc->store();
   }

    public function testWithConsistentState() {
        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is in search index');

        $consistencyCheck = new Opus_Util_ConsistencyCheck();
        $consistencyCheck->run();

        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is still in search index (was left unchanged)');
    }

    public function testWithInconsistentStateAfterDeletion() {
        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is in search index');

        $this->manipulateSolrConfig();
        $this->doc->delete();
        $this->restoreSolrConfig();

        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is in search index');

        $consistencyCheck = new Opus_Util_ConsistencyCheck();
        $consistencyCheck->run();

        $this->assertFalse($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is not in search index (was deleted)');
    }

    public function testWithInconsistentStateAfterPermanentDeletion() {
        $this->manipulateSolrConfig();
        $this->doc->deletePermanent();

        $this->restoreSolrConfig();
        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is in search index');

        $consistencyCheck = new Opus_Util_ConsistencyCheck();
        $consistencyCheck->run();

        $this->assertFalse($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is not in search index (was deleted)');
    }

    public function testWithInconsistentStateAfterServerStateChange() {
        $this->manipulateSolrConfig();

        try {
            $this->doc->setServerState('unpublished');
            $this->doc->store();
        } catch ( Opus_Search_Exception $e ) {
        }

        $this->restoreSolrConfig();
        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is in search index');

        $consistencyCheck = new Opus_Util_ConsistencyCheck();
        $consistencyCheck->run();

        $this->assertFalse($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is not in search index (is unpublished)');
    }

    public function testWithInconsistentStateAfterModifyingDocument() {
	    $searcher = Opus_Search_Service::selectSearchingService();
	    $query    = Opus_Search_QueryFactory::selectDocumentById( $searcher, $this->docId );

	    $result = $searcher->customSearch( $query );
        $resultList = $result->getReturnedMatches();

        $this->assertEquals(1, $result->getAllMatchesCount(), 'asserting that document ' . $this->docId . ' is in search index');
        $this->assertTrue($resultList[0]->getServerDateModified()->getUnixTimestamp() == $this->doc->getServerDateModified()->getUnixTimestamp());

        $this->manipulateSolrConfig();

        sleep(1);
        $this->doc->setLanguage('eng');
        $this->doc->store();

        $this->restoreSolrConfig();

        $searcher = Opus_Search_Service::selectSearchingService();
        $query    = Opus_Search_QueryFactory::selectDocumentById( $searcher, $this->docId );

        $result = $searcher->customSearch( $query );
        $resultList = $result->getReturnedMatches();

        $this->assertEquals(1, $result->getAllMatchesCount(), 'asserting that document ' . $this->docId . ' is in search index');
        $this->assertTrue($resultList[0]->getServerDateModified()->getUnixTimestamp() < $this->doc->getServerDateModified()->getUnixTimestamp(), 'change of serverDateModified is not reflected in Solr index');

        $consistencyCheck = new Opus_Util_ConsistencyCheck();
        $consistencyCheck->run();

	    $searcher = Opus_Search_Service::selectSearchingService();
	    $query    = Opus_Search_QueryFactory::selectDocumentById( $searcher, $this->docId );

	    $result = $searcher->customSearch( $query );
	    $resultList = $result->getReturnedMatches();

	    $this->assertEquals(1, $result->getAllMatchesCount(), 'asserting that document ' . $this->docId . ' is in search index');
        $this->assertTrue($resultList[0]->getServerDateModified()->getUnixTimestamp() == $this->doc->getServerDateModified()->getUnixTimestamp());
    }

    public function testWithInconsistentStateAfterIndexDeletion() {
        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is in search index');

        // remove document from search index directly
	    $indexer = Opus_Search_Service::selectIndexingService();
	    $indexer->removeDocumentsFromIndexById( $this->docId );

        $this->assertFalse($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is not in search index');

        $consistencyCheck = new Opus_Util_ConsistencyCheck();
        $consistencyCheck->run();

        $this->assertTrue($this->isDocumentInSearchIndex(), 'asserting that document ' . $this->docId . ' is in search index');
    }

    private function manipulateSolrConfig() {
        $this->dropDeprecatedConfiguration();

        $config = Zend_Registry::get('Zend_Config');
        $this->indexHost = $config->searchengine->solr->default->service->endpoint;

        $this->adjustConfiguration( array(), function( $config ) {
            $config->searchengine->solr->default->service->default->endpoint = new Zend_Config( array( 'primary' => array(
                'host' => '1.2.3.4',
                'port' => '8983',
                'path' => '/solr/solr',
            ) ) );

            return $config;
        } );

	    $this->assertEquals( '1.2.3.4', Opus_Search_Config::getServiceConfiguration( 'index', null, 'solr' )->endpoint->primary->host );

        Opus_Search_Service::dropCached();
    }

    private function restoreSolrConfig() {
        $saved = $this->indexHost;

        $this->adjustConfiguration( array(), function( $config ) use ( $saved ) {
            $config->searchengine->solr->default->service->default->endpoint = $saved;

            return $config;
        } );

	    $this->assertNotEquals( 'example.org', Opus_Search_Config::getServiceConfiguration( 'index', null, 'solr' )->endpoint->primary->host );

        Opus_Search_Service::dropCached();
    }

    private function isDocumentInSearchIndex() {
	    $searcher = Opus_Search_Service::selectSearchingService();
	    $query    = Opus_Search_QueryFactory::selectDocumentById( $searcher, $this->docId );
        $result   = $searcher->customSearch( $query );
        return $result->getAllMatchesCount() == 1;
    }
}
