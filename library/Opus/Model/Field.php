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
class Opus_Model_Field implements Opus_Model_ModificationTracking {

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
     * Holds the classname for link fields.
     *
     * @var string
     */
    protected $_linkModelClass = null;

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
     * Specify if a field can be displayed as a checkbox.
     *
     * @var boolean
     */
    protected $_checkbox = false;

    /**
     * Set to true if the field value has been modified.
     *
     * @var boolean Saves the state of the field.
     */
    protected $_modified = false;
    
    
    /**
     * Set of pending delete operations for dependent Models.
     *
     * @var array Associative array mapping object hashes to array('model' => $instance, 'token' => $deleteToken);
     */
    protected $_pendingDeletes = array();
    

    // TODO: Dirty hack to set this "true".  You should have a good reason!  If
    // TODO: you find an issue related to this field, please file a bug report.
    private $ignoreMultiplicity = true;


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
     * @param Zend_Filter_Interface $filter A filter.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setFilter(Zend_Filter_Interface $filter) {
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
     * @param integer|string $max Upper limit for multiple values, either a number or "*" for infinity.
     * @throws InvalidArgumentException If $max is neither "*" nor an integer.
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
     * @return integer|string Upper limit for multiple values, either a number or "*" for infinity.
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
    public function isMandatory() {
        return $this->_mandatory;
    }

    /**
     * Set the field value. If multiplicity is set to values greater than one
     * only array are valid input values.
     *
     * This method issues a delete() on dependent models when "null" is passed as value. (see rev2514)
     * It also calls delete() on models that appear to be in the value list, but not in the given argument. (see #434)
     *
     * @param mixed $value The field value to be set.
     * @throws InvalidArgumentException If Multivalue option and input argument do not match (an array is required but not given).
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setValue($value) {
        // If the fields value is not going to change, leave.
        if (is_object($value) === true) {
            // weak comparison for objects
            if ($value == $this->_value) {
                return $this;
            }
        } else {
            // strong comparison for other values
            if ($value === $this->_value) {
                return $this;
            }
        }

        if (true === is_array($value) and 1 === count($value)) {
            $value = array_pop($value);
        } else if (true === is_array($value) and 0 === count($value)) {
            $value = null;
        }

        // if null is given, delete dependent objects
        if (null === $value) {
            $this->_deleteDependentModels();
        } else {
            // otherwise process the given value(s)
            $multiValueCondition = $this->hasMultipleValues();
            $arrayCondition = is_array($value);

            // Reject input if an array is required but not is given
            if (($multiValueCondition === false) and ($arrayCondition === true) and ($this->ignoreMultiplicity === false)) {
                throw new InvalidArgumentException('Multivalue option and input argument do not match. (Field ' . $this->_name . ')');
            }

            // arrayfy value
            $values = $value;
            if (false === $arrayCondition) {
                $values = array($value);
            }

            // try to cast non-object values to model instance if valueModelClass is set
            $values = $this->_tryCastValuesToModel($values);

            // Check type of the values if _valueModelClass is set
            // and reject any input that is not of this type
            $this->_typeCheckValues($values);

            // determine Opus_Model_Dependent_Abstract instances that
            // are in the current value set but not in the given
            $this->_deleteUnreferencedDependentModels($values);
            
            // remove wrapper array if multivalue condition is not given
            if (false === $multiValueCondition) {
                $value = $values[0];
            }
        }
        $this->_value = $value;
        $this->_modified = true;
        return $this;
    }

    /**
     * Determines Opus_Model_Dependent_Abstract instances that are in the fields current value
     * but not in the given in the update value and issue delete() to these Models.
     *
     * @param array &$values Reference to value update set.
     * @return void
     */
    private function _deleteUnreferencedDependentModels(array &$values) {
        // arrayfy field value for iteration
        $fvals = $this->_value;
        if (false === is_array($fvals)) {
            $fvals = array($fvals);
        }

        $nids = array();
        if (false === is_null($this->_valueModelClass)) {
            foreach ($values as $val) {
                if (false === $val instanceof Opus_Model_Dependent_Abstract
                    or true === is_null($val->getId())) {
                    continue;
                }
                $nids[] = $val->getId();
            }
        }

        // collect removal candidates    
        $removees = array();
        foreach ($fvals as $victim) {
            if ($victim instanceof Opus_Model_Dependent_Abstract) {
                $vid = $victim->getId();
                if (false === is_null($vid) and false === in_array($vid, $nids)) {
                    $removees[] = $victim;
                }
            }
        }
        // delete obsolete dependent models
        $this->_deleteDependentModels($removees);
    }

    /**
     * Check if the values have the correct type.
     *
     * @return void
     */
    private function _typeCheckValues(array $values) {
        if (null !== $this->_valueModelClass) {
            // typecheck each array element
            foreach ($values as $v) {
                // skip null values
                if (null === $v) {
                    continue;
                }
                
                // determine expected type            
                $etype = $this->_valueModelClass;
                
                // values must be objects - should be checked before get_class.
                if (false === is_object($v)) {
                    throw new Opus_Model_Exception("Expected object of type $etype but " . gettype($v) . ' given. ' . "(Field {$this->_name})");
                }

                // determine actual type
                if ($v instanceof Opus_Model_Dependent_Link_Abstract) {
                    $vtype = $v->getModelClass();
                } else {
                    $vtype = get_class($v);
                }
                
                // perform typecheck
                if ($vtype !== $etype) {
                    if (false === is_subclass_of($vtype, $etype)) {
                        throw new Opus_Model_Exception("Value of type $vtype given but expected $etype. (Field {$this->_name})");
                    }
                }
            }
        }
    }

    /**
     * Prepare delete calls to all dependent models stored in this field.
     * To actually delete those dependent model doPendingDeleteOperations() has to be called.
     *
     * @param array $removees (Optional) Array of Opus_Model_Dependent_Abstract instances to be deleted.
     * @return void
     */
    private function _deleteDependentModels(array $removees = null) {
        if (null === $removees) {
            if (is_array($this->_value)) {
                foreach ($this->_value as $submodel) {
                    if ($submodel instanceof Opus_Model_Dependent_Abstract) {
                        $token = $submodel->delete();
                        $objhash = spl_object_hash($submodel);
                        $this->_pendingDeletes[$objhash] = array('model' => $submodel, 'token' => $token);
                    }
                }
            } else {
                if ($this->_value instanceof Opus_Model_Dependent_Abstract) {
                    $submodel = $this->_value;
                    $token = $submodel->delete();
                    $objhash = spl_object_hash($submodel);
                    $this->_pendingDeletes[$objhash] = array('model' => $submodel, 'token' => $token);
                }
            }
        } else {
            foreach ($removees as $submodel) {
                if ($submodel instanceof Opus_Model_Dependent_Abstract) {
                    $token = $submodel->delete();
                    $objhash = spl_object_hash($submodel);
                    $this->_pendingDeletes[$objhash] = array('model' => $submodel, 'token' => $token);
                }
            }
        }
    }
    
    /**
     * Perform all pending delete operations for dependent Models.
     *
     * @return void
     */   
    public function doPendingDeleteOperations() {
        foreach (array_values($this->_pendingDeletes) as $info) {
            $info['model']->doDelete($info['token']);
        }
    }
     

    /**
     * If a value model class is set for this field,
     * try to cast the given value to this model class.
     *
     * If casting is not possible, it returns the value.
     *
     * @param array $values Set of values.
     * @return array Set of new models of type _valueModelClass or the given values.
     */
    private function _tryCastValuesToModel(array $values) {
        if (null !== $this->_valueModelClass) {
            foreach ($values as $i => $value) {
                if ((false === is_object($value)) and (null !== $value)) {
                    try {
                        $valueObj = new $this->_valueModelClass($value);
                        $values[$i] = $valueObj;
                    } catch (Exception $ex) {
                        $values[$i] = $value;
                    }
                }
            }
        }
        return $values;
    }



    /**
     * Get the fields value
     *
     * @param  integer $index (Optional) The index of the value, if it's an array.
     * @throws InvalidArgumentException If you try to access an index, that does not exists.
     * @return Mixed Whatever the value of the field might be.
     */
    public function getValue($index = null) {
        // Caller requested specific array index
        if (is_null($index) === false) {
            if (is_array($this->_value) === true and array_key_exists($index, $this->_value) === true) {
                return $this->_value[$index];
            } else {
                throw new InvalidArgumentException('Invalid index: ' . $index);
            }
        } else {
            // If multiple values are possible return an array in every case
            if (($this->hasMultipleValues() === true) and (is_array($this->_value) === false)) {
                if (is_null($this->_value) === false) {
                    return array($this->_value);
                } else {
                    return array();
                }
            }
            return $this->_value;
        }
    }


    /**
     * If the field can have multiple values, this method adds a new value
     * to the already existing field values.
     *
     * @param mixed $value The value to add.
     * @throws InvalidArgumentException If no more values can be added to this value (f.e. multiplicity allows 2 values an both are set already).
     * @return Opus_Model_Field Fluent interface.
     */
    public function addValue($value) {

        if ($this->hasMultipleValues() === false) {
            // One cannot add an array of values to an single-multiplicity field
            if (is_array($value) or is_null($this->_value) === false) {
                throw new InvalidArgumentException('Cannot add multiple values to ' . $this->_name);
            } else {
                $this->setValue($value);
                return $this;
            }
        }

        // No value set yet.
        if (is_null($this->_value) === true) {
           $this->_value = array();
        } else {
            // If the value is not an array, make one
            if (is_array($this->_value) === false) {
                $this->_value = array($this->_value);
            }
        }

        // Check type of the values if _valueModelClass is set
        // and reject any input that is not of this type
        if (null !== $this->_valueModelClass) {
            foreach ($value as $v) {
                $vtype = get_class($v);
                $etype = $this->_valueModelClass;
                if (get_class($v) !== $etype) {
                    throw new Opus_Model_Exception("Value of type $vtype given but expected $etype. (Field {$this->_name})");
                }
            }
        }

        // Check multiplicity constraint
        if (is_int($this->_multiplicity) === true) {
            if ((count($value) > $this->_multiplicity)
                or ((count($value) + count($this->_value)) > $this->_multiplicity)) {
                throw new InvalidArgumentException('Field ' . $this->_name . ' cannot hold more then ' . $this->_multiplicity . ' values.');
            }
        }

        // Add the value to the array
        if (is_array($value) === true) {
            $this->_value = array_merge($this->_value, $value);
        } else {
            $this->_value[] = $value;
        }

        $this->_modified = true;
        return $this;
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
     * Set the textarea property. Override other properties if textarea is set
     * to true.
     *
     * @param boolean $value True, if the field can be displayed as a text box.
     * @return void
     */
    public function setTextarea($value) {
        $this->_textarea = (bool) $value;
        if ($this->_textarea === true) {
            $this->_checkbox = false;
            $this->_selection = false;
        }
    }

    /**
     * Return textarea property.
     *
     * @return Boolean True, if the field can be displayed as a text box.
     */
    public function isTextarea() {
        return $this->_textarea;
    }


    /**
     * Set the selection property. Override other properties if selection is
     * set to true.
     *
     * @param boolean $value True, if the field can be displayed as a selection
     *                       list.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setSelection($value) {
        $this->_selection = (bool) $value;
        if ($this->_selection === true) {
            $this->_checkbox = false;
            $this->_textarea = false;
        }
        return $this;
    }

    /**
     * Return selection property.
     *
     * @return Boolean True, if the field can be displayed as a selection list.
     */
    public function isSelection() {
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
     * @param  string $classname The name of the class that is used as model for this field or null.
     * @return Opus_Model_Field Fluent interface.
     */
    public function setValueModelClass($classname) {
        $this->_valueModelClass = $classname;
        return $this;
    }

    /**
     * Return the name of model class if the field holds link model instances.
     *
     * @return string Class name or null if the value is not a model.
     */
    public function getLinkModelClass() {
        return $this->_linkModelClass;
    }

    /**
     * Set the name of model class if the field holds link model instances.
     *
     * @param  string $classname The name of the class that is used as model for this field or null.
     * @return Opus_Model_Field Fluent interface.
     */
    public function setLinkModelClass($classname) {
        $this->_linkModelClass = $classname;
        return $this;
    }

    /**
     * Tell whether the fields value has been modified.
     *
     * @return boolean
     */
    public function isModified() {
        if ($this->_value instanceof Opus_Model_ModificationTracking) {
            if (true === $this->_value->isModified()) {
                $this->_modified = true;
            }
        }
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

    /**
     * Trigger indication of modification.
     *
     * @return void
     */
    public function setModified() {
        $this->_modified = true;
    }


    /**
     * Set the checkbox property. Override other properties if checkbox is
     * set to true.
     *
     * @param boolean $value True, if the field can be displayed as a checkbox
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setCheckbox($value) {
        $this->_checkbox = (bool) $value;
        if ($this->_checkbox === true) {
            $this->_selection = false;
            $this->_textarea = false;
        }
        return $this;
    }

    /**
     * Return checkbox property.
     *
     * @return Boolean True, if the field can be displayed as a checkbox.
     */
    public function isCheckbox() {
        return $this->_checkbox;
    }
    
}
