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
    public function setUp() {
        $adapter = Zend_Db_Table::getDefaultAdapter();
    }

    /**
     * Tear down test fixture.
     *
     * @return void
     */
    public function tearDown() {
        TestHelper::clearTable('document_identifiers');
        TestHelper::clearTable('link_persons_documents');
        TestHelper::clearTable('link_institutes_documents');
        TestHelper::clearTable('link_documents_licences');
        TestHelper::clearTable('document_title_abstracts');
        TestHelper::clearTable('documents');
        TestHelper::clearTable('document_patents');
        TestHelper::clearTable('document_notes');
        TestHelper::clearTable('document_enrichments');
        TestHelper::clearTable('document_licences');
        TestHelper::clearTable('institutes_contents');
        TestHelper::clearTable('persons');
    }
	
    /**
     * Valid document data provider
     *
     * @return array
     */
    public function dummyData() {
        $docresult = Opus_Search_DummyData::getDummyDocuments();
        
        $hitlist = new Opus_Search_List_HitList();
        foreach ($docresult as $row) {
       		$searchhit = new Opus_Search_SearchHit($row);
       		$hitlist->add($searchhit);
        }
        
        return array($hitlist);		
    }

    /**
     * Real document data provider
     *
     * @return array Array containing all Opus_Search_Adapter_DocumentAdapters from the database
     * @throws Exception Opus_Model_Exception
     */
    public function allRealData() {
        return BrowsingFilter::getAllTitles();
    }

    /**
     * Definition of document type article
     *
     * @return String XML-Definition
     */
    private function article() {
        $xml = '<documenttype name="article"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Language" mandatory="yes" />
            <field name="Licence"/>
            <field name="ContributingCorporation"/>
            <field name="CreatingCorporation"/>
            <field name="DateAccepted"/>
            <field name="DocumentType"/>
            <field name="Edition"/>
            <field name="Issue"/>
            <field name="NonInstituteAffiliation"/>
            <field name="PageFirst"/>
            <field name="PageLast"/>
            <field name="PageNumber"/>
            <mandatory type="one-at-least">
                <field name="CompletedYear"/>
                <field name="CompletedDate"/>
            </mandatory>
            <field name="Reviewed"/>
            <field name="ServerDateModified"/>
            <field name="ServerDatePublished"/>
            <field name="ServerDateUnlocking"/>
            <field name="ServerDateValid"/>
            <field name="Source"/>
            <field name="SwbId"/>
            <field name="VgWortPixelUrl"/>
            <field name="Volume"/>
            <field name="TitleMain" multiplicity="*"/>
            <field name="TitleParent"/>
            <field name="TitleAbstract" multiplicity="*"/>
            <field name="Isbn"/>
            <field name="Note"/>
            <field name="Patent"/>
            <field name="Enrichment"/>
            <field name="PersonAuthor" multiplicity="3" />
        </documenttype>';
        return new Opus_Document_Type($xml);
    }

    /**
     * Real document data provider
     *
     * @return Opus_Search_Adapter_DocumentAdapter with one document from the database
     */
    public function oneRealDoc() {
        Opus_Document_Type::setXmlDoctypePath(dirname(__FILE__));
        $document = new Opus_Model_Document(null, 'article');
        
        $title = $document->addTitleMain();
        $title->setTitleAbstractValue('Title');
        $title->setTitleAbstractLanguage('de');

        $abstract = $document->addTitleAbstract();
        $abstract->setTitleAbstractValue('Abstract');
        $abstract->setTitleAbstractLanguage('fr');

        $parentTitle = $document->addTitleParent();
        $parentTitle->setTitleAbstractValue('Parent');
        $parentTitle->setTitleAbstractLanguage('en');

        $isbn = $document->addIsbn();
        $isbn->setIdentifierValue('123-123-123');
        $isbn->setIdentifierLabel('label');

        $note = $document->addNote();
        $note->setMessage('Ich bin eine öffentliche Notiz.');
        $note->setCreator('Jim Knopf');
        $note->setScope('public');

        $patent = $document->addPatent();
        $patent->setPatentCountries('Lummerland');
        $patent->setPatentDateGranted('2008-12-05');
        $patent->setPatentNumber('123456789');
        $patent->setPatentYearApplied('2008');
        $patent->setPatentApplication('Absolutely none.');

        $enrichment = $document->addEnrichment();
        $enrichment->setEnrichmentValue('Poor enrichment.');
        $enrichment->setEnrichmentType('nonesense');

        $author = new Opus_Model_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $author->setDateOfBirth('1889-04-26 00:00:00');
        $author->setPlaceOfBirth('Wien');
        $document->addPersonAuthor($author);

        $author = new Opus_Model_Person();
        $author->setFirstName('Ferdinand');
        $author->setLastName('de Saussure');
        $author->setDateOfBirth('1857-11-26 00:00:00');
        $author->setPlaceOfBirth('Genf');
        $document->addPersonAuthor($author);

        $licence = new Opus_Model_Licence;
        $licence->setActive(1);
        $licence->setLicenceLanguage('de');
        $licence->setLinkLicence('http://creativecommons.org/');
        $licence->setMimeType('text/pdf');
        $licence->setNameLong('Creative Commons');
        $licence->setPodAllowed(1);
        $licence->setSortOrder(0);
        $document->addLicence($licence);

        $title2 = $document->addTitleMain();
        $title2->setTitleAbstractValue('Title Two');
        $title2->setTitleAbstractLanguage('en');
        $abstract2 = $document->addTitleAbstract();
        $abstract2->setTitleAbstractValue('Kurzfassung');
        $abstract2->setTitleAbstractLanguage('de');
        $id = $document->store();
        
        try {
        	$doc = new Opus_Search_Adapter_DocumentAdapter((int) $id);
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
	public function testDocumentAdapterFromDb(Opus_Search_Adapter_DocumentAdapter $document) {
		$docData = $document->getDocument();
		$this->assertEquals(array_key_exists('author', $docData), true);
		$this->assertEquals(array_key_exists('frontdoorUrl', $docData), true);
		$this->assertEquals(array_key_exists('fileUrl', $docData), true);
		$this->assertEquals(array_key_exists('title', $docData), true);
		$this->assertEquals(array_key_exists('abstract', $docData), true);
	}

//    /**
//     * Test if the structure of Dummydata is valid for Opus_Search
//     * 
//     * @param array $dataList Array with DummyData-Documents
//     * @return void
//     *
//     * @dataProvider dummyData
//     */
//	public function testDocumentAdapterFromDummyData($dataList) {
//		$document = $dataList;
//		$docData = $document->getDocument();
//		$this->assertEquals(array_key_exists('author', $docData), true);
//		$this->assertEquals(array_key_exists('frontdoorUrl', $docData), true);
//		$this->assertEquals(array_key_exists('fileUrl', $docData), true);
//		$this->assertEquals(array_key_exists('title', $docData), true);
//		$this->assertEquals(array_key_exists('abstract', $docData), true);
//		$this->assertEquals(array_key_exists('documentType', $docData), true);
//	}
}