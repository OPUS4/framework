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
     * Constructor. Pass an id to fetch from database.
     *
     * @param integer       $id                (Optional) Id of existing database row.
     * @param Zend_Db_Table $tableGatewayModel (Optional) Opus_Db model to fetch table row from.
     * @throws Opus_Model_Exception            Thrown if passed id is invalid.
     */
    public function __construct($id = null, Zend_Db_Table $tableGatewayModel = null) {
        if ($tableGatewayModel === null) {
            throw new Opus_Model_Exception('No table gateway model passed.');
        }
        if ($id === null) {
            $this->_primaryTableRow = $tableGatewayModel->createRow();
        } else {
            $this->_primaryTableRow = $tableGatewayModel->find($id)->getRow(0);
            if ($this->_primaryTableRow === null) {
                throw new Opus_Model_Exception('No ' .
                get_class($tableGatewayModel) . ' with id $id in database.');
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
     * Fetch attribute values from the table row and set up all fields.
     *
     * @return void
     */
    protected function _fetchValues() {
        foreach ($this->_fields as $fieldname => $field) {
            if (in_array($fieldname, array_keys($this->_externalFields)) === true) {
                $callname = '_fetch' . $fieldname;

                if (method_exists($this, $callname) === true) {
                    $field->setValue($this->$callname());
                } else {
                    $table = $this->_externalFields[$fieldname]['table'];
                    if (array_key_exists('conditions', $this->_externalFields[$fieldname]) === true) {
                        $conditions = $this->_externalFields[$fieldname]['conditions'];
                    } else {
                        $conditions = null;
                    }
                    $model = $field->getValueModelClass();
                    $loadedValue = $this->_loadExternal($model, $table, $conditions);
                    $field->setValue($loadedValue);
                }
            } else {
                $colname = strtolower(preg_replace('/(?!^)[[:upper:]]/','_\0', $fieldname));
                $field->setValue($this->_primaryTableRow->$colname);
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
            foreach ($this->_fields as $fieldname => $field) {
                if (in_array($fieldname, array_keys($this->_externalFields)) === false) {
                    $colname = strtolower(preg_replace('/(?!^)[[:upper:]]/','_\0', $fieldname));
                    $this->_primaryTableRow->{$colname} = $this->_fields[$fieldname]->getValue();
                }
            }
            $id = $this->_primaryTableRow->save();
            foreach (array_keys($this->_externalFields) as $fieldname) {
                if (in_array($fieldname, array_keys($this->_fields)) === true) {
                    $callname = '_store' . $fieldname;
                    if (method_exists($this, $callname) === true) {
                        $this->$callname($this->_fields[$fieldname]->getValue());
                    } else {
                        $this->_storeExternal($this->_fields[$fieldname]->getValue());
                    }
                }
            }
            if ($this->_transactional === true) {
                $dbadapter->commit();
            }
        } catch (Exception $e) {
            if ($this->_transactional === true) {
                $dbadapter->rollback();
            }
            throw new Opus_Model_Exception($e->getMessage());
        }
        return $id;
    }

    /**
     * Save the values of external fields.
     *
     * @param array|Opus_Model_DependentAbstract $values One or mor dependent opus models.
     * @throws Opus_Model_Exception Thrown when trying to save non Opus_Model_Dependent objects.
     * @return void
     */
    protected function _storeExternal($values) {
        if (is_array($values) === true) {
            foreach ($values as $value) {
                $this->_storeExternal($value);
            }
        } else if (is_null($values) === false) {
            if ($values instanceof Opus_Model_DependentAbstract === false) {
                throw new Opus_Model_Exception('External fields must be Opus_Model_Dependent.');
            }
            $values->setParentId($this->getId());
            $values->store();
        }
    }

    /**
     * Load the values of external fields.
     *
     * @param  string        $targetModel Name of the Opus_Model_Dependent class to build.
     * @param  string        $tablename   Table to query.
     * @param  array         $conditions  Conditions for the query.
     * @return array
     */
    protected function _loadExternal($targetModel, $tablename, array $conditions = null) {
        // 1. Get name of id column in target table
        // 2. Get Ids of dependent rows
        // 3. create new model for each id
        $table = new $tablename;
        if ($this->getId() === null) {
            return null;
        }
        $result = array();
        $tableInfo = $table->info();
        $primaryKey = $tableInfo['primary'][1];
        $select = $table->select()->from($table, array($primaryKey));
        if (is_null($conditions) === false) {
            foreach ($conditions as $column => $value) {
                $select = $select->where("$column = ?", $value);
            }
        }
        $ids = $this->_primaryTableRow->findDependentRowset(get_class($table), null, $select)->toArray();
        foreach ($ids as $id) {
            $result[] = new $targetModel($id[$primaryKey]);
        }
        if (count($ids) === 1) {
            return $result[0];
        } else if (count($ids) === 0) {
            return null;
        } else {
            return $result;
        }
    }

    /**
     * Magic method to access the models fields via virtual set/get methods.
     *
     * @param string $name      Name of the method beeing called.
     * @param array  $arguments Arguments for function call.
     * @throws Opus_Model_Exception If an unknown field or method is requested.
     * @return mixed Might return a value if a getter method is called.
     */
    public function __call($name, array $arguments) {
        $accessor = substr($name, 0, 3);
        $fieldname = substr($name, 3);

        if (array_key_exists($fieldname, $this->_fields) === false) {
            throw new Opus_Model_Exception('Unknown field: ' . $fieldname);
        }

        switch ($accessor) {
            case 'get':
                if (empty($arguments) === false) {
                    return $this->_fields[$fieldname]->getValue($arguments[0]);
                } else {
                    return $this->_fields[$fieldname]->getValue();
                }
                break;

            case 'set':
                if (empty($arguments) === true) {
                    throw new Opus_Model_Exception('Argument required for setter function!');
                }
                $this->_fields[$fieldname]->setValue($arguments[0]);
                return $this->_fields[$fieldname]->getValue();
                break;

            case 'add':
                if (is_null($this->_fields[$fieldname]->getValueModelClass()) === true) {
                    throw new Opus_Model_Exception('Add accessor currently only available for fields holding models.');
                }
                $modelclass = $this->_fields[$fieldname]->getValueModelClass();
                $model = new $modelclass;
                if (is_array($this->_fields[$fieldname]->getValue()) === true) {
                    // Add instance to existing multiple values.
                    $this->_fields[$fieldname]->setValue(
                            array_merge(
                                $this->_fields[$fieldname]->getValue(),
                                array($model)
                                )
                            );
                } else if (is_null($this->_fields[$fieldname]->getValue()) === false) {
                    // Add instance to existing single value.
                    $this->_fields[$fieldname]->setValue(array(
                                $this->_fields[$fieldname]->getValue(), $model));
                } else {
                    // Add instance to empty field.
                    $this->_fields[$fieldname]->setValue($model);
                }
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
     * Return a reference to an actual field.
     *
     * @param string $name Name of the requested field.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name) {
        if (array_key_exists($name, $this->_fields) === true) {
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
     * Get a list of all fields (internal & external) attached to the model.
     *
     * @return array    List of fields
     */
    public function describe() {
        return array_keys($this->_fields);
    }
    
    /**
     * Reconnect primary table row to database after unserializing.
     *
     * @return void
     */
    public function __wakeup() {
        $tableclass = $this->_primaryTableRow->getTableClass();
        $this->_primaryTableRow->setTable(new $tableclass);
    }

    /**
     * Set whether storing this model opens a database transaction or not.
     *
     * @param  boolean  $transactional (Optional) Whether to use a transaction or not.
     * @return void
     */
    protected function _setTransactional($transactional = true) {
        $this->_transactional = $transactional;
    }


}
