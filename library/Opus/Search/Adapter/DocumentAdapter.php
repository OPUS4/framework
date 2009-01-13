<?php

/**
 * Adapter to use the Documents from the framework in Module_Search
 * 
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
 * @category    Framework
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Search_Adapter_DocumentAdapter # extends Opus_Model_Document
{
	/**
	 * Attribute to store the Document as an Array
	 * 
	 * @var array data of the document in form of an array
	 * @access private
	 */
	private $documentData;

	/**
	 * Constructor
	 * 
	 * @param integer|array|Opus_Search_Adapter_DocumentAdapter $opusDocument (Optional) Data for the new Opus_Search_Adapter_DocumentAdapter-Object 
	 */
	public function __construct($opusDocument = null) {
		$this->documentData = array();
		if (is_int($opusDocument) === true) {
			$this->documentData['id'] = $opusDocument;
			$this->mapDocument();
		} else if (is_array($opusDocument) === true) {
			$this->documentData = $opusDocument;
		} else if (get_class($opusDocument) === 'Opus_Search_Adapter_DocumentAdapter') {
			$this->documentData = $opusDocument->getDocument();
		}
	}

	/**
	 * Returns the document data as an array
	 * 
	 * @return array Array with document data usable in Module_Search 
	 */
	public function getDocument() {
		return $this->documentData;
	}

	/**
	 * Maps the document data to array data usable in Module_Search
	 * 
	 * @return void
	 */
	private function mapDocument() {
		$document = new Opus_Model_Document($this->documentData['id']);
		try	{
			$title = $document->getTitleMain(0);
			$this->documentData['title'] = $title->getTitleAbstractValue();
		} catch (Exception $e) {
			$this->documentData['title'] = 'No title specified!';
		}
		if (is_array($document->getTitleAbstract()) === true ) {
			$abstract = $this->documentData['abstract'] = $document->getTitleAbstract(0)->getTitleAbstractValue();
		} else {
			$abstract = $this->documentData['abstract'] = $document->getTitleAbstract()->getTitleAbstractValue();
		}
		
		$this->documentData['frontdoorUrl'] = array(
			'module' => 'frontdoor',
			'controller' => 'index',
			'action' => 'index',
			'docId' => $this->documentData['id']
		);
		$this->documentData['fileUrl'] = array(
			'module' => 'frontdoor',
			'controller' => 'index',
			'action' => 'showfile',
			'docId' => $this->documentData['id'],
			'filename' => 'testfile.pdf'
		);

		#$authorsList = DummyData::getDummyPersons();
		#$autlist1 = new PersonsList();
		#$autlist1->add($authorsList[0]);
		#$autlist1->add($authorsList[1]);
		unset($authors);
		$authors = array();
		$c = count($document->getPersonAuthor());
		try {
			for ($n = 0; $n < $c; $n++) {
				array_push($authors, $document->getPersonAuthor($n));
			}
		} catch (Exception $e) {
			// do nothing, as there is the exception that no author is specified
			if ($e->getCode() === 0) { 
				$this->documentData['author'] = 'No author specified';
			} else {
				$this->documentData['author'] = $e->getMessage();
			}
		}
		$this->documentData['author'] = new Opus_Search_List_PersonsList();
		if (count($authors) > 0) {
			foreach ($authors as $authorId) {
				$this->documentData['author']->add(new Opus_Search_Adapter_PersonAdapter(array('id' => $authorId->getId(), 'firstName' => $authorId->getFirstName(), 'lastName' => $authorId->getLastName())));
			}
		} else {
			$this->documentData['author']->add(new Opus_Search_Adapter_PersonAdapter(array('id' => 0, 'firstName' => 'Unknown', 'lastName' => 'Unknown')));
		}
	}
}