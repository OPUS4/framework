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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Abstract class for all domain models in the Opus framework that are connected
 * to a database table.
 *
 * @category    Framework
 * @package     Opus_Model
 */

abstract class Opus_Model_AbstractDb extends Opus_Model_Abstract
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
     * Names of the fields that are in suspended fetch state.
     *
     * @var array
     */
    protected $_pending = array();

    /**
     * Holds persistance status of the model, including all dependant models.
     *
     * @var int  Defaults to true.
     */
    protected $_isNewRecord = true;

    /**
     * Construct a new model instance and connect it a database table's row.
     * Pass an id to immediately fetch model data from the database. If not id is given
     * a new persistent intance gets created wich got its id set as soon as it is stored
     * via a call to _store().
     *
     * @param integer|Zend_Db_Table_Row $id                (Optional) (Id of) Existing database row.
     * @param Zend_Db_Table_Abstract    $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @throws Opus_Model_Exception     Thrown if passed id is invalid.
     */
    public function __construct($id = null, Zend_Db_Table_Abstract $tableGatewayModel = null) {
        // Ensure that a default table gateway class is set
        if ((is_null($this->getTableGatewayClass()) === true) and (is_null($tableGatewayModel) === true)) {
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
        } else if ($id instanceof Zend_Db_Table_Row) {
            if ($id->getTableClass() !== $this->getTableGatewayClass()) {
                throw new Opus_Model_Exception('Mistyped table row passed. Expected row from ' .
                $this->getTableGatewayClass() . ', got row from ' . $id->getTableClass() . '.');
            }
            $this->_primaryTableRow = $id;
            $this->_isNewRecord = false;
        } else {
            $this->_primaryTableRow = call_user_func_array(array(&$tableGatewayModel, 'find'),$id)->getRow(0);
            if ($this->_primaryTableRow === null) {
                throw new Opus_Model_Exception('No ' . get_class($tableGatewayModel) . " with id $id in database.");
            }
            $this->_isNewRecord = false;
        }
        parent::__construct();
        $this->_fetchValues();
    }

    /**
     * Fetch attribute values from the table row and set up all fields. If fields containing
     * dependent models or link models those got fetched too.
     *
     * @return void
     */
    protected function _fetchValues() {
        foreach ($this->_fields as $fieldname => $field) {

            // Field is declared as external and requires special handling
            if (array_key_exists($fieldname, $this->_externalFields) === true) {
                // Determine the fields fetching mode
                if (array_key_exists('fetch', $this->_externalFields[$fieldname]) === true) {
                    $fetchmode = $this->_externalFields[$fieldname]['fetch'];
                } else {
                    $fetchmode = 'lazy';
                }
                if ($fetchmode === 'lazy') {
                    // Remember the field to be fetched later.
                    $this->_pending[] = $fieldname;
                    // Go to next field
                    continue;
                } else {
                    // Immediately load external field if fetching mode is set to 'eager'
                    // Load the model instance from the database and
                    // take the resulting object as value for the field
                    $this->_loadExternal($fieldname);
                }
            } else {
                // Field is not external an gets handled by simply reading
                // its value from the table row
                // Check if the fetch mechanism for the field is overwritten in model.
                $callname = '_fetch' . $fieldname;
                if (method_exists($this, $callname) === true) {
                    $field->setValue($this->$callname());
                } else {
                    $colname = strtolower(preg_replace('/(?!^)[[:upper:]]/','_\0', $fieldname));
                    $fieldval = $this->_primaryTableRow->$colname;
                    // explicitly set null if the field represents a model
                    if (null !== $field->getValueModelClass()) {
                        if (true === empty($fieldval)) {
                            $fieldval = null;
                        }
                    }
                    if (true === $field->hasMultipleValues()) {
                        $fieldval = json_decode($fieldval);
                    }
                    $field->setValue($fieldval);
                }
            }
            // Clear the modified flag for the just loaded field
            $field->clearModified();
        }
    }

    /**
     * Perform any actions needed to provide storing.
     *
     * Currently modification checking and validation.
     *
     * @return mixed Anything else then null will cancel the storage process.
     */
    protected function _preStore() {
        // do not perfom storing actions when model is not modified and not new
        if ((false === $this->isNewRecord()) and (false === $this->isModified())) {
            return $this->getId();
        }

        // refuse to store if data is not valid
        if (false === $this->isValid()) {
            $msg = 'Attempt to store model with invalid data.';
            foreach ($this->getValidationErrors() as $fieldname=>$err) {
                if (false === empty($err)) {
                    $msg = $msg . "\n" . "$fieldname\t" . implode("\n", $err);
                }
            }
            throw new Opus_Model_Exception($msg);
        }

        return null;
    }

    /**
     * Perform any actions needed after storing.
     *
     * Sets _isNewRecord to false.
     *
     * @return void
     */
    protected function _postStore() {
        $this->_isNewRecord = false;
    }

    /**
     * Perform any actions needed after storing internal fields.
     *
     * @return void
     */
    protected function _postStoreInternalFields() {
    }


    /**
     * Persist all the models information to its database locations.
     *
     * Storage logic is surrounded by _preStore() and _postStore() calls
     * to enable custom implementations.
     *
     * @see    Opus_Model_Interface::store()
     * @throws Opus_Model_Exception     Thrown if the store operation could not be performed.
     * @return mixed $id    Primary key of the models primary table row.
     */
    public function store() {
        $pre = $this->_preStore();
        if (null !== $pre) {
            return $pre;
        }

        // Start transaction
        $dbadapter = $this->_primaryTableRow->getTable()->getAdapter();
        $dbadapter->beginTransaction();

        // store internal and external fields
        try {
            $id = $this->_storeInternalFields();
            $this->_postStoreInternalFields();
            $this->_storeExternalFields();
        } catch (Exception $e) {
            $dbadapter->rollBack();
            throw $e;
        }

        // commit transaction
        $dbadapter->commit();

        $this->_postStore();
        return $id;
    }

    /**
     * Persists the intal Fields to the primary table row.
     *
     * @return string The id of the primary table row is returned.
     */
    protected function _storeInternalFields() {
        try {
            // Store basic simple fields to complete the table row
            foreach ($this->_fields as $fieldname => $field) {
                if (in_array($fieldname, array_keys($this->_externalFields)) === false) {
                    
                    // analyze field values
                    $fieldValues = $this->_fields[$fieldname]->getValue();
                    if (is_array($fieldValues) === true) {
                        // internal fields can never be a array, encode as json
                        $fieldValue = json_encode($fieldValues);
                    } else {
                        $fieldValue = $fieldValues;
                    }
                    
                    // Check if the store mechanism for the field is overwritten in model.
                    $callname = '_store' . $fieldname;
                    if (method_exists($this, $callname) === true) {
                        // Call custom store method
                        $this->$callname($fieldValue);
                    } else if ($field->isModified() === false) {
                        // Skip non-modified field.
                        continue;
                    } else {
                        $colname = strtolower(preg_replace('/(?!^)[[:upper:]]/','_\0', $fieldname));
                        $this->_primaryTableRow->{$colname} = $fieldValue;
                    }
                    // Clear modification status of successfully stored field.
                    $field->clearModified();
                }
            }
            // Save the row.
            // This returnes the id needed to store external fields.
            $id = $this->_primaryTableRow->save();
        } catch (Exception $e) {
            $msg = $e->getMessage() . ' Model: ' . get_class($this);
            // this works with php >= 5.3.0: throw new Opus_Model_Exception($msg, $e->getCode(), $e);
            // workaround:
            $msg .= "\nThrown in " . $e->getFile() . ':' . $e->getLine();
            throw new Opus_Model_Exception($msg);
        }
        return $id;
    }

    /**
     * Calls the methods to store the external Fields.
     *
     * @return void
     */
    protected function _storeExternalFields() {
        try {
            // Store external fields.
            foreach (array_keys($this->_externalFields) as $fieldname) {
                if (in_array($fieldname, array_keys($this->_fields)) === true) {
                    // Check if the store mechanism for the field is overwritten in model.
                    $callname = '_store' . $fieldname;
                    if (method_exists($this, $callname) === true) {
                        // Call custom store method
                        $this->$callname($this->_fields[$fieldname]->getValue());
                    } else {
                        if (array_key_exists('options', $this->_externalFields[$fieldname]) === true) {
                            $options = $this->_externalFields[$fieldname]['options'];
                        } else {
                            $options = null;
                        }
                        $this->_storeExternal($this->_fields[$fieldname]->getValue(), $options);
                    }
                    // trigger any pending delete operations
                    $this->_fields[$fieldname]->doPendingDeleteOperations();
                    // Clear modification status of successfully stored field.
                    $this->_fields[$fieldname]->clearModified();
                }
            }
        } catch (Exception $e) {
            $msg = $e->getMessage() . ' Model: ' . get_class($this) . ' Field: ' . $fieldname . '.';
            // this works with php >= 5.3.0: throw new Opus_Model_Exception($msg, $e->getCode(), $e);
            // workaround:
            $msg .= "\nThrown in " . $e->getFile() . ':' . $e->getLine();
            throw new Opus_Model_Exception($msg);
        }
    }

    /**
     * Save the values of external fields.
     *
     * @param array|Opus_Model_Dependent_Abstract $values One or mor dependent opus models.
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
            if ($values instanceof Opus_Model_Dependent_Abstract === false) {
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

        // Check if the fetch mechanism for the field is overwritten in model.
        $callname = '_fetch' . $fieldname;
        if (method_exists($this, $callname) === true) {
            $this->_fields[$fieldname]->setValue($this->$callname());
        } else {
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
            if (is_null($options) === false) {
                $select = $table->select();
                foreach ($options as $column => $value) {
                    $select = $select->where("$column = ?", $value);
                }
            } else {
                $select = null;
            }

            // Get dependent rows
            $rows = $this->_primaryTableRow->findDependentRowset(get_class($table), null, $select);

            // Create new model for each row
            foreach ($rows as $row) {
                $result[] = new $modelclass($row);
            }

            // Form return value
            if (count($rows) === 1) {
                // Return a single object if threre is only one model in the result
                $result = $result[0];
            } else if (count($rows) === 0) {
                // Return explicitly null if no results have been found.
                $result = null;
            }

            // Set the field value
            $this->_fields[$fieldname]->setValue($result);
        }
    }

    /**
     * Remove the model instance from the database.
     *
     * @throws Opus_Model_Exception If a delete operation could not be performed on this model.
     * @return void
     */
    public function delete() {
        // if no primary key is set the model has
        // not been stored yet, so delete gets skipped
        if (null === $this->getId()) {
            return;
        }

        // Start transaction
        $dbadapter = $this->_primaryTableRow->getTable()->getAdapter();
        $dbadapter->beginTransaction();
        try {
            $this->_primaryTableRow->delete();
            $this->_primaryTableRow = null;
            $dbadapter->commit();
        } catch (Exception $e) {
            $dbadapter->rollback();
            $msg = $e->getMessage() . ' Model: ' . get_class($this);
            throw new Opus_Model_Exception($msg);
        }
    }

    /**
     * Get the models primary key.
     *
     * @return mixed
     */
    public function getId() {
        if (null === $this->_primaryTableRow) {
            return null;
        }
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
     * By default, the textual representation of a modeled entity is
     * its class name and identifier.
     *
     * @return string Model class name and identifier (e.g. Opus_Document#4711).
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
     * Retrieve all instances of a particular Opus_Model that are known
     * to the database.
     *
     * @param string $modelClassName        Name of the model class.
     * @param string $tableGatewayClassName Name of the table gateway class
     *                                      to determine the table entities shall
     *                                      be fetched from.
     * @param array  $ids                   A list of ids to fetch.
     * @param string $orderBy               A column name to order by.
     *
     * @return array List of all known model entities.
     * @throws InvalidArgumentException When not passing class names.
     *
     * TODO: Include options array to parametrize query.
     */
    public static function getAllFrom($modelClassName = null, $tableGatewayClassName = null, array $ids = null, $orderBy = null) {

        // As we are in static context, we have no chance to retrieve
        // those class names.
        if ((is_null($modelClassName) === true) or (is_null($tableGatewayClassName) === true)) {
            throw new InvalidArgumentException('Both model class and table gateway class must be given.');
        }

        // As this is calling from static context we cannot
        // use the instance variable $_tableGateway here.
        $table = Opus_Db_TableGateway::getInstance($tableGatewayClassName);

        // Fetch all entries in one query and pass result table rows
        // directly to models.
        if (is_null($ids) === true) {
            $rows = $table->fetchAll(null, $orderBy);
        } else if (empty($ids) === true) {
            $rows = array();
        } else {
            $rowset = $table->find($ids);
            if (false === is_null($orderBy)) {
                // Sort manually, since find() does not support order by clause.
                foreach($rowset as $key => $row) {
                    $vals[$key] = $row->$orderBy;
                    $rows[] = $row;
                }
                array_multisort($vals, SORT_ASC, $rows);
            } else {
                $rows = $rowset;
            }
        }
        $result = array();
        foreach ($rows as $row) {
            $model = new $modelClassName($row);
            $result[] = $model;
        }
        return $result;
    }

    /**
     * Return a reference to an actual field. If an external field yet has to be fetched
     * _loadExternal is called.
     *
     * @param string $name           Name of the requested field.
     * @param bool   $ignore_pending (Optional) If true is given currently pending fields are ignored.
     *                               Default is false.
     * @param string $name           Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    protected function _getField($name, $ignorePending = false) {
        if (array_key_exists($name, $this->_fields) === true) {

            // Check if the field is in suspended fetch state
            if (in_array($name, $this->_pending) === true and $ignorePending === false) {
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
     * Returns whether model is a new record.
     *
     * @return boolean
     */
    public function isNewRecord() {
        return $this->_isNewRecord;
    }

}
