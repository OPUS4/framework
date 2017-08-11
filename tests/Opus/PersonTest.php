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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Person.
 *
 * @package Opus
 * @category Tests
 *
 * @group PersonTest
 *
 */
class Opus_PersonTest extends TestCase {

    /**
     * List of Opus_Person identifiers having the role Author.
     *
     * @var array
     */
    private $_authors = array();

    /**
     * List of test documents.
     *
     * @var array
     */
    private $_documents = array();

    /**
     * Set up test data documents and persons.
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();

        // create documents
        for ($i = 0; $i<10; $i++) {
            $doc = new Opus_Document();
            $doc->store();
            $this->_documents[] = $doc;
        }

        for ($i = 0; $i<10; $i++) {
            $p = new Opus_Person();
            $p->setFirstName("Dummy-$i")
                ->setLastName("Empty-$i")
                ->store();
        }

        // add a person as author to every document
        // and add the person to the list of authors
        foreach ($this->_documents as $document) {
            $p = new Opus_Person;
            $p->setFirstName('Rainer')
                ->setLastName('Zufall')
                ->setAcademicTitle('Prof. Dr.')
                ->store();
            $this->_authors[] = $p;
            $document->addPersonAuthor($p);
            $document->store();
        }
    }

    /**
     * Get all documents for a given role.
     *
     * @return void
     */
    public function testGetDocumentsByRole() {
        // TODO: $doc->getPersonAuthor()->getId() gibt nicht die Id eines
        // TODO: Autors zurueck, sondern das Paar (document_id, person_id) aus
        // TODO: der Tabelle link_persons_documents.
        //
        // TODO: Die ID der Person erhält man mit getLinkedModelId()

        foreach ($this->_authors as $author) {
            $docs = $author->getDocumentsByRole('author');
            foreach ($docs as $doc) {
                $this->assertEquals(
                    $doc->getPersonAuthor(0)->getLinkedModelId(),
                    $author->getId(),
                    'Retrieved author is not the author of the document as defined in test data.'
                    );
            }
        }
    }

    /**
     * Test if all Person identifer for persons of a given role
     * can be obtained.
     *
     * @return void
     */
    public function testGetAllPersonIdsByRole() {
        $ids = Opus_Person::getAllIdsByRole('author');

        $this->assertTrue(is_array($ids), 'No array returned.');

        foreach ($this->_authors as $author) {
            $this->assertTrue(
                in_array($author->getId(), $ids),
                'Author id not found.');
        }

    }

    public function testDeletePerson() {
        $docId = $this->_documents[0]->getId();
        $d = new Opus_Document($docId);
        $persons = $d->getPerson();
        $this->assertTrue(1 == count($persons));

        $person = $persons[0];
        $this->assertTrue($person->getFirstName() === 'Rainer');
        $this->assertTrue($person->getLastName() === 'Zufall');

        $d->setPerson(array());
        $d->store();

        $d = new Opus_Document($docId);
        $this->assertTrue(0 == count($d->getPerson()));
    }

    public function testOnlyLastNameMandatory() {
        $person = new Opus_Person();

        $fields = $person->describe();

        foreach ($fields as $fieldName) {
            $field = $person->getField($fieldName);

            if ($fieldName === 'LastName') {
                $this->assertTrue($field->isMandatory(), "'$fieldName' should be mandatory.");
            }
            else {
                $this->assertFalse($field->isMandatory(), "'$fieldName' should not be mandatory.");
            }
        }
    }

    public function testGetNameForLastAndFirstName() {
        $person = new Opus_Person();

        $person->setFirstName('Jane');
        $person->setLastName('Doe');

        $this->assertEquals('Doe, Jane', $person->getName());
    }

    public function testGetNameForLastNameOnly() {
        $person = new Opus_Person();

        $person->setLastName('Doe');

        $this->assertEquals('Doe', $person->getName());
    }
    
    public function testSetGetIdentifiers() {
        $person = new Opus_Person();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('http://orcid.org/0000-0002-1694-233X');
        $person->setIdentifierGnd('test_gnd_identifier');
        $person->setIdentifierMisc('myid');
        
        $personId = $person->store();
        
        $person = new Opus_Person($personId);

        $this->assertEquals('http://orcid.org/0000-0002-1694-233X', $person->getIdentifierOrcid());
        $this->assertEquals('test_gnd_identifier', $person->getIdentifierGnd());
        $this->assertEquals('myid', $person->getIdentifierMisc());
    }
    
    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache() {

        $person = new Opus_Person();
        $person->setFirstName('Jane');
        $person->setLastName('Doe');
        $person->store();
        $doc = new Opus_Document();
        $doc->setType("article")
                ->setServerState('published')
                ->setPersonAuthor($person);
        $docId = $doc->store();

        $xmlCache = new Opus_Model_Xml_Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $person->setFirstName('John');
        $person->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }

    public function testGetAllPersons()
    {
        $persons = Opus_Person::getAllPersons();

        $this->assertInternalType('array', $persons);
        $this->assertCount(11, $persons);

        $first = $persons[0];

        $this->assertArrayHasKey('last_name', $first);
        $this->assertArrayHasKey('first_name', $first);
        $this->assertArrayHasKey('identifier_orcid', $first);
        $this->assertArrayHasKey('identifier_gnd', $first);
        $this->assertArrayHasKey('identifier_misc', $first);
    }

    public function testGetAllPersonsPartial()
    {
        $persons = Opus_Person::getAllPersons(null, 0, 1);

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Empty-0', $person['last_name']);
        $this->assertEquals('Dummy-0', $person['first_name']);

        $persons = Opus_Person::getAllPersons(null, 10, 1);

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Zufall', $person['last_name']);
        $this->assertEquals('Rainer', $person['first_name']);

        $persons = Opus_Person::getAllPersons(null, 2, 4);

        $this->assertInternalType('array', $persons);
        $this->assertCount(4, $persons);

        $person = $persons[0];

        $this->assertEquals('Empty-2', $person['last_name']);
        $this->assertEquals('Dummy-2', $person['first_name']);
    }

    public function testGetAllPersonsInRole()
    {
        $persons = Opus_Person::getAllPersons('author');

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Zufall', $person['last_name']);
        $this->assertEquals('Rainer', $person['first_name']);

        $persons = Opus_Person::getAllPersons('other');

        $this->assertCount(0, $persons);

        $docId = $this->_documents[0]->getId();

        $doc = new Opus_Document($docId);
        $other = new Opus_Person();
        $other->setLastName('Musterfrau');
        $other->setFirstName('Erika');
        $doc->addPersonOther($other);
        $doc->store();

        $persons = Opus_Person::getAllPersons('other');

        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Musterfrau', $person['last_name']);
        $this->assertEquals('Erika', $person['first_name']);
    }

    public function testGetAllPersonsInRoleWithFilter()
    {
        $this->markTestIncomplete('not implemented yet');
    }

    /**
     * TODO should this throw an exception?
     */
    public function testGetAllPersonsInUnknownRole()
    {
        $persons = Opus_Person::getAllPersons('cook');

        $this->assertInternalType('array', $persons);
        $this->assertCount(0, $persons);
    }

    public function testGetAllPersonsSorting()
    {
        $doc = new Opus_Document($this->_documents[0]->getId());

        $person = new Opus_Person();
        $person->setLastName('Blau');
        $doc->addPersonReferee($person);

        $person = new Opus_Person();
        $person->setLastName('Rot');
        $doc->addPersonReferee($person);

        $person = new Opus_Person();
        $person->setLastName('Grün');
        $doc->addPersonReferee($person);

        $doc->store();

        $persons = Opus_Person::getAllPersons('referee');

        $this->assertInternalType('array', $persons);
        $this->assertCount(3, $persons);

        $this->assertEquals('Blau', $persons[0]['last_name']);
        $this->assertEquals('Grün', $persons[1]['last_name']);
        $this->assertEquals('Rot', $persons[2]['last_name']);
    }

    /**
     * Names with leading spaces should not appear at the beginning of the list, but in their proper place.
     */
    public function testGetAllPersonsSortingWithLeadingSpaces()
    {
        $doc = new Opus_Document($this->_documents[0]->getId());

        $person = new Opus_Person();
        $person->setLastName('A');
        $doc->addPersonReferee($person);

        $person = new Opus_Person();
        $person->setLastName('B');
        $doc->addPersonReferee($person);

        $person = new Opus_Person();
        $person->setLastName('C');
        $doc->addPersonReferee($person);

        $doc->store();

        // add leading space to Person 'C' (framework trims leadings spaces - OPUSVIER-3832)
        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Persons');
        $database = $table->getAdapter();
        $table->update(array('last_name' => ' B'), array($database->quoteInto('last_name = ?', 'B')));

        $persons = Opus_Person::getAllPersons('referee');

        $this->assertInternalType('array', $persons);
        $this->assertCount(3, $persons);

        $this->assertEquals('A', $persons[0]['last_name']);
        $this->assertEquals('B', $persons[1]['last_name']);
        $this->assertEquals('C', $persons[2]['last_name']);
    }

    /**
     * Persons that have different identifiers are not considered the same.
     */
    public function testGetAllPersonsHandlingOfIdentifier()
    {
        $doc = new Opus_Document($this->_documents[0]->getId());

        $person1 = new Opus_Person();
        $person1->setLastName('Person');
        $doc->addPersonReferee($person1);

        $person2 = new Opus_Person();
        $person2->setLastName('Person');
        $doc->addPersonReferee($person2);

        $person3 = new Opus_Person();
        $person3->setLastName('Person');
        $doc->addPersonReferee($person3);

        $doc->store();

        $persons = Opus_Person::getAllPersons('referee');

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person3->setIdentifierMisc('123');
        $person3->store();

        $persons = Opus_Person::getAllPersons('referee');

        $this->assertCount(2, $persons);

        $person2->setIdentifierGnd('654');
        $person2->store();

        $persons = Opus_Person::getAllPersons('referee');

        $this->assertCount(3, $persons);
    }

    public function testGetAllPersonsLeadingSpacesMerged()
    {
        $personsSetup = array(
            'Mueller' => array(),
            ' Mueller' => array()
        );

        $this->_createPersons($personsSetup);

        $persons = Opus_Person::getAllPersons(null, 0, 0, 'Mueller');

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $persons = Opus_Person::getAllPersons();

        $this->assertInternalType('array', $persons);
        $this->assertCount(12, $persons);
    }

    public function testGetPersonRoles()
    {
        $roles = Opus_Person::getPersonRoles(array('last_name' => 'Zufall', 'first_name' => 'Rainer'));

        $this->assertInternalType('array', $roles);
        $this->assertCount(1, $roles);

        $role = $roles[0];

        $this->assertInternalType('array', $role);
        $this->assertArrayHasKey('role', $role);
        $this->assertEquals('author', $role['role']);
        $this->assertArrayHasKey('documents', $role);
        $this->assertEquals(10, $role['documents']);

        $doc = new Opus_Document($this->_documents[0]->getId());
        $person = new Opus_Person();
        $person->setLastName('Zufall');
        $person->setFirstName('Rainer');
        $doc->addPersonOther($person);
        $doc->store();

        $roles = Opus_Person::getPersonRoles(array('last_name' => 'Zufall', 'first_name' => 'Rainer'));

        $this->assertInternalType('array', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetPersonDocuments()
    {
        $documents = Opus_Person::getPersonDocuments(array('last_name' => 'Zufall', 'first_name' => 'Rainer'));

        $this->assertInternalType('array', $documents);
        $this->assertCount(10, $documents);

        $doc = new Opus_Document($this->_documents[0]->getId());
        $person = new Opus_Person();
        $person->setLastName('Zufall');
        $doc->addPersonOther($person);
        $doc->store();

        $documents = Opus_Person::getPersonDocuments(array('last_name' => 'Zufall'));

        $this->assertInternalType('array', $documents);
        $this->assertCount(1, $documents);
        $this->assertEquals($this->_documents[0]->getId(), $documents[0]);
    }

    public function testGetPersonDocumentsByState()
    {
        $person = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $documents = Opus_Person::getPersonDocuments($person);

        $this->assertCount(10, $documents);

        $documents = Opus_Person::getPersonDocuments($person, 'unpublished');

        $this->assertCount(10, $documents);

        for ($i = 0; $i < 5; $i++)
        {
            $doc = $this->_documents[$i];
            $doc->setServerState('audited');
            $doc->store();
        }

        $documents = Opus_Person::getPersonDocuments($person, 'audited');

        $this->assertCount(5, $documents);

        $documents = Opus_Person::getPersonDocuments($person, 'unpublished');

        $this->assertCount(5, $documents);
    }

    public function testGetPersonDocumentsDistinct()
    {
        $person = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $documents = Opus_Person::getPersonDocuments($person);

        $this->assertCount(10, $documents);

        $person2 = new Opus_Person();
        $person2->setLastName('Zufall');
        $this->_documents[0]->addPersonAuthor($person2);
        $this->_documents[0]->store();

        $documents = Opus_Person::getPersonDocuments($person);

        $this->assertNotCount(11, $documents);
        $this->assertCount(10, $documents);
    }

    public function testGetPersonDocumentsSortedById()
    {
        $doc1 = new Opus_Document();
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2 = new Opus_Document();
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = array('last_name' => 'Testy');

        $documents = Opus_Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'id', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'id', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }


    public function testGetPersonDocumentsSortedByType()
    {
        $doc1 = new Opus_Document();
        $doc1->setType('article');
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2 = new Opus_Document();
        $doc2->setType('dissertation');
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = array('last_name' => 'Testy');

        $documents = Opus_Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'docType', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'docType', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByPublicationDate()
    {
        $doc1 = new Opus_Document();
        $date = new Opus_Date(new DateTime());
        $doc1->setServerDatePublished($date);
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        sleep(2);

        $doc2 = new Opus_Document();
        $date = new Opus_Date(new DateTime());
        $doc2->setServerDatePublished($date);
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = array('last_name' => 'Testy');

        $documents = Opus_Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'publicationDate', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'publicationDate', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByTitle()
    {
        $doc1 = new Opus_Document();
        $title = $doc1->addTitleMain();
        $title->setValue('A Title');
        $title->setLanguage('eng');
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2 = new Opus_Document();
        $title = $doc2->addTitleMain();
        $title->setValue('B Title');
        $title->setLanguage('eng');
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = array('last_name' => 'Testy');

        $documents = Opus_Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'title', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'title', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByAuthor()
    {
        $this->markTestSkipped('TODO - sorting by author not properly working yet OPUSVIER-3810');
        $doc1 = new Opus_Document();
        $person = new Opus_Person();
        $person->setLastName('A Person');
        $personLink = $doc1->addPersonAuthor($person);
        $personLink->setSortOrder(0);
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $personLink = $doc1->addPersonAuthor($person);
        $personLink->setSortOrder(1);
        $docId1 = $doc1->store();

        $doc2 = new Opus_Document();
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = array('last_name' => 'Testy');

        $documents = Opus_Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'author', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Opus_Person::getPersonDocuments($personMatch, null, null, 'author', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsByStateSorted()
    {
        $doc1 = new Opus_Document();
        $title = $doc1->addTitleMain();
        $title->setValue('A Title');
        $title->setLanguage('eng');
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2 = new Opus_Document();
        $title = $doc2->addTitleMain();
        $title->setValue('B Title');
        $title->setLanguage('eng');
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = array('last_name' => 'Testy');

        $documents = Opus_Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Opus_Person::getPersonDocuments($personMatch, 'unpublished', null, 'title', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Opus_Person::getPersonDocuments($personMatch, 'unpublished', null, 'title', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsByRole()
    {
        $person = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $documents = Opus_Person::getPersonDocuments($person, null,'author');

        $this->assertCount(10, $documents);

        $documents = Opus_Person::getPersonDocuments($person, null,'editor');

        $this->assertCount(0, $documents);

        $doc = $this->_documents[0];

        $editor = new Opus_Person();
        $editor->setLastName('Zufall');
        $editor->setFirstName('Rainer');
        $doc->addPersonEditor($editor);
        $doc->store();

        $documents = Opus_Person::getPersonDocuments($person, null, 'editor');

        $this->assertCount(1, $documents);
    }

    public function testGetPersonDocumentsByStateAndRole()
    {
        $person = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $documents = Opus_Person::getPersonDocuments($person, 'unpublished', 'author');
        $this->assertCount(10, $documents);

        $documents = Opus_Person::getPersonDocuments($person, 'published', 'author');
        $this->assertCount(0, $documents);

        $documents = Opus_Person::getPersonDocuments($person, 'unpublished', 'editor');
        $this->assertCount(0, $documents);

        $doc = $this->_documents[0];

        $doc->setServerState('published');
        $editor = new Opus_Person();
        $editor->setLastName('Zufall');
        $editor->setFirstName('Rainer');
        $doc->addPersonEditor($editor);
        $doc->store();

        $documents = Opus_Person::getPersonDocuments($person, 'published', 'editor');
        $this->assertCount(1, $documents);
    }

    public function testGetAllPersonsWithFilter()
    {
        $persons = Opus_Person::getAllPersons(null, 0, 0);

        $this->assertCount(11, $persons);

        $persons = Opus_Person::getAllPersons(null, 0, 0, 'fal');

        $this->assertCount(1, $persons);
        $this->assertEquals('Zufall', $persons[0]['last_name']);

        $persons = Opus_Person::getAllPersons(null, 0, 0, 'pty');

        $this->assertCount(10, $persons);
    }

    public function testGetAllPersonsWithFilterFirstName()
    {
        $doc = new Opus_Document($this->_documents[0]->getId());
        $person = new Opus_Person();
        $person->setLastName('Mustermann');
        $person->setFirstName('Bafala');
        $doc->addPersonOther($person);
        $doc->store();

        $persons = Opus_Person::getAllPersons(null, 0, 0, 'fal');

        $this->assertCount(2, $persons);
    }

    public function testGetAllPersonsWithFilterCaseInsensitive()
    {
        $persons = Opus_Person::getAllPersons(null, 0, 0, 'FAL');

        $this->assertCount(1, $persons);

        $persons = Opus_Person::getAllPersons(null, 0, 0, 'uFa');

        $this->assertCount(1, $persons);
    }

    public function testGetAllPersonsCount()
    {
        $persons = Opus_Person::getAllPersons();
        $count = Opus_Person::getAllPersonsCount();

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(11, $count);

        $persons = Opus_Person::getAllPersons('author');
        $count = Opus_Person::getAllPersonsCount('author');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(1, $count);

        $persons = Opus_Person::getAllPersons('author', 0, 0, 'fal');
        $count = Opus_Person::getAllPersonsCount('author', 'fal');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(1, $count);

        $persons = Opus_Person::getAllPersons(null, 0, 0, 'emp');
        $count = Opus_Person::getAllPersonsCount(null, 'emp');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(10, $count);
    }

    public function testOpusId()
    {
        $person = new Opus_Person();
        $person->setLastName('Testy');
        $person->setOpusId('12345');
        $personId = $person->store();

        $person = new Opus_Person($personId);

        $this->assertEquals('12345', $person->getOpusId());
        $this->assertEquals('Testy', $person->getLastName());
    }

    public function testGetPersonValues()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $values = Opus_Person::getPersonValues($personCrit);

        $this->assertCount(11, $values);

        $this->assertArrayHasKey('id', $values);

        $personIds = $values['id'];

        $this->assertInternalType('array', $personIds);
        $this->assertCount(10, $personIds);

        $this->assertArrayHasKey('last_name', $values);
        $this->assertInternalType('string', $values['last_name']);
        $this->assertEquals('Zufall', $values['last_name']);

        foreach ($values as $key => $value)
        {
            if ($key !== 'id')
            {
                $this->assertNotInternalType('array', $value);
            }
        }

        $person = new Opus_Person($personIds[0]);
        $person->setPlaceOfBirth('Hamburg');
        $person->store();

        $values = Opus_Person::getPersonValues($personCrit);

        $this->assertArrayHasKey('place_of_birth', $values);
        $this->assertInternalType('array', $values['place_of_birth']);
        $this->assertCount(2, $values['place_of_birth']);
    }

    public function testGetPersonValuesWithExtraSpaces()
    {
        $names = array(
            'Spacey' => array(
                'Email' => 'test@example.org'
            ),
            '  Spacey' => array(
                'Email' => 'spacey@example.org'
            ),
            'Spacey  ' => array(),
            '  Spacey  ' => array(),
            ' Spacey ' => array(),
            '  spacey  ' => array(
                'AcademicTitle' => 'Prof.'
            )
        );

        $personIds = $this->_createPersons($names);

        $personCrit = array(
            'last_name' => 'Spacey'
        );

        $values = Opus_Person::getPersonValues($personCrit);

        $this->assertNotNull($values);
        $this->assertInternalType('array', $values);

        $this->assertArrayHasKey('id', $values);
        $this->assertInternalType('array', $values['id']);
        $this->assertCount(6, $values['id']);

        foreach ($personIds as $personId)
        {
            $this->assertContains($personId, $values['id']);
        }

        $this->assertArrayHasKey('last_name', $values);
        $this->assertInternalType('array', $values['last_name']);

        $this->assertCount(6, $values['last_name']);
        $this->assertContains('Spacey', $values['last_name']);
        $this->assertContains('  spacey  ', $values['last_name']);

        $this->assertArrayHasKey('email', $values);
        $emails = $values['email'];
        $this->assertCount(3, $emails);
        $this->assertContains('test@example.org', $emails);
        $this->assertContains('spacey@example.org', $emails); // got the name with leading spaces
        $this->assertContains( null, $emails);

        $this->assertArrayHasKey('academic_title', $values);
        $this->assertInternalType('array', $values['academic_title']);
        $this->assertCount(2, $values['academic_title']);
        $this->assertContains('Prof.', $values['academic_title']);
        $this->assertContains(null, $values['academic_title']);
    }

    public function testGetPersonValuesNotFound()
    {
        $values = Opus_Person::getPersonValues(array('last_name' => 'doesnotexist'));

        $this->assertNull($values);
    }

    public function testCreatePersonTestFunction()
    {
        $personValues = array(
            ' Spacey ' => array()
        );

        $personIds = $this->_createPersons($personValues);

        $this->assertNotNull($personIds);
        $this->assertInternalType('array', $personIds);
        $this->assertCount(1, $personIds);

        $person = new Opus_Person($personIds[0]);

        $this->assertEquals(' Spacey ', $person->getLastName());
    }

    public function testUpdateAll()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $changes = array(
            'Email' => 'bulktest@example.org'
        );

        Opus_Person::updateAll($personCrit, $changes);

        foreach ($this->_authors as $author)
        {
            $person = new Opus_Person($author->getId());

            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }
    }

    public function testUpdateAllForSpecifiedDocuments()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $changes = array(
            'Email' => 'bulktest@example.org'
        );

        $documents = array(2, 4, 7);

        Opus_Person::updateAll($personCrit, $changes, $documents);

        foreach($this->_authors as $author)
        {
            $person = new Opus_Person($author->getId());

            $personDocs = $person->getDocumentsByRole('author');

            $this->assertCount(1, $personDocs);

            $docId = $personDocs[0]->getId();

            if (in_array($docId, $documents))
            {
                $this->assertEquals('bulktest@example.org', $person->getEmail());
            }
            else
            {
                $this->assertNull($person->getEmail());
            }
        }
    }

    public function testUpdateAllForDocumentWithoutMatchingPerson()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $changes = array(
            'Email' => 'bulktest@example.org'
        );

        $doc = new Opus_Document();
        $title = new Opus_Title();
        $title->setLanguage('deu');
        $title->setValue('Document with no author');
        $doc->addTitleMain($title);
        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $documents = array(3, 5, $docId);

        $lastModified = $doc->getServerDateModified();

        sleep(2);

        $now = new Opus_Date();
        $now->setNow();

        sleep(2);

        // new ServerDateModified should be past $now
        Opus_Person::updateAll($personCrit, $changes, $documents);

        // document without matching author was not modified
        $this->assertEquals($lastModified, $doc->getServerDateModified());

        //filtered documents were not modified
        foreach ($this->_documents as $doc)
        {
            $document = new Opus_Document($doc->getId()); // don't use old objects - they are not updated

            $dateModified = $document->getServerDateModified();

            if (in_array($document->getId(), array(3, 5)))
            {
                $this->assertGreaterThan($now->getUnixTimestamp(), $dateModified->getUnixTimestamp());
            }
            else
            {
                $this->assertLessThan($now->getUnixTimestamp(), $dateModified->getUnixTimestamp());
            }
        }
    }

    public function testUpdateAllNoChanges()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $changes = array();

        $lastModified = $this->_documents[0]->getServerDateModified();

        sleep(2);

        Opus_Person::updateAll($personCrit, $changes);

        $this->assertEquals($lastModified, $this->_documents[0]->getServerDateModified());
    }

    /**
     * @expectedException Opus_Model_Exception
     * @expectedExceptionMessage unknown field 'IdentifierIntern' for update
     */
    public function testUpdateAllBadChanges()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $changes = array('IdentifierIntern' => 'id1234'); // only Identifier(Orcid|Gnd|Misc) exist

        $lastModified = $this->_documents[0]->getServerDateModified();

        sleep(2);

        Opus_Person::updateAll($personCrit, $changes);

        $this->assertEquals($lastModified, $this->_documents[0]->getServerDateModified());
    }

    public function testUpdateAllWithSpaces()
    {
        $personCrit = array('last_name' => 'Tester', 'first_name' => 'Usual');

        $persons = array(
            'Tester' => array(
                'FirstName' => 'Usual'
            ),
            '  Tester' => array(
                'FirstName' => 'Usual  ',
            ),
            'Tester  ' => array(
                'FirstName' => '  Usual',
            ),
            ' Tester ' => array(
                'FirstName' => ' Usual '
            ),
            'Tester ' => array()
        );

        $personIds = $this->_createPersons($persons);

        $changes = array(
            'Email' => 'bulktest@example.org'
        );

        Opus_Person::updateAll($personCrit, $changes);

        for ($index = 0; $index < 4; $index++)
        {
            $person = new Opus_Person($personIds[$index]);
            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }

        $person = new Opus_Person($personIds[4]);
        $this->assertNull($person->getEmail());
    }

    public function testUpdateAllValuesAreTrimmed()
    {
        $personCrit = array('last_name' => 'Tester');

        $persons = array('Tester' => array());

        $personIds = $this->_createPersons($persons);

        $changes = array(
            'FirstName' => ' John '
        );

        Opus_Person::updateAll($personCrit, $changes);

        $person = new Opus_Person($personIds[0]);

        $this->assertEquals('John', $person->getFirstName());
    }

    public function testGetPersonsAndDocumentsWithSpaces()
    {
        $personCrit = array('last_name' => 'Tester', 'first_name' => 'Usual');

        $persons = array(
            'Tester' => array(
                'FirstName' => 'Usual'
            ),
            '  Tester' => array(
                'FirstName' => 'Usual  ',
            ),
            'Tester  ' => array(
                'FirstName' => '  Usual',
            ),
            ' Tester ' => array(
                'FirstName' => ' Usual '
            ),
            'Tester ' => array()
        );

        $personIds = $this->_createPersons($persons);

        $personDocs = Opus_Person::getPersonsAndDocuments($personCrit);

        $this->markTestIncomplete('TODO finish');
    }

    public function testGetPersons()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $person = new Opus_Person();
        $person->setLastName('Zufall');
        $person->store(); // not Rainer

        $personIds = Opus_Person::getPersons($personCrit);

        $this->assertNotNull($personIds);
        $this->assertInternalType('array', $personIds);
        $this->assertCount(10, $personIds);
    }

    public function testGetPersonsForDocuments()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $documentIds = array(2, 4, 7, 8);

        $personIds = Opus_Person::getPersons($personCrit, $documentIds);

        $this->assertNotNull($personIds);
        $this->assertInternalType('array', $personIds);
        $this->assertCount(4, $personIds);

        foreach ($personIds as $personId)
        {
            $person = new Opus_Person($personId);

            $documents = $person->getDocumentsByRole('author');

            $this->assertCount(1, $documents);

            $this->assertContains($documents[0]->getId(), $documentIds);
        }
    }

    public function testGetPersonsForDocumentsBadIds()
    {
        $personCrit = array('first_name' => 'Rainer');

        $persons = Opus_Person::getPersons($personCrit, array(33, 34));

        $this->assertCount(0, $persons);
    }

    public function testGetPersonsForDocumentsCaseInsensitive()
    {
        $personCrit = array('last_name' => 'zuFall', 'first_name' => 'Rainer');

        $documentIds = array(2, 3, 4);

        $personIds = Opus_Person::getPersons($personCrit, $documentIds);

        $this->assertCount(3, $personIds);

        foreach ($personIds as $personId)
        {
            $person = new Opus_Person($personId);

            $this->assertEquals('Zufall', $person->getLastName());
        }
    }

    public function testUpdateAllChangeLastName()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $changes = array('LastName' => 'Plannt', 'FirstName' => 'Volge');

        Opus_Person::updateAll($personCrit, $changes);

        foreach ($this->_authors as $author)
        {
            $person = new Opus_Person($author->getId());

            $this->assertEquals('Plannt', $person->getLastName());
            $this->assertEquals('Volge', $person->getFirstName());
        }
    }

    public function testConvertChanges()
    {
        $changes = array(
            'LastName' => 'Zufall',
            'FirstName' => 'Rainer',
            'Email' => 'example@example.org'
        );

        $result = Opus_Person::convertChanges($changes);

        $this->assertEquals(array(
            'last_name' => 'Zufall',
            'first_name' => 'Rainer',
            'email' => 'example@example.org'
        ), $result);
    }

    public function testConvertToFieldNames()
    {
        $values = array(
            'last_name' => 'Zufall',
            'first_name' => 'Rainer',
            'email' => 'example@example.org'
        );

        $result = Opus_Person::convertToFieldNames($values);

        $this->assertEquals(array(
            'LastName' => 'Zufall',
            'FirstName' => 'Rainer',
            'Email' => 'example@example.org'
        ), $result);
    }

    public function testMatches()
    {
        $criteria = array('LastName' => 'Zufall');

        $person = $this->_authors[0];

        $this->assertFalse($person->matches($criteria));

        $criteria['FirstName'] = 'Rainer';

        $this->assertTrue($person->matches($criteria));
    }

    protected function _createPersons($persons)
    {
        $personIds = array();

        $table = Opus_Db_TableGateway::getInstance('Opus_Db_Persons');
        $database = $table->getAdapter();

        foreach ($persons as $name => $values)
        {
            $person = new Opus_Person();
            $person->setLastName($name);

            foreach ($values as $fieldName => $value)
            {
                $person->getField($fieldName)->setValue($value);
            }

            $personId = $person->store();

            array_push($personIds, $personId);

            // check if there are extra spaces
            if ($name !== trim($name))
            {
                $table->update(
                    array('last_name' => $name),
                    array($database->quoteInto('id = ?', $personId))
                );
            }
        }

        return $personIds;
    }

    public function testGetPersonsAndDocuments()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $personDocs = Opus_Person::getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(10, $personDocs);

        foreach ($personDocs as $match)
        {
            $this->assertInternalType('array', $match);
            $this->assertCount(2, $match);
            $this->assertArrayHasKey('person_id', $match);
            $this->assertArrayHasKey('document_id', $match);
            $personId = $match['person_id'];
            $docId = $match['document_id'];
            $person = new Opus_Person($personId);
            $assocDocs = $person->getDocumentIds();
            $this->assertContains($docId, $assocDocs);
        }

        $personIds = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(10, $personIds);
        $this->assertCount(10, $documentIds);
    }

    public function testGetPersonsAndDocumentsForSubset()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $docSet = array(2, 5, 6, 10, 99); // document 99 does not exist

        $personDocs = Opus_Person::getPersonsAndDocuments($personCrit, $docSet);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(4, $personDocs);

        $documentIds = array_column($personDocs, 'document_id');

        $this->assertContains(2, $documentIds);
        $this->assertContains(5, $documentIds);
        $this->assertContains(6, $documentIds);
        $this->assertContains(10, $documentIds);

        $doc = new Opus_Document($this->_documents[1]->getId());

        $this->assertEquals(2, $doc->getId());

        $doc->setPerson(null); // remove all persons
        $doc->store();

        $personDocs = Opus_Person::getPersonsAndDocuments($personCrit, $docSet);

        $this->assertCount(3, $personDocs);

        $documentIds = array_column($personDocs, 'document_id');

        $this->assertNotContains(2, $documentIds);
    }

    public function testGetPersonsAndDocumentsMultiplePersonsOnDocument()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $doc = new Opus_Document($this->_documents[0]->getId());

        $person = new Opus_Person();
        $person->setLastName('Zufall');
        $person->setFirstName('Rainer');

        $doc->addPersonOther($person);
        $doc->store();

        $personDocs = Opus_Person::getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(11, $personDocs);

        $personIds = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(11, $personIds);
        $this->assertCount( 10,array_unique($documentIds));
    }

    public function testGetPersonsAndDocumentsMultipleDocumentsOnPerson()
    {
        $personCrit = array('last_name' => 'Zufall', 'first_name' => 'Rainer');

        $doc = new Opus_Document();
        $doc->setType('article');
        $title = new Opus_Title();
        $title->setLanguage('eng');
        $title->setValue('Test document');
        $doc->addTitleMain($title);
        $doc->addPersonOther($this->_authors[0]);
        $doc->store();

        $personDocs = Opus_Person::getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(11, $personDocs);

        $personIds = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(10, array_unique($personIds));
        $this->assertCount( 11, $documentIds);
    }

    public function testGetDocumentIds()
    {
        $person = new Opus_Person($this->_authors[0]->getId());

        $docIds = $person->getDocumentIds();

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains(1, $docIds);
    }

    public function testGetDocumentIdsUniqueValues()
    {
        $person = new Opus_Person($this->_authors[0]->getId());
        $doc = new Opus_Document($this->_documents[0]->getId());

        $doc->addPersonAdvisor($person);
        $doc->store();

        $docIds = $person->getDocumentIds();

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains($doc->getId(), $docIds);
    }

    public function testGetDocumentIdsForRole()
    {
        $person = new Opus_Person($this->_authors[0]->getId());

        $docIds = $person->getDocumentIds('author');

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains($this->_documents[0]->getId(), $docIds);

        $docIds = $person->getDocumentIds('advisor');

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(0, $docIds);

        $this->_documents[0]->addPersonAdvisor($person);
        $this->_documents[0]->store();

        $docIds = $person->getDocumentIds('advisor');

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains($this->_documents[0]->getId(), $docIds);
    }

    public function testGetDocuments()
    {
        $personIds = array(
            $this->_authors[0]->getId(),
            $this->_authors[4]->getId(),
        );

        $documentIds = Opus_Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->_documents[0]->getId(), $documentIds);
        $this->assertContains($this->_documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsBadArgument()
    {
        $personIds = array(
            $this->_authors[0]->getId(),
            $this->_authors[0]->getId(), // same person id twice
            $this->_authors[4]->getId(),
            999 // unknown person
        );

        $documentIds = Opus_Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->_documents[0]->getId(), $documentIds);
        $this->assertContains($this->_documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsOnePersonTwoDocuments()
    {
        $doc = new Opus_Document($this->_documents[1]->getId());
        $doc->addPersonSubmitter($this->_authors[0]);
        $docId = $doc->store();

        $personIds = array(
            $this->_authors[0]->getId(),
            $this->_authors[4]->getId(),
        );

        $documentIds = Opus_Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(3, $documentIds);
        $this->assertContains($this->_documents[0]->getId(), $documentIds);
        $this->assertContains($this->_documents[4]->getId(), $documentIds);
        $this->assertContains($docId, $documentIds);
    }

    public function testGetDocumentsTwoPersonsOneDocument()
    {
        $doc = new Opus_Document($this->_documents[0]->getId());
        $person = new Opus_Person();
        $person->setLastName('Tester');
        $plink = $doc->addPersonSubmitter($person);
        $doc->store();

        $personIds = array(
            $this->_authors[0]->getId(), // document 0
            $this->_authors[4]->getId(),
            $plink->getModel()->getId()  // document 0
        );

        $documentIds = Opus_Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->_documents[0]->getId(), $documentIds);
        $this->assertContains($this->_documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsFilterIds()
    {
        $personIds = array(
            $this->_authors[0]->getId(),
            $this->_authors[4]->getId(),
        );

        $allowedDocuments = array(
            $this->_documents[4]->getId()
        );

        $documentIds = Opus_Person::getDocuments($personIds, $allowedDocuments);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(1, $documentIds);
        $this->assertNotContains($this->_documents[0]->getId(), $documentIds);
        $this->assertContains($this->_documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsEmptyDocumentsParam()
    {
        $personIds = array(
            $this->_authors[0]->getId(),
            $this->_authors[4]->getId(),
        );

        $allowedDocuments = array(); // should be handled like null

        $documentIds = Opus_Person::getDocuments($personIds, $allowedDocuments);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->_documents[0]->getId(), $documentIds);
        $this->assertContains($this->_documents[4]->getId(), $documentIds);
    }

    public function testPersonRolesWithSpacesAroundParameterValues()
    {
        $personCrit = array(
            'last_name' => ' Zufall ',
            'first_name' => ' Rainer '
        );

        $persons = Opus_Person::getPersons($personCrit);

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(10, $persons);
    }

    public function testStoreValuesAreTrimmed()
    {
        $person = new Opus_Person();
        $person->setLastName(' Zufall ');
        $person->setFirstName(' Rainer ');
        $personId = $person->store();

        $person = new Opus_Person($personId);

        $this->assertEquals('Zufall', $person->getLastName());
        $this->assertEquals('Rainer', $person->getFirstName());
    }
  
    /**
     * OPUSVIER-3764
     *
     * @expectedException Opus_Model_Exception
     * @expectedExceptionMessage No Opus_Db_Documents with id
     */
    public function testDeleteAssignedPerson()
    {
        $this->markTestIncomplete('TODO not sure what/how to test');

        $doc = new Opus_Document();
        $doc->setServerState('published');
        $doc->setType('article');

        $person = new Opus_Person();
        $person->setLastName('Tester');

        $doc->addPersonAuthor($person);

        $docId = $doc->store();

        $person->delete();

        // $doc = new Opus_Document($docId);

        $doc->deletePermanent();

        new Opus_Document($docId);
    }

}
