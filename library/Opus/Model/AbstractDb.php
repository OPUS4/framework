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

abstract class Opus_Model_AbstractDb
    extends Opus_Model_Abstract
    implements Opus_Model_ModificationTracking {

    /**
     * @TODO: Change name of this array to somewhat more general.
     * @TODO: Not enforce existence of custom _fetch and _store methods in Opus_Model_AbstractDb.
     *
     * In this array extra information for each field of the model can be
     * given, such like the classname of a referenced model object or specific options.
     *
     * It is an associative array referencing an declaration array for each field.
     *
     * 'MyField' => array(
     *          'model' => 'Opus_Title',
     *          'options' => array('type' => 'main'))
     *
     * @var array
     */
    protected $_externalFields = array();

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
     * @var boolean  Defaults to true.
     */
    protected $_isNewRecord = true;

    /**
     * Array mapping plugin class names to model plugins.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @var Array
     */
    protected $_plugins = array();

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

            $id_tupel = is_array($id) ? $id : array($id);
            $id_string = is_array($id) ? "(".implode(",", $id).")" : $id;

            // This is needed, because find takes as many parameters as
            // primary keys.  It *does* *not* accept arrays with all primary
            // key columns.
            $rowset = call_user_func_array(array(&$tableGatewayModel, 'find'), $id_tupel);
            if (false == ($rowset->count() > 0)) {
                throw new Opus_Model_NotFoundException('No ' . get_class($tableGatewayModel) . " with id $id_string in database.");
            }

            $this->_primaryTableRow = $rowset->getRow(0);
            $this->_isNewRecord = false;
        }
        parent::__construct();

        // initialize plugins
        $this->_loadPlugins();

        $this->_fetchValues();

        $this->_clearFieldsModifiedFlag();
    }

    /**
     * Clear the modified flag on all fields.
     *
     * @return void
     */
    protected function _clearFieldsModifiedFlag() {
        foreach ($this->_fields as $field) {
            $field->clearModified();
        }
    }

    /**
     * Tell whether there is a modified field.
     *
     * @return boolean
     */
    public function isModified() {
        foreach ($this->_fields as $field) {
            if (true === $field->isModified()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Add an field to the model. If a field with the same name has already been added,
     * it will be replaced by the given field.
     *
     * @param Opus_Model_Field $field Field instance that gets appended to the models field collection.
     * @return Opus_Model_Abstract Provide fluent interface.
     */
    public function addField(Opus_Model_Field $field) {
        $result = parent::addField($field);

        // set ValueModelClass if a through option is given
        $valueModelClass = $this->_getValueModelClassForField($field);
        if (null !== $valueModelClass) {
            $field->setValueModelClass($valueModelClass);
        }

        // set LinkModelClass if a through option is given
        $linkModelClass = $this->_getLinkModelClassForField($field);
        if (null !== $linkModelClass) {
            $field->setLinkModelClass($linkModelClass);
        }

        return $result;
    }

    /**
     * Check if a given field instance is a value model field and
     * return the value model class name as defined in the models
     * $_externalFields array.
     *
     * @param Opus_Model_Field $field Field instance to check.
     * @return mixed Class name if 'model' parameter is set for field, null otherwise
     */
    private function _getValueModelClassForField(Opus_Model_Field $field) {
        $fieldname = $field->getName();
        if (array_key_exists($fieldname, $this->_externalFields) === true) {
            if (array_key_exists('model', $this->_externalFields[$fieldname]) === true) {
                return $this->_externalFields[$fieldname]['model'];
            }
        }
    }

    /**
     * Check if a given field instance is a link model field and
     * return the link model class name as defined in the models
     * $_externalFields array.
     *
     * @param Opus_Model_Field $field Field instance to check.
     * @return mixed Class name if 'through' parameter is set for field, null otherwise
     */
    private function _getLinkModelClassForField(Opus_Model_Field $field) {
        $fieldname = $field->getName();
        if (array_key_exists($fieldname, $this->_externalFields) === true) {
            if (array_key_exists('through', $this->_externalFields[$fieldname]) === true) {
                return $this->_externalFields[$fieldname]['through'];
            }
        }
    }

     /**
     * Instanciate and install plugins for this model.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @return void
     */
    protected function _loadPlugins() {
        foreach ($this->_plugins as $pluginname => $plugin) {
           if (true === is_string($plugin)) {
                $pluginname = $plugin;
                $plugin = null;
            }

            if (null === $plugin) {
                $plugin = new $pluginname;
            }

            $this->registerPlugin($plugin);
        }
    }

    /**
     * Register a pre- or post processing plugin.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @param Opus_Model_Plugin_Interface $plugin Plugin to register for this very model.
     * @return void
     */
    public function registerPlugin(Opus_Model_Plugin_Interface $plugin) {
        $this->_plugins[get_class($plugin)] = $plugin;
    }

    /**
     * Unregister a pre- or post processing plugin.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @param string|object $plugin Instance or class name to unregister plugin.
     * @throw Opus_Model_Exception Thrown if specified plugin does not exist.
     * @return void
     */
    public function unregisterPlugin($plugin) {
        $key = '';
        if (true === is_string($plugin)) {
            $key = $plugin;
        }
        if (true === is_object($plugin)) {
            $key = get_class($plugin);
        }
        if (false === array_key_exists($key, $this->_plugins)) {
            throw new Opus_Model_Exception('Cannot unregister specified plugin: ' . $key);
        }
        unset($this->_plugins[$key]);
    }

    /**
     * Fetch attribute values from the table row and set up all fields. If fields containing
     * dependent models or link models those got fetched too.
     *
     * @return void
     */
    protected function _fetchValues() {
        // preFetch plugin hook
        $this->_preFetch();

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

                    $field->setValue($fieldval);
                }
            }
            // Clear the modified flag for the just loaded field
            $field->clearModified();
        }
    }

    /**
     * Calls a specified plugin method in all available plugins.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @param string $methodname Name of plugin method to call
     * @param mixed  $parameter  Value that gets passed instead of the model instance.
     */
    private function _callPluginMethod($methodname, $parameter = null) {
        try {
            if (null === $parameter) {
                $param = $this;
            } else {
                $param = $parameter;
            }
            foreach ($this->_plugins as $name=>$plugin) {
                $plugin->$methodname($param);
            }
        } catch (Exception $ex) {
            throw new Opus_Model_Exception('Plugin ' . $name . ' failed in ' . $methodname
                    . ' with ' . $ex->getMessage());
        }
    }

    /**
     * Trigger preFetch plugins.
     *
     * @return void
     * @throw Opus_Model_Exception Throws whenever a plugin failes.
     */
    protected function _preFetch() {
        $this->_callPluginMethod('preFetch');
    }

    /**
     * Perform any actions needed to provide storing.
     *
     * Currently modification checking and validation.
     *
     * @return mixed Anything else then null will cancel the storage process.
     */
    protected function _preStore() {
        $this->_callPluginMethod('preStore');

        // do not perfom storing actions when model is not modified and not new
        if ((false === $this->isNewRecord()) and (false === $this->isModified())) {
            return $this->getId();
        }

        // refuse to store if data is not valid
        if (false === $this->isValid()) {
            $msg = 'Attempt to store model with invalid data.';
            foreach ($this->getValidationErrors() as $fieldname => $err) {
                if (false === empty($err)) {
                    $msg = $msg . "\n" . "$fieldname\t" . implode("\n", $err);
                }
            }
            // $this->$fieldname = 'null';
            // TODO: handle error (but without throwing it)
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
        $this->_callPluginMethod('postStore');
        $this->_isNewRecord = false;
    }

    /**
     * Perform any actions needed after storing internal fields.
     *
     * @return void
     */
    protected function _postStoreInternalFields() {
        $this->_callPluginMethod('postStoreInternal');
    }

    /**
     * Perform any actions needed after storing internal fields.
     *
     * @return void
     * @throw Opus_Model_Exception Throws whenever an error occurs
     */
    function _postStoreExternalFields() {
        $this->_callPluginMethod('postStoreExternal');
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
            $this->_postStoreExternalFields();
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
                
                // Skip external fields.
                if (in_array($fieldname, array_keys($this->_externalFields))) {
                    continue;
                }

                // map field values: Cannot process array-valued fields
                $fieldValue = $this->_fields[$fieldname]->getValue();

                // Check if the store mechanism for the field is overwritten in model.
                $callname = '_store' . $fieldname;
                if (method_exists($this, $callname) === true) {
                    // Call custom store method
                    $this->$callname($fieldValue);
                }
                else if ($field->isModified() === false) {
                    // Skip non-modified field.
                    continue;
                }
                else {
                    $colname = strtolower(preg_replace('/(?!^)[[:upper:]]/', '_\0', $fieldname));
                    $this->_primaryTableRow->{$colname} = $fieldValue;
                }
                // Clear modification status of successfully stored field.
                $field->clearModified();
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

                // Skip external fields, that have not been added to the model.
                if (in_array($fieldname, array_keys($this->_fields)) === false) {
                    continue;
                }

                // Check if the store mechanism for the field is overwritten in model.
                $callname = '_store' . $fieldname;
                if (method_exists($this, $callname) === true) {
                    $field = $this->_getField($fieldname, true);
                    if (true === $field->isModified()) {
                        // Call custom store method
                        $this->$callname($this->_fields[$fieldname]->getValue());
                    }
                }
                else {
                    $options = null;
                    if (array_key_exists('options', $this->_externalFields[$fieldname]) === true) {
                        $options = $this->_externalFields[$fieldname]['options'];
                    }
                    $this->_storeExternal($this->_fields[$fieldname]->getValue(), $options);
                }
                // trigger any pending delete operations
                $this->_fields[$fieldname]->doPendingDeleteOperations();
                // Clear modification status of successfully stored field.
                $this->_fields[$fieldname]->clearModified();
            }
        }
        catch (Zend_Db_Exception $zdbe) {
            // workaround: throw database adapter exceptions                                                                                                                                                       
            throw $zdbe;
        }
        catch (Exception $e) {
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
            $result = $this->$callname();
        } else {
            // Get declared options if any
            $options = null;
            if (array_key_exists('options', $this->_externalFields[$fieldname]) === true) {
                $options = $this->_externalFields[$fieldname]['options'];
            }

            // Get declared sort order if any
            $sort_order = null;
            if (array_key_exists('sort_order', $this->_externalFields[$fieldname]) === true) {
                $sort_order = $this->_externalFields[$fieldname]['sort_order'];
            }

            // Determine the class of the field values model
            $modelclass = null;
            if (array_key_exists('through', $this->_externalFields[$fieldname])) {
                // If handling a link model, fetch modelclass from 'through' option.
                $modelclass = $this->_externalFields[$fieldname]['through'];
            } 
            else if (array_key_exists('model', $this->_externalFields[$fieldname])) {
                // Otherwise just use the 'model' option.
                $modelclass = $this->_externalFields[$fieldname]['model'];
            }

            if (empty($modelclass)) {
                throw new Opus_Model_Exception('Definition of external field "' . $fieldname . '" must contain "model" or "through" key.');
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
                return;
            }

            // Get the table gateway class
            // Workaround for missing late static binding.
            // Should look like this one day (from PHP 5.3.0 on) static::$_tableGatewayClass
            eval('$tablename = ' . $modelclass . '::$_tableGatewayClass;');
            $table = Opus_Db_TableGateway::getInstance($tablename);

            // Get name of id column in target table
            $select = $table->select();
            if (is_array($options)) {
                foreach ($options as $column => $value) {
                    $select = $select->where("$column = ?", $value);
                }
            }

            // TODO: $sort_order does not work if no $options are given?
            if (is_array($sort_order)) {
                foreach ($sort_order as $column => $order) {
                    $select = $select->order("$column $order");
                }
            }

            // Get dependent rows
            $rows = $this->_primaryTableRow->findDependentRowset($table, null, $select);

            // Create new model for each row
            foreach ($rows as $row) {
                $newModel = new $modelclass($row);

                if (!($newModel instanceof Opus_Model_Dependent_Abstract)) {
                    throw new Opus_Model_Exception('Class of ' . $fieldname . ' does not extend Opus_Model_Dependent_Abstract.  Please check class ' . get_class($newModel) . '.');
                }

                if (is_null($newModel->getParentId())) {
                    throw new Opus_Model_Exception('Object in ' . $fieldname . ' contains empty ParentId.  Please check class ' . get_class($newModel) . '.');
                }

                $result[] = $newModel;
            }

            // Form return value
            if (count($rows) === 1) {
                // Return a single object if threre is only one model in the result
                $result = $result[0];
            } else if (count($rows) === 0) {
                // Return explicitly null if no results have been found.
                $result = null;
            }
        }
        // Set the field value
        $this->_fields[$fieldname]->setValue($result);

        // TODO: Could be removed!  Needs more testing before doing so...
        // iterate through dependend models and set parent id
        $list = $result;
        if (false === is_array($result)) {
            $list = array($list);
        }

        $myid = $this->getId();
        foreach ($list as $child) {
            if ($child instanceof Opus_Model_Dependent_Abstract) {
                $child->setParentId($myid);
            }
        }

    }

    /**
     * Remove the model instance from the database.
     *
     * @throws Opus_Model_Exception If a delete operation could not be performed on this model.
     * @return void
     */
    public function delete() {
        $this->_callPluginMethod('preDelete');

        $modelId = $this->getId();

        // if no primary key is set the model has
        // not been stored yet, so delete gets skipped
        if (null === $modelId) {
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

        $this->_callPluginMethod('postDelete', $modelId);
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
        if (false === is_null($this->_primaryTableRow)) {
            $tableclass = $this->_primaryTableRow->getTableClass();
            $table = Opus_Db_TableGateway::getInstance($tableclass);
            $this->_primaryTableRow->setTable($table);
        }
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
        $rows = array();
        if (is_null($ids) === true) {
            $rows = $table->fetchAll(null, $orderBy);
        } else if (empty($ids) === false) {
            $rowset = $table->find($ids);
            if (false === is_null($orderBy)) {
                // Sort manually, since find() does not support order by clause.
                $vals = array();
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
        if (array_key_exists($name, $this->_fields) !== true) {
            return null;
        }

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
    }

    /**
     * Returns whether model is a new record.
     *
     * @return boolean
     */
    public function isNewRecord() {
        return $this->_isNewRecord;
    }

    /**
     * Overwrited setter mechanism to handle link retrieval properly.
     *
     * @param string $fieldname The name of the field.
     * @param mixed  $arguments Arguments passed in the get-call.
     *
     * @return void
     */
    protected function _set($fieldname, $arguments) {
        $field = $this->getField($fieldname);
        if (empty($arguments) === true) {
            throw new Opus_Model_Exception('Argument required for setter function!');
        }
        else if (is_null($arguments[0]) === false) {
            $argumentModelGiven = true;
        }
        else {
            $argumentModelGiven = false;
        }

        $linkmodelclass = $field->getLinkModelClass();

        if (false === is_array($arguments[0])) {
            $values = array($arguments[0]);
        }
        else {
            $values = $arguments[0];
        }

        if (!is_null($linkmodelclass) and ($argumentModelGiven === true)) {
            foreach ($values as $i => $value) {
                $linkmodel = null;
                if (($value instanceof Opus_Model_Dependent_Link_Abstract) === true) {
                    $linkmodel = $value;
                }
                else if (is_null($this->getId()) or is_null($value->getId())) {
                    // If any of the linked models hasn't been stored yet.
                    $linkmodel = new $linkmodelclass;
                    $linkmodel->setModel($value);
                }
                else {
                    try {
                        $linkmodel = new $linkmodelclass(array($this->getId(), $value->getId()));
                    }
                    catch (Opus_Model_NotFoundException $e) {
                        $linkmodel = new $linkmodelclass;
                    }
                    $linkmodel->setModel($value);
                }
                $values[$i] = $linkmodel;
            }
        }

        $field->setValue($values);

    }

    /**
     *  Debugging helper.  Sends the given message to Zend_Log.
     *
     * @param string $message
     */
    protected function logger($message) {
        $registry = Zend_Registry::getInstance();
        $logger = $registry->get('Zend_Log');
        $logger->info(__CLASS__ . ": $message");
    }
}
