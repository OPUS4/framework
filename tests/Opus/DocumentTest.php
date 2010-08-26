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
 * @package     Opus
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
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
        $this->assertTrue(is_int($match_result) && $match_result > 0,
                'Serialized string does not contain Opus_Document as string.');
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
                'ServerDateUnlocking' => '2008-12-01',
                'BelongsToBibliography' => 1,
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
        $this->assertType('Opus_Model_Dependent_Link_DocumentLicence', $value[0], 'Returned object is of wrong type.');
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
        $this->assertType('Opus_Note', $value[0], 'Returned object is of wrong type.');
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
     * Test if adding more values to a multi-value field than it may hold throws
     * an InvalidArgumentException.
     *
     * @return void
     */
    public function testAddingMoreValuesThanMultiplicityAllowsThrowsException() {
        // TODO: Write new unit test for new behaviour.
        $this->markTestSkipped('Obsolete test after removing document builder.');

        $document = new Opus_Document();
        $document->setType("doctoral_thesis");

        $author = new Opus_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');

        $document->addPersonAuthor($author);
        $document->addPersonAuthor($author);
        $this->setExpectedException('InvalidArgumentException');
        $document->addPersonAuthor($author);

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
        $this->assertEquals('deleted', $doc->getServerState(),
                "Server state should be set to 'deleted' now.");
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
        $doc->store();

        $linkId = $doc->getPersonAuthor(0)->getId();

        $doc->deletePermanent();
        $this->setExpectedException('Opus_Model_NotFoundException');
        $link = new Opus_Model_Dependent_Link_DocumentPerson($linkId);
    }

    /**
     * Test if corresponding links to licences are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesLicenceLink() {
        $this->markTestSkipped('Delete documents is currently under development.');

        $doc = new Opus_Document();
        $licence = new Opus_Licence();
        $licence->setNameLong('LongName');
        $licence->setLinkLicence('http://long.org/licence');

        $doc->addLicence($licence);
        $docid = $doc->store();
        $linkid = $doc->getLicence(0)->getId();
        $doc->delete();

        $this->setExpectedException('Opus_Model_Exception');
        $link = new Opus_Model_Dependent_Link_DocumentLicence($linkid);

        $this->fail("Document delete has not been cascaded.");
    }

    /**
     * Test if corresponding enrichments are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesEnrichments() {
        $this->markTestSkipped('Enrichments currently under development.');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $enrichment = new Opus_Enrichment();

        $doc->addEnrichment($enrichment);
        $doc->store();
        $id = $doc->getEnrichment()->getId();
        $doc->delete();
        $this->setExpectedException('Opus_Model_Exception');
        $enrichment = new Opus_Enrichment($id);
    }

    /**
     * Test if corresponding identifiers are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesIdentifiers() {
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $isbn = new Opus_Identifier();
        $isbn->setValue('ISBN');

        $doc->addIdentifierIsbn($isbn);
        $doc->store();
        $id = $doc->getIdentifierIsbn()->getId();
        $doc->delete();
        $this->setExpectedException('Opus_Model_Exception');
        $isbn = new Opus_Identifier($id);
    }

    /**
     * Test if corresponding patents are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesPatents() {
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $patent = new Opus_Patent();
        $patent->setNumber('X0815');
        $patent->setDateGranted('01-01-2001');

        $doc->addPatent($patent);
        $doc->store();
        $id = $doc->getPatent()->getId();
        $doc->delete();
        $this->setExpectedException('Opus_Model_Exception');
        $patent = new Opus_Patent($id);
    }

    /**
     * Test if corresponding notes are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesNotes() {
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $note = new Opus_Note();
        $note->setMessage('A note!')
            ->setCreator('Me');

        $doc->addNote($note);
        $doc->store();
        $id = $doc->getNote()->getId();
        $doc->delete();
        $this->setExpectedException('Opus_Model_Exception');
        $note = new Opus_Note($id);
    }

    /**
     * Test if corresponding subjects are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesSubjects() {
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $subject = new Opus_SubjectSwd();
        $subject->setValue('Schlagwort');

        $doc->addSubjectSwd($subject);
        $doc->store();
        $id = $doc->getSubjectSwd()->getId();
        $doc->delete();
        $this->setExpectedException('Opus_Model_Exception');
        $subject = new Opus_Subject($id);
    }

    /**
     * Test if corresponding titles are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesTitles() {
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $title = new Opus_Title();
        $title->setValue('Title of a document');

        $doc->addTitleMain($title);
        $doc->store();
        $id = $doc->getTitleMain()->getId();
        $doc->delete();
        $this->setExpectedException('Opus_Model_Exception');
        $title = new Opus_Title($id);
    }

    /**
     * Test if corresponding abstracts are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesAbstracts() {
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $abstract = new Opus_Abstract();
        $abstract->setValue('It is necessary to give an abstract.');

        $doc->addTitleAbstract($abstract);
        $doc->store();
        $id = $doc->getTitleAbstract()->getId();
        $doc->delete();
        $this->setExpectedException('Opus_Model_Exception');
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
     * Test if an empty title list can be retrieved from an empty database.
     *
     * @return void
     */
    public function testRetrieveEmptyTitleListFromEmptyDatabase() {
        // TODO: $this->assertTrue(false, 'Cannot check title list - Opus_Document::getAllDocumentTitles does not exist.');
        $this->markTestSkipped('TODO: analyze');

        $result = Opus_Document::getAllDocumentTitles();
        $this->assertTrue(empty($result), 'Title list contains phantom results.');
    }

    /**
     * Test if a correct title list can be retrieved.
     *
     * @return void
     */
    public function testRetrieveAllTitles() {
        $doc1 = new Opus_Document();
        $doc1->setType("doctoral_thesis");

        $title1 = $doc1->addTitleMain();
        $title1->setLanguage('de');
        $title1->setValue('Ein deutscher Titel');
        $id1 = $doc1->store();

        $doc2 = new Opus_Document();
        $doc2->setType("doctoral_thesis");

        $title2 = $doc2->addTitleMain();
        $title2->setLanguage('en');
        $title2->setValue('An english titel');
        $id2 = $doc2->store();

        // TODO: $this->assertTrue(false, 'Cannot check title list - Opus_Document::getAllDocumentTitles does not exist.');
        $this->markTestSkipped('TODO: analyze');

        $result = Opus_Document::getAllDocumentTitles();
        $this->assertEquals(2, count($result), 'Wrong number of title entries.');
        $this->assertEquals($title1->getValue(), $result[$id1][0], 'Expected title is not in the list.');
        $this->assertEquals($title2->getValue(), $result[$id2][0], 'Expected title is not in the list.');
    }

    /**
     * Test if the corresponding document id is set for each titile in the tile list.
     *
     * @return void
     */
    public function testRetrieveDocumentIdPerTitle() {

        $doc1 = new Opus_Document();
        $doc1->setType("doctoral_thesis");
        $title1 = $doc1->addTitleMain();
        $title1->setLanguage('de');
        $title1->setValue('Ein deutscher Titel');
        $title2 = $doc1->addTitleMain();
        $title2->setLanguage('en');
        $title2->setValue('Ein englischer Titel');
        $id1 = $doc1->store();


        $doc2 = new Opus_Document();
        $doc2->setType("doctoral_thesis");
        $title3 = $doc2->addTitleMain();
        $title3->setLanguage('en');
        $title3->setValue('An english titel');
        $id2 = $doc2->store();

        // TODO: $this->assertTrue(false, 'Cannot check title list - Opus_Document::getAllDocumentTitles does not exist.');
        $this->markTestSkipped('TODO: analyze');

        $result = Opus_Document::getAllDocumentTitles();

        $this->assertEquals($title1->getValue(), $result[$id1][0], 'Wrong document id for title.');
        $this->assertEquals($title2->getValue(), $result[$id1][1], 'Wrong document id for title.');
        $this->assertEquals($title3->getValue(), $result[$id2][0], 'Wrong document id for title.');
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
        $this->assertTrue( $licence[0] instanceof Opus_Model_Dependent_Link_Abstract,
                'Adding to a field containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue( $licence[0] instanceof Opus_Model_Dependent_Link_Abstract,
                'Adding to a field containing a link model failed (getLicence).');
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
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract,
                'Setting a field containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract,
                'Setting a field containing a link model failed (getLicence).');
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
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract,
                'Getting a field value containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof Opus_Model_Dependent_Link_Abstract,
                'Getting a field value containing a link model failed (getLicence).');
    }

    /**
     * Test if title informations delivered back properly with toArray().
     *
     * @return void
     */
    public function testToArrayReturnsCorrectValuesForTitleMain(){
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
//            'SortOrder' => null
            );
        $this->assertEquals($expected, $result, 'toArray() deliver not expected title data.');
    }

    /**
     * Test if multiple languages are (re)stored properly.
     *
     * @return void
     */
    public function testMultipleLanguageStorage() {
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $doc->addLanguage('de');
        $doc->addLanguage('en');
        $doc->addLanguage('fr');
        $languages = $doc->getLanguage();
        $id = $doc->store();

        $doc = new Opus_Document($id);
        $this->assertEquals($languages, $doc->getLanguage(), 'Document language list corrupted by storage.');
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
        $this->markTestSkipped("Creating URN for document not implemented?");

        $doc = new Opus_Document();
        $doc->setType("doctoral_thesis");

        $urn_model = new Opus_Identifier();
        $doc->setIdentifierUrn($urn_model);
        $id = $doc->store();
        $doc2 = new Opus_Document($id);
        $this->assertNotNull($doc2->getIdentifierUrn(0)->getValue(), 'URN value should not be empty.');
        $urn = new Opus_Identifier_Urn('swb', '14', 'opus');
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
        // TODO: analyze
        $this->markTestSkipped('TODO: analyze');

        Opus_Document_Type::setXmlDoctypePath(dirname(__FILE__));
        $newdoc = new Opus_Document(null, 'article');
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

        $doc->setPublishedDate('05.10.2008');
        $doc->setServerDateUnlocking('05.04.2009');

        $personAuthor = new Opus_Person();
        $personAuthor->setFirstName('Real');
        $personAuthor->setLastName('Tester');
        $personAuthor->setDateOfBirth('23.06.1965');
        $doc->addPersonAuthor($personAuthor);

        $patent = new Opus_Patent();
        $patent->setNumber('08 15');
        $patent->setDateGranted('07.07.2008');
        $doc->addPatent($patent);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $publishedDate = $doc->getPublishedDate();
        $serverDateUnlocking = $doc->getServerDateUnlocking();
        $personAuthor = $doc->getPersonAuthor(0);
        $patent = $doc->getPatent(0);
        
        $localeFormatDate = Zend_Locale_Format::getDateFormat($locale);
        $this->assertEquals('05.10.2008', $publishedDate->getZendDate()->toString($localeFormatDate), 'Setting a date through string does not work.');
        $this->assertEquals('05.04.2009', $serverDateUnlocking->getZendDate()->toString($localeFormatDate), 'Setting a date through Zend_Date does not work.');
        $this->assertEquals('23.06.1965', $personAuthor->getDateOfBirth()->getZendDate()->toString($localeFormatDate), 'Setting a date on a model doesn not work.');
        $this->assertEquals('07.07.2008', $patent->getDateGranted()->getZendDate()->toString($localeFormatDate), 'Setting a date on a dependent model doesn not work.');
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

}
