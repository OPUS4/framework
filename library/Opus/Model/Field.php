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
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Model;

use Exception;
use InvalidArgumentException;
use Opus\Common\Model\ComparableInterface;
use Opus\Common\Model\FieldInterface;
use Opus\Common\Model\ModelException;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Dependent\Link\AbstractLinkModel;
use Zend_Validate_Interface;

use function array_merge;
use function array_pop;
use function array_values;
use function count;
use function get_class;
use function gettype;
use function in_array;
use function is_array;
use function is_bool;
use function is_int;
use function is_object;
use function is_string;
use function is_subclass_of;
use function spl_object_hash;

/**
 * Domain model for fields in the Opus framework
 *
 * TODO remove presentation layer functions like setCheckbox etc.
 *
 * phpcs:disable
 */
class Field implements ModificationTrackingInterface, FieldInterface
{
    /**
     * Hold validator.
     *
     * @var Zend_Validate_Interface
     */
    protected $validator;

    /**
     * Hold multiplicity constraint.
     *
     * @var int|string
     */
    protected $multiplicity = 1;

    /**
     * Simple check for multiple values.
     *
     * @var bool
     */
    private $hasMultipleValues = false;

    /**
     * Specifiy whether the field is required or not.
     *
     * @var bool
     */
    protected $mandatory = false;

    /**
     * Hold the fields value.
     *
     * @var mixed
     */
    protected $value;

    /**
     * Holds the classname for external fields.
     *
     * @var string
     */
    protected $valueModelClass;

    /**
     * Holds the classname for link fields.
     *
     * @var string
     */
    protected $linkModelClass;

    /**
     * Holds the classname of the model that the field belongs to.
     */
    protected $owningModelClass;

    /**
     * Holds the name of the sort field...
     *
     * @var string
     */
    protected $sortFieldName;

    /**
     * Holds the fields default values. For selection list fields this should
     * contain the list of options.
     *
     * @var mixed
     */
    protected $default;

    /**
     * Internal name of the field.
     *
     * @var string
     */
    protected $name = '';

    /**
     * Specify if a field can be displayed as a text box.
     *
     * @var bool
     */
    protected $textarea = false;

    /**
     * Specify if a field can be displayed as a selection list.
     *
     * @var bool
     */
    protected $selection = false;

    /**
     * Specify if a field can be displayed as a checkbox.
     *
     * @var bool
     */
    protected $checkbox = false;

    /**
     * Set to true if the field value has been modified.
     *
     * @var bool Saves the state of the field.
     */
    protected $modified = false;

    /**
     * Set of pending delete operations for dependent Models.
     *
     * @var array Associative array mapping object hashes to array('model' => $instance, 'token' => $deleteToken);
     */
    protected $pendingDeletes = [];

    /** @var null|string */
    private $type;

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
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Get the internal name of the field.
     *
     * @return string Internal field name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set a validator for the field.
     *
     * @param Zend_Validate_Interface $validator A validator.
     * @return $this Provide fluent interface.
     */
    public function setValidator(Zend_Validate_Interface $validator)
    {
        $this->validator = $validator;
        return $this;
    }

    /**
     * Get the assigned validator for the field.
     *
     * @return Zend_Validate_Interface The fields validator if one is assigned.
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * Set multiplicity constraint for multivalue fields.
     *
     * @param int|string $max Upper limit for multiple values, either a number or "*" for infinity.
     * @throws InvalidArgumentException If $max is neither "*" nor an integer.
     * @return $this Provide fluent interface.
     */
    public function setMultiplicity($max)
    {
        if ($max !== '*') {
            if ((is_int($max) === false) or ($max < 1)) {
                throw new InvalidArgumentException('Only integer values > 1 or "*" allowed.');
            }
        }
        $this->multiplicity      = $max;
        $this->hasMultipleValues = $max > 1 || $max === '*';
        return $this;
    }

    /**
     * Return the fields maximum number of values.
     *
     * @return int|string Upper limit for multiple values, either a number or "*" for infinity.
     */
    public function getMultiplicity()
    {
        return $this->multiplicity;
    }

    /**
     * Return whether the field has a multiplicity greater 1.
     *
     * @return bool True, if field can have multiple values.
     */
    public function hasMultipleValues()
    {
        return $this->hasMultipleValues;
    }

    /**
     * Set the mandatory flag for the field. This flag states out whether a field is required
     * to have a value or not.
     *
     * @param bool $mandatory Set to true if the field shall be a required field.
     * @return $this Provide fluent interface.
     */
    public function setMandatory($mandatory)
    {
        $this->mandatory = (bool) $mandatory;
        return $this;
    }

    /**
     * Get the mandatory flag.
     *
     * @return bool True, if the field is marked tobe mandatory.
     */
    public function isMandatory()
    {
        return $this->mandatory;
    }

    /**
     * Set the field value. If multiplicity is set to values greater than one
     * only array are valid input values.
     *
     * This method issues a delete() on dependent models when "null" is passed as value. (see rev2514)
     * It also calls delete() on models that appear to be in the value list, but not in the given argument. (see #434)
     *
     * @param mixed $value The field value to be set.
     * @throws InvalidArgumentException If Multivalue option and input argument do not match
     *         (an array is required but not given).
     * @return $this Provide fluent interface.
     */
    public function setValue($value)
    {
        // If the fields value is not going to change, leave.
        if (is_object($value) === true) {
            // ComparableInterface used for comparing Date objects
            if ($value instanceof ComparableInterface) {
                if ($value->compare($this->value) === 0) {
                    return $this;
                }

                // weak comparison for objects
                // TODO: DateTimeZone===DateTimeZone always returns true in weak equal check!  Why?
                // TODO: Why weak comparisons? They are tricky in PHP.
            } elseif ($value == $this->value) {
                return $this;
            }
        } else {
            // strong comparison for other values
            if ($value === $this->value) {
                return $this;
            }
        }

        if (true === is_array($value) && 1 === count($value)) {
            $value = array_pop($value);
        } elseif (true === is_array($value) && 0 === count($value)) {
            $value = null;
        } elseif (is_bool($value)) {
            // make sure 'false' is not converted to '' (empty string), but 0 for database
            $value = (int) $value;
        }

        // if null is given, delete dependent objects
        if (null === $value) {
            $this->_deleteDependentModels();
        } else {
            // otherwise process the given value(s)
            $multiValueCondition = $this->hasMultipleValues();
            $arrayCondition      = is_array($value);

            // Reject input if an array is required but not is given
            if ($multiValueCondition === false && $arrayCondition === true) {
                throw new InvalidArgumentException(
                    'Multivalue option and input argument do not match. (Field ' . $this->name . ')'
                );
            }

            // arrayfy value
            $values = $value;
            if (false === $arrayCondition) {
                $values = [$value];
            }

            // try to cast non-object values to model instance if valueModelClass is set
            $values = $this->_tryCastValuesToModel($values);

            // Check type of the values if _valueModelClass is set
            // and reject any input that is not of this type
            $this->typeCheckValues($values);

            // determine Opus\Model\Dependent\AbstractDependentModel instances that
            // are in the current value set but not in the given
            $this->_deleteUnreferencedDependentModels($values);

            // remove wrapper array if multivalue condition is not given
            if (false === $multiValueCondition) {
                $value = $values[0];
            }
        }

        // Re-set sort order:
        $this->_fixSortOrder($value);

        $this->value    = $value;
        $this->modified = true;
        return $this;
    }

    /**
     * Fixes sort order for a given array of fields.
     *
     * TODO For authors for instance this function prevents specifying the order using SortOrder.
     *
     * @param array $values
     */
    private function _fixSortOrder($values, $sortValue = 1)
    {
        if ($values === null) {
            return;
        }

        $sortField = $this->sortFieldName;
        if (! is_string($sortField)) {
            return;
        }

        $values = is_array($values) ? $values : [$values];

        foreach ($values as $valueNew) {
            $field        = $valueNew->getField($sortField);
            $oldSortValue = $field->getValue();
            if ($oldSortValue != $sortValue) {
                $field->setValue($sortValue);
            }
            $sortValue++;
        }
    }

    /**
     * Determines Opus\Model\Dependent\AbstractDependentModel instances that are in the fields current value
     * but not in the given in the update value and issue delete() to these Models.
     *
     * @param array &$values Reference to value update set.
     */
    private function _deleteUnreferencedDependentModels(array &$values)
    {
        // arrayfy field value for iteration
        $fvals = $this->value;
        if (false === is_array($fvals)) {
            $fvals = [$fvals];
        }

        $nids = [];
        if ($this->valueModelClass !== null) {
            foreach ($values as $val) {
                if (
                    false === $val instanceof AbstractDependentModel
                    or $val->getId() === null
                ) {
                    continue;
                }
                $nids[] = $val->getId();
            }
        }

        // collect removal candidates
        $removees = [];
        foreach ($fvals as $victim) {
            if ($victim instanceof AbstractDependentModel) {
                $vid = $victim->getId();
                if ($vid !== null && false === in_array($vid, $nids)) {
                    $removees[] = $victim;
                }
            }
        }
        // delete obsolete dependent models
        $this->_deleteDependentModels($removees);
    }

    /**
     * Check if the values have the correct type.
     */
    private function typeCheckValues(array $values)
    {
        if ($this->valueModelClass === null) {
            return;
        }

        // determine expected type
        $etype = $this->valueModelClass;

        // typecheck each array element
        foreach ($values as $v) {
            // skip null values
            if (null === $v) {
                continue;
            }

            // values must be objects - should be checked before get_class.
            if (false === is_object($v)) {
                throw new ModelException(
                    "Expected object of type $etype but " . gettype($v) . ' given. ' . "(Field {$this->name})"
                );
            }

            // determine actual type
            if ($v instanceof AbstractLinkModel) {
                $vtype = $v->getModelClass();
            } else {
                $vtype = get_class($v);
            }

            // perform typecheck
            if ($vtype !== $etype) {
                if (false === is_subclass_of($vtype, $etype)) {
                    throw new ModelException(
                        "Value of type $vtype given but expected $etype. (Field {$this->name})"
                    );
                }
            }
        }
    }

    /**
     * Prepare delete calls to all dependent models stored in this field.
     * To actually delete those dependent model doPendingDeleteOperations() has to be called.
     *
     * @param null|array $removees (Optional) Array of Opus\Model\Dependent\AbstractDependentModel instances to be deleted.
     */
    private function _deleteDependentModels(?array $removees = null)
    {
        if (null === $removees) {
            $removees = is_array($this->value) ? $this->value : [$this->value];
        }

        foreach ($removees as $submodel) {
            if ($submodel instanceof AbstractDependentModel) {
                $token                           = $submodel->delete();
                $objhash                         = spl_object_hash($submodel);
                $this->pendingDeletes[$objhash] = ['model' => $submodel, 'token' => $token];
            }
        }
    }

    /**
     * Perform all pending delete operations for dependent Models.
     */
    public function doPendingDeleteOperations()
    {
        foreach (array_values($this->pendingDeletes) as $info) {
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
    private function _tryCastValuesToModel(array $values)
    {
        if ($this->valueModelClass === null) {
            return $values;
        }

        foreach ($values as $i => $value) {
            if (is_object($value) || $value === null) {
                continue;
            }

            try {
                $valueObj   = new $this->valueModelClass($value);
                $values[$i] = $valueObj;
            } catch (Exception $ex) {
                throw new ModelException(
                    "Failed to cast value '$value' to class '{$this->valueModelClass}'. (Field {$this->name})",
                    0,
                    $ex
                );
            }
        }
        return $values;
    }

    /**
     * Get the field's value. If the field is a multi-valued one it is guaranteed
     * that an array is returned.
     *
     * @param  null|int $index (Optional) The index of the value, if it's an array.
     * @throws InvalidArgumentException If you try to access an index, that does not exists.
     * @return Mixed Whatever the value of the field might be.
     */
    public function getValue($index = null)
    {
        // wrap start value in array if multivalue option is set for this field
        $this->value = $this->_wrapValueInArrayIfRequired($this->value);

        // Caller requested a specific array index
        if ($index !== null) {
            if (is_array($this->value)) {
                if (isset($this->value[$index])) {
                    return $this->value[$index];
                } else {
                    throw new InvalidArgumentException('Unvalid index: ' . $index);
                }
            } else {
                throw new InvalidArgumentException('Invalid index (' . $index . '). Requested value is not an array.');
            }
        }

        if ($this->value !== null || $this->getType() === 'bool') {
            switch ($this->getType()) {
                case 'int':
                    return (int) $this->value;
                case 'bool':
                    return (bool) $this->value;
            }
        }

        return $this->value;
    }

    /**
     * Wrap the passed value in a new array if this field can hold multiple values and the
     * given parameter is not already an array. If the passed parameter is null, an empty
     * array is returned.
     *
     * @param mixed $value Arbitrary value.
     * @return mixed|array The parameter value, or an array holding the parameter value.
     */
    private function _wrapValueInArrayIfRequired($value)
    {
        if (is_array($value) || ! $this->hasMultipleValues()) {
            return $value;
        }

        if ($value === null) {
            return [];
        }

        return [$value];
    }

    /**
     * If the field can have multiple values, this method adds a new value
     * to the already existing field values.
     *
     * @param mixed $value The value to add.
     * @throws InvalidArgumentException If no more values can be added to this value
     *         (f.e. multiplicity allows 2 values an both are set already).
     * @return $this Fluent interface.
     */
    public function addValue($value)
    {
        if ($this->hasMultipleValues() === false) {
            // One cannot add an array of values to an single-multiplicity field
            if (is_array($value) or $this->value !== null) {
                throw new \InvalidArgumentException('Cannot add multiple values to ' . $this->name);
            } else {
                $this->setValue($value);
                return $this;
            }
        }

        $this->value = $this->_wrapValueInArrayIfRequired($this->value);

        // Check type of the values if _valueModelClass is set
        // and reject any input that is not of this type
        if (null !== $this->valueModelClass) {
            foreach ($value as $v) {
                $vtype = get_class($v);
                $etype = $this->valueModelClass;
                if (get_class($v) !== $etype) {
                    throw new ModelException(
                        "Value of type $vtype given but expected $etype. (Field {$this->name})"
                    );
                }
            }
        }

        // Check multiplicity constraint
        if (is_int($this->multiplicity) === true) {
            $valueCount = is_array($value) ? count($value) : 1;

            if (
                ($valueCount > $this->multiplicity)
                || ($valueCount + count($this->value)) > $this->multiplicity
            ) {
                throw new InvalidArgumentException(
                    'Field ' . $this->name . ' cannot hold more then ' . $this->multiplicity . ' values.'
                );
            }
        }

        if (is_string($this->sortFieldName)) {
            $sortField = $this->sortFieldName;

            $sortValueMax = 0;
            foreach ($this->value as $valueOld) {
                $sortValue = $valueOld->getField($sortField)->getValue();
                if ($sortValue > $sortValueMax) {
                    $sortValueMax = $sortValue;
                }
            }

            $this->_fixSortOrder($value, $sortValueMax + 1);
        }

        // Add the value to the array
        if (is_array($value) === true) {
            $this->value = array_merge($this->value, $value);
        } else {
            $this->value[] = $value;
        }

        $this->modified = true;
        return $this;
    }

    /**
     * Set the fields default value.
     *
     * @param mixed $value The field default value to be set.
     * @return $this Provide fluent interface.
     */
    public function setDefault($value)
    {
        $this->default = $value;
        return $this;
    }

    /**
     * Get the fields default value.
     *
     * @return mixed Whatever the default value of the field might be.
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set the textarea property. Override other properties if textarea is set
     * to true.
     *
     * @param bool $value True, if the field can be displayed as a text box.
     */
    public function setTextarea($value)
    {
        $this->textarea = (bool) $value;
        if ($this->textarea === true) {
            $this->checkbox  = false;
            $this->selection = false;
        }
    }

    /**
     * Return textarea property.
     *
     * @return bool True, if the field can be displayed as a text box.
     */
    public function isTextarea()
    {
        return $this->textarea;
    }

    /**
     * Set the selection property. Override other properties if selection is
     * set to true.
     *
     * @param bool $value True, if the field can be displayed as a selection
     *                       list.
     * @return $this Provide fluent interface.
     */
    public function setSelection($value)
    {
        $this->selection = (bool) $value;
        if ($this->selection === true) {
            $this->checkbox = false;
            $this->textarea = false;
        }
        return $this;
    }

    /**
     * Return selection property.
     *
     * @return bool True, if the field can be displayed as a selection list.
     */
    public function isSelection()
    {
        return $this->selection;
    }

    /**
     * Return the name of model class if the field holds model instances.
     *
     * @return string Class name or null if the value is not a model.
     */
    public function getValueModelClass()
    {
        return $this->valueModelClass;
    }

    /**
     * Set the name of model class if the field holds model instances.
     *
     * @param  string $classname The name of the class that is used as model for this field or null.
     * @return $this Fluent interface.
     */
    public function setValueModelClass($classname)
    {
        $this->valueModelClass = $classname;
        return $this;
    }

    /**
     * Return the name of model class if the field holds link model instances.
     *
     * @return string Class name or null if the value is not a model.
     */
    public function getLinkModelClass()
    {
        return $this->linkModelClass;
    }

    /**
     * Set the name of model class if the field holds link model instances.
     *
     * @param  string $classname The name of the class that is used as model for this field or null.
     * @return $this Fluent interface.
     */
    public function setLinkModelClass($classname)
    {
        $this->linkModelClass = $classname;
        return $this;
    }

    /**
     * Return the name of the model class that owns the field.
     *
     * @return string Class name of the model that own the field or null.
     */
    public function getOwningModelClass()
    {
        return $this->owningModelClass;
    }

    /**
     * Set the name of the model class that owns the field.
     *
     * @param string $classname The name of the class that owns the field.
     * @return $this Fluent interface.
     */
    public function setOwningModelClass($classname)
    {
        $this->owningModelClass = $classname;
        return $this;
    }

    /**
     * Set the name of model field for sorting.
     *
     * @param  string $classname The field name used as model for sorting.
     * @return $this Fluent interface.
     */
    public function setSortFieldName($fieldname)
    {
        $this->sortFieldName = $fieldname;
        return $this;
    }

    /**
     * Tell whether the fields value has been modified.
     *
     * @return bool
     */
    public function isModified()
    {
        if ($this->value instanceof ModificationTrackingInterface) {
            if (true === $this->value->isModified()) {
                $this->modified = true;
            }
        } elseif (is_array($this->value)) {
            foreach ($this->value as $value) {
                if ($value instanceof ModificationTrackingInterface) {
                    if (true === $value->isModified()) {
                        $this->modified = true;
                    }
                }
            }
        }

        return $this->modified;
    }

    /**
     * Set the modified flag back to false.
     */
    public function clearModified()
    {
        $this->modified = false;
        $this->clearModifiedOfValueModels();
    }

    /**
     * Removes modified status from value objects.
     */
    protected function clearModifiedOfValueModels()
    {
        if ($this->value instanceof ModificationTrackingInterface) {
            $this->value->setModified(false);
        } elseif (is_array($this->value)) {
            foreach ($this->value as $value) {
                if ($value instanceof ModificationTrackingInterface) {
                    $value->setModified(false);
                }
            }
        }
    }

    /**
     * Trigger indication of modification.
     */
    public function setModified($modified = true)
    {
        $this->modified = $modified;
    }

    /**
     * Set the checkbox property. Override other properties if checkbox is
     * set to true.
     *
     * @param bool $value True, if the field can be displayed as a checkbox
     * @return $this Provide fluent interface.
     */
    public function setCheckbox($value)
    {
        $this->checkbox = (bool) $value;
        if ($this->checkbox === true) {
            $this->selection = false;
            $this->textarea  = false;
        }
        return $this;
    }

    /**
     * Return checkbox property.
     *
     * @return bool True, if the field can be displayed as a checkbox.
     */
    public function isCheckbox()
    {
        return $this->checkbox;
    }

    /**
     * @param null|string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }
}
