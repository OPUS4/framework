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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Abstract class for all links to independent models in the Opus framework.
 *
 * @category    Framework
 * @package     Opus_Model
 */
abstract class Opus_Model_Dependent_Link_Abstract extends Opus_Model_Dependent_Abstract
{

    /**
     * The model to link to.
     *
     * @var mixed
     */
    protected $_model;

    /**
     * The linked models foreign key.
     *
     * @var mixed
     */
    protected $_modelKey;

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $_modelClass = '';

    /**
     * FIXME:Bad design workaround for modification tracking.
     * The linked model is not hold by a native field, so there is no modification
     * tracking for it. Thus we have to track the modification of this using
     * a special private variable.
     *
     * @var boolean
     */
    private $_isModified = false;

     /** Plugins to load
     *
     * @var array
     */
    protected $_plugins = [
// Plugin Opus_Model_Plugin_InvalidateDocumentCache
// must NOT be used in dependent link models!
//        'Opus_Model_Plugin_InvalidateDocumentCache' => null,
    ];


    /**
     * Set the model that is linked to.
     *
     * @param  Opus_Model_Abstract $model The new model to link to.
     * @return void
     */
    public function setModel(Opus_Model_Abstract $model)
    {
        if (($model instanceof $this->_modelClass) === false) {
            throw new Opus_Model_Exception(
                get_class($this) . ' expects ' . $this->_modelClass . ' as a link target, ' .
                get_class($model) . ' given.'
            );
        }

        $this->_model = $model;
        $this->_isModified = true;
    }

    /**
     * Return the model instance that is linked to.
     *
     * @return Opus_Model_Abstract The model that is linked to.
     */
    public function getModel()
    {
       return $this->_model;
    }

    /**
     * Get the model class name this link will accept associated models to be instances of.
     *
     * @return string Class name of assignable models.
     */
    public function getModelClass()
    {
        return $this->_modelClass;
    }

    /**
     * Get the linked model's foreign key.
     *
     * @return string Name of foreign key for linked model in link model.
     */
    public function getModelKey()
    {
        return $this->_modelKey;
    }

    /**
     * Perform get/set/add calls.

     * If the requested Field is not owned by this model it tunnels get/set/add methods to the linked model.
     *
     * @param  mixed $name      The name of the called method.
     * @param  array $arguments The arguments passed in the method call.
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        $fieldname = substr($name, 3);

        // use own __call method if field is appended to the link model
        if (true === isset($this->_fields[$fieldname])) {
            return parent::__call($name, $arguments);
        }
        else {
            if (array_key_exists(0, $arguments) === true) {
                return $this->_model->$name($arguments[0]);
            }
            else {
                return $this->_model->$name();
            }
        }
    }

    /**
     * Get a list of all fields attached to the linked model plus
     * all fields attached to this link model itself.
     *
     * @see    Opus_Model_Abstract::_internalFields
     * @return array    List of fields
     */
    public function describe()
    {
        $result = parent::describe();
        if (null !== $this->_model) {
            $result = array_merge($this->_model->describe(), $result);
        }
        return $result;
    }

    /**
     * Get a list of all fields no matter if "hidden" or not plus
     * all fields attached to this LinkModel itself.
     *
     * @see library/Opus/Model/Opus_Model_Abstract#describeAll()
     * @return array    List of fields
     */
    public function describeAll()
    {
        return array_merge($this->_model->describeAll(), parent::describeAll());
    }

    /**
     * Returns a list of all current model fields.
     *
     * @return array  List of fields
     */
    public function describeUntunneled()
    {
        return parent::describe();
    }

    /**
     * Pass the getDisplayname() calls to linked model.
     *
     * @return string Model class name and identifier (e.g. Opus_Document#4711).
     */
    public function getDisplayName()
    {
        return $this->_model->getDisplayName();
    }

    /**
     * Return a reference to an actual field in the linked model if the field is
     * not itself appended to this link model.
     *
     * @param string $name           Name of the requested field.
     * @param bool   $ignorePending If a pending field's values should be fetched, or not.
     * @return Opus_Model_Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name, $ignorePending = false)
    {
        if (true === isset($this->_fields[$name])) {
            return parent::getField($name, $ignorePending); // TODO bug? parent function has only one parameter
        }
        return $this->_model->getField($name, $ignorePending);
    }

    /**
     * Get the primary key of the linked model instance.
     *
     * @return mixed The primary key of the linked model.
     */
    public function getLinkedModelId()
    {
        return $this->_model->getId();
    }


    /**
     * Get a nested associative array representation of the linked model.
     *
     * @return array A (nested) array representation of the linked model.
     */
    public function toArray()
    {
        return array_merge($this->_model->toArray(), parent::toArray());
    }


   /**
    * Perform security resoure registration.
    *
    * @return void
    */
   protected function _postStoreInternalFields()
   {
       $isNewFlagBackup = $this->_isNewRecord;
       $this->_isNewRecord = false;

       parent::_postStoreInternalFields();

       $this->_isNewRecord = $isNewFlagBackup;
   }


   /**
    * Return the primary key of the Link Model if it has been persisted.
    *
    * @return array|null Primary key or Null if the Linked Model has not been persisted.
    */
   public function getId()
   {
       // The given id consists of the ids of the referenced linked models,
       // but there is no evidence that the LinkModel itself has been persisted yet.
       // We so have to validate, if the LinkModel is persistent or still transient.
       if (true === $this->isNewRecord()) {
           // its a new record, so return null
           return null;
       }

       // its not a new record, so we can hand over to the parent method
       return parent::getId();
   }


    /**
     * Tell whether there is a modified field or if the linked
     * model has been newly set via setModel().
     *
     * @return boolean
     */
    public function isModified()
    {
        return ($this->_isModified) or (parent::isModified())
                or ($this->_model->isModified());
    }

    /**
     * This model is valid IFF both link model *and* linked model are valid.
     *
     * @return boolean
     */
    public function isValid()
    {
        return ($this->_model->isValid()) && (parent::isValid());
    }
}
