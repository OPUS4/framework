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
 *
 * @category    Framework
 * @package     Tests
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Model\Dependent\Link;

use Opus\Common\Model\ModelException;
use Opus\DnbInstitute;
use Opus\Document;
use Opus\Model\Dependent\Link\DocumentDnbInstitute;
use OpusTest\TestAsset\TestCase;

class DocumentDnbInstituteTest extends TestCase
{
    public function testRoleFieldNoSetAccess()
    {
        $institute = new DocumentDnbInstitute();

        $this->expectException(ModelException::class, 'Access to internal field not allowed: Role');

        $institute->setRole('grantor');
    }

    public function testRoleFieldNoGetAccess()
    {
        $institute = new DocumentDnbInstitute();

        $this->expectException(ModelException::class, 'Access to internal field not allowed: Role');

        $institute->getRole();
    }

    public function testToArray()
    {
        $institute = new DocumentDnbInstitute();

        $institute->setModel(new DnbInstitute());
        $institute->setName('Solutions');
        $institute->setDepartment('Big Solutions');
        $institute->setAddress('Research Street');
        $institute->setCity('Berlin');
        $institute->setPhone('555-1234');
        $institute->setDnbContactId('123');
        $institute->setIsGrantor(0);
        $institute->setIsPublisher(1);

        $data = $institute->toArray();

        $this->assertEquals([
            'Name'         => 'Solutions',
            'Department'   => 'Big Solutions',
            'Address'      => 'Research Street',
            'City'         => 'Berlin',
            'Phone'        => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor'    => 0,
            'IsPublisher'  => 1,
            'Role'         => null,
        ], $data);
    }

    public function testFromArray()
    {
        $institute = DocumentDnbInstitute::fromArray([
            'Name'         => 'Solutions',
            'Department'   => 'Big Solutions',
            'Address'      => 'Research Street',
            'City'         => 'Berlin',
            'Phone'        => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor'    => 1,
            'IsPublisher'  => 0,
            'Role'         => 'grantor',
        ]);

        $this->assertNotNull($institute);
        $this->assertInstanceOf(DocumentDnbInstitute::class, $institute);

        $this->assertEquals('Solutions', $institute->getName());
        $this->assertEquals('Big Solutions', $institute->getDepartment());
        $this->assertEquals('Research Street', $institute->getAddress());
        $this->assertEquals('Berlin', $institute->getCity());
        $this->assertEquals('555-1234', $institute->getPhone());
        $this->assertEquals('123', $institute->getDnbContactId());
        $this->assertEquals(1, $institute->getIsGrantor());
        $this->assertEquals(0, $institute->getIsPublisher());
    }

    public function testUpdateFromArray()
    {
        $institute = new DocumentDnbInstitute();

        $institute->updateFromArray([
            'Name'         => 'Solutions',
            'Department'   => 'Big Solutions',
            'Address'      => 'Research Street',
            'City'         => 'Berlin',
            'Phone'        => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor'    => 1,
            'IsPublisher'  => 0,
            'Role'         => 'grantor',
        ]);

        $this->assertNotNull($institute);
        $this->assertInstanceOf(DocumentDnbInstitute::class, $institute);

        $this->assertEquals('Solutions', $institute->getName());
        $this->assertEquals('Big Solutions', $institute->getDepartment());
        $this->assertEquals('Research Street', $institute->getAddress());
        $this->assertEquals('Berlin', $institute->getCity());
        $this->assertEquals('555-1234', $institute->getPhone());
        $this->assertEquals('123', $institute->getDnbContactId());
        $this->assertEquals(1, $institute->getIsGrantor());
        $this->assertEquals(0, $institute->getIsPublisher());
    }

    /**
     * The 'Role' field is set automatically once a document is written to the database. If the model is
     * retrieved from the database, the 'Role' field will be set. Without the database the behaviour of
     * the data model is not completely as expected.
     *
     * TODO fix behaviour of data model - if institute added to document, 'Role' should be set (OPUSVIER-3942)
     */
    public function testRoleSetAutomatically()
    {
        $this->markTestSkipped('Not the current behaviour.');

        $institute = new DocumentDnbInstitute();

        $institute->setModel(new DnbInstitute());
        $institute->setName('Solutions');
        $institute->setDepartment('Big Solutions');
        $institute->setAddress('Research Street');
        $institute->setCity('Berlin');
        $institute->setPhone('555-1234');
        $institute->setDnbContactId('123');
        $institute->setIsGrantor(0);
        $institute->setIsPublisher(1);

        $document = new Document();
        $document->addThesisGrantor($institute);

        $data = $institute->toArray();

        $this->assertEquals([
            'Name'         => 'Solutions',
            'Department'   => 'Big Solutions',
            'Address'      => 'Research Street',
            'City'         => 'Berlin',
            'Phone'        => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor'    => 0,
            'IsPublisher'  => 1,
            'Role'         => 'grantor',
        ], $data);
    }
}
