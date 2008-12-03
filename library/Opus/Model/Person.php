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
 * @package     Opus_Model
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
 * @package     Opus_Model
 * @uses        Opus_Model_Abstract
 */
class Opus_Model_Person extends Opus_Model_Abstract
{

    /**
     * Create a new person model instance.
     *
     * @param mixed         $id                (Optional) Primary key of a persisted model instance.
     * @param Zend_Db_Table $tableGatewayModel (Optional) The table gateway model to use.
     * @see    Opus_Model_Abstract::__construct()
     * @throws Opus_Model_Exception Thrown if an instance with the given primary key could not be found.
     */
    public function __construct($id = null, Zend_Db_Table $tableGatewayModel = null) {
        if ($tableGatewayModel === null) {
            parent::__construct($id, new Opus_Db_Persons);
        } else {
            parent::__construct($id, $tableGatewayModel);
        }
    }

    /**
     * Initialize model with the following fields:
     * - AcademicTitle
     * - DateOfBirth
     * - PlaceOfBirth
     * - Email
     * - FirstName
     * - LastName
     * 
     * @return void
     */
    protected function _init() {
        $academic_title = new Opus_Model_Field('AcademicTitle');

        $date_of_birth = new Opus_Model_Field('DateOfBirth');

        $place_of_birth = new Opus_Model_Field('PlaceOfBirth');

        $email = new Opus_Model_Field('Email');

        $first_name = new Opus_Model_Field('FirstName');

        $last_name = new Opus_Model_Field('LastName');

        $this->addField($academic_title)
            ->addField($date_of_birth)
            ->addField($place_of_birth)
            ->addField($email)
            ->addField($first_name)
            ->addField($last_name);
    }

    /**
     * Fetches all documents associated to the person by a certain role.
     *
     * @param string $role The role that the person has for the documents.
     * @return array An array of Opus_Model_Document
     */
    public function getDocumentsByRole($role) {
        $documentsLinkTable = new Opus_Db_LinkDocumentsPersons();
        $documents = array();
        $select = $documentsLinkTable->select();
        $select->where('role=?', $role);
        foreach ($this->_primaryTableRow->findManyToManyRowset('Opus_Db_Documents',
                'Opus_Db_LinkDocumentsPersons', null, null, $select) as $document) {
            $documents[] = new Opus_Model_Document($document->documents_id);
        }
        return $documents;
    }

}
