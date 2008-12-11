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
 * Domain model for fields in the Opus framework
 *
 * @category Framework
 * @package  Opus_Model
 */
class Opus_Model_Field
{

    /**
     * Hold validator.
     *
     * @var Zend_Validate_Interface
     */
    protected $_validator = null;

    /**
     * Hold value filter.
     *
     * @var Zend_Filter
     */
    protected $_filter = null;

    /**
     * Hold multiplicity constraint.
     *
     * @var Integer|String
     */
    protected $_multiplicity = 1;

    /**
     * Specifiy whether the field is required or not.
     *
     * @var unknown_type
     */
    protected $_mandatory = false;

    /**
     * Hold the fields value.
     *
     * @var mixed
     */
    protected $_value = null;

    /**
     * Holds the classname for external fields.
     *
     * @var string
     */
    protected $_valueModelClass = null;


    /**
     * Holds the fields default values. For selection list fields this should
     * contain the list of options.
     *
     * @var mixed
     */
    protected $_default = null;


    /**
     * Internal name of the field.
     *
     * @var string
     */
    protected $_name = '';

    /**
     * Specify if a field can be displayed as a text box.
     *
     * @var boolean
     */
    protected $_textarea = false;


    /**
     * Specify if a field can be displayed as a selection list.
     *
     * @var boolean
     */
    protected $_selection = false;

    
    /**
     * Set to true if the field value has been modified.
     *
     * @var Boolean
     */
    protected $_modified = false;
    
    /**
     * Create an new field instance and set the given name.
     *
     * Creating a new instance also sets some default values:
     * - type = DT_TEXT
     * - multiplicity = 1
     * - languageoption = false
     * - mandatory = false
     *
     * @param string $name Internal name of the field.
     */
    public function __construct($name) {
        $this->_name = $name;
    }

    /**
     * Get the internal name of the field.
     *
     * @return String Internal field name.
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Set a validator for the field.
     *
     * @param Zend_Validate_Interface $validator A validator.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setValidator(Zend_Validate_Interface $validator) {
        $this->_validator = $validator;
        return $this;
    }

    /**
     * Get the assigned validator for the field.
     *
     * @return Zend_Validate_Interface The fields validator if one is assigned.
     */
    public function getValidator() {
        return $this->_validator;
    }


    /**
     * Set a filter for the field.
     *
     * @param Zend_Filter $filter A filter.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setFilter(Zend_Filter $filter) {
        $this->_filter = $filter;
        return $this;
    }

    /**
     * Get the assigned filter for the field.
     *
     * @return Zend_Filter The fields filter if one is assigned.
     */
    public function getFilter() {
        return $this->_filter;
    }

    /**
     * Set multiplicity constraint for multivalue fields.
     *
     * @param Integer|String $max Upper limit for multiple values.
     *                            Either a number or "*" for infinity.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setMultiplicity($max) {
        if ($max !== '*') {
            if ((is_int($max) === false) or ($max < 1)) {
                throw new InvalidArgumentException('Only integer values > 1 or "*" allowed.');
            }
        }
        $this->_multiplicity = $max;
        return $this;
    }

    /**
     * Return the fields maximum number of values.
     *
     * @return Integer|String Upper limit for multiple values.
     *                        Either a number or "*" for infinity.
     */
    public function getMultiplicity() {
        return $this->_multiplicity;
    }

    /**
     * Return whether the field has a multiplicity greater 1.
     *
     * @return Boolean True, if field can have multiple values.
     */
    public function hasMultipleValues() {
        $mult = $this->getMultiplicity();
        return (($mult > 1) or ($mult === '*'));
    }

    /**
     * Set the mandatory flag for the field. This flag states out whether a field is required
     * to have a value or not.
     *
     * @param boolean $mandatory Set to true if the field shall be a required field.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setMandatory($mandatory) {
        $this->_mandatory = (bool) $mandatory;
        return $this;
    }

    /**
     * Get the mandatory flag.
     *
     * @return Boolean True, if the field is marked tobe mandatory.
     */
    public function getMandatory() {
        return $this->_mandatory;
    }

    /**
     * Set the field value. If multiplicity is set to values greater than one
     * only array are valid input values.
     *
     * @param mixed $value The field value to be set.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setValue($value) {
        // If the fields value is not going to change, leave.
        if ($value === $this->_value) {
            return $this;
        }
        
        $multiValueCondition = $this->hasMultipleValues();
        $arrayCondition = is_array($value);

        if (($multiValueCondition === false) and ($arrayCondition === true)) {
            throw new InvalidArgumentException('Multivalue option and input argument do not match.');
        }
        
        if (($multiValueCondition === true) and ($arrayCondition === false)) {
            $value = array($value);
        }

        $this->_value = $value;
        $this->_modified = true;
        return $this;
    }


    /**
     * Get the fields value
     *
     * @param  int $index (Optional) The index of the value, if it's an array.
     * @return Mixed Whatever the value of the field might be.
     */
    public function getValue($index = null) {
        // Caller requested specific array index
        if (is_null($index) === false) {
            if (is_array($this->_value) === true and isset($this->_value[$index]) === true) {
                return $this->_value[$index];
            } else {
                throw new InvalidArgumentException('Unvalid index: ' . $index);
            }
            // Return value
        } else {
            // If multiple values are possible return an array in every case
            if (($this->hasMultipleValues() === true) and (is_array($this->_value) === false)) {
                return array($this->_value);
            }
            return $this->_value;
        }
    }

    /**
     * Set the fields default value.
     *
     * @param mixed $value The field default value to be set.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setDefault($value) {
        $this->_default = $value;
        return $this;
    }


    /**
     * Get the fields default value.
     *
     * @return mixed Whatever the default value of the field might be.
     */
    public function getDefault() {
        return $this->_default;
    }

    /**
     * Set the textarea property. Override selection property if textarea is set
     * to true.
     *
     * @param boolean $value True, if the field can be displayed as a text box.
     * @return void
     */
    public function setTextarea($value) {
        $this->_textarea = (bool) $value;
        if ($this->_textarea === true) {
            $this->_selection = false;
        }
    }

    /**
     * Return textarea property.
     *
     * @return Boolean True, if the field can be displayed as a text box.
     */
    public function getTextarea() {
        return $this->_textarea;
    }


    /**
     * Set the selection property. Override textarea property if selection is
     * set to true.
     *
     * @param boolean $value True, if the field can be displayed as a selection
     *                       list.
     * @return void
     */
    public function setSelection($value) {
        $this->_selection = (bool) $value;
        if ($this->_selection === true) {
            $this->_textarea = false;
        }
    }

    /**
     * Return selection property.
     *
     * @return Boolean True, if the field can be displayed as a selection list.
     */
    public function getSelection() {
        return $this->_selection;
    }

    /**
     * Return the name of model class if the field holds model instances.
     *
     * @return string Class name or null if the value is not a model.
     */
    public function getValueModelClass() {
        return $this->_valueModelClass;
    }

    /**
     * Set the name of model class if the field holds model instances.
     *
     * @param string Class name
     * @return void
     */
    public function setValueModelClass($classname) {
        $this->_valueModelClass = $classname;
    }

    /**
     * Tell whether the fields value has been modified.
     *
     * @return Boolean
     */
    public function isModified() {
        return $this->_modified;
    }
    
    /**
     * Set the modified flag back to false.
     *
     * @return void
     */
    public function clearModified() {
        $this->_modified = false;
    }
}
