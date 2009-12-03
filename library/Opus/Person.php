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
        $academic_title = new Opus_Model_Field('AcademicTitle');

        $email = new Opus_Model_Field('Email');
        $email->setValidator(new Zend_Validate_EmailAddress());

        $first_name = new Opus_Model_Field('FirstName');

        $last_name = new Opus_Model_Field('LastName');
        $last_name->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $name = new Opus_Model_Field('Name');

        $this->addField($academic_title)
            ->addField($email)
            ->addField($first_name)
            ->addField($last_name)
            ->addField($name);
    }

    /**
     * Get uniform representation of names.
     *
     * @return string
     */
    public function getName() {
        $name = '';
        if (false === is_null($this->getAcademicTitle()) && ! $this->getAcademicTitle() === '') {
            $name .= $this->getAcademicTitle() . ' ';
        }
        $name .= $this->getShortName();
        return $name;
    }
                    
   /**
     * Get short of name (no academic title)
     *
     * @return string
     */
    public function getShortName() {
        $name = '';
                                                        
        if (false === is_null($this->getLastName())) {
            $name .= $this->getLastName();
        }
        if (false === is_null($this->getFirstName())) {
            $name .= ', ' . $this->getFirstName();
        }
        return $name;
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
     * Virtual field to deliver uniform representation of names.
     *
     * @return string
     */
    public function _fetchName() {
        return $this->getName();
    }

    /**
     * Name is a virtual field (i.e. it is not persisted).
     *
     * @return void
     */
    public function _storeName() {
    }

    /**
     * Fetches all documents associated to the person by a certain role.
     *
     * @param string $role The role that the person has for the documents.
     * @return array An array of Opus_Document
     */
    public function getDocumentsByRole($role) {
        $documentsLinkTable = new Opus_Db_LinkPersonsDocuments();
        $documents = array();
        $select = $documentsLinkTable->select();
        $select->where('role=?', $role);
        foreach ($this->_primaryTableRow->findManyToManyRowset('Opus_Db_Documents',
                'Opus_Db_LinkPersonsDocuments', null, null, $select) as $document) {
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
        $documentsLinkTable = new Opus_Db_LinkPersonsDocuments();
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
     * Retrieve all Opus_Person instances from the database.
     *
     * @return array Array of Opus_Person objects.
     */
    public static function getAll() {
        return self::getAllFrom('Opus_Person', 'Opus_Db_Persons');
    }

}
