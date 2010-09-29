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
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_File.
 *
 * @package Opus
 * @category Tests
 *
 * @group DnbInstituteTests
 */
class Opus_DnbInstituteTests extends TestCase {

    public function setUp() {
        parent::setUp();
    }

    public function tearDown() {
        parent::tearDown();
    }

    public function testStoreAndLoadDnbInstitute() {
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
        
        $this->assertEquals($name, $loaded_institute->getName(),
                'Loaded other name, then stored.');
        $this->assertEquals($address, $loaded_institute->getAddress(),
                'Loaded other address, then stored.');
        $this->assertEquals($city, $loaded_institute->getCity(),
                'Loaded other city, then stored.');
        $this->assertEquals($phone, $loaded_institute->getPhone(),
                'Loaded other phone number, then stored.');
        $this->assertEquals($dnb_contact_id, $loaded_institute->getDnbContactId(),
                'Loaded other DNB contact ID, then stored.');
        $this->assertEquals($is_grantor, $loaded_institute->getIsGrantor(),
                'Loaded other information about grantor status, then stored.');
    }

    /**
     * Test if a set of dnb institutes can be retrieved by getAll().
     *
     * @return void
     */
    public function testRetrieveAllDnbInstitutes() {
        $dnb_institutes = array();
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

    public function testRetrieveGrantors() {
        $publishers = array();
        $grantors = array();
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
     * Test if the DnbInstitute display name matches its name.
     *
     * @return void
     */
    public function testDisplayNameMatchesName() {
        $dnbInstitute = new Opus_DnbInstitute();
        $dnbInstitute->setName('MyTestName');
        $this->assertEquals($dnbInstitute->getName(), $dnbInstitute->getDisplayName(), 'Displayname does not match name.');
    }

}