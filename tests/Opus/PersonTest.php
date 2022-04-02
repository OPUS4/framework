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
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest;

use DateTime;
use Opus\Common\Model\ModelException;
use Opus\Date;
use Opus\Db\Persons;
use Opus\Db\TableGateway;
use Opus\Document;
use Opus\Model\Xml\Cache;
use Opus\Person;
use Opus\Title;
use OpusTest\TestAsset\TestCase;

use function array_column;
use function array_push;
use function array_unique;
use function count;
use function in_array;
use function is_array;
use function sleep;
use function trim;

/**
 * Test cases for class Opus\Person.
 *
 * @package Opus
 * @category Tests
 * @group PersonTest
 */
class PersonTest extends TestCase
{
    /**
     * List of Opus\Person identifiers having the role Author.
     *
     * @var array
     */
    private $authors = [];

    /**
     * List of test documents.
     *
     * @var array
     */
    private $documents = [];

    /**
     * Set up test data documents and persons.
     */
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false);

        // create documents
        for ($i = 0; $i < 10; $i++) {
            $doc = new Document();
            $doc->store();
            $this->documents[] = $doc;
        }

        for ($i = 0; $i < 10; $i++) {
            $p = new Person();
            $p->setFirstName("Dummy-$i")
                ->setLastName("Empty-$i")
                ->store();
        }

        // add a person as author to every document
        // and add the person to the list of authors
        foreach ($this->documents as $document) {
            $p = new Person();
            $p->setFirstName('Rainer')
                ->setLastName('Zufall')
                ->setAcademicTitle('Prof. Dr.')
                ->store();
            $this->authors[] = $p;
            $document->addPersonAuthor($p);
            $document->store();
        }
    }

    /**
     * Get all documents for a given role.
     */
    public function testGetDocumentsByRole()
    {
        // TODO: $doc->getPersonAuthor()->getId() gibt nicht die Id eines
        // TODO: Autors zurueck, sondern das Paar (document_id, person_id) aus
        // TODO: der Tabelle link_persons_documents.
        //
        // TODO: Die ID der Person erhält man mit getLinkedModelId()

        foreach ($this->authors as $author) {
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
     */
    public function testGetAllPersonIdsByRole()
    {
        $ids = Person::getAllIdsByRole('author');

        $this->assertTrue(is_array($ids), 'No array returned.');

        foreach ($this->authors as $author) {
            $this->assertTrue(
                in_array($author->getId(), $ids),
                'Author id not found.'
            );
        }
    }

    public function testDeletePerson()
    {
        $docId   = $this->documents[0]->getId();
        $d       = new Document($docId);
        $persons = $d->getPerson();
        $this->assertTrue(1 === count($persons));

        $person = $persons[0];
        $this->assertTrue($person->getFirstName() === 'Rainer');
        $this->assertTrue($person->getLastName() === 'Zufall');

        $d->setPerson([]);
        $d->store();

        $d = new Document($docId);
        $this->assertTrue(0 === count($d->getPerson()));
    }

    public function testOnlyLastNameMandatory()
    {
        $person = new Person();

        $fields = $person->describe();

        foreach ($fields as $fieldName) {
            $field = $person->getField($fieldName);

            if ($fieldName === 'LastName') {
                $this->assertTrue($field->isMandatory(), "'$fieldName' should be mandatory.");
            } else {
                $this->assertFalse($field->isMandatory(), "'$fieldName' should not be mandatory.");
            }
        }
    }

    public function testGetNameForLastAndFirstName()
    {
        $person = new Person();

        $person->setFirstName('Jane');
        $person->setLastName('Doe');

        $this->assertEquals('Doe, Jane', $person->getName());
    }

    public function testGetNameForLastNameOnly()
    {
        $person = new Person();

        $person->setLastName('Doe');

        $this->assertEquals('Doe', $person->getName());
    }

    public function testSetGetIdentifiers()
    {
        $person = new Person();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('http://orcid.org/0000-0002-1694-233X');
        $person->setIdentifierGnd('test_gnd_identifier');
        $person->setIdentifierMisc('myid');

        $personId = $person->store();

        $person = new Person($personId);

        $this->assertEquals('http://orcid.org/0000-0002-1694-233X', $person->getIdentifierOrcid());
        $this->assertEquals('test_gnd_identifier', $person->getIdentifierGnd());
        $this->assertEquals('myid', $person->getIdentifierMisc());
    }

    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache()
    {
        $person = new Person();
        $person->setFirstName('Jane');
        $person->setLastName('Doe');
        $person->store();
        $doc = new Document();
        $doc->setType("article")
                ->setServerState('published')
                ->setPersonAuthor($person);
        $docId = $doc->store();

        $xmlCache = new Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $person->setFirstName('John');
        $person->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }

    public function testGetAllPersons()
    {
        $persons = Person::getAllPersons();

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
        $persons = Person::getAllPersons(null, 0, 1);

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Empty-0', $person['last_name']);
        $this->assertEquals('Dummy-0', $person['first_name']);

        $persons = Person::getAllPersons(null, 10, 1);

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Zufall', $person['last_name']);
        $this->assertEquals('Rainer', $person['first_name']);

        $persons = Person::getAllPersons(null, 2, 4);

        $this->assertInternalType('array', $persons);
        $this->assertCount(4, $persons);

        $person = $persons[0];

        $this->assertEquals('Empty-2', $person['last_name']);
        $this->assertEquals('Dummy-2', $person['first_name']);
    }

    public function testGetAllPersonsInRole()
    {
        $persons = Person::getAllPersons('author');

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Zufall', $person['last_name']);
        $this->assertEquals('Rainer', $person['first_name']);

        $persons = Person::getAllPersons('other');

        $this->assertCount(0, $persons);

        $docId = $this->documents[0]->getId();

        $doc   = new Document($docId);
        $other = new Person();
        $other->setLastName('Musterfrau');
        $other->setFirstName('Erika');
        $doc->addPersonOther($other);
        $doc->store();

        $persons = Person::getAllPersons('other');

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
        $persons = Person::getAllPersons('cook');

        $this->assertInternalType('array', $persons);
        $this->assertCount(0, $persons);
    }

    public function testGetAllPersonsSorting()
    {
        $doc = new Document($this->documents[0]->getId());

        $person = new Person();
        $person->setLastName('Blau');
        $doc->addPersonReferee($person);

        $person = new Person();
        $person->setLastName('Rot');
        $doc->addPersonReferee($person);

        $person = new Person();
        $person->setLastName('Grün');
        $doc->addPersonReferee($person);

        $doc->store();

        $persons = Person::getAllPersons('referee');

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
        $doc = new Document($this->documents[0]->getId());

        $person = new Person();
        $person->setLastName('A');
        $doc->addPersonReferee($person);

        $person = new Person();
        $person->setLastName('B');
        $doc->addPersonReferee($person);

        $person = new Person();
        $person->setLastName('C');
        $doc->addPersonReferee($person);

        $doc->store();

        // add leading space to Person 'C' (framework trims leadings spaces - OPUSVIER-3832)
        $table    = TableGateway::getInstance(Persons::class);
        $database = $table->getAdapter();
        $table->update(['last_name' => ' B'], [$database->quoteInto('last_name = ?', 'B')]);

        $persons = Person::getAllPersons('referee');

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
        $doc = new Document($this->documents[0]->getId());

        $person1 = new Person();
        $person1->setLastName('Person');
        $doc->addPersonReferee($person1);

        $person2 = new Person();
        $person2->setLastName('Person');
        $doc->addPersonReferee($person2);

        $person3 = new Person();
        $person3->setLastName('Person');
        $doc->addPersonReferee($person3);

        $doc->store();

        $persons = Person::getAllPersons('referee');

        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $person3->setIdentifierMisc('123');
        $person3->store();

        $persons = Person::getAllPersons('referee');

        $this->assertCount(2, $persons);

        $person2->setIdentifierGnd('654');
        $person2->store();

        $persons = Person::getAllPersons('referee');

        $this->assertCount(3, $persons);
    }

    public function testGetAllPersonsLeadingSpacesMerged()
    {
        $personsSetup = [
            'Mueller'  => [],
            ' Mueller' => [],
        ];

        $this->createPersons($personsSetup);

        $persons = Person::getAllPersons(null, 0, 0, 'Mueller');

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(1, $persons);

        $persons = Person::getAllPersons();

        $this->assertInternalType('array', $persons);
        $this->assertCount(12, $persons);
    }

    public function testGetPersonRoles()
    {
        $roles = Person::getPersonRoles(['last_name' => 'Zufall', 'first_name' => 'Rainer']);

        $this->assertInternalType('array', $roles);
        $this->assertCount(1, $roles);

        $role = $roles[0];

        $this->assertInternalType('array', $role);
        $this->assertArrayHasKey('role', $role);
        $this->assertEquals('author', $role['role']);
        $this->assertArrayHasKey('documents', $role);
        $this->assertEquals(10, $role['documents']);

        $doc    = new Document($this->documents[0]->getId());
        $person = new Person();
        $person->setLastName('Zufall');
        $person->setFirstName('Rainer');
        $doc->addPersonOther($person);
        $doc->store();

        $roles = Person::getPersonRoles(['last_name' => 'Zufall', 'first_name' => 'Rainer']);

        $this->assertInternalType('array', $roles);
        $this->assertCount(2, $roles);
    }

    public function testGetPersonDocuments()
    {
        $documents = Person::getPersonDocuments(['last_name' => 'Zufall', 'first_name' => 'Rainer']);

        $this->assertInternalType('array', $documents);
        $this->assertCount(10, $documents);

        $doc    = new Document($this->documents[0]->getId());
        $person = new Person();
        $person->setLastName('Zufall');
        $doc->addPersonOther($person);
        $doc->store();

        $documents = Person::getPersonDocuments(['last_name' => 'Zufall']);

        $this->assertInternalType('array', $documents);
        $this->assertCount(1, $documents);
        $this->assertEquals($this->documents[0]->getId(), $documents[0]);
    }

    public function testGetPersonDocumentsByState()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $documents = Person::getPersonDocuments($person);

        $this->assertCount(10, $documents);

        $documents = Person::getPersonDocuments($person, 'unpublished');

        $this->assertCount(10, $documents);

        for ($i = 0; $i < 5; $i++) {
            $doc = $this->documents[$i];
            $doc->setServerState('audited');
            $doc->store();
        }

        $documents = Person::getPersonDocuments($person, 'audited');

        $this->assertCount(5, $documents);

        $documents = Person::getPersonDocuments($person, 'unpublished');

        $this->assertCount(5, $documents);
    }

    public function testGetPersonDocumentsDistinct()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $documents = Person::getPersonDocuments($person);

        $this->assertCount(10, $documents);

        $person2 = new Person();
        $person2->setLastName('Zufall');
        $this->documents[0]->addPersonAuthor($person2);
        $this->documents[0]->store();

        $documents = Person::getPersonDocuments($person);

        $this->assertNotCount(11, $documents);
        $this->assertCount(10, $documents);
    }

    public function testGetPersonDocumentsSortedById()
    {
        $doc1   = new Document();
        $person = new Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2   = new Document();
        $person = new Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $documents = Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'id', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'id', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByType()
    {
        $doc1 = new Document();
        $doc1->setType('article');
        $person = new Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2 = new Document();
        $doc2->setType('dissertation');
        $person = new Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $documents = Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'docType', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'docType', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByPublicationDate()
    {
        $doc1 = new Document();
        $date = new Date(new DateTime());
        $doc1->setServerDatePublished($date);
        $person = new Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        sleep(2);

        $doc2 = new Document();
        $date = new Date(new DateTime());
        $doc2->setServerDatePublished($date);
        $person = new Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $documents = Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'publicationDate', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'publicationDate', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByTitle()
    {
        $doc1  = new Document();
        $title = $doc1->addTitleMain();
        $title->setValue('A Title');
        $title->setLanguage('eng');
        $person = new Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2  = new Document();
        $title = $doc2->addTitleMain();
        $title->setValue('B Title');
        $title->setLanguage('eng');
        $person = new Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $documents = Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'title', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'title', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByAuthor()
    {
        $this->markTestSkipped('TODO - sorting by author not properly working yet OPUSVIER-3810');
        $doc1   = new Document();
        $person = new Person();
        $person->setLastName('A Person');
        $personLink = $doc1->addPersonAuthor($person);
        $personLink->setSortOrder(0);
        $person = new Person();
        $person->setLastName('Testy');
        $personLink = $doc1->addPersonAuthor($person);
        $personLink->setSortOrder(1);
        $docId1 = $doc1->store();

        $doc2   = new Document();
        $person = new Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $documents = Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'author', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Person::getPersonDocuments($personMatch, null, null, 'author', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsByStateSorted()
    {
        $doc1  = new Document();
        $title = $doc1->addTitleMain();
        $title->setValue('A Title');
        $title->setLanguage('eng');
        $person = new Person();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2  = new Document();
        $title = $doc2->addTitleMain();
        $title->setValue('B Title');
        $title->setLanguage('eng');
        $person = new Person();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $documents = Person::getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = Person::getPersonDocuments($personMatch, 'unpublished', null, 'title', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = Person::getPersonDocuments($personMatch, 'unpublished', null, 'title', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsByRole()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $documents = Person::getPersonDocuments($person, null, 'author');

        $this->assertCount(10, $documents);

        $documents = Person::getPersonDocuments($person, null, 'editor');

        $this->assertCount(0, $documents);

        $doc = $this->documents[0];

        $editor = new Person();
        $editor->setLastName('Zufall');
        $editor->setFirstName('Rainer');
        $doc->addPersonEditor($editor);
        $doc->store();

        $documents = Person::getPersonDocuments($person, null, 'editor');

        $this->assertCount(1, $documents);
    }

    public function testGetPersonDocumentsByStateAndRole()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $documents = Person::getPersonDocuments($person, 'unpublished', 'author');
        $this->assertCount(10, $documents);

        $documents = Person::getPersonDocuments($person, 'published', 'author');
        $this->assertCount(0, $documents);

        $documents = Person::getPersonDocuments($person, 'unpublished', 'editor');
        $this->assertCount(0, $documents);

        $doc = $this->documents[0];

        $doc->setServerState('published');
        $editor = new Person();
        $editor->setLastName('Zufall');
        $editor->setFirstName('Rainer');
        $doc->addPersonEditor($editor);
        $doc->store();

        $documents = Person::getPersonDocuments($person, 'published', 'editor');
        $this->assertCount(1, $documents);
    }

    public function testGetAllPersonsWithFilter()
    {
        $persons = Person::getAllPersons(null, 0, 0);

        $this->assertCount(11, $persons);

        $persons = Person::getAllPersons(null, 0, 0, 'fal');

        $this->assertCount(1, $persons);
        $this->assertEquals('Zufall', $persons[0]['last_name']);

        $persons = Person::getAllPersons(null, 0, 0, 'pty');

        $this->assertCount(10, $persons);
    }

    public function testGetAllPersonsWithFilterFirstName()
    {
        $doc    = new Document($this->documents[0]->getId());
        $person = new Person();
        $person->setLastName('Mustermann');
        $person->setFirstName('Bafala');
        $doc->addPersonOther($person);
        $doc->store();

        $persons = Person::getAllPersons(null, 0, 0, 'fal');

        $this->assertCount(2, $persons);
    }

    public function testGetAllPersonsWithFilterCaseInsensitive()
    {
        $persons = Person::getAllPersons(null, 0, 0, 'FAL');

        $this->assertCount(1, $persons);

        $persons = Person::getAllPersons(null, 0, 0, 'uFa');

        $this->assertCount(1, $persons);
    }

    public function testGetAllPersonsCount()
    {
        $persons = Person::getAllPersons();
        $count   = Person::getAllPersonsCount();

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(11, $count);

        $persons = Person::getAllPersons('author');
        $count   = Person::getAllPersonsCount('author');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(1, $count);

        $persons = Person::getAllPersons('author', 0, 0, 'fal');
        $count   = Person::getAllPersonsCount('author', 'fal');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(1, $count);

        $persons = Person::getAllPersons(null, 0, 0, 'emp');
        $count   = Person::getAllPersonsCount(null, 'emp');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(10, $count);
    }

    public function testGetAllPersonsCountBug()
    {
        $person = new Person();
        $person->setLastName(' Zufall  ');
        $person->setFirstName(' Rainer ');
        $person->setAcademicTitle('Prof.');
        $person->store();

        $person = new Person();
        $person->setLastName(' Zufall');
        $person->setFirstName(' Rainer ');
        $person->store();

        $count = Person::getAllPersonsCount();

        $this->assertEquals(11, $count);
    }

    public function testOpusId()
    {
        $person = new Person();
        $person->setLastName('Testy');
        $person->setOpusId('12345');
        $personId = $person->store();

        $person = new Person($personId);

        $this->assertEquals('12345', $person->getOpusId());
        $this->assertEquals('Testy', $person->getLastName());
    }

    public function testGetPersonValues()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $values = Person::getPersonValues($personCrit);

        $this->assertCount(11, $values);

        $this->assertArrayHasKey('id', $values);

        $personIds = $values['id'];

        $this->assertInternalType('array', $personIds);
        $this->assertCount(10, $personIds);

        $this->assertArrayHasKey('last_name', $values);
        $this->assertInternalType('string', $values['last_name']);
        $this->assertEquals('Zufall', $values['last_name']);

        foreach ($values as $key => $value) {
            if ($key !== 'id') {
                $this->assertNotInternalType('array', $value);
            }
        }

        $person = new Person($personIds[0]);
        $person->setPlaceOfBirth('Hamburg');
        $person->store();

        $values = Person::getPersonValues($personCrit);

        $this->assertArrayHasKey('place_of_birth', $values);
        $this->assertInternalType('array', $values['place_of_birth']);
        $this->assertCount(2, $values['place_of_birth']);
    }

    public function testGetPersonValuesWithExtraSpaces()
    {
        $names = [
            'Spacey'     => [
                'Email' => 'test@example.org',
            ],
            '  Spacey'   => [
                'Email' => 'spacey@example.org',
            ],
            'Spacey  '   => [],
            '  Spacey  ' => [],
            ' Spacey '   => [],
            '  spacey  ' => [
                'AcademicTitle' => 'Prof.',
            ],
        ];

        $personIds = $this->createPersons($names);

        $personCrit = [
            'last_name' => 'Spacey',
        ];

        $values = Person::getPersonValues($personCrit);

        $this->assertNotNull($values);
        $this->assertInternalType('array', $values);

        $this->assertArrayHasKey('id', $values);
        $this->assertInternalType('array', $values['id']);
        $this->assertCount(6, $values['id']);

        foreach ($personIds as $personId) {
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
        $this->assertContains(null, $emails);

        $this->assertArrayHasKey('academic_title', $values);
        $this->assertInternalType('array', $values['academic_title']);
        $this->assertCount(2, $values['academic_title']);
        $this->assertContains('Prof.', $values['academic_title']);
        $this->assertContains(null, $values['academic_title']);
    }

    public function testGetPersonValuesNotFound()
    {
        $values = Person::getPersonValues(['last_name' => 'doesnotexist']);

        $this->assertNull($values);
    }

    public function testCreatePersonTestFunction()
    {
        $personValues = [
            ' Spacey ' => [],
        ];

        $personIds = $this->createPersons($personValues);

        $this->assertNotNull($personIds);
        $this->assertInternalType('array', $personIds);
        $this->assertCount(1, $personIds);

        $person = new Person($personIds[0]);

        $this->assertEquals(' Spacey ', $person->getLastName());
    }

    public function testUpdateAll()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        Person::updateAll($personCrit, $changes);

        foreach ($this->authors as $author) {
            $person = new Person($author->getId());

            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }
    }

    public function testUpdateAllForSpecifiedDocuments()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        $documents = [2, 4, 7];

        Person::updateAll($personCrit, $changes, $documents);

        foreach ($this->authors as $author) {
            $person = new Person($author->getId());

            $personDocs = $person->getDocumentsByRole('author');

            $this->assertCount(1, $personDocs);

            $docId = $personDocs[0]->getId();

            if (in_array($docId, $documents)) {
                $this->assertEquals('bulktest@example.org', $person->getEmail());
            } else {
                $this->assertNull($person->getEmail());
            }
        }
    }

    public function testUpdateAllForDocumentWithoutMatchingPerson()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        $doc   = new Document();
        $title = new Title();
        $title->setLanguage('deu');
        $title->setValue('Document with no author');
        $doc->addTitleMain($title);
        $docId = $doc->store();

        $doc = new Document($docId);

        $documents = [3, 5, $docId];

        $lastModified = $doc->getServerDateModified();

        sleep(2);

        $now = new Date();
        $now->setNow();

        sleep(2);

        // new ServerDateModified should be past $now
        Person::updateAll($personCrit, $changes, $documents);

        // document without matching author was not modified
        $this->assertEquals($lastModified, $doc->getServerDateModified());

        //filtered documents were not modified
        foreach ($this->documents as $doc) {
            $document = new Document($doc->getId()); // don't use old objects - they are not updated

            $dateModified = $document->getServerDateModified();

            if (in_array($document->getId(), [3, 5])) {
                $this->assertGreaterThan($now->getUnixTimestamp(), $dateModified->getUnixTimestamp());
            } else {
                $this->assertLessThan($now->getUnixTimestamp(), $dateModified->getUnixTimestamp());
            }
        }
    }

    public function testUpdateAllNoChanges()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [];

        $lastModified = $this->documents[0]->getServerDateModified();

        sleep(2);

        Person::updateAll($personCrit, $changes);

        $this->assertEquals($lastModified, $this->documents[0]->getServerDateModified());
    }

    public function testUpdateAllBadChanges()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = ['IdentifierIntern' => 'id1234']; // only Identifier(Orcid|Gnd|Misc) exist

        $lastModified = $this->documents[0]->getServerDateModified();

        sleep(2);

        $this->setExpectedException(ModelException::class, 'unknown field \'IdentifierIntern\' for update');

        Person::updateAll($personCrit, $changes);

        $this->assertEquals($lastModified, $this->documents[0]->getServerDateModified());
    }

    public function testUpdateAllWithSpaces()
    {
        $personCrit = ['last_name' => 'Tester', 'first_name' => 'Usual'];

        $persons = [
            'Tester'   => [
                'FirstName' => 'Usual',
            ],
            '  Tester' => [
                'FirstName' => 'Usual  ',
            ],
            'Tester  ' => [
                'FirstName' => '  Usual',
            ],
            ' Tester ' => [
                'FirstName' => ' Usual ',
            ],
            'Tester '  => [],
        ];

        $personIds = $this->createPersons($persons);

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        Person::updateAll($personCrit, $changes);

        for ($index = 0; $index < 4; $index++) {
            $person = new Person($personIds[$index]);
            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }

        $person = new Person($personIds[4]);
        $this->assertNull($person->getEmail());
    }

    public function testUpdateAllValuesAreTrimmed()
    {
        $personCrit = ['last_name' => 'Tester'];

        $persons = ['Tester' => []];

        $personIds = $this->createPersons($persons);

        $changes = [
            'FirstName' => ' John ',
        ];

        Person::updateAll($personCrit, $changes);

        $person = new Person($personIds[0]);

        $this->assertEquals('John', $person->getFirstName());
    }

    public function testUpdateAllWithoutDocuments()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        $documents = null;

        Person::updateAll($personCrit, $changes, $documents);

        foreach ($this->authors as $author) {
            $person = new Person($author->getId());

            $personDocs = $person->getDocumentsByRole('author');

            $this->assertCount(1, $personDocs);
            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }
    }

    public function testUpdateAllWithoutDocumentsInArray()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        $documents = [];

        Person::updateAll($personCrit, $changes, $documents);

        foreach ($this->authors as $author) {
            $person = new Person($author->getId());

            $personDocs = $person->getDocumentsByRole('author');

            $this->assertCount(1, $personDocs);
            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }
    }

    public function testGetPersonsAndDocumentsWithSpaces()
    {
        $personCrit = ['last_name' => 'Tester', 'first_name' => 'Usual'];

        $persons = [
            'Tester'   => [
                'FirstName' => 'Usual',
            ],
            '  Tester' => [
                'FirstName' => 'Usual  ',
            ],
            'Tester  ' => [
                'FirstName' => '  Usual',
            ],
            ' Tester ' => [
                'FirstName' => ' Usual ',
            ],
            'Tester '  => [],
        ];

        $personIds = $this->createPersons($persons);

        $personDocs = Person::getPersonsAndDocuments($personCrit);

        $this->markTestIncomplete('TODO finish');
    }

    public function testGetPersons()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $person = new Person();
        $person->setLastName('Zufall');
        $person->store(); // not Rainer

        $personIds = Person::getPersons($personCrit);

        $this->assertNotNull($personIds);
        $this->assertInternalType('array', $personIds);
        $this->assertCount(10, $personIds);
    }

    public function testGetPersonsForDocuments()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $documentIds = [2, 4, 7, 8];

        $personIds = Person::getPersons($personCrit, $documentIds);

        $this->assertNotNull($personIds);
        $this->assertInternalType('array', $personIds);
        $this->assertCount(4, $personIds);

        foreach ($personIds as $personId) {
            $person = new Person($personId);

            $documents = $person->getDocumentsByRole('author');

            $this->assertCount(1, $documents);

            $this->assertContains($documents[0]->getId(), $documentIds);
        }
    }

    public function testGetPersonsForDocumentsBadIds()
    {
        $personCrit = ['first_name' => 'Rainer'];

        $persons = Person::getPersons($personCrit, [33, 34]);

        $this->assertCount(0, $persons);
    }

    public function testGetPersonsForDocumentsCaseInsensitive()
    {
        $personCrit = ['last_name' => 'zuFall', 'first_name' => 'Rainer'];

        $documentIds = [2, 3, 4];

        $personIds = Person::getPersons($personCrit, $documentIds);

        $this->assertCount(3, $personIds);

        foreach ($personIds as $personId) {
            $person = new Person($personId);

            $this->assertEquals('Zufall', $person->getLastName());
        }
    }

    public function testUpdateAllChangeLastName()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = ['LastName' => 'Plannt', 'FirstName' => 'Volge'];

        Person::updateAll($personCrit, $changes);

        foreach ($this->authors as $author) {
            $person = new Person($author->getId());

            $this->assertEquals('Plannt', $person->getLastName());
            $this->assertEquals('Volge', $person->getFirstName());
        }
    }

    public function testConvertChanges()
    {
        $changes = [
            'LastName'  => 'Zufall',
            'FirstName' => 'Rainer',
            'Email'     => 'example@example.org',
        ];

        $result = Person::convertChanges($changes);

        $this->assertEquals([
            'last_name'  => 'Zufall',
            'first_name' => 'Rainer',
            'email'      => 'example@example.org',
        ], $result);
    }

    public function testConvertToFieldNames()
    {
        $values = [
            'last_name'  => 'Zufall',
            'first_name' => 'Rainer',
            'email'      => 'example@example.org',
        ];

        $result = Person::convertToFieldNames($values);

        $this->assertEquals([
            'LastName'  => 'Zufall',
            'FirstName' => 'Rainer',
            'Email'     => 'example@example.org',
        ], $result);
    }

    public function testMatches()
    {
        $criteria = ['LastName' => 'Zufall'];

        $person = $this->authors[0];

        $this->assertFalse($person->matches($criteria));

        $criteria['FirstName'] = 'Rainer';

        $this->assertTrue($person->matches($criteria));
    }

    /**
     * @param array $persons
     * @return array
     * @throws ModelException
     */
    protected function createPersons($persons)
    {
        $personIds = [];

        $table    = TableGateway::getInstance(Persons::class);
        $database = $table->getAdapter();

        foreach ($persons as $name => $values) {
            $person = new Person();
            $person->setLastName($name);

            foreach ($values as $fieldName => $value) {
                $person->getField($fieldName)->setValue($value);
            }

            $personId = $person->store();

            array_push($personIds, $personId);

            // check if there are extra spaces
            if ($name !== trim($name)) {
                $table->update(
                    ['last_name' => $name],
                    [$database->quoteInto('id = ?', $personId)]
                );
            }
        }

        return $personIds;
    }

    public function testGetPersonsAndDocuments()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $personDocs = Person::getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(10, $personDocs);

        foreach ($personDocs as $match) {
            $this->assertInternalType('array', $match);
            $this->assertCount(2, $match);
            $this->assertArrayHasKey('person_id', $match);
            $this->assertArrayHasKey('document_id', $match);
            $personId  = $match['person_id'];
            $docId     = $match['document_id'];
            $person    = new Person($personId);
            $assocDocs = $person->getDocumentIds();
            $this->assertContains($docId, $assocDocs);
        }

        $personIds   = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(10, $personIds);
        $this->assertCount(10, $documentIds);
    }

    public function testGetPersonsAndDocumentsForSubset()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $docSet = [2, 5, 6, 10, 99]; // document 99 does not exist

        $personDocs = Person::getPersonsAndDocuments($personCrit, $docSet);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(4, $personDocs);

        $documentIds = array_column($personDocs, 'document_id');

        $this->assertContains(2, $documentIds);
        $this->assertContains(5, $documentIds);
        $this->assertContains(6, $documentIds);
        $this->assertContains(10, $documentIds);

        $doc = new Document($this->documents[1]->getId());

        $this->assertEquals(2, $doc->getId());

        $doc->setPerson(null); // remove all persons
        $doc->store();

        $personDocs = Person::getPersonsAndDocuments($personCrit, $docSet);

        $this->assertCount(3, $personDocs);

        $documentIds = array_column($personDocs, 'document_id');

        $this->assertNotContains(2, $documentIds);
    }

    public function testGetPersonsAndDocumentsMultiplePersonsOnDocument()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $doc = new Document($this->documents[0]->getId());

        $person = new Person();
        $person->setLastName('Zufall');
        $person->setFirstName('Rainer');

        $doc->addPersonOther($person);
        $doc->store();

        $personDocs = Person::getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(11, $personDocs);

        $personIds   = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(11, $personIds);
        $this->assertCount(10, array_unique($documentIds));
    }

    public function testGetPersonsAndDocumentsMultipleDocumentsOnPerson()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $doc = new Document();
        $doc->setType('article');
        $title = new Title();
        $title->setLanguage('eng');
        $title->setValue('Test document');
        $doc->addTitleMain($title);
        $doc->addPersonOther($this->authors[0]);
        $doc->store();

        $personDocs = Person::getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertInternalType('array', $personDocs);
        $this->assertCount(11, $personDocs);

        $personIds   = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(10, array_unique($personIds));
        $this->assertCount(11, $documentIds);
    }

    public function testGetDocumentIds()
    {
        $person = new Person($this->authors[0]->getId());

        $docIds = $person->getDocumentIds();

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains(1, $docIds);
    }

    public function testGetDocumentIdsUniqueValues()
    {
        $person = new Person($this->authors[0]->getId());
        $doc    = new Document($this->documents[0]->getId());

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
        $person = new Person($this->authors[0]->getId());

        $docIds = $person->getDocumentIds('author');

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains($this->documents[0]->getId(), $docIds);

        $docIds = $person->getDocumentIds('advisor');

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(0, $docIds);

        $this->documents[0]->addPersonAdvisor($person);
        $this->documents[0]->store();

        $docIds = $person->getDocumentIds('advisor');

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains($this->documents[0]->getId(), $docIds);
    }

    public function testGetDocuments()
    {
        $personIds = [
            $this->authors[0]->getId(),
            $this->authors[4]->getId(),
        ];

        $documentIds = Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsBadArgument()
    {
        $personIds = [
            $this->authors[0]->getId(),
            $this->authors[0]->getId(), // same person id twice
            $this->authors[4]->getId(),
            999, // unknown person
        ];

        $documentIds = Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsOnePersonTwoDocuments()
    {
        $doc = new Document($this->documents[1]->getId());
        $doc->addPersonSubmitter($this->authors[0]);
        $docId = $doc->store();

        $personIds = [
            $this->authors[0]->getId(),
            $this->authors[4]->getId(),
        ];

        $documentIds = Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(3, $documentIds);
        $this->assertContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
        $this->assertContains($docId, $documentIds);
    }

    public function testGetDocumentsTwoPersonsOneDocument()
    {
        $doc    = new Document($this->documents[0]->getId());
        $person = new Person();
        $person->setLastName('Tester');
        $plink = $doc->addPersonSubmitter($person);
        $doc->store();

        $personIds = [
            $this->authors[0]->getId(), // document 0
            $this->authors[4]->getId(),
            $plink->getModel()->getId(), // document 0
        ];

        $documentIds = Person::getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsFilterIds()
    {
        $personIds = [
            $this->authors[0]->getId(),
            $this->authors[4]->getId(),
        ];

        $allowedDocuments = [
            $this->documents[4]->getId(),
        ];

        $documentIds = Person::getDocuments($personIds, $allowedDocuments);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(1, $documentIds);
        $this->assertNotContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsEmptyDocumentsParam()
    {
        $personIds = [
            $this->authors[0]->getId(),
            $this->authors[4]->getId(),
        ];

        $allowedDocuments = []; // should be handled like null

        $documentIds = Person::getDocuments($personIds, $allowedDocuments);

        $this->assertNotNull($documentIds);
        $this->assertInternalType('array', $documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
    }

    public function testPersonRolesWithSpacesAroundParameterValues()
    {
        $personCrit = [
            'last_name'  => ' Zufall ',
            'first_name' => ' Rainer ',
        ];

        $persons = Person::getPersons($personCrit);

        $this->assertNotNull($persons);
        $this->assertInternalType('array', $persons);
        $this->assertCount(10, $persons);
    }

    public function testStoreValuesAreTrimmed()
    {
        $person = new Person();
        $person->setLastName(' Zufall ');
        $person->setFirstName(' Rainer ');
        $personId = $person->store();

        $person = new Person($personId);

        $this->assertEquals('Zufall', $person->getLastName());
        $this->assertEquals('Rainer', $person->getFirstName());
    }

    /**
     * OPUSVIER-3764
     */
    public function testDeleteAssignedPerson()
    {
        $this->markTestIncomplete('TODO not sure what/how to test');

        $doc = new Document();
        $doc->setServerState('published');
        $doc->setType('article');

        $person = new Person();
        $person->setLastName('Tester');

        $doc->addPersonAuthor($person);

        $docId = $doc->store();

        $person->delete();

        // $doc = new Document($docId);

        $doc->delete();

        $this->setExpectedException(ModelException::class, 'No Opus\Db\Documents with id');
        new Document($docId);
    }

    public function testSortOrderDefault()
    {
        $doc = new Document();

        $person = new Person();
        $person->setLastName('Person1');
        $doc->addPersonAuthor($person);

        $person = new Person();
        $person->setLastName('Person2');
        $doc->addPersonAuthor($person);

        $docId = $doc->store();

        $doc = new Document($docId);

        $authors = $doc->getPersonAuthor();

        $this->assertNotNull($authors);
        $this->assertCount(2, $authors);

        $this->assertEquals(1, $authors[0]->getSortOrder());
        $this->assertEquals('Person1', $authors[0]->getLastName());
        $this->assertEquals(2, $authors[1]->getSortOrder());
        $this->assertEquals('Person2', $authors[1]->getLastName());
    }

    public function testMatchesPersonObjects()
    {
        $person1 = new Person();
        $person2 = new Person();

        // Test LastName matching

        $person1->setLastName('Doe');

        $this->assertFalse($person1->matches($person2));
        $this->assertFalse($person2->matches($person1));

        $person2->setLastName('Doe');

        $this->assertTrue($person1->matches($person2));
        $this->assertTrue($person2->matches($person1));

        // Test FirstName matching

        $person1->setFirstName('Jane');

        $this->assertFalse($person1->matches($person2));
        $this->assertFalse($person2->matches($person1));

        $person2->setFirstName('Jane');

        $this->assertTrue($person1->matches($person2));
        $this->assertTrue($person2->matches($person1));

        // Test IdentifierMisc matching

        $person1->setIdentifierMisc('1234');

        $this->assertFalse($person1->matches($person2));
        $this->assertFalse($person2->matches($person1));

        $person2->setIdentifierMisc('1234');

        $this->assertTrue($person1->matches($person2));
        $this->assertTrue($person2->matches($person1));

        // Test IdentifierOrcid matching

        $person1->setIdentifierOrcid('0000-0000-1234-5678');

        $this->assertFalse($person1->matches($person2));
        $this->assertFalse($person2->matches($person1));

        $person2->setIdentifierOrcid('0000-0000-1234-5678');

        $this->assertTrue($person1->matches($person2));
        $this->assertTrue($person2->matches($person1));

        // Test IdentifierGnd matching

        $person1->setIdentifierGnd('4321');

        $this->assertFalse($person1->matches($person2));
        $this->assertFalse($person2->matches($person1));

        $person2->setIdentifierGnd('4321');

        $this->assertTrue($person1->matches($person2));
        $this->assertTrue($person2->matches($person1));

        // Email does not matter

        $person1->setEmail('test1@example.org');

        $this->assertTrue($person1->matches($person2));
        $this->assertTrue($person2->matches($person1));

        $person2->setEmail('test2@example.org');

        $this->assertTrue($person1->matches($person2));
        $this->assertTrue($person2->matches($person1));
    }

    public function testToArray()
    {
        $person = new Person();
        $person->setAcademicTitle('Prof.');
        $person->setFirstName('Thomas');
        $person->setLastName('Mueller');

        $dateOfBirth = new Date('1960-05-17');
        $person->setDateOfBirth($dateOfBirth);
        $dateOfBirthArray = $dateOfBirth->toArray();

        $person->setPlaceOfBirth('München');
        $person->setEmail('mueller@example.org');
        $person->setOpusId('2');
        $person->setIdentifierOrcid('0000-0000-0000-0002');
        $person->setIdentifierGnd('123456789');
        $person->setIdentifierMisc('B');

        $data = $person->toArray();

        $this->assertEquals([
            'AcademicTitle'   => 'Prof.',
            'DateOfBirth'     => $dateOfBirthArray,
            'PlaceOfBirth'    => 'München',
            'FirstName'       => 'Thomas',
            'LastName'        => 'Mueller',
            'Email'           => 'mueller@example.org',
            'IdentifierOrcid' => '0000-0000-0000-0002',
            'IdentifierGnd'   => '123456789',
            'IdentifierMisc'  => 'B',
            'OpusId'          => '2',
        ], $data);
    }

    public function testFromArray()
    {
        $person = Person::fromArray([
            'AcademicTitle'   => 'Prof.',
            'DateOfBirth'     => '1960-05-17',
            'PlaceOfBirth'    => 'München',
            'FirstName'       => 'Thomas',
            'LastName'        => 'Mueller',
            'Email'           => 'mueller@example.org',
            'IdentifierOrcid' => '0000-0000-0000-0002',
            'IdentifierGnd'   => '123456789',
            'IdentifierMisc'  => 'B',
            'OpusId'          => '2',
        ]);

        $this->assertNotNull($person);
        $this->assertInstanceOf(Person::class, $person);

        $this->assertEquals('Prof.', $person->getAcademicTitle());
        $this->assertEquals('Thomas', $person->getFirstName());
        $this->assertEquals('Mueller', $person->getLastName());
        $this->assertEquals('mueller@example.org', $person->getEmail());
        $this->assertEquals('1960-05-17', $person->getDateOfBirth()->__toString());
        $this->assertEquals('München', $person->getPlaceOfBirth());
        $this->assertEquals('0000-0000-0000-0002', $person->getIdentifierOrcid());
        $this->assertEquals('123456789', $person->getIdentifierGnd());
        $this->assertEquals('B', $person->getIdentifierMisc());
        $this->assertEquals('2', $person->getOpusId());
    }

    public function testUpdateFromArray()
    {
        $person = new Person();

        $person->updateFromArray([
            'AcademicTitle'   => 'Prof.',
            'DateOfBirth'     => '1960-05-17',
            'PlaceOfBirth'    => 'München',
            'FirstName'       => 'Thomas',
            'LastName'        => 'Mueller',
            'Email'           => 'mueller@example.org',
            'IdentifierOrcid' => '0000-0000-0000-0002',
            'IdentifierGnd'   => '123456789',
            'IdentifierMisc'  => 'B',
            'OpusId'          => '2',
        ]);

        $this->assertEquals('Prof.', $person->getAcademicTitle());
        $this->assertEquals('Thomas', $person->getFirstName());
        $this->assertEquals('Mueller', $person->getLastName());
        $this->assertEquals('mueller@example.org', $person->getEmail());
        $this->assertEquals('1960-05-17', $person->getDateOfBirth()->__toString());
        $this->assertEquals('München', $person->getPlaceOfBirth());
        $this->assertEquals('0000-0000-0000-0002', $person->getIdentifierOrcid());
        $this->assertEquals('123456789', $person->getIdentifierGnd());
        $this->assertEquals('B', $person->getIdentifierMisc());
        $this->assertEquals('2', $person->getOpusId());
    }

    public function testGetModelType()
    {
        $person = new Person();
        $this->assertEquals('person', $person->getModelType());
    }
}
