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
     * @var string
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
        TestHelper::clearTable('persons');
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
     *
     * @return array
     */
    public static function validDocumentDataProvider() {
        return self::$_validDocumentData;
    }



    /**
     * Test if tunneling setter calls through a n:m link model reaches
     * the target model instance.
     *
     * @return void
     */
    public function testTunnelingSetterCallsInManyToManyLinks() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Licence" multiplicity="3"/>
        </documenttype>';

        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $licence = new Opus_Model_Licence();

        $doc->addLicence($licence);
        $doc->getLicence(0)->setSortOrder(47);
        $value = $doc->getLicence(0)->getSortOrder();

        $this->assertEquals(47, $value, 'Wrong value returned from linked model.');
    }


    /**
     * Test if adding an many-to-many models works.
     *
     * @return void
     */
    public function testAddingModelInManyToManyLink() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Licence" multiplicity="3"/>
        </documenttype>';

        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);

        $value = $doc->getLicence();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(0, count($value), 'Expected zero objects to be returned initially.');

        $doc->addLicence(new Opus_Model_Licence());
        $value = $doc->getLicence();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(1, count($value), 'Expected only one object to be returned after adding.');
        $this->assertType('Opus_Model_Dependent_Link_DocumentLicence', $value[0], 'Returned object is of wrong type.');
    }


    /**
     * Test if adding an one-to-many model works.
     *
     * @return void
     */
    public function testAddingModelInOneToManyLink() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Note" multiplicity="*"/>
        </documenttype>';

        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);

        $value = $doc->getNote();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(0, count($value), 'Expected zero objects to be returned initially.');

        $doc->addNote();
        $value = $doc->getNote();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(1, count($value), 'Expected only one object to be returned after adding.');
        $this->assertType('Opus_Model_Dependent_Note', $value[0], 'Returned object is of wrong type.');
    }


    /**
     * Test if storing a document wich has a linked model doesnt throw
     * an Opus_Model_Exception.
     *
     * @return void
     *
     */
    public function testStoreWithLinkToIndependentModel() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="PersonAuthor" multiplicity="*"/>
        </documenttype>';

        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);

        $author = new Opus_Model_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $doc->addPersonAuthor($author);

        $doc->store();
    }

    /**
     * Test if adding more values to a multi-value field than it may hold throws
     * an InvalidArgumentException.
     *
     * @return void
     */
    public function testAddingMoreValuesThanMultiplicityAllowsThrowsException() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="PersonAuthor" multiplicity="2"/>
        </documenttype>';

        $type = new Opus_Document_Type($xml);
        $document = new Opus_Model_Document(null, $type);

        $author = new Opus_Model_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');

        $document->addPersonAuthor($author);
        $document->addPersonAuthor($author);
        $this->setExpectedException('InvalidArgumentException');
        $document->addPersonAuthor($author);

    }

    /**
     * Test if adding a value to a single-value field that is already populated
     * throws an InvaludArgumentException.
     *
     * @return void
     */
    public function testAddingValuesToPopulatedSingleValueFieldThrowsException() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Enrichment"/>
        </documenttype>';

        $type = new Opus_Document_Type($xml);
        $document = new Opus_Model_Document(null, $type);

        $enrichment = new Opus_Model_Dependent_Enrichment;
        $enrichment->setEnrichmentValue('Poor enrichment.');
        $enrichment->setEnrichmentType('nonesense');

        $document->addEnrichment($enrichment);
        $this->setExpectedException('InvalidArgumentException');
        $document->addEnrichment($enrichment);
    }

    /**
     * Test if an exception is thrown when using a model in a field that does
     * not extend Opus_Model_Abstract and for which no custom _fetch method
     * is defined.
     *
     * @return void
     */
    public function testUndefinedFetchMethodForFieldValueClassNotExtendingAbstractModelThrowsException() {
        $this->setExpectedException('Opus_Model_Exception');
        $document = new Opus_Model_ModelWithNonAbstractExtendingClassField;
    }

    /**
     * Test if a document's fields come out of the database as they went in.
     *
     * @param array $documentDataset Array with valid data of documents.
     * @return void
     *
     * @dataProvider validDocumentDataProvider
     */
    public function testDocumentFieldsPersistDatabaseStorage(array $documentDataset) {
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

        // Save document, modify, and save again.
        $id = $document->store();
        $document = new Opus_Model_Document($id);
        $title = $document->addTitleMain();
        $title->setTitleAbstractValue('Title Two');
        $title->setTitleAbstractLanguage('en');
        $id = $document->store();
        $document = new Opus_Model_Document($id);

        foreach ($documentDataset as $fieldname => $value) {
            $this->assertEquals($value, $document->{'get' . $fieldname}(), "Field $fieldname was changed by database.");
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
        $this->assertEquals($document->getPersonAuthor(0)->getFirstName(), 'Ludwig');
        $this->assertEquals($document->getPersonAuthor(0)->getLastName(), 'Wittgenstein');
        $this->assertEquals($document->getPersonAuthor(0)->getDateOfBirth(), '1889-04-26 00:00:00');
        $this->assertEquals($document->getPersonAuthor(0)->getPlaceOfBirth(), 'Wien');
        $this->assertEquals($document->getPersonAuthor(1)->getFirstName(), 'Ferdinand');
        $this->assertEquals($document->getPersonAuthor(1)->getLastName(), 'de Saussure');
        $this->assertEquals($document->getPersonAuthor(1)->getDateOfBirth(), '1857-11-26 00:00:00');
        $this->assertEquals($document->getPersonAuthor(1)->getPlaceOfBirth(), 'Genf');
        $this->assertEquals($document->getLicence()->getActive(), 1);
        $this->assertEquals($document->getLicence()->getLicenceLanguage(), 'de');
        $this->assertEquals($document->getLicence()->getLinkLicence(), 'http://creativecommons.org/');
        $this->assertEquals($document->getLicence()->getMimeType(), 'text/pdf');
        $this->assertEquals($document->getLicence()->getNameLong(), 'Creative Commons');
        $this->assertEquals($document->getLicence()->getPodAllowed(), 1);
        $this->assertEquals($document->getLicence()->getSortOrder(), 0);
    }

    /**
     * Test if corresponding links to persons are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesPersonLinks() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="PersonAuthor" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $author = new Opus_Model_Person();

        $doc->addPersonAuthor($author);
        $doc->store();
        $doc->delete();
        $id = $doc->getPersonAuthor()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $link = new Opus_Model_Dependent_Link_DocumentPerson($id);
    }

    /**
     * Test if corresponding links to licences are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesLicenceLinks() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Licence" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $licence = new Opus_Model_Licence();

        $doc->addLicence($licence);
        $doc->store();
        $doc->delete();
        $id = $doc->getLicence()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $link = new Opus_Model_Dependent_Link_DocumentLicence($id);
    }

    /**
     * Test if corresponding enrichments are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesEnrichments() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Enrichment" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $enrichment = new Opus_Model_Dependent_Enrichment();

        $doc->addEnrichment($enrichment);
        $doc->store();
        $doc->delete();
        $id = $doc->getEnrichment()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $enrichment = new Opus_Model_Dependent_Enrichment($id);
    }

    /**
     * Test if corresponding identifiers are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesIdentifiers() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Isbn" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $isbn = new Opus_Model_Dependent_Isbn();

        $doc->addIsbn($isbn);
        $doc->store();
        $doc->delete();
        $id = $doc->getIsbn()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $isbn = new Opus_Model_Dependent_Isbn($id);
    }

    /**
     * Test if corresponding patents are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesPatents() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Patent" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $patent = new Opus_Model_Dependent_Patent();

        $doc->addPatent($patent);
        $doc->store();
        $doc->delete();
        $id = $doc->getPatent()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $patent = new Opus_Model_Dependent_Patent($id);
    }

    /**
     * Test if corresponding notes are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesNotes() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Note" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $note = new Opus_Model_Dependent_Note();

        $doc->addNote($note);
        $doc->store();
        $doc->delete();
        $id = $doc->getNote()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $note = new Opus_Model_Dependent_Note($id);
    }

    /**
     * Test if corresponding subjects are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesSubjects() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="SubjectSwd" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $subject = new Opus_Model_Dependent_Subject();

        $doc->addSubjectSwd($subject);
        $doc->store();
        $doc->delete();
        $id = $doc->getSubjectSwd()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $subject = new Opus_Model_Dependent_Subject($id);
    }

    /**
     * Test if corresponding titles are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesTitles() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="TitleMain" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $title = new Opus_Model_Dependent_Title();

        $doc->addTitleMain($title);
        $doc->store();
        $doc->delete();
        $id = $doc->getTitleMain()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $title = new Opus_Model_Dependent_Title($id);
    }

    /**
     * Test if corresponding abstracts are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesAbstracts() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="TitleAbstract" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $doc = new Opus_Model_Document(null, $type);
        $abstract = new Opus_Model_Dependent_Abstract();

        $doc->addTitleAbstract($abstract);
        $doc->store();
        $doc->delete();
        $id = $doc->getTitleAbstract()->getId();
        $this->setExpectedException('Opus_Model_Exception');
        $abstract = new Opus_Model_Dependent_Abstract($id);
    }

    /**
     * Test if adding a model to a field that is defined as a link sets the
     * field value to the corresponding dependent link model.
     *
     * TODO: This test should be moved to AbstractTest.
     *
     * @return void
     */
    public function testAddLinkModel() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Licence" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $document = new Opus_Model_Document(null, $type);
        $licence = new Opus_Model_Licence;
        $document->addLicence($licence);

        $this->assertTrue($document->getField('Licence')->getValue() instanceof Opus_Model_Dependent_Link_Abstract,
                'Adding to a field containing a link model failed.');
    }

    /**
     * Test if setting a model's field that is defined as a link sets the
     * field value to the corresponding dependent link model.
     *
     * TODO: This test should be moved to AbstractTest.
     *
     * @return void
     */
    public function testSetLinkModel() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Licence" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $document = new Opus_Model_Document(null, $type);
        $licence = new Opus_Model_Licence;
        $document->setLicence($licence);

        $this->assertTrue($document->getField('Licence')->getValue() instanceof Opus_Model_Dependent_Link_Abstract,
                'Setting a field containing a link model failed.');
    }

    /**
     * Test if getting a model's field value  that is defined as a link sets the
     * field value to the corresponding dependent link model.
     *
     * TODO: This test should be moved to AbstractTest.
     *
     * @return void
     */
    public function testGetLinkModel() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Licence" />
        </documenttype>';
        $type = new Opus_Document_Type($xml);
        $document = new Opus_Model_Document(null, $type);
        $licence = new Opus_Model_Licence;
        $document->setLicence($licence);
        $licence = $document->getLicence();

        $this->assertTrue($licence instanceof Opus_Model_Dependent_Link_Abstract,
                'Getting a field value containing a link model failed.');
    }

}
