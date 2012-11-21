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
 * @package     Opus
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Job model used to manage job descriptions.
 *
 * @category    Framework
 * @package     Opus
 */
class Opus_Job extends Opus_Model_AbstractDb {
    
    const STATE_PROCESSING = 'processing';
    const STATE_FAILED = 'failed';
    const STATE_UNDEFINED = 'undefined';
    
    /**
     * Specify then table gateway.
     *
     * @var string
     */
    protected static $_tableGatewayClass = 'Opus_Db_Jobs';
    
    /**
     * Initialize model with the following fields:
     * - Language
     * - Title
     *
     * @return void
     */
    protected function _init() {
        $label = new Opus_Model_Field('Label');
        $label->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());

        $state = new Opus_Model_Field('State');
        
        $data = new Opus_Model_Field('Data');

        $errors = new Opus_Model_Field('Errors');

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
    protected function _preStore() {
        $this->_primaryTableRow->sha1_id = $this->getSha1Id();
        return parent::_preStore();
    }
    
    /**
     * Intercept setter logic to do JSON encoding.
     *
     * @param mixed $value Field value.
     * @throws Exception Thrown if json encoding produce an empty value.
     * @return void
     */
    public function setData($value) {
        $jsonEncode = json_encode($value);
        if ((null !== $value) and (null == $jsonEncode)) {
            throw new Exception('Json encoding failed.');
        }
        $this->_getField('Data')->setValue($jsonEncode);
    }
    
    /**
     * Intercept getter logic to do JSON decoding.
     *
     * @throws Exception Thrown if json decoding failed.
     * @return mixed Value of field.
     */
    public function getData($convertObjectsIntoAssociativeArrays = false) {
        $fieldData = $this->_getField('Data')->getValue();
        $jsonDecode = json_decode($fieldData, $convertObjectsIntoAssociativeArrays);
        if ((null != $fieldData) and (null === $jsonDecode)) {
            throw new Exception('Json decoding failed.');
        }
        return $jsonDecode;
    }
    
    /**
     * Retrieve number of Opus_Job entries in the database.
     *
     * @param string $state (optional) only retrieve jobs in given state (@see Opus_Job for state definitions)
     * @return integer Number of entries in database.
     */
    public static function getCount($state = null) {
        $table  = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->from($table, array('COUNT(id) AS count'));
        if(!is_null($state)) {
            if($state == Opus_Job::STATE_UNDEFINED) {
                $select->where('state IS NULL');
            } else {
                $select->where('state = ?', $state);
            }
        }
        $rowset = $table->fetchAll($select);
        $result = $rowset[0]['count'];
        return $result;
    }

    /**
     * Retrieve number of Opus_Job instances from the database.
     *
     * @param string $state (optional) only retrieve jobs in given state (@see Opus_Job for state definitions)
     * @return array Key / Value pairs of label / count for database entries.
     */
    public static function getCountPerLabel($state = null) {
        $table  = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()
                ->from($table, array('label','COUNT(id) AS count'))
                ->group('label');
        if(!is_null($state)) {
            if($state == Opus_Job::STATE_UNDEFINED) {
                $select->where('state IS NULL');
            } else {
                $select->where('state = ?', $state);
            }
        }
        $rowset = $table->fetchAll($select);
        
        $result = array();
        foreach ($rowset as $row) {
            $result[$row->label] = $row->count;
        }                
        return $result;
    }

    /**
     * Retrieve all Opus_Job instances from the database.
     *
     * @param array $ids (Optional) Set of IDs specifying the models to fetch.
     * @return array Array of Opus_Job objects.
     */
    public static function getAll(array $ids = null) {
        return self::getAllFrom('Opus_Job', self::$_tableGatewayClass, $ids);
    }
    
    
    /**
     * Retrieve all Jobs that have a certain label.
     *
     * @param array $labels Set of labels to get Jobs for.
     * @param string $limit (optional) Number of jobs to retrieve
     * @param string $state (optional) only retrieve jobs in given state
     * @return array Set of Opus_Job objects.
     */
    public static function getByLabels(array $labels, $limit=null, $state = null) {
        if (count($labels) < 1) {
            return null;
        }

        $table  = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select()->from($table);
        foreach ($labels as $label) {
            $select->orWhere('label = ?', $label);    
        }
        if(!is_null($state)) {
            if($state == Opus_Job::STATE_UNDEFINED) {
                $select->where('state IS NULL');
            } else {
                $select->where('state = ?', $state);
            }
        }

        $select->order('id');
        if(!is_null($limit)) {
            $select->limit($limit);
        }
        $rowset = $table->fetchAll($select);

        $result = array();
        foreach ($rowset as $row) {
            $result[] = new Opus_Job($row);
        }                
        return $result;
    }

    /**
     * Tells whether the Job is unique amongst all other jobs
     * in the queue.
     *
     * @return boolean True if job is unique, False otherwise.
     */
    public function isUniqueInQueue() {
        $table  = Opus_Db_TableGateway::getInstance(self::$_tableGatewayClass);
        $select = $table->select();
        $select->from($table, array('count(sha1_id) as count'))
                ->where('sha1_id = ?', $this->getSha1Id());
        $row = $table->fetchRow($select);
        return ((int) $row->count === 0);
    }

    /**
     * Create Sha1 Hash unique to the job.
     *
     * @return string SHA1 Hash.
     */
    public function getSha1Id() {
        $content = $this->getLabel() . serialize($this->getData());
        return sha1($content);
    }
    
}

