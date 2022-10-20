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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model\Dependent\Link;

use Opus\Common\Date;
use Opus\Model\Dependent\Link\DocumentPerson;
use Opus\Person;
use OpusTest\TestAsset\TestCase;

class DocumentPersonTest extends TestCase
{
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

        $personLink = new DocumentPerson();
        $personLink->setModel($person);
        $personLink->setRole('author');
        $personLink->setAllowEmailContact(1);
        $personLink->setSortOrder(2);

        $data = $personLink->toArray();

        $this->assertEquals([
            'AcademicTitle'     => 'Prof.',
            'DateOfBirth'       => $dateOfBirthArray,
            'PlaceOfBirth'      => 'München',
            'FirstName'         => 'Thomas',
            'LastName'          => 'Mueller',
            'Email'             => 'mueller@example.org',
            'IdentifierOrcid'   => '0000-0000-0000-0002',
            'IdentifierGnd'     => '123456789',
            'IdentifierMisc'    => 'B',
            'OpusId'            => '2',
            'Role'              => 'author',
            'AllowEmailContact' => 1,
            'SortOrder'         => 2,
        ], $data);
    }

    public function testFromArray()
    {
        $personLink = DocumentPerson::fromArray([
            'AcademicTitle'     => 'Prof.',
            'DateOfBirth'       => '1960-05-17',
            'PlaceOfBirth'      => 'München',
            'FirstName'         => 'Thomas',
            'LastName'          => 'Mueller',
            'Email'             => 'mueller@example.org',
            'IdentifierOrcid'   => '0000-0000-0000-0002',
            'IdentifierGnd'     => '123456789',
            'IdentifierMisc'    => 'B',
            'OpusId'            => '2',
            'Role'              => 'author',
            'AllowEmailContact' => 1,
            'SortOrder'         => 2,
        ]);

        $this->assertNotNull($personLink);
        $this->assertInstanceOf(DocumentPerson::class, $personLink);

        $this->assertEquals('Prof.', $personLink->getAcademicTitle());
        $this->assertEquals('Thomas', $personLink->getFirstName());
        $this->assertEquals('Mueller', $personLink->getLastName());
        $this->assertEquals('mueller@example.org', $personLink->getEmail());
        $this->assertEquals('1960-05-17', $personLink->getDateOfBirth()->__toString());
        $this->assertEquals('München', $personLink->getPlaceOfBirth());
        $this->assertEquals('0000-0000-0000-0002', $personLink->getIdentifierOrcid());
        $this->assertEquals('123456789', $personLink->getIdentifierGnd());
        $this->assertEquals('B', $personLink->getIdentifierMisc());
        $this->assertEquals('2', $personLink->getOpusId());
        $this->assertEquals('author', $personLink->getRole());
        $this->assertEquals(1, $personLink->getAllowEmailContact());
        $this->assertEquals(2, $personLink->getSortOrder());
    }

    public function testUpdateFromArray()
    {
        $personLink = new DocumentPerson();

        $personLink->updateFromArray([
            'AcademicTitle'     => 'Prof.',
            'DateOfBirth'       => '1960-05-17',
            'PlaceOfBirth'      => 'München',
            'FirstName'         => 'Thomas',
            'LastName'          => 'Mueller',
            'Email'             => 'mueller@example.org',
            'IdentifierOrcid'   => '0000-0000-0000-0002',
            'IdentifierGnd'     => '123456789',
            'IdentifierMisc'    => 'B',
            'OpusId'            => '2',
            'Role'              => 'author',
            'AllowEmailContact' => 1,
            'SortOrder'         => 2,
        ]);

        $this->assertEquals('Prof.', $personLink->getAcademicTitle());
        $this->assertEquals('Thomas', $personLink->getFirstName());
        $this->assertEquals('Mueller', $personLink->getLastName());
        $this->assertEquals('mueller@example.org', $personLink->getEmail());
        $this->assertEquals('1960-05-17', $personLink->getDateOfBirth()->__toString());
        $this->assertEquals('München', $personLink->getPlaceOfBirth());
        $this->assertEquals('0000-0000-0000-0002', $personLink->getIdentifierOrcid());
        $this->assertEquals('123456789', $personLink->getIdentifierGnd());
        $this->assertEquals('B', $personLink->getIdentifierMisc());
        $this->assertEquals('2', $personLink->getOpusId());
        $this->assertEquals('author', $personLink->getRole());
        $this->assertEquals(1, $personLink->getAllowEmailContact());
        $this->assertEquals(2, $personLink->getSortOrder());
    }

    public function testUpdateFromArrayUseExistingModel()
    {
        $personLink = new DocumentPerson();

        $person = new Person();
        $person->setPlaceOfBirth('Berlin');

        $personLink->setModel($person);

        $personLink->updateFromArray([
            'AcademicTitle'     => 'Prof.',
            'DateOfBirth'       => '1960-05-17',
            'FirstName'         => 'Thomas',
            'LastName'          => 'Mueller',
            'Email'             => 'mueller@example.org',
            'IdentifierOrcid'   => '0000-0000-0000-0002',
            'IdentifierGnd'     => '123456789',
            'IdentifierMisc'    => 'B',
            'OpusId'            => '2',
            'Role'              => 'author',
            'AllowEmailContact' => 1,
            'SortOrder'         => 2,
        ]);

        $this->assertSame($person, $personLink->getModel());

        // PlaceOfBirth is not part of array (existing model gets reset)
        $this->assertNotEquals('Berlin', $personLink->getPlaceOfBirth());
        $this->assertNull($personLink->getPlaceOfBirth());

        $this->assertEquals('Prof.', $personLink->getAcademicTitle());
        $this->assertEquals('Thomas', $personLink->getFirstName());
        $this->assertEquals('Mueller', $personLink->getLastName());
        $this->assertEquals('mueller@example.org', $personLink->getEmail());
        $this->assertEquals('1960-05-17', $personLink->getDateOfBirth()->__toString());
        $this->assertEquals('0000-0000-0000-0002', $personLink->getIdentifierOrcid());
        $this->assertEquals('123456789', $personLink->getIdentifierGnd());
        $this->assertEquals('B', $personLink->getIdentifierMisc());
        $this->assertEquals('2', $personLink->getOpusId());
        $this->assertEquals('author', $personLink->getRole());
        $this->assertEquals(1, $personLink->getAllowEmailContact());
        $this->assertEquals(2, $personLink->getSortOrder());
    }
}
