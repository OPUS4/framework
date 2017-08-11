<?php

/*
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
 * @package     Opus
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Michael Lang <lang@zib.de>
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008-2014, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Document.
 *
 * @package Opus
 * @category Tests
 *
 * @group DocumentTest
 *
 */
class Opus_DocumentTest extends TestCase {

    private $testFiles;

    /**
     * Set up test fixture.
     *
     * @return void
     */
    public function setUp() {
        // Set up a mock language list.
        $list = array('de' => 'Test_Deutsch', 'en' => 'Test_Englisch', 'fr' => 'Test_Französisch');
        Zend_Registry::set('Available_Languages', $list);

        parent::setUp();
    }

    /**
     * Test if a Document instance can be serialized.
     *
     * @return void
     */
    public function testSerializing() {
        $doc = new Opus_Document();
        $ser = serialize($doc);

        $this->assertNotNull($ser, 'Serializing returned NULL.');
        $match_result = preg_match('/"Opus_Document"/', $ser);
        $this->assertTrue(is_int($match_result) && $match_result > 0, 'Serialized string does not contain Opus_Document as string.');
    }

    /**
     * Test if a serialized Document instance can be deserialized.
     *
     * @return void
     */
    public function testDeserializing() {
        $doc1 = new Opus_Document();
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
                'ThesisDateAccepted' => '1901-01-01',
                'Edition' => 2,
                'Issue' => 3,
                'Volume' => 1,
                'PageFirst' => 1,
                'PageLast' => 297,
                'PageNumber' => 297,
                'CompletedYear' => 1960,
                'CompletedDate' => '1901-01-01',
                'BelongsToBibliography' => 1,
                'EmbargoDate' => '1902-01-01',
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
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $licence = new Opus_Licence();
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
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $value = $doc->getLicence();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(0, count($value), 'Expected zero objects to be returned initially.');

        $doc->addLicence(new Opus_Licence());
        $value = $doc->getLicence();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(1, count($value), 'Expected only one object to be returned after adding.');
        $this->assertInstanceOf('Opus_Model_Dependent_Link_DocumentLicence', $value[0], 'Returned object is of wrong type.');
    }

    /**
     * Test if adding an one-to-many model works.
     *
     * @return void
     */
    public function testAddingModelInOneToManyLink() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $value = $doc->getNote();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(0, count($value), 'Expected zero objects to be returned initially.');

        $doc->addNote();
        $value = $doc->getNote();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(1, count($value), 'Expected only one object to be returned after adding.');
        $this->assertInstanceOf('Opus_Note', $value[0], 'Returned object is of wrong type.');
    }

    /**
     * Test if storing a document wich has a linked model doesnt throw
     * an Opus_Model_Exception.
     *
     * @return void
     *
     */
    public function testStoreWithLinkToIndependentModel() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $author = new Opus_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $doc->addPersonAuthor($author);

        $doc->store();
    }

    /**
     * Test if adding a value to a single-value field that is already populated
     * throws an InvalidArgumentException.
     *
     * @return void
     */
    public function testAddingValuesToPopulatedSingleValueFieldThrowsException() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $doc->addPageFirst(10);
        $this->setExpectedException('InvalidArgumentException');
        $doc->addPageFirst(100);
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
     * FIXME: Handling of Files and Enrichments are not tested!
     *
     * Test if a document's fields come out of the database as they went in.
     *
     * @param array $documentDataset Array with valid data of documents.
     * @return void
     *
     * @dataProvider validDocumentDataProvider
     */
    public function testDocumentFieldsPersistDatabaseStorage(array $documentDataset) {
        $document = new Opus_Document();
        $document->setType("article");

        foreach ($documentDataset as $fieldname => $value) {
            $callname = 'set' . $fieldname;
            $document->$callname($value);
        }

        $title = $document->addTitleMain();
        $title->setValue('Title');
        $title->setLanguage('de');

        $abstract = $document->addTitleAbstract();
        $abstract->setValue('Abstract');
        $abstract->setLanguage('fr');

        $parentTitle = $document->addTitleParent();
        $parentTitle->setValue('Parent');
        $parentTitle->setLanguage('en');

        $isbn = $document->addIdentifierIsbn();
        $isbn->setValue('123-123-123');

        $note = $document->addNote();
        $note->setMessage('Ich bin eine öffentliche Notiz.');
        $note->setVisibility('public');

        $patent = $document->addPatent();
        $patent->setCountries('Lummerland');
        $patent->setDateGranted('2008-12-05');
        $patent->setNumber('123456789');
        $patent->setYearApplied('2008');
        $patent->setApplication('Absolutely none.');

        $enrichmentkey = new Opus_EnrichmentKey();
        $enrichmentkey->setName('foo');
        $enrichmentkey->store();

        $enrichment = $document->addEnrichment();
        $enrichment->setKeyName('foo');
        $enrichment->setValue('Poor enrichment.');

        $author = new Opus_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $author->setDateOfBirth('1889-04-26');
        $author->setPlaceOfBirth('Wien');
        $document->addPersonAuthor($author);

        $author = new Opus_Person();
        $author->setFirstName('Ferdinand');
        $author->setLastName('de Saussure');
        $author->setDateOfBirth('1857-11-26');
        $author->setPlaceOfBirth('Genf');
        $document->addPersonAuthor($author);

        $licence = new Opus_Licence;
        $licence->setActive(1);
        $licence->setLanguage('de');
        $licence->setLinkLicence('http://creativecommons.org/');
        $licence->setMimeType('text/pdf');
        $licence->setNameLong('Creative Commons');
        $licence->setPodAllowed(1);
        $licence->setSortOrder(0);
        $document->addLicence($licence);

        $dnbInstitute = new Opus_DnbInstitute();
        $dnbInstitute->setName('Forschungsinstitut für Code Coverage');
        $dnbInstitute->setCity('Calisota');
        $dnbInstitute->setIsGrantor(1);
        $document->addThesisPublisher($dnbInstitute);
        $document->addThesisGrantor($dnbInstitute);

        // Save document, modify, and save again.
        $id = $document->store();
        $document = new Opus_Document($id);
        $title = $document->addTitleMain();
        $title->setValue('Title Two');
        $title->setLanguage('en');
        $id = $document->store();
        $document = new Opus_Document($id);

        foreach ($documentDataset as $fieldname => $value) {
            $field = $document->{'get' . $fieldname}();

            // Special handling for Opus_Date fields...
            if ($field instanceof Opus_Date) {
                $field = substr($field->__toString(), 0, 10);
            }

            $this->assertEquals($value, $field, "Field $fieldname was changed by database.");
        }

        $this->assertEquals($document->getTitleMain(0)->getValue(), 'Title');
        $this->assertEquals($document->getTitleMain(0)->getLanguage(), 'de');
        $this->assertEquals($document->getTitleMain(1)->getValue(), 'Title Two');
        $this->assertEquals($document->getTitleMain(1)->getLanguage(), 'en');
        $this->assertEquals($document->getTitleAbstract(0)->getValue(), 'Abstract');
        $this->assertEquals($document->getTitleAbstract(0)->getLanguage(), 'fr');
        $this->assertEquals($document->getTitleParent(0)->getValue(), 'Parent');
        $this->assertEquals($document->getTitleParent(0)->getLanguage(), 'en');
        $this->assertEquals($document->getIdentifierIsbn(0)->getValue(), '123-123-123');
        $this->assertEquals($document->getNote(0)->getMessage(), 'Ich bin eine öffentliche Notiz.');
        $this->assertEquals($document->getNote(0)->getVisibility(), 'public');
        $this->assertEquals($document->getPatent(0)->getCountries(), 'Lummerland');
        $this->assertStringStartsWith('2008-12-05', $document->getPatent(0)->getDateGranted()->__toString());
        $this->assertEquals($document->getPatent(0)->getNumber(), '123456789');
        $this->assertEquals($document->getPatent(0)->getYearApplied(), '2008');
        $this->assertEquals($document->getPatent(0)->getApplication(), 'Absolutely none.');
        $this->assertEquals($document->getEnrichment(0)->getValue(), 'Poor enrichment.');
        $this->assertEquals($document->getEnrichment(0)->getKeyName(), 'foo');
        $this->assertEquals($document->getPersonAuthor(0)->getFirstName(), 'Ludwig');
        $this->assertEquals($document->getPersonAuthor(0)->getLastName(), 'Wittgenstein');
        $this->assertStringStartsWith('1889-04-26', $document->getPersonAuthor(0)->getDateOfBirth()->__toString());
        $this->assertEquals($document->getPersonAuthor(0)->getPlaceOfBirth(), 'Wien');
        $this->assertEquals($document->getPersonAuthor(1)->getFirstName(), 'Ferdinand');
        $this->assertEquals($document->getPersonAuthor(1)->getLastName(), 'de Saussure');
        $this->assertStringStartsWith('1857-11-26', $document->getPersonAuthor(1)->getDateOfBirth()->__toString());
        $this->assertEquals($document->getPersonAuthor(1)->getPlaceOfBirth(), 'Genf');
        $this->assertEquals($document->getLicence(0)->getActive(), 1);
        $this->assertEquals($document->getLicence(0)->getLanguage(), 'de');
        $this->assertEquals($document->getLicence(0)->getLinkLicence(), 'http://creativecommons.org/');
        $this->assertEquals($document->getLicence(0)->getMimeType(), 'text/pdf');
        $this->assertEquals($document->getLicence(0)->getNameLong(), 'Creative Commons');
        $this->assertEquals($document->getLicence(0)->getPodAllowed(), 1);
        $this->assertEquals($document->getLicence(0)->getSortOrder(), 0);
        $thesisPublishers = $document->getThesisPublisher();
        $this->assertEquals($document->getThesisPublisher(0)->getName(), 'Forschungsinstitut für Code Coverage');
        $this->assertEquals($document->getThesisPublisher(0)->getCity(), 'Calisota');
        $this->assertEquals($document->getThesisGrantor(0)->getName(), 'Forschungsinstitut für Code Coverage');
        $this->assertEquals($document->getThesisGrantor(0)->getCity(), 'Calisota');
    }

    /**
     * Test if corresponding deleting documents works.
     *
     * @return void
     */
    public function testDelete() {
        $doc = new Opus_Document();
        $docid = $doc->store();
        $doc->delete();

        $doc = new Opus_Document($docid);
        $this->assertEquals('deleted', $doc->getServerState(), "Server state should be set to 'deleted' now.");
    }

    /**
     * Test if corresponding permanently deleting documents works.
     *
     * @return void
     */
    public function testDeletePermanent() {
        $doc = new Opus_Document();
        $docid = $doc->store();
        $doc->deletePermanent();

        $this->setExpectedException('Opus_Model_NotFoundException');
        $doc = new Opus_Document($docid);
    }

    /**
     * Test if document with author can be deleted permanently.
     *
     * @return void
     */
    public function testDeleteDocumentWithAuthorPermanently() {
        $doc = new Opus_Document();
        $doc->setType('doctoral_thesis');

        $author = new Opus_Person();
        $author->setFirstName('M.');
        $author->setLastName('Gandi');

        $doc->addPersonAuthor($author);
        $modelId = $doc->store();

        $linkId = $doc->getPersonAuthor(0)->getId();

        $doc->deletePermanent();

        $this->setExpectedException('Opus_Model_NotFoundException');
        $doc = new Opus_Document($modelId);
    }

    /**
     * Test if document with missing file can be deleted permanently.
     */
    public function testDeleteDocumentWithMissingFile() {
        $doc = new Opus_Document();
        $doc->setType('doctoral_thesis');

        $modelId = $doc->store();

        $config = Zend_Registry::get('Zend_Config');
        $tempFile = $config->workspacePath . '/' . uniqid();
        touch($tempFile);

        $file = $doc->addFile();
        $file->setPathName('test.txt');
        $file->setMimeType('text/plain');
        $file->setTempFile($tempFile);

        $doc->store();

        $doc = new Opus_Document($modelId);

        $file = $doc->getFile(0);

        $this->assertTrue(!empty($file)); // document has a file

        $filePath = $file->getPath();

        $this->assertTrue(is_file($filePath)); // file exists

        unlink($filePath);

        $this->assertFalse(is_file($filePath)); // file is gone

        $doc->deletePermanent(); // delete document with missing file

        $this->setExpectedException('Opus_Model_NotFoundException');
        $doc = new Opus_Document($modelId);
    }

    /**
     * Test if corresponding links to persons are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesPersonLinks() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $author = new Opus_Person();
        $author->setFirstName('M.');
        $author->setLastName('Gandi');

        $doc->addPersonAuthor($author);
        $modelId = $doc->store();

        $linkId = $doc->getPersonAuthor(0)->getId();

        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $link = new Opus_Model_Dependent_Link_DocumentPerson($linkId);
    }

    /**
     * Test if corresponding links to dnb_institutes are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesDnbInstituteLink() {
        $doc = new Opus_Document();
        $dnbInstitute = new Opus_DnbInstitute();
        $dnbInstitute->setName('Forschungsinstitut für Code Coverage');
        $dnbInstitute->setCity('Calisota');

        $doc->addThesisPublisher($dnbInstitute);
        $doc->store();
        $linkid = $doc->getThesisPublisher(0)->getId();
        $doc->deletePermanent();

        $this->setExpectedException('Opus_Model_NotFoundException');
        $link = new Opus_Model_Dependent_Link_DocumentDnbInstitute($linkid);

        $this->fail("Document delete has not been cascaded.");
    }

    /**
     * Test if corresponding links to licences are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesLicenceLink() {
        $doc = new Opus_Document();
        $licence = new Opus_Licence();
        $licence->setNameLong('LongName');
        $licence->setLinkLicence('http://long.org/licence');

        $doc->addLicence($licence);
        $doc->store();
        $linkid = $doc->getLicence(0)->getId();
        $doc->deletePermanent();

        $this->setExpectedException('Opus_Model_NotFoundException');
        $link = new Opus_Model_Dependent_Link_DocumentLicence($linkid);

        $this->fail("Document delete has not been cascaded.");
    }

    /**
     * Test if corresponding enrichments are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesEnrichments() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $enrichmentkey = new Opus_EnrichmentKey();
        $enrichmentkey->setName('foo');
        $enrichmentkey->store();

        $enrichment = new Opus_Enrichment();
        $enrichment->setKeyName('foo');
        $enrichment->setValue('Poor enrichment.');

        $doc->addEnrichment($enrichment);
        $doc->store();
        $id = $doc->getEnrichment(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $enrichment = new Opus_Enrichment($id);
    }

    /**
     * Test if corresponding identifiers are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesIdentifiers() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $isbn = new Opus_Identifier();
        $isbn->setValue('ISBN');

        $doc->addIdentifierIsbn($isbn);
        $doc->store();
        $id = $doc->getIdentifierIsbn(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $isbn = new Opus_Identifier($id);
    }

    /**
     * Test if corresponding patents are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesPatents() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $patent = new Opus_Patent();
        $patent->setCountries('Germany');
        $patent->setNumber('X0815');
        $patent->setDateGranted('2001-01-01');
        $patent->setApplication('description');

        $doc->addPatent($patent);
        $doc->store();
        $id = $doc->getPatent(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $patent = new Opus_Patent($id);
    }

    /**
     * Test if corresponding notes are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesNotes() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $note = new Opus_Note();
        $note->setMessage('A note!');

        $doc->addNote($note);
        $doc->store();
        $id = $doc->getNote(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $note = new Opus_Note($id);
    }

    /**
     * Test if corresponding subjects are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesSubjects() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $subject = new Opus_SubjectSwd();
        $subject->setValue('Schlagwort');

        $doc->addSubject($subject);
        $doc->store();
        $id = $doc->getSubject(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $subject = new Opus_Subject($id);
    }

    /**
     * Test if corresponding titles are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesTitles() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $title = new Opus_Title();
        $title->setValue('Title of a document');
        $title->setLanguage('eng');

        $doc->addTitleMain($title);
        $doc->store();
        $id = $doc->getTitleMain(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $title = new Opus_Title($id);
    }

    /**
     * Test if corresponding abstracts are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesAbstracts() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $abstract = new Opus_Title();
        $abstract->setValue('It is necessary to give an abstract.');
        $abstract->setLanguage('eng');

        $doc->addTitleAbstract($abstract);
        $doc->store();
        $id = $doc->getTitleAbstract(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $abstract = new Opus_Title($id);
    }

    /**
     * Test if a set of documents can be retrieved by getAll().
     *
     * @return void
     */
    public function testRetrieveAllDocuments() {
        $max_docs = 5;
        for ($i = 0; $i < $max_docs; $i++) {
            $doc = new Opus_Document();
            $doc->setType("doctoral_thesis");
            $doc->store();
        }

        $result = Opus_Document::getAll();
        $this->assertEquals($max_docs, count($result), 'Wrong number of objects retrieved.');
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
        $document = new Opus_Document();
        $document->setType("doctoral_thesis");

        $licence = new Opus_Licence;
        $document->addLicence($licence);

        $licence = $document->getField('Licence')->getValue();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract, 'Adding to a field containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract, 'Adding to a field containing a link model failed (getLicence).');
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
        $document = new Opus_Document();
        $document->setType("doctoral_thesis");

        $licence = new Opus_Licence;
        $document->setLicence($licence);

        $licence = $document->getField('Licence')->getValue();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract, 'Setting a field containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract, 'Setting a field containing a link model failed (getLicence).');
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
        $document = new Opus_Document();
        $document->setType("doctoral_thesis");

        $licence = new Opus_Licence;
        $document->setLicence($licence);

        $licence = $document->getField('Licence')->getValue();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract, 'Getting a field value containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract, 'Getting a field value containing a link model failed (getLicence).');
    }

    /**
     * Test if title informations delivered back properly with toArray().
     *
     * @return void
     */
    public function testToArrayReturnsCorrectValuesForTitleMain() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $title = $doc->addTitleMain();
        $title->setLanguage('de');
        $title->setValue('Ein deutscher Titel');
        $id = $doc->store();

        $loaded_document = new Opus_Document($id);
        $iterim_result = $loaded_document->toArray();
        $result = $iterim_result['TitleMain'][0];
        $expected = array(
            'Language' => 'de',
            'Value' => 'Ein deutscher Titel',
            'Type' => 'main'
//            'SortOrder' => null
        );
        $this->assertEquals($expected, $result, 'toArray() deliver not expected title data.');
    }

    /**
     * Test if multiple languages are (re)stored properly.
     *
     * @return void
     *
     * TODO analyse usage of addLanguage function
     *
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage Cannot add multiple values to Language
     */
    public function testMultipleLanguageStorage() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $doc->addLanguage('de');
        $doc->addLanguage('en');
    }

    /**
     * Test storing of a urn.
     *
     * @return void
     */
    public function testStoringOfOneIdentifierUrn() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");
        $id = $doc->store();
        $doc2 = new Opus_Document($id);

        // TODO: Cannot check Urn if we did not add it...
        $this->markTestSkipped('TODO: analyze');

        $this->assertNotNull($doc2->getIdentifierUrn(0));
        $urn_value = $doc2->getIdentifierUrn(0)->getValue();

        $urn = new Opus_Identifier_Urn('swb', '14', 'opus');
        $this->assertEquals($urn->getUrn($id), $urn_value, 'Stored and expected URN value did not match.');
    }

    /**
     * Test saving of empty multiple urn fields.
     *
     * @return void
     */
    public function testStoringOfMultipleIdentifierUrnField() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        // TODO: Cannot check Urn if we did not add it...
        $this->markTestSkipped('TODO: analyze');

        $id = $doc->store();
        $doc2 = new Opus_Document($id);
        $urn_value = $doc2->getIdentifierUrn(0)->getValue();

        $urn = new Opus_Identifier_Urn('swb', '14', 'opus');
        $this->assertEquals($urn->getUrn($id), $urn_value, 'Stored and expected URN value did not match.');
        $this->assertEquals(1, count($doc2->getIdentifierUrn()), 'On an empty multiple field only 2 URN value should be stored.');
    }

    /**
     * Ensure that existing urn values not overriden.
     *
     * @return void
     */
    public function testNotOverrideExistingUrn() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $urn_value = 'urn:nbn:de:swb:14-opus-5548';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value);

        $id = $doc->store();
        $doc2 = new Opus_Document($id);

        $this->assertEquals($urn_value, $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test storing document with empty identifier urn model create a urn.
     *
     * @return void
     */
    public function testStoreUrnWithEmptyModel() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $urn_model = new Opus_Identifier();
        $doc->setIdentifierUrn($urn_model);
        $id = $doc->store();

        $doc2 = new Opus_Document($id);
        $this->assertNotNull($doc2->getIdentifierUrn(0)->getValue(), 'URN value should not be empty.');

        $urn = new Opus_Identifier_Urn('nbn', 'de:kobv:test-opus');
        $this->assertEquals($urn->getUrn($id), $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test if multiple existing URN values does not overriden.
     *
     * @return void
     */
    public function testNotOverrideExistingMultipleUrn() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $urn_value_1 = 'urn:nbn:de:swb:14-opus-5548';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_1);

        $urn_value_2 = 'urn:nbn:de:swb:14-opus-5598';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_2);
        $id = $doc->store();
        $doc2 = new Opus_Document($id);

        $this->assertEquals($urn_value_1, $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
        $this->assertEquals($urn_value_2, $doc2->getIdentifierUrn(1)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test if at least one value inside a multiple urn values does not create a new urn.
     *
     * @return void
     */
    public function testNotOverridePartialExistingMultipleUrn() {
        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $urn_value_1 = 'urn:nbn:de:swb:14-opus-5548';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_1);

        $urn_value_2 = 'urn:nbn:de:swb:14-opus-2345';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_2);
        $id = $doc->store();
        $doc2 = new Opus_Document($id);

        $this->assertEquals($urn_value_1, $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
        $this->assertEquals($urn_value_2, $doc2->getIdentifierUrn(1)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test if after creation of a document leaves the fields marked unmodified.
     *
     * @return void
     */
    public function testNewlyCreatedDocumentsHaveNoModifiedFields() {
        $newdoc = new Opus_Document();

        $fieldnames = $newdoc->describe();
        foreach ($fieldnames as $fieldname) {
            $field = $newdoc->getField($fieldname);
            $this->assertFalse($field->isModified(), 'Field ' . $fieldname . ' marked as modified after creation.');
        }
    }

    /**
     * Test retrieving a document list based on server (publication) states.
     *
     * @return void
     */
    public function testGetByServerStateReturnsCorrectDocuments() {
        $publishedDoc1 = new Opus_Document();
        $publishedDoc1->setType("doctoral_thesis")
                ->setServerState('published')
                ->store();

        $publishedDoc2 = new Opus_Document();
        $publishedDoc2->setType("doctoral_thesis")
                ->setServerState('published')
                ->store();

        $unpublishedDoc1 = new Opus_Document();
        $unpublishedDoc1->setType("doctoral_thesis")
                ->setServerState('unpublished')
                ->store();

        $unpublishedDoc2 = new Opus_Document();
        $unpublishedDoc2->setType("doctoral_thesis")
                ->setServerState('unpublished')
                ->store();

        $deletedDoc1 = new Opus_Document();
        $deletedDoc1->setType("doctoral_thesis")
                ->setServerState('deleted')
                ->store();

        $deletedDoc2 = new Opus_Document();
        $deletedDoc2->setType("doctoral_thesis")
                ->setServerState('deleted')
                ->store();

        $publishedDocs = Opus_Document::getAllByState('published');
        $unpublishedDocs = Opus_Document::getAllByState('unpublished');
        $deletedDocs = Opus_Document::getAllByState('deleted');

        $this->assertEquals(2, count($publishedDocs));
        $this->assertEquals(2, count($unpublishedDocs));
        $this->assertEquals(2, count($deletedDocs));
    }

    /**
     * Test setting and getting date values on different ways and fields.
     *
     * @return void
     */
    public function testSettingAndGettingDateValues() {
        $locale = new Zend_Locale('de_DE');
        $doc = new Opus_Document();

        $doc->setPublishedDate('2008-10-05');

        $personAuthor = new Opus_Person();
        $personAuthor->setFirstName('Real');
        $personAuthor->setLastName('Tester');
        $personAuthor->setDateOfBirth('1965-06-23');
        $doc->addPersonAuthor($personAuthor);

        $patent = new Opus_Patent();
        $patent->setNumber('08 15');
        $patent->setDateGranted('2008-07-07');
        $patent->setCountries('Germany');
        $patent->setApplication('description');
        $doc->addPatent($patent);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $publishedDate = $doc->getPublishedDate();
        $personAuthor = $doc->getPersonAuthor(0);
        $patent = $doc->getPatent(0);

        $formatDate = 'd.m.Y';
        $this->assertEquals('05.10.2008', $publishedDate->getDateTime()->format($formatDate), 'Setting a date through string does not work.');
        $this->assertEquals('23.06.1965', $personAuthor->getDateOfBirth()->getDateTime()->format($formatDate), 'Setting a date on a model doesn not work.');
        $this->assertEquals('07.07.2008', $patent->getDateGranted()->getDateTime()->format($formatDate), 'Setting a date on a dependent model doesn not work.');
    }

    /**
     * Test if ServerState becomes value unpublished if not set and document is stored.
     *
     * @return void
     */
    public function testCheckIfDefaultServerStateValueIsSetCorrectAfterStoringModel() {
        $doc = new Opus_Document();
        $doc->store();

        $this->assertEquals('unpublished', $doc->getServerState(), 'ServerState should be unpublished if not set and document is stored.');
    }

    /**
     * Test for Issue in Opus_Model_Xml_Version1.  The field ServerDatePublished
     * disappeared from the XML-DOM-Tree after storing.
     */
    public function testExistenceOfServerDatePublished() {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->setServerDatePublished('2011-11-11T11:11+01:00');
        $doc->store();

        $filter = new Opus_Model_Filter;
        $filter->setModel($doc);

        $docXml = $doc->toXml(array(), new Opus_Model_Xml_Version1());
        $serverDatePublElements = $docXml->getElementsByTagName("ServerDatePublished");
        $this->assertEquals(1, count($serverDatePublElements), 'document xml should contain one field "ServerDatePublished"');
        $this->assertTrue($serverDatePublElements->item(0)->hasAttributes(), 'document xml field "ServerDatePublished" should have attributes');

        $modelXml = $filter->toXml(array(), new Opus_Model_Xml_Version1());
        $serverDatePublElements = $modelXml->getElementsByTagName("ServerDatePublished");
        $this->assertEquals(1, count($serverDatePublElements), 'model xml should contain one field "ServerDatePublished"');
        $this->assertTrue($serverDatePublElements->item(0)->hasAttributes(), 'model xml field "ServerDatePublished" should have attributes');
    }

    /**
     * Tests initialization of ServerDate-Fields.
     *
     * @return void
     */
    public function testInitializationOfServerDateFields() {
        $d = new Opus_Document();
        $id = $d->store();

        $d = new Opus_Document($id);
        $this->assertNotNull($d->getServerDateCreated(), 'ServerDateCreated should *not* be NULL');
        $this->assertNotNull($d->getServerDateModified(), 'ServerDateModified should *not* be NULL');
        $this->assertNull($d->getServerDatePublished(), 'ServerDatePublished *should* be NULL');
    }

    /**
     * Tests initialization of ServerDatePublished field.
     *
     * @return void
     */
    public function testSetServerDatePublished() {
        $d = new Opus_Document();
        $d->setServerState('published');
        $id = $d->store();

        $this->assertNotNull($d->getServerDatePublished());
    }

    /**
     * Tests initialization of ServerDatePublished field.
     *
     * @return void
     */
    public function testDontChangeUserSpecifiedServerDatePublished() {
        $examplePublishedDate = new Opus_Date('2010-05-09T18:20:17+02:00');

        $d = new Opus_Document();
        $d->setServerDatePublished($examplePublishedDate);
        $d->setServerState('published');
        $id = $d->store();

        $this->assertEquals(
                $examplePublishedDate->__toString(), $d->getServerDatePublished()->__toString(), "Don't change user-specified server_date_published");

        $testStates = array('unpublished', 'published', 'published', 'unpublished');
        foreach ($testStates AS $state) {
            $d = new Opus_Document($id);
            $d->setServerState($state);
            $d->store();

            $d = new Opus_Document($id);
            $this->assertNotNull($d->getServerDatePublished());
            $this->assertEquals($examplePublishedDate->__toString(), $d->getServerDatePublished()->__toString(),
                "Don't change user-specified server_date_published (state $state)");
        }
    }

    /**
     * Tests initialization of ServerDatePublished field.
     *
     * @return void
     */
    public function testSetServerDatePublishedOnlyAfterPublish() {
        $d = new Opus_Document();
        $d->setServerState('unpublished');
        $id = $d->store();

        $this->assertNull($d->getServerDatePublished(), 'published date should be NULL after store()');

        $d = new Opus_Document($id);
        $this->assertNull($d->getServerDatePublished(), 'published date should be NULL after store() and reload');

        $d->setServerState('published');
        $id = $d->store();

        $this->assertNotNull($d->getServerDatePublished(), 'published date should NOT be NULL after publish');
    }

    /**
     * Tests overriding the initialization of ServerDate-Fields.
     *
     * @return void
     */
    public function testInitializationOfServerDateFieldsOverride() {
        $exampleCreateDate = '2010-05-11T18:20:17+02:00';
        $examplePublishedDate = '2010-05-09T18:20:17+02:00';

        $d = new Opus_Document();
        $d->setServerDateCreated($exampleCreateDate);
        $d->setServerDatePublished($examplePublishedDate);
        $id = $d->store();

        $d = new Opus_Document($id);
        $this->assertEquals($exampleCreateDate, $d->getServerDateCreated()->__toString());
        $this->assertNotNull($d->getServerDatePublished());
        $this->assertEquals($examplePublishedDate, $d->getServerDatePublished()->__toString());
    }

    /**
     * Test for storing collections
     */
    public function testStoreDocumentWithCollectionsTest() {
        $role = new Opus_CollectionRole();
        $role->setName('foobar-' . rand());
        $role->setOaiName('foobar-oai-' . rand());
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $collection1 = $root->addFirstChild();
        $root->store();

        $collection2 = $root->addLastChild();
        $root->store();

        $document = new Opus_Document();
        $document->setType('test');
        $document->addCollection($collection1);
        $document->addCollection($collection2);

        $document->store();
        $this->assertEquals(2, count($document->getCollection()), 'After storing: document should have 2 collections.');

        $document = new Opus_Document($document->getId());
        $this->assertEquals(2, count($document->getCollection()), 'After storing: document should have 2 collections.');
    }

    /**
     * Test for storing collections, adding same collection twice.
     */
    public function testStoreDocumentWithDuplicateCollectionsTest() {
        $role = new Opus_CollectionRole();
        $role->setName('foobar-' . rand());
        $role->setOaiName('foobar-oai-' . rand());
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $collection1 = $root->addFirstChild();
        $root->store();

        $document = new Opus_Document();
        $document->setType('test');
        $document->addCollection($collection1);
        $document->addCollection($collection1);
        $document->store();

        $document = new Opus_Document($document->getId());
        $this->assertEquals(1, count($document->getCollection()), 'After storing: document should have 1 collections.');
    }

    /**
     * Test for storing collections, check that collection still exists after
     * second store.
     */
    public function testStoreDocumentDoesNotDeleteCollectionTest() {
        $role = new Opus_CollectionRole();
        $role->setName('foobar-' . rand());
        $role->setOaiName('foobar-oai-' . rand());

        $root = $role->addRootCollection();
        $collection = $root->addFirstChild();
        $role->store();

        $document = new Opus_Document();
        $document->setType('test');
        $document->addCollection($collection);
        $docId = $document->store();

        // Check if we created what we're expecting later.
        $document = new Opus_Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After storing: document should have 1 collection.');

        // Storing
        $document = new Opus_Document($docId);
        $document->store();

        $document = new Opus_Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After 2nd store(): document should still have 1 collection.');

        // Storing
        $document = new Opus_Document($docId);
        $document->setType('test');
        $document->store();

        $document = new Opus_Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After 3rd store(): document should still have 1 collection.');

        // Storing
        $document = new Opus_Document($docId);
        $c = $document->getCollection();
        $document->store();

        $document = new Opus_Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After 4th store(): document should still have 1 collection.');
    }

    public function testGetAllDocumentsByAuthorsReturnsDocumentsWithoutAuthor() {
        $d = new Opus_Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Opus_Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Opus_Document::getAllDocumentsByAuthors();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Opus_Document::getAllDocumentsByAuthorsByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByAuthorsByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByAuthorsByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    public function testGetAllDocumentsByTitleReturnsDocumentsWithoutTitle() {
        $d = new Opus_Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Opus_Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Opus_Document::getAllDocumentsByTitles();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Opus_Document::getAllDocumentsByTitlesByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByTitlesByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByTitlesByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    public function testGetAllDocumentsByDoctype() {
        $d = new Opus_Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Opus_Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Opus_Document::getAllDocumentsByDoctype();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Opus_Document::getAllDocumentsByDoctypeByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByDoctypeByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByDoctypeByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    public function testGetAllDocumentsByPubDate() {
        $d = new Opus_Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Opus_Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Opus_Document::getAllDocumentsByPubDate();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Opus_Document::getAllDocumentsByPubDateByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByPubDateByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Opus_Document::getAllDocumentsByPubDateByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    /**
     * We had a problem, that we were caching the xml document of a newly
     * created document, which had incomplete File entries.
     */
    public function testDocumentCacheContainsFileWithOutdatedData() {
        $config = Zend_Registry::get('Zend_Config');
        $filename = $config->workspacePath;
        touch($filename);

        $doc = new Opus_Document();
        $doc->setType('test');
        $doc->setServerState('published');
        $file = $doc->addFile();
        $file->setPathName($filename);
        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $file = $doc->getFile(0);

        $this->assertEquals('1', $file->getVisibleInFrontdoor());
        $this->assertEquals('1', $file->getVisibleInOai());

        $cache = new Opus_Model_Xml_Cache();
        $xmlVersion1 = new Opus_Model_Xml_Version1();

        $xmlModel = new Opus_Model_Xml;
        $xmlModel->setModel($doc);
        $xmlModel->setStrategy($xmlVersion1);
        $xmlModel->setXmlCache($cache);

        $xml_file = $xmlModel->getDomDocument()->getElementsByTagName('File')->item(0);
        $this->assertInstanceOf('DOMNode', $xml_file);

        $expected_visible_field = $file->getVisibleInFrontdoor();
        $actual_visible_field = $xml_file->getAttribute('VisibleInFrontdoor');
        $this->assertEquals($expected_visible_field, $actual_visible_field);

        $expected_visible_field = $file->getVisibleInOai();
        $actual_visible_field = $xml_file->getAttribute('VisibleInOai');
        $this->assertEquals($expected_visible_field, $actual_visible_field);
    }

    public function testAddDnbInstitute() {
        $dnb_institute = new Opus_DnbInstitute();
        $dnb_institute->setName('Forschungsinstitut für Code Coverage')
                ->setAddress('Musterstr. 23 - 12345 Entenhausen - Calisota')
                ->setCity('Calisota')
                ->setPhone('+1 234 56789')
                ->setDnbContactId('F1111-1111')
                ->setIsGrantor('1');
        // store
        $id = $dnb_institute->store();

        $document = new Opus_Document();
        $document->store();

        $document->addThesisGrantor($dnb_institute);
        $docId = $document->store();

        $document = new Opus_Document($docId);
        $this->assertEquals(1, count($document->getThesisGrantor()));
    }

    public function testSetDnbInstitute() {
        $dnb_institute = new Opus_DnbInstitute();
        $dnb_institute->setName('Forschungsinstitut für Code Coverage')
                ->setAddress('Musterstr. 23 - 12345 Entenhausen - Calisota')
                ->setCity('Calisota')
                ->setPhone('+1 234 56789')
                ->setDnbContactId('F1111-1111')
                ->setIsGrantor('1');
        // store
        $id = $dnb_institute->store();

        $document = new Opus_Document();
        $document->store();

        $document->setThesisGrantor($dnb_institute);
        $docId = $document->store();

        $document = new Opus_Document($docId);
        $this->assertEquals(1, count($document->getThesisGrantor()));
    }

    /**
     * Regression test for OPUSVIER-2205.
     */
    public function testStoringPageFieldsAsAlnumStrings() {
        $document = new Opus_Document();
        $document->setPageFirst('III');
        $document->setPageLast('IV');
        $document->setPageNumber('II');

        $document->store();

        $docId = $document->getId();

        $document = new Opus_Document($docId);

        $this->assertNotEquals('0', $document->getPageFirst());
        $this->assertEquals('III', $document->getPageFirst());

        $this->assertNotEquals('0', $document->getPageLast());
        $this->assertEquals('IV', $document->getPageLast());

        $this->assertNotEquals('0', $document->getPageNumber());
        $this->assertEquals('II', $document->getPageNumber());
    }

    public function testSortOrderForAddPersonAuthors() {
        $document = $this->_createDocumentWithPersonAuthors(16);
        $docId = $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Opus_Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // First check, if everybody is in place.
        $authors = $document->getPersonAuthor();
        for ($i = 0; $i < count($authors); $i++) {
            $this->assertEquals('firstname-$i=' . $i, $authors[$i]->getFirstName());
            $this->assertEquals('lastname-$i=' . $i, $authors[$i]->getLastName());
        }
    }

    public function testSortOrderForSetPersonAuthorReverse() {
        $document = $this->_createDocumentWithPersonAuthors(16);
        $docId = $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Opus_Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // Do something with authors: reverse
        $authors = $document->getPersonAuthor();
        $new_authors = array_reverse($authors);
        $document->setPersonAuthor($new_authors);
        $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Opus_Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // First check, if everybody is in place.
        $authors = $document->getPersonAuthor();
        $this->assertTrue(is_array($authors));
        $this->assertTrue(is_array($new_authors));
        $this->assertEquals(count($new_authors), count($authors));

        for ($i = 0; $i < count($new_authors); $i++) {
            $this->assertEquals($new_authors[$i]->getFirstName(), $authors[$i]->getFirstName());
            $this->assertEquals($new_authors[$i]->getLastName(), $authors[$i]->getLastName());
        }
    }

    public function testSortOrderForSetPersonAuthorShuffleDeleteAdd() {
        $document = $this->_createDocumentWithPersonAuthors(16);
        $docId = $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Opus_Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // Do something with authors: shuffle, remove some, add one...
        $authors = $document->getPersonAuthor();
        $new_authors = $authors;

        shuffle($new_authors);
        array_pop($new_authors);
        array_shift($new_authors);

        $new_authors[] = $document->addPersonAuthor(new Opus_Person)
                ->setFirstName("new")
                ->setLastName("new");

        $document->setPersonAuthor($new_authors);
        $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Opus_Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // First check, if everybody is in place.
        $authors = $document->getPersonAuthor();
        $this->assertTrue(is_array($authors));
        $this->assertTrue(is_array($new_authors));
        $this->assertEquals(count($new_authors), count($authors));

        for ($i = 0; $i < count($new_authors); $i++) {
            $this->assertEquals($new_authors[$i]->getFirstName(), $authors[$i]->getFirstName());
            $this->assertEquals($new_authors[$i]->getLastName(), $authors[$i]->getLastName());
        }
    }

    private function _createDocumentWithPersonAuthors($author_count) {
        $document = new Opus_Document();
        for ($i = 0; $i < $author_count; $i++) {
            $person = new Opus_Person();
            $person->setFirstName('firstname-$i=' . $i);
            $person->setLastName('lastname-$i=' . $i);

            $document->addPersonAuthor($person);
        }
        return $document;
    }

    private function _checkPersonAuthorSortOrderForDocument($document) {
        $authors = $document->getPersonAuthor();
        $numbers = array();
        foreach ($authors AS $author) {
            $this->assertNotNull($author->getSortOrder());
            $numbers[] = $author->getSortOrder();
        }

        // Check if all numbers are unique
        $unique_numbers = array_unique($numbers);
        $this->assertEquals(count($authors), count($unique_numbers));
    }

    public function testGetEarliestPublicationDate() {
        $nullDate = Opus_Document::getEarliestPublicationDate();
        $this->assertNull($nullDate, "Expected NULL on empty database.");

        // Insert valid entry through framework.
        $document = new Opus_Document();
        $document->setServerDatePublished('2011-06-01T00:00:00Z');
        $document->store();
        $validDate = Opus_Document::getEarliestPublicationDate();
        $this->assertEquals('2011-06-01', $validDate);

        // Insert invalid entry into database...
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');
        $table->insert(array('server_date_published' => '1234', 'server_date_created' => '1234'));
        $invalidDate = Opus_Document::getEarliestPublicationDate();
        $this->assertNull($invalidDate, "Expected NULL on invalid date.");
    }

    public function testGetDefaultsForPublicationState() {
        $doc = new Opus_Document();

        $values = $doc->getField('PublicationState')->getDefault();

        $this->assertEquals(5, count($values));
        $this->assertContains('draft', $values);
    }

    /**
     * Regression test for OPUSVIER-2111
     * @expectedException Opus_Model_DbException
     * @expectedExceptionMessage truncated
     */
    public function testTruncateExceptionIsThrownFor26Chars() {
        $d = new Opus_Document();
        $stringWith26Chars = '';
        for ($i = 0; $i <= 255; $i++) {
            $stringWith26Chars .= 'x';
        }
        $d->setEdition($stringWith26Chars);
        $d->setIssue($stringWith26Chars);
        $d->setVolume($stringWith26Chars);
        $d->store();
    }

    /**
     * Regression test for OPUSVIER-2111
     * @expectedException Opus_Model_DbException
     * @expectedExceptionMessage truncated
     */
    public function testTruncateExceptionIsThrownFor256Chars() {
        $d = new Opus_Document();
        $stringWith256Chars = '';
        for ($i = 0; $i <= 255; $i++) {
            $stringWith256Chars .= 'x';
        }
        $d->setPublisherPlace($stringWith256Chars);
        $d->setPublisherName($stringWith256Chars);
        $d->setLanguage($stringWith256Chars);
        $d->store();
    }

    /**
     * Regression test for OPUSVIER-2111
     */
    public function testTruncateExceptionIsNotThrown() {
        $d = new Opus_Document();
        $stringWith25Chars = '';
        for ($i = 0; $i < 25; $i++) {
            $stringWith25Chars .= 'x';
        }
        $d->setEdition($stringWith25Chars);
        $d->setIssue($stringWith25Chars);
        $d->setVolume($stringWith25Chars);
        $d->store();

        $stringWith255Chars = '';
        for ($i = 0; $i < 255; $i++) {
            $stringWith255Chars .= 'x';
        }
        $d->setPublisherPlace($stringWith255Chars);
        $d->setPublisherName($stringWith255Chars);
        $d->setLanguage($stringWith255Chars);
        $d->store();
    }

    /**
     * High-level regression test for OPUSVIER-2261.
     */
    public function testStoringTwiceWithSeriesModications() {
        $doc = new Opus_Document();

        $series = new Opus_Series();
        $series->setTitle('testseries');
        $series->store();

        $slink = $doc->addSeries($series);
        $slink->setNumber(50);

        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $assignedSeries = $doc->getSeries();

        $this->assertEquals(1, count($assignedSeries));
        $this->assertEquals(50, $assignedSeries[0]->getNumber());

        $doc->store(); // NOTE: without this store the test was successfull

        $assignedSeries = $doc->getSeries();

        $assignedSeries[0]->setNumber(60);

        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $assignedSeries = $doc->getSeries();

        $this->assertEquals(60, $assignedSeries[0]->getNumber());
    }

    /**
     * High-level regression test for OPUSVIER-2261.
     */
    public function testStoringTwiceWithPersonModications() {
        $doc = new Opus_Document();

        $person = new Opus_Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $persons = $doc->getPerson();

        $this->assertEquals(1, count($persons));
        $this->assertEquals('advisor', $persons[0]->getRole());

        $doc->store(); // NOTE: without this store the test was successfull

        $persons = $doc->getPerson();

        $persons[0]->setRole('author');

        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $persons = $doc->getPerson();

        $this->assertEquals('author', $persons[0]->getRole());
    }

    public function testChangingRoleOfPerson() {
        $this->markTestIncomplete('Knallt. Soll das so sein? Was ist falsch?');
        $doc = new Opus_Document();

        $person = new Opus_Person();
        $person->setLastName('Testy');
        $person->store(); // notwendig?

        $doc->setPersonAuthor(array($person));

        $doc = new Opus_Document($doc->store());

        $this->assertEquals(1, count($doc->getPerson()));
        $this->assertEquals(1, count($doc->getPersonAuthor()));

        $persons = $doc->getPersonAuthor();
        $person = $persons[0];

        $person->setRole('submitter');

        $doc->setPersonAuthor(array());
        $doc->setPersonSubmitter(array($person));

        $doc = new Opus_Document($doc->store());

        $this->assertEquals(1, count($doc->getPerson()));
        $this->assertEquals(1, count($doc->getPersonSubmitter()));
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetPersonZero() {
        $doc = new Opus_Document();
        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified');

        $this->assertTrue(count($doc->getPerson()) == 0, 'testcase changed?');
        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified after getField(Person)!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetFieldPersonZero() {
        $doc = new Opus_Document();
        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified');

        $this->assertEquals(false, $doc->getField('Person')->isModified(), 'Field Person should not be modified');
        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified after getField(Person)!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetPersonOne() {
        $doc = new Opus_Document();

        $person = new Opus_Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified');

        $persons = $doc->getPerson();
        $this->assertTrue(count($persons) == 1, 'testcase changed?');

        $this->assertEquals(false, $persons[0]->getModel()->isModified(), 'linked model has just been loaded and is not modified!');

        $this->markTestIncomplete('Check: Is only SortOrder modified?');
        $this->assertEquals(false, $persons[0]->isModified(), 'link model has just been loaded and is not modified!');

        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified after getPerson!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetFieldPersonOne() {
        $doc = new Opus_Document();

        $person = new Opus_Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $doc = new Opus_Document($doc->getId());
        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified');

        $this->markTestIncomplete('Check: Is only SortOrder modified?');
        $this->assertEquals(false, $doc->getField('Person')->isModified(), 'Field Person should not be modified');

        $this->assertEquals(false, $doc->isModified(), 'doc should not be modified after getField(Person)!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testPlinkIsNotModified() {
        $doc = new Opus_Document();

        $person = new Opus_Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $plink->getField('SortOrder')
                ->setValue(123)
                ->clearModified();

        $this->assertEquals(false, $plink->getField('SortOrder')->isModified(), 'plink->SortOrder should not be modified before');

        $newField = new Opus_Model_Field('test');
        $newField->setSortFieldName('SortOrder');
        $newField->setValue(array($plink));

        $this->markTestIncomplete('Modification tracking bug with SortOrder field not fully fixed yet.');

        $this->assertEquals(false, $plink->getField('SortOrder')->isModified(), 'plink->SortOrder should not be modified after');
    }

    public function testChangeTitleType() {
        $this->markTestSkipped('Does not work (see OPUSVIER-2318).');
        $doc = new Opus_Document();

        $titleParent = new Opus_Title();
        $titleParent->setLanguage('deu');
        $titleParent->setValue('Title Parent');

        $doc->addTitleParent($titleParent);
        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $this->assertEquals(0, count($doc->getTitleMain()));
        $this->assertEquals(1, count($doc->getTitleParent()));

        $titleParent = $doc->getTitleParent();
        $titleParent[0]->setType('main');

        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $this->assertEquals(1, count($doc->getTitleMain()), 'Should have 1 TitleMain.');
        $this->assertEquals(0, count($doc->getTitleParent()), 'Should have 0 TitleParent.');
    }

    public function testChangeTitleTypeAlternateWay() {
        $doc = new Opus_Document();

        $titleParent = new Opus_Title();
        $titleParent->setLanguage('deu');
        $titleParent->setValue('Title Parent');

        $doc->addTitleParent($titleParent);
        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $this->assertEquals(0, count($doc->getTitleMain()));
        $this->assertEquals(1, count($doc->getTitleParent()));

        // remove title
        $titleParent = $doc->getTitleParent();
        $title = $titleParent[0];
        $movedTitle = new Opus_Title();
        $movedTitle->setLanguage($title->getLanguage());
        $movedTitle->setValue($title->getValue());
        unset($titleParent[0]);
        $doc->setTitleParent($titleParent);
        $doc->store();

        // add title
        $doc = new Opus_Document($doc->getId());
        $doc->addTitleMain($movedTitle);
        $doc->store();

        $doc = new Opus_Document($doc->getId());

        $this->assertEquals(1, count($doc->getTitleMain()), 'Should have 1 TitleMain.');
        $this->assertEquals(0, count($doc->getTitleParent()), 'Should have 0 TitleParent.');
    }

    public function testRegression2916StoreModifiesServerDataModifiedForOtherDocs() {
        $doc1 = new Opus_Document();
        $doc1Id = $doc1->store();
        $doc1ServerDateModified = $doc1->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $doc2 = new Opus_Document();
        $title = new Opus_Title();
        $title->setLanguage('eng');
        $title->setValue('Test Titel');
        $doc2->addTitleMain($title);
        $doc2->store();

        $doc1 = new Opus_Document($doc1Id);

        $this->assertEquals($doc1ServerDateModified, $doc1->getServerDateModified()->getUnixTimestamp(), 'ServerDateModified was modified by store on a differnet document.');
    }

    public function testRegression2982StoreWithInstituteModifiesServerDateModifiedForOtherDocs() {
        $institute = new Opus_DnbInstitute();
        $institute->setName('Test Institut');
        $institute->setCity('Berlin');
        $institute->setIsGrantor(true);
        $institute->setIsPublisher(true);
        $instituteId = $institute->store();

        $doc1 = new Opus_Document();
        $institute = new Opus_DnbInstitute($instituteId);
        $doc1->setThesisGrantor(array($institute));
        $doc1id = $doc1->store();
        $doc1ServerDateModified = $doc1->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $doc2 = new Opus_Document();
        $institute = new Opus_DnbInstitute($instituteId);
        $doc2->setThesisGrantor(array($institute));
        $doc2->store();

        $doc1 = new Opus_Document($doc1id);

        $this->assertEquals($doc1ServerDateModified, $doc1->getServerDateModified()->getUnixTimestamp(), 'ServerDateModified was modified by store on a differnet document.');
    }

    public function testHasPlugins() {
        $doc = new Opus_Document();
        $this->assertTrue($doc->hasPlugin('Opus_Document_Plugin_Index'), 'Opus_Document_Plugin_Index is not registered');
        $this->assertTrue($doc->hasPlugin('Opus_Document_Plugin_XmlCache'), 'Opus_Document_Plugin_XmlCache is not registered');
        $this->assertTrue($doc->hasPlugin('Opus_Document_Plugin_IdentifierUrn'), 'Opus_Document_Plugin_IdentifierUrn is not registered');
    }

    /**
     * Regression Test for OPUSVIER-3203
     */
    public function testDeleteFields() {

        $title = new Opus_Title();
        $title->setValue('Blah Blah');
        $title->setLanguage('deu');

        $doc = new Opus_Document();
        $doc->setTitleMain($title);
        $docid = $doc->store();

        $redoc = new Opus_Document($docid);
        $redoc->deleteFields(array('TitleMain'));
        $redoc->store();

        $retitle = new Opus_Title();
        $retitle->setValue('Blah Blah Blah');
        $retitle->setLanguage('deu');

        $redoc->setTitleMain($retitle);

        try {
            $redoc->store();
        } catch(Opus_Model_Exception $ome) {
            $this->fail($ome->getMessage());
        }
    }

    public function testUpdateServerDateModifiedAfterDeleteFields() {
        $doc = new Opus_Document();
        $doc->setEdition('Test Edition');
        $docId = $doc->store();
        $docServerDateModified = $doc->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $doc = new Opus_Document($docId);
        $doc->deleteFields(array('Edition'));
        $doc->store();

        $doc = new Opus_Document($docId);

        $this->assertNotEquals($docServerDateModified, $doc->getServerDateModified()->getUnixTimestamp(),
            'ServerDateModified was not modified by deleteFields.');
    }

    /**
     * The results should be sorted ascending according to their sort order.
     */
    public function testGetFileSortOrder() {
        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();
        touch($path);

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $file1 = $doc->addFile();
        $file1->setPathName('testC.txt');
        $file1->setSortOrder(20);
        $file1->setTempFile($path);
        $file2 = $doc->addFile();
        $file2->setPathName('testB.txt');
        $file2->setSortOrder(10);
        $file2->setTempFile($path);
        $file3 = $doc->addFile();
        $file3->setPathName('testA.txt');
        $file3->setSortOrder(30);
        $file3->setTempFile($path);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();

        unlink($file1->getPath());
        unlink($file2->getPath());
        unlink($file3->getPath());

        $this->assertEquals($files[0]->getPathName(), 'testB.txt');
        $this->assertEquals($files[1]->getPathName(), 'testC.txt');
        $this->assertEquals($files[2]->getPathName(), 'testA.txt');
    }

    /**
     * Sortierung muss auch beim Zugriff über Modelklasse funktionieren.
     */
    public function testFileSortOrderThroughFieldModel() {
        $this->markTestSkipped('TODO noch nicht gefixt, aber langfristig evtl. auch nicht notwendig');

        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();
        touch($path);

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $file1 = $doc->addFile();
        $file1->setPathName('testC.txt');
        $file1->setSortOrder(20);
        $file1->setTempFile($path);
        $file2 = $doc->addFile();
        $file2->setPathName('testB.txt');
        $file2->setSortOrder(10);
        $file2->setTempFile($path);
        $file3 = $doc->addFile();
        $file3->setPathName('testA.txt');
        $file3->setSortOrder(30);
        $file3->setTempFile($path);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $field = $doc->getField('File');
        $files = $field->getValue();

        unlink($file1->getPath());
        unlink($file2->getPath());
        unlink($file3->getPath());

        $this->assertEquals($files[0]->getPathName(), 'testB.txt');
        $this->assertEquals($files[1]->getPathName(), 'testC.txt');
        $this->assertEquals($files[2]->getPathName(), 'testA.txt');
    }

    /**
     * If the sort order is equal for every file, the results should be sorted ascending according to their id.
     */
    public function testGetFileSortingWithEqualSortOrder() {
        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();
        touch($path);

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $file1 = $doc->addFile();
        $file1->setPathName('testC.txt');
        $file1->setSortOrder(0);
        $file1->setTempFile($path);
        $file2 = $doc->addFile();
        $file2->setPathName('testB.txt');
        $file2->setSortOrder(0);
        $file2->setTempFile($path);
        $file3 = $doc->addFile();
        $file3->setPathName('testA.txt');
        $file3->setSortOrder(0);
        $file3->setTempFile($path);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $files = $doc->getFile();

        unlink($file1->getPath());
        unlink($file2->getPath());
        unlink($file3->getPath());

        $this->assertEquals($files[0]->getPathName(), 'testC.txt');
        $this->assertEquals($files[1]->getPathName(), 'testB.txt');
        $this->assertEquals($files[2]->getPathName(), 'testA.txt');
    }

    /**
     * Test für OPUSVIER-3276.
     */
    public function testHasEmbargoDatePassedFalse() {
        $doc = new Opus_Document();
        $doc->setEmbargoDate('2100-10-13');

        $now = new Opus_Date('2014-06-18');
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $this->assertFalse($doc->hasEmbargoPassed(), 'OPUS has been developed for way too long. :-)');
    }

    public function testHasEmbargoDatePassedTrue() {
        $doc = new Opus_Document();
        $doc->setEmbargoDate('2000-10-12');
        $this->assertTrue($doc->hasEmbargoPassed());

        $now = new Opus_Date('2000-11-10');
        $this->assertTrue($doc->hasEmbargoPassed($now));
    }

    public function testHasEmbargoDatePassedSameDay() {
        $now = new Opus_Date('2014-06-18');

        $doc = new Opus_Document();
        $doc->setEmbargoDate('2014-06-18');
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $now = new Opus_Date("2014-06-18T12:00:00");
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $now = new Opus_Date("2014-06-18T23:59:59");
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $now = new Opus_Date("2014-06-19");
        $this->assertTrue($doc->hasEmbargoPassed($now));
    }

    public function testIsNewRecord()
    {
        $doc = new Opus_Document();

        $this->assertTrue($doc->isNewRecord());

        $doc->store();

        $this->assertFalse($doc->isNewRecord());
    }

    public function testSetServerDateModifiedByIds() {
        $doc = new Opus_Document();
        $doc1Id = $doc->store();

        $doc = new Opus_Document();
        $doc2Id = $doc->store();

        $doc = new Opus_Document();
        $doc3Id = $doc->store();

        $date = new Opus_Date('2016-05-10');

        Opus_Document::setServerDateModifiedByIds($date, array(1, 3));

        $doc = new Opus_Document($doc1Id);
        $this->assertEquals('2016-05-10', $doc->getServerDateModified());

        $doc = new Opus_Document($doc2Id);
        $this->assertNotEquals('2016-05-10', $doc->getServerDateModified());

        $doc = new Opus_Document($doc3Id);
        $this->assertEquals('2016-05-10', $doc->getServerDateModified());
    }

    /**
     * @expectedException Opus_Model_DbException
     * @expectedExceptionMessage truncated
     */
    public function testSetServerStateInvalidValue() {
        $doc = new Opus_Document();
        $doc->setServerState('unknown');
        $doc->store();
    }

    /**
     * TODO how to test if indexing is called only once?
     *
     * Probably the best way of testing this would be replacing the regular Solr adapter with a dummy adapter, that
     * just counts functions calls. However it isn't clear yet how such an adapter can be injected. Such an adapter
     * would be very valuable to verify the indexing behaviour of OPUS under various conditions.
     */
    public function testStoreingPublishedIndexingOnlyOnce()
    {
        $this->markTestIncomplete('Requires way of counting calls to indexing adapter');

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->store();

        // check indexing operations
    }

    protected function setupDocumentWithMultipleTitles()
    {
        $doc = new Opus_Document();
        $doc->setLanguage('deu');

        $title = $doc->addTitleMain();
        $title->setValue('French');
        $title->setLanguage('fre');

        $title = $doc->addTitleMain();
        $title->setValue('Deutsch');
        $title->setLanguage('deu');

        $title = $doc->addTitleMain();
        $title->setValue('English');
        $title->setLanguage('eng');

        return $doc->store();
    }

    public function testGetMainTitle()
    {
        $docId = $this->setupDocumentWithMultipleTitles();

        $doc = new Opus_Document($docId);

        $this->assertCount(3, $doc->getTitleMain());

        $title = $doc->getMainTitle();

        $this->assertInstanceOf('Opus_Title', $title);
        $this->assertEquals('Deutsch', $title->getValue());
        $this->assertEquals('deu', $title->getLanguage());
    }

    public function testGetMainTitleForLanguage()
    {
        $docId = $this->setupDocumentWithMultipleTitles();

        $doc = new Opus_Document($docId);

        $title = $doc->getMainTitle('fre');

        $this->assertEquals('French', $title->getValue());
        $this->assertEquals('fre', $title->getLanguage());

        $title = $doc->getMainTitle('eng');

        $this->assertEquals('English', $title->getValue());
        $this->assertEquals('eng', $title->getLanguage());
    }

    public function testGetMainTitleForUnknownLanguage()
    {
        $docId = $this->setupDocumentWithMultipleTitles();

        $doc = new Opus_Document($docId);

        $title = $doc->getMainTitle('rus');

        // should return title in document language
        $this->assertEquals('Deutsch', $title->getValue());
        $this->assertEquals('deu', $title->getLanguage());
    }

    public function testGetMainTitleWithNoDocumentLanguage()
    {
        $docId = $this->setupDocumentWithMultipleTitles();

        $doc = new Opus_Document($docId);

        $doc->setLanguage(null);
        $doc->store();

        $doc = new Opus_Document($docId);

        $this->assertNull($doc->getLanguage());

        $title = $doc->getMainTitle();

        // should return first title
        $this->assertEquals('French', $title->getValue());
        $this->assertEquals('fre', $title->getLanguage());
    }

    public function testGetMainTitleForNoTitles()
    {
        $doc = new Opus_Document();
        $doc->setLanguage('deu');
        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $title = $doc->getMainTitle();

        $this->assertNull($title);
    }

    public function testHasFulltext()
    {
        $doc = new Opus_Document();

        $config = Zend_Registry::get('Zend_Config');
        $tempFile = $config->workspacePath . '/tmp/'. uniqid();

        touch($tempFile);

        $file = $doc->addFile();
        $file->setPathName('test.txt');
        $file->setMimeType('text/plain');
        $file->setTempFile($tempFile);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $files = $doc->getFile();

        $this->assertTrue($doc->hasFulltext());

        $files[0]->setVisibleInFrontdoor(0);

        $doc = new Opus_Document($doc->store());

        $this->assertFalse($doc->hasFulltext());

        unlink($files[0]->getPath());
    }

    public function testIsOpenAccess()
    {
        $role = new Opus_CollectionRole();
        $role->setName('open_access');
        $role->setOaiName('open_access');
        $role->store();

        $root = $role->addRootCollection();

        $col = new Opus_Collection();
        $col->setName('open_access');
        $col->setOaiSubset('open_access');

        $root->addFirstChild($col);
        $role->store();

        $doc = new Opus_Document();
        $doc->setType('article');
        $doc->addCollection($col);
        $docId = $doc->store();

        $this->assertTrue($col->holdsDocumentById($docId));

        $doc = new Opus_Document($docId);

        $this->assertTrue($doc->isOpenAccess());

        $doc->setCollection(null);
        $doc->store();

        $this->assertFalse($doc->isOpenAccess());
    }

    public function testRemoveAllPersons()
    {
        // create document with one person
        $doc = new Opus_Document();
        $doc->setType('article');

        $title = new Opus_Title();
        $title->setLanguage('eng');
        $title->setValue('Test document');
        $doc->addTitleMain($title);

        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc->addPersonAuthor($person);

        $docId = $doc->store();

        // add second person
        $doc = new Opus_Document($docId);

        $persons = $doc->getPerson();

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = new Opus_Person();
        $person->setLastName('Tester2');
        $doc->addPersonReferee($person);

        $doc->store();

        $doc = new Opus_Document($docId);

        $persons = $doc->getPerson();

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(2, $persons);

        // remove all persons
        $doc->setPerson(null);
        $doc->store();

        $doc = new Opus_Document($docId);

        $persons = $doc->getPerson();

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(0, $persons);
    }

}
