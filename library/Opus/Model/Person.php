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
     * @see Opus_Model_Abstract::__construct()
     * @param mixed $id (Optional) Primary key of a persisted model instance.
     * @throws Opus_Model_Exception Thrown if an instance with the given primary key could not be found.
     */
    public function __construct($id = null) {
        parent::__construct(new Opus_Db_Persons, $id);
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
        $date_of_birth->setType(Opus_Model_Field::DT_DATE);

        $place_of_birth = new Opus_Model_Field('PlaceOfBirth');

        $email = new Opus_Model_Field('Email');
        $email->setValidator(new Zend_Validate_Email());

        $first_name = new Opus_Model_Field('FirstName');

        $last_name = new Opus_Model_Field('LastName');

        $this->addField($academic_title)
            ->addField($date_of_birth)
            ->addField($place_of_birth)
            ->addField($email)
            ->addField($first_name)
            ->addField($last_name);
    }
    
}
