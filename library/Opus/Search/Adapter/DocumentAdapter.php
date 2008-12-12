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
			//parent::__construct($opusDocument);
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
		#print_r($title);
		#$abstract = $document->getTitleAbstract();
		#$abs = $abstract->getTitleAbstractValue();
		#$this->documentData['abstract'] = $abs;
		$this->documentData['frontdoorUrl'] = array(
			'module' => 'frontdoor',
			'controller' => 'index',
			'action' => 'index',
			'id' => $this->documentData['id']
		);
		$this->documentData['fileUrl'] = array(
			'module' => 'frontdoor',
			'controller' => 'index',
			'action' => 'showfile',
			'id' => $this->documentData['id'],
			'filename' => 'testfile.pdf'
		);

		$authorsList = DummyData::getDummyPersons();
		$autlist1 = new PersonsList();
		$autlist1->add($authorsList[0]);
		$autlist1->add($authorsList[1]);
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
		#print_r($authors);
		if (count($authors) > 0) {
			$this->documentData['author'] = new PersonsList();
			foreach ($authors as $authorId) {
				$this->documentData['author']->add(new Opus_Search_Adapter_PersonAdapter(array('id' => $authorId->getId(), 'firstName' => $authorId->getFirstName(), 'lastName' => $authorId->getLastName())));
			}
		} else {
			$this->documentData['author'] = $autlist1;
		}
		#$this->documentData["documentType"] = $this->getBuilder()->getDocumentType()->getName();
		#Fields that should be set by this method 
		#$this->documentData["author"] = PersonsList
		#$this->documentData["frontdoorUrl"] = array (with elements for View::Url)
		#$this->documentData["title"] = String
		#$this->documentData["abstract"] = String
		#$this->documentData["fileUrl"] = array (with elements for View::Url)
		#$this->documentData["documentType"] = DocumentTypeAdapter
		#Sample datastructure
		#"author" => new OpusPersonAdapter(
		#	array(
		#		"id" => "1", 
		#		"lastName" => "Marahrens", 
		#		"firstName" => "Oliver"
		#	)
		#), 
		#"frontdoorUrl" => array(
		#	"module"=>"frontdoor", 
		#	"controller" => "index", 
		#	"action"=>"index", 
		#	"id"=>"82"
		#), 
		#"title" => "Prüfung und Erweiterung der technischen Grundlagen des Dokumentenservers OPUS zur Zertifizierung gegenüber der DINI anhand der Installation an der TU Hamburg-Harburg", 
		#"abstract" => "Viele Hochschulen (bzw. die Hochschulbibliotheken) setzen heutzutage Dokumentenserver ein, um Dokumente online verfügbar zu machen und diese Online-Dokumente zu verwalten. In manchen Hochschulen ist es für die Studierenden sogar möglich, ihre Abschlussarbeit auf diesem Server zu veröffentlichen, was im Sinne der Promotionsordnung als ordnungsgemässe Veröffentlichung akzeptiert werden und so den Doktoranden eventuell hohe Kosten einer Verlagsveröffentlichung oder anderweitigen gedruckten Publikation ersparen kann. Ein solcher Dokumentenserver, der unter anderem in der Bibliothek der Technischen Universität Hamburg eingesetzt wird, ist OPUS. Um die Akzeptanz eines solchen Servers bei den Promovenden (aber auch den Studierenden, da OPUS nicht ausschliesslich Dissertationen und Habilitationen aufnimmt) zu erhöhen und sicherzustellen, dass der Server internationalen Standards folgt und so zum Beispiel auch von anderen Hochschulen oder Metasuchmaschinen etc. durchsucht werden kann, gibt es die Möglichkeit, einen Dokumentenserver zertifizieren zu lassen. Ein solches Zertifikat wird von der DINI (Deutsche Initiative für Netzwerkinformation) vergeben. In der vorliegenden Arbeit wird untersucht, inwiefern die Installation des Dokumentenservers OPUS an der TU Hamburg-Harburg die Zertifizierungsbedingungen der DINI erfüllt und wo ggf. Erweiterungsbedarf besteht.", 
		#"fileUrl" => array(
		#	"module"=>"frontdoor", 
		#	"controller" => "file", 
		#	"action"=>"view", 
		#	"id"=>"82",
		#	"filename"=>"projektbericht.pdf"
		#), 
		#"documentType" => new DocumentTypeAdapter(
		#	array(
		#		"id" => "1", 
		#		"name" => "Dissertation", 
		#		"type" => "Thesis"
		#	)
		#)
	}
}