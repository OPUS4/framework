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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model;

use InvalidArgumentException;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\NotFoundException;
use Opus\Db\TableGateway;
use Zend_Db_Table_Abstract;
use Zend_Db_Table_Row;

use function array_multisort;
use function call_user_func_array;
use function get_class;
use function implode;
use function is_array;
use function method_exists;

use const SORT_ASC;

/**
 * Trait for database functionality.
 *
 * @todo only used for refactoring right now as a step to get database out of model classes without disruption
 */
trait DatabaseTrait
{
    /**
     * Holds the primary database table row. The concrete class is responsible
     * for any additional table rows it might need.
     *
     * @var Zend_Db_Table_Row
     */
    protected $primaryTableRow;

    /**
     * Holds the name of the models table gateway class.
     *
     * @var string Classname of \Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass;

    /**
     * Names of the fields that are in suspended fetch state.
     *
     * @var array
     */
    protected $pending = [];

    /**
     * Holds persistance status of the model, including all dependant models.
     *
     * @var bool Defaults to true.
     */
    protected $isNewRecord = true;

    /**
     * @param null|int                    $id
     * @param Zend_Db_Table_Abstract|null $tableGatewayModel
     * @throws ModelException
     * @throws NotFoundException
     */
    protected function initDatabase($id = null, $tableGatewayModel = null)
    {
        $gatewayClass = self::getTableGatewayClass();

        // Ensure that a default table gateway class is set
        if ($gatewayClass === null && $tableGatewayModel === null) {
            throw new ModelException(
                'No table gateway model passed or specified by $tableGatewayClass for class: ' . static::class
            );
        }

        if ($tableGatewayModel === null) {
            // Try to query table gateway from internal attribute
            $tableGatewayModel = TableGateway::getInstance($gatewayClass);
        }

        if ($id === null) {
            $this->primaryTableRow = $tableGatewayModel->createRow();
        } elseif ($id instanceof Zend_Db_Table_Row) {
            if ($id->getTableClass() !== $gatewayClass) {
                throw new ModelException(
                    'Mistyped table row passed. Expected row from '
                    . $gatewayClass . ', got row from ' . $id->getTableClass() . '.'
                );
            }
            $this->primaryTableRow = $id;
            $this->isNewRecord     = false;
        } else {
            $idTupel  = is_array($id) ? $id : [$id];
            $idString = is_array($id) ? "(" . implode(",", $id) . ")" : $id;

            // This is needed, because find takes as many parameters as
            // primary keys.  It *does* *not* accept arrays with all primary
            // key columns.
            $rowset = call_user_func_array([&$tableGatewayModel, 'find'], $idTupel);

            if (false === $rowset->count() > 0) {
                throw new NotFoundException(
                    'No ' . get_class($tableGatewayModel)
                    . " with id $idString in database."
                );
            }

            $this->primaryTableRow = $rowset->getRow(0);
            $this->isNewRecord     = false;
        }

        // Paranoid programming, sorry!  Check if proper row has been created.
        if (! $this->primaryTableRow instanceof Zend_Db_Table_Row) {
            throw new ModelException("Invalid row object for class " . static::class);
        }
    }

    /**
     * Retrieve all instances of a particular Opus\Model that are known
     * to the database.
     *
     * @param null|string $modelClassName Name of the model class.
     * @param null|string $tableGatewayClass Name of the table gateway class
     *                                      to determine the table entities shall
     *                                      be fetched from.
     * @param null|array  $ids A list of ids to fetch.
     * @param null|string $orderBy A column name to order by.
     * @return array List of all known model entities.
     * @throws InvalidArgumentException When not passing class names.
     *
     * TODO: Include options array to parametrize query.
     */
    public static function getAllFrom(
        $modelClassName = null,
        $tableGatewayClass = null,
        ?array $ids = null,
        $orderBy = null
    ) {
        // As we are in static context, we have no chance to retrieve
        // those class names.
        if ($modelClassName === null || $tableGatewayClass === null) {
            throw new InvalidArgumentException('Both model class and table gateway class must be given.');
        }

        // As this is calling from static context we cannot
        // use the instance variable $_tableGateway here.
        $table = TableGateway::getInstance($tableGatewayClass);

        // Fetch all entries in one query and pass result table rows
        // directly to models.
        $rows = [];
        if ($ids === null) {
            $rows = $table->fetchAll(null, $orderBy);
        } elseif (empty($ids) === false) {
            $rowset = $table->find($ids);
            if ($orderBy !== null) {
                // Sort manually, since find() does not support order by clause.
                $vals = [];
                foreach ($rowset as $key => $row) {
                    $vals[$key] = $row->$orderBy;
                    $rows[]     = $row;
                }
                array_multisort($vals, SORT_ASC, $rows);
            } else {
                $rows = $rowset;
            }
        }
        $result = [];
        foreach ($rows as $row) {
            $model    = new $modelClassName($row);
            $result[] = $model;
        }
        return $result;
    }

    /**
     * Get current table row object.
     *
     * @return Zend_Db_Table_Row
     * @throws ModelException On invalid row object.
     */
    protected function getTableRow()
    {
        if (! $this->primaryTableRow instanceof Zend_Db_Table_Row) {
            throw new ModelException(
                "Invalid row object for class " . static::class . " -- got class "
                . get_class($this->primaryTableRow)
            );
        }
        return $this->primaryTableRow;
    }

    /**
     * Fetch attribute values from the table row and set up all fields. If fields containing
     * dependent models or link models those got fetched too.
     *
     * phpcs:disable
     */
    protected function _fetchValues()
    {
        // phpcs:enable
        // preFetch plugin hook
        $this->_preFetch();

        foreach ($this->fields as $fieldname => $field) {
            // Field is declared as external and requires special handling
            if (isset($this->externalFields[$fieldname]) === true) {
                // Determine the fields fetching mode
                $fetchmode = 'lazy';
                if (isset($this->externalFields[$fieldname]['fetch']) === true) {
                    $fetchmode = $this->externalFields[$fieldname]['fetch'];
                }

                if ($fetchmode === 'lazy') {
                    // Remember the field to be fetched later.
                    $this->pending[] = $fieldname;
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
                    $colname  = self::convertFieldnameToColumn($fieldname);
                    $fieldval = $this->primaryTableRow->$colname;
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
     * Reconnect primary table row to database after unserializing.
     */
    public function __wakeup()
    {
        if ($this->primaryTableRow !== null) {
            $tableclass = $this->primaryTableRow->getTableClass();
            $table      = TableGateway::getInstance($tableclass);
            $this->primaryTableRow->setTable($table);
        }
    }

    /**
     * Returns whether model is a new record.
     *
     * @return bool
     *
     * TODO LAMINAS isNew replaces isNewRecord
     */
    public function isNewRecord()
    {
        return $this->isNewRecord;
    }

    /**
     * @return bool
     *
     * TODO LAMINAS isNew replaces isNewRecord
     */
    public function isNew()
    {
        return $this->isNewRecord();
    }

    /**
     * Return this models table gateway class name.
     *
     * @return string Table gateway class name.
     */
    public static function getTableGatewayClass()
    {
        return static::$tableGatewayClass;
    }
}
