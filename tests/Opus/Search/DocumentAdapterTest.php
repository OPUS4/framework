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
 * @category    Test
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Search_DocumentAdapterTest extends PHPUnit_Framework_TestCase {
	/**
     * SetUp database 
     *
     * @return void
     */
    #public function setUp() {
    	// Insert data set number 37 into database if it does not exist
        #$adapter = Zend_Db_Table::getDefaultAdapter();
        //$adapter->query("DELETE FROM `link_persons_documents` WHERE `documents_id` = 37");
        //$adapter->query("DELETE FROM `documents` WHERE `documents_id` = 37");
        //$adapter->query("DELETE FROM `document_title_abstracts` WHERE `documents_id` = 37");
        //$adapter->query("DELETE FROM `persons` WHERE `persons_id` = 1");
        //$adapter->query("INSERT INTO `documents` (`documents_id`, `range_id`, `completed_date`, `completed_year`, `contributing_corporation`, `creating_corporation`, `date_accepted`, `document_type`, `edition`, `issue`, `language`, `non_institute_affiliation`, `page_first`, `page_last`, `page_number`, `publication_state`, `published_date`, `published_year`, `reviewed`, `server_date_modified`, `server_date_published`, `server_date_unlocking`, `server_date_valid`, `source`, `swb_id`, `vg_wort_pixel_url`, `volume`) VALUES
//(37, NULL, NULL, 0000, NULL, NULL, NULL, 'monograph', NULL, NULL, 'ger', NULL, NULL, NULL, NULL, 0, NULL, 2002, 'peer', NULL, '0000-00-00 00:00:00', NULL, NULL, NULL, NULL, NULL, NULL)");
        //$adapter->query("INSERT INTO `document_title_abstracts` (`document_title_abstracts_id`, `documents_id`, `title_abstract_type`, `title_abstract_value`, `title_abstract_language`) VALUES
//(1, 37, 'main', 'Informationskompetenz und studentisches Lernen im elektronischen Zeitalter', 'ger'),
//(2, 37, 'main', 'Information literacy and student learning in the electronic age', 'eng'),
//(3, 37, 'abstract', 'Die Integration der Vermittlung von allgemeiner und fachlicher\nInformationskompetenz in das Lernen und Lehren an unseren Universitäten ist\neine wichtige Voraussetzung für die zeitgemässe Qualifizierung von\nHochschulabsolventen. Der Beitrag gibt eine Übersicht zur Ausgangssituation, zu\nProblemen, Zielen, Inhalten und Methoden der Vermittlung von\nInformationskompetenz im Rahmen studentischen Lernens aus der Sicht der\nUniversitätsbibliothek einer Technischen Universität.', 'ger')");
		//$adapter->query("INSERT INTO `persons` (`persons_id`, `academic_title`, `date_of_birth`, `email`, `first_name`, `last_name`, `place_of_birth`) VALUES
//(1, '', '2008-07-15 00:00:00', 'mustermann@domain.com', 'Thomas', 'Hapke', 'Musterstadt')");
        //$adapter->query("INSERT INTO `link_persons_documents` (`persons_id`, `documents_id`, `institutes_id`, `role`, `sort_order`) VALUES
//(1, 37, 1, 'author', 0)");
    #}
	
    /**
     * Valid document data provider
     *
     * @return array
     */
    public function dummyData() {
        $docresult = DummyData::getDummyDocuments();
        
        $hitlist = new Opus_Search_List_HitList();
        foreach ($docresult as $row)
        {
       		$searchhit = new SearchHit($row);
       		$hitlist->add($searchhit);
        }
        
        return ($hitlist);		
    }

    /**
     * Real document data provider
     *
     * @return array Array containing all Opus_Search_Adapter_DocumentAdapters from the database
     */
    public function allRealData() {
        return BrowsingFilter::getAllTitles();
    }
    
    /**
     * Real document data provider
     *
     * @return Opus_Search_Adapter_DocumentAdapter with one document from the database
     */
    public function oneRealDoc() {
        Opus_Document_Type::setXmlDoctypePath(dirname(__FILE__));
        try {
        	$doc = new Opus_Search_Adapter_DocumentAdapter(37);
        } catch (Exception $e) {
        	throw $e;
        }
        return array(array($doc));
    }

    /**
     * Test if the structure of Documentdata from the DB is valid for Opus_Search
     * 
     * @param Opus_Search_Adapter_DocumentAdapter $document Document from the database
     * @return void 
     *
     * @dataProvider oneRealDoc
     */
	public function testDocumentAdapterFromDb($document) {
		$docData = $document->getDocument();
		$this->assertEquals(array_key_exists('author', $docData), true);
		$this->assertEquals(array_key_exists('frontdoorUrl', $docData), true);
		$this->assertEquals(array_key_exists('fileUrl', $docData), true);
		$this->assertEquals(array_key_exists('title', $docData), true);
		#$this->assertEquals(array_key_exists('abstract', $docData), true);
		#$this->assertEquals(array_key_exists('documentType', $docData), true);
	}

    /**
     * Test if the structure of Dummydata is valid for Opus_Search
     * 
     * @param array $dataList Array with DummyData-Documents
     * @return void
     *
     * @dataProvider dummyData
     */
	#public function testDocumentAdapterFromDummyData($dataList) {
	#	$document = $dataList;
	#	$docData = $document->getDocument();
	#	$this->assertEquals(array_key_exists('author', $docData), true);
	#	$this->assertEquals(array_key_exists('frontdoorUrl', $docData), true);
	#	$this->assertEquals(array_key_exists('fileUrl', $docData), true);
	#	$this->assertEquals(array_key_exists('title', $docData), true);
	#	$this->assertEquals(array_key_exists('abstract', $docData), true);
	#	$this->assertEquals(array_key_exists('documentType', $docData), true);
	#}
}