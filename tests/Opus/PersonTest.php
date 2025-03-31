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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Date;
use Opus\Common\Document;
use Opus\Common\Model\ModelException;
use Opus\Common\Person;
use Opus\Common\PersonInterface;
use Opus\Model\Xml\Cache;
use Opus\Person as FrameworkPerson;
use OpusTest\TestAsset\TestCase;

use function count;

/**
 * Test cases for class Opus\Person.
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

    public function testDeletePerson()
    {
        $docId   = $this->documents[0]->getId();
        $d       = Document::get($docId);
        $persons = $d->getPerson();
        $this->assertTrue(1 === count($persons));

        $person = $persons[0];
        $this->assertTrue($person->getFirstName() === 'Rainer');
        $this->assertTrue($person->getLastName() === 'Zufall');

        $d->setPerson([]);
        $d->store();

        $d = Document::get($docId);
        $this->assertTrue(0 === count($d->getPerson()));
    }

    public function testOnlyLastNameMandatory()
    {
        $person = Person::new();

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
        $person = Person::new();

        $person->setFirstName('Jane');
        $person->setLastName('Doe');

        $this->assertEquals('Doe, Jane', $person->getName());
    }

    public function testGetNameForLastNameOnly()
    {
        $person = Person::new();

        $person->setLastName('Doe');

        $this->assertEquals('Doe', $person->getName());
    }

    public function testSetGetIdentifiers()
    {
        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('0000-0002-1694-233X');
        $person->setIdentifierGnd('test_gnd_identifier');
        $person->setIdentifierMisc('myid');

        $personId = $person->store();

        $person = Person::get($personId);

        $this->assertEquals('0000-0002-1694-233X', $person->getIdentifierOrcid());
        $this->assertEquals('test_gnd_identifier', $person->getIdentifierGnd());
        $this->assertEquals('myid', $person->getIdentifierMisc());
    }

    public function testStoreIdentifierOrcidRemoveUrlHttp()
    {
        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('http://orcid.org/0000-0002-1694-233X');

        $person = Person::get($person->store());

        $this->assertEquals('0000-0002-1694-233X', $person->getIdentifierOrcid());
    }

    public function testStoreIdentifierOrcidRemoveUrlHttps()
    {
        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('https://orcid.org/0000-0002-1694-233X');

        $person = Person::get($person->store());

        $this->assertEquals('0000-0002-1694-233X', $person->getIdentifierOrcid());
    }

    public function testStoreIdentifierOrcidInvalidValue()
    {
        $person = Person::new();
        $person->setLastName('Tester');
        $person->setIdentifierOrcid('000000002-1694-2338');

        $person = Person::get($person->store());

        $this->assertEquals('000000002-1694-2338', $person->getIdentifierOrcid());
    }

    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache()
    {
        $person = Person::new();
        $person->setFirstName('Jane');
        $person->setLastName('Doe');
        $person->store();
        $doc = Document::new();
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

    public function testConvertToFieldNames()
    {
        $values = [
            'last_name'  => 'Zufall',
            'first_name' => 'Rainer',
            'email'      => 'example@example.org',
        ];

        $result = FrameworkPerson::convertToFieldNames($values);

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

    public function testGetDocumentIds()
    {
        $person = Person::get($this->authors[0]->getId());

        $docIds = $person->getDocumentIds();

        $this->assertNotNull($docIds);
        $this->assertInternalType('array', $docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains(1, $docIds);
    }

    public function testGetDocumentIdsUniqueValues()
    {
        $person = Person::get($this->authors[0]->getId());
        $doc    = Document::get($this->documents[0]->getId());

        $doc->addPersonAdvisor($person);
        $doc->store();

        $docIds = $person->getDocumentIds();

        $this->assertNotNull($docIds);
        $this->assertIsArray($docIds);
        $this->assertCount(1, $docIds);
        $this->assertContains($doc->getId(), $docIds);
    }

    public function testGetDocumentIdsForRole()
    {
        $person = Person::get($this->authors[0]->getId());

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

    public function testStoreValuesAreTrimmed()
    {
        $person = Person::new();
        $person->setLastName(' Zufall ');
        $person->setFirstName(' Rainer ');
        $personId = $person->store();

        $person = Person::get($personId);

        $this->assertEquals('Zufall', $person->getLastName());
        $this->assertEquals('Rainer', $person->getFirstName());
    }

    /**
     * OPUSVIER-3764
     */
    public function testDeleteAssignedPerson()
    {
        $this->markTestIncomplete('TODO not sure what/how to test');

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->setType('article');

        $person = Person::new();
        $person->setLastName('Tester');

        $doc->addPersonAuthor($person);

        $docId = $doc->store();

        $person->delete();

        // $doc = Document::get($docId);

        $doc->delete();

        $this->expectException(ModelException::class, 'No Opus\Db\Documents with id');
        Document::get($docId);
    }

    public function testSortOrderDefault()
    {
        $doc = Document::new();

        $person = Person::new();
        $person->setLastName('Person1');
        $doc->addPersonAuthor($person);

        $person = Person::new();
        $person->setLastName('Person2');
        $doc->addPersonAuthor($person);

        $docId = $doc->store();

        $doc = Document::get($docId);

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
        $person1 = Person::new();
        $person2 = Person::new();

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
        $person = Person::new();
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
        $this->assertInstanceOf(PersonInterface::class, $person);

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
        $person = Person::new();

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
        $person = Person::new();
        $this->assertEquals('person', $person->getModelType());
    }
}
