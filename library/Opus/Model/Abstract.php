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
 * Abstract class for all domain models in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 */

abstract class Opus_Model_Abstract implements Opus_Model_Interface
{
    /**
     * Holds the primary database table row. The concrete class is responsible
     * for any additional table rows it might need.
     *
     * @var Zend_Db_Table_Row
     */
    protected $_primaryTableRow;


    /**
     * Holds the name of the models table gateway class.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = null;

    /**
     * Holds all fields of the domain model.
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * Whether db transaction should be used in store()
     *
     * @var boolean  Defaults to true.
     */
    protected $_transactional = true;

    /**
     * Array of validator prefixes used to instanciate validator classes for fields.
     *
     * @var array
     */
    protected $_validatorPrefix = array('Opus_Validate');

    /**
     * Array of filter prefixes used to instanciate filter classes for fields.
     *
     * @var array
     */
    protected $_filterPrefix = array('Opus_Filter');

    /**
     * Holds the name of those fields of the domain model that do not map to the
     * primary table row. Concrete classes that use external fields must supply
     * _fetch{fieldname} and _store{fieldname} functions that handle these fields.
     *
     * @var array
     */
    protected $_externalFields = array();

    /**
     * Fields to be not reported by describe().
     *
     * @var array
     */
    protected $_hiddenFields = array();


    /**
     * Names of the fields that are in suspended fetch state.
     *
     * @var array
     */
    protected $_pending = array();


    /**
     * Constructor. Pass an id to fetch from database.
     *
     * @param integer       $id                (Optional) Id of existing database row.
     * @param Zend_Db_Table $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @throws Opus_Model_Exception            Thrown if passed id is invalid.
     */
    public function __construct($id = null, Opus_Db_TableGateway $tableGatewayModel = null) {
        // Ensure that a default table gateway class is set
        if (is_null($this->getTableGatewayClass()) === true) {
            throw new Opus_Model_Exception('No table gateway model passed or specified by $_tableGatewayClass for class: ' . get_class($this));
        }

        if ($tableGatewayModel === null) {
            // Try to query table gateway from internal attribute
            // Create an instance
            $classname = $this->getTableGatewayClass();
            $tableGatewayModel = Opus_Db_TableGateway::getInstance($classname);
        }

        if ($id === null) {
            $this->_primaryTableRow = $tableGatewayModel->createRow();
        } else {
            $this->_primaryTableRow = call_user_func_array(array(&$tableGatewayModel, 'find'),$id)->getRow(0);
            if ($this->_primaryTableRow === null) {
                throw new Opus_Model_Exception('No ' .
                get_class($tableGatewayModel) . " with id $id in database.");
            }
        }
        $this->_init();
        $this->_addValidators();
        $this->_addFilters();
        $this->_fetchValues();
    }

    /**
     * Overwrite to initialize fields.
     *
     * @return void
     */
    protected function _init() {
    }

    /**
     * Fetch attribute values from the table row and set up all fields. If fields containing
     * dependent models or link models those got fetched too.
     *
     * @return void
     */
    protected function _fetchValues() {
        foreach ($this->_fields as $fieldname => $field) {
            // Check if the fetch mechanism for the field is overwritten in model.
            $callname = '_fetch' . $fieldname;
            if (method_exists($this, $callname) === true) {
                $field->setValue($this->$callname());
            } else {

                // Field is declared as external and requires special handling
                if (in_array($fieldname, array_keys($this->_externalFields)) === true) {

                    // Determine the fields fetching mode
                    if (array_key_exists('fetch', $this->_externalFields[$fieldname]) === true) {
                        $fetchmode = $this->_externalFields[$fieldname];

                        // Remember the field to be fetched later.
                        $this->_pending[] = $fieldname;
                    } else {
                        $fetchmode = 'eager';
                    }

                    // Immediately load external field if fetching mode is set to 'eager'
                    if ($fetchmode === 'eager') {
                        // Load the model instance from the database and
                        // take the resulting object as value for the field
                        $this->_loadExternal($fieldname);
                    }

                } else {
                    // Field is not external an gets handled by simply reading
                    // its value from the table row
                    $colname = strtolower(preg_replace('/(?!^)[[:upper:]]/','_\0', $fieldname));
                    $field->setValue($this->_primaryTableRow->$colname);
                }

                // Clear the modified flag for the just loaded field
                $field->clearModified();
            }
        }
    }

    /**
     * Add validators to the fields. Opus_Validate_{fieldname} classes are
     * expected to exist. The base classname prefixes are defined in $_validatorPrefix.
     *
     * @return void
     */
    protected function _addValidators() {
        foreach ($this->_fields as $fieldname => $field) {
            foreach ($this->_validatorPrefix as $prefix) {
                $classname = $prefix . '_' . $fieldname;
                // suppress warnings about not existing classes
                if (@class_exists($classname) === true) {
                    $field->setValidator(new $classname);
                    break;
                }
            }
        }
    }

    /**
     * Add filters to the fields. Opus_Filter_{fieldname} classes are
     * expected to exist. The base classname prefixes are defined in $_filterPrefix.
     *
     * @return void
     */
    protected function _addFilters() {
        foreach ($this->_fields as $fieldname => $field) {
            foreach ($this->_filterPrefix as $prefix) {
                $classname = $prefix . '_' . $fieldname;
                // suppress warnings about not existing classes
                if (@class_exists($classname) === true) {

                    $filter = $field->getFilter();
                    if (is_null($filter) === true) {
                        $filter = new Zend_Filter();
                        $field->setFilter($filter);
                    }
                    $filter->addFilter(new $classname);
                    break;
                }
            }
        }
    }


    /**
     * Persist all the models information to its database locations.
     *
     * @see    Opus_Model_Interface::store()
     * @throws Opus_Model_Exception Thrown if the store operation could not be performed.
     * @return mixed $id    Primary key of the models primary table row.
     */
    public function store() {
        if ($this->_transactional === true) {
            $dbadapter = $this->_primaryTableRow->getTable()->getAdapter();
            $dbadapter->beginTransaction();
        }
        try {
            // Store basic simple fields to complete the table row
            foreach ($this->_fields as $fieldname => $field) {
                // Skip non-modified field.
                if ($field->isModified() === false) {
                    continue;
                }

                if (in_array($fieldname, array_keys($this->_externalFields)) === false) {
                    // Check if the store mechanism for the field is overwritten in model.
                    $callname = '_store' . $fieldname;
                    if (method_exists($this, $callname) === true) {
                        $this->$callname($this->_fields[$fieldname]->getValue());
                    } else {
                        $colname = strtolower(preg_replace('/(?!^)[[:upper:]]/','_\0', $fieldname));
                        $this->_primaryTableRow->{$colname} = $this->_fields[$fieldname]->getValue();
                    }
                }

                // Clear modification status of successfully stored field.
                $field->clearModified();
            }

            // Save the row.
            // This returnes the id needed to store external fields.
            $id = $this->_primaryTableRow->save();

            // Store external fields.
            foreach (array_keys($this->_externalFields) as $fieldname) {
                if (in_array($fieldname, array_keys($this->_fields)) === true) {
                    // Check if the store mechanism for the field is overwritten in model.
                    $callname = '_store' . $fieldname;
                    if (method_exists($this, $callname) === true) {
                        $this->$callname($this->_fields[$fieldname]->getValue());
                    } else {
                        if (array_key_exists('options', $this->_externalFields[$fieldname]) === true) {
                            $options = $this->_externalFields[$fieldname]['options'];
                        } else {
                            $options = null;
                        }
                        $this->_storeExternal($this->_fields[$fieldname]->getValue(), $options);
                    }
                }
                // Clear modification status of successfully stored field.
                $field->clearModified();
            }
            if ($this->_transactional === true) {
                $dbadapter->commit();
            }
        } catch (Exception $e) {
            if ($this->_transactional === true) {
                $dbadapter->rollback();
            }
            $msg = $e->getMessage() . 'Model: ' . get_class($this);
            throw new Opus_Model_Exception($msg);
        }
        return $id;
    }

    /**
     * Save the values of external fields.
     *
     * @param array|Opus_Model_DependentAbstract $values One or mor dependent opus models.
     * @param array                              $conditions (Optional) fixed conditions for certain attributes.
     * @throws Opus_Model_Exception Thrown when trying to save non Opus_Model_Dependent objects.
     * @return void
     */
    protected function _storeExternal($values, array $conditions = null) {
        if (is_array($values) === true) {
            foreach ($values as $value) {
                $this->_storeExternal($value, $conditions);
            }
        } else if (is_null($values) === false) {
            if ($values instanceof Opus_Model_DependentAbstract === false) {
                throw new Opus_Model_Exception('External fields must be Opus_Model_Dependent.');
            }
            if (is_null($conditions) === false) {
                foreach ($conditions as $column => $value) {
                    $values->_primaryTableRow->$column = $value;
                }
            }
            $values->setParentId($this->getId());
            $values->store();
        }
    }

    /**
     * Load the value of an external field. Sets an model instance or an array of
     * model instances depending on whether the field has multiple linked models or not.
     *
     * @param  string $fieldname Name of the external field.
     * @throws Opus_Model_Exception If no _fetch-method is defined for an external field.
     * @return void
     */
    protected function _loadExternal($fieldname) {

        // Get declared options if any
        if (array_key_exists('options', $this->_externalFields[$fieldname]) === true) {
            $options = $this->_externalFields[$fieldname]['options'];
        } else {
            $options = null;
        }

        // Determine the class of the field values model
        if (array_key_exists('through', $this->_externalFields[$fieldname])) {
            // If handling a link model, fetch modelclass from 'through' option.
            $modelclass = $this->_externalFields[$fieldname]['through'];
        } else {
            // Otherwise just use the 'model' option.
            $modelclass = $this->_externalFields[$fieldname]['model'];
        }

        // Make sure that a field's value model is inherited from Opus_Model_Abstract
        if (is_subclass_of($modelclass, 'Opus_Model_Abstract') === false) {
            throw new Opus_Model_Exception('Value of ' . $fieldname . ' does not extend Opus_Model_Abstract.
                                Define _fetch' . $fieldname . ' method in model class.');
        }

        // Prepare field value
        $result = array();

        // Do nothing if the current model has not been persisted
        // (if no identifier given)
        if ($this->getId() === null) {
            $result = null;
            return;
        }

        // Get the table gateway class
        // Workaround for missing late static binding.
        // Should look like this one day (from PHP 5.3.0 on) static::$_tableGatewayClass
        eval('$tablename = ' . $modelclass . '::$_tableGatewayClass;');
        $table = Opus_Db_TableGateway::getInstance($tablename);


        // Get name of id column in target table
        $tableInfo = $table->info();
        $primaryKey = $tableInfo['primary'];
        $select = $table->select()->from($table, $primaryKey);
        if (is_null($options) === false) {
            foreach ($options as $column => $value) {
                $select = $select->where("$column = ?", $value);
            }
        }

        // Get Ids of dependent rows
        $ids = $this->_primaryTableRow->findDependentRowset(get_class($table), null, $select)->toArray();

        // Create new model for each id
        foreach ($ids as $id) {
            $result[] = new $modelclass(array_values($id));
        }

        // Form return value
        if (count($ids) === 1) {
            // Return a single object if threre is only one model in the result
            $result = $result[0];
        } else if (count($ids) === 0) {
            // Return explicitly null if no results have been found.
            $result = null;
        }

        // Set the field value
        $this->_fields[$fieldname]->setValue($result);
    }

    /**
     * Magic method to access the models fields via virtual set/get methods.
     *
     * @param string $name      Name of the method beeing called.
     * @param array  $arguments Arguments for function call.
     * @throws Opus_Model_Exception If an unknown field or method is requested.
     * @throws InvalidArgumentException When adding a link to a field without an argument.
     * @return mixed Might return a value if a getter method is called.
     */
    public function __call($name, array $arguments) {
        $accessor = substr($name, 0, 3);
        $fieldname = substr($name, 3);

        $argumentModelGiven = false;
        if (empty($arguments) === false) {
            if (is_null($arguments[0]) === false) {
                $argumentModelGiven = true;
            }
        };

        if (array_key_exists($fieldname, $this->_fields) === false) {
            throw new Opus_Model_Exception('Unknown field: ' . $fieldname);
        }

        $fieldIsExternal = array_key_exists($fieldname, $this->_externalFields);
        if ($fieldIsExternal === true) {
            $fieldHasThroughOption = array_key_exists('through', $this->_externalFields[$fieldname]);
        }
        $field = $this->getField($fieldname);

        switch ($accessor) {
            case 'get':
                if (empty($arguments) === false) {
                    return $field->getValue($arguments[0]);
                } else {
                    return $field->getValue();
                }
                break;

            case 'set':
                if (empty($arguments) === true) {
                    throw new Opus_Model_Exception('Argument required for setter function!');
                }
                if (($fieldIsExternal === true)
                and ($fieldHasThroughOption === true)
                and ($argumentModelGiven === true)) {

                    $linkmodelclass = $this->_externalFields[$fieldname]['through'];
                    $linkmodel = new $linkmodelclass;

                    if (($arguments[0] instanceof Opus_Model_Dependent_Link_Abstract) === true) {
                        $linkmodel->setModel($arguments[0]->_model);
                    } else {
                        $linkmodel->setModel($arguments[0]);
                    }
                    $model = $linkmodel;

                } else {
                    $model = $arguments[0];
                }

                $field->setValue($model);
                return $field->getValue();
                break;

            case 'add':
                if (is_null($field->getValueModelClass()) === true) {
                    throw new Opus_Model_Exception('Add accessor currently only available for fields holding models.');
                }

                // get Modelclass if model is linked
                if ($fieldHasThroughOption === true) {

                    $linkmodelclass = $this->_externalFields[$fieldname]['through'];

                    // Check if $linkmodelclass is a known class name
                    if (class_exists($linkmodelclass) === false) {
                        throw new Opus_Model_Exception("Link model class '$linkmodelclass' does not exist.");
                    }
                    $linkmodel = new $linkmodelclass;

                    if ((count($arguments) === 1)) {
                        if (($arguments[0] instanceof Opus_Model_Dependent_Link_Abstract) === true) {
                            $linkmodel->setModel($arguments[0]->_model);
                        } else {
                            $linkmodel->setModel($arguments[0]);
                        }
                    } else {
                        throw new InvalidArgumentException('Argument required when adding to a link field.');
                    }
                    $model = $linkmodel;

                } else {
                    if ((count($arguments) === 1)) {
                        $model = $arguments[0];
                    } else {
                        $modelclass = $field->getValueModelClass();
                        $model = new $modelclass;
                    }
                }

                $field->addValue($model);
                return $model;
                break;

            default:
                throw new Opus_Model_Exception('Unknown accessor function: ' . $accessor);
                break;
        }

    }

    /**
     * Add an field to the model. If a field with the same name has already been added,
     * it will be replaced by the given field.
     *
     * @param Opus_Model_Field $field Field instance that gets appended to the models field collection.
     * @return Opus_Model_Abstract Provide fluent interface.
     */
    public function addField(Opus_Model_Field $field) {
        $fieldname = $field->getName();
        $this->_fields[$fieldname] = $field;

        // set Modelclass if a model exists
        if (array_key_exists($fieldname, $this->_externalFields) === true) {
            if (array_key_exists('model', $this->_externalFields[$fieldname]) === true) {
                $model = $this->_externalFields[$fieldname]['model'];
                $field->setValueModelClass($model);
            }
        }

        return $this;
    }

    /**
     * Return a reference to an actual field. If an external field yet has to be fetched
     * _loadExternal is called.
     *
     * @param string $name Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name) {
        if (array_key_exists($name, $this->_fields) === true) {

            // Check if the field is in suspended fetch state
            if (in_array($name, $this->_pending) === true) {
                // Ensure that _loadExternal is called only on external fields
                if (array_key_exists($name, $this->_externalFields)) {
                    $this->_loadExternal($name);
                    // Workaround for: unset($this->_pending[$name]);
                    $result = array();
                    foreach ($this->_pending as $fieldname) {
                        if ($fieldname !== $name) {
                            $result[] = $fieldname;
                        }
                    }
                    $this->_pending = $result;
                }
            }
            return $this->_fields[$name];

        } else {
            return null;
        }
    }

    /**
     * Remove the model instance from the database.
     *
     * @see    Opus_Model_Interface::delete()
     * @throws Opus_Model_Exception If a delete operation could not be performed on this model.
     * @return void
     */
    public function delete() {
        $this->_primaryTableRow->delete();
        $this->_primaryTableRow = null;
    }

    /**
     * Get the models primary key.
     *
     * @return mixed
     */
    public function getId() {
        $tableInfo = $this->_primaryTableRow->getTable()->info();
        $result = array();
        foreach ($tableInfo['primary'] as $primary_key) {
            $result[] = $this->_primaryTableRow->$primary_key;
        }
        if (count($result) > 1) {
            return $result;
        } else if (count($result) === 1) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * Get a list of all fields attached to the model. Filters all fieldnames
     * that are defined to be hidden in $_hiddenFields.
     *
     * @see    Opus_Model_Abstract::_hiddenFields
     * @return array    List of fields
     */
    public function describe() {
        $result = array();
        foreach (array_keys($this->_fields) as $fieldname) {
            if (in_array($fieldname, $this->_hiddenFields) === false) {
                $result[] = $fieldname;
            }
        }
        return $result;
    }

    /**
     * By default, the textual representation of a modeled entity is
     * its class name and identifier.
     *
     * @return string Model class name and identifier (e.g. Opus_Model_Document#4711).
     */
    public function getDisplayName() {
        return get_class($this) . '#' . $this->getId();
    }


    /**
     * Reconnect primary table row to database after unserializing.
     *
     * @return void
     */
    public function __wakeup() {
        $tableclass = $this->_primaryTableRow->getTableClass();
        $table = Opus_Db_TableGateway::getInstance($tableclass);
        $this->_primaryTableRow->setTable($table);
    }

    /**
     * Set whether storing this model opens a database transaction or not.
     *
     * @param  boolean $transactional (Optional) Whether to use a transaction or not.
     * @return void
     */
    public function setTransactional($transactional = true) {
        $this->_transactional = $transactional;
    }

    /**
     * Return this models table gateway class name.
     *
     * @return string Table gateway class name.
     */
    public function getTableGatewayClass() {
        // Use get_class as a workaround for late static binding.
        $modelClass = get_class($this);
        eval('$tableGatewayClass = ' . $modelClass . '::$_tableGatewayClass;');
        return $tableGatewayClass;
    }

    /**
     * Get a nested associative array representation of the model.
     *
     * @return array A (nested) array representation of the model.
     */
    public function toArray() {
        $result = array();
        foreach ($this->_fields as $fieldname => $field) {

            // Call to getField() to ensure fetching of pending fields.
            if (in_array($fieldname, $this->_pending) === true) {
                $field = $this->getField($fieldname);
            }

            if ($field->hasMultipleValues()) {
                $fieldvalues = array();
                foreach($field->getValue() as $value) {
                    if ($value instanceof Opus_Model_Abstract) {
                        $fieldvalues[] = $value->toArray();
                    } else {
                        $fieldvalues[] = $value;
                    }
                }
                $result[$fieldname] = $fieldvalues;
            } else {
                if ($field->getValue() instanceof Opus_Model_Abstract) {
                    $result[$fieldname] = $field->getValue()->toArray();
                } else {
                    $result[$fieldname] = $field->getValue();
                }
            }
        }
        return $result;
    }

    /**
     * Returns an Xml-string representation of the model.
     *
     * @return string A plain Xml-string representation of the model.
     */
    public function toXml() {
        $result = '';
        foreach ($this->_fields as $fieldname => $field) {

            // Call to getField() to ensure fetching of pending fields.
            if (in_array($fieldname, $this->_pending) === true) {
                $field = $this->getField($fieldname);
            }

            if ($field->hasMultipleValues()) {
                $fieldvalues = '';
                foreach($field->getValue() as $value) {
                    if ($value instanceof Opus_Model_Abstract) {
                        $result .= '<' . $fieldname . '>' . $value->toXml() . '</' . $fieldname . '>';
                    } else {
                        $result .= '<' . $fieldname . '>' . $value . '</' . $fieldname . '>';
                    }
                }
            } else {
                if ($field->getValue() instanceof Opus_Model_Abstract) {
                    $result .= '<' . $fieldname . '>' . $field->getValue()->toXml() . '</' . $fieldname . '>';
                } else {
                    $result .= '<' . $fieldname . '>' . $field->getValue() . '</' . $fieldname . '>';
                }
            }
        }
        return $result;
    }

    /**
     * Retrieve all instances of a particular Opus_Model that are known
     * to the database.
     *
     * @param string $modelClassName        Name of the model class.
     * @param string $tableGatewayClassName Name of the table gateway class
     *                                      to determine the table entities shall
     *                                      be fetched from.
     * @return array List of all known model entities.
     * @throws InvalidArgumentException When not passing class names.
     */
    public static function getAllFrom($modelClassName = null, $tableGatewayClassName = null) {

        // As we are in static context, we have no chance to retrieve
        // those class names.
        if ((is_null($modelClassName) === true) or (is_null($tableGatewayClassName))) {
            throw new InvalidArgumentException('Both model class and table gateway class must be given.');
        }

        // As this is calling from static context we cannot
        // use the instance variable $_tableGateway here.
        $table = Opus_Db_TableGateway::getInstance($tableGatewayClassName);
        $tableInfo = $table->info();
        // FIXME: Assuming that there is no compound primary key.
        $primaryKeyName = $tableInfo['primary'][1];

        // Fetch all present primary keys.
        $select = $table->select()->from($table)->columns($primaryKeyName);
        $rows = $table->fetchAll($select)->toArray();

        // Turn the list of primary keys in a list of objects.
        $result = array();

        foreach ($rows as $row) {
            $result[] = new $modelClassName($row[$primaryKeyName]);
        }
        return $result;
    }


}
