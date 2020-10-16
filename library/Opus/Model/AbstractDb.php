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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model;

use Opus\Db\TableGateway;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Dependent\Link\AbstractLinkModel;

/**
 * Abstract class for all domain models in the Opus framework that are connected
 * to a database table.
 *
 * @category    Framework
 * @package     Opus\Model
 */
abstract class AbstractDb extends AbstractModel implements ModificationTracking, ModelInterface
{

    use PluginsTrait;

    use DatabaseTrait;

    /**
     * TODO: Change name of this array to somewhat more general.
     * TODO: Not enforce existence of custom _fetch and _store methods in Opus\Model\AbstractDb.
     *
     * In this array extra information for each field of the model can be
     * given, such like the classname of a referenced model object or specific options.
     *
     * It is an associative array referencing an declaration array for each field.
     *
     * 'MyField' => [
     *          'model' => 'Opus\Title',
     *          'options' => ['type' => 'main']]
     *
     * @var array
     */
    protected $_externalFields = [];

    /**
     * Construct a new model instance and connect it a database table's row.
     * Pass an id to immediately fetch model data from the database. If not id is given
     * a new persistent intance gets created wich got its id set as soon as it is stored
     * via a call to _store().
     *
     * @param integer|\Zend_Db_Table_Row $id                (Optional) (Id of) Existing database row.
     * @param \Zend_Db_Table_Abstract    $tableGatewayModel (Optional) Opus\Db model to fetch table row from.
     * @throws ModelException     Thrown if passed id is invalid.
     */
    public function __construct($id = null, \Zend_Db_Table_Abstract $tableGatewayModel = null)
    {
        $this->initDatabase($id, $tableGatewayModel);

        parent::__construct();

        $this->_fetchValues();

        $this->_clearFieldsModifiedFlag();

        $this->_setDefaults();
    }

    /**
     * Clear the modified flag on all fields.
     *
     * @return void
     */
    protected function _clearFieldsModifiedFlag()
    {
        foreach ($this->_fields as $field) {
            $field->clearModified();
        }
    }

    /**
     * Tell whether there is a modified field.
     *
     * @return boolean
     */
    public function isModified()
    {
        foreach ($this->_fields as $field) {
            if (true === $field->isModified()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Set modified status.
     *
     * This function can really only be used to clear the modification status, because it does not make sense to set
     * all fields to modified and this class does not have a separate isModified status.
     *
     * @param bool $modified
     *
     * @return mixed|void
     *
     * TODO throw exception or log warning if setModified is used with 'true'?
     */
    public function setModified($modified = true)
    {
        if (! $modified) {
            $this->_clearFieldsModifiedFlag();
        }
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
     * Trigger preFetch plugins.
     *
     * @return void
     * @throw Opus\Model\ModelException Throws whenever a plugin failes.
     */
    protected function _preFetch()
    {
        $this->callPluginMethod('preFetch');
    }

    /**
     * Perform any actions needed to provide storing.
     *
     * Currently modification checking and validation.
     *
     * @throws ModelException
     * @return mixed Anything else then null will cancel the storage process.
     */
    protected function _preStore()
    {
        $this->callPluginMethod('preStore');

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
            throw new ModelException($msg);
        }

        return null;
    }

    /**
     * Perform any actions needed after storing.
     *
     * Sets _isNewRecord to false.
     *
     * @throws ModelException
     * @return void
     */
    protected function _postStore()
    {
        $this->callPluginMethod('postStore');
        $this->_isNewRecord = false;
    }

    /**
     * Perform any actions needed after storing internal fields.
     *
     * @throws ModelException
     * @return void
     */
    protected function _postStoreInternalFields()
    {
        $this->callPluginMethod('postStoreInternal');
    }

    /**
     * Perform any actions needed after storing internal fields.
     *
     * @return void
     * @throws ModelException Throws whenever an error occurs
     */
    protected function _postStoreExternalFields()
    {
        $this->callPluginMethod('postStoreExternal');
    }

    /**
     * Persist all the models information to its database locations.
     *
     * Storage logic is surrounded by _preStore() and _postStore() calls
     * to enable custom implementations.
     *
     * @see    ModelInterface::store()
     * @throws \Exception
     * @throws ModelException     Thrown if the store operation could not be performed.
     * @return mixed $id    Primary key of the models primary table row.
     */
    public function store()
    {
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
        } catch (\Exception $e) {
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
    protected function _storeInternalFields()
    {
        try {
            // Store basic simple fields to complete the table row
            foreach ($this->_fields as $fieldname => $field) {
                // Skip external fields.
                if (isset($this->_externalFields[$fieldname])) {
                    continue;
                }

                // map field values: Cannot process array-valued fields
                $fieldValue = $field->getValue();

                if (! is_null($fieldValue)) {
                    $fieldValue = trim($fieldValue);
                }

                // Check if the store mechanism for the field is overwritten in model.
                $callname = '_store' . $fieldname;
                if (method_exists($this, $callname) === true) {
                    // Call custom store method
                    $this->$callname($fieldValue);
                } elseif ($field->isModified() === false) {
                    // Skip non-modified field.
                    continue;
                } else {
                    $colname = self::convertFieldnameToColumn($fieldname);
                    $this->_primaryTableRow->{$colname} = $fieldValue;
                }
                // Clear modification status of successfully stored field.
                $field->clearModified();
            }

            // Save the row.
            // This returnes the id needed to store external fields.
            $id = $this->_primaryTableRow->save();
        } catch (\Zend_Db_Statement_Exception $ze) {
            if ($ze->getChainedException() instanceof \PDOException and $ze->getCode() === 23000) {
                throw new DbConstrainViolationException($ze->getMessage(), $ze->getCode(), $ze);
            }
            throw new DbException($ze->getMessage(), $ze->getCode(), $ze);
        } catch (ModelException $ome) {
            // Needed to let instances of Opus\Model\ModelException pass without
            // modifying their type.
            throw $ome;
        } catch (\Exception $e) {
            $msg = $e->getMessage() . ' Model: ' . get_class($this);
            // this works with php >= 5.3.0: throw new Opus\Model\ModelException($msg, $e->getCode(), $e);
            // workaround:
            $msg .= "\nThrown in " . $e->getFile() . ':' . $e->getLine();
            throw new ModelException($msg);
        }
        return $id;
    }

    /**
     * Calls the methods to store the external Fields.
     *
     * @return void
     */
    protected function _storeExternalFields()
    {
        try {
            // Store external fields.
            foreach ($this->_externalFields as $fieldname => $fieldInfo) {
                // Skip external fields, that have not been added to the model.
                if (! isset($this->_fields[$fieldname])) {
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
                } else {
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
        } catch (\Zend_Db_Exception $zdbe) {
            // workaround: throw database adapter exceptions
            throw $zdbe;
        } catch (ModelException $ome) {
            throw $ome;
        } catch (\Exception $e) {
            $msg = $e->getMessage() . ' Model: ' . get_class($this) . ' Field: ' . $fieldname . '.';
            // this works with php >= 5.3.0: throw new Opus\Model\ModelException($msg, $e->getCode(), $e);
            // workaround:
            $msg .= "\nThrown in " . $e->getFile() . ':' . $e->getLine();
            throw new ModelException($msg);
        }
    }

    /**
     * Save the values of external fields.
     *
     * @param array|AbstractDependentModel $values One or mor dependent opus models.
     * @param array                              $conditions (Optional) fixed conditions for certain attributes.
     * @throws ModelException Thrown when trying to save non Opus\Model\Dependent objects.
     * @return void
     */
    protected function _storeExternal($values, array $conditions = null)
    {
        if (is_array($values) === true) {
            foreach ($values as $value) {
                $this->_storeExternal($value, $conditions);
            }
        } elseif (is_null($values) === false) {
            if ($values instanceof AbstractDependentModel === false) {
                throw new ModelException('External fields must be Opus\Model\Dependent.');
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
    public static function convertColumnToFieldname($column)
    {
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
    public static function convertFieldnameToColumn($fieldname)
    {
        return strtolower(preg_replace('/(?!^)[[:upper:]]/', '_\0', $fieldname));
    }

    /**
     * Load the value of an external field. Sets an model instance or an array of
     * model instances depending on whether the field has multiple linked models or not.
     *
     * @param  string $fieldname Name of the external field.
     * @throws ModelException If no _fetch-method is defined for an external field.
     * @return void
     */
    protected function _loadExternal($fieldname)
    {
        $field = $this->_fields[$fieldname];

        // Check if the fetch mechanism for the field is overwritten in model.
        $callname = '_fetch' . $fieldname;
        if (method_exists($this, $callname) === true) {
            $result = $this->$callname();
        } else {
            // Determine the class of the field values model
            // For handling a link model, see 'through' option.
            $modelclass = $field->getLinkModelClass();
            if (! isset($modelclass)) {
                // For handling a value model, see 'model' option.
                $modelclass = $field->getValueModelClass();
            }

            // Make sure that a field's value model is inherited from Opus\Model\AbstractDb
            if (empty($modelclass) or is_subclass_of($modelclass, 'Opus\Model\AbstractDb') === false) {
                $message = "Field $fieldname must extend Opus\Model\AbstractDb.";
                throw new ModelException($message);
            }

            // Do nothing if the current model has not been persisted
            // (if no identifier given)
            if ($this->getId() === null) {
                return;
            }

            if (empty($modelclass) or is_subclass_of($modelclass, 'Opus\Model\Dependent\AbstractDependentModel') === false) {
                throw new ModelException(
                    'Class of ' . $fieldname . ' does not extend Opus\Model\Dependent\AbstractDependentModel.  Please check class '
                    . $modelclass . '.'
                );
            }

            $tableclass = $modelclass::getTableGatewayClass();
            $table = TableGateway::getInstance($tableclass);
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
            $result = [];
            $rows = $this->_primaryTableRow->findDependentRowset($table, null, $select);

            // Create new model for each row
            foreach ($rows as $row) {
                $newModel = new $modelclass($row);

                if (is_null($newModel->getParentId())) {
                    throw new ModelException(
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
            $list = [$list];
        }

        $myid = $this->getId();
        foreach ($list as $child) {
            if ($child instanceof AbstractDependentModel) {
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
     * @throws ModelException If a delete operation could not be performed on this model.
     * @return void
     */
    public function delete()
    {
        $modelId = $this->getId();

        // if no primary key is set the model has
        // not been stored yet, so delete gets skipped
        // therefore postDelete of plugins does not get called either
        if (null === $modelId) {
            return;
        }

        $this->callPluginMethod('preDelete');

        // Start transaction
        $dbadapter = $this->getTableRow()->getTable()->getAdapter();
        $dbadapter->beginTransaction();
        try {
            $this->_primaryTableRow->delete();
            $this->_primaryTableRow = null;
            $dbadapter->commit();
        } catch (\Exception $e) {
            $dbadapter->rollback();
            $msg = $e->getMessage() . ' Model: ' . get_class($this);
            throw new ModelException($msg);
        }

        $this->callPluginMethod('postDelete', $modelId);
    }

    /**
     * Get the models primary key.
     *
     * @return mixed
     */
    public function getId()
    {
        if (null === $this->_primaryTableRow) {
            return null;
        }
        $tableInfo = $this->_primaryTableRow->getTable()->info();
        $result = [];
        foreach ($tableInfo['primary'] as $primaryKey) {
            $result[] = $this->_primaryTableRow->$primaryKey;
        }
        if (count($result) > 1) {
            return $result;
        } elseif (count($result) === 1) {
            return $result[0];
        } else {
            return null;
        }
    }

    /**
     * By default, the textual representation of a modeled entity is
     * its class name and identifier.
     *
     * @return string Model class name and identifier (e.g. Opus\Document#4711).
     */
    public function getDisplayName()
    {
        return get_class($this) . '#' . $this->getId();
    }

    /**
     * Return a reference to an actual field. If an external field yet has to be fetched
     * _loadExternal is called.
     *
     * @param string $name           Name of the requested field.
     * @param bool   $ignore_pending (Optional) If true is given currently pending fields are ignored.
     *                               Default is false.
     * @param string $name           Name of the requested field.
     * @return Field The requested field instance. If no such instance can be found, null is returned.
     */
    protected function _getField($name, $ignorePending = false)
    {
        if (isset($this->_fields[$name]) !== true) {
            return null;
        }

        // Check if the field is in suspended fetch state
        if (in_array($name, $this->_pending) === true and $ignorePending === false) {
            // Ensure that _loadExternal is called only on external fields
            if (isset($this->_externalFields[$name])) {
                $this->_loadExternal($name);
                if (($key = array_search($name, $this->_pending)) !== false) {
                    unset($this->_pending[$key]);
                }
            }
        }
        return $this->_fields[$name];
    }

    /**
     * Overwrited setter mechanism to handle link retrieval properly.
     *
     * @see \Opus\Model\Abstract::_setFieldValue()
     */
    protected function _setFieldValue(Field $field, $values)
    {
        $fieldname = $field->getName();
        $linkmodelclass = $field->getLinkModelClass();
        if (! is_null($values) and ! is_null($linkmodelclass)) {
            // Workaround for link_-tables with ternary relations.  It's not
            // beautyful, but it works for now.  There won't be an easier
            // solution without major changes on the framework/schema, since
            // we cannot know the type of ternary relations at this point.
            $ternaryRelationName = null;
            if (isset($this->_externalFields[$fieldname]['addprimarykey'][0])) {
                $ternaryRelationName = $this->_externalFields[$fieldname]['addprimarykey'][0];
            }

            $valuesAsArray = is_array($values);
            $values = is_array($values) ? $values : [$values];

            foreach ($values as $i => $value) {
                $linkmodel = null;
                if (($value instanceof AbstractLinkModel) === true) {
                    $linkmodel = $value;
                } elseif (is_null($this->getId()) or is_null($value->getId())) {
                    // If any of the linked models hasn't been stored yet.
                    $linkmodel = new $linkmodelclass;
                    $linkmodel->setModel($value);
                } else {
                    $linkId = [$this->getId(), $value->getId()];
                    if (isset($ternaryRelationName)) {
                        $linkId[] = $ternaryRelationName;
                    }

                    try {
                        $linkmodel = new $linkmodelclass($linkId);
                    } catch (NotFoundException $e) {
                        $linkmodel = new $linkmodelclass;
                    }
                    $linkmodel->setModel($value);
                }
                $values[$i] = $linkmodel;
            }

            if (! $valuesAsArray) {
                $values = $values[0];
            }
        }

        return parent::_setFieldValue($field, $values);
    }

    /**
     * Implements adder mechanism.
     *
     * @see AbstractModel::_addFieldValue()
     */
    protected function _addFieldValue(Field $field, $value)
    {
        // get Modelclass if model is linked
        $linkmodelclass = $field->getLinkModelClass();
        if (! is_null($linkmodelclass)) {
            // Check if $linkmodelclass is a known class name
            if (class_exists($linkmodelclass) === false) {
                throw new ModelException("Link model class '$linkmodelclass' does not exist.");
            }

            if (is_null($value)) {
                throw new \InvalidArgumentException('Argument required when adding to a link field.');
            }

            if (! $value instanceof AbstractLinkModel) {
                $linkmodel = new $linkmodelclass;
                $linkmodel->setModel($value);
                $value = $linkmodel;
            }
        }

        $value = parent::_addFieldValue($field, $value);
        if ($value instanceof AbstractDependentModel) {
            $value->setParentId($this->getId());
        }

        return $value;
    }

    /**
     * Returns maximal length for field.
     * @param $name
     */
    public static function getFieldMaxLength($name)
    {
        $column = self::convertFieldnameToColumn($name);

        $table = TableGateway::getInstance(self::getTableGatewayClass());

        $metadata = $table->info();

        if (isset($metadata['metadata'][$column]['LENGTH'])) {
            return $metadata['metadata'][$column]['LENGTH'];
        } else {
            $class = get_called_class();
            \Zend_Registry::get('Zend_Log')->err("Call to $class::getFieldMaxLength for unknown field '$name'.");
            return null;
        }
    }

    /**
     * TODO refactor and document
     */
    public function _setDefaults()
    {
    }
}
