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
 * @package     Opus\Model\Dependent
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Dependent;

use Opus\Model\AbstractDb;
use Opus\Model\ModelException;

/**
 * Abstract class for all dependent models in the Opus framework.
 *
 * @category    Framework
 * @package     Opus\Model\Dependent
 *
 */
abstract class AbstractDependentModel extends AbstractDb
{

    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId Defaults to null.
     */
    protected $_parentId = null;

    /**
     * Name of the column in the dependent model's primary table row that
     * contains the parent model's primary key.
     *
     * @var mixed $_parentColumn Defaults to null.
     */
    protected $_parentColumn = null;


    /**
     * Holds the current valid deletion token.
     *
     * Calls to doDelete() will only succeed if this token is passed.
     * A call to delete() will return the next valid token.
     *
     * @var string
     */
    private $_deletionToken = null;

     /** Plugins to load
     *
     * @var array
     */
    public function getDefaultPlugins()
    {
        return [
            'Opus\Model\Plugin\InvalidateDocumentCache'
        ];
    }

    /**
     * Construct a new model instance and connect it a database table's row.
     * Pass an id to immediately fetch model data from the database. If not id is given
     * a new persistent intance gets created wich got its id set as soon as it is stored
     * via a call to _store().
     *
     * @param integer|\Zend_Db_Table_Row $id                (Optional) (Id of) Existing database row.
     * @param \Zend_Db_Table_Abstract    $tableGatewayModel (Optional) Opus\Db model to fetch table row from.
     * @throws ModelException     Thrown if passed id is invalid.
     * @see AbstractDb#__construct()
     */
    public function __construct($id = null, \Zend_Db_Table_Abstract $tableGatewayModel = null)
    {
        parent::__construct($id, $tableGatewayModel);
        if (false === is_null($this->_parentColumn) && $this->_parentColumn != '') {
            $parentId = $this->_primaryTableRow->{$this->_parentColumn};
            if (false === is_null($parentId)) {
                $this->setParentId($parentId);
            }
        }
    }

    /**
     * Setter for $_parentId.
     *
     * @param integer $parentId The id of the parent Opus\Model
     * @return void
     */
    public function setParentId($parentId)
    {
        $this->_parentId = $parentId;
    }

    /**
     * Return the identifier of this models parent model.
     *
     * @return mixed Identifier of the parent model or Null if not set.
     */
    public function getParentId()
    {
        return $this->_parentId;
    }

    /**
     * Set the name of the column holding the parent id
     * of the linked model.
     *
     * @param string $column Name of the parent id column.
     * @return void
     */
    public function setParentIdColumn($column)
    {
        $this->_parentColumn = $column;
    }

    /**
     * Get the name of the column holding the parent id
     * of the linked model.
     *
     * @return string $column Name of the parent id column.
     */
    public function getParentIdColumn()
    {
        return $this->_parentColumn;
    }

    /**
     * Set up the foreign key of the parent before storing.
     *
     * @throws ModelException Thrown if trying to store without parent.
     * @return mixed $id    Primary key of the models primary table row.
     */
    public function store()
    {
        if (null === $this->_parentId) {
            throw new ModelException(
                'Dependent Model ' . get_class($this) . ' without parent cannot be persisted.'
            );
        }
        if (null === $this->_parentColumn) {
            throw new ModelException(
                'Dependent Model ' . get_class($this) . ' needs to know name of the parent-id column.'
            );
        }
        $this->_primaryTableRow->{$this->_parentColumn} = $this->_parentId;
        return parent::store();
    }

    /**
     * Register dependent Model for deletion in its parent Model.
     *
     * This method does not delete the dependent Model. It is in the
     * responsibility of the parent Model to call doDelete() with the
     * delete Token returned by this method.
     *
     * @return string Token to be passed to doDelete() method to actually confirm deletion request.
     */
    public function delete()
    {
        $this->_deletionToken = uniqid();
        return $this->_deletionToken;
    }

    /**
     * Perform actual delete operation if the correct token has been provided.
     *
     * @param string $token Delete token as returned by previous call to delete()
     * @return void
     * @throws ModelException
     */
    public function doDelete($token)
    {
        if ($this->_deletionToken === null) {
            throw new ModelException('No deletion token set. Call delete() prior to doDelete().');
        }
        if ($this->_deletionToken !== $token) {
            throw new ModelException('Invalid deletion token passed.');
        }
        parent::delete();
    }
}
