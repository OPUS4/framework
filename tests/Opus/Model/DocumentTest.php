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
 * @package     Opus_Model
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Model_Document.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group DocumentTest
 *
 */
class Opus_Model_DocumentTest extends PHPUnit_Framework_TestCase {


    /**
     * Test document type.
     *
     * @var String
     */
    protected $_xmlDoctype =
        '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">

            <field name="Language" mandatory="yes" />
            <field name="Licence"/>
            <field name="ContributingCorporation"/>
            <field name="CreatingCorporation"/>
            <field name="ContributingCorporation"/>

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

        </documenttype>';


    /**
     * Test fixture document type.
     *
     * @var Opus_Document_Type
     */
    protected $_type = null;


    /**
     * Set up test fixture.
     *
     * @return void
     */
    public function setUp() {
        $this->_type = new Opus_Document_Type($this->_xmlDoctype);
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
    }


    /**
     * Test if a Document instance can be serialized.
     *
     * @return void
     */
    public function testSerializing() {
        $doc = new Opus_Model_Document(null, $this->_type);
        $ser = serialize($doc);
    }

    /**
     * Test if a serialized Document instance can be deserialized.
     *
     * @return void
     */
    public function testDeserializing() {
        $doc1 = new Opus_Model_Document(null, $this->_type);
        $ser = serialize($doc1);
        $doc2 = unserialize($ser);
        $this->assertEquals($doc1, $doc2, 'Deserializing unsuccesful.');
    }

    /**
     * Valid document data.
     *
     * @var array  An array of arrays of arrays. Each 'inner' array must be an
     * associative array that represents valid document data.
     */
    protected static $_validDocumentData = array(
            array(
                array(
                    'Language' => 'de',
                    'ContributingCorporation' => 'Contributing, Inc.',
                    'CreatingCorporation' => 'Creating, Inc.',
                    'DateAccepted' => '1901-01-01',
                    'Edition' => 2,
                    'Issue' => 3,
                    'Volume' => 1,
                    'NonInstituteAffiliation' => 'Wie bitte?',
                    'PageFirst' => 1,
                    'PageLast' => 297,
                    'PageNumber' => 297,
                    'CompletedYear' => 1960,
                    'CompletedDate' => '1901-01-01',
                    'Reviewed' => 'peer',
                    'ServerDateModified' => '2008-12-01 00:00:00',
                    'ServerDatePublished' => '2008-12-01 00:00:00',
                    'ServerDateUnlocking' => '2008-12-01',
                    'ServerDateValid' => '2008-12-01',
                    'Source' => 'BlaBla',
                    'SwbId' => '1',
                    'VgWortPixelUrl' => 'http://geht.doch.eh.nicht',
                )
            )
        );

    /**
     * Valid document data provider
     * @return array
     */
    public static function validDocumentDataProvider() {
        return self::$_validDocumentData;
    }


    /**
     * Test if a document's fields come out of the database as they went in.
     *
     * @dataProvider validDocumentDataProvider
     */
    public function testDocumentFieldsPersistDatabaseStorage($documentDataset) {
        Opus_Document_Type::setXmlDoctypePath(dirname(__FILE__));
        $document = new Opus_Model_Document(null, 'article');
        foreach ($documentDataset as $fieldname => $value) {
            $callname = 'set' . $fieldname;
            $document->$callname($value);
        }
        $document->setDocumentType('article');

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
//        $document->addPersonAuthor($author);

        $licence = new Opus_Model_Licence;
        $licence->setActive(1);
        $licence->setLicenceLanguage('de');
        $licence->setLinkLicence('http://creativecommons.org/');
        $licence->setMimeType('text/pdf');
        $licence->setNameLong('Creative Commons');
        $licence->setPodAllowed(1);
        $licence->setSortOrder(0);
//        $document->addLicence($licence);

        // Save document, modify, and save again.
        $id = $document->store();
        $document = new Opus_Model_Document($id);
        $title = $document->addTitleMain();
        $title->setTitleAbstractValue('Title Two');
        $title->setTitleAbstractLanguage('en');
        $document = new Opus_Model_Document($document->store());

        foreach ($documentDataset as $fieldname => $value) {
            $this->assertEquals($value, $document->{'get'.$fieldname}(), "Field $fieldname was changed by database.");
        }
        $this->assertEquals($document->getTitleMain(0)->getTitleAbstractValue(), 'Title');
        $this->assertEquals($document->getTitleMain(0)->getTitleAbstractLanguage(), 'de');
        $this->assertEquals($document->getTitleMain(1)->getTitleAbstractValue(), 'Title Two');
        $this->assertEquals($document->getTitleMain(1)->getTitleAbstractLanguage(), 'en');
        $this->assertEquals($document->getTitleAbstract()->getTitleAbstractValue(), 'Abstract');
        $this->assertEquals($document->getTitleAbstract()->getTitleAbstractLanguage(), 'fr');
        $this->assertEquals($document->getTitleParent()->getTitleAbstractValue(), 'Parent');
        $this->assertEquals($document->getTitleParent()->getTitleAbstractLanguage(), 'en');
        $this->assertEquals($document->getIsbn()->getIdentifierValue(), '123-123-123');
        $this->assertEquals($document->getIsbn()->getIdentifierLabel(), 'label');
        $this->assertEquals($document->getNote()->getMessage(), 'Ich bin eine öffentliche Notiz.');
        $this->assertEquals($document->getNote()->getCreator(), 'Jim Knopf');
        $this->assertEquals($document->getNote()->getScope(), 'public');
        $this->assertEquals($document->getPatent()->getPatentCountries(), 'Lummerland');
        $this->assertEquals($document->getPatent()->getPatentDateGranted(), '2008-12-05');
        $this->assertEquals($document->getPatent()->getPatentNumber(), '123456789');
        $this->assertEquals($document->getPatent()->getPatentYearApplied(), '2008');
        $this->assertEquals($document->getPatent()->getPatentApplication(), 'Absolutely none.');
        $this->assertEquals($document->getEnrichment()->getEnrichmentValue(), 'Poor enrichment.');
        $this->assertEquals($document->getEnrichment()->getEnrichmentType(), 'nonesense');
//        $this->assertEquals($document->getPersonAuthor(0)->getFirstName(), 'Ludwig');
//        $this->assertEquals($document->getPersonAuthor(0)->getLastName(), 'Wittgenstein');
//        $this->assertEquals($document->getPersonAuthor(0)->getDateOfBirth(), '1889-04-26 00:00:00');
//        $this->assertEquals($document->getPersonAuthor(0)->getPlaceOfBirth(), 'Wien');
//        $this->assertEquals($document->getLicence()->getActive(), 1);
//        $this->assertEquals($document->getLicence()->getLicenceLanguage(), 'de');
//        $this->assertEquals($document->getLicence()->getLinkLicence(), 'http://creativecommons.org/');
//        $this->assertEquals($document->getLicence()->getMimeType(), 'text/pdf');
//        $this->assertEquals($document->getLicence()->getNameLong(), 'Creative Commons');
//        $this->assertEquals($document->getLicence()->getPodAllowed(), 1);
//        $this->assertEquals($document->getLicence()->getSortOrder(), 0);
    }

}
