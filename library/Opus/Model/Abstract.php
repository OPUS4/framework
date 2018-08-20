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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Abstract class for all domain models in the Opus framework.
 * It implements set and get accessors for field handling and rendering
 * of an array and an xml representation as well.
 *
 * @category    Framework
 * @package     Opus_Model
 */
abstract class Opus_Model_Abstract
{

    /**
     * Holds all fields of the domain model.
     *
     * @var array
     */
    protected $_fields = [];

    /**
     * TODO This should be an option in externalFields[]
     *
     * Fields to be not reported by describe() and not accessable
     * via get/set/add methods.
     *
     * @var array
     */
    protected $_internalFields = [];

    /**
     * Logger for class.
     */
    private $logger;

    /**
     * Call to _init().
     *
     */
    public function __construct()
    {
        $this->_init();
    }

    /**
     * Overwrite to initialize custom fields.
     *
     * @return void
     */
    abstract protected function _init();

    /**
     * Magic method to access the models fields via virtual set/get methods.
     *
     * @param string $name      Name of the method beeing called.
     * @param array  $arguments Arguments for function call.
     * @throws InvalidArgumentException When adding a link to a field without an argument.
     * @throws Opus_Model_Exception     If an unknown field or method is requested.
     * @throws Opus_Security_Exception  If the current role has no permission for the requested operation.
     * @return mixed Might return a value if a getter method is called.
     */
    public function __call($name, array $arguments)
    {
        $accessor = substr($name, 0, 3);
        $fieldname = substr($name, 3);

        $argumentGiven = false;
        $argument      = null;
        if (false === empty($arguments)) {
            $argumentGiven = true;
            $argument = $arguments[0];
        }

        // Filter calls to unknown methods and turn them into an exception
        $validAccessors = ['set', 'get', 'add'];
        if (in_array($accessor, $validAccessors) === false) {
            throw new BadMethodCallException($name . ' is no method in this object.');
        }

        // check if requested field is known
        $field = $this->getField($fieldname);
        if (!isset($field)) {
            throw new Opus_Model_Exception('Unknown field: ' . $fieldname);
        }

        // check if set/add has been called with an argument
        if ((false === $argumentGiven) and ($accessor === 'set')) {
            throw new Opus_Model_Exception('Argument required for set() calls, none given.');
        }

        switch ($accessor) {
            case 'get':
                return $this->_getFieldValue($field, $argument);
                break;

            case 'set':
                return $this->_setFieldValue($field, $argument);
                break;

            case 'add':
                return $this->_addFieldValue($field, $argument);
                break;

            default:
                throw new Opus_Model_Exception('Unknown accessor function: ' . $accessor);
                break;
        }

    }

    /**
     * Implements field getter mechanism.
     *
     * @param Opus_Model_Field $field The field to work on.
     * @param mixed            $index Index of the element to fetch.
     *
     * @return mixed    The value of the field.
     */
    protected function _getFieldValue(Opus_Model_Field $field, $index)
    {
        if (!is_null($index)) {
            return $field->getValue($index);
        }

        return $field->getValue();
    }

    /**
     * Implements setter mechanism.
     *
     * @param Opus_Model_Field $field The field to work on.
     * @param array|null       $values Any value to set.
     * @return Opus_Model_Abstract Provide fluent interface.
     */
    protected function _setFieldValue(Opus_Model_Field $field, $values)
    {
        $field->setValue($values);
        return $this;
    }

    /**
     * Implements adder mechanism.
     *
     * @param Opus_Model_Field $field The field to work on.
     * @param mixed  $arguments Arguments passed in the get-call.
     *
     * @return Opus_Model_Abstract The added model (can be a new model).
     */
    protected function _addFieldValue(Opus_Model_Field $field, $value)
    {
        if (is_null($value)) {
            $modelclass = $field->getValueModelClass();
            if (is_null($modelclass)) {
                throw new Opus_Model_Exception(
                    'Add accessor without parameter currently only available for fields holding models.'
                );
            }
            $value = new $modelclass;
        }

        $field->addValue($value);
        return $value;
    }

    /**
     * Add an field to the model. If a field with the same name has already been added,
     * it will be replaced by the given field.
     *
     * @param Opus_Model_Field $field Field instance that gets appended to the models field collection.
     * @return Opus_Model_Abstract Provide fluent interface.
     */
    public function addField(Opus_Model_Field $field)
    {
        $this->_fields[$field->getName()] = $field;
        $field->setOwningModelClass(get_class($this));
        return $this;
    }

    /**
     * Return a reference to an actual field but only allow access to public fields.
     *
     * @param string $name Name of the requested field.
     * @throws Opus_Model_Exception If the field is internal.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name)
    {
        if (true === in_array($name, $this->_internalFields, true)) {
            throw new Opus_Model_Exception('Access to internal field not allowed: ' . $name);
        }
        return $this->_getField($name);
    }

    /**
     * Return a reference to an actual field.
     *
     * @param string $name Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    protected function _getField($name)
    {
        if (isset($this->_fields[$name])) {
            return $this->_fields[$name];
        }
        else {
            return null;
        }
    }

    /**
     * Checks if a given field exists as internal or external field.
     *
     * @param string $name Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function hasField($name)
    {
        return (true === isset($this->_fields[$name]))
                and (false === in_array($name, $this->_internalFields, true));
    }

    /**
     * Checks if a given field exists as internal or external field.
     *
     * @param string $name Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function hasMultipleValueField($name)
    {
        return (true === isset($this->_fields[$name]))
                and (false === in_array($name, $this->_internalFields, true))
                and (true === $this->_fields[$name]->getMultiplicity());
    }

    /**
     * Get a list of all fields attached to the model. Filters all fieldnames
     * that are defined to be inetrnal in $_internalFields.
     *
     * @see    Opus_Model_Abstract::_internalFields
     * @return array    List of fields
     */
    public function describe()
    {
        return array_diff(array_keys($this->_fields), $this->_internalFields);
    }

    /**
     * By default, the textual representation of a modeled entity is
     * its class name.
     *
     * @return string Model class name.
     */
    public function getDisplayName()
    {
        return get_class($this);
    }

    /**
     * Magic method called when string representation of object is requested.
     *
     * @return string String representation of the object.
     */
    public function __toString()
    {
        if (is_null($this->getDisplayName())) {
            return '';
        }

        return $this->getDisplayName();
    }

    /**
     * Get a nested associative array representation of the model.
     *
     * @return array A (nested) array representation of the model.
     */
    public function toArray()
    {
        $result = [];

        foreach (array_keys($this->_fields) as $fieldname) {

            $field = $this->_getField($fieldname);
            $fieldvalue = $field->getValue();

            if (!$field->hasMultipleValues()) {
                $fieldvalue = [$fieldvalue];
            }

            $fieldvalues = [];

            foreach ($fieldvalue as $value) {
                if ($value instanceof Opus_Model_Abstract) {
                    $fieldvalues[] = $value->toArray();
                }
                else if ($value instanceOf Zend_Date) {
                    $fieldvalues[] = $value->toArray();
                }
                else {
                    $fieldvalues[] = $value;
                }
            }

            if (!$field->hasMultipleValues()) {
                $fieldvalues = $fieldvalues[0];
            }

            $result[$fieldname] = $fieldvalues;
        }

        return $result;
    }

    /**
     * Creates object and initializes it with data.
     * @param $data
     * @return mixed
     */
    public static function fromArray($data)
    {
        $modelClass = get_called_class();
        $model = new $modelClass();
        $model->updateFromArray($data);
        return $model;
    }

    /**
     * Updates the model with the data from an array.
     *
     * New objects are created for values with a model class. If a link model class is specified those objects
     * are created as well.
     *
     * @param $data
     * @throws Opus_Model_Exception
     */
    public function updateFromArray($data)
    {
        $this->clearFields();

        foreach($data as $fieldName => $values) {
            $field = $this->getField($fieldName);
            if (!is_null($field)) {
                $fieldModelClass = $field->getValueModelClass();
                $linkModelClass = $field->getLinkModelClass();

                if (is_null($fieldModelClass)) {
                    $field->setValue($values);
                } else {
                    $models = [];
                    foreach ($values as $modelValues) {
                        $model = new $fieldModelClass();

                        if (!is_null($linkModelClass)) {
                            $linkModel = new $linkModelClass();
                            $linkModel->setModel($model);
                            $model = $linkModel;
                        }

                        $model->updateFromArray($modelValues);
                        $models[] = $model;
                    }
                    $field->setValue($models);
                }
            } else {
                $modelClass = get_called_class();
                $this->getLogger()->err("Unknown field name '$fieldName' in class '$modelClass'.");
            }
        }
    }

    /**
     * Returns a Dom representation of the model.
     *
     * @param array $excludeFields Array of fields that shall not be serialized.
     * @param Opus_Model_Xml_Strategy $strategy Version of Xml to process
     * @return DomDocument A Dom representation of the model.
     */
    public function toXml(array $excludeFields = null, $strategy = null)
    {
        if (is_null($excludeFields) === true) {
            $excludeFields = [];
        }
        if (is_null($strategy) === true) {
            $strategy = new Opus_Model_Xml_Version1();
        }
        $xml = new Opus_Model_Xml();
        $xml->setModel($this)
                ->exclude($excludeFields)
                ->excludeEmptyFields()
                ->setStrategy($strategy);
        return $xml->getDomDocument();
    }

    /**
     * Instantiates an Opus_Model from xml as delivered by the toXml() method.
     *
     * @param  DomDocument|string  $xml                The xml representing the model.
     * @param  Opus_Model_Xml      $customDeserializer (Optional) Specify a custom deserializer object.
     * @return Opus_Model_Abstract The Opus_Model derived from xml.
     */
    public static function fromXml($xml, Opus_Model_Xml $customDeserializer = null)
    {
        if (is_null($customDeserializer)) {
            $customDeserializer = new Opus_Model_Xml();
        }

        if ($xml instanceof DomDocument) {
            $customDeserializer->setDomDocument($xml);
        }
        else if (is_string($xml)) {
            $customDeserializer->setXml($xml);
        }
        else {
            throw new Opus_Model_Exception('Either DomDocument or xml string must be passed.');
        }

        return $customDeserializer->getModel();
    }


    /**
     * Loop through all fields and check if they are valid.
     *
     * Empty fields (null or empty string) get only validated if they are
     * marked to be mandatory.
     *
     * If a mandatory field contains models itself, a validation is triggered
     * on these models.
     *
     * @return Boolean True if all fields report to be valid, false if
     *                 at least one field fails validation.
     */
    public function isValid()
    {
        foreach ($this->_fields as $field) {
            $value = $field->getValue();
            $mandatory = $field->isMandatory();

            // skip optional and empty fields
            if ((false === $mandatory) and (is_null($value) or ('' === $value))) {
                continue;
            }

            // validate
            $validator = $field->getValidator();
            if (is_null($validator) === false) {
                if ($validator->isValid($value) === false) {
                    return false;
                }
            }

            if (true === $mandatory) {
                // submodel handling
                $fieldValues = $field->hasMultipleValues() ? $value : [$value];

                foreach ($fieldValues as $submodel) {
                    if ($submodel instanceof Opus_Model_Abstract) {
                        if ($submodel->isValid() === false) {
                            return false;
                        }
                    }
                }
            }
        }
        return true;
    }

    /**
     * Return list of validation errors per field.
     * E.g. 'FieldName' => array('Msg1', 'Msg2', ...)
     *
     * @return array Associative array mapping fieldnames to validation errors.
     */
    public function getValidationErrors()
    {
        $result = [];
        foreach ($this->_fields as $field) {
            $validator = $field->getValidator();
            if (null !== $validator) {
                $messages = $validator->getMessages();
                $result[$field->getName()] = $messages;
            }
        }
        return $result;
    }

    /**
     * Update the field values of this model by using
     * another model instance having the same fields.
     *
     * All fields of the given Model that also occur in the
     * the targeted Model (this instance) are used for update.
     *
     * To exclude fields from updating consider using a
     * Opus_Model_Filter decorator for the given update model.
     *
     * @return void
     *
     * TODO does not work recursive - values of submodels don't get transferred
     */
    public function updateFrom(Opus_Model_Abstract $model)
    {
        // use all available fields for update
        foreach ($model->describe() as $fieldname) {
            // find corresponding field to update
            $myfield = $this->_getField($fieldname);

            if (null !== $myfield) {
                // update the field
                $fieldvalue = $model->getField($fieldname)->getValue();
                $myfield->setValue($fieldvalue);
            }
        }
    }

    /**
     * Returns logger for this class.
     * @return Zend_Log
     * @throws Zend_Exception
     */
    public function getLogger()
    {
        if (is_null($this->logger)) {
            $this->logger = Zend_Registry::get('Zend_Log');
        }

        return $this->logger;
    }

    /**
     * Sets logger for this class.
     * @param $logger Zend_Log
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Clears all values from fields.
     */
    public function clearFields()
    {
        foreach($this->_fields as $fieldName => $field) {
            $field->setValue(null);
        }
    }
}
