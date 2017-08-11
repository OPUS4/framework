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


class Opus_Search_Solr_Document_XsltTest extends DocumentBasedTestCase {

	public function createConverter() {
		$converter = new Opus_Search_Solr_Document_Xslt( Opus_Search_Config::getDomainConfiguration( 'solr' ) );
	}

	public function testArticleConversion() {
		$document = $this->createDocument( 'article' );
		$this->assertInstanceOf( 'Opus_Document', $document );

		$converter = new Opus_Search_Solr_Document_Xslt( Opus_Search_Config::getDomainConfiguration( 'solr' ) );
		$solr = $converter->toSolrDocument( $document, new DOMDocument() );

		$this->assertInstanceOf( 'DOMDocument', $solr );

		$xpath  = new DOMXPath( $solr );
		$simple = simplexml_import_dom( $solr );

		$this->assertEquals( 'add', $simple->getName() );
		$this->assertNotNull( $simple->doc );
		$this->assertNotNull( $simple->doc[0]->field );

		$container = $xpath->query( '/add/doc' );
		$this->assertEquals( 1, $container->length );

		$allFields   = $xpath->query( '/add/doc/field' );
		$namedFields = $xpath->query( '/add/doc/field[@name]' );
		$this->assertTrue( $allFields->length == $namedFields->length );

		$field = $xpath->query( '/add/doc/field[@name="id"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( $document->getId(), $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="year"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( '', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="year_inverted"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="server_date_published"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="server_date_modified"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="language"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'Test Main Article', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="title_output"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="abstract"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="abstract_output"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="author"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="author_sort"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="fulltext"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="has_fulltext"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'false', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="fulltext_id_success"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="fulltext_id_failure"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="referee"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="persons"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="doctype"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'article', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="subject"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="belongs_to_bibliography"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'false', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="project"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="institute"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="collection_ids"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title_parent"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title_sub"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title_additional"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="series_ids"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[substring(@name,1,21)="series_number_for_id_"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[substring(@name,1,28)="doc_sort_order_for_seriesid_"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="creating_corporation"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'Creating, Inc.', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="contributing_corporation"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'Contributing, Inc.', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="publisher_name"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="publisher_place"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="identifier"]' );
		$this->assertEquals( 0, $field->length );

	}

	public function testBookConversion() {
		$document = $this->createDocument( 'book' );
		$this->assertInstanceOf( 'Opus_Document', $document );

		$converter = new Opus_Search_Solr_Document_Xslt( Opus_Search_Config::getDomainConfiguration( 'solr' ) );
		$solr = $converter->toSolrDocument( $document, new DOMDocument() );

		$this->assertInstanceOf( 'DOMDocument', $solr );

		$xpath  = new DOMXPath( $solr );
		$simple = simplexml_import_dom( $solr );

		$this->assertEquals( 'add', $simple->getName() );
		$this->assertNotNull( $simple->doc );
		$this->assertNotNull( $simple->doc[0]->field );

		$container = $xpath->query( '/add/doc' );
		$this->assertEquals( 1, $container->length );

		$allFields   = $xpath->query( '/add/doc/field' );
		$namedFields = $xpath->query( '/add/doc/field[@name]' );
		$this->assertTrue( $allFields->length == $namedFields->length );

		$field = $xpath->query( '/add/doc/field[@name="id"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( $document->getId(), $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="year"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( '', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="year_inverted"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="server_date_published"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="server_date_modified"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="language"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title_output"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="abstract"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="abstract_output"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="author"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertTrue( !!preg_match( '/jane\s+doe/i', $field->item( 0 )->nodeValue ) );

		$field = $xpath->query( '/add/doc/field[@name="author_sort"]' );
		$this->assertEquals( 1, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="fulltext"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="has_fulltext"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'false', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="fulltext_id_success"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="fulltext_id_failure"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="referee"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="persons"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="doctype"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'book', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="subject"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="belongs_to_bibliography"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'true', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="project"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="institute"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="collection_ids"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title_parent"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title_sub"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="title_additional"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="series_ids"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[substring(@name,1,21)="series_number_for_id_"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[substring(@name,1,28)="doc_sort_order_for_seriesid_"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="creating_corporation"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'Creating, Inc.', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="contributing_corporation"]' );
		$this->assertEquals( 1, $field->length );
		$this->assertEquals( 'Contributing, Inc.', $field->item( 0 )->nodeValue );

		$field = $xpath->query( '/add/doc/field[@name="publisher_name"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="publisher_place"]' );
		$this->assertEquals( 0, $field->length );

		$field = $xpath->query( '/add/doc/field[@name="identifier"]' );
		$this->assertEquals( 0, $field->length );

	}
}
