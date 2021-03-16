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
 * @package     Opus\Model
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model;

use Opus\Model\Dependent\Link\AbstractLinkModel;
use Opus\Model\Xml\Strategy;
use Opus\Model\Xml\Version1;

/**
 * Abstract class for all domain models in the Opus framework.
 * It implements set and get accessors for field handling and rendering
 * of an array and an xml representation as well.
 *
 * Properties
 *
 * The PropertySupportInterface defines functions that allow setting and
 * getting properties for model objects. Each property has a key and a
 * value. The values are strings. Keys can be up to 50 characters long.
 *
 * The properties are for storing system information that needs to be
 * associated with a model, like the extraction status of file or the
 * source of a document.
 *
 * The difference to a Enrichment is that a property is for system data
 * while an Enrichment stores actual content metadata about a document.
 * Properties are defined by developers, while an Enrichment is defined
 * by the institutions running OPUS 4.
 *
 *
 * @category    Framework
 * @package     Opus\Model
 */
abstract class AbstractModel implements PropertySupportInterface
{

    use \Opus\LoggingTrait;

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
     * @var Properties Access object for internal properties associated with model
     */
    private static $propertiesService;

    /**
     * Call to _init().
     *
     */
    public function __construct()
    {
        $this->_init();
    }

    public static function new()
    {
        return new static();
    }

    public static function get($modelId)
    {
        return new static($modelId);
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
     * @throws \InvalidArgumentException When adding a link to a field without an argument.
     * @throws ModelException     If an unknown field or method is requested.
     * @throws SecurityException  If the current role has no permission for the requested operation.
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
            throw new \BadMethodCallException($name . ' is no method in this object.');
        }

        // check if requested field is known
        $field = $this->getField($fieldname);
        if (! isset($field)) {
            throw new ModelException('Unknown field: ' . $fieldname);
        }

        // check if set/add has been called with an argument
        if ((false === $argumentGiven) and ($accessor === 'set')) {
            throw new ModelException('Argument required for set() calls, none given.');
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
                throw new ModelException('Unknown accessor function: ' . $accessor);
                break;
        }
    }

    /**
     * Implements field getter mechanism.
     *
     * @param $field Field The field to work on.
     * @param mixed            $index Index of the element to fetch.
     *
     * @return mixed    The value of the field.
     */
    protected function _getFieldValue(Field $field, $index)
    {
        if (! is_null($index)) {
            return $field->getValue($index);
        }

        return $field->getValue();
    }

    /**
     * Implements setter mechanism.
     *
     * @param Field $field The field to work on.
     * @param array|null       $values Any value to set.
     * @return AbstractModel Provide fluent interface.
     */
    protected function _setFieldValue(Field $field, $values)
    {
        $field->setValue($values);
        return $this;
    }

    /**
     * Implements adder mechanism.
     *
     * @param Field $field The field to work on.
     * @param mixed  $arguments Arguments passed in the get-call.
     *
     * @return AbstractModel The added model (can be a new model).
     */
    protected function _addFieldValue(Field $field, $value)
    {
        if (is_null($value)) {
            $modelclass = $field->getValueModelClass();
            if (is_null($modelclass)) {
                throw new ModelException(
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
     * @param Field $field Field instance that gets appended to the models field collection.
     * @return AbstractModel Provide fluent interface.
     */
    public function addField(Field $field)
    {
        $this->_fields[$field->getName()] = $field;
        $field->setOwningModelClass(get_class($this));
        return $this;
    }

    /**
     * Return a reference to an actual field but only allow access to public fields.
     *
     * @param string $name Name of the requested field.
     * @throws ModelException If the field is internal.
     * @return Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name)
    {
        if (true === in_array($name, $this->_internalFields, true)) {
            throw new ModelException('Access to internal field not allowed: ' . $name);
        }
        return $this->_getField($name);
    }

    /**
     * Return a reference to an actual field.
     *
     * @param string $name Name of the requested field.
     * @return Field The requested field instance. If no such instance can be found, null is returned.
     */
    protected function _getField($name)
    {
        if (isset($this->_fields[$name])) {
            return $this->_fields[$name];
        } else {
            return null;
        }
    }

    /**
     * Checks if a given field exists as internal or external field.
     *
     * @param string $name Name of the requested field.
     * @return Field The requested field instance. If no such instance can be found, null is returned.
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
     * @return Field The requested field instance. If no such instance can be found, null is returned.
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
     * @see    \Opus\Model\Abstract::_internalFields
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

            if (! $field->hasMultipleValues()) {
                $fieldvalue = [$fieldvalue];
            }

            $fieldvalues = [];

            foreach ($fieldvalue as $value) {
                if ($value instanceof AbstractModel) {
                    $fieldvalues[] = $value->toArray();
                } elseif ($value instanceof \Zend_Date) {
                    $fieldvalues[] = $value->toArray();
                } else {
                    $fieldvalues[] = $value;
                }
            }

            if (! $field->hasMultipleValues()) {
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
     * @throws ModelException
     */
    public function updateFromArray($data)
    {
        if ($this instanceof AbstractLinkModel) {
            // Link-model classes proxy functions to a model class
            $model = $this->getModel();
            if (is_null($model)) {
                // if model object not present create one
                $modelClass = $this->getModelClass();
                $model = new $modelClass();
                $this->setModel($model);
            } else {
                $model->clearFields();
            }
        }

        $this->clearFields();

        foreach ($data as $fieldName => $values) {
            $field = $this->getField($fieldName);
            if (! is_null($field)) {
                $fieldModelClass = $field->getValueModelClass();
                $linkModelClass = $field->getLinkModelClass();

                if (is_null($fieldModelClass)) {
                    $field->setValue($values);
                } else {
                    if ($field->getMultiplicity() == '*') {
                        $models = [];
                        foreach ($values as $modelValues) {
                            $model = new $fieldModelClass();

                            if (! is_null($linkModelClass)) {
                                $linkModel = new $linkModelClass();
                                $linkModel->setModel($model);
                                $model = $linkModel;
                            }

                            $model->updateFromArray($modelValues);
                            $models[] = $model;
                        }

                        $field->setValue($models);
                    } else {
                        $model = new $fieldModelClass();

                        if (! is_null($linkModelClass)) {
                            $linkModel = new $linkModelClass();
                            $linkModel->setModel($model);
                            $model = $linkModel;
                        }

                        $model->updateFromArray($values);

                        $field->setvalue($model);
                    }
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
     * @param Strategy $strategy Version of Xml to process
     * @return \DomDocument A Dom representation of the model.
     */
    public function toXml(array $excludeFields = null, $strategy = null)
    {
        if (is_null($excludeFields) === true) {
            $excludeFields = [];
        }
        if (is_null($strategy) === true) {
            $strategy = new Version1();
        }
        $xml = new Xml();
        $xml->setModel($this)
                ->exclude($excludeFields)
                ->excludeEmptyFields()
                ->setStrategy($strategy);
        return $xml->getDomDocument();
    }

    /**
     * Instantiates an Opus\Model from xml as delivered by the toXml() method.
     *
     * @param  \DomDocument|string  $xml                The xml representing the model.
     * @param  Xml      $customDeserializer (Optional) Specify a custom deserializer object.
     * @return AbstractModel The Opus\Model derived from xml.
     */
    public static function fromXml($xml, Xml $customDeserializer = null)
    {
        if (is_null($customDeserializer)) {
            $customDeserializer = new Xml();
        }

        if ($xml instanceof \DomDocument) {
            $customDeserializer->setDomDocument($xml);
        } elseif (is_string($xml)) {
            $customDeserializer->setXml($xml);
        } else {
            throw new ModelException('Either DomDocument or xml string must be passed.');
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
                    if ($submodel instanceof AbstractModel) {
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
     * Opus\Model\Filter decorator for the given update model.
     *
     * @return void
     *
     * TODO does not work recursive - values of submodels don't get transferred
     */
    public function updateFrom(AbstractModel $model)
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
     * Clears all values from fields.
     */
    public function clearFields()
    {
        foreach ($this->_fields as $fieldName => $field) {
            $field->setValue(null);
        }
    }

    /**
     * Part of PropertySupportInterface.
     * @return int|null ID of model
     * @throws SecurityException
     * @throws ModelException
     */
    public function getId()
    {
        return $this->__call('getId', []);
    }

    /**
     * Set a property for a model.
     * @param string $key Name of property
     * @param string $value Value of property
     * @throws UnknownModelTypeException
     * @throws UnknownPropertyKeyException
     */
    public function setProperty($key, $value)
    {
        $properties = self::getPropertiesService();

        $properties->setProperty($this, $key, $value);
    }

    /**
     * Returns value of a property stored for a model.
     * @param string $key Name of property
     * @return string|null
     * @throws PropertiesException
     * @throws UnknownModelTypeException
     * @throws UnknownPropertyKeyException
     */
    public function getProperty($key)
    {
        $properties = self::getPropertiesService();

        return $properties->getProperty($this, $key);
    }

    /**
     * Returns identifier for model type.
     *
     * This needs to be overwritten by class that wants to support model properties.
     *
     * @return string|null
     *
     * TODO use protected variable for defining type in subclasses?
     */
    public function getModelType()
    {
        $className = get_class($this);
        throw new UnknownModelTypeException("Properties not supported for $className");
    }

    /**
     * Returns access object for model properties.
     *
     * @return mixed
     *
     * TODO should probably handled in separate class (revisit with ZF3)
     */
    protected static function getPropertiesService()
    {
        if (self::$propertiesService === null) {
            $service = new Properties();
            $service->setAutoRegisterTypeEnabled(true);
            $service->setAutoRegisterKeyEnabled(true);
            self::$propertiesService = $service;
        }

        return self::$propertiesService;
    }
}
