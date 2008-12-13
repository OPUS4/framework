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
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for institutes in the Opus framework
 * 
 * TODO Currently just a mockup
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Abstract
 */
class Opus_Model_Institute extends Opus_Model_Interface
{
    
    
    protected $_fields = array();

    /**
     * TODO Connect to institutes-tree of Opus_Collection
     *
     * @param mixed $id Primary key of an institute or null for creating a new one.
     */
    public function __construct($id = null) {
        $this->_fields = array(
            'Name' => new Opus_Model_Field('Name'),
            'PostalAddress' => new Opus_Model_Field('PostalAddress'),
            'Site' => new Opus_Model_Field('Site'),        
        );
    }

    /**
     * Persist all the models information to its database locations.
     *
     * @throws Opus_Model_Exception Thrown if the store operation could not be performed.
     * @return void
     */
    public function store() {
        
    }

    /**
     * Return the primary key that identifies the model instance in the database.
     * If called on a clean new instance, null is returned until a call to store(). 
     *
     * @return void
     */
    public function getId() {
        return null;
    }

    /**
     * Remove the model instance from the database.
     *
     * @throws Opus_Model_Exception If a delete operation could not be performed on this model.
     * @return void
     */
    public function delete() {
        
    }

    /**
     * Returns describing information about the model. This includes the list
     * of fields and field properties thus others components know the field
     * interface to interact with the model.
     *
     * @return Mixed Model self description.
     */
    public function describe() {
        return array_keys($this->_fields);
    }
    
    /**
     * Add an field to the model. If a field with the same name has already been added,
     * it will be replaced by the given field.
     *
     * @param Opus_Model_Field $field Field instance that gets appended to the models field collection.
     * @return Opus_Model_Abstract Provide fluent interface.
     */
    public function addField(Opus_Model_Field $field) {
        return $this;
    }
    
    /**
     * Return a reference to an actual field.
     *
     * @param string $name Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name) {
        return $this->_fields[$name];
    }
    
}
