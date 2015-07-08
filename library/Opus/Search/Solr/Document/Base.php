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

abstract class Opus_Search_Solr_Document_Base {

	public function __construct( Zend_Config $options ) {}

	/**
	 * Retrieves XML describing model data of provided Opus document.
	 *
	 * @param Opus_Document $opusDoc
	 * @return DOMDocument
	 */
	protected function getModelXml( Opus_Document $opusDoc ) {
		// Set up caching xml-model and get XML representation of document.
		$caching_xml_model = new Opus_Model_Xml;
		$caching_xml_model->setModel( $opusDoc );
		$caching_xml_model->excludeEmptyFields();
		$caching_xml_model->setStrategy( new Opus_Model_Xml_Version1 );
		$cache = new Opus_Model_Xml_Cache( $opusDoc->hasPlugin( 'Opus_Document_Plugin_Index' ) );
		$caching_xml_model->setXmlCache( $cache );

		$modelXml = $caching_xml_model->getDomDocument();

		// extract fulltext from file and append it to the generated xml.
		$this->attachFulltextToXml( $modelXml, $opusDoc->getFile(), $opusDoc->getId() );

		return $modelXml;
	}

	/**
	 * Appends fulltext data of every listen file to provided XML document.
	 *
	 * @param DomDocument $modelXml
	 * @param Opus_File[] $files
	 * @param string $docId ID of document
	 * @return void
	 */
	private function attachFulltextToXml( $modelXml, $files, $docId ) {

		// get root element of XML document containing document's information
		$docXml = $modelXml->getElementsByTagName( 'Opus_Document' )->item( 0 );
		if ( is_null( $docXml ) ) {
			Opus_Log::get()->warn( 'An error occurred while attaching fulltext information to the xml for document with id ' . $docId );
			return;
		}


		// only consider files which are visible in frontdoor
		$files = array_filter( $files, function ( $file ) {
			/** @var Opus_File $file */
			return $file->getVisibleInFrontdoor() === '1';
		} );

		if ( !count( $files ) ) {
			// any attached file is hidden from public
			$docXml->appendChild( $modelXml->createElement( 'Has_Fulltext', 'false' ) );
			return;
		}

		$docXml->appendChild( $modelXml->createElement( 'Has_Fulltext', 'true' ) );


		// fetch reference on probably separate service for extracting fulltext data
		$extractingService = Opus_Search_Service::selectExtractingService();

		// extract fulltext data for every file left in set after filtering before
		foreach ( $files as $file ) {
			$fulltext = '';

			try {
                $fulltext = $extractingService->extractDocumentFile($file);
                $fulltext = trim(iconv("UTF-8", "UTF-8//IGNORE", $fulltext));
            }
            catch (Opus_Search_Exception $se) {
                Opus_Log::get()->err( 'An error occurred while getting fulltext data for document with id ' . $docId . ': ' . $se->getMessage() );
			}
            catch ( Opus_Storage_Exception $se ) {
                Opus_Log::get()->err( 'An error occurred while getting fulltext data for document with id ' . $docId . ': ' . $se->getMessage() );
			}

			if ( $fulltext != '' ) {
				$element = $modelXml->createElement( 'Fulltext_Index' );
				$element->appendChild( $modelXml->createCDATASection( $fulltext ) );
				$docXml->appendChild( $element );

				$element = $modelXml->createElement( 'Fulltext_ID_Success' );
				$element->appendChild( $modelXml->createTextNode( $this->getFulltextHash( $file ) ) );
				$docXml->appendChild( $element );
			} else {
				$element = $modelXml->createElement( 'Fulltext_ID_Failure' );
				$element->appendChild( $modelXml->createTextNode( $this->getFulltextHash( $file ) ) );
				$docXml->appendChild( $element );
			}
		}
	}

	/**
	 *
	 * @param Opus_File $file
	 * @return string
	 */
	private function getFulltextHash( Opus_File $file ) {
		$hash = '';

		try {
			$hash = $file->getRealHash( 'md5' );
		} catch ( Exception $e ) {
			Opus_Log::get()->err('could not compute MD5 hash for ' . $file->getPath() . ' : ' . $e);
		}

		return $file->getId() . ':' . $hash;
	}


	/*
	 *
	 * --- abstract part of API ---
	 *
	 */

	/**
	 * Derives Solr-compatible description of document from provided Opus
	 * document.
	 *
	 * @note Parameter $solrDoc must pass reference on object providing proper
	 *       API for describing Solr-compatible document. On return the same
	 *       reference is returned.
	 *
	 * @param Opus_Document $opusDoc
	 * @param mixed $solrDoc depends on derived implementation
	 * @return mixed reference provided in parameter $solrDoc
	 */
	abstract public function toSolrDocument( Opus_Document $opusDoc, $solrDoc );

}
