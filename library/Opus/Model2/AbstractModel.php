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
 * @copyright   Copyright (c) 2021-2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model2;

use BadMethodCallException;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Opus\Date;
use Opus\Db2\OpusEntityManager;
use Opus\Model\DbException;
use Opus\Model\NotFoundException;

use function in_array;
use function lcfirst;
use function property_exists;
use function substr;

/**
 * Base class for OPUS 4 model classes.
 *
 * TODO define interface for basic model classes
 * TODO toXml function
 * TODO fromXml function
 * TODO integrate Properties functionality, implement interface
 * TODO move getId and $id to base class (here)
 * TODO implement basic getDisplayName function ?
 * TODO add new() function
 */
abstract class AbstractModel
{
    /** @var OpusEntityManager Object for accessing database connections/repositories */
    private static $entityManager;

    // TODO The use of String as the type of $modelId become necessary due to the existence of models
    //      which do not use "id" as the primary key
  
    /**
     * @param int|string $modelId
     * @return self|null
     * @throws DbException
     * @throws NotFoundException
     */
    public static function get($modelId)
    {
        try {
            $model = self::getRepository()->findOneBy(['id' => $modelId]);
        } catch (Exception $e) {
            throw new DbException($e->getMessage());
        }

        if ($model === null) {
            throw new NotFoundException('No ' . static::class . " with id $modelId in database.");
        } else {
            return $model;
        }
    }

    /**
     * Returns the relevant properties of the class
     *
     * @return array
     */
    abstract protected static function describe();

    /**
     * @return OpusEntityManager
     */
    protected static function getEntityManager()
    {
        if (self::$entityManager === null) {
            self::$entityManager = new OpusEntityManager();
        }
        return self::$entityManager;
    }

    /**
     * @return EntityRepository|ObjectRepository
     * @throws ORMException
     */
    protected static function getRepository()
    {
        return self::getEntityManager()->getRepository(static::class);
    }

    // TODO: The use of String as the return type become necessary due to the existence of models
    // TODO: wich do not use "id" as the primary key
    /** @return int|string */
    abstract public function getId();

    /**
     * Persist all the models information to its database locations.
     *
     * @return int ID of stored object
     * @throws DbException
     */
    public function store()
    {
        return $this->getEntityManager()->store($this);
    }

    /**
     * Remove the model instance from the database.
     *
     * @throws DbException
     */
    public function delete()
    {
        try {
            $this->getEntityManager()->delete($this);
        } catch (Exception $e) {
            throw new DbException($e->getMessage());
        }
    }

    /**
     * Returns a nested associative array representation of the model data.
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];
        foreach (static::describe() as $propertyName) {
            $value = $this->{"get" . $propertyName}();

            // TODO: Because Date was not derived from Model2/AbstractModel we need to check for Date explicitly,
            // TODO: In the future Date should extend Model2/bstractModel or we need a more basic class/interface.
            if ($value instanceof Date || $value instanceof AbstractModel) {
                $result[$propertyName] = $value->toArray();
            } else {
                $result[$propertyName] = $value;
            }
        }

        return $result;
    }

    /**
     * Updates the model with the data from an array.
     *
     * New objects are created for values with a model class. If a link model class is specified those objects
     * are created as well.
     *
     * @param array $data
     *
     * TODO support updateFromArray for linked model objects (e.g. update Title object when updating Document)
     */
    public function updateFromArray($data)
    {
        $validProperties = static::describe();

        foreach ($data as $propertyName => $value) {
            if (in_array($propertyName, $validProperties, true)) {
                $this->{"set" . $propertyName}($value);
            }
        }
    }

    /**
     * Creates a new object and initializes it with data.
     *
     * @param array $data
     * @return array
     */
    public static function fromArray($data)
    {
        $model = new static();
        $model->updateFromArray($data);
        return $model;
    }
}
