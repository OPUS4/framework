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
 * Interface for all domain models in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 */
interface Opus_Model_Interface
{
    /**
     * Persist all the models information to its database locations.
     *
     * @throws Opus_Model_Exception Thrown if the store operation could not be performed.
     * @return void
     */
    public function store();
    
    /**
     * Return the primary key that identifies the model instance in the database.
     * If called on a clean new instance, null is returned until a call to store(). 
     *
     * @return void
     */
    public function getId();
    
    /**
     * Remove the model instance from the database.
     *
     * @throws Opus_Model_Exception If a delete operation could not be performed on this model.
     */
    public function delete();
    
    /**
     * Returns describing information about the model. This includes the list
     * of fields and field properties thus others components know the field
     * interface to interact with the model.
     *
     * @return Mixed Model self description.
     */
    public function describe();
}
