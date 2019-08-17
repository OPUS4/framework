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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Test cases for class Opus_File.
 *
 * @package Opus
 * @category Tests
 *
 * @group DnbInstituteTests
 */
class Opus_DnbInstituteTest extends TestCase
{

    public function testStoreAndLoadDnbInstitute()
    {
        $name = 'Forschungsinstitut für Code Coverage';
        $address = 'Musterstr. 23 - 12345 Entenhausen - Calisota';
        $city = 'Calisota';
        $phone = '+1 234 56789';
        $dnb_contact_id = 'F1111-1111';
        $is_grantor = '1';

        $dnb_institute = new Opus_DnbInstitute();
        $dnb_institute->setName($name)
                ->setAddress($address)
                ->setCity($city)
                ->setPhone($phone)
                ->setDnbContactId($dnb_contact_id)
                ->setIsGrantor($is_grantor);
        // store
        $id = $dnb_institute->store();

        //load
        $loaded_institute = new Opus_DnbInstitute($id);

        $this->assertEquals($name, $loaded_institute->getName(), 'Loaded other name, then stored.');
        $this->assertEquals($address, $loaded_institute->getAddress(), 'Loaded other address, then stored.');
        $this->assertEquals($city, $loaded_institute->getCity(), 'Loaded other city, then stored.');
        $this->assertEquals($phone, $loaded_institute->getPhone(), 'Loaded other phone number, then stored.');
        $this->assertEquals(
            $dnb_contact_id,
            $loaded_institute->getDnbContactId(),
            'Loaded other DNB contact ID, then stored.'
        );
        $this->assertEquals(
            $is_grantor,
            $loaded_institute->getIsGrantor(),
            'Loaded other information about grantor status, then stored.'
        );
    }

    /**
     * Test if a set of dnb institutes can be retrieved by getAll().
     *
     * @return void
     */
    public function testRetrieveAllDnbInstitutes()
    {
        $dnb_institutes = [];
        for ($i = 1; $i <= 3; $i++) {
            $dnb_institute = new Opus_DnbInstitute();
            $dnb_institute->setName('Forschungsinstitut für Code Coverage Abt. ' + $i);
            $dnb_institute->setCity('Calisota');
            $dnb_institute->store();
            $dnb_institutes[] = $dnb_institutes;
        }

        $result = Opus_DnbInstitute::getAll();
        $this->assertEquals(count($dnb_institutes), count($result), 'Wrong number of objects retrieved.');
    }

    public function testRetrieveGrantors()
    {
        $publishers = [];
        $grantors = [];
        for ($i = 1; $i <= 10; $i++) {
            $dnb_institute = new Opus_DnbInstitute();
            $dnb_institute->setName('Forschungsinstitut für Code Coverage Abt. ' + $i);
            $dnb_institute->setCity('Calisota');
            if (0 == ($i % 2)) {
                $dnb_institute->setIsGrantor(1);
                $dnb_institute->store();
                $grantors[] = $dnb_institute;
            } else {
                $dnb_institute->store();
                $publishers[] = $dnb_institute;
            }
        }
        $result = Opus_DnbInstitute::getGrantors();
        $this->assertEquals(count($grantors), count($result), 'Wrong number of objects retrieved.');
    }

    /**
     * Test if the DnbInstitute display name matches its name,
     * optionally followed by its department name if set.
     *
     *
     * @return void
     */
    public function testDisplayNameMatchesNameAndDepartmentIfSet()
    {
        $dnbInstitute = new Opus_DnbInstitute();
        $dnbInstitute->setName('MyTestName');
        $this->assertEquals(
            $dnbInstitute->getName(),
            $dnbInstitute->getDisplayName(),
            'Displayname does not match name.'
        );
        $dnbInstitute->setDepartment('MyTestDepartment');
        $this->assertEquals(
            $dnbInstitute->getName() . ', ' . $dnbInstitute->getDepartment(),
            $dnbInstitute->getDisplayName(),
            'Displayname does not match name and department.'
        );
    }

    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache()
    {
        $dnb_institute = new Opus_DnbInstitute();
        $dnbId = $dnb_institute->setName('Test')
                ->setCity('Berlin')
                ->setIsGrantor(1)
                ->store();

        $doc = new Opus_Document();
        $doc->setType("article")
                ->setServerState('published')
                ->setThesisGrantor($dnb_institute);
        $docId = $doc->store();

        $xmlCache = new Opus_Model_Xml_Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $dnb_institute->setName('Test Institute');
        $dnb_institute->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }

    /**
     * Regression Test for OPUSVIER-3041
     * added field 'department' to model
     */

    public function testDepartmentIsStored()
    {
        $dnbInstitute = new Opus_DnbInstitute();
        $dnbId = $dnbInstitute->setName('Foo University')
                ->setDepartment('Paranormal Research Institute')
                ->setCity('Berlin')
                ->setIsGrantor(1)
                ->store();

        $dnbReloaded = new Opus_DnbInstitute($dnbId);

        $this->assertEquals('Paranormal Research Institute', $dnbReloaded->getDepartment());
    }

    /**
     * Regression Test for OPUSVIER-3114
     */
    public function testDocumentServerDateModifiedNotUpdatedWithConfiguredFields()
    {
        $fields = ['Address','City','Phone','DnbContactId'];

        $dnb_institute = new Opus_DnbInstitute();
        $dnbId = $dnb_institute->setName('Test')
                ->setCity('Berlin')
                ->setIsGrantor(1)
                ->store();

        $doc = new Opus_Document();
        $doc->setType("article")
                ->setServerState('published')
                ->setThesisGrantor($dnb_institute);
        $docId = $doc->store();
        $serverDateModified = $doc->getServerDateModified();

        sleep(1);

        foreach ($fields as $fieldName) {
            $oldValue = $dnb_institute->{'get' . $fieldName}();
            $dnb_institute->{'set' . $fieldName}(1);
            $this->assertNotEquals(
                $dnb_institute->{'get' . $fieldName}(),
                $oldValue,
                'Expected different values before and after setting value'
            );
        }
        $dnb_institute->store();
        $docReloaded = new Opus_Document($docId);

        $this->assertEquals(
            (string)$serverDateModified,
            (string)$docReloaded->getServerDateModified(),
            'Expected no difference in server date modified.'
        );
    }

    public function testModifyingIsGrantorDoesNotUpdateServerDateModified()
    {
        $institute = new Opus_DnbInstitute();
        $institute->setName('Test')
            ->setCity('Berlin')
            ->setIsGrantor(1)
            ->store();

        $doc = new Opus_Document();
        $doc->setType('article')
            ->setServerState('published')
            ->setThesisGrantor($institute);

        $docId = $doc->store();
        $serverDateModified = $doc->getServerDateModified();

        sleep(1);

        $institute->setIsGrantor(0);
        $institute->store();

        $doc = new Opus_Document($docId);

        $this->assertEquals($serverDateModified, $doc->getServerDateModified());
    }

    public function testModifyingIsPublisherDoesNotUpdateServerDateModified()
    {
        $institute = new Opus_DnbInstitute();
        $institute->setName('Test')
            ->setCity('Berlin')
            ->setIsPublisher(1)
            ->store();

        $doc = new Opus_Document();
        $doc->setType('article')
            ->setServerState('published')
            ->setThesisPublisher($institute);

        $docId = $doc->store();
        $serverDateModified = $doc->getServerDateModified();

        sleep(1);

        $institute->setIsPublisher(0);
        $institute->store();

        $doc = new Opus_Document($docId);

        $this->assertEquals($serverDateModified, $doc->getServerDateModified());
    }

    public function testToArray()
    {
        $institute = new Opus_DnbInstitute();

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
            'Name' => 'Solutions',
            'Department' => 'Big Solutions',
            'Address' => 'Research Street',
            'City' => 'Berlin',
            'Phone' => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor' => 0,
            'IsPublisher' => 1
        ], $data);
    }

    public function testFromArray()
    {
        $institute = Opus_DnbInstitute::fromArray([
            'Name' => 'Solutions',
            'Department' => 'Big Solutions',
            'Address' => 'Research Street',
            'City' => 'Berlin',
            'Phone' => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor' => 0,
            'IsPublisher' => 1
        ]);

        $this->assertNotNull($institute);
        $this->assertInstanceOf('Opus_DnbInstitute', $institute);

        $this->assertEquals('Solutions', $institute->getName());
        $this->assertEquals('Big Solutions', $institute->getDepartment());
        $this->assertEquals('Research Street', $institute->getAddress());
        $this->assertEquals('Berlin', $institute->getCity());
        $this->assertEquals('555-1234', $institute->getPhone());
        $this->assertEquals('123', $institute->getDnbContactId());
        $this->assertEquals(0, $institute->getIsGrantor());
        $this->assertEquals(1, $institute->getIsPublisher());
    }

    public function testUpdateFromArray()
    {
        $institute = new Opus_DnbInstitute();

        $institute->updateFromArray([
            'Name' => 'Solutions',
            'Department' => 'Big Solutions',
            'Address' => 'Research Street',
            'City' => 'Berlin',
            'Phone' => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor' => 0,
            'IsPublisher' => 1
        ]);

        $this->assertNotNull($institute);
        $this->assertInstanceOf('Opus_DnbInstitute', $institute);

        $this->assertEquals('Solutions', $institute->getName());
        $this->assertEquals('Big Solutions', $institute->getDepartment());
        $this->assertEquals('Research Street', $institute->getAddress());
        $this->assertEquals('Berlin', $institute->getCity());
        $this->assertEquals('555-1234', $institute->getPhone());
        $this->assertEquals('123', $institute->getDnbContactId());
        $this->assertEquals(0, $institute->getIsGrantor());
        $this->assertEquals(1, $institute->getIsPublisher());
    }

    public function testIsUsed()
    {
        $institute = new Opus_DnbInstitute();

        $institute->updateFromArray([
            'Name' => 'Solutions',
            'Department' => 'Big Solutions',
            'Address' => 'Research Street',
            'City' => 'Berlin',
            'Phone' => '555-1234',
            'DnbContactId' => '123',
            'IsGrantor' => 0,
            'IsPublisher' => 1
        ]);

        $institute->store();

        $this->assertFalse($institute->isUsed());

        $document = new Opus_Document();
        $document->addThesisPublisher($institute);
        $document->store();

        $this->assertTrue($institute->isUsed());

        $document->setThesisPublisher(null);
        $document->store();

        $this->assertFalse($institute->isUsed());

        $document->setThesisGrantor($institute);
        $document->store();

        $this->assertTrue($institute->isUsed());
    }

    public function testName191Chars()
    {
        $institute = new Opus_DnbInstitute();

        $name = str_repeat('0123456789', 19);

        $name .= '0';

        $this->assertTrue(strlen($name) === 191);

        $institute->updateFromArray([
            'Name' => $name,
            'City' => 'Berlin'
        ]);

        $instituteId = $institute->store();

        $institute = new Opus_DnbInstitute($instituteId);

        $this->assertEquals($name, $institute->getName());
    }

    /**
     * @expectedException Opus_Model_DbException
     * @expectedExceptionMessage truncated
     */
    public function testNameTooLong()
    {
        $institute = new Opus_DnbInstitute();

        $name = str_repeat('0123456789', 19);

        $name .= '0A';

        $this->assertTrue(strlen($name) === 192);

        $institute->updateFromArray([
            'Name' => $name,
            'City' => 'Berlin'
        ]);

        $instituteId = $institute->store();

        $institute = new Opus_DnbInstitute($instituteId);

        $this->assertEquals($name, $institute->getName());
    }

    /**
     * @expectedException Opus_Model_DbConstrainViolationException
     * @expectedExceptionMessage Duplicate entry
     */
    public function testNameAndDepartmentUnique()
    {
        $institute = new Opus_DnbInstitute();

        $name = str_repeat('0123456789', 19);
        $name .= '0';

        $department = str_repeat('0123456789', 19);
        $department .= '0';

        $this->assertTrue(strlen($name) === 191);
        $this->assertTrue(strlen($department) === 191);

        $institute->updateFromArray([
            'Name'       => $name,
            'Department' => $department,
            'City'       => 'Berlin'
        ]);

        $instituteId = $institute->store();

        $institute = new Opus_DnbInstitute($instituteId);

        $this->assertEquals($name, $institute->getName());
        $this->assertEquals($department, $institute->getDepartment());

        // try storing identical name and department
        $name = str_repeat('0123456789', 19);
        $name .= '0';

        $department = str_repeat('0123456789', 19);
        $department .= '0';

        $institute2 = new Opus_DnbInstitute();

        $institute2->updateFromArray([
            'Name'       => $name,
            'Department' => $department,
            'City'       => 'Berlin'
        ]);

        $institute2->store();
    }

    /**
     * We are trying to see if unique key is as long as both columns together or if differences beyond the length of
     * the key are ignored.
     */
    public function testNameAndDepartmentUniqueCheckWithLastCharacter()
    {
        $institute = new Opus_DnbInstitute();

        $name = str_repeat('0123456789', 19);
        $name .= '0';

        $department = str_repeat('0123456789', 19);
        $department .= '0';

        $this->assertTrue(strlen($name) === 191);
        $this->assertTrue(strlen($department) === 191);

        $institute->updateFromArray([
            'Name'       => $name,
            'Department' => $department,
            'City'       => 'Berlin'
        ]);

        $instituteId = $institute->store();

        $institute = new Opus_DnbInstitute($instituteId);

        $this->assertEquals($name, $institute->getName());
        $this->assertEquals($department, $institute->getDepartment());

        // try storing name and department that differ at the very last character of key
        $name = str_repeat('0123456789', 19);
        $name .= '0';

        $department = str_repeat('0123456789', 19);
        $department .= 'A';

        $institute2 = new Opus_DnbInstitute();

        $institute2->updateFromArray([
            'Name'       => $name,
            'Department' => $department,
            'City'       => 'Berlin'
        ]);

        $institute2->store();
    }
}
