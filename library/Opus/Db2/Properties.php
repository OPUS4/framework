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
 * @copyright   Copyright (c) 2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Model
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Db2;

use Opus\Model\DbException;
use Opus\Model\PropertiesException;
use Opus\Model\PropertySupportInterface;
use Opus\Model\UnknownModelTypeException;
use Opus\Model\UnknownPropertyKeyException;

/**
 * Class for accessing/storing properties for model objects in database.
 *
 * This class is used for internal metadata associated with objects like documents, files, etc.
 *
 * Separating these properties from "regular" metadata makes sense if you consider the possibility
 * of storing the document metadata, the content of the repository, in XML files rather than
 * database tables. In that case we still need to have internal properties like the extraction status
 * for files or the registration status of identifiers in the database for quick and easy access.
 *
 * Model types are not class names to keep the stored properties independent of implementation details.
 * The names of classes can change while still representing the same model type, e.g. a document.
 *
 * Models need to implement `Opus\Model\PropertySupportInterface`.
 *
 * Ideas for future development.
 * TODO support registering handlers for property conversion (e.g. array to json)
 * TODO add interface to make class exchangeable
 * TODO performance?
 * TODO cache type ids?
 * TODO cache key ids?
 * TODO see getTable function (the model class/interface should be database independent)
 *
 * phpcs:disable
 */
class Properties extends TableGateway
{

    /**
     * Table storing properties.
     */
    const TABLE_PROPERTIES = 'model_properties';

    /**
     * Table storing registered keys.
     */
    const TABLE_KEYS = 'propertykeys';

    /**
     * Table storing registered types.
     */
    const TABLE_TYPES = 'model_types';

    /**
     * Allow letters, numbers and single dots for keys. Key may not end with a dot and must start with a letter.
     */
    const KEY_PATTERN = '/^[A-Za-z][A-Za-z0-9]*(?:\.[A-Za-z0-9]+)*$/';

    /**
     * @var bool Enables automatic registration of model types
     */
    private $autoRegisterType = false;

    /**
     * @var bool Enables automatic registration of keys
     */
    private $autoRegisterKey = false;

    /**
     * Registers a model type.
     *
     * @param string $type Identifier for model type
     * @throws DbException
     */
    public function registerType($type)
    {
        $conn = $this->getConnection();

        if (! in_array($type, $this->getTypes())) {
            try {
                $conn->beginTransaction();
                $conn->insert(self::TABLE_TYPES, ['type' => $type]);
                $conn->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $conn->rollBack();
                throw new DbException($e);
            }
        }
    }

    /**
     * Removes a model type from the properties.
     *
     * This also removes all the properties stored for the model type.
     *
     * @param string $type Model type to be removed
     * @throws DbException
     * @throws UnknownModelTypeException
     */
    public function unregisterType($type)
    {
        $conn = $this->getConnection();

        if (in_array($type, $this->getTypes())) {
            try {
                $conn->beginTransaction();
                $conn->delete(self::TABLE_TYPES, ['type' => $type]);
                $conn->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $conn->rollBack(); // finish transaction without doing anything
                throw new DbException($e);
            }
        } else {
            throw new UnknownModelTypeException("Model type '$type' not found");
        }
    }

    /**
     * Returns all registered model types.
     * @return string[] Model types
     */
    public function getTypes()
    {
        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('type')
            ->from(self::TABLE_TYPES);

        $result = $select->execute()->fetchFirstColumn($select);

        return $result;
    }

    /**
     * Adds property key to database.
     *
     * This function does not use "ON DUPLICATE KEY UPDATE" because there are no columns that
     * would have to be updated if the key is already present in the table.
     *
     * @param string $key Name of property
     * @throws DbException
     */
    public function registerKey($key)
    {
        $conn = $this->getConnection();

        if (! in_array($key, $this->getKeys())) {
            $this->validateKey($key);

            try {
                $conn->beginTransaction();
                $conn->insert(self::TABLE_KEYS, ['name' => $key]);
                $conn->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $conn->rollBack();
                throw new DbException($e);
            }
        }
    }

    /**
     * Removes a key from the properties.
     *
     * This will remove that key from the properties of all models.
     *
     * @param string $key Name of property
     * @throws DbException
     * @throws UnknownPropertyKeyException
     */
    public function unregisterKey($key)
    {
        $conn = $this->getConnection();

        if (in_array($key, $this->getKeys())) {
            try {
                $conn->beginTransaction();
                $conn->delete(self::TABLE_KEYS, ['name' => $key]);
                $conn->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $conn->rollBack(); // finish transaction without doing anything
                throw new DbException($e);
            }
        } else {
            throw new UnknownPropertyKeyException("Property key '$key' not found");
        }
    }

    /**
     * Returns all registered keys.
     * @return string[] Names of properties
     */
    public function getKeys()
    {
        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('name')
            ->from(self::TABLE_KEYS);

        $result = $queryBuilder->execute()->fetchFirstColumn($select);

        return $result;
    }

    /**
     * Stores a property for a model.
     *
     * @param mixed $model Model object implementing Opus\Model\PropertySupportInterface
     * @param string $key Name of property
     * @param string $value Value of property
     * @throws UnknownModelTypeException
     * @throws UnknownPropertyKeyException
     *
     * TODO transaction?
     */
    public function setProperty($model, $key, $value)
    {
        if ($value === null) {
            $this->removeProperty($model, $key);
            return;
        }

        $modelType = $this->getModelType($model);
        $modelTypeId = $this->getModelTypeId($modelType);
        $modelId = $this->getModelId($model);
        $keyId = $this->getKeyId($key);

        $this->insertIgnoreDuplicate(self::TABLE_PROPERTIES, null, [
            'model_type_id' => $modelTypeId,
            'model_id' => $modelId,
            'key_id' => $keyId,
            'value' => $value
        ]);
    }

    /**
     * Returns all the properties of a model.
     *
     * @param mixed $model Model object
     * @param string|null $type Model type
     * @return array Associative array with property keys and values
     * @throws PropertiesException
     * @throws UnknownModelTypeException
     */
    public function getProperties($model, $type = null)
    {
        if ($type !== null && (is_int($model) || ctype_digit($model))) {
            $modelTypeId = $this->getModelTypeId($type);
            $modelId = $model;
        } else {
            $modelType = $this->getModelType($model);
            $modelTypeId = $this->getModelTypeId($modelType);
            $modelId = $this->getModelId($model);
        }

        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('k.name', 'p.value')
            ->from(self::TABLE_PROPERTIES, 'p')
            ->join('p', self::TABLE_KEYS, 'k', 'p.key_id = k.id')
            ->where('p.model_type_id = ?')
            ->andWhere('p.model_id = ?');

        $select->setParameters([$modelTypeId, $modelId]);

        $result = $queryBuilder->execute()->fetchAllKeyValue($select);

        return $result;
    }

    /**
     * Returns value of a property for a model.
     * @param mixed $model Model object
     * @param string $key Name of property
     * @return string|null Value of property or null
     * @throws PropertiesException
     * @throws UnknownModelTypeException
     * @throws UnknownPropertyKeyException
     */
    public function getProperty($model, $key)
    {
        $modelType = $this->getModelType($model);
        $keyId = $this->getKeyId($key);
        $modelTypeId = $this->getModelTypeId($modelType);
        $modelId = $this->getModelId($model);

        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('value')
            ->from(self::TABLE_PROPERTIES)
            ->where('model_type_id = ?')
            ->andWhere('key_id = ?')
            ->andWhere('model_id = ?');

        $select->setParameters([$modelTypeId, $keyId, $modelId]);

        $value = $queryBuilder->execute()->fetchOne($select);

        if ($value === false) {
            return null;
        }

        return $value;
    }

    /**
     * Removes all properties of a model.
     * @param mixed $model Model object
     * @param string|null $type Model type
     * @throws DbException
     * @throws PropertiesException
     * @throws UnknownModelTypeException
     */
    public function removeProperties($model, $type = null)
    {
        $conn = $this->getConnection();

        if ($type !== null && (is_int($model) || ctype_digit($model))) {
            $modelTypeId = $this->getModelTypeId($type);
            $modelId = $model;
        } else {
            $modelType = $this->getModelType($model);
            $modelTypeId = $this->getModelTypeId($modelType);
            $modelId = $this->getModelId($model);
        }

        try {
            $conn->beginTransaction();
            $conn->delete(self::TABLE_PROPERTIES, [
                'model_type_id' => $modelTypeId,
                'model_id' => $modelId
            ]);
            $conn->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $conn->rollBack();
            throw new DbException($e);
        }
    }

    /**
     * Removes a property from a model.
     * @param mixed $model Model object
     * @param string $key Name of property
     * @throws DbException
     * @throws PropertiesException
     * @throws UnknownModelTypeException
     * @throws UnknownPropertyKeyException
     */
    public function removeProperty($model, $key)
    {
        $conn = $this->getConnection();

        $keyId = $this->getKeyId($key);
        $modelType = $this->getModelType($model);
        $modelTypeId = $this->getModelTypeId($modelType);
        $modelId = $this->getModelId($model);

        try {
            $conn->beginTransaction();
            $conn->delete(self::TABLE_PROPERTIES, [
                'model_type_id' => $modelTypeId,
                'model_id' => $modelId,
                'key_id' => $keyId
            ]);
            $conn->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $conn->rollBack();
            throw new DbException($e);
        }
    }

    public function findModels($key, $value, $modelType = null)
    {
        $keyId = $this->getKeyId($key);

        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('model_id')
            ->from(self::TABLE_PROPERTIES)
            ->where('key_id = ?');

        if ($modelType !== null) {
            $modelTypeId = $this->getModelTypeId($modelType);
            $select->andWhere('model_type_id = ?');
            $select->setParameters([$keyId, $modelTypeId]);
            $value = $queryBuilder->execute()->fetchFirstColumn($select);
        } else {
            $select->setParameters([$keyId]);
            $value = $queryBuilder->execute()->fetchFirstColumn($select);
        }

        if ($value === false) {
            return null;
        } else {
            return $value;
        }
    }

    /**
     * Renames a key without removing the stored values.
     * @param string $oldKey Name of existing key
     * @param string $newKey New name of key
     * @throws DbException
     * @throws UnknownPropertyKeyException
     */
    public function renameKey($oldKey, $newKey)
    {
        $this->validateKey($newKey);

        $keyId = $this->getKeyId($oldKey);

        $conn = $this->getConnection();

        try {
            $conn->beginTransaction();
            $conn->update(self::TABLE_KEYS, [
                'name' => $newKey
            ], [
                'id' => $keyId
            ]);
            $conn->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $conn->rollback();
            throw new DbException($e);
        }
    }

    /**
     * Returns true if automatic registration of model types is enabled.
     * @return bool true if automatic registration is enabled
     */
    public function isAutoRegisterTypeEnabled()
    {
        return $this->autoRegisterType;
    }

    /**
     * Enables/disables automatic registration of model types.
     * @param boolean $enabled Enables/disables auto registration
     */
    public function setAutoRegisterTypeEnabled($enabled)
    {
        if ($enabled === null) {
            throw new \InvalidArgumentException('Argument must not be null');
        }

        $bool = filter_var($enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            throw new \InvalidArgumentException('Argument must be boolean');
        }

        $this->autoRegisterType = $bool;
    }

    /**
     * Returns true if automatic registration of keys is enabled.
     * @return bool true if automatic registration is enabled
     */
    public function isAutoRegisterKeyEnabled()
    {
        return $this->autoRegisterKey;
    }

    /**
     * Enables/disables automatic registration of keys.
     * @param $enabled bool Enable/disable auto registration
     */
    public function setAutoRegisterKeyEnabled($enabled)
    {
        if ($enabled === null) {
            throw new \InvalidArgumentException('Argument must not be null');
        }

        $bool = filter_var($enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        if ($bool === null) {
            throw new \InvalidArgumentException('Argument must be boolean');
        }

        $this->autoRegisterKey = $bool;
    }

    /**
     * Returns ID of a model.
     *
     * @param mixed $model Model object
     * @return int Model identifier
     * @throws PropertiesException
     */
    protected function getModelId($model)
    {
        $modelId = $model->getId();

        if ($modelId === null) {
            throw new PropertiesException('Model ID is null');
        }

        return $modelId;
    }

    /**
     * Returns ID for a key.
     * @param string $key Name of property
     * @return int
     * @throws UnknownPropertyKeyException
     */
    protected function getKeyId($key)
    {
        if ($key === null) {
            throw new \InvalidArgumentException('Key argument must not be null');
        }

        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('name', 'id')
            ->from(self::TABLE_KEYS);

        $result = $queryBuilder->execute()->fetchAllKeyValue($select);

        if (isset($result[$key])) {
            return $result[$key];
        } else {
            if ($this->isAutoRegisterKeyEnabled()) {
                $this->registerKey($key);
                return $this->getKeyId($key);
            } else {
                throw new UnknownPropertyKeyException("Property key '$key' not found");
            }
        }
    }

    /**
     * Returns type for model.
     * @param mixed $model Model object
     * @return string Type of model
     */
    protected function getModelType($model)
    {
        if ($model === null) {
            throw new \InvalidArgumentException('Model argument must not be null');
        }

        if (! $model instanceof PropertySupportInterface) {
            throw new \InvalidArgumentException(
                'Model argument must be of type Opus\Model\PropertySupportInterface'
            );
        }

        return $model->getModelType();
    }

    /**
     * Returns ID for model type.
     * @param string $type Model type
     * @return int
     * @throws UnknownModelTypeException
     * @throws \Doctrine\DBAL\Exception
     */
    protected function getModelTypeId($type)
    {
        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('type', 'id')
            ->from(self::TABLE_TYPES);

        $result = $queryBuilder->execute()->fetchAllKeyValue($select);

        if (isset($result[$type])) {
            return $result[$type];
        } else {
            if ($this->isAutoRegisterTypeEnabled()) {
                $this->registerType($type);
                return $this->getModelTypeId($type);
            } else {
                throw new UnknownModelTypeException("Model type '$type' not found");
            }
        }
    }

    /**
     * Validates format of property key.
     * @param string $key Name of property
     */
    protected function validateKey($key)
    {
        if (preg_match(self::KEY_PATTERN, $key) === 0) {
            throw new \InvalidArgumentException("Key '$key' is not valid.");
        }
    }
}