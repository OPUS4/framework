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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Exception;
use Opus\Common\JobInterface;
use Opus\Common\JobRepositoryInterface;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Field;
use Zend_Validate_NotEmpty;

use function count;
use function func_get_args;
use function json_decode;
use function json_encode;
use function serialize;
use function sha1;

/**
 * Job model used to manage job descriptions.
 */
class Job extends AbstractDb implements JobInterface, JobRepositoryInterface
{
    const STATE_PROCESSING = 'processing';

    const STATE_FAILED = 'failed';

    const STATE_UNDEFINED = 'undefined';

    /**
     * Specify then table gateway.
     *
     * @var string
     */
    protected static $tableGatewayClass = Db\Jobs::class;

    /**
     * Initialize model with the following fields:
     * - Language
     * - Title
     */
    protected function init()
    {
        $label = new Field('Label');
        $label->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $state = new Field('State');

        $data = new Field('Data');

        $errors = new Field('Errors');

        $this->addField($label)
            ->addField($state)
            ->addField($data)
            ->addField($errors);
    }

    /**
     * Set SHA1 hash column value to table row.
     *
     * @return mixed Database id.
     */
    protected function _preStore()
    {
        $this->primaryTableRow->sha1_id = $this->getSha1Id();
        return parent::_preStore();
    }

    /**
     * Intercept setter logic to do JSON encoding.
     *
     * @param mixed $value Field value.
     * @throws Exception Thrown if json encoding produce an empty value.
     */
    public function setData($value)
    {
        $jsonEncode = json_encode($value);
        if ((null !== $value) && (null === $jsonEncode)) {
            throw new Exception('Json encoding failed.');
        }
        $this->_getField('Data')->setValue($jsonEncode);
    }

    /**
     * Intercept getter logic to do JSON decoding.
     *
     * @param bool $convertObjectsIntoAssociativeArrays
     * @return mixed Value of field.
     * @throws Exception Thrown if json decoding failed.
     */
    public function getData($convertObjectsIntoAssociativeArrays = false)
    {
        $fieldData = $this->_getField('Data')->getValue();
        if ($fieldData === null) {
            throw new Exception('No JSON data to decode.');
        }
        $jsonDecode = json_decode($fieldData, $convertObjectsIntoAssociativeArrays);
        if (null === $jsonDecode) {
            throw new Exception('JSON decoding failed.');
        }
        return $jsonDecode;
    }

    /**
     * Retrieve number of Opus\Job entries in the database.
     *
     * @param null|string $state (optional) only retrieve jobs in given state (@see Opus\Job for state definitions)
     * @return int Number of entries in database.
     */
    public function getCount($state = null)
    {
        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->from($table, ['COUNT(id) AS count']);
        if ($state !== null) {
            if ($state === self::STATE_UNDEFINED) {
                $select->where('state IS NULL');
            } else {
                $select->where('state = ?', $state);
            }
        }
        $rowset = $table->fetchAll($select);
        return $rowset[0]['count'];
    }

    /**
     * Retrieve number of Opus\Job entries for a given label in the database.
     *
     * @param string      $label only consider jobs with the given label
     * @param null|string $state (optional) only retrieve jobs in given state (@see Opus\Job for state definitions)
     * @return int Number of entries in database.
     */
    public function getCountForLabel($label, $state = null)
    {
        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->from($table, ['COUNT(id) AS count']);
        if ($state !== null) {
            if ($state === self::STATE_UNDEFINED) {
                $select->where('state IS NULL');
            } else {
                $select->where('state = ?', $state);
            }
        }
        $select->where('label = ?', $label);
        $rowset = $table->fetchAll($select);
        return $rowset[0]['count'];
    }

    /**
     * Retrieve number of Opus\Job instances from the database.
     *
     * @param null|string $state (optional) only retrieve jobs in given state (@see Opus\Job for state definitions)
     * @return array Key / Value pairs of label / count for database entries.
     */
    public function getCountPerLabel($state = null)
    {
        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()
                ->from($table, ['label', 'COUNT(id) AS count'])
                ->group('label');
        if ($state !== null) {
            if ($state === self::STATE_UNDEFINED) {
                $select->where('state IS NULL');
            } else {
                $select->where('state = ?', $state);
            }
        }
        $rowset = $table->fetchAll($select);

        $result = [];
        foreach ($rowset as $row) {
            $result[$row->label] = $row->count;
        }
        return $result;
    }

    /**
     * Retrieve all Opus\Job instances from the database.
     *
     * @param null|array $ids (Optional) Set of IDs specifying the models to fetch.
     * @return array Array of Opus\Job objects.
     */
    public function getAll($ids = null)
    {
        return self::getAllFrom(self::class, self::$tableGatewayClass, $ids);
    }

    /**
     * Retrieve all Jobs that have a certain label.
     *
     * @param array       $labels Set of labels to get Jobs for.
     * @param null|string $limit (optional) Number of jobs to retrieve
     * @param null|string $state (optional) only retrieve jobs in given state
     * @return array|null Set of Opus\Job objects.
     */
    public function getByLabels($labels, $limit = null, $state = null)
    {
        if (count($labels) < 1) {
            return null;
        }

        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select()->from($table);
        foreach ($labels as $label) {
            $select->orWhere('label = ?', $label);
        }
        if ($state !== null) {
            if ($state === self::STATE_UNDEFINED) {
                $select->where('state IS NULL');
            } else {
                $select->where('state = ?', $state);
            }
        }

        $select->order('id');
        if ($limit !== null) {
            $select->limit($limit);
        }
        $rowset = $table->fetchAll($select);

        $result = [];
        foreach ($rowset as $row) {
            $result[] = new Job($row);
        }
        return $result;
    }

    /**
     * Tells whether the Job is unique amongst all other jobs
     * in the queue.
     *
     * @return bool True if job is unique, False otherwise.
     */
    public function isUniqueInQueue()
    {
        $table  = TableGateway::getInstance(self::$tableGatewayClass);
        $select = $table->select();
        $select->from($table, ['count(sha1_id) as count'])
                ->where('sha1_id = ?', $this->getSha1Id());
        $row = $table->fetchRow($select);
        return (int) $row->count === 0;
    }

    /**
     * Create Sha1 Hash unique to the job.
     *
     * @return string SHA1 Hash.
     */
    public function getSha1Id()
    {
        $content = $this->getLabel() . serialize($this->getData());
        return sha1($content);
    }

    /**
     * Deletes all jobs currently stored in the datebase.
     *
     * Used especially for setting up unit tests.
     */
    public function deleteAll()
    {
        $table = TableGateway::getInstance(self::$tableGatewayClass);
        $table->getAdapter()->query('DELETE from jobs');
    }

    /**
     * @return string
     */
    public function getErrors()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $errors
     * @return $this
     */
    public function setErrors($errors)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @return string|null
     */
    public function getState()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    /**
     * @param string|null $state
     * @return $this
     */
    public function setState($state)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
