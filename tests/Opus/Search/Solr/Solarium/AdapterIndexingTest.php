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
 * @category    Application
 * @author      Thomas Urban <thomas.urban@cepharum.de>
 * @copyright   Copyright (c) 2009-2015, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


class Opus_Search_Solr_Solarium_AdapterIndexingTest extends DocumentBasedTestCase {

	public function testService() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );
		$this->assertInstanceOf( 'Opus_Search_Solr_Solarium_Adapter', $service );
	}

	/**
	 * @expectedException Opus_Search_Exception
	 */
	public function testDisfunctServiceFails() {
		Opus_Search_Service::selectIndexingService( 'disfunct' );
	}

	public function testRemoveAllDocuments() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );
		$service->removeAllDocumentsFromIndex();
	}

	public function testIndexingArticleWithoutFiles() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );
	}

	public function testIndexingBookWithoutFiles() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'book' );

		$service->addDocumentsToIndex( $doc );
	}

	public function testIndexingArticleWithPublicFile() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$this->addFileToDocument( $doc, "test.pdf", "PDF Document", true );

		$service->addDocumentsToIndex( $doc );
	}

	public function testIndexingArticleWithPublicFiles() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$this->addFileToDocument( $doc, "test.pdf", "PDF Document", true );
		$this->addFileToDocument( $doc, "test.ps", "PS Document", true );

		$service->addDocumentsToIndex( $doc );
	}

	public function testIndexingArticleWithHiddenFile() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$this->addFileToDocument( $doc, "test.pdf", "PDF Document", false );

		$service->addDocumentsToIndex( $doc );
	}

	public function testIndexingArticleWithHiddenFiles() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$this->addFileToDocument( $doc, "test.pdf", "PDF Document", false );
		$this->addFileToDocument( $doc, "test.ps", "PS Document", false );

		$service->addDocumentsToIndex( $doc );
	}

	public function testIndexingArticleWithMixedFiles() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$this->addFileToDocument( $doc, "test.pdf", "PDF Document", false );
		$this->addFileToDocument( $doc, "test.ps", "PS Document", true );
		$this->addFileToDocument( $doc, "test.html", "HTML Document", true );
		$this->addFileToDocument( $doc, "test.odt", "ODT Document", false );

		$service->addDocumentsToIndex( $doc );
	}

	public function testRemovingIndexedArticle() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );

		$service->removeDocumentsFromIndex( $doc );
	}

	public function testRemovingIndexedArticleById() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );

		$service->removeDocumentsFromIndexById( $doc->getId() );
	}

	public function testRemovingIndexedArticleTwiceFails() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );

		$service->removeDocumentsFromIndex( $doc );
		$service->removeDocumentsFromIndex( $doc );
	}

	public function testRemovingIndexedArticleByIdTwiceFails() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );

		$service->removeDocumentsFromIndexById( $doc->getId() );
		$service->removeDocumentsFromIndexById( $doc->getId() );
	}

	public function testMixedRemovingIndexedArticleTwiceFails() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );

		$service->removeDocumentsFromIndex( $doc );
		$service->removeDocumentsFromIndexById( $doc->getId() );
	}

	public function testMixedRemovingIndexedArticleTwiceFailsAgain() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );

		$service->removeDocumentsFromIndexById( $doc->getId() );
		$service->removeDocumentsFromIndex( $doc );
	}

	public function testRemovingNonIndexedArticleFails() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->removeDocumentsFromIndex( $doc );
	}

	public function testRemovingNonIndexedArticleByIdFails() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->removeDocumentsFromIndexById( $doc->getId() );
	}

	public function testIndexingMultipleDocument() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$docA = $this->createDocument( 'article' );
		$docB = $this->createDocument( 'book' );
		$docC = $this->createDocument( 'monograph' );
		$docD = $this->createDocument( 'report' );

		$service->addDocumentsToIndex( array( $docA, $docB, $docC, $docD ) );
	}

	public function testRemovingMultipleIndexedDocuments() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$docA = $this->createDocument( 'article' );
		$docB = $this->createDocument( 'book' );
		$docC = $this->createDocument( 'monograph' );
		$docD = $this->createDocument( 'report' );

		$service->addDocumentsToIndex( array( $docA, $docB, $docC, $docD ) );
		$service->removeDocumentsFromIndex( array( $docA, $docB, $docC, $docD ) );
	}

	public function testMultiplyRemovingMultipleIndexedDocumentsFails() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$docA = $this->createDocument( 'article' );
		$docB = $this->createDocument( 'book' );
		$docC = $this->createDocument( 'monograph' );
		$docD = $this->createDocument( 'report' );

		$service->addDocumentsToIndex( array( $docA, $docB, $docC, $docD ) );
		$service->removeDocumentsFromIndex( array( $docA, $docB, $docC, $docD ) );
		$service->removeDocumentsFromIndex( array( $docA, $docB, $docC, $docD ) );
	}

	public function testMultiplyRemovingMultipleIndexedDocumentsFailsAgain() {
		$service = Opus_Search_Service::selectIndexingService( null, 'solr' );

		$docA = $this->createDocument( 'article' );
		$docB = $this->createDocument( 'book' );
		$docC = $this->createDocument( 'monograph' );
		$docD = $this->createDocument( 'report' );

		$service->addDocumentsToIndex( array( $docA, $docB, $docC, $docD ) );
		$service->removeDocumentsFromIndex( array( $docA, $docB, $docC, $docD ) );
		$service->removeDocumentsFromIndex( array( $docA ) );
	}

	/**
	 * @expectedException Opus_Search_Exception
	 */
	public function testIndexingArticleOnDisfunctServiceFails() {
		$service = Opus_Search_Service::selectIndexingService( 'disfunct', 'solr' );

		$doc = $this->createDocument( 'article' );

		$service->addDocumentsToIndex( $doc );
	}

}
