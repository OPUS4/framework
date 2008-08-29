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
 * @package     Opus_Person
 * @author      Frank Niebling (niebling@slub-dresden.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Person_Information.
 *
 * @category Tests
 * @package  Opus_Person
 */
class Opus_Person_InformationTest extends PHPUnit_Framework_TestCase {

    /**
     * Clear persons table.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('persons');
    }

    /**
     * Used to calculate the order of two person records depending
     * on the value of their 'id' fields.
     *
     * @param array $person1 A Person record.
     * @param array $person2 Another Person record.
     * @return integer Returns an integer less than, equal to, or greater than zero
     *                 if the first element is considered to be respectively less than,
     *                 equal to, or greater than the second.
     *
     */
    static private function orderPersonsByIdentifer(array $person1, array $person2) {
        if ($person1['id'] < $person2['id']) {
            return -1;
        }
        if ($person1['id'] === $person2['id']) {
            return 0;
        }
        if ($person1['id'] > $person2['id']) {
            return 1;
        }
    }

    /**
     * Test data provider for valid person data.
     *
     * @return array An array, each entry containing valid person information.
     */
    public function validPersonDataProvider() {
        return array(
        array(array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => new Zend_Date('15.07.2008'),
            'email'        => 'mustermann@domain.com')),
        array(array(
            'firstName'    => 'Mathilde',
            'lastName'     => 'Musterfrau',
            'placeOfBirth' => 'Neualtkassel',
            'dateOfBirth'  => new Zend_Date('31.12.2008'),
            'email'        => 'huhu@hallo.de')),
        array(array(
            'academicTitle'=> '',
            'firstName'    => 'Tom',
            'lastName'     => 'TomTom',
            'placeOfBirth' => 'Hütte',
            'dateOfBirth'  => new Zend_Date('01.01.1100'),
            'email'        => 'tom@host.org'))
        );
    }

    /**
     * Test data provider for invalid person data.
     *
     * @return array An array, each entry containing partially invalid person information and
     *               an error message.
     */
    public function invalidPersonDataProvider() {
        return array(
        array(array(
            'academicTitle'=> 'Dr.',
            'firstName'    => '',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => new Zend_Date('01.01.1901'),
            'email'        => 'mustermann@domain.com'), 'Empty first name not rejected.'),
        array(array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => '',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => new Zend_Date('01.01.1901'),
            'email'        => 'mustermann@domain.com'), 'Empty last name not rejected.'),
        array(array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => '',
            'dateOfBirth'  => new Zend_Date('01.01.1901'),
            'email'        => 'mustermann@domain.com'), 'Empty place of birth not rejected.'),
        array(array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => -800,
            'email'        => 'mustermann@domain.com'), 'Invalid date of birth not rejected.'),
        array(array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => new Zend_Date('01.01.1901'),
            'email'        => 'xyz0815!?'), 'Invalid email address not rejected.')
        );
    }

    /**
     * Test data provider for invalid criteria data.
     *
     * @return array Each entry contains invalid criteria and error message.
     */
    public function invalidCriteriaProvider() {
        return array(
        array(null, 'Null criteria was not rejected.'),
        array(array(), 'Empty array criteria was not rejected.'),
        array('foobar', 'String criteria was not rejected.'),
        array(-0.12, 'Float value was not rejected.'),
        );
    }

    /**
     * Test if illegal person data values raise exceptions in add function.
     *
     * @param array  $data Person information record.
     * @param string $msg  Message to be shown on failure.
     * @return void
     *
     * @dataProvider invalidPersonDataProvider
     *
     */
    public function testCallAddFunctionWithInvalidValues(array $data, $msg) {
        try {
            Opus_Person_Information::add($data);
        } catch (InvalidArgumentException $ex) {
            return;
        }
        $this->fail($msg);
    }

    /**
     * Test if a person record can be created.
     *
     * @param array $data Person information record.
     * @return void
     *
     * @dataProvider validPersonDataProvider
     *
     */
    public function testAddPerson(array $data) {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $count_pre = (int) $dba->query('SELECT COUNT(*) FROM persons')->fetchColumn(0);

        Opus_Person_Information::add($data);

        $count_post = (int) $dba->query('SELECT COUNT(*) FROM persons')->fetchColumn(0);
        $this->assertGreaterThan($count_pre, $count_post, 'No new records in database.');
    }

    /**
     * Test if add() really returnes an integer value as its identifier.
     *
     * @return void
     */
    public function testAddReturnsInteger() {
        $data = self::validPersonDataProvider();
        $result = Opus_Person_Information::add($data[0][0]);
        $this->assertTrue(is_int($result), 'Returned identifier is not of type integer.');
    }

    /**
     * Test if get() returns an person record where the identifier has type integer
     * and the birthday is an Zend_Date object.
     *
     * @param array $data Person information record.
     * @return void
     *
     * @dataProvider validPersonDataProvider
     *
     */
    public function testGetReturnsCorrectTypes(array $data) {
        $id = Opus_Person_Information::add($data);
        $result = Opus_Person_Information::get($id);

        $this->assertTrue(is_int($result['id']), 'Retrieved identifier is not of type integer.');
        $this->assertTrue($result['dateOfBirth'] instanceof Zend_Date, 'Retrieved birthday is not of type Zend_Date.');
    }

    /**
     * Test if a call to get() with an invalid criteria raises an InvalidArgumentException.
     *
     * @param array|integer $criteria Criteria set by the data provider.
     * @param string        $msg      Message to show in case of failure.
     * @return void
     *
     * @dataProvider invalidCriteriaProvider
     */
    public function testGetWithInvalidCriteriaRaisesException($criteria, $msg) {
        try {
            Opus_Person_Information::get($criteria);
        } catch (InvalidArgumentException $ex) {
            return;
        }
        $this->fail($msg);
    }

    /**
     * Test if a formerly created person record can be retrieved by its id.
     *
     * @param array $data Person information record.
     * @return void
     *
     * @dataProvider validPersonDataProvider
     *
     */
    public function testGetPerson(array $data) {
        $id = Opus_Person_Information::add($data);

        $result = Opus_Person_Information::get($id);

        $this->assertFalse(empty($result), 'Result is empty.');
        $this->assertEquals($id, $result['id'], 'Result record id does not match requested id.');

        // Prepare the input data for comparation with result data.
        $data['id'] = $id;
        if (array_key_exists('academicTitle', $data) === false) {
            $data['academicTitle'] = '';
        }
        $this->assertEquals($data, $result, 'Retrieved record does not match stored record.');
    }

    /**
     * Test if attempt to retrieve a non-existent record delivers an empty result.
     *
     * @return void
     *
     */
    public function testGetNonexistentRecordDeliversEmtpyResult() {
       // Table is cleared before by setUp() so 4711 is not a used identifier.
       $result = Opus_Person_Information::get(4711);
       $this->assertTrue(empty($result), 'Result is not empty.');
    }

    /**
     * Test if all person records can be retrieved.
     *
     * @return void
     *
     */
    public function testGetAll() {
        // Insert some person records.
        $provided = self::validPersonDataProvider();
        $inserted = array();
        foreach ($provided as $array) {
            // The real data is wrapped twice within arrays.
            $record = $array[0];
            $id = Opus_Person_Information::add($record);
            // Prepare the input data for comparation with result data.
            $record['id'] = $id;
            if (array_key_exists('academicTitle', $record) === false) {
                $record['academicTitle'] = '';
            }
            $inserted[] = $record;
        }

        // Assume the all records have been processed.
        $this->assertEquals(count($provided), count($inserted), 'Not all records have been inserted.');

        // Retrieve all records.
        $all = Opus_Person_Information::getAll();

        // Assume that the number of inserted records is equal to the number of retrieved records.
        $this->assertEquals(count($all), count($inserted), 'Retrieved record count differs to inserted record count.');

        // Check if the date has been retrieved properly.
        usort($all, array('Opus_Person_InformationTest','orderPersonsByIdentifer'));
        usort($inserted, array('Opus_Person_InformationTest','orderPersonsByIdentifer'));

        $this->assertEquals($inserted, $all, 'Inserted records are not equal to retrieved ones.');
    }

    /**
     * Test if a set of records can be retrieved by passing an array of identifiers.
     *
     * @return void
     */
    public function testGetManyByIdCriteria() {
        // Insert some person records.
        $provided = self::validPersonDataProvider();
        $inserted = array();
        foreach ($provided as $array) {
            // The real data is wrapped twice within arrays.
            $record = $array[0];
            $inserted[] = Opus_Person_Information::add($record);
        }
        // Assume the all records have been processed.
        $this->assertEquals(count($provided), count($inserted), 'Not all records have been inserted.');

        // Retrieve all inserted records by id.
        $all = Opus_Person_Information::get($inserted);

        // Assume that the number of inserted records is equal to the number of retrieved records.
        $this->assertEquals(count($all), count($inserted), 'Retrieved record count differs to inserted record count.');
    }


    /**
     * Test if a person record can be stored, retrieved and updated.
     *
     * @return void
     */
    public function testUpdatePerson() {
        // Add a person.
        $person = array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => new Zend_Date('15.07.2008'),
            'email'        => 'mustermann@domain.com');
        $id = Opus_Person_Information::add($person);

        // Retrieve the record back from storage.
        $person_tmp = Opus_Person_Information::get($id);
        // Change and update it.
        $person_tmp['firstName'] = 'Moritz';
        $person_tmp['dateOfBirth'] = new Zend_Date('24.03.2007');
        Opus_Person_Information::update($person_tmp);

        // Get it right back using the original identifier.
        $person = Opus_Person_Information::get($id);

        // Check the changes.
        $this->assertEquals('Moritz', $person['firstName'], 'Update of first name attribute failed');
        $this->assertEquals(new Zend_Date('24.03.2007'), $person['dateOfBirth'], 'Update of birthday attribute failed');
    }



    /**
     * Test if illegal person data values raise exceptions in update function.
     *
     * @param array  $data Person information record.
     * @param string $msg  Message to be shown on failure.
     * @return void
     *
     * @dataProvider invalidPersonDataProvider
     *
     */
    public function testCallUpdateFunctionWithInvalidValues(array $data, $msg) {
        // Add a person.
        $person = array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => new Zend_Date('15.07.2008'),
            'email'        => 'mustermann@domain.com');
        $id = Opus_Person_Information::add($person);

        try {
            $data['id'] = $id;
            Opus_Person_Information::update($data);
        } catch (InvalidArgumentException $ex) {
            return;
        }
        $this->fail($msg);
    }


    /**
     * Test if adding and removing a person record works.
     *
     * @return void
     */
    public function testRetrievingRemovedRecord() {
        // Add a person.
        $person = array(
            'academicTitle'=> 'Dr.',
            'firstName'    => 'Max',
            'lastName'     => 'Mustermann',
            'placeOfBirth' => 'Musterstadt',
            'dateOfBirth'  => new Zend_Date('15.07.2008'),
            'email'        => 'mustermann@domain.com');
        $id = Opus_Person_Information::add($person);

        // Remove the person.
        Opus_Person_Information::remove($id);

        // Try to get the removed person again and expect an empty result.
        $result = Opus_Person_Information::get($id);
        $this->assertTrue(empty($result), 'Result is not empty.');
    }

}