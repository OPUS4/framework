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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
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

abstract class Opus_Model_AbstractDb extends Opus_Model_Abstract implements Opus_Model_ModificationTracking {

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
        $gatewayClass = self::getTableGatewayClass();

        // Ensure that a default table gateway class is set
        if ((is_null($gatewayClass) === true) and (is_null($tableGatewayModel) === true)) {
            throw new Opus_Model_Exception(
                'No table gateway model passed or specified by $_tableGatewayClass for class: ' . get_class($this)
            );
        }

        if ($tableGatewayModel === null) {
            // Try to query table gateway from internal attribute
            $tableGatewayModel = Opus_Db_TableGateway::getInstance($gatewayClass);
        }

        if ($id === null) {
            $this->_primaryTableRow = $tableGatewayModel->createRow();
        }
        else if ($id instanceof Zend_Db_Table_Row) {
            if ($id->getTableClass() !== $gatewayClass) {
                throw new Opus_Model_Exception(
                    'Mistyped table row passed. Expected row from ' .
                    $gatewayClass . ', got row from ' . $id->getTableClass() . '.'
                );
            }
            $this->_primaryTableRow = $id;
            $this->_isNewRecord = false;
        }
        else {

            $idTupel = is_array($id) ? $id : array($id);
            $idString = is_array($id) ? "(".implode(",", $id).")" : $id;

            // This is needed, because find takes as many parameters as
            // primary keys.  It *does* *not* accept arrays with all primary
            // key columns.
            $rowset = call_user_func_array(array(&$tableGatewayModel, 'find'), $idTupel);

            if (false == ($rowset->count() > 0)) {
                throw new Opus_Model_NotFoundException(
                    'No ' . get_class($tableGatewayModel)
                    . " with id $idString in database."
                );
            }

            $this->_primaryTableRow = $rowset->getRow(0);
            $this->_isNewRecord = false;
        }

        // Paranoid programming, sorry!  Check if proper row has been created.
        if (!$this->_primaryTableRow instanceof Zend_Db_Table_Row) {
           throw new Opus_Model_Exception("Invalid row object for class " . get_class($this));
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
        $fieldname = $field->getName();
        if (isset($fieldname, $this->_externalFields[$fieldname])) {
            $options = $this->_externalFields[$fieldname];

            // set ValueModelClass if a through option is given
            if (isset($options['model'])) {
                $field->setValueModelClass($options['model']);
            }

            // set LinkModelClass if a through option is given
            if (isset($options['through'])) {
                $field->setLinkModelClass($options['through']);
            }

            // set SortOrderField, if a "sort_field" option is given.
            if (isset($options['sort_field'])) {
                $field->setSortFieldName($options['sort_field']);
            }
        }

        return parent::addField($field);
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
        if (false === isset($this->_plugins[$key])) {
            // don't throw exception, just write a warning
            $this->getLogger()->warn('Cannot unregister specified plugin: ' . $key);
        }
        else {
            unset($this->_plugins[$key]);
        }
    }

    /**
     * Return true if the given plugin was already registered; otherwise false.
     * @param string $plugin class name of the plugin
     */
    public function hasPlugin($plugin) {
        return array_key_exists($plugin, $this->_plugins);
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
            if (isset($this->_externalFields[$fieldname]) === true) {
                // Determine the fields fetching mode
                $fetchmode = 'lazy';
                if (isset($this->_externalFields[$fieldname]['fetch']) === true) {
                    $fetchmode = $this->_externalFields[$fieldname]['fetch'];
                }

                if ($fetchmode === 'lazy') {
                    // Remember the field to be fetched later.
                    $this->_pending[] = $fieldname;
                    // Go to next field
                    continue;
                }
                else {
                    // Immediately load external field if fetching mode is set to 'eager'
                    // Load the model instance from the database and
                    // take the resulting object as value for the field
                    $this->_loadExternal($fieldname);
                }
            }
            else {
                // Field is not external an gets handled by simply reading
                // its value from the table row
                // Check if the fetch mechanism for the field is overwritten in model.
                $callname = '_fetch' . $fieldname;
                if (method_exists($this, $callname) === true) {
                    $field->setValue($this->$callname());
                }
                else {
                    $colname = self::convertFieldnameToColumn($fieldname);
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
    protected function _callPluginMethod($methodname, $parameter = null) {
        try {
            if (null === $parameter) {
                $param = $this;
            }
            else {
                $param = $parameter;
            }
            foreach ($this->_plugins as $name=>$plugin) {
                $plugin->$methodname($param);
            }
        } catch (Exception $ex) {
            throw new Opus_Model_Exception(
                'Plugin ' . $name . ' failed in ' . $methodname
                . ' with ' . $ex->getMessage()
            );
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
     * Get current table row object.
     *
     * @return Zend_Db_Table_Row
     *
     * @throws Opus_Model_Exception on invalid row object.
     */
    protected function getTableRow() {
        if (!$this->_primaryTableRow instanceof Zend_Db_Table_Row) {
           throw new Opus_Model_Exception(
               "Invalid row object for class " . get_class($this) . " -- got class "
               . get_class($this->_primaryTableRow)
           );
        }
        return $this->_primaryTableRow;
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
        $dbadapter = $this->getTableRow()->getTable()->getAdapter();
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
                if (isset($this->_externalFields[$fieldname])) {
                    continue;
                }

                // map field values: Cannot process array-valued fields
                $fieldValue = $field->getValue();

                if (!is_null($fieldValue))
                {
                    $fieldValue = trim($fieldValue);
                }

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
                    $colname = self::convertFieldnameToColumn($fieldname);
                    $this->_primaryTableRow->{$colname} = $fieldValue;
                }
                // Clear modification status of successfully stored field.
                $field->clearModified();
            }

            // Backing up values to check for truncated fields after save().
            $backupValues = $this->_primaryTableRow->toArray();

            // Save the row.
            // This returnes the id needed to store external fields.
            $id = $this->_primaryTableRow->save();

            // Hack to check truncated fields.  (See ticket OPUSVIER-2111)
            // TODO: Better use MySQL strict mode "STRICT_TRANS_TABLES".
            foreach ($this->_primaryTableRow->toArray() AS $key => $newValue) {
                // skip id-field
                if ($key === 'id') {
                    continue;
                }

                // if field was empty/too short before storing, skip it!
                if (!isset($backupValues[$key]) || strlen($backupValues[$key]) <= 4) {
                    continue;
                }

                if (strlen($backupValues[$key]) > strlen($newValue)) {
                    $truncateLength = strlen($backupValues[$key]) - strlen($newValue);
                    $msg = get_class($this);
                    $msg .= ": Database column '$key' has been truncated";
                    $msg .= " by $truncateLength characters!";
                    throw new Opus_Model_DbException(get_class($this) . ":  $msg");
                }
            }
        }
        catch (Zend_Db_Statement_Exception $ze) {
            if ($ze->getChainedException() instanceof PDOException and $ze->getCode() === 23000) {
                throw new Opus_Model_DbConstrainViolationException($ze->getMessage(), $ze->getCode(), $ze);
            }
            throw new Opus_Model_DbException($ze->getMessage(), $ze->getCode(), $ze);
        }
        catch (Opus_Model_Exception $ome) {
            // Needed to let instances of Opus_Model_Exception pass without
            // modifying their type.
            throw $ome;
        }
        catch (Exception $e) {
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
            foreach ($this->_externalFields as $fieldname => $fieldInfo) {

                // Skip external fields, that have not been added to the model.
                if (!isset($this->_fields[$fieldname])) {
                    continue;
                }

                $fieldValue = $this->_fields[$fieldname]->getValue();

                // Check if the store mechanism for the field is overwritten in model.
                $callname = '_store' . $fieldname;
                if (method_exists($this, $callname) === true) {
                    $field = $this->_getField($fieldname, true);
                    if (true === $field->isModified()) {
                        // Call custom store method
                        $this->$callname($fieldValue);
                    }
                }
                else {
                    $options = null;
                    if (isset($fieldInfo['options']) === true) {
                        $options = $fieldInfo['options'];
                    }
                    $this->_storeExternal($fieldValue, $options);
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
        catch (Opus_Model_Exception $ome) {
            throw $ome;
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
        }
        else if (is_null($values) === false) {
            if ($values instanceof Opus_Model_Dependent_Abstract === false) {
                throw new Opus_Model_Exception('External fields must be Opus_Model_Dependent.');
            }
            if (is_null($conditions) === false) {
                foreach ($conditions as $column => $value) {
                    $values->_primaryTableRow->$column = $value;

                    // HACK!  See OPUSVIER-2289, OPUSVIER-2292
                    // Make sure, that model field will be initialized, too!
                    $fieldName = self::convertColumnToFieldname($column);
                    if ($this->hasField($fieldName)) {
                        $values->getField($fieldName)
                            ->setValue($value)
                            ->clearModified();
                    }
                }
            }
            $values->setParentId($this->getId());
            $values->store();
        }
    }

    /**
     *
     * @param string $column Column name as string
     * @return string Field name in camel case
     */
    public static function convertColumnToFieldname($column) {
        return preg_replace_callback(
            '/(?:^|_)([a-z])([a-z]+)/',
            function ($matches) {
                return strtoupper($matches[1]) . $matches[2];
            },
            $column
        );
    }

    /**
     *
     * @param string $fieldname Field name in camel case
     * @return string Column name with case-change replaced by underscores "_"
     */
    public static function convertFieldnameToColumn($fieldname) {
        return strtolower(preg_replace('/(?!^)[[:upper:]]/', '_\0', $fieldname));
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
        $field = $this->_fields[$fieldname];

        // Check if the fetch mechanism for the field is overwritten in model.
        $callname = '_fetch' . $fieldname;
        if (method_exists($this, $callname) === true) {
            $result = $this->$callname();
        }
        else {
            // Determine the class of the field values model
            // For handling a link model, see 'through' option.
            $modelclass = $field->getLinkModelClass();
            if (!isset($modelclass)) {
                // For handling a value model, see 'model' option.
                $modelclass = $field->getValueModelClass();
            }

            // Make sure that a field's value model is inherited from Opus_Model_AbstractDb
            if (empty($modelclass) or is_subclass_of($modelclass, 'Opus_Model_AbstractDb') === false) {
                $message = "Field $fieldname must extend Opus_Model_AbstractDb.";
                throw new Opus_Model_Exception($message);
            }

            // Do nothing if the current model has not been persisted
            // (if no identifier given)
            if ($this->getId() === null) {
                return;
            }

            if (empty($modelclass) or is_subclass_of($modelclass, 'Opus_Model_Dependent_Abstract') === false) {
                throw new Opus_Model_Exception(
                    'Class of ' . $fieldname . ' does not extend Opus_Model_Dependent_Abstract.  Please check class '
                    . $modelclass . '.'
                );
            }

            $tableclass = $modelclass::getTableGatewayClass();
            $table = Opus_Db_TableGateway::getInstance($tableclass);
            $select = $table->select();

            // If any declared constraints, add them to query
            if (isset($this->_externalFields[$fieldname]['options'])) {
                $options = $this->_externalFields[$fieldname]['options'];
                foreach ($options as $column => $value) {
                    $select = $select->where("$column = ?", $value);
                }
            }

            // If sort_order is defined, add to query
            if (isset($this->_externalFields[$fieldname]['sort_order'])) {
                $sortOrder = $this->_externalFields[$fieldname]['sort_order'];
                foreach ($sortOrder as $column => $order) {
                    $select = $select->order("$column $order");
                }
            }

            // Get dependent rows
            $result = array();
            $rows = $this->_primaryTableRow->findDependentRowset($table, null, $select);

            // Create new model for each row
            foreach ($rows as $row) {
                $newModel = new $modelclass($row);

                if (is_null($newModel->getParentId())) {
                    throw new Opus_Model_Exception(
                        'Object in ' . $fieldname . ' contains empty ParentId.  Please check class '
                        . get_class($newModel) . '.'
                    );
                }

                $result[] = $newModel;
            }
        }
        // Set the field value
        $field->setValue($result);

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

        // Clear the modified flag for the just loaded field
        $field->clearModified();
    }

    /**
     * Remove the model instance from the database.
     * NOTE: This method should not be overriden, use plugins instead where needed.
     *
     * @throws Opus_Model_Exception If a delete operation could not be performed on this model.
     * @return void
     */
    public function delete() {
        $modelId = $this->getId();

        // if no primary key is set the model has
        // not been stored yet, so delete gets skipped
        // therefore postDelete of plugins does not get called either
        if (null === $modelId) {
            return;
        }

        $this->_callPluginMethod('preDelete');

        // Start transaction
        $dbadapter = $this->getTableRow()->getTable()->getAdapter();
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
        foreach ($tableInfo['primary'] as $primaryKey) {
            $result[] = $this->_primaryTableRow->$primaryKey;
        }
        if (count($result) > 1) {
            return $result;
        }
        else if (count($result) === 1) {
            return $result[0];
        }
        else {
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
    public static function getTableGatewayClass() {
        return static::$_tableGatewayClass;
    }

    /**
     * Retrieve all instances of a particular Opus_Model that are known
     * to the database.
     *
     * @param string $modelClassName        Name of the model class.
     * @param string $tableGatewayClass     Name of the table gateway class
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
    public static function getAllFrom($modelClassName = null, $tableGatewayClass = null, array $ids = null,
                                      $orderBy = null) {

        // As we are in static context, we have no chance to retrieve
        // those class names.
        if ((is_null($modelClassName) === true) or (is_null($tableGatewayClass) === true)) {
            throw new InvalidArgumentException('Both model class and table gateway class must be given.');
        }

        // As this is calling from static context we cannot
        // use the instance variable $_tableGateway here.
        $table = Opus_Db_TableGateway::getInstance($tableGatewayClass);

        // Fetch all entries in one query and pass result table rows
        // directly to models.
        $rows = array();
        if (is_null($ids) === true) {
            $rows = $table->fetchAll(null, $orderBy);
        }
        else if (empty($ids) === false) {
            $rowset = $table->find($ids);
            if (false === is_null($orderBy)) {
                // Sort manually, since find() does not support order by clause.
                $vals = array();
                foreach ($rowset as $key => $row) {
                    $vals[$key] = $row->$orderBy;
                    $rows[] = $row;
                }
                array_multisort($vals, SORT_ASC, $rows);
            }
            else {
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
        if (isset($this->_fields[$name]) !== true) {
            return null;
        }

        // Check if the field is in suspended fetch state
        if (in_array($name, $this->_pending) === true and $ignorePending === false) {
            // Ensure that _loadExternal is called only on external fields
            if (isset($this->_externalFields[$name])) {
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
     * @see Opus_Model_Abstract::_setFieldValue()
     */
    protected function _setFieldValue(Opus_Model_Field $field, $values) {
        $fieldname = $field->getName();
        $linkmodelclass = $field->getLinkModelClass();
        if (!is_null($values) and !is_null($linkmodelclass)) {
            // Workaround for link_-tables with ternary relations.  It's not
            // beautyful, but it works for now.  There won't be an easier
            // solution without major changes on the framework/schema, since
            // we cannot know the type of ternary relations at this point.
            $ternaryRelationName = null;
            if (isset($this->_externalFields[$fieldname]['addprimarykey'][0])) {
                $ternaryRelationName = $this->_externalFields[$fieldname]['addprimarykey'][0];
            }

            $valuesAsArray = is_array($values);
            $values = is_array($values) ? $values : array($values);

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
                    $linkId = array($this->getId(), $value->getId());
                    if (isset($ternaryRelationName)) {
                        $linkId[] = $ternaryRelationName;
                    }

                    try {
                        $linkmodel = new $linkmodelclass($linkId);
                    }
                    catch (Opus_Model_NotFoundException $e) {
                        $linkmodel = new $linkmodelclass;
                    }
                    $linkmodel->setModel($value);
                }
                $values[$i] = $linkmodel;
            }

            if (!$valuesAsArray) {
                $values = $values[0];
            }
        }

        return parent::_setFieldValue($field, $values);
    }

    /**
     * Implements adder mechanism.
     *
     * @see Opus_Model_Abstract::_addFieldValue()
     */
    protected function _addFieldValue(Opus_Model_Field $field, $value) {
        // get Modelclass if model is linked
        $linkmodelclass = $field->getLinkModelClass();
        if (!is_null($linkmodelclass)) {

            // Check if $linkmodelclass is a known class name
            if (class_exists($linkmodelclass) === false) {
                throw new Opus_Model_Exception("Link model class '$linkmodelclass' does not exist.");
            }

            if (is_null($value)) {
                throw new InvalidArgumentException('Argument required when adding to a link field.');
            }

            if (!$value instanceof Opus_Model_Dependent_Link_Abstract) {
                $linkmodel = new $linkmodelclass;
                $linkmodel->setModel($value);
                $value = $linkmodel;
            }
        }

        $value = parent::_addFieldValue($field, $value);
        if ($value instanceof Opus_Model_Dependent_Abstract) {
            $value->setParentId($this->getId());
        }

        return $value;
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
