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
 * @category    Framework
 * @package     Opus_Person
 * @author      Frank Niebling (niebling@slub-dresden.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Provides functions to add, remove, alter and retrieve person information.
 *
 * @category Framework
 * @package  Opus_Person
 */
class Opus_Person_Information {

    /**
     * Check if certain parameters are existent and non-empty or have a correct type respectivly.
     *
     * @param array   $data           Person data record.
     * @param boolean $forceKeyExists (Optional) If true, every parameter has to exist.
     * @throws InvalidArgumentException Thrown if a parameter is missing or has a wrong type.
     * @return void
     *
     */
    static protected function validate(array $data, $forceKeyExists = false) {
        // Check each valid parameter.
        $valid_keys = array('firstName', 'lastName', 'placeOfBirth', 'dateOfBirth', 'email', 'academicTitle');
        foreach ($valid_keys as $key) {
            if (array_key_exists($key, $data) === true) {
                if (empty($data[$key]) === true) {
                    // Academic title is an optional parameter and
                    // might be existent but empty.
                    if ($key !== 'academicTitle') {
                        throw new InvalidArgumentException($key . ' is empty.');
                    }
                } else {
                    // If it is non-empty it might be the birthdate or email parameter,
                    // so go on checking for correct types.
                    switch ($key) {
                        case 'dateOfBirth':
                            if (($data[$key] instanceof Zend_Date) === false) {
                                throw new InvalidArgumentException('Argument for date of birth is not a Zend_Date instance.');
                            }
                            break;

                        case 'email':
                            if (self::isValidEmail($data[$key]) === false) {
                                throw new InvalidArgumentException('Argument for email address is not a valid.');
                            }
                            break;

                        default:
                            // Just to make CodeSniffer happy.
                            break;
                    }
                }
            } else {
                // Throw exception if the presence of parameter is forced
                // but it is missing.
                if ($forceKeyExists === true) {
                    // Academic title is an optional parameter and
                    // might be existent but empty.
                    if ($key !== 'academicTitle') {
                        throw new InvalidArgumentException($key . ' does not exists.');
                    }
                }
            }
        }
    }

    /**
     * Use given person information to create a new record. Information is given
     * in the form of associative array containing the keys firstName, lastName, placeOfBirth,
     * dateOfBirth, email and academicTitle.
     *
     * firstName        - a person's first name
     * lastName         - a person's last name
     * placeOfBirth     - birthplace
     * dateOfBirth      - Zend_Date object representing the birthday
     * email            - contact email address
     * academicTitle    - (Optional) a person's academic title
     *
     * @param array $person Array with person information.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return integer Identifier of added person record.
     */
    static public function add(array $person) {
        self::validate($person, true);
        $persons = new Opus_Db_Persons();
        $id = $persons->insert(self::map($person));
        return (int) $id;
    }

    /**
     * Returns an array of person records according to given filter criteria or
     * identifier.
     *
     * firstName,    - (Optional) a person's first name
     * lastName,     - (Optional) a person's last name
     * placeOfBirth, - (Optional) birthplace
     * dateOfBirth,  - (Optional) Zend_Date object representing the birthday
     * email,        - (Optional) contact email address
     * academicTitle - (Optional) a person's academic title
     *
     * The returned array is empty if no records have been found. Otherwise it
     * will contain one or more array(s) of personal information simliar to the criteria
     * array.
     *
     * Another usage is to pass an array of identifiers (all integer) to fetch all
     * records having one of them as their private key; E.g. array(1,2,3) would
     * look up the three person records with the identifiers 1,2 and 3 respectivly.
     *
     * TODO Add wildcard support.
     *
     * @param array|integer $criteria Array with filter criteria or valid person identifier.
     *                                Alternatively an array of identifier numbers (all of type integer)
     *                                can be passed to retrieve all records that have an identifier
     *                                contained in this array.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array Array of person records matching the criteria.
     */
    static public function get($criteria) {
        // This is for querying by identifier.
        if (is_int($criteria) === true) {

            $persons = new Opus_Db_Persons();
            $row = $persons->find($criteria)->current();
            if (empty($row) === false) {
                return self::map($row);
            }
        } else if (is_array($criteria) === true) {
            // Here we query by criterias.
            if ( empty($criteria) === true ) {
                throw new InvalidArgumentException('Criteria array must not be empty.');
            }

            // Check for all integer values in the array
            // and treat them as identifiers to look for.
            $allint = array();
            foreach ($criteria as $c) {
                if (is_int($c) === true) {
                    $allint[] = $c;
                }
            }

            // Fetching all records having an identifier included in the array.
            $persons = new Opus_Db_Persons();
            $rows = $persons->fetchAll($allint);
            $result = array();
            foreach ($rows as $row) {
                $result[] = self::map($row);
            }

            // TODO Getting person record by criteria
            return $result;
        } else {
            // Invalid criteria has been passed.
            throw new InvalidArgumentException('Criteria must be either an integer or an array.');
        }

        // Default result if no record has been found.
        return array();
    }

    /**
     * Return an array containing all person records available.
     *
     * The returned array is either empty or consists of the following keys:
     *
     * firstName,    - a person's first name
     * lastName,     - a person's last name
     * placeOfBirth, - birthplace
     * dateOfBith,   - (Zend_Date) birthday
     * email,        - contact email address
     * academicTitle - (Optional) a person's academic title
     *
     * @return array Array of person information records.
     *
     */
    static public function getAll() {
        $result = array();
        $persons = new Opus_Db_Persons();
        $rowset = $persons->fetchAll();
        foreach ($rowset as $row) {
            $result[] = self::map($row);
        }
        return $result;
    }



    /**
     * Use given person information to update a record. The given array has to have
     * a 'Id' key set to a valid person identifier.
     *
     * id            - (Required) person's identifier
     * firstName     - (Optional) a person's first name
     * lastName      - (Optional) a person's last name
     * placeOfBirth  - (Optional) birthplace
     * dateOfBirth   - (Optional) birthday
     * email         - (Optional) contact email address
     * academicTitle - (Optional) a person's academic title
     *
     * @param array $person Array with person information.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function update(array $person) {

        // Check data integrity before making an update attempt.
        if (self::isValidIdentifier($person['id']) === false ) {
            throw new InvalidArgumentException('Invalid identifer given.');
        }
        self::validate($person);

        // Find the record to update.
        $persons = new Opus_Db_Persons();
        $row = $persons->find($person['id'])->current();

        // Reset its values from the given data.
        $row->setFromArray(self::map($person));
        $row->save();
    }

    /**
     * Remove a record identified by a given identifier value.
     *
     * @param integer $id Identifier of the specific record to be removed.
     * @throws InvalidArgumentException Is thrown on invalid argument.
     * @return void
     */
    static public function remove($id) {
        $persons = new Opus_Db_Persons();
        $persons->delete($id);
    }



    /**
     * Check wether a given identifier is valid.
     *
     * @param integer $id Record identifier to validate.
     * @return boolean False, if the given identifier is invalid.
     */
    static protected function isValidIdentifier($id) {
        return is_int($id);
    }

    /**
     * Check if a given email address is valid.
     *
     * @param string $email Email address string.
     * @return boolean False, if the given string is not a valid email address.
     */
    protected static function isValidEmail($email) {
        $validator = new Zend_Validate_EmailAddress();
        return $validator->isValid($email);
    }

    /**
     * Maps person information from Zend_Db_Table_Row object to array and vice versa.
     *
     * The returned array has the following keys when given an Zend_Db_Table_Row:
     *
     * firstName    - a person's first name
     * lastName     - a person's last name
     * placeOfBirth - birthplace
     * dateOfBith   - (Zend_Date) birthday
     * email        - contact email address
     * academicTitle - (Optional) a person's academic title
     *
     * If an array in this form is given, it returns the keys ready to be used as an
     * parameter for the insert() method:
     *
     * first_name     - a person's first name
     * last_name      - a person's last name
     * place_of_birth - birthplace
     * date_of_birth  - (Zend_Date) birthday
     * email          - contact email address
     * academic_title - a person's academic title
     *
     * @param Zend_Db_Table_Row|array $data Either a Zend_Db_Table_Row object or an array representing a
     *                                      person record .
     * @throws InvalidArgumentException Thrown if the given person record is neither an Zend_Db_Tabel_Row
     *                                  object nor an array representing a person record.
     * @return array Array with person information as described in the method comment.
     */
    protected static function map($data) {
        if (($data instanceof Zend_Db_Table_Row) === true) {
            return array(
                'id'            => (int) $data->persons_id,
                'firstName'     => $data->first_name,
                'lastName'      => $data->last_name,
                'placeOfBirth'  => $data->place_of_birth,
                'dateOfBirth'   => new Zend_Date($data->date_of_birth),
                'email'         => $data->email,
                'academicTitle' => $data->academic_title
            );
        }
        if (is_array($data) === true) {
            $result = array(
                'first_name'     => $data['firstName'],
                'last_name'      => $data['lastName'],
                'place_of_birth' => $data['placeOfBirth'],
                'date_of_birth'  => $data['dateOfBirth']->getIso(),
                'email'          => $data['email'],
            // Insert empty parameter to have all keys presented.
                'academic_title' => '');
            if (array_key_exists('academicTitle', $data) === true) {
                $result['academic_title'] = $data['academicTitle'];
            }
            if (array_key_exists('id', $data) === true) {
                if (self::isValidIdentifier($data['id']) === true ) {
                    $result['persons_id'] = (int) $data['id'];
                }
            }
            return $result;
        }
        throw new InvalidArgumentException('The given object does not represent a person record.');
    }

}