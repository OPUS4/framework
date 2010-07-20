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
 * @package     Opus
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for persons in the Opus framework
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Person extends Opus_Model_AbstractDb
{

    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Db_Persons';

    protected $_externalFields = array(
            'IdentifierPnd' => array(
                            'model' => 'Opus_Person_ExternalKey',
                            'options' => array('type' => 'pnd'),
                            'fetch' => 'lazy'
            ),
            'IdentifierLocal' => array(
                            'model' => 'Opus_Person_ExternalKey',
                            'options' => array('type' => 'local'),
                            'fetch' => 'lazy'
            ),            
            );

    /**
     * Initialize model with the following fields:
     * - AcademicTitle
     * - Email
     * - FirstName
     * - LastName
     *
     * @return void
     */
    protected function _init() {
        $first_name = new Opus_Model_Field('FirstName');
        $first_name->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $last_name = new Opus_Model_Field('LastName');
        $last_name->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $date_of_birth = new Opus_Model_Field('DateOfBirth');
        $place_of_birth = new Opus_Model_Field('PlaceOfBirth');

        $email = new Opus_Model_Field('Email');
        $email->setValidator(new Zend_Validate_EmailAddress());

        $this->addField($first_name)
            ->addField($last_name)
            ->addField($date_of_birth)
            ->addField($place_of_birth)
            ->addField($email);

        // Add fields to be used as external identifiers (optional only).
        $pndField = new Opus_Model_Field('IdentifierPnd');
        $pndField->setMultiplicity(1);
        $pndField->setMandatory(false);
        $this->addField($pndField);
        $localIdField = new Opus_Model_Field('IdentifierLocal');
        $localIdField->setMultiplicity(1);
        $localIdField->setMandatory(false);
        $this->addField($localIdField);
    }

    /**
     * Get uniform representation of names.
     *
     * @return string
     */
    public function getName() {
        return $this->getLastName() . ', ' . $this->getFirstName();
    }

    /**
     * Returns name.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getDisplayName()
     */
    public function getDisplayName() {
       return $this->getName();
    }

    /**
     * Fetches all documents associated to the person by a certain role.
     *
     * @param string $role The role that the person has for the documents.
     * @return array An array of Opus_Document
     */
    public function getDocumentsByRole($role) {
        // $documentsLinkTable = new Opus_Db_LinkPersonsDocuments();
        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_LinkPersonsDocuments');
        $documentsTable = Opus_Db_TableGateway::getInstance('Opus_Db_Documents');
        $documents = array();
        $select = $documentsLinkTable->select();
        $select->where('role=?', $role);
        foreach ($this->_primaryTableRow->findManyToManyRowset($documentsTable,
                $documentsLinkTable, null, null, $select) as $document) {
            $documents[] = new Opus_Document($document->id);
        }
        return $documents;
    }

    /**
     * Get a list of IDs for Persons that have the specified role for
     * certain documents.
     *
     * @param string $role Role name.
     * @return array List of Opus_Person Ids for Person models assigned to the specified Role.
     */
    public static function getAllIdsByRole($role) {
        // $documentsLinkTable = new Opus_Db_LinkPersonsDocuments();
        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_LinkPersonsDocuments');
        $tablename = $documentsLinkTable->info(Zend_Db_Table::NAME);
        $db = $documentsLinkTable->getAdapter();
        $select = $db->select()->from($tablename, array('person_id'))
            ->where('role = ? ', $role);
        $personIds = $documentsLinkTable->getAdapter()->fetchCol($select);

        if (is_null($personIds) === true) {
            $personIds = array();
        }

        return $personIds;
    }

    /**
     * Finds a person by a given identifier
     *
     * @param string $id The identifier that should be queried.
     * @param string $type [optional] The type of the identifier (local or pnd), default is local
     * @return array List of Opus_Person Ids for Person models assigned to the specified Role.
     */
    public static function findByIdentifier($id, $type = 'local') {
        // $documentsLinkTable = new Opus_Db_PersonExternalKeys();
        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_PersonExternalKeys');
        $tablename = $documentsLinkTable->info(Zend_Db_Table::NAME);
        $db = $documentsLinkTable->getAdapter();
        $select = $db->select()->from($tablename, array('person_id'))
            ->where('type = ? ', $type)
            ->where('value = ?', $id);
        $personIds = $documentsLinkTable->getAdapter()->fetchCol($select);

        if (is_null($personIds) === true) {
            $personIds = array();
        }

        return $personIds;
    }

    /**
     * Finds a person by a given name
     *
     * @param string $lastName The last name of the person to be queried.
     * @param string $firstName [optional] The first name of the person to be queried.
     * @return array List of Opus_Person Ids for Person models assigned to the specified Role.
     */
    public static function findByName($lastName, $firstName = null) {
        // $documentsLinkTable = new Opus_Db_Persons();
        $documentsLinkTable = Opus_Db_TableGateway::getInstance('Opus_Db_Persons');
        $tablename = $documentsLinkTable->info(Zend_Db_Table::NAME);
        $db = $documentsLinkTable->getAdapter();
        $select = $db->select()->from($tablename, array('id'));
        
        if ($firstName === null) {
        	$select = $db->select()->from($tablename, array('id'))
        	    ->where('last_name = ?', $lastName);
        }
        else {
        	$select = $db->select()->from($tablename, array('id'))
        	    ->where('last_name = ?', $lastName)
        	    ->where('first_name = ?', $firstName);
        }
        $personIds = $documentsLinkTable->getAdapter()->fetchCol($select);

        if (is_null($personIds) === true) {
            $personIds = array();
        }

        return $personIds;
    }

    /**
     * Retrieve all Opus_Person instances from the database.
     *
     * @return array Array of Opus_Person objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Person', 'Opus_Db_Persons');
    }

}
