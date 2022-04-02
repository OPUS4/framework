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

namespace Opus\Model\Dependent;

use Opus\Common\Model\ModelException;
use Opus\Model\AbstractDb;
use Opus\Model\Plugin\InvalidateDocumentCache;
use Zend_Db_Table_Abstract;
use Zend_Db_Table_Row;

use function uniqid;

/**
 * Abstract class for all dependent models in the Opus framework.
 *
 * phpcs:disable
 */
abstract class AbstractDependentModel extends AbstractDb
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed Defaults to null.
     */
    protected $parentId;

    /**
     * Name of the column in the dependent model's primary table row that
     * contains the parent model's primary key.
     *
     * @var mixed Defaults to null.
     */
    protected $parentColumn;

    /**
     * Holds the current valid deletion token.
     *
     * Calls to doDelete() will only succeed if this token is passed.
     * A call to delete() will return the next valid token.
     *
     * @var string
     */
    private $deletionToken;

     /** Plugins to load
      *
      * @var array
      */
    public function getDefaultPlugins()
    {
        return [
            InvalidateDocumentCache::class,
        ];
    }

    /**
     * Construct a new model instance and connect it a database table's row.
     * Pass an id to immediately fetch model data from the database. If not id is given
     * a new persistent intance gets created wich got its id set as soon as it is stored
     * via a call to _store().
     *
     * @see AbstractDb#__construct()
     *
     * @param null|int|Zend_Db_Table_Row  $id (Optional) (Id of) Existing database row.
     * @param null|Zend_Db_Table_Abstract $tableGatewayModel (Optional) Opus\Db model to fetch table row from.
     * @throws ModelException     Thrown if passed id is invalid.
     */
    public function __construct($id = null, ?Zend_Db_Table_Abstract $tableGatewayModel = null)
    {
        parent::__construct($id, $tableGatewayModel);
        if ($this->parentColumn !== null && $this->parentColumn != '') {
            $parentId = $this->primaryTableRow->{$this->parentColumn};
            if ($parentId !== null) {
                $this->setParentId($parentId);
            }
        }
    }

    /**
     * Setter for $_parentId.
     *
     * @param int $parentId The id of the parent Opus\Model
     */
    public function setParentId($parentId)
    {
        $this->parentId = $parentId;
    }

    /**
     * Return the identifier of this models parent model.
     *
     * @return mixed Identifier of the parent model or Null if not set.
     */
    public function getParentId()
    {
        return $this->parentId;
    }

    /**
     * Set the name of the column holding the parent id
     * of the linked model.
     *
     * @param string $column Name of the parent id column.
     */
    public function setParentIdColumn($column)
    {
        $this->parentColumn = $column;
    }

    /**
     * Get the name of the column holding the parent id
     * of the linked model.
     *
     * @return string $column Name of the parent id column.
     */
    public function getParentIdColumn()
    {
        return $this->parentColumn;
    }

    /**
     * Set up the foreign key of the parent before storing.
     *
     * @throws ModelException Thrown if trying to store without parent.
     * @return mixed $id    Primary key of the models primary table row.
     */
    public function store()
    {
        if (null === $this->parentId) {
            throw new ModelException(
                'Dependent Model ' . static::class . ' without parent cannot be persisted.'
            );
        }
        if (null === $this->parentColumn) {
            throw new ModelException(
                'Dependent Model ' . static::class . ' needs to know name of the parent-id column.'
            );
        }
        $this->primaryTableRow->{$this->parentColumn} = $this->parentId;
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
        $this->deletionToken = uniqid();
        return $this->deletionToken;
    }

    /**
     * Perform actual delete operation if the correct token has been provided.
     *
     * @param string $token Delete token as returned by previous call to delete()
     * @throws ModelException
     */
    public function doDelete($token)
    {
        if ($this->deletionToken === null) {
            throw new ModelException('No deletion token set. Call delete() prior to doDelete().');
        }
        if ($this->deletionToken !== $token) {
            throw new ModelException('Invalid deletion token passed.');
        }
        parent::delete();
    }
}
