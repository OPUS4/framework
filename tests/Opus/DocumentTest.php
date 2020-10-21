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
 * @author      Michael Lang <lang@zib.de>
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Collection;
use Opus\CollectionRole;
use Opus\Date;
use Opus\Db\TableGateway;
use Opus\DnbInstitute;
use Opus\Document;
use Opus\Enrichment;
use Opus\EnrichmentKey;
use Opus\Identifier;
use Opus\Identifier\Urn;
use Opus\Licence;
use Opus\Model\Dependent\Link\AbstractLinkModel;
use Opus\Model\Dependent\Link\DocumentDnbInstitute;
use Opus\Model\Dependent\Link\DocumentLicence;
use Opus\Model\Dependent\Link\DocumentPerson;
use Opus\Model\Field;
use Opus\Model\Filter;
use Opus\Model\ModelException;
use Opus\Model\Xml;
use Opus\Model\Xml\Cache;
use Opus\Model\Xml\Version1;
use Opus\Note;
use Opus\Patent;
use Opus\Person;
use Opus\Series;
use Opus\Subject;
use Opus\SubjectSwd;
use Opus\Title;
use OpusTest\Model\Mock\ModelWithNonAbstractExtendingClassField;
use OpusTest\TestAsset\TestCase;

/**
 * Test cases for class Opus\Document.
 *
 * @package Opus
 * @category Tests
 *
 * @group DocumentTest
 *
 */
class DocumentTest extends TestCase
{

    private $testFiles;

    /**
     * Set up test fixture.
     *
     * @return void
     */
    public function setUp()
    {
        // Set up a mock language list.
        $list = ['de' => 'Test_Deutsch', 'en' => 'Test_Englisch', 'fr' => 'Test_Französisch'];
        \Zend_Registry::set('Available_Languages', $list);

        parent::setUp();
    }

    public function tearDown()
    {
        $document = new Document();
        $document->setDefaultPlugins(null);

        parent::tearDown();
    }

    /**
     * Test if a Document instance can be serialized.
     *
     * @return void
     */
    public function testSerializing()
    {
        $doc = new Document();
        $ser = serialize($doc);

        $this->assertNotNull($ser, 'Serializing returned NULL.');
        $match_result = preg_match('/"Opus\\\\Document"/', $ser); // four backslashes necessary to match one '\'
        $this->assertTrue(
            is_int($match_result) && $match_result > 0,
            'Serialized string does not contain Opus\Document as string.'
        );
    }

    /**
     * Test if a serialized Document instance can be deserialized.
     *
     * @return void
     */
    public function testDeserializing()
    {
        $doc1 = new Document();
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
    protected static $_validDocumentData = [[[
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
        'ArticleNumber' => 42,
        'CompletedYear' => 1960,
        'CompletedDate' => '1901-01-01',
        'BelongsToBibliography' => 1,
        'EmbargoDate' => '1902-01-01',
    ]]];

    /**
     * Valid document data provider
     *
     * @return array
     */
    public static function validDocumentDataProvider()
    {
        return self::$_validDocumentData;
    }

    /**
     * Test if tunneling setter calls through a n:m link model reaches
     * the target model instance.
     *
     * @return void
     */
    public function testTunnelingSetterCallsInManyToManyLinks()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $licence = new Licence();
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
    public function testAddingModelInManyToManyLink()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $value = $doc->getLicence();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(0, count($value), 'Expected zero objects to be returned initially.');

        $doc->addLicence(new Licence());
        $value = $doc->getLicence();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(1, count($value), 'Expected only one object to be returned after adding.');
        $this->assertInstanceOf('Opus\Model\Dependent\Link\DocumentLicence', $value[0], 'Returned object is of wrong type.');
    }

    /**
     * Test if adding an one-to-many model works.
     *
     * @return void
     */
    public function testAddingModelInOneToManyLink()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $value = $doc->getNote();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(0, count($value), 'Expected zero objects to be returned initially.');

        $doc->addNote();
        $value = $doc->getNote();
        $this->assertTrue(is_array($value), 'Expected array type.');
        $this->assertEquals(1, count($value), 'Expected only one object to be returned after adding.');
        $this->assertInstanceOf('Opus\Note', $value[0], 'Returned object is of wrong type.');
    }

    /**
     * Test if storing a document wich has a linked model doesnt throw
     * an Opus\Model\ModelException.
     *
     * @return void
     *
     */
    public function testStoreWithLinkToIndependentModel()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $author = new Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $doc->addPersonAuthor($author);

        $doc->store();
    }

    /**
     * Test if adding a value to a single-value field that is already populated
     * throws an \InvalidArgumentException.
     *
     * @return void
     */
    public function testAddingValuesToPopulatedSingleValueFieldThrowsException()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $doc->addPageFirst(10);
        $this->setExpectedException('InvalidArgumentException');
        $doc->addPageFirst(100);
    }

    /**
     * Test if an exception is thrown when using a model in a field that does
     * not extend Opus\Model\AbstractModel and for which no custom _fetch method
     * is defined.
     *
     * @return void
     */
    public function testUndefinedFetchMethodForFieldValueClassNotExtendingAbstractModelThrowsException()
    {
        $this->setExpectedException('Opus\Model\ModelException');
        $document = new ModelWithNonAbstractExtendingClassField();
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
    public function testDocumentFieldsPersistDatabaseStorage(array $documentDataset)
    {
        $document = new Document();
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

        $enrichmentkey = new EnrichmentKey();
        $enrichmentkey->setName('foo');
        $enrichmentkey->store();

        $enrichment = $document->addEnrichment();
        $enrichment->setKeyName('foo');
        $enrichment->setValue('Poor enrichment.');

        $author = new Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $author->setDateOfBirth('1889-04-26');
        $author->setPlaceOfBirth('Wien');
        $document->addPersonAuthor($author);

        $author = new Person();
        $author->setFirstName('Ferdinand');
        $author->setLastName('de Saussure');
        $author->setDateOfBirth('1857-11-26');
        $author->setPlaceOfBirth('Genf');
        $document->addPersonAuthor($author);

        $licence = new Licence();
        $licence->setActive(1);
        $licence->setLanguage('de');
        $licence->setLinkLicence('http://creativecommons.org/');
        $licence->setMimeType('text/pdf');
        $licence->setNameLong('Creative Commons');
        $licence->setPodAllowed(1);
        $licence->setSortOrder(0);
        $document->addLicence($licence);

        $dnbInstitute = new DnbInstitute();
        $dnbInstitute->setName('Forschungsinstitut für Code Coverage');
        $dnbInstitute->setCity('Calisota');
        $dnbInstitute->setIsGrantor(1);
        $document->addThesisPublisher($dnbInstitute);
        $document->addThesisGrantor($dnbInstitute);

        // Save document, modify, and save again.
        $id = $document->store();
        $document = new Document($id);
        $title = $document->addTitleMain();
        $title->setValue('Title Two');
        $title->setLanguage('en');
        $id = $document->store();
        $document = new Document($id);

        foreach ($documentDataset as $fieldname => $value) {
            $field = $document->{'get' . $fieldname}();

            // Special handling for Opus\Date fields...
            if ($field instanceof Date) {
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
    public function testDelete()
    {
        $doc = new Document();
        $docid = $doc->store();
        $doc->delete();

        $doc = new Document($docid);
        $this->assertEquals('deleted', $doc->getServerState(), "Server state should be set to 'deleted' now.");
    }

    /**
     * Test if corresponding permanently deleting documents works.
     *
     * @return void
     */
    public function testDeletePermanent()
    {
        $doc = new Document();
        $docid = $doc->store();
        $doc->deletePermanent();

        $this->setExpectedException('Opus\Model\NotFoundException');
        $doc = new Document($docid);
    }

    /**
     * Test if document with author can be deleted permanently.
     *
     * @return void
     */
    public function testDeleteDocumentWithAuthorPermanently()
    {
        $doc = new Document();
        $doc->setType('doctoral_thesis');

        $author = new Person();
        $author->setFirstName('M.');
        $author->setLastName('Gandi');

        $doc->addPersonAuthor($author);
        $modelId = $doc->store();

        $linkId = $doc->getPersonAuthor(0)->getId();

        $doc->deletePermanent();

        $this->setExpectedException('Opus\Model\NotFoundException');
        $doc = new Document($modelId);
    }

    /**
     * Test if document with missing file can be deleted permanently.
     */
    public function testDeleteDocumentWithMissingFile()
    {
        $doc = new Document();
        $doc->setType('doctoral_thesis');

        $modelId = $doc->store();

        $config = \Zend_Registry::get('Zend_Config');
        $tempFile = $config->workspacePath . '/' . uniqid();
        touch($tempFile);

        $file = $doc->addFile();
        $file->setPathName('test.txt');
        $file->setMimeType('text/plain');
        $file->setTempFile($tempFile);

        $doc->store();

        $doc = new Document($modelId);

        $file = $doc->getFile(0);

        $this->assertTrue(! empty($file)); // document has a file

        $filePath = $file->getPath();

        $this->assertTrue(is_file($filePath)); // file exists

        unlink($filePath);

        $this->assertFalse(is_file($filePath)); // file is gone

        $doc->deletePermanent(); // delete document with missing file

        $this->setExpectedException('Opus\Model\NotFoundException');
        $doc = new Document($modelId);
    }

    /**
     * Test if corresponding links to persons are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesPersonLinks()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $author = new Person();
        $author->setFirstName('M.');
        $author->setLastName('Gandi');

        $doc->addPersonAuthor($author);
        $modelId = $doc->store();

        $linkId = $doc->getPersonAuthor(0)->getId();

        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $link = new DocumentPerson($linkId);
    }

    /**
     * Test if corresponding links to dnb_institutes are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesDnbInstituteLink()
    {
        $doc = new Document();
        $dnbInstitute = new DnbInstitute();
        $dnbInstitute->setName('Forschungsinstitut für Code Coverage');
        $dnbInstitute->setCity('Calisota');

        $doc->addThesisPublisher($dnbInstitute);
        $doc->store();
        $linkid = $doc->getThesisPublisher(0)->getId();
        $doc->deletePermanent();

        $this->setExpectedException('Opus\Model\NotFoundException');
        $link = new DocumentDnbInstitute($linkid);

        $this->fail("Document delete has not been cascaded.");
    }

    /**
     * Test if corresponding links to licences are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesLicenceLink()
    {
        $doc = new Document();
        $licence = new Licence();
        $licence->setNameLong('LongName');
        $licence->setLinkLicence('http://long.org/licence');

        $doc->addLicence($licence);
        $doc->store();
        $linkid = $doc->getLicence(0)->getId();
        $doc->deletePermanent();

        $this->setExpectedException('Opus\Model\NotFoundException');
        $link = new DocumentLicence($linkid);

        $this->fail("Document delete has not been cascaded.");
    }

    /**
     * Test if corresponding enrichments are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesEnrichments()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $enrichmentkey = new EnrichmentKey();
        $enrichmentkey->setName('foo');
        $enrichmentkey->store();

        $enrichment = new Enrichment();
        $enrichment->setKeyName('foo');
        $enrichment->setValue('Poor enrichment.');

        $doc->addEnrichment($enrichment);
        $doc->store();
        $id = $doc->getEnrichment(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $enrichment = new Enrichment($id);
    }

    /**
     * Test if corresponding identifiers are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesIdentifiers()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $isbn = new Identifier();
        $isbn->setValue('ISBN');

        $doc->addIdentifierIsbn($isbn);
        $doc->store();
        $id = $doc->getIdentifierIsbn(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $isbn = new Identifier($id);
    }

    /**
     * Test if corresponding patents are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesPatents()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $patent = new Patent();
        $patent->setCountries('Germany');
        $patent->setNumber('X0815');
        $patent->setDateGranted('2001-01-01');
        $patent->setApplication('description');

        $doc->addPatent($patent);
        $doc->store();
        $id = $doc->getPatent(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $patent = new Patent($id);
    }

    /**
     * Test if corresponding notes are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesNotes()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $note = new Note();
        $note->setMessage('A note!');

        $doc->addNote($note);
        $doc->store();
        $id = $doc->getNote(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $note = new Note($id);
    }

    /**
     * Test if corresponding subjects are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesSubjects()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $subject = new SubjectSwd();
        $subject->setValue('Schlagwort');

        $doc->addSubject($subject);
        $doc->store();
        $id = $doc->getSubject(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $subject = new Subject($id);
    }

    /**
     * Test if corresponding titles are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesTitles()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $title = new Title();
        $title->setValue('Title of a document');
        $title->setLanguage('eng');

        $doc->addTitleMain($title);
        $doc->store();
        $id = $doc->getTitleMain(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $title = new Title($id);
    }

    /**
     * Test if corresponding abstracts are removed when deleting a document.
     *
     * @return void
     */
    public function testDeleteDocumentCascadesAbstracts()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $abstract = new Title();
        $abstract->setValue('It is necessary to give an abstract.');
        $abstract->setLanguage('eng');

        $doc->addTitleAbstract($abstract);
        $doc->store();
        $id = $doc->getTitleAbstract(0)->getId();
        $doc->deletePermanent();
        $this->setExpectedException('Opus\Model\NotFoundException');
        $abstract = new Title($id);
    }

    /**
     * Test if a set of documents can be retrieved by getAll().
     *
     * @return void
     */
    public function testRetrieveAllDocuments()
    {
        $max_docs = 5;
        for ($i = 0; $i < $max_docs; $i++) {
            $doc = new Document();
            $doc->setType("doctoral_thesis");
            $doc->store();
        }

        $result = Document::getAll();
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
    public function testAddLinkModel()
    {
        $document = new Document();
        $document->setType("doctoral_thesis");

        $licence = new Licence();
        $document->addLicence($licence);

        $licence = $document->getField('Licence')->getValue();
        $this->assertTrue($licence[0] instanceof AbstractLinkModel, 'Adding to a field containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof AbstractLinkModel, 'Adding to a field containing a link model failed (getLicence).');
    }

    /**
     * Test if setting a model's field that is defined as a link sets the
     * field value to the corresponding dependent link model.
     *
     * TODO: This test should be moved to AbstractTest.
     *
     * @return void
     */
    public function testSetLinkModel()
    {
        $document = new Document();
        $document->setType("doctoral_thesis");

        $licence = new Licence;
        $document->setLicence($licence);

        $licence = $document->getField('Licence')->getValue();
        $this->assertTrue($licence[0] instanceof AbstractLinkModel, 'Setting a field containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof AbstractLinkModel, 'Setting a field containing a link model failed (getLicence).');
    }

    /**
     * Test if getting a model's field value  that is defined as a link sets the
     * field value to the corresponding dependent link model.
     *
     * TODO: This test should be moved to AbstractTest.
     *
     * @return void
     */
    public function testGetLinkModel()
    {
        $document = new Document();
        $document->setType("doctoral_thesis");

        $licence = new Licence;
        $document->setLicence($licence);

        $licence = $document->getField('Licence')->getValue();
        $this->assertTrue($licence[0] instanceof AbstractLinkModel, 'Getting a field value containing a link model failed (getField).');

        $licence = $document->getLicence();
        $this->assertTrue($licence[0] instanceof AbstractLinkModel, 'Getting a field value containing a link model failed (getLicence).');
    }

    /**
     * Test if title informations delivered back properly with toArray().
     *
     * @return void
     */
    public function testToArrayReturnsCorrectValuesForTitleMain()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $title = $doc->addTitleMain();
        $title->setLanguage('de');
        $title->setValue('Ein deutscher Titel');
        $id = $doc->store();

        $loaded_document = new Document($id);
        $iterim_result = $loaded_document->toArray();
        $result = $iterim_result['TitleMain'][0];
        $expected = [
            'Language' => 'de',
            'Value' => 'Ein deutscher Titel',
            'Type' => 'main'
//            'SortOrder' => null
        ];
        $this->assertEquals($expected, $result, 'toArray() deliver not expected title data.');
    }

    /**
     * Test if multiple languages are (re)stored properly.
     *
     * @return void
     *
     * TODO analyse usage of addLanguage function
     *
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Cannot add multiple values to Language
     */
    public function testMultipleLanguageStorage()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $doc->addLanguage('de');
        $doc->addLanguage('en');
    }

    /**
     * Test storing of a urn.
     *
     * @return void
     */
    public function testStoringOfOneIdentifierUrn()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");
        $doc->addIdentifierUrn(new Identifier());
        $id = $doc->store();

        $doc2 = new Document($id);

        $this->assertNotNull($doc2->getIdentifierUrn(0));
        $urn_value = $doc2->getIdentifierUrn(0)->getValue();

        $urn = new Urn('nbn', 'de:kobv:test-opus');
        $this->assertEquals($urn->getUrn($id), $urn_value, 'Stored and expected URN value did not match.');
    }

    /**
     * Test saving of empty multiple urn fields.
     *
     * @return void
     */
    public function testStoringOfMultipleIdentifierUrnField()
    {
        $doc = new Document();
        $doc->addIdentifierUrn(new Identifier());
        $doc->addIdentifierUrn(new Identifier());
        $doc->setType("doctoral_thesis");

        $this->assertCount(2, $doc->getIdentifier());

        $id = $doc->store();
        $doc2 = new Document($id);

        $urn_value = $doc2->getIdentifierUrn(0)->getValue();

        $urn = new Urn('nbn', 'de:kobv:test-opus');
        $this->assertEquals($urn->getUrn($id), $urn_value, 'Stored and expected URN value did not match.');
        $this->assertCount(1, $doc2->getIdentifier());
        $this->assertEquals(
            1,
            count($doc2->getIdentifierUrn()),
            'On an empty multiple field only one URN value should be stored.'
        );
    }

    /**
     * Ensure that existing urn values not overriden.
     *
     * @return void
     */
    public function testNotOverrideExistingUrn()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $urn_value = 'urn:nbn:de:swb:14-opus-5548';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value);

        $id = $doc->store();
        $doc2 = new Document($id);

        $this->assertEquals($urn_value, $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test storing document with empty identifier urn model create a urn.
     *
     * @return void
     */
    public function testStoreUrnWithEmptyModel()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $urn_model = new Identifier();
        $doc->setIdentifierUrn($urn_model);
        $id = $doc->store();

        $doc2 = new Document($id);
        $this->assertNotNull($doc2->getIdentifierUrn(0)->getValue(), 'URN value should not be empty.');

        $urn = new Urn('nbn', 'de:kobv:test-opus');
        $this->assertEquals($urn->getUrn($id), $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test if multiple existing URN values does not overriden.
     *
     * @return void
     */
    public function testNotOverrideExistingMultipleUrn()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $urn_value_1 = 'urn:nbn:de:swb:14-opus-5548';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_1);

        $urn_value_2 = 'urn:nbn:de:swb:14-opus-5598';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_2);
        $id = $doc->store();
        $doc2 = new Document($id);

        $this->assertEquals($urn_value_1, $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
        $this->assertEquals($urn_value_2, $doc2->getIdentifierUrn(1)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test if at least one value inside a multiple urn values does not create a new urn.
     *
     * @return void
     */
    public function testNotOverridePartialExistingMultipleUrn()
    {
        $doc = new Document();
        $doc->setType("doctoral_thesis");

        $urn_value_1 = 'urn:nbn:de:swb:14-opus-5548';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_1);

        $urn_value_2 = 'urn:nbn:de:swb:14-opus-2345';
        $urn_model = $doc->addIdentifierUrn();
        $urn_model->setValue($urn_value_2);
        $id = $doc->store();
        $doc2 = new Document($id);

        $this->assertEquals($urn_value_1, $doc2->getIdentifierUrn(0)->getValue(), 'Stored and expected URN value did not match.');
        $this->assertEquals($urn_value_2, $doc2->getIdentifierUrn(1)->getValue(), 'Stored and expected URN value did not match.');
    }

    /**
     * Test if after creation of a document leaves the fields marked unmodified.
     *
     * @return void
     */
    public function testNewlyCreatedDocumentsHaveNoModifiedFields()
    {
        $newdoc = new Document();

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
    public function testGetByServerStateReturnsCorrectDocuments()
    {
        $publishedDoc1 = new Document();
        $publishedDoc1->setType("doctoral_thesis")
            ->setServerState('published')
            ->store();

        $publishedDoc2 = new Document();
        $publishedDoc2->setType("doctoral_thesis")
            ->setServerState('published')
            ->store();

        $unpublishedDoc1 = new Document();
        $unpublishedDoc1->setType("doctoral_thesis")
            ->setServerState('unpublished')
            ->store();

        $unpublishedDoc2 = new Document();
        $unpublishedDoc2->setType("doctoral_thesis")
            ->setServerState('unpublished')
            ->store();

        $deletedDoc1 = new Document();
        $deletedDoc1->setType("doctoral_thesis")
            ->setServerState('deleted')
            ->store();

        $deletedDoc2 = new Document();
        $deletedDoc2->setType("doctoral_thesis")
            ->setServerState('deleted')
            ->store();

        $publishedDocs = Document::getAllByState('published');
        $unpublishedDocs = Document::getAllByState('unpublished');
        $deletedDocs = Document::getAllByState('deleted');

        $this->assertEquals(2, count($publishedDocs));
        $this->assertEquals(2, count($unpublishedDocs));
        $this->assertEquals(2, count($deletedDocs));
    }

    /**
     * Test setting and getting date values on different ways and fields.
     *
     * @return void
     */
    public function testSettingAndGettingDateValues()
    {
        $locale = new \Zend_Locale('de_DE');
        $doc = new Document();

        $doc->setPublishedDate('2008-10-05');

        $personAuthor = new Person();
        $personAuthor->setFirstName('Real');
        $personAuthor->setLastName('Tester');
        $personAuthor->setDateOfBirth('1965-06-23');
        $doc->addPersonAuthor($personAuthor);

        $patent = new Patent();
        $patent->setNumber('08 15');
        $patent->setDateGranted('2008-07-07');
        $patent->setCountries('Germany');
        $patent->setApplication('description');
        $doc->addPatent($patent);

        $docId = $doc->store();

        $doc = new Document($docId);
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
    public function testCheckIfDefaultServerStateValueIsSetCorrectAfterStoringModel()
    {
        $doc = new Document();
        $doc->store();

        $this->assertEquals('unpublished', $doc->getServerState(), 'ServerState should be unpublished if not set and document is stored.');
    }

    /**
     * Test for Issue in Opus\Model\Xml\Version1.  The field ServerDatePublished
     * disappeared from the XML-DOM-Tree after storing.
     */
    public function testExistenceOfServerDatePublished()
    {
        $doc = new Document();
        $doc->setServerState('published');
        $doc->setServerDatePublished('2011-11-11T11:11:11+01:00');
        $doc->store();

        $filter = new Filter();
        $filter->setModel($doc);

        $docXml = $doc->toXml([], new Version1());
        $serverDatePublElements = $docXml->getElementsByTagName("ServerDatePublished");
        $this->assertEquals(1, count($serverDatePublElements), 'document xml should contain one field "ServerDatePublished"');
        $this->assertTrue($serverDatePublElements->item(0)->hasAttributes(), 'document xml field "ServerDatePublished" should have attributes');

        $modelXml = $filter->toXml([], new Version1());
        $serverDatePublElements = $modelXml->getElementsByTagName("ServerDatePublished");
        $this->assertEquals(1, count($serverDatePublElements), 'model xml should contain one field "ServerDatePublished"');
        $this->assertTrue($serverDatePublElements->item(0)->hasAttributes(), 'model xml field "ServerDatePublished" should have attributes');
    }

    /**
     * Tests initialization of ServerDate-Fields.
     *
     * @return void
     */
    public function testInitializationOfServerDateFields()
    {
        $d = new Document();
        $id = $d->store();

        $d = new Document($id);
        $this->assertNotNull($d->getServerDateCreated(), 'ServerDateCreated should *not* be NULL');
        $this->assertNotNull($d->getServerDateModified(), 'ServerDateModified should *not* be NULL');
        $this->assertNull($d->getServerDatePublished(), 'ServerDatePublished *should* be NULL');
    }

    /**
     * Tests initialization of ServerDatePublished field.
     *
     * @return void
     */
    public function testSetServerDatePublished()
    {
        $d = new Document();
        $d->setServerState('published');
        $id = $d->store();

        $this->assertNotNull($d->getServerDatePublished());
    }

    /**
     * Tests initialization of ServerDatePublished field.
     *
     * @return void
     */
    public function testDontChangeUserSpecifiedServerDatePublished()
    {
        $examplePublishedDate = new Date('2010-05-09T18:20:17+02:00');

        $d = new Document();
        $d->setServerDatePublished($examplePublishedDate);
        $d->setServerState('published');
        $id = $d->store();

        $this->assertEquals(
            $examplePublishedDate->__toString(),
            $d->getServerDatePublished()->__toString(),
            "Don't change user-specified server_date_published"
        );

        $testStates = ['unpublished', 'published', 'published', 'unpublished'];
        foreach ($testStates as $state) {
            $d = new Document($id);
            $d->setServerState($state);
            $d->store();

            $d = new Document($id);
            $this->assertNotNull($d->getServerDatePublished());
            $this->assertEquals(
                $examplePublishedDate->__toString(),
                $d->getServerDatePublished()->__toString(),
                "Don't change user-specified server_date_published (state $state)"
            );
        }
    }

    /**
     * Tests initialization of ServerDatePublished field.
     *
     * @return void
     */
    public function testSetServerDatePublishedOnlyAfterPublish()
    {
        $d = new Document();
        $d->setServerState('unpublished');
        $id = $d->store();

        $this->assertNull($d->getServerDatePublished(), 'published date should be NULL after store()');

        $d = new Document($id);
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
    public function testInitializationOfServerDateFieldsOverride()
    {
        $exampleCreateDate = '2010-05-11T18:20:17+02:00';
        $examplePublishedDate = '2010-05-09T18:20:17+02:00';

        $d = new Document();
        $d->setServerDateCreated($exampleCreateDate);
        $d->setServerDatePublished($examplePublishedDate);
        $id = $d->store();

        $d = new Document($id);
        $this->assertEquals($exampleCreateDate, $d->getServerDateCreated()->__toString());
        $this->assertNotNull($d->getServerDatePublished());
        $this->assertEquals($examplePublishedDate, $d->getServerDatePublished()->__toString());
    }

    /**
     * Test for storing collections
     */
    public function testStoreDocumentWithCollectionsTest()
    {
        $role = new CollectionRole();
        $role->setName('foobar-' . rand());
        $role->setOaiName('foobar-oai-' . rand());
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $collection1 = $root->addFirstChild();
        $root->store();

        $collection2 = $root->addLastChild();
        $root->store();

        $document = new Document();
        $document->setType('test');
        $document->addCollection($collection1);
        $document->addCollection($collection2);

        $document->store();
        $this->assertEquals(2, count($document->getCollection()), 'After storing: document should have 2 collections.');

        $document = new Document($document->getId());
        $this->assertEquals(2, count($document->getCollection()), 'After storing: document should have 2 collections.');
    }

    /**
     * Test for storing collections, adding same collection twice.
     */
    public function testStoreDocumentWithDuplicateCollectionsTest()
    {
        $role = new CollectionRole();
        $role->setName('foobar-' . rand());
        $role->setOaiName('foobar-oai-' . rand());
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $collection1 = $root->addFirstChild();
        $root->store();

        $document = new Document();
        $document->setType('test');
        $document->addCollection($collection1);
        $document->addCollection($collection1);
        $document->store();

        $document = new Document($document->getId());
        $this->assertEquals(1, count($document->getCollection()), 'After storing: document should have 1 collections.');
    }

    /**
     * Test for storing collections, check that collection still exists after
     * second store.
     */
    public function testStoreDocumentDoesNotDeleteCollectionTest()
    {
        $role = new CollectionRole();
        $role->setName('foobar-' . rand());
        $role->setOaiName('foobar-oai-' . rand());

        $root = $role->addRootCollection();
        $collection = $root->addFirstChild();
        $role->store();

        $document = new Document();
        $document->setType('test');
        $document->addCollection($collection);
        $docId = $document->store();

        // Check if we created what we're expecting later.
        $document = new Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After storing: document should have 1 collection.');

        // Storing
        $document = new Document($docId);
        $document->store();

        $document = new Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After 2nd store(): document should still have 1 collection.');

        // Storing
        $document = new Document($docId);
        $document->setType('test');
        $document->store();

        $document = new Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After 3rd store(): document should still have 1 collection.');

        // Storing
        $document = new Document($docId);
        $c = $document->getCollection();
        $document->store();

        $document = new Document($docId);
        $this->assertEquals(1, count($document->getCollection()), 'After 4th store(): document should still have 1 collection.');
    }

    public function testGetAllDocumentsByAuthorsReturnsDocumentsWithoutAuthor()
    {
        $d = new Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Document::getAllDocumentsByAuthors();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Document::getAllDocumentsByAuthorsByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Document::getAllDocumentsByAuthorsByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Document::getAllDocumentsByAuthorsByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    public function testGetAllDocumentsByTitleReturnsDocumentsWithoutTitle()
    {
        $d = new Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Document::getAllDocumentsByTitles();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Document::getAllDocumentsByTitlesByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Document::getAllDocumentsByTitlesByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Document::getAllDocumentsByTitlesByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    public function testGetAllDocumentsByDoctype()
    {
        $d = new Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Document::getAllDocumentsByDoctype();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Document::getAllDocumentsByDoctypeByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Document::getAllDocumentsByDoctypeByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Document::getAllDocumentsByDoctypeByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    public function testGetAllDocumentsByPubDate()
    {
        $d = new Document();
        $d->setServerState('published');
        $published_id = $d->store();

        $d = new Document();
        $d->setServerState('unpublished');
        $unpublished_id = $d->store();

        $docs = Document::getAllDocumentsByPubDate();
        $this->assertContains($published_id, $docs, 'all should contain "published"');
        $this->assertContains($unpublished_id, $docs, 'all should contain "unpublished"');

        $docs = Document::getAllDocumentsByPubDateByState('published');
        $this->assertContains($published_id, $docs, 'published list should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list should not contain unpublished');

        $docs = Document::getAllDocumentsByPubDateByState('published', 0);
        $this->assertContains($published_id, $docs, 'published list (sorted, 0) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 0) should not contain unpublished');

        $docs = Document::getAllDocumentsByPubDateByState('published', 1);
        $this->assertContains($published_id, $docs, 'published list (sorted, 1) should contain published');
        $this->assertNotContains($unpublished_id, $docs, 'published list (sorted, 1) should not contain unpublished');
    }

    /**
     * We had a problem, that we were caching the xml document of a newly
     * created document, which had incomplete File entries.
     */
    public function testDocumentCacheContainsFileWithOutdatedData()
    {
        $config = \Zend_Registry::get('Zend_Config');
        $filename = $config->workspacePath;
        touch($filename);

        $doc = new Document();
        $doc->setType('test');
        $doc->setServerState('published');
        $file = $doc->addFile();
        $file->setPathName($filename);
        $doc->store();

        $doc = new Document($doc->getId());
        $file = $doc->getFile(0);

        $this->assertEquals('1', $file->getVisibleInFrontdoor());
        $this->assertEquals('1', $file->getVisibleInOai());

        $cache = new Cache();
        $xmlVersion1 = new Version1();

        $xmlModel = new Xml();
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

    public function testAddDnbInstitute()
    {
        $dnb_institute = new DnbInstitute();
        $dnb_institute->setName('Forschungsinstitut für Code Coverage')
            ->setAddress('Musterstr. 23 - 12345 Entenhausen - Calisota')
            ->setCity('Calisota')
            ->setPhone('+1 234 56789')
            ->setDnbContactId('F1111-1111')
            ->setIsGrantor('1');
        // store
        $id = $dnb_institute->store();

        $document = new Document();
        $document->store();

        $document->addThesisGrantor($dnb_institute);
        $docId = $document->store();

        $document = new Document($docId);
        $this->assertEquals(1, count($document->getThesisGrantor()));
    }

    public function testSetDnbInstitute()
    {
        $dnb_institute = new DnbInstitute();
        $dnb_institute->setName('Forschungsinstitut für Code Coverage')
            ->setAddress('Musterstr. 23 - 12345 Entenhausen - Calisota')
            ->setCity('Calisota')
            ->setPhone('+1 234 56789')
            ->setDnbContactId('F1111-1111')
            ->setIsGrantor('1');
        // store
        $id = $dnb_institute->store();

        $document = new Document();
        $document->store();

        $document->setThesisGrantor($dnb_institute);
        $docId = $document->store();

        $document = new Document($docId);
        $this->assertEquals(1, count($document->getThesisGrantor()));
    }

    /**
     * Regression test for OPUSVIER-2205.
     */
    public function testStoringPageFieldsAsAlnumStrings()
    {
        $document = new Document();
        $document->setPageFirst('III');
        $document->setPageLast('IV');
        $document->setPageNumber('II');
        $document->setArticleNumber('X');

        $document->store();

        $docId = $document->getId();

        $document = new Document($docId);

        $this->assertEquals('III', $document->getPageFirst());
        $this->assertEquals('IV', $document->getPageLast());
        $this->assertEquals('II', $document->getPageNumber());
        $this->assertEquals('X', $document->getArticleNumber());
    }

    public function testSortOrderForAddPersonAuthors()
    {
        $document = $this->_createDocumentWithPersonAuthors(16);
        $docId = $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // First check, if everybody is in place.
        $authors = $document->getPersonAuthor();
        for ($i = 0; $i < count($authors); $i++) {
            $this->assertEquals('firstname-$i=' . $i, $authors[$i]->getFirstName());
            $this->assertEquals('lastname-$i=' . $i, $authors[$i]->getLastName());
        }
    }

    public function testSortOrderForSetPersonAuthorReverse()
    {
        $document = $this->_createDocumentWithPersonAuthors(16);
        $docId = $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // Do something with authors: reverse
        $authors = $document->getPersonAuthor();
        $new_authors = array_reverse($authors);

        $index = 1;

        foreach ($authors as $author) {
            $this->assertEquals($index, $author->getSortOrder());
            $index++;
        }

        $document->setPersonAuthor($new_authors);
        $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Document($docId);
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

    public function testSortOrderForSetPersonAuthorShuffleDeleteAdd()
    {
        $document = $this->_createDocumentWithPersonAuthors(16);
        $docId = $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Document($docId);
        $this->_checkPersonAuthorSortOrderForDocument($document);

        // Do something with authors: shuffle, remove some, add one...
        $authors = $document->getPersonAuthor();
        $new_authors = $authors;

        shuffle($new_authors);
        array_pop($new_authors);
        array_shift($new_authors);

        $new_authors[] = $document->addPersonAuthor(new Person)
            ->setFirstName("new")
            ->setLastName("new");

        $document->setPersonAuthor($new_authors);
        $document->store();

        // Reload document; sanity check of SortOrder...
        $document = new Document($docId);
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

    private function _createDocumentWithPersonAuthors($author_count)
    {
        $document = new Document();
        for ($i = 0; $i < $author_count; $i++) {
            $person = new Person();
            $person->setFirstName('firstname-$i=' . $i);
            $person->setLastName('lastname-$i=' . $i);

            $document->addPersonAuthor($person);
        }
        return $document;
    }

    private function _checkPersonAuthorSortOrderForDocument($document)
    {
        $authors = $document->getPersonAuthor();
        $numbers = [];
        foreach ($authors as $author) {
            $this->assertNotNull($author->getSortOrder());
            $numbers[] = $author->getSortOrder();
        }

        // Check if all numbers are unique
        $unique_numbers = array_unique($numbers);
        $this->assertEquals(count($authors), count($unique_numbers));
    }

    public function testGetEarliestPublicationDate()
    {
        $nullDate = Document::getEarliestPublicationDate();
        $this->assertNull($nullDate, "Expected NULL on empty database.");

        // Insert valid entry through framework.
        $document = new Document();
        $document->setServerDatePublished('2011-06-01T00:00:00Z');
        $document->store();
        $validDate = Document::getEarliestPublicationDate();
        $this->assertEquals('2011-06-01', $validDate);

        // Insert invalid entry into database...
        $table = TableGateway::getInstance('Opus\Db\Documents');
        $table->insert(['server_date_published' => '1234', 'server_date_created' => '1234']);
        $invalidDate = Document::getEarliestPublicationDate();
        $this->assertNull($invalidDate, "Expected NULL on invalid date.");
    }

    public function testGetDefaultsForPublicationState()
    {
        $doc = new Document();

        $values = $doc->getField('PublicationState')->getDefault();

        $this->assertEquals(5, count($values));
        $this->assertContains('draft', $values);
    }

    /**
     * Regression test for OPUSVIER-2111
     * @expectedException \Opus\Model\DbException
     * @expectedExceptionMessage truncated
     */
    public function testTruncateExceptionIsThrownFor26Chars()
    {
        $d = new Document();
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
     * @expectedException \Opus\Model\DbException
     * @expectedExceptionMessage truncated
     */
    public function testTruncateExceptionIsThrownFor256Chars()
    {
        $d = new Document();
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
    public function testTruncateExceptionIsNotThrown()
    {
        $d = new Document();
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
    public function testStoringTwiceWithSeriesModications()
    {
        $doc = new Document();

        $series = new Series();
        $series->setTitle('testseries');
        $series->store();

        $slink = $doc->addSeries($series);
        $slink->setNumber(50);

        $doc->store();

        $doc = new Document($doc->getId());

        $assignedSeries = $doc->getSeries();

        $this->assertEquals(1, count($assignedSeries));
        $this->assertEquals(50, $assignedSeries[0]->getNumber());

        $doc->store(); // NOTE: without this store the test was successfull

        $assignedSeries = $doc->getSeries();

        $assignedSeries[0]->setNumber(60);

        $doc->store();

        $doc = new Document($doc->getId());

        $assignedSeries = $doc->getSeries();

        $this->assertEquals(60, $assignedSeries[0]->getNumber());
    }

    /**
     * High-level regression test for OPUSVIER-2261.
     */
    public function testStoringTwiceWithPersonModications()
    {
        $doc = new Document();

        $person = new Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $doc = new Document($doc->getId());

        $persons = $doc->getPerson();

        $this->assertEquals(1, count($persons));
        $this->assertEquals('advisor', $persons[0]->getRole());

        $doc->store(); // NOTE: without this store the test was successfull

        $persons = $doc->getPerson();

        $persons[0]->setRole('author');

        $doc->store();

        $doc = new Document($doc->getId());

        $persons = $doc->getPerson();

        $this->assertEquals('author', $persons[0]->getRole());
    }

    public function testChangingRoleOfPerson()
    {
        $this->markTestIncomplete('Knallt. Soll das so sein? Was ist falsch?');
        $doc = new Document();

        $person = new Person();
        $person->setLastName('Testy');
        $person->store(); // notwendig?

        $doc->setPersonAuthor([$person]);

        $doc = new Document($doc->store());

        $this->assertEquals(1, count($doc->getPerson()));
        $this->assertEquals(1, count($doc->getPersonAuthor()));

        $persons = $doc->getPersonAuthor();
        $person = $persons[0];

        $person->setRole('submitter');

        $doc->setPersonAuthor([]);
        $doc->setPersonSubmitter([$person]);

        $doc = new Document($doc->store());

        $this->assertEquals(1, count($doc->getPerson()));
        $this->assertEquals(1, count($doc->getPersonSubmitter()));
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetPersonZero()
    {
        $doc = new Document();
        $doc->store();

        $doc = new Document($doc->getId());
        $this->assertFalse($doc->isModified(), 'doc should not be modified');

        $this->assertTrue(count($doc->getPerson()) == 0, 'testcase changed?');
        $this->assertFalse($doc->isModified(), 'doc should not be modified after getField(Person)!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetFieldPersonZero()
    {
        $doc = new Document();
        $doc->store();

        $doc = new Document($doc->getId());
        $this->assertFalse($doc->isModified(), 'doc should not be modified');

        $this->assertFalse($doc->getField('Person')->isModified(), 'Field Person should not be modified');
        $this->assertFalse($doc->isModified(), 'doc should not be modified after getField(Person)!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetPersonOne()
    {
        $doc = new Document();

        $person = new Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $doc = new Document($doc->getId());
        $this->assertFalse($doc->isModified(), 'doc should not be modified');

        $person = $doc->getPerson(0);

        $this->assertFalse($person->getModel()->isModified());
        $this->assertFalse($person->isModified());
        $this->assertFalse($doc->isModified());

        $persons = $doc->getPerson();
        $this->assertCount(1, $persons, 'testcase changed?');

        $this->assertFalse($persons[0]->getModel()->isModified(), 'linked model has just been loaded and is not modified!');

        $this->assertFalse($persons[0]->isModified(), 'link model has just been loaded and should not be modified!');

        $this->assertFalse($doc->isModified(), 'doc should not be modified after getPerson!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testDocumentIsNotModifiedAfterGetFieldPersonOne()
    {
        $doc = new Document();

        $person = new Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $doc = new Document($doc->getId());
        $this->assertFalse($doc->isModified(), 'doc should not be modified');

        $this->assertFalse($doc->getField('Person')->isModified(), 'Field Person should not be modified');

        $this->assertFalse($doc->isModified(), 'doc should not be modified after getField(Person)!');
    }

    /**
     * Regression test for OPUSVIER-2307: Test for modification tracking bug.
     */
    public function testPlinkIsModifiedAfterFixingSortOrder()
    {
        $doc = new Document();

        $person = new Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->store();

        $plink = $doc->addPerson($person);
        $plink->setRole('advisor');

        $doc->store();

        $plink->getField('SortOrder')
            ->setValue(123)
            ->clearModified();

        $this->assertFalse($plink->getField('SortOrder')->isModified(), 'plink->SortOrder should not be modified');

        $newField = new Field('test');
        $newField->setSortFieldName('SortOrder');
        $newField->setValue([$plink]);

        $this->assertTrue($plink->isModified());
        $this->assertTrue($plink->getField('SortOrder')->isModified(), 'plink->SortOrder should be modified');
    }

    public function testChangeTitleType()
    {
        $this->markTestSkipped('Does not work (see OPUSVIER-2318).');
        $doc = new Document();

        $titleParent = new Title();
        $titleParent->setLanguage('deu');
        $titleParent->setValue('Title Parent');

        $doc->addTitleParent($titleParent);
        $doc->store();

        $doc = new Document($doc->getId());

        $this->assertEquals(0, count($doc->getTitleMain()));
        $this->assertEquals(1, count($doc->getTitleParent()));

        $titleParent = $doc->getTitleParent();
        $titleParent[0]->setType('main');

        $doc->store();

        $doc = new Document($doc->getId());

        $this->assertEquals(1, count($doc->getTitleMain()), 'Should have 1 TitleMain.');
        $this->assertEquals(0, count($doc->getTitleParent()), 'Should have 0 TitleParent.');
    }

    public function testChangeTitleTypeAlternateWay()
    {
        $doc = new Document();

        $titleParent = new Title();
        $titleParent->setLanguage('deu');
        $titleParent->setValue('Title Parent');

        $doc->addTitleParent($titleParent);
        $doc->store();

        $doc = new Document($doc->getId());

        $this->assertEquals(0, count($doc->getTitleMain()));
        $this->assertEquals(1, count($doc->getTitleParent()));

        // remove title
        $titleParent = $doc->getTitleParent();
        $title = $titleParent[0];
        $movedTitle = new Title();
        $movedTitle->setLanguage($title->getLanguage());
        $movedTitle->setValue($title->getValue());
        unset($titleParent[0]);
        $doc->setTitleParent($titleParent);
        $doc->store();

        // add title
        $doc = new Document($doc->getId());
        $doc->addTitleMain($movedTitle);
        $doc->store();

        $doc = new Document($doc->getId());

        $this->assertEquals(1, count($doc->getTitleMain()), 'Should have 1 TitleMain.');
        $this->assertEquals(0, count($doc->getTitleParent()), 'Should have 0 TitleParent.');
    }

    public function testRegression2916StoreModifiesServerDataModifiedForOtherDocs()
    {
        $doc1 = new Document();
        $doc1Id = $doc1->store();
        $doc1ServerDateModified = $doc1->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $doc2 = new Document();
        $title = new Title();
        $title->setLanguage('eng');
        $title->setValue('Test Titel');
        $doc2->addTitleMain($title);
        $doc2->store();

        $doc1 = new Document($doc1Id);

        $this->assertEquals($doc1ServerDateModified, $doc1->getServerDateModified()->getUnixTimestamp(), 'ServerDateModified was modified by store on a differnet document.');
    }

    public function testRegression2982StoreWithInstituteModifiesServerDateModifiedForOtherDocs()
    {
        $institute = new DnbInstitute();
        $institute->setName('Test Institut');
        $institute->setCity('Berlin');
        $institute->setIsGrantor(true);
        $institute->setIsPublisher(true);
        $instituteId = $institute->store();

        $doc1 = new Document();
        $institute = new DnbInstitute($instituteId);
        $doc1->setThesisGrantor([$institute]);
        $doc1id = $doc1->store();
        $doc1ServerDateModified = $doc1->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $doc2 = new Document();
        $institute = new DnbInstitute($instituteId);
        $doc2->setThesisGrantor([$institute]);
        $doc2->store();

        $doc1 = new Document($doc1id);

        $this->assertEquals($doc1ServerDateModified, $doc1->getServerDateModified()->getUnixTimestamp(), 'ServerDateModified was modified by store on a differnet document.');
    }

    public function testHasPlugins()
    {
        $doc = new Document();
        $this->assertTrue($doc->hasPlugin('Opus\Document\Plugin\XmlCache'), 'Opus\Document\Plugin\XmlCache is not registered');
        $this->assertTrue($doc->hasPlugin('Opus\Document\Plugin\IdentifierUrn'), 'Opus\Document\Plugin\IdentifierUrn is registered');
        $this->assertTrue($doc->hasPlugin('Opus\Document\Plugin\IdentifierDoi'), 'Opus\Document\Plugin\IdentifierDoi is registered');
        $this->assertFalse($doc->hasPlugin('Opus\Document\Plugin\SequenceNumber'), 'Opus\Document\Plugin\SequenceNumber is registered');
    }

    /**
     * Regression Test for OPUSVIER-3203
     */
    public function testDeleteFields()
    {

        $title = new Title();
        $title->setValue('Blah Blah');
        $title->setLanguage('deu');

        $doc = new Document();
        $doc->setTitleMain($title);
        $docid = $doc->store();

        $redoc = new Document($docid);
        $redoc->deleteFields(['TitleMain']);
        $redoc->store();

        $retitle = new Title();
        $retitle->setValue('Blah Blah Blah');
        $retitle->setLanguage('deu');

        $redoc->setTitleMain($retitle);

        try {
            $redoc->store();
        } catch (ModelException $ome) {
            $this->fail($ome->getMessage());
        }
    }

    public function testUpdateServerDateModifiedAfterDeleteFields()
    {
        $doc = new Document();
        $doc->setEdition('Test Edition');
        $docId = $doc->store();
        $docServerDateModified = $doc->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $doc = new Document($docId);
        $doc->deleteFields(['Edition']);
        $doc->store();

        $doc = new Document($docId);

        $this->assertNotEquals(
            $docServerDateModified,
            $doc->getServerDateModified()->getUnixTimestamp(),
            'ServerDateModified was not modified by deleteFields.'
        );
    }

    /**
     * The results should be sorted ascending according to their sort order.
     */
    public function testGetFileSortOrder()
    {
        $config = \Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();
        touch($path);

        $doc = new Document();
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

        $doc = new Document($docId);
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
    public function testFileSortOrderThroughFieldModel()
    {
        $this->markTestSkipped('TODO noch nicht gefixt, aber langfristig evtl. auch nicht notwendig');

        $config = \Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();
        touch($path);

        $doc = new Document();
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

        $doc = new Document($docId);
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
    public function testGetFileSortingWithEqualSortOrder()
    {
        $config = \Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();
        touch($path);

        $doc = new Document();
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

        $doc = new Document($docId);
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
    public function testHasEmbargoDatePassedFalse()
    {
        $doc = new Document();
        $doc->setEmbargoDate('2100-10-13');

        $now = new Date('2014-06-18');
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $this->assertFalse($doc->hasEmbargoPassed(), 'OPUS has been developed for way too long. :-)');
    }

    public function testHasEmbargoDatePassedTrue()
    {
        $doc = new Document();
        $doc->setEmbargoDate('2000-10-12');
        $this->assertTrue($doc->hasEmbargoPassed());

        $now = new Date('2000-11-10');
        $this->assertTrue($doc->hasEmbargoPassed($now));
    }

    public function testHasEmbargoDatePassedSameDay()
    {
        $now = new Date('2014-06-18');

        $doc = new Document();
        $doc->setEmbargoDate('2014-06-18');
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $now = new Date("2014-06-18T12:00:00Z");
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $now = new Date("2014-06-18T23:59:59Z");
        $this->assertFalse($doc->hasEmbargoPassed($now));

        $now = new Date("2014-06-19");
        $this->assertTrue($doc->hasEmbargoPassed($now));
    }

    public function testIsNewRecord()
    {
        $doc = new Document();

        $this->assertTrue($doc->isNewRecord());

        $doc->store();

        $this->assertFalse($doc->isNewRecord());
    }

    public function testSetServerDateModifiedByIds()
    {
        $doc = new Document();
        $doc1Id = $doc->store();

        $doc = new Document();
        $doc2Id = $doc->store();

        $doc = new Document();
        $doc3Id = $doc->store();

        $date = new Date('2016-05-10');

        Document::setServerDateModifiedByIds($date, [1, 3]);

        $doc = new Document($doc1Id);
        $this->assertEquals('2016-05-10', $doc->getServerDateModified());

        $doc = new Document($doc2Id);
        $this->assertNotEquals('2016-05-10', $doc->getServerDateModified());

        $doc = new Document($doc3Id);
        $this->assertEquals('2016-05-10', $doc->getServerDateModified());
    }

    /**
     * @expectedException \Opus\Model\DbException
     * @expectedExceptionMessage truncated
     */
    public function testSetServerStateInvalidValue()
    {
        $doc = new Document();
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

        $doc = new Document();
        $doc->setServerState('published');
        $doc->store();

        // check indexing operations
    }

    protected function setupDocumentWithMultipleTitles()
    {
        $doc = new Document();
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

        $doc = new Document($docId);

        $this->assertCount(3, $doc->getTitleMain());

        $title = $doc->getMainTitle();

        $this->assertInstanceOf('Opus\Title', $title);
        $this->assertEquals('Deutsch', $title->getValue());
        $this->assertEquals('deu', $title->getLanguage());
    }

    public function testGetMainTitleForLanguage()
    {
        $docId = $this->setupDocumentWithMultipleTitles();

        $doc = new Document($docId);

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

        $doc = new Document($docId);

        $title = $doc->getMainTitle('rus');

        // should return title in document language
        $this->assertEquals('Deutsch', $title->getValue());
        $this->assertEquals('deu', $title->getLanguage());
    }

    public function testGetMainTitleWithNoDocumentLanguage()
    {
        $docId = $this->setupDocumentWithMultipleTitles();

        $doc = new Document($docId);

        $doc->setLanguage(null);
        $doc->store();

        $doc = new Document($docId);

        $this->assertNull($doc->getLanguage());

        $title = $doc->getMainTitle();

        // should return first title
        $this->assertEquals('French', $title->getValue());
        $this->assertEquals('fre', $title->getLanguage());
    }

    public function testGetMainTitleForNoTitles()
    {
        $doc = new Document();
        $doc->setLanguage('deu');
        $docId = $doc->store();

        $doc = new Document($docId);

        $title = $doc->getMainTitle();

        $this->assertNull($title);
    }

    public function testHasFulltext()
    {
        $doc = new Document();

        $config = \Zend_Registry::get('Zend_Config');
        $tempFile = $config->workspacePath . '/tmp/' . uniqid();

        touch($tempFile);

        $file = $doc->addFile();
        $file->setPathName('test.txt');
        $file->setMimeType('text/plain');
        $file->setTempFile($tempFile);

        $docId = $doc->store();

        $doc = new Document($docId);

        $files = $doc->getFile();

        $this->assertTrue($doc->hasFulltext());

        $files[0]->setVisibleInFrontdoor(0);

        $doc = new Document($doc->store());

        $this->assertFalse($doc->hasFulltext());

        unlink($files[0]->getPath());
    }

    public function testIsOpenAccess()
    {
        $role = new CollectionRole();
        $role->setName('open_access');
        $role->setOaiName('open_access');
        $role->store();

        $root = $role->addRootCollection();

        $col = new Collection();
        $col->setName('open_access');
        $col->setOaiSubset('open_access');

        $root->addFirstChild($col);
        $role->store();

        $doc = new Document();
        $doc->setType('article');
        $doc->addCollection($col);
        $docId = $doc->store();

        $this->assertTrue($col->holdsDocumentById($docId));

        $doc = new Document($docId);

        $this->assertTrue($doc->isOpenAccess());

        $doc->setCollection(null);
        $doc->store();

        $this->assertFalse($doc->isOpenAccess());
    }

    public function testRemoveAllPersons()
    {
        // create document with one person
        $doc = new Document();
        $doc->setType('article');

        $title = new Title();
        $title->setLanguage('eng');
        $title->setValue('Test document');
        $doc->addTitleMain($title);

        $person = new Person();
        $person->setLastName('Testy');
        $doc->addPersonAuthor($person);

        $docId = $doc->store();

        // add second person
        $doc = new Document($docId);

        $persons = $doc->getPerson();

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = new Person();
        $person->setLastName('Tester2');
        $doc->addPersonReferee($person);

        $doc->store();

        $doc = new Document($docId);

        $persons = $doc->getPerson();

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(2, $persons);

        // remove all persons
        $doc->setPerson(null);
        $doc->store();

        $doc = new Document($docId);

        $persons = $doc->getPerson();

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(0, $persons);
    }

    /**
     * OPUSVIER-3860 Regression test.
     */
    public function testGetIdentifierDoiProducesDifferentResultThanGetIdentifier()
    {
        $doc = new Document();
        $doc->store();
        $id = new Identifier();
        $id->setType('doi');
        $id->setValue('someVal');
        $ids = $doc->getIdentifier();
        $ids[] = $id;
        $doc->setIdentifier($ids);

        $test1 = $doc->getIdentifier();
        $test2 = $doc->getIdentifierDoi();

        $this->assertCount(1, $test2);
        $this->assertEquals($test1, $test2);
    }

    public function testGetEnrichment()
    {
        $keyName = 'test.key1';

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName($keyName);
        $enrichmentKey->store();

        $enrichment = new Enrichment();
        $enrichment->setKeyName($keyName);
        $enrichment->setValue('test-value');

        $doc = new Document();
        $doc->setLanguage('deu');
        $doc->addEnrichment($enrichment);

        $docId = $doc->store();

        $doc = new Document($docId);

        $enrichment = $doc->getEnrichment();

        $this->assertInternalType('array', $enrichment);
        $this->assertCount(1, $enrichment);
        $this->assertEquals($keyName, $enrichment[0]->getKeyName());
        $this->assertEquals('test-value', $enrichment[0]->getValue());

        $enrichment = $doc->getEnrichment(0);

        $this->assertInstanceOf('Opus\Enrichment', $enrichment);
        $this->assertEquals($keyName, $enrichment->getKeyName());
        $this->assertEquals('test-value', $enrichment->getValue());

        $enrichment = $doc->getEnrichment($keyName);

        $this->assertInstanceOf('Opus\Enrichment', $enrichment);
        $this->assertEquals($keyName, $enrichment->getKeyName());
        $this->assertEquals('test-value', $enrichment->getValue());
    }

    public function testGetEnrichmentSingleMatch()
    {
        $keyName = "test.key1";

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName($keyName);
        $enrichmentKey->store();

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName('anotherKey');
        $enrichmentKey->store();

        $enrichment1 = new Enrichment();
        $enrichment1->setKeyName('anotherKey');
        $enrichment1->setValue('another-value');

        $enrichment = new Enrichment();
        $enrichment->setKeyName($keyName);
        $enrichment->setValue('test-value');

        $doc = new Document();
        $doc->setLanguage('deu');
        $doc->addEnrichment($enrichment1);
        $doc->addEnrichment($enrichment);

        $docId = $doc->store();

        $doc = new Document($docId);

        $enrichment = $doc->getEnrichment($keyName);

        $this->assertInstanceOf('Opus\Enrichment', $enrichment);
        $this->assertEquals($keyName, $enrichment->getKeyName());
        $this->assertEquals('test-value', $enrichment->getValue());
    }

    public function testGetEnrichmentBadKey()
    {
        $keyName = 'test.key1';

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName($keyName);
        $enrichmentKey->store();

        $enrichment = new Enrichment();
        $enrichment->setKeyName($keyName);
        $enrichment->setValue('test-value');

        $doc = new Document();
        $doc->setLanguage('deu');
        $doc->addEnrichment($enrichment);

        $docId = $doc->store();

        $doc = new Document($docId);

        $enrichment = $doc->getEnrichment('unknownkey');

        $this->assertNull($enrichment);
    }

    public function testGetEnrichmentValue()
    {
        $keyName = 'test.key1';

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName($keyName);
        $enrichmentKey->store();

        $enrichment = new Enrichment();
        $enrichment->setKeyName($keyName);
        $enrichment->setValue('test-value');

        $doc = new Document();
        $doc->setLanguage('deu');
        $doc->addEnrichment($enrichment);

        $docId = $doc->store();

        $doc = new Document($docId);

        $value = $doc->getEnrichmentValue($keyName);

        $this->assertEquals('test-value', $value);
    }

    /**
     * @expectedException \Opus\Model\ModelException
     * @expectedExceptionMessage unknown enrichment key
     */
    public function testGetEnrichmentValueBadKey()
    {
        $keyName = 'test.key1';

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName($keyName);
        $enrichmentKey->store();

        $enrichment = new Enrichment();
        $enrichment->setKeyName($keyName);
        $enrichment->setValue('test-value');

        $doc = new Document();
        $doc->setLanguage('deu');
        $doc->addEnrichment($enrichment);

        $docId = $doc->store();

        $doc = new Document($docId);

        $doc->getEnrichmentValue('unknownkey');
    }

    public function testGetEnrichmentMultiValue()
    {
        $keyName = 'test.key1';

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName($keyName);
        $enrichmentKey->store();

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName('otherkey');
        $enrichmentKey->store();

        $doc = new Document();
        $doc->setLanguage('deu');

        $enrichment = new Enrichment();
        $enrichment->setKeyName($keyName);
        $enrichment->setValue('test-value');

        $doc->addEnrichment($enrichment);

        $enrichment = new Enrichment();
        $enrichment->setKeyName($keyName);
        $enrichment->setValue('test-value-2');

        $doc->addEnrichment($enrichment);

        $enrichment = new Enrichment();
        $enrichment->setKeyName('otherkey');
        $enrichment->setValue('test-value-other');

        $doc->addEnrichment($enrichment);

        $docId = $doc->store();

        $doc = new Document($docId);

        $enrichments = $doc->getEnrichment();

        $this->assertInternalType('array', $enrichments);
        $this->assertCount(3, $enrichments);

        $this->assertEquals($keyName, $enrichments[0]->getKeyName());
        $this->assertEquals('test-value', $enrichments[0]->getValue());

        $this->assertEquals($keyName, $enrichments[1]->getKeyName());
        $this->assertEquals('test-value-2', $enrichments[1]->getValue());

        $this->assertEquals('otherkey', $enrichments[2]->getKeyName());
        $this->assertEquals('test-value-other', $enrichments[2]->getValue());

        $enrichments = $doc->getEnrichment($keyName);

        $this->assertInternalType('array', $enrichments);
        $this->assertCount(2, $enrichments);

        $this->assertEquals($keyName, $enrichments[0]->getKeyName());
        $this->assertEquals('test-value', $enrichments[0]->getValue());

        $this->assertEquals($keyName, $enrichments[1]->getKeyName());
        $this->assertEquals('test-value-2', $enrichments[1]->getValue());

        $values = $doc->getEnrichmentValue($keyName);

        $this->assertInternalType('array', $values);
        $this->assertCount(2, $values);
        $this->assertContains('test-value', $values);
        $this->assertContains('test-value-2', $values);
    }

    public function testStoreAsNew()
    {
        $this->markTestIncomplete('Storing as new document not implemented yet.');
        $doc = new Document();

        $title = $doc->addTitleMain();
        $title->setValue('Title');
        $title->setLanguage('de');

        $docId = $doc->store();

        $doc = new Document($docId);
        $titles = $doc->getTitleMain();

        $this->assertCount(1, $titles);

        $docId2 = $doc->storeAsNew();

        $doc2 = new Document($docId2);

        $this->assertNotEquals($docId, $docId2);

        $this->assertEquals('Title', $doc2->getMainTitle('de')->getValue());
    }

    public function testGetCopy()
    {
        $this->markTestIncomplete('Getting a copy/clone of a document not implemented yet.');

        $doc = new Document();

        $title = $doc->addTitleMain();
        $title->setValue('Original Title');
        $title->setLanguage('en');

        $docId = $doc->store();

        $doc = new Document($docId);

        $copy = $doc->getCopy();

        $copy->store();
    }

    public function testToArray()
    {
        $doc = new Document();

        $bibliography = 1;
        $doc->setBelongsToBibliography($bibliography);

        $contributingCorporation = 'KOBV Corp';
        $doc->setContributingCorporation($contributingCorporation);

        $creatingCorporation = 'ZIB Corp';
        $doc->setCreatingCorporation($creatingCorporation);

        $edition = '3rd';
        $doc->setEdition($edition);

        $issue = 'Nov';
        $doc->setIssue($issue);

        $pageFirst = 10;
        $doc->setPageFirst($pageFirst);

        $pageLast = 20;
        $doc->setPageLast($pageLast);

        $pageNumber = 11;
        $doc->setPageNumber($pageNumber);

        $articleNumber = 99;
        $doc->setArticleNumber($articleNumber);

        $publishedYear = 2015;
        $doc->setPublishedYear($publishedYear);

        $publisherName = 'Verlag SoUndSo';
        $doc->setPublisherName($publisherName);

        $publisherPlace = 'Bonn';
        $doc->setPublisherPlace($publisherPlace);

        $publicationState = 'draft';
        $doc->setPublicationState($publicationState);

        $now = Date::getNow();

        $nowArray = [
            'Year' => $now->getYear(),
            'Month' => $now->getMonth(),
            'Day' => $now->getDay(),
            'Hour' => $now->getHour(),
            'Minute' => $now->getMinute(),
            'Second' => $now->getSecond(),
            'Timezone' => $now->getTimezone(),
            'UnixTimestamp' => $now->getUnixTimestamp()
        ];

        $doc->setCompletedDate($now);
        $doc->setPublishedDate($now);
        $doc->setServerDateDeleted($now);
        $doc->setServerDatePublished($now);
        $doc->setThesisDateAccepted($now);
        $doc->setEmbargoDate($now);

        $thesisYearAccepted = 2015;
        $doc->setThesisYearAccepted($thesisYearAccepted);

        $completedYear = 2018;
        $doc->setCompletedYear($completedYear);

        $lang = 'eng';
        $doc->setLanguage($lang);

        $type = 'article';
        $doc->setType($type);

        $volume = 'III';
        $doc->setVolume($volume);

        $title = $doc->addTitleMain();
        $title->setValue('Original Title');
        $title->setLanguage('eng');

        $title = $doc->addTitleMain();
        $title->setValue('Deutscher Titel');
        $title->setLanguage('deu');

        $parent = $doc->addTitleParent();
        $parent->setValue('Parent title');
        $parent->setLanguage('eng');

        $parent = $doc->addTitleParent();
        $parent->setValue('Übergeordneter Titel');
        $parent->setLanguage('deu');

        $sub = $doc->addTitleSub();
        $sub->setValue('subtitle');
        $sub->setLanguage('eng');

        $sub = $doc->addTitleSub();
        $sub->setValue('Untertitel');
        $sub->setLanguage('deu');

        $additional = $doc->addTitleAdditional();
        $additional->setValue('Another title');
        $additional->setLanguage('eng');

        $additional = $doc->addTitleAdditional();
        $additional->setValue('Weiterer Titel');
        $additional->setLanguage('deu');

        $abstract = $doc->addTitleAbstract();
        $abstract->setValue('English abstract');
        $abstract->setLanguage('eng');

        $abstract = $doc->addTitleAbstract();
        $abstract->setValue('Zusammenfassung');
        $abstract->setLanguage('deu');

        $note = $doc->addNote();
        $note->setMessage('A private note');
        $note->setVisibility(Note::ACCESS_PRIVATE);

        $note = $doc->addNote();
        $note->setMessage('A public note');
        $note->setVisibility(Note::ACCESS_PUBLIC);

        $patent = $doc->addPatent();
        $patent->setCountries('Germany');
        $patent->setDateGranted($now);
        $patent->setNumber('123');
        $patent->setYearApplied(2017);
        $patent->setApplication('Invention');

        $patent = $doc->addPatent();
        $patent->setCountries('France');
        $patent->setDateGranted($now);
        $patent->setNumber('456');
        $patent->setYearApplied(2018);
        $patent->setApplication('Another invention');

        $licence1 = new Licence();
        $licence1->setActive(0);
        $licence1->setCommentInternal('first licence');
        $licence1->setDescMarkup('<b>Main Licence</b>');
        $licence1->setDescText('Main licence');
        $licence1->setLanguage('eng');
        $licence1->setLinkLicence('http://www.example.org/licence1');
        $licence1->setLinkLogo('http://www.example.org/licence1/logo');
        $licence1->setLinkSign('http://www.example.org/licence1/sign');
        $licence1->setMimeType('text/plain');
        $licence1->setName('MLN');
        $licence1->setNameLong('Main Licence Name');
        $licence1->setPodAllowed(1);
        $licence1->setSortOrder(2);
        $doc->addLicence($licence1);

        $licence2 = new Licence();
        $licence2->setActive(1);
        $licence2->setCommentInternal('second licence');
        $licence2->setDescMarkup('<b>Second Licence</b>');
        $licence2->setDescText('Second licence');
        $licence2->setLanguage('eng');
        $licence2->setLinkLicence('http://www.example.org/licence2');
        $licence2->setLinkLogo('http://www.example.org/licence2/logo');
        $licence2->setLinkSign('http://www.example.org/licence2/sign');
        $licence2->setMimeType('text/plain');
        $licence2->setName('SLN');
        $licence2->setNameLong('Second Licence Name');
        $licence2->setPodAllowed(0);
        $licence2->setSortOrder(1);
        $doc->addLicence($licence2);

        $keyword = $doc->addSubject();
        $keyword->setValue('A keyword');
        $keyword->setLanguage('eng');
        $keyword->setType('uncontrolled');
        $keyword->setExternalKey('ext:keyword:key'); // not a real example

        $keyword = new SubjectSwd();
        $keyword->setValue('Schlagwort');
        $keyword->setExternalKey('gnd:Schlagwort'); // not a real example
        $doc->addSubject($keyword);

        $grantor = new DnbInstitute();
        $grantor->setAddress('Grantor Str. 18');
        $grantor->setCity('Berlin');
        $grantor->setDepartment('The department');
        $grantor->setDnbContactId('123');
        $grantor->setIsGrantor(1);
        $grantor->setIsPublisher(0);
        $grantor->setName('Big Granting');
        $grantor->setPhone('555 1234');
        $doc->addThesisGrantor($grantor);

        $grantor = new DnbInstitute();
        $grantor->setAddress('Grantor Str. 19');
        $grantor->setCity('Berlin');
        $grantor->setDepartment('The department 2');
        $grantor->setDnbContactId('456');
        $grantor->setIsGrantor(1);
        $grantor->setIsPublisher(1);
        $grantor->setName('Big Granting 2');
        $grantor->setPhone('555 5678');
        $doc->addThesisGrantor($grantor);

        $publisher = new DnbInstitute();
        $publisher->setAddress('Publishing Str. 18');
        $publisher->setCity('Berlin');
        $publisher->setDepartment('The other department');
        $publisher->setDnbContactId('321');
        $publisher->setIsGrantor(0);
        $publisher->setIsPublisher(1);
        $publisher->setName('Big Publishing');
        $publisher->setPhone('555 4321');
        $doc->addThesisPublisher($publisher);

        $publisher = new DnbInstitute();
        $publisher->setAddress('Publishing Str. 19');
        $publisher->setCity('London');
        $publisher->setDepartment('The other department 2');
        $publisher->setDnbContactId('234');
        $publisher->setIsGrantor(1);
        $publisher->setIsPublisher(1);
        $publisher->setName('Big Publishing 2');
        $publisher->setPhone('555 8765');
        $doc->addThesisPublisher($publisher);

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName('enkey1');
        $enrichmentKey->store();

        $enrichmentKey = new EnrichmentKey();
        $enrichmentKey->setName('enkey2');
        $enrichmentKey->store();

        $enrichment = $doc->addEnrichment();
        $enrichment->setKeyName('enkey1');
        $enrichment->setValue('enrichment1');

        $enrichment = $doc->addEnrichment();
        $enrichment->setKeyName('enkey2');
        $enrichment->setValue('enrichment2');

        $enrichment = $doc->addEnrichment();
        $enrichment->setKeyName('enkey1');
        $enrichment->setValue('another enrichment value');

        $collectionRole = new CollectionRole();
        $collectionRole->setName('ColRole1');
        $collectionRole->setOaiName('oaiColRole1');
        $collectionRole->addRootCollection();
        $collectionRole->store();

        $collection = new Collection();
        $collection->setName('col1');
        $collection->setNumber('colnum1');
        $collection->setOaiSubset('oaicol1');
        $collection->setVisible(0);
        $collection->setVisiblePublish(1);
        $collectionRole->getRootCollection()->addFirstChild($collection);
        $collection->store();
        $doc->addCollection($collection);

        $collectionRole2 = new CollectionRole();
        $collectionRole2->setName('ColRole2');
        $collectionRole2->setOaiName('oaiColRole2');
        $collectionRole2->addRootCollection();
        $collectionRole2->store();

        $collection2 = new Collection();
        $collection2->setName('col2');
        $collection2->setNumber('colnum2');
        $collection2->setOaiSubset('oaicol2');
        $collection2->setVisible(1);
        $collection2->setVisiblePublish(0);
        $collectionRole2->getRootCollection()->addFirstChild($collection2);
        $collection2->store();
        $doc->addCollection($collection2);

        $series = new Series();
        $series->setTitle('Series1');
        $series->setInfobox('Series1 description');
        $series->setVisible(1);
        $series->setSortOrder(2);
        $series->store();

        $series2 = new Series();
        $series2->setTitle('Series2');
        $series2->setInfobox('Series2 description');
        $series2->setVisible(0);
        $series2->setSortOrder(1);
        $series2->store();

        $seriesLink = $doc->addSeries($series);
        $seriesLink->setNumber('3');

        $seriesLink = $doc->addSeries($series2);
        $seriesLink->setNumber('7');

        $identifier = $doc->addIdentifier();
        $identifier->setValue('123');
        $identifier->setType('isbn');
        $identifier->setStatus('registered');
        $identifier->setRegistrationTs('2018-10-12 13:45:21');

        $identifier = $doc->addIdentifier();
        $identifier->setValue('abc');
        $identifier->setType('doi');
        $identifier->setStatus('registered');
        $identifier->setRegistrationTs('2018-10-12 13:45:21');

        $ref = $doc->addReference();
        $ref->setValue('146');
        $ref->setType('opus4-id');
        $ref->setLabel('Previous version');
        $ref->setRelation('updates');

        $person = new Person();
        $person->setFirstName('John');
        $person->setLastName('Doe');
        $person->setAcademicTitle('Prof.');
        $person->setDateOfBirth(new Date('1995-04-01'));
        $person->setEmail('john@example.org');
        $person->setOpusId(1);
        $person->setIdentifierOrcid('0000-0000-0000-0001');
        $person->setIdentifierGnd('123456789');
        $person->setIdentifierMisc('opus2');
        $person->setPlaceOfBirth('Berlin');
        $personLink = $doc->addPersonAuthor($person);
        $personLink->setAllowEmailContact(1);
        $personLink->setSortOrder(2);

        // TODO files

        // store document and retrieve it again
        // TODO does it also work without storing (especially identifier and other redundant fields)
        // TODO IMPORTANT it does not work without storing - figure out why and fix it
        $doc = new Document($doc->store());

        // create array and verify it

        $dateCreated = $doc->getServerDateCreated();

        $dateModified = $doc->getServerDateModified();

        $data = $doc->toArray();

        $this->checkArrayEntry('BelongsToBibliography', $bibliography, $data);
        $this->checkArrayEntry('ContributingCorporation', $contributingCorporation, $data);
        $this->checkArrayEntry('CreatingCorporation', $creatingCorporation, $data);
        $this->checkArrayEntry('Edition', $edition, $data);
        $this->checkArrayEntry('Issue', $issue, $data);
        $this->checkArrayEntry('PageFirst', $pageFirst, $data);
        $this->checkArrayEntry('PageLast', $pageLast, $data);
        $this->checkArrayEntry('PageNumber', $pageNumber, $data);
        $this->checkArrayEntry('ArticleNumber', $articleNumber, $data);
        $this->checkArrayEntry('PublishedYear', $publishedYear, $data);
        $this->checkArrayEntry('PublisherName', $publisherName, $data);
        $this->checkArrayEntry('PublisherPlace', $publisherPlace, $data);
        $this->checkArrayEntry('PublicationState', $publicationState, $data);
        $this->checkArrayEntry('ServerState', 'unpublished', $data);
        $this->checkArrayEntry('Type', $type, $data);
        $this->checkArrayEntry('Volume', $volume, $data);

        $this->checkArrayEntry('CompletedDate', $nowArray, $data);
        $this->checkArrayEntry('CompletedYear', $completedYear, $data);

        $this->checkArrayEntry('ThesisDateAccepted', $nowArray, $data);
        $this->checkArrayEntry('ThesisYearAccepted', $thesisYearAccepted, $data);

        $this->checkArrayEntry('ServerDateCreated', $dateCreated->toArray(), $data);
        $this->checkArrayEntry('ServerDateModified', $dateModified->toArray(), $data);
        $this->checkArrayEntry('ServerDateDeleted', $nowArray, $data);
        $this->checkArrayEntry('ServerDatePublished', $nowArray, $data);

        $this->checkArrayEntry('EmbargoDate', $nowArray, $data);

        $this->checkArrayEntry('Language', $lang, $data);

        $this->checkArrayEntry('PublishedDate', $nowArray, $data);

        // check main titles

        $this->assertArrayHasKey('TitleMain', $data);
        $titles = $data['TitleMain'];
        unset($data['TitleMain']);

        $this->assertCount(2, $titles);

        $this->assertEquals([
            'Value' => 'Original Title',
            'Language' => 'eng',
            'Type' => 'main'
        ], $titles[0]);

        $this->assertEquals([
            'Value' => 'Deutscher Titel',
            'Language' => 'deu',
            'Type' => 'main'
        ], $titles[1]);

        // check abstracts

        $this->assertArrayHasKey('TitleAbstract', $data);
        $titles = $data['TitleAbstract'];
        unset($data['TitleAbstract']);

        $this->assertCount(2, $titles);

        $this->assertEquals([
            'Value' => 'English abstract',
            'Language' => 'eng',
            'Type' => 'abstract'
        ], $titles[0]);

        $this->assertEquals([
            'Value' => 'Zusammenfassung',
            'Language' => 'deu',
            'Type' => 'abstract'
        ], $titles[1]);

        // check parent titles

        $this->assertArrayHasKey('TitleParent', $data);
        $titles = $data['TitleParent'];
        unset($data['TitleParent']);

        $this->assertCount(2, $titles);

        $this->assertEquals([
            'Value' => 'Parent title',
            'Language' => 'eng',
            'Type' => 'parent'
        ], $titles[0]);

        $this->assertEquals([
            'Value' => 'Übergeordneter Titel',
            'Language' => 'deu',
            'Type' => 'parent'
        ], $titles[1]);

        // check sub titles

        $this->assertArrayHasKey('TitleSub', $data);
        $titles = $data['TitleSub'];
        unset($data['TitleSub']);

        $this->assertCount(2, $titles);

        $this->assertEquals([
            'Value' => 'subtitle',
            'Language' => 'eng',
            'Type' => 'sub'
        ], $titles[0]);

        $this->assertEquals([
            'Value' => 'Untertitel',
            'Language' => 'deu',
            'Type' => 'sub'
        ], $titles[1]);

        // check additional titles

        $this->assertArrayHasKey('TitleAdditional', $data);
        $titles = $data['TitleAdditional'];
        unset($data['TitleAdditional']);

        $this->assertCount(2, $titles);

        $this->assertEquals([
            'Value' => 'Another title',
            'Language' => 'eng',
            'Type' => 'additional'
        ], $titles[0]);

        $this->assertEquals([
            'Value' => 'Weiterer Titel',
            'Language' => 'deu',
            'Type' => 'additional'
        ], $titles[1]);

        // check notes

        $this->assertArrayHasKey('Note', $data);
        $notes = $data['Note'];
        unset($data['Note']);

        $this->assertCount(2, $notes);

        $this->assertEquals([
            'Message' => 'A private note',
            'Visibility' => 'private',
        ], $notes[0]);

        $this->assertEquals([
            'Message' => 'A public note',
            'Visibility' => 'public',
        ], $notes[1]);

        // check patents

        $this->assertArrayHasKey('Patent', $data);
        $patents = $data['Patent'];
        unset($data['Patent']);

        $this->assertCount(2, $patents);

        $this->assertEquals([
            'Countries' => 'Germany',
            'DateGranted' => $nowArray,
            'Number' => '123',
            'YearApplied' => 2017,
            'Application' => 'Invention'
        ], $patents[0]);

        $this->assertEquals([
            'Countries' => 'France',
            'DateGranted' => $nowArray,
            'Number' => '456',
            'YearApplied' => 2018,
            'Application' => 'Another invention'
        ], $patents[1]);

        // check licences

        $this->assertArrayHasKey('Licence', $data);
        $licences = $data['Licence'];
        unset($data['Licence']);

        $this->assertCount(2, $licences);

        $this->assertEquals([
            'Active' => 0,
            'CommentInternal' => 'first licence',
            'DescMarkup' => '<b>Main Licence</b>',
            'DescText' => 'Main licence',
            'Language' => 'eng',
            'LinkLicence' => 'http://www.example.org/licence1',
            'LinkLogo' => 'http://www.example.org/licence1/logo',
            'LinkSign' => 'http://www.example.org/licence1/sign',
            'MimeType' => 'text/plain',
            'Name' => 'MLN',
            'NameLong' => 'Main Licence Name',
            'PodAllowed' => 1,
            'SortOrder' => 2
        ], $licences[0]);

        $this->assertEquals([
            'Active' => 1,
            'CommentInternal' => 'second licence',
            'DescMarkup' => '<b>Second Licence</b>',
            'DescText' => 'Second licence',
            'Language' => 'eng',
            'LinkLicence' => 'http://www.example.org/licence2',
            'LinkLogo' => 'http://www.example.org/licence2/logo',
            'LinkSign' => 'http://www.example.org/licence2/sign',
            'MimeType' => 'text/plain',
            'Name' => 'SLN',
            'NameLong' => 'Second Licence Name',
            'PodAllowed' => 0,
            'SortOrder' => 1
        ], $licences[1]);

        // check keywords

        $this->assertArrayHasKey('Subject', $data);
        $subjects = $data['Subject'];
        unset($data['Subject']);

        $this->assertEquals([
            'Language' => 'eng',
            'Type' => 'uncontrolled',
            'Value' => 'A keyword',
            'ExternalKey' => 'ext:keyword:key'
        ], $subjects[0]);

        $this->assertEquals([
            'Language' => 'deu',
            'Type' => 'swd',
            'Value' => 'Schlagwort',
            'ExternalKey' => 'gnd:Schlagwort'
        ], $subjects[1]);

        // check thesis grantors

        $this->assertArrayHasKey('ThesisGrantor', $data);
        $grantors = $data['ThesisGrantor'];
        unset($data['ThesisGrantor']);

        $this->assertEquals([
            'Address' => 'Grantor Str. 18',
            'City' => 'Berlin',
            'Department' => 'The department',
            'DnbContactId' => '123',
            'IsGrantor' => '1', // TODO why strings here?
            'IsPublisher' => '0', // TODO why strings here?
            'Name' => 'Big Granting',
            'Phone' => '555 1234',
            'Role' => 'grantor'
        ], $grantors[0]);

        $this->assertEquals([
            'Address' => 'Grantor Str. 19',
            'City' => 'Berlin',
            'Department' => 'The department 2',
            'DnbContactId' => '456',
            'IsGrantor' => '1',
            'IsPublisher' => '1',
            'Name' => 'Big Granting 2',
            'Phone' => '555 5678',
            'Role' => 'grantor'
        ], $grantors[1]);

        // check thesis publishers

        $this->assertArrayHasKey('ThesisPublisher', $data);
        $publishers = $data['ThesisPublisher'];
        unset($data['ThesisPublisher']);

        $this->assertEquals([
            'Address' => 'Publishing Str. 18',
            'City' => 'Berlin',
            'Department' => 'The other department',
            'DnbContactId' => '321',
            'IsGrantor' => '0', // TODO why strings here?
            'IsPublisher' => '1', // TODO why strings here?
            'Name' => 'Big Publishing',
            'Phone' => '555 4321',
            'Role' => 'publisher'
        ], $publishers[0]);

        $this->assertEquals([
            'Address' => 'Publishing Str. 19',
            'City' => 'London',
            'Department' => 'The other department 2',
            'DnbContactId' => '234',
            'IsGrantor' => '1',
            'IsPublisher' => '1',
            'Name' => 'Big Publishing 2',
            'Phone' => '555 8765',
            'Role' => 'publisher'
        ], $publishers[1]);

        // check enrichments

        $this->assertArrayHasKey('Enrichment', $data);
        $enrichments = $data['Enrichment'];
        unset($data['Enrichment']);

        $this->assertEquals([
            'KeyName' => 'enkey1',
            'Value' => 'enrichment1'
        ], $enrichments[0]);

        $this->assertEquals([
            'KeyName' => 'enkey2',
            'Value' => 'enrichment2'
        ], $enrichments[1]);

        $this->assertEquals([
            'KeyName' => 'enkey1',
            'Value' => 'another enrichment value'
        ], $enrichments[2]);

        // TODO check collections (big problem because collections have very incomplete arrays)

        // check series

        $this->assertArrayHasKey('Series', $data);
        $series = $data['Series'];
        unset($data['Series']);
        $this->assertCount(2, $series);

        $this->assertEquals([
            'Title' => 'Series1',
            'Infobox' => 'Series1 description',
            'SortOrder' => '2',
            'Number' => '3',
            'DocSortOrder' => '0',
            'Visible' => '1'
        ], $series[0]);

        $this->assertEquals([
            'Title' => 'Series2',
            'Infobox' => 'Series2 description',
            'SortOrder' => '1',
            'Number' => '7',
            'DocSortOrder' => '0',
            'Visible' => '0'
        ], $series[1]);

        // check identifiers

        $this->assertArrayHasKey('Identifier', $data);
        $identifiers = $data['Identifier'];
        unset($data['Identifier']);
        $this->assertCount(2, $identifiers);

        $this->assertEquals([
            'Value' => '123',
            'Type' => 'isbn',
            'Status' => 'registered',
            'RegistrationTs' => '2018-10-12 13:45:21'
        ], $identifiers[0]);

        $this->assertEquals([
            'Value' => 'abc',
            'Type' => 'doi',
            'Status' => 'registered',
            'RegistrationTs' => '2018-10-12 13:45:21'
        ], $identifiers[1]);

        // check references

        $this->assertArrayHasKey('Reference', $data);
        $references = $data['Reference'];
        unset($data['Reference']);
        $this->assertCount(1, $references);

        $this->assertEquals([
            'Value' => '146',
            'Type' => 'opus4-id',
            'Relation' => 'updates',
            'Label' => 'Previous version'
        ], $references[0]);

        $this->assertArrayHasKey('ReferenceOpus4', $data);
        $references = $data['ReferenceOpus4'];
        unset($data['ReferenceOpus4']);
        $this->assertCount(1, $references);

        $this->assertEquals([
            'Value' => '146',
            'Type' => 'opus4-id',
            'Relation' => 'updates',
            'Label' => 'Previous version'
        ], $references[0]);

        $referenceTypes = [
            'Isbn', 'Urn', 'Doi', 'Handle', 'Url', 'Issn', 'StdDoi', 'CrisLink', 'SplashUrl'
        ];

        foreach ($referenceTypes as $type) {
            $fieldName = "Reference$type";
            $this->assertArrayHasKey($fieldName, $data);
            $references = $data[$fieldName];
            unset($data[$fieldName]);
            $this->assertEmpty($references);
        }

        // check persons

        $this->assertArrayHasKey('Person', $data);
        $persons = $data['Person'];
        unset($data['Person']);
        $this->assertCount(1, $persons);

        $this->assertEquals([
            'AcademicTitle' => 'Prof.',
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'DateOfBirth' => [
                'Year' => '1995',
                'Month' => '04',
                'Day' => '01',
                'Hour' => null,
                'Minute' => null,
                'Second' => null,
                'Timezone' => null,
                'UnixTimestamp' => 796694400,
            ],
            'Email' => 'john@example.org',
            'OpusId' => '1',
            'IdentifierOrcid' => '0000-0000-0000-0001',
            'IdentifierGnd' => '123456789',
            'IdentifierMisc' => 'opus2',
            'PlaceOfBirth' => 'Berlin',
            'Role' => 'author',
            'AllowEmailContact' => 1,
            'SortOrder' => 1
        ], $persons[0]);

        $this->assertArrayHasKey('PersonAuthor', $data);
        $persons = $data['PersonAuthor'];
        unset($data['PersonAuthor']);
        $this->assertCount(1, $persons);

        $this->assertEquals([
            'AcademicTitle' => 'Prof.',
            'FirstName' => 'John',
            'LastName' => 'Doe',
            'DateOfBirth' => [
                'Year' => '1995',
                'Month' => '04',
                'Day' => '01',
                'Hour' => null,
                'Minute' => null,
                'Second' => null,
                'Timezone' => null,
                'UnixTimestamp' => 796694400,
            ],
            'Email' => 'john@example.org',
            'OpusId' => '1',
            'IdentifierOrcid' => '0000-0000-0000-0001',
            'IdentifierGnd' => '123456789',
            'IdentifierMisc' => 'opus2',
            'PlaceOfBirth' => 'Berlin',
            'Role' => 'author',
            'AllowEmailContact' => 1,
            'SortOrder' => 1
        ], $persons[0]);

        $personRoles = ['Contributor', 'Referee', 'Advisor', 'Editor', 'Translator', 'Other', 'Submitter'];

        foreach ($personRoles as $role) {
            $fieldName = "Person$role";
            $this->assertArrayHasKey($fieldName, $data);
            $persons = $data[$fieldName];
            unset($data[$fieldName]);
            $this->assertEmpty($persons);
        }

        // capture output of var_dump to provide remaining array keys if necessary
        ob_start();
        var_dump($data);
        $output = ob_get_clean();
        ob_clean();

        // TODO make exception for File and Collection until we know how to handle them
        $this->assertCount(2, $data, $output);

        $this->assertArrayHasKey('File', $data, $output);
        unset($data['File']);

        $this->assertArrayHasKey('Collection', $data, $output);
        unset($data['Collection']);

        // check if everything has been verified
        $this->assertEmpty($data, $output);
    }

    /**
     * Checks if array key exists and value matches.
     *
     * Removes the key from the array in order to check at the end if all keys have been
     * tested.
     *
     * @param $key
     * @param $value
     * @param $array
     */
    protected function checkArrayEntry($key, $value, &$array)
    {
        $this->assertArrayHasKey($key, $array);
        $this->assertEquals($value, $array[$key]);
        unset($array[$key]);
    }

    /**
     * TODO licences referenced if possible
     * TODO enrichment keys created if necessary?
     * TODO collections referenced
     * TODO series referenced
     * TODO handling ThesisPublisher and ThesisGrantor
     */
    public function testFromArray()
    {
        $data = [
            'Type' => 'article',
            'TitleMain' => [
                [
                    'Type' => 'Main',
                    'Language' => 'eng',
                    'Value' => 'Test Title'
                ],
                [
                    'Type' => 'Main',
                    'Language' => 'deu',
                    'Value' => 'Testtitel'
                ]
            ]
        ];

        $document = Document::fromArray($data);

        $this->assertNotNull($document);
        $this->assertInstanceOf('Opus\Document', $document);
        $this->assertEquals('article', $document->getType());

        $titles = $document->getTitleMain();

        $this->assertCount(2, $titles);
    }

    public function testUpdateFrom()
    {
        $this->markTestIncomplete('Not implemented yet.');

        $doc = new Document();

        $title = $doc->addTitleMain();
        $title->setValue('Original Title');
        $title->setLanguage('en');

        $doc = new Document($doc->store());

        $copy = new Document();

        $copy->updateFrom($doc);

        $titles = $copy->getTitleMain();

        $this->assertCount(1, $titles);

        $copy = new Document($copy->store());

        $this->assertCount(1, $copy->getTitleMain());
    }

    public function testUpdateFromArrayForFullDocument()
    {
        $this->markTestIncomplete('Not implemented yet.');

        $doc = new Document();

        $data = [
            'Type' => 'article'
        ];

        $doc->updateFromArray($data);

        $this->assertEquals($data, $doc->toArray());
    }

    /**
     * In the database the date is stored as a single value.
     *
     * It used to be, when the value is read the unix timestamp is set to the correct value. Now the setting of the
     * UNIX timestamp actually changes the date and time in the Opus\Date object.
     */
    public function testStoringDateWithConflictingUnixTimestamp()
    {
        $doc = new Document();

        $date = new Date();
        $date->setFromString('2011-10-24'); // 1319414400
        $date->setUnixTimestamp(1322694000); // Field UnixTimestamp is read-only now

        $doc->setCompletedDate($date);

        $doc = new Document($doc->store());

        $date = $doc->getCompletedDate();

        $this->assertEquals('2011-10-24 00:00:00', date_format($date->getDateTime(), 'Y-m-d H:i:s'));
        $this->assertEquals('2011-10-24', $date->__toString());
        $this->assertNotEquals(1322694000, $date->getUnixTimestamp());
        $this->assertEquals(1319414400, $date->getUnixTimestamp());

        $expected = new \DateTime();
        $expected->setTimestamp(1319407200);

        $this->assertEquals($expected, $date->getDateTime());
    }

    public function testDateSettingUnixTimestamp()
    {
        $doc = new Document();

        $date = new Date();

        $date->setTimestamp(1322694000);

        $doc->setCompletedDate($date);

        $doc = new Document($doc->store());

        $date = $doc->getCompletedDate();

        $this->assertEquals(1322694000, $date->getUnixTimestamp());
        $this->assertEquals('2011-11-30 23:00:00', date_format($date->getDateTime(), 'Y-m-d H:i:s'));
        $this->assertEquals('2011-11-30T23:00:00Z', $date->__toString());
    }

    public function testGetSubjectOrderAsAdded()
    {
        $doc = new Document();

        $keyword = new Subject();
        $keyword->setType(Subject::SWD);
        $keyword->setValue('Berlin');

        $doc->addSubject($keyword);

        $keyword = new Subject();
        $keyword->setType(Subject::SWD);
        $keyword->setValue('Antonplatz');

        $doc->addSubject($keyword);

        $keyword = new Subject();
        $keyword->setType(Subject::SWD);
        $keyword->setValue('Checkpoint');

        $doc->addSubject($keyword);

        $doc = new Document($doc->store());

        $subjects = $doc->getSubject();

        $this->assertCount(3, $subjects);
        $this->assertEquals('Berlin', $subjects[0]->getValue());
        $this->assertEquals('Antonplatz', $subjects[1]->getValue());
        $this->assertEquals('Checkpoint', $subjects[2]->getValue());
    }

    public function testGetDefaultPlugins()
    {
        $document = new Document();

        $this->assertEquals([
            'Opus\Document\Plugin\XmlCache',
            'Opus\Document\Plugin\IdentifierUrn',
            'Opus\Document\Plugin\IdentifierDoi'
        ], $document->getDefaultPlugins());
    }

    public function testGetDefaultPluginsConfigured()
    {
        \Zend_Registry::get('Zend_Config')->merge(new \Zend_Config([
            'model' => [
                'plugins' => [
                    'document' => [
                        'Opus\Document\Plugin\SequenceNumber'
                    ]
                ]
            ]
        ]));

        $document = new Document();

        $document->setDefaultPlugins(null);

        $this->assertEquals([
            'Opus\Document\Plugin\SequenceNumber',
        ], $document->getDefaultPlugins());

        $this->assertTrue($document->hasPlugin('Opus\Document\Plugin\SequenceNumber'));
    }

    public function testServerStateChanged()
    {
        $doc = new Document();
        $this->assertFalse($doc->getServerStateChanged());

        $doc->setServerState('unpublished');
        $this->assertTrue($doc->getServerStateChanged());

        $docId = $doc->store();

        $doc = new Document($docId);
        $this->assertFalse($doc->getServerStateChanged());

        $doc->setServerState('published');
        $this->assertTrue($doc->getServerStateChanged());

        $doc->setServerState('unpublished');
        $this->assertFalse($doc->getServerStateChanged());

        $doc->store();

        $doc = new Document($docId);

        $doc->setServerState('unpublished');
        $this->assertFalse($doc->getServerStateChanged());

        $doc->setServerState('published');
        $this->assertTrue($doc->getServerStateChanged());
    }

    /**
     * This test threw the following exception.
     *
     * "Opus\Model\DbException : Opus\Document:  Opus\Document: Database column 'edition' has been truncated by
     * 1 characters!"
     *
     * This is caused by the truncate check after saving an object. The function deletePermanent calls delete first.
     * Delete is a status change, so it is an update operation for the database. After an update the truncate check
     * verifies that all the values have been stored completelly. The framework only stores changed values. The check
     * verifies all values. The old longer value has not been changed and therefore is not stored when delete is called.
     * The database contains the shorter value so the truncation check fails.
     */
    public function testNoTruncateExceptionDeletingDocumentUsingOutOfDateObject()
    {
        $doc = new Document();
        $doc->setEdition('0123456789');
        $docId = $doc->store();

        $newObj = new Document($docId);
        $newObj->setEdition('012345678');
        $newObj->store();

        $doc->deletePermanent();
    }

    /**
     * Old truncation check code caused this test to fail with an exception.
     *
     * "Opus\Model\DbException : Opus\Patent:  Opus\Patent: Database column 'number' has been truncated by
     * 1 characters!"
     *
     * The reason is that the delete function a save triggers, because it is actually a status change. After the saving
     * a truncate check is performed, because the Number field in the original patent Object has not been changed, it
     * is not saved. The database however contains a short entry by now. The truncate check always compared all values
     * and so this lead to the exception, because the value in the model was longer than the one in the database.
     */
    public function testNoTruncateExceptionDeletingDocumentWithPatentUsingOutOfDateObject()
    {
        $doc = new Document();
        $patent = new Patent();
        $patent->setNumber('0123456789');
        $patent->setCountries('Germany');
        $patent->setApplication('Application');
        $doc->addPatent($patent);
        $docId = $doc->store();

        $newObj = new Document($docId);
        $patents = $newObj->getPatent();
        $patents[0]->setNumber('012345678');
        $newObj->store();

        $patent->setCountries('France');
        $doc->delete();

        $doc = new Document($docId);
        $patents = $doc->getPatent();

        // old, longer value '0123456789' does not get stored, because it is not modified (anymore)
        $this->assertEquals('012345678', $patents[0]->getNumber());
    }

    /**
     * @expectedException  \Opus\Model\DbException
     * @expectedExceptionMessage Data too long
     *
     * TODO originally tested problem during deletePermanent (which triggered a store in delete function)
     */
    public function testTruncateExceptionForTooLongValue()
    {
        $doc = new Document();
        $patent = new Patent();
        $value = str_repeat('0123456789', 25);
        $patent->setNumber($value);
        $patent->setCountries('Germany');
        $patent->setApplication('Application');
        $doc->addPatent($patent);
        $doc->store();

        $patent->setNumber(str_repeat('0123456789', 26));
        $doc->store();
    }

    public function testDeleteSavesChanges()
    {
        $doc = new Document();
        $doc->setServerState('unpublished');
        $doc->setEdition('1st');
        $docId = $doc->store();

        $doc->setEdition('2nd');

        $doc->delete();

        $doc = new Document($docId);

        $this->assertEquals('deleted', $doc->getServerState());
        $this->assertEquals('2nd', $doc->getEdition());
    }

    public function getIdentifierTypes()
    {
        $identifier = new Identifier();

        $types = array_keys($identifier->getField('Type')->getDefault());

        $types = array_map(function ($value) {
            return [$value];
        }, $types);

        return $types;
    }

    /**
     * @dataProvider getIdentifierTypes
     */
    public function testGetIdentifierDiffersFromGetIdentiferForType($type)
    {
        $doc = new Document();
        $doc->store();

        $id = new Identifier();
        $id->setType($type);
        $id->setValue('someVal');

        $ids = $doc->getIdentifier();
        $ids[] = $id;
        $doc->setIdentifier($ids);

        $specialNames = ['pmid' => 'Pubmed', 'opus3-id' => 'Opus3', 'opac-id' => 'Opac'];

        if (array_key_exists($type, $specialNames)) {
            $typeName = $specialNames[$type];
        } else {
            $typeName = str_replace('-', '', ucwords($type, '-'));
        }

        $funcName = 'getIdentifier' . ucfirst($typeName);

        $identifiers = $doc->getIdentifier();
        $identifiersForType = $doc->$funcName();

        $this->assertEquals($identifiers, $identifiersForType);
    }

    public function testSettingIdentifierDoiChangesIdentifier()
    {
        $doc = new Document();
        $doc->store();

        $id = new Identifier();
        $id->setType('doi');
        $id->setValue('someVal');

        $ids = [$id];
        $doc->setIdentifierDoi($ids);

        $test1 = $doc->getIdentifier();
        $test2 = $doc->getIdentifierDoi();

        $this->assertCount(1, $test1);
        $this->assertEquals($test1, $test2);
    }

    public function testGetIdentifierByType()
    {
        $doc = new Document();

        $id = new Identifier();
        $id->setType('doi');
        $id->setValue('someVal');

        $id2 = new Identifier();
        $id2->setType('doi');
        $id2->setValue('someVal2');

        $id3 = new Identifier();
        $id3->setType('issn');
        $id3->setValue('someVal3');

        $doc->setIdentifier([$id, $id3, $id2]);

        $doc = new Document($doc->store());

        $values = $doc->getIdentifierByType('doi');

        $this->assertCount(2, $values);

        $values = $doc->getIdentifierByType('issn');

        $this->assertCount(1, $values);
    }

    public function testGetIdentifierByTypeWithIndex()
    {
        $doc = new Document();

        $id = new Identifier();
        $id->setType('doi');
        $id->setValue('someVal');

        $id2 = new Identifier();
        $id2->setType('doi');
        $id2->setValue('someVal2');

        $doc->setIdentifier([$id, $id2]);

        $value = $doc->getIdentifierByType('doi', 1);

        $this->assertNotNull($value);
        $this->assertEquals('someVal2', $value->getValue());
    }

    public function testAddIdentifierForType()
    {
        $doc = new Document();

        $identifier = $doc->addIdentifierForType('doi');

        $this->assertNotNull($identifier);
        $this->assertInstanceOf('Opus\Identifier', $identifier);
        $this->assertEquals('doi', $identifier->getType());
    }

    public function testAddIdentifierDoi()
    {
        $doc = new Document();

        $identifier = $doc->addIdentifierDoi();

        $this->assertNotNull($identifier);
        $this->assertInstanceOf('Opus\Identifier', $identifier);
        $this->assertEquals('doi', $identifier->getType());
    }

    public function testSetIdentifiersForType()
    {
        $doc = new Document();

        $ident = new Identifier();
        $ident->setType('doi');
        $ident->setValue('doi-value1');

        $doc->addIdentifier($ident);

        $ident = new Identifier();
        $ident->setType('issn');
        $ident->setValue('issn-value1');

        $doc->addIdentifier($ident);

        $ident = new Identifier();
        $ident->setType('doi');
        $ident->setValue('doi-value2');

        $doc->addIdentifier($ident);

        $all = $doc->getIdentifier();

        $this->assertCount(3, $all);

        $doc->setIdentifiersForType('doi', []);

        $all = $doc->getIdentifier();

        $this->assertCount(1, $all);
        $this->assertEquals('issn', $all[0]->getType());
    }

    public function testCompareDocuments()
    {
        $this->markTestIncomplete('not implemented yet');
    }

    /**
     * TODO My assumption is, that a Collection gets modified when a document is stored. For instance its name.
     */
    public function testModifingCollectionWhenStoringDocument()
    {
        $this->markTestIncomplete('not implemented yet');
    }

    public function testSortingMoreThan255Authors()
    {
        $doc = new Document();

        $authorsCount = 300;

        for ($index = 1; $index <= $authorsCount; $index++) {
            $author = new Person();
            $lastName = sprintf('author%1$03d', $index);
            $author->setLastName($lastName);
            $doc->addPersonAuthor($author);
        }

        $doc = new Document($doc->store());

        $authors = $doc->getPersonAuthor();

        $this->assertCount($authorsCount, $authors);
    }

    public function testGetModelType()
    {
        $doc = new Document();
        $this->assertEquals('document', $doc->getModelType());
    }

    public function testNew()
    {
        $doc = Document::new();

        $this->assertInstanceOf(Document::class, $doc);
    }

    public function testGet()
    {
        $doc = Document::new();
        $docId = $doc->store();

        $doc = Document::get($docId);

        $this->assertInstanceOf(Document::class, $doc);
        $this->assertEquals($docId, $doc->getId());
    }
}
