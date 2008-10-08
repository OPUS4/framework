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
 * @package     Opus_Validate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Defines an validator checking the class type of a given object.
 *
 * @category    Framework
 * @package     Opus_Validate
 */
class Opus_Validate_InstanceOf extends Zend_Validate_Abstract {

    /**
     * Error message key for invalid type.
     *
     */
    const MSG_TYPE = 'instance';

    /**
     * Error message templates.
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::MSG_TYPE => "'%value%' is not of expected type '%classname%'.",
    );

    /**
     * Placeholder for message variables.
     *
     * @var array
     */
    protected $_messageVariables = array(
        // This points to the protected variable defined below
        'classname' => '_classname' 
    );
    
    /**
     * Holds the name of the expected class.
     *
     * @var string
     */
    protected $_classname = '';
    
    /**
     * Initialize the validator with the expected class name.
     *
     * @param string $classname Name of the class to check objects for.
     * @throws InvalidArgumentException If $classname is empty.
     */
    public function __construct($classname) {
        if (empty($classname) === true) {
            throw new InvalidArgumentException('A classname has to be given.');
        }
        $this->_classname = $classname;
    }

    /**
     * Return the name of the class objects get validated against.
     *
     * @return string Classname.
     */
    public function getExpectedClassName() {
        return $this->_classname;
    }
    
    /**
     * Validate the given object instance.
     *
     * @param mixed $value An object.
     * @return boolean True if the given object is an instance of the expected class.
     */
    public function isValid($value)
    {
        $this->_setValue(get_class($value));

        if (($value instanceof $this->_classname) === false) {
            $this->_error(self::MSG_TYPE);
            return false;
        }

        return true;
    }

}