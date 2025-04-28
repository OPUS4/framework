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
 * @copyright   Copyright (c) 2025, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use DateTime;
use Opus\Common\Date;
use Opus\Common\Document;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\ModelRepositoryInterface;
use Opus\Common\Model\NotFoundException;
use Opus\Common\Person;
use Opus\Common\Repository;
use Opus\Db\Persons;
use Opus\Db\TableGateway;
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

class PersonRepositoryTest extends TestCase
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
    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false);

        // create documents
        for ($i = 0; $i < 10; $i++) {
            $doc = Document::new();
            $doc->store();
            $this->documents[] = $doc;
        }

        for ($i = 0; $i < 10; $i++) {
            $p = Person::new();
            $p->setFirstName("Dummy-$i")
                ->setLastName("Empty-$i")
                ->store();
        }

        // add a person as author to every document
        // and add the person to the list of authors
        foreach ($this->documents as $document) {
            $p = Person::new();
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
     * Test if all Person identifer for persons of a given role
     * can be obtained.
     */
    public function testGetAllPersonIdsByRole()
    {
        $personRepository = $this->getPersonRepository();

        $ids = $personRepository->getAllIdsByRole('author');

        $this->assertTrue(is_array($ids), 'No array returned.');

        foreach ($this->authors as $author) {
            $this->assertTrue(
                in_array($author->getId(), $ids),
                'Author id not found.'
            );
        }
    }

    public function testGetAllPersons()
    {
        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons();

        $this->assertIsArray($persons);
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
        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons(null, 0, 1);

        $this->assertIsArray($persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Empty-0', $person['last_name']);
        $this->assertEquals('Dummy-0', $person['first_name']);

        $persons = $personRepository->getAllPersons(null, 10, 1);

        $this->assertIsArray($persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Zufall', $person['last_name']);
        $this->assertEquals('Rainer', $person['first_name']);

        $persons = $personRepository->getAllPersons(null, 2, 4);

        $this->assertIsArray($persons);
        $this->assertCount(4, $persons);

        $person = $persons[0];

        $this->assertEquals('Empty-2', $person['last_name']);
        $this->assertEquals('Dummy-2', $person['first_name']);
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
        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons('cook');

        $this->assertIsArray($persons);
        $this->assertCount(0, $persons);
    }

    public function testGetAllPersonsSorting()
    {
        $doc = Document::get($this->documents[0]->getId());

        $person = Person::new();
        $person->setLastName('Blau');
        $doc->addPersonReferee($person);

        $person = Person::new();
        $person->setLastName('Rot');
        $doc->addPersonReferee($person);

        $person = Person::new();
        $person->setLastName('Grün');
        $doc->addPersonReferee($person);

        $doc->store();

        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons('referee');

        $this->assertIsArray($persons);
        $this->assertCount(3, $persons);

        $this->assertEquals('Blau', $persons[0]['last_name']);
        $this->assertEquals('Grün', $persons[1]['last_name']);
        $this->assertEquals('Rot', $persons[2]['last_name']);
    }

    public function testGetAllPersonsInRole()
    {
        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons('author');

        $this->assertIsArray($persons);
        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Zufall', $person['last_name']);
        $this->assertEquals('Rainer', $person['first_name']);

        $persons = $personRepository->getAllPersons('other');

        $this->assertCount(0, $persons);

        $docId = $this->documents[0]->getId();

        $doc   = Document::get($docId);
        $other = Person::new();
        $other->setLastName('Musterfrau');
        $other->setFirstName('Erika');
        $doc->addPersonOther($other);
        $doc->store();

        $persons = $personRepository->getAllPersons('other');

        $this->assertCount(1, $persons);

        $person = $persons[0];

        $this->assertEquals('Musterfrau', $person['last_name']);
        $this->assertEquals('Erika', $person['first_name']);
    }

    /**
     * Names with leading spaces should not appear at the beginning of the list, but in their proper place.
     */
    public function testGetAllPersonsSortingWithLeadingSpaces()
    {
        $doc = Document::get($this->documents[0]->getId());

        $person = Person::new();
        $person->setLastName('A');
        $doc->addPersonReferee($person);

        $person = Person::new();
        $person->setLastName('B');
        $doc->addPersonReferee($person);

        $person = Person::new();
        $person->setLastName('C');
        $doc->addPersonReferee($person);

        $doc->store();

        // add leading space to Person 'C' (framework trims leadings spaces - OPUSVIER-3832)
        $table    = TableGateway::getInstance(Persons::class);
        $database = $table->getAdapter();
        $table->update(['last_name' => ' B'], [$database->quoteInto('last_name = ?', 'B')]);

        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons('referee');

        $this->assertIsArray($persons);
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
        $doc = Document::get($this->documents[0]->getId());

        $person1 = Person::new();
        $person1->setLastName('Person');
        $doc->addPersonReferee($person1);

        $person2 = Person::new();
        $person2->setLastName('Person');
        $doc->addPersonReferee($person2);

        $person3 = Person::new();
        $person3->setLastName('Person');
        $doc->addPersonReferee($person3);

        $doc->store();

        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons('referee');

        $this->assertIsArray($persons);
        $this->assertCount(1, $persons);

        $person3->setIdentifierMisc('123');
        $person3->store();

        $persons = $personRepository->getAllPersons('referee');

        $this->assertCount(2, $persons);

        $person2->setIdentifierGnd('654');
        $person2->store();

        $persons = $personRepository->getAllPersons('referee');

        $this->assertCount(3, $persons);
    }

    public function testGetAllPersonsLeadingSpacesMerged()
    {
        $personsSetup = [
            'Mueller'  => [],
            ' Mueller' => [],
        ];

        $this->createPersons($personsSetup);

        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons(null, 0, 0, 'Mueller');

        $this->assertNotNull($persons);
        $this->assertIsArray($persons);
        $this->assertCount(1, $persons);

        $persons = $personRepository->getAllPersons();

        $this->assertIsArray($persons);
        $this->assertCount(12, $persons);
    }

    public function testGetPersonRoles()
    {
        $personRepository = $this->getPersonRepository();

        $roles = $personRepository->getPersonRoles(['last_name' => 'Zufall', 'first_name' => 'Rainer']);

        $this->assertIsArray($roles);
        $this->assertCount(1, $roles);

        $role = $roles[0];

        $this->assertIsArray($role);
        $this->assertArrayHasKey('role', $role);
        $this->assertEquals('author', $role['role']);
        $this->assertArrayHasKey('documents', $role);
        $this->assertEquals(10, $role['documents']);

        $doc    = Document::get($this->documents[0]->getId());
        $person = Person::new();
        $person->setLastName('Zufall');
        $person->setFirstName('Rainer');
        $doc->addPersonOther($person);
        $doc->store();

        $roles = $personRepository->getPersonRoles(['last_name' => 'Zufall', 'first_name' => 'Rainer']);

        $this->assertIsArray($roles);
        $this->assertCount(2, $roles);
    }

    public function testGetPersonDocuments()
    {
        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments(['last_name' => 'Zufall', 'first_name' => 'Rainer']);

        $this->assertIsArray($documents);
        $this->assertCount(10, $documents);

        $doc    = Document::get($this->documents[0]->getId());
        $person = Person::new();
        $person->setLastName('Zufall');
        $doc->addPersonOther($person);
        $doc->store();

        $documents = $personRepository->getPersonDocuments(['last_name' => 'Zufall']);

        $this->assertIsArray($documents);
        $this->assertCount(1, $documents);
        $this->assertEquals($this->documents[0]->getId(), $documents[0]);
    }

    public function testGetPersonDocumentsByState()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($person);

        $this->assertCount(10, $documents);

        $documents = $personRepository->getPersonDocuments($person, 'unpublished');

        $this->assertCount(10, $documents);

        for ($i = 0; $i < 5; $i++) {
            $doc = $this->documents[$i];
            $doc->setServerState('audited');
            $doc->store();
        }

        $documents = $personRepository->getPersonDocuments($person, 'audited');

        $this->assertCount(5, $documents);

        $documents = $personRepository->getPersonDocuments($person, 'unpublished');

        $this->assertCount(5, $documents);
    }

    public function testGetPersonDocumentsDistinct()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($person);

        $this->assertCount(10, $documents);

        $person2 = Person::new();
        $person2->setLastName('Zufall');
        $this->documents[0]->addPersonAuthor($person2);
        $this->documents[0]->store();

        $documents = $personRepository->getPersonDocuments($person);

        $this->assertNotCount(11, $documents);
        $this->assertCount(10, $documents);
    }

    public function testGetPersonDocumentsSortedById()
    {
        $doc1   = Document::new();
        $person = Person::new();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2   = Document::new();
        $person = Person::new();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'id', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'id', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByType()
    {
        $doc1 = Document::new();
        $doc1->setType('article');
        $person = Person::new();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2 = Document::new();
        $doc2->setType('dissertation');
        $person = Person::new();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'docType', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'docType', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByPublicationDate()
    {
        $doc1 = Document::new();
        $date = new Date(new DateTime());
        $doc1->setServerDatePublished($date);
        $person = Person::new();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        sleep(2);

        $doc2 = Document::new();
        $date = new Date(new DateTime());
        $doc2->setServerDatePublished($date);
        $person = Person::new();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'publicationDate', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'publicationDate', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByTitle()
    {
        $doc1  = Document::new();
        $title = $doc1->addTitleMain();
        $title->setValue('A Title');
        $title->setLanguage('eng');
        $person = Person::new();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2  = Document::new();
        $title = $doc2->addTitleMain();
        $title->setValue('B Title');
        $title->setLanguage('eng');
        $person = Person::new();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'title', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'title', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsSortedByAuthor()
    {
        $this->markTestSkipped('TODO - sorting by author not properly working yet OPUSVIER-3810');
        $doc1   = Document::new();
        $person = Person::new();
        $person->setLastName('A Person');
        $personLink = $doc1->addPersonAuthor($person);
        $personLink->setSortOrder(0);
        $person = Person::new();
        $person->setLastName('Testy');
        $personLink = $doc1->addPersonAuthor($person);
        $personLink->setSortOrder(1);
        $docId1 = $doc1->store();

        $doc2   = Document::new();
        $person = Person::new();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $documents = $personRepository->getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'author', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = $personRepository->getPersonDocuments($personMatch, null, null, 'author', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsByStateSorted()
    {
        $doc1  = Document::new();
        $title = $doc1->addTitleMain();
        $title->setValue('A Title');
        $title->setLanguage('eng');
        $person = Person::new();
        $person->setLastName('Testy');
        $doc1->addPersonAuthor($person);
        $docId1 = $doc1->store();

        $doc2  = Document::new();
        $title = $doc2->addTitleMain();
        $title->setValue('B Title');
        $title->setLanguage('eng');
        $person = Person::new();
        $person->setLastName('Testy');
        $doc2->addPersonAuthor($person);
        $docId2 = $doc2->store();

        $personMatch = ['last_name' => 'Testy'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($personMatch);

        $this->assertCount(2, $documents);

        $documents = $personRepository->getPersonDocuments($personMatch, 'unpublished', null, 'title', true);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId1, $documents[0]);
        $this->assertEquals($docId2, $documents[1]);

        $documents = $personRepository->getPersonDocuments($personMatch, 'unpublished', null, 'title', false);

        $this->assertCount(2, $documents);

        $this->assertEquals($docId2, $documents[0]);
        $this->assertEquals($docId1, $documents[1]);
    }

    public function testGetPersonDocumentsByRole()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($person, null, 'author');

        $this->assertCount(10, $documents);

        $documents = $personRepository->getPersonDocuments($person, null, 'editor');

        $this->assertCount(0, $documents);

        $doc = $this->documents[0];

        $editor = Person::new();
        $editor->setLastName('Zufall');
        $editor->setFirstName('Rainer');
        $doc->addPersonEditor($editor);
        $doc->store();

        $documents = $personRepository->getPersonDocuments($person, null, 'editor');

        $this->assertCount(1, $documents);
    }

    public function testGetPersonDocumentsByStateAndRole()
    {
        $person = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $personRepository = $this->getPersonRepository();

        $documents = $personRepository->getPersonDocuments($person, 'unpublished', 'author');
        $this->assertCount(10, $documents);

        $documents = $personRepository->getPersonDocuments($person, 'published', 'author');
        $this->assertCount(0, $documents);

        $documents = $personRepository->getPersonDocuments($person, 'unpublished', 'editor');
        $this->assertCount(0, $documents);

        $doc = $this->documents[0];

        $doc->setServerState('published');
        $editor = Person::new();
        $editor->setLastName('Zufall');
        $editor->setFirstName('Rainer');
        $doc->addPersonEditor($editor);
        $doc->store();

        $documents = $personRepository->getPersonDocuments($person, 'published', 'editor');
        $this->assertCount(1, $documents);
    }

    public function testGetAllPersonsWithFilter()
    {
        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons(null, 0, 0);

        $this->assertCount(11, $persons);

        $persons = $personRepository->getAllPersons(null, 0, 0, 'fal');

        $this->assertCount(1, $persons);
        $this->assertEquals('Zufall', $persons[0]['last_name']);

        $persons = $personRepository->getAllPersons(null, 0, 0, 'pty');

        $this->assertCount(10, $persons);
    }

    public function testGetAllPersonsWithFilterFirstName()
    {
        $doc    = Document::get($this->documents[0]->getId());
        $person = Person::new();
        $person->setLastName('Mustermann');
        $person->setFirstName('Bafala');
        $doc->addPersonOther($person);
        $doc->store();

        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons(null, 0, 0, 'fal');

        $this->assertCount(2, $persons);
    }

    public function testGetAllPersonsWithFilterCaseInsensitive()
    {
        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons(null, 0, 0, 'FAL');

        $this->assertCount(1, $persons);

        $persons = $personRepository->getAllPersons(null, 0, 0, 'uFa');

        $this->assertCount(1, $persons);
    }

    public function testGetAllPersonsCount()
    {
        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getAllPersons();
        $count   = $personRepository->getAllPersonsCount();

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(11, $count);

        $persons = $personRepository->getAllPersons('author');
        $count   = $personRepository->getAllPersonsCount('author');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(1, $count);

        $persons = $personRepository->getAllPersons('author', 0, 0, 'fal');
        $count   = $personRepository->getAllPersonsCount('author', 'fal');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(1, $count);

        $persons = $personRepository->getAllPersons(null, 0, 0, 'emp');
        $count   = $personRepository->getAllPersonsCount(null, 'emp');

        $this->assertEquals(count($persons), $count);
        $this->assertEquals(10, $count);
    }

    public function testGetAllPersonsCountBug()
    {
        $person = Person::new();
        $person->setLastName(' Zufall  ');
        $person->setFirstName(' Rainer ');
        $person->setAcademicTitle('Prof.');
        $person->store();

        $person = Person::new();
        $person->setLastName(' Zufall');
        $person->setFirstName(' Rainer ');
        $person->store();

        $personRepository = $this->getPersonRepository();

        $count = $personRepository->getAllPersonsCount();

        $this->assertEquals(11, $count);
    }

    public function testOpusId()
    {
        $person = Person::new();
        $person->setLastName('Testy');
        $person->setOpusId('12345');
        $personId = $person->store();

        $person = Person::get($personId);

        $this->assertEquals('12345', $person->getOpusId());
        $this->assertEquals('Testy', $person->getLastName());
    }

    public function testGetPersonValues()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $personRepository = $this->getPersonRepository();

        $values = $personRepository->getPersonValues($personCrit);

        $this->assertCount(11, $values);

        $this->assertArrayHasKey('id', $values);

        $personIds = $values['id'];

        $this->assertIsArray($personIds);
        $this->assertCount(10, $personIds);

        $this->assertArrayHasKey('last_name', $values);
        $this->assertIsString($values['last_name']);
        $this->assertEquals('Zufall', $values['last_name']);

        foreach ($values as $key => $value) {
            if ($key !== 'id') {
                $this->assertIsNotArray($value);
            }
        }

        $person = Person::get($personIds[0]);
        $person->setPlaceOfBirth('Hamburg');
        $person->store();

        $values = $personRepository->getPersonValues($personCrit);

        $this->assertArrayHasKey('place_of_birth', $values);
        $this->assertIsArray($values['place_of_birth']);
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

        $personRepository = $this->getPersonRepository();

        $values = $personRepository->getPersonValues($personCrit);

        $this->assertNotNull($values);
        $this->assertIsArray($values);

        $this->assertArrayHasKey('id', $values);
        $this->assertIsArray($values['id']);
        $this->assertCount(6, $values['id']);

        foreach ($personIds as $personId) {
            $this->assertContains($personId, $values['id']);
        }

        $this->assertArrayHasKey('last_name', $values);
        $this->assertIsArray($values['last_name']);

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
        $this->assertIsArray($values['academic_title']);
        $this->assertCount(2, $values['academic_title']);
        $this->assertContains('Prof.', $values['academic_title']);
        $this->assertContains(null, $values['academic_title']);
    }

    public function testUpdateAll()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        $personRepository = $this->getPersonRepository();

        $personRepository->updateAll($personCrit, $changes);

        foreach ($this->authors as $author) {
            $person = Person::get($author->getId());

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

        $personRepository = $this->getPersonRepository();

        $personRepository->updateAll($personCrit, $changes, $documents);

        foreach ($this->authors as $author) {
            $person = Person::get($author->getId());

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

        $doc   = Document::new();
        $title = new Title();
        $title->setLanguage('deu');
        $title->setValue('Document with no author');
        $doc->addTitleMain($title);
        $docId = $doc->store();

        $doc = Document::get($docId);

        $documents = [3, 5, $docId];

        $lastModified = $doc->getServerDateModified();

        sleep(2);

        $now = new Date();
        $now->setNow();

        sleep(2);

        $personRepository = $this->getPersonRepository();

        // new ServerDateModified should be past $now
        $personRepository->updateAll($personCrit, $changes, $documents);

        // document without matching author was not modified
        $this->assertEquals($lastModified, $doc->getServerDateModified());

        //filtered documents were not modified
        foreach ($this->documents as $doc) {
            $document = Document::get($doc->getId()); // don't use old objects - they are not updated

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

        $personRepository = $this->getPersonRepository();

        $personRepository->updateAll($personCrit, $changes);

        $this->assertEquals($lastModified, $this->documents[0]->getServerDateModified());
    }

    public function testUpdateAllBadChanges()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = ['IdentifierIntern' => 'id1234']; // only Identifier(Orcid|Gnd|Misc) exist

        $lastModified = $this->documents[0]->getServerDateModified();

        sleep(2);

        $this->expectException(ModelException::class, 'unknown field \'IdentifierIntern\' for update');

        $personRepository = $this->getPersonRepository();

        $personRepository->updateAll($personCrit, $changes);

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

        $personRepository = $this->getPersonRepository();

        $personRepository->updateAll($personCrit, $changes);

        for ($index = 0; $index < 4; $index++) {
            $person = Person::get($personIds[$index]);
            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }

        $person = Person::get($personIds[4]);
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

        $personRepository = $this->getPersonRepository();

        $personRepository->updateAll($personCrit, $changes);

        $person = Person::get($personIds[0]);

        $this->assertEquals('John', $person->getFirstName());
    }

    public function testUpdateAllWithoutDocuments()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        $documents = null;

        $personRepository = $this->getPersonRepository();

        $personRepository->updateAll($personCrit, $changes, $documents);

        foreach ($this->authors as $author) {
            $person = Person::get($author->getId());

            $personDocs = $person->getDocumentsByRole('author');

            $this->assertCount(1, $personDocs);
            $this->assertEquals('bulktest@example.org', $person->getEmail());
        }
    }

    public function testUpdateAllWithoutDocumentsInArray()
    {
        $personRepository = $this->getPersonRepository();

        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = [
            'Email' => 'bulktest@example.org',
        ];

        $documents = [];

        $personRepository->updateAll($personCrit, $changes, $documents);

        foreach ($this->authors as $author) {
            $person = Person::get($author->getId());

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

        $personRepository = $this->getPersonRepository();

        $personDocs = $personRepository->getPersonsAndDocuments($personCrit);

        $this->markTestIncomplete('TODO finish');
    }

    public function testGetPersons()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $person = Person::new();
        $person->setLastName('Zufall');
        $person->store(); // not Rainer

        $personRepository = $this->getPersonRepository();

        $personIds = $personRepository->getPersons($personCrit);

        $this->assertNotNull($personIds);
        $this->assertIsArray($personIds);
        $this->assertCount(10, $personIds);
    }

    public function testGetPersonsForDocuments()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $documentIds = [2, 4, 7, 8];

        $personRepository = $this->getPersonRepository();

        $personIds = $personRepository->getPersons($personCrit, $documentIds);

        $this->assertNotNull($personIds);
        $this->assertIsArray($personIds);
        $this->assertCount(4, $personIds);

        foreach ($personIds as $personId) {
            $person = Person::get($personId);

            $documents = $person->getDocumentsByRole('author');

            $this->assertCount(1, $documents);

            $this->assertContains($documents[0]->getId(), $documentIds);
        }
    }

    public function testGetPersonsForDocumentsBadIds()
    {
        $personCrit = ['first_name' => 'Rainer'];

        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getPersons($personCrit, [33, 34]);

        $this->assertCount(0, $persons);
    }

    public function testGetPersonsForDocumentsCaseInsensitive()
    {
        $personCrit = ['last_name' => 'zuFall', 'first_name' => 'Rainer'];

        $documentIds = [2, 3, 4];

        $personRepository = $this->getPersonRepository();

        $personIds = $personRepository->getPersons($personCrit, $documentIds);

        $this->assertCount(3, $personIds);

        foreach ($personIds as $personId) {
            $person = Person::get($personId);

            $this->assertEquals('Zufall', $person->getLastName());
        }
    }

    public function testUpdateAllChangeLastName()
    {
        $personRepository = $this->getPersonRepository();

        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $changes = ['LastName' => 'Plannt', 'FirstName' => 'Volge'];

        $personRepository->updateAll($personCrit, $changes);

        foreach ($this->authors as $author) {
            $person = Person::get($author->getId());

            $this->assertEquals('Plannt', $person->getLastName());
            $this->assertEquals('Volge', $person->getFirstName());
        }
    }

    public function testGetPersonsAndDocuments()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $personRepository = $this->getPersonRepository();

        $personDocs = $personRepository->getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertIsArray($personDocs);
        $this->assertCount(10, $personDocs);

        foreach ($personDocs as $match) {
            $this->assertIsArray($match);
            $this->assertCount(2, $match);
            $this->assertArrayHasKey('person_id', $match);
            $this->assertArrayHasKey('document_id', $match);
            $personId  = $match['person_id'];
            $docId     = $match['document_id'];
            $person    = Person::get($personId);
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

        $personRepository = $this->getPersonRepository();

        $personDocs = $personRepository->getPersonsAndDocuments($personCrit, $docSet);

        $this->assertNotNull($personDocs);
        $this->assertIsArray($personDocs);
        $this->assertCount(4, $personDocs);

        $documentIds = array_column($personDocs, 'document_id');

        $this->assertContains(2, $documentIds);
        $this->assertContains(5, $documentIds);
        $this->assertContains(6, $documentIds);
        $this->assertContains(10, $documentIds);

        $doc = Document::get($this->documents[1]->getId());

        $this->assertEquals(2, $doc->getId());

        $doc->setPerson(null); // remove all persons
        $doc->store();

        $personDocs = $personRepository->getPersonsAndDocuments($personCrit, $docSet);

        $this->assertCount(3, $personDocs);

        $documentIds = array_column($personDocs, 'document_id');

        $this->assertNotContains(2, $documentIds);
    }

    public function testGetPersonsAndDocumentsMultiplePersonsOnDocument()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $doc = Document::get($this->documents[0]->getId());

        $person = Person::new();
        $person->setLastName('Zufall');
        $person->setFirstName('Rainer');

        $doc->addPersonOther($person);
        $doc->store();

        $personRepository = $this->getPersonRepository();

        $personDocs = $personRepository->getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertIsArray($personDocs);
        $this->assertCount(11, $personDocs);

        $personIds   = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(11, $personIds);
        $this->assertCount(10, array_unique($documentIds));
    }

    public function testGetPersonsAndDocumentsMultipleDocumentsOnPerson()
    {
        $personCrit = ['last_name' => 'Zufall', 'first_name' => 'Rainer'];

        $doc = Document::new();
        $doc->setType('article');
        $title = new Title();
        $title->setLanguage('eng');
        $title->setValue('Test document');
        $doc->addTitleMain($title);
        $doc->addPersonOther($this->authors[0]);
        $doc->store();

        $personRepository = $this->getPersonRepository();

        $personDocs = $personRepository->getPersonsAndDocuments($personCrit);

        $this->assertNotNull($personDocs);
        $this->assertIsArray($personDocs);
        $this->assertCount(11, $personDocs);

        $personIds   = array_column($personDocs, 'person_id');
        $documentIds = array_column($personDocs, 'document_id');

        $this->assertCount(10, array_unique($personIds));
        $this->assertCount(11, $documentIds);
    }

    public function testGetPersonValuesNotFound()
    {
        $personRepository = $this->getPersonRepository();

        $values = $personRepository->getPersonValues(['last_name' => 'doesnotexist']);

        $this->assertNull($values);
    }

    public function testGetDocuments()
    {
        $personIds = [
            $this->authors[0]->getId(),
            $this->authors[4]->getId(),
        ];

        $personRepository = $this->getPersonRepository();

        $documentIds = $personRepository->getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertIsArray($documentIds);
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

        $personRepository = $this->getPersonRepository();

        $documentIds = $personRepository->getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertIsArray($documentIds);
        $this->assertCount(2, $documentIds);
        $this->assertContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
    }

    public function testGetDocumentsOnePersonTwoDocuments()
    {
        $doc = Document::get($this->documents[1]->getId());
        $doc->addPersonSubmitter($this->authors[0]);
        $docId = $doc->store();

        $personIds = [
            $this->authors[0]->getId(),
            $this->authors[4]->getId(),
        ];

        $personRepository = $this->getPersonRepository();

        $documentIds = $personRepository->getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertIsArray($documentIds);
        $this->assertCount(3, $documentIds);
        $this->assertContains($this->documents[0]->getId(), $documentIds);
        $this->assertContains($this->documents[4]->getId(), $documentIds);
        $this->assertContains($docId, $documentIds);
    }

    public function testGetDocumentsTwoPersonsOneDocument()
    {
        $doc    = Document::get($this->documents[0]->getId());
        $person = Person::new();
        $person->setLastName('Tester');
        $plink = $doc->addPersonSubmitter($person);
        $doc->store();

        $personIds = [
            $this->authors[0]->getId(), // document 0
            $this->authors[4]->getId(),
            $plink->getModel()->getId(), // document 0
        ];

        $personRepository = $this->getPersonRepository();

        $documentIds = $personRepository->getDocuments($personIds);

        $this->assertNotNull($documentIds);
        $this->assertIsArray($documentIds);
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

        $personRepository = $this->getPersonRepository();

        $documentIds = $personRepository->getDocuments($personIds, $allowedDocuments);

        $this->assertNotNull($documentIds);
        $this->assertIsArray($documentIds);
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

        $personRepository = $this->getPersonRepository();

        $documentIds = $personRepository->getDocuments($personIds, $allowedDocuments);

        $this->assertNotNull($documentIds);
        $this->assertIsArray($documentIds);
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

        $personRepository = $this->getPersonRepository();

        $persons = $personRepository->getPersons($personCrit);

        $this->assertNotNull($persons);
        $this->assertIsArray($persons);
        $this->assertCount(10, $persons);
    }

    public function testConvertChanges()
    {
        $changes = [
            'LastName'  => 'Zufall',
            'FirstName' => 'Rainer',
            'Email'     => 'example@example.org',
        ];

        $personRepository = $this->getPersonRepository();

        $result = $personRepository::convertChanges($changes);

        $this->assertEquals([
            'last_name'  => 'Zufall',
            'first_name' => 'Rainer',
            'email'      => 'example@example.org',
        ], $result);
    }

    public function testGetAllUniqueIdentifierOrcid()
    {
        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('1111-2222-3333-4444');
        $person->store();

        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('111102222');
        $person->store();

        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('111102222');
        $person->store();

        $persons = Repository::getInstance()->getModelRepository(Person::class);

        $info = $persons->getAllUniqueIdentifierOrcid();

        $this->assertIsArray($info);
        $this->assertCount(2, $info);
    }

    /**
     * @return array[]
     */
    public static function orcidProvider()
    {
        return [
            [' http://orcid.org/1111-2222-3333-4444', '1111-2222-3333-4444'],
            [' HTTP://orcid.org/2222-2222-2222-2222', '2222-2222-2222-2222'],
            ['https://orcid.org/3333-3333-3333-333X ', '3333-3333-3333-333X'],
            ['HTTPS://orcid.org/111102222 ', '111102222'],
            ['1111-2222-3333', '1111-2222-3333'],
            ['3333-3333-3333-333x', '3333-3333-3333-333x'],
        ];
    }

    /**
     * @param string $orcid
     * @param string $expected
     * @dataProvider orcidProvider
     */
    public function testNormalizeOrcidValues($orcid, $expected)
    {
        $person = Person::new();
        $person::setFilterEnabled(false);
        $person->setLastName('Tester');
        $person->setIdentifierOrcid($orcid);
        $person->store();
        $person::setFilterEnabled(true);

        $persons = $this->getPersonRepository();

        $persons->normalizeOrcidValues();

        $values = $persons->getAllUniqueIdentifierOrcid();

        $this->assertIsArray($values);
        $this->assertCount(1, $values);
        $this->assertEquals($expected, $values[0]);
    }

    public function testGetAllIdentifierOrcid()
    {
        $doc1   = Document::new();
        $person = Person::new();
        $person->setLastName('Test1');
        $person->setIdentifierOrcid('1111-2222-3333-4444');
        $doc1->addPersonAuthor($person);
        $docId1    = (int) $doc1->store();
        $personId1 = $doc1->getPersonAuthor()[0]->getModel()->getId();

        $doc2   = Document::new();
        $person = Person::new();
        $person->setLastName('Test2');
        $person->setIdentifierOrcid('2222-2222-2222-2222');
        $doc2->addPersonAuthor($person);
        $docId2    = (int) $doc2->store();
        $personId2 = $doc2->getPersonAuthor()[0]->getModel()->getId();

        $doc3   = Document::new();
        $person = Person::new();
        $person->setLastName('Test3');
        $person->setIdentifierOrcid('1111-2222-3333-4444');
        $doc3->addPersonAuthor($person);
        $docId3    = (int) $doc3->store();
        $personId3 = $doc3->getPersonAuthor()[0]->getModel()->getId();

        $persons = $this->getPersonRepository();

        $result = $persons->getAllIdentifierOrcid();

        $this->assertEquals(
            [
                ['documentId' => $docId1, 'personId' => $personId1, 'orcidId' => '1111-2222-3333-4444'],
                ['documentId' => $docId2, 'personId' => $personId2, 'orcidId' => '2222-2222-2222-2222'],
                ['documentId' => $docId3, 'personId' => $personId3, 'orcidId' => '1111-2222-3333-4444'],
            ],
            $result
        );
    }

    public function testDeleteOrphanedPersons()
    {
        $doc1   = Document::new();
        $person = Person::new();
        $person->setLastName('Test1');
        $person->setIdentifierOrcid('1111-2222-3333-4444');
        $doc1->addPersonAuthor($person);
        $docId1    = (int) $doc1->store();
        $personId1 = $doc1->getPersonAuthor()[0]->getModel()->getId();

        $doc2   = Document::new();
        $person = Person::new();
        $person->setLastName('Test2');
        $person->setIdentifierOrcid('2222-2222-2222-2222');
        $doc2->addPersonAuthor($person);
        $docId2    = (int) $doc2->store();
        $personId2 = $doc2->getPersonAuthor()[0]->getModel()->getId();

        $doc1 = Document::get($docId1);
        $doc1->delete();

        $orphan = Person::get($personId1);
        $this->assertNotNull($orphan);

        $persons = $this->getPersonRepository();

        $persons->deleteOrphanedPersons();

        $person2 = Person::get($personId2);
        $this->assertNotNull($person2);

        $this->expectException(NotFoundException::class);
        Person::get($personId1);
    }

    public function testDeleteOrphanedPersonsKeepPersonsWithIdentifiers()
    {
        $doc1   = Document::new();
        $person = Person::new();
        $person->setLastName('Test1');
        $person->setIdentifierOrcid('1111-2222-3333-4444');
        $doc1->addPersonAuthor($person);
        $docId1    = (int) $doc1->store();
        $personId1 = $doc1->getPersonAuthor()[0]->getModel()->getId();

        $doc2   = Document::new();
        $person = Person::new();
        $person->setLastName('Test2');
        $doc2->addPersonAuthor($person);
        $docId2    = (int) $doc2->store();
        $personId2 = $doc2->getPersonAuthor()[0]->getModel()->getId();

        $doc1 = Document::get($docId1);
        $doc1->delete();

        $doc2 = Document::get($docId2);
        $doc2->delete();

        $person1 = Person::get($personId1);
        $this->assertNotNull($person1);

        $person2 = Person::get($personId2);
        $this->assertNotNull($person2);

        $persons = $this->getPersonRepository();

        $persons->deleteOrphanedPersons(true);

        $person1 = Person::get($personId1);
        $this->assertNotNull($person1);

        $this->expectException(NotFoundException::class);
        Person::get($personId2);
    }

    public function testGetOrphanedPersonsCount()
    {
        $doc1   = Document::new();
        $person = Person::new();
        $person->setLastName('Test1');
        $person->setIdentifierOrcid('1111-2222-3333-4444');
        $doc1->addPersonAuthor($person);
        $docId1 = (int) $doc1->store();

        $doc2   = Document::new();
        $person = Person::new();
        $person->setLastName('Test2');
        $doc2->addPersonAuthor($person);
        $docId2 = (int) $doc2->store();

        $doc1 = Document::get($docId1);
        $doc1->delete();

        $doc2 = Document::get($docId2);
        $doc2->delete();

        $persons = $this->getPersonRepository();

        // 10 orphans are generated in setUp
        $this->assertEquals(12, $persons->getOrphanedPersonsCount());

        $persons->deleteOrphanedPersons();

        $this->assertEquals(0, $persons->getOrphanedPersonsCount());
    }

    public function testReplaceOrcid()
    {
        $doc    = Document::new();
        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('1111-2222-3333-444');
        $doc->addPersonAuthor($person);
        $docId = (int) $doc->store();

        $persons = $this->getPersonRepository();

        $persons->replaceOrcid('1111-2222-3333-444', '1111-2222-3333-444X');

        $doc    = Document::get($docId);
        $person = $doc->getPersonAuthor(0);
        $this->assertEquals('1111-2222-3333-444X', $person->getIdentifierOrcid());
    }

    /**
     * @return ModelRepositoryInterface
     */
    protected function getPersonRepository()
    {
        return Repository::getInstance()->getModelRepository(Person::class);
    }

    public function testCreatePersonTestFunction()
    {
        $personValues = [
            ' Spacey ' => [],
        ];

        $personIds = $this->createPersons($personValues);

        $this->assertNotNull($personIds);
        $this->assertIsArray($personIds);
        $this->assertCount(1, $personIds);

        $person = Person::get($personIds[0]);

        $this->assertEquals(' Spacey ', $person->getLastName());
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
            $person = Person::new();
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
}
