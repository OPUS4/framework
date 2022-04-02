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
 * @copyright   Copyright (c) 2008-2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Dependent\Link;

use Opus\Common\Model\ModelException;
use Opus\Model\AbstractModel;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Field;

use function array_key_exists;
use function array_merge;
use function get_class;
use function substr;

/**
 * Abstract class for all links to independent models in the Opus framework.
 *
 * phpcs:disable
 */
abstract class AbstractLinkModel extends AbstractDependentModel
{
    /**
     * The model to link to.
     *
     * @var mixed
     */
    protected $model;

    /**
     * The linked models foreign key.
     *
     * @var mixed
     */
    protected $modelKey;

    /**
     * The class of the model that is linked to.
     *
     * @var string
     */
    protected $modelClass = '';

    /**
     * FIXME:Bad design workaround for modification tracking.
     * The linked model is not hold by a native field, so there is no modification
     * tracking for it. Thus we have to track the modification of this using
     * a special private variable.
     *
     * @var bool
     */
    private $isModified = false;

     /** Plugins to load
      *
      * @var array
      */
    public function getDefaultPlugins()
    {
        return null;
    }

    /**
     * Set the model that is linked to.
     *
     * @param  AbstractModel $model The new model to link to.
     */
    public function setModel(AbstractModel $model)
    {
        if ($model instanceof $this->modelClass === false) {
            throw new ModelException(
                static::class . ' expects ' . $this->modelClass . ' as a link target, '
                . get_class($model) . ' given.'
            );
        }

        $this->model      = $model;
        $this->isModified = true;
    }

    /**
     * Return the model instance that is linked to.
     *
     * @return AbstractModel The model that is linked to.
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Get the model class name this link will accept associated models to be instances of.
     *
     * @return string Class name of assignable models.
     */
    public function getModelClass()
    {
        return $this->modelClass;
    }

    /**
     * Get the linked model's foreign key.
     *
     * @return string Name of foreign key for linked model in link model.
     */
    public function getModelKey()
    {
        return $this->modelKey;
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
        if (true === isset($this->fields[$fieldname])) {
            return parent::__call($name, $arguments);
        } else {
            if (array_key_exists(0, $arguments) === true) {
                return $this->model->$name($arguments[0]);
            } else {
                return $this->model->$name();
            }
        }
    }

    /**
     * Get a list of all fields attached to the linked model plus
     * all fields attached to this link model itself.
     *
     * @see    \Opus\Model\Abstract::_internalFields
     *
     * @return array    List of fields
     */
    public function describe()
    {
        $result = parent::describe();
        if (null !== $this->model) {
            $result = array_merge($this->model->describe(), $result);
        }
        return $result;
    }

    /**
     * Get a list of all fields no matter if "hidden" or not plus
     * all fields attached to this LinkModel itself.
     *
     * @see \Opus\Model\Abstract#describeAll()
     *
     * @return array    List of fields
     */
    public function describeAll()
    {
        return array_merge($this->model->describeAll(), parent::describeAll());
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
     * @return string Model class name and identifier (e.g. Opus\Document#4711).
     */
    public function getDisplayName()
    {
        return $this->model->getDisplayName();
    }

    /**
     * Return a reference to an actual field in the linked model if the field is
     * not itself appended to this link model.
     *
     * @param string $name           Name of the requested field.
     * @param bool   $ignorePending If a pending field's values should be fetched, or not.
     * @return Field The requested field instance. If no such instance can be found, null is returned.
     */
    public function getField($name, $ignorePending = false)
    {
        if (true === isset($this->fields[$name])) {
            return parent::getField($name, $ignorePending); // TODO bug? parent function has only one parameter
        }
        return $this->model->getField($name, $ignorePending);
    }

    /**
     * Get the primary key of the linked model instance.
     *
     * @return mixed The primary key of the linked model.
     */
    public function getLinkedModelId()
    {
        return $this->model->getId();
    }

    /**
     * Get a nested associative array representation of the linked model.
     *
     * @return array A (nested) array representation of the linked model.
     */
    public function toArray()
    {
        return array_merge($this->model->toArray(), parent::toArray());
    }

    /**
     * Perform security resoure registration.
     */
    protected function _postStoreInternalFields()
    {
        $isNewFlagBackup   = $this->isNewRecord;
        $this->isNewRecord = false;

        parent::_postStoreInternalFields();

        $this->isNewRecord = $isNewFlagBackup;
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
     * @return bool
     */
    public function isModified()
    {
        return ($this->isModified) || parent::isModified() || $this->model->isModified();
    }

    /**
     * Clears modification flag, but cannot set it to true.
     *
     * @param bool $modified
     * @return mixed|void
     *
     * TODO Function should be renamed since it can actually only clear the modification flag.
     */
    public function setModified($modified = true)
    {
        if (! $modified) {
            $this->isModified = false;
            parent::setModified($modified);
        }
    }

    /**
     * This model is valid IFF both link model *and* linked model are valid.
     *
     * @return bool
     */
    public function isValid()
    {
        return $this->model->isValid() && parent::isValid();
    }
}
