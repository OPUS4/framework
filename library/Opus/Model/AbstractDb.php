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
     * Define name for 'update' permission.
     *
     * @var string
     */
    const PERM_UPDATE = 'update';

    /**
     * Define name for 'delete' permission.
     *
     * @var string
     */
    const PERM_DELETE = 'delete';

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
     * Whether db transaction should be used in store()
     *
     * @var boolean  Defaults to true.
     */
    protected $_transactional = true;

    /**
     * Names of the fields that are in suspended fetch state.
     *
     * @var array
     */
    protected $_pending = array();


    /**
     * Construct a new model instance and connect it a database table's row.
     * Pass an id to immediately fetch model data from the database. If not id is given
     * a new persistent intance gets created wich got its id set as soon as it is stored
     * via a call to _store().
     *
     * @param integer|Zend_Db_Table_Row $id                (Optional) (Id of) Existing database row.
     * @param Zend_Db_Table             $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @throws Opus_Model_Exception            Thrown if passed id is invalid.
     */
    public function __construct($id = null, Opus_Db_TableGateway $tableGatewayModel = null) {
        // Ensure that a default table gateway class is set
        if (is_null($this->getTableGatewayClass()) === true and is_null($tableGatewayModel) === true) {
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
        } else {
            $this->_primaryTableRow = call_user_func_array(array(&$tableGatewayModel, 'find'),$id)->getRow(0);
            if ($this->_primaryTableRow === null) {
                throw new Opus_Model_Exception('No ' . get_class($tableGatewayModel) . " with id $id in database.");
            }
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
                    $field->setValue($this->_primaryTableRow->$colname);
                }
            }
            // Clear the modified flag for the just loaded field
            $field->clearModified();
        }
    }

    /**
     * Persist all the models information to its database locations.
     *
     * @see    Opus_Model_Interface::store()
     * @throws Opus_Model_Exception     Thrown if the store operation could not be performed.
     * @throws Opus_Security_Exception  If the current role has no permission for the 'update' operation.
     * @return mixed $id    Primary key of the models primary table row.
     */
    public function store() {
        if (false === Opus_Security_Realm::getInstance()->isAllowed(self::PERM_UPDATE, $this)) {
            throw new Opus_Security_Exception('Operation ' . self::PERM_UPDATE . ' not allowed for current Role on ' . $this->getResourceId());
        }

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
            $msg = $e->getMessage() . ' Model: ' . get_class($this) . ' Field: ' . $fieldname;
            throw new Opus_Model_Exception($msg);
        }
        return $id;
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
     * @see    Opus_Model_Interface::delete()
     * @throws Opus_Model_Exception    If a delete operation could not be performed on this model.
     * @throws Opus_Security_Exception If the current role has no permission for the 'delete' operation.
     * @return void
     */
    public function delete() {
        if (false === Opus_Security_Realm::getInstance()->isAllowed(self::PERM_DELETE, $this)) {
            throw new Opus_Security_Exception('Operation ' . self::PERM_DELETE . ' not allowed for current Role on ' . $this->getResourceId());
        }
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
     * Returns class name and the id of the model instance if an id is
     * set already.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#getResourceId()
     * @return string The ResourceId for Zend_Acl.
     */
    public function getResourceId() {
        $id = $this->getId();
        $result = get_class($this);

        if (is_null($id)) {
            return $result;
        }

        $result .= "#";
        if (is_array($id)) {
            for ($i = 0; $i < count($id); $i++) {
                if ($i != 0) {
                    $result .= "/";
                }
                $result .= $id[$i];
            }
        } else {
            $result .= $id;
        }
        return $result;
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
    public static function getAllFrom($modelClassName = null, $tableGatewayClassName = null, array $ids = null) {

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
        if (is_null($ids) === true or empty($ids) === true) {
            $rows = $table->fetchAll();
        } else {
            $rows = $table->find($ids);
        }
        $result = array();
        foreach ($rows as $row) {
            $result[] = new $modelClassName($row);
        }
        return $result;
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


}
