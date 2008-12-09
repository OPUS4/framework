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
 * @package     Opus_Form
 * @author      Ralf Claußnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: Model.php 1167 2008-12-05 13:36:36Z claussnitzer $
 */

/**
 * Domain model for Form_Builder test that is not connected to a database row. It
 * serves as a reference model used with fields.
 *
 * @category    Tests
 * @package     Opus_Form
 * @uses        Opus_Model_Interface
 */
class Opus_Form_BuilderTest_DisconnectedModel implements Opus_Model_Interface {

    
    /**
     * Holds a simple field instance.
     *
     * @var Opus_Model_Field
     */
    protected $_field = null;
    
    /**
     * Set up simple field instance "field1";
     *
     * @return void
     */
    public function __construct() {
        $this->_field = new Opus_Model_Field('Field1');
    }
    
    /**
     * Just a mock function.
     *
     * @see Opus_Model_Interface
     * @return void
     */
    public function store() {
        
    }

    /**
     * Always returns 4711.
     *
     * @see Opus_Model_Interface
     * @return Integer 4711.
     */
    public function getId() {
        return 4711;
    }

    /**
     * Just a mock function.
     *
     * @see Opus_Model_Interface
     * @return void
     */
    public function delete() {
        
    }

    /**
     * Return a single field name.
     *
     * @return Mixed Model self description.
     */
    public function describe() {
        return array('Field1');
    }

    /**
     * Mock function.
     *
     * @param Opus_Model_Field $field Field instance that gets appended to the models field collection.
     * @return Opus_Model_Abstract Provide fluent interface.
     */
    public function addField(Opus_Model_Field $field) {
        return $this;
    }
    
    /**
     * Return a field definition.
     *
     * @param string $name Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name) {
        return $this->_field;
    }

    /**
     * Mock setter function.
     *
     * @param mixed $value
     * @return void
     */
    public function setField1($value) {
        $this->_field->setValue($value);
    }
    
    /**
     * Mock getter function.
     *
     * @return mixed
     */
    public function getField1() {
        return $this->_field->getValue();
    }
}
