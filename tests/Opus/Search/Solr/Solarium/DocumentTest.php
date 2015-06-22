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


class Opus_Search_Solr_Solarium_DocumentTest extends DocumentBasedTestCase {

	public function testConvertingArticle() {
		$service = new \Solarium\Client();
		$update  = $service->createUpdate();

		$article = $this->createDocument( 'article' );

		$converter = new Opus_Search_Solr_Solarium_Document( Opus_Search_Config::getDomainConfiguration( 'solr' ) );
		$solrDoc   = $converter->toSolrDocument( $article, $update->createDocument() );
		$this->assertInstanceOf( '\Solarium\QueryType\Update\Query\Document\Document', $solrDoc );

		$fields = $solrDoc->getFields();

		$this->assertArrayHasKey( 'id', $fields );
		$this->assertArrayHasKey( 'year', $fields );
		$this->assertArrayHasKey( 'year_inverted', $fields );
		//$this->assertArrayHasKey( 'server_date_published', $fields );
		$this->assertArrayHasKey( 'server_date_modified', $fields );
		$this->assertArrayHasKey( 'language', $fields );
		$this->assertArrayHasKey( 'title', $fields );
		//$this->assertArrayHasKey( 'title_output', $fields );
		//$this->assertArrayHasKey( 'abstract', $fields );
		//$this->assertArrayHasKey( 'abstract_output', $fields );
		//$this->assertArrayHasKey( 'author', $fields );
		$this->assertArrayHasKey( 'author_sort', $fields );
		//$this->assertArrayHasKey( 'fulltext', $fields );
		$this->assertArrayHasKey( 'has_fulltext', $fields );
		//$this->assertArrayHasKey( 'fulltext_id_success', $fields );
		//$this->assertArrayHasKey( 'fulltext_id_failure', $fields );
		//$this->assertArrayHasKey( 'referee', $fields );
		//$this->assertArrayHasKey( 'persons', $fields );
		$this->assertArrayHasKey( 'doctype', $fields );
		//$this->assertArrayHasKey( 'subject', $fields );
		$this->assertArrayHasKey( 'belongs_to_bibliography', $fields );
		//$this->assertArrayHasKey( 'project', $fields );
		//$this->assertArrayHasKey( 'app_area', $fields );
		//$this->assertArrayHasKey( 'institute', $fields );
		//$this->assertArrayHasKey( 'collection_ids', $fields );
		//$this->assertArrayHasKey( 'title_parent', $fields );
		//$this->assertArrayHasKey( 'title_sub', $fields );
		//$this->assertArrayHasKey( 'title_additional', $fields );
		$this->assertArrayHasKey( 'creating_corporation', $fields );
		$this->assertArrayHasKey( 'contributing_corporation', $fields );
		//$this->assertArrayHasKey( 'publisher_name', $fields );
		//$this->assertArrayHasKey( 'identifier', $fields );
	}

}
