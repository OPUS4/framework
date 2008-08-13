<?php
/**
 *
 */

/**
 * Provides functions to add, remove, alter and retrieve person information.
 *
 * @category Framework
 * @package  Opus_Person
 */
class Opus_Person_Information {


    /**
     * Use given person information to create a new record. Information is given
     * in the form of an array containing the keys firstName, lastName, placeOfBirth,
     * dateOfBirth, email and academicTitle.
     *
     * @param array $person Array with person information.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function add(array $person) {

    }

    /**
     * Use given person information to update a record. The given array has to have
     * a 'Id' key set to a valid person identifier.
     *
     * @param array $person Array with person information.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return void
     */
    static public function update(array $person) {

    }

    /**
     * Remove a record identified by a given Id value.
     *
     * @param integer $id Identifier of the specific record to be removed.
     * @throws InvalidArgumentException Is thrown on invalid argument.
     * @return void
     */
    static public function remove($id) {

    }

    /**
     * Returns an array of person records according to given filter criteria or
     * identifier.
     *
     * @param array|integer $criteria Array with filter criteria or valid person identifier.
     * @throws InvalidArgumentException Is thrown on invalid arguments.
     * @return array Array of person records matching the criteria.
     */
    static public function get($criteria) {
        return array();
    }

    /**
     * Return an array containing all person records available.
     *
     * @return array Array of person information records.
     */
    static public function getAll() {
        return array();
    }

}
