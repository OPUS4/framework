<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @package     Opus_Db
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Implements the singleton pattern for table gateway classes.
 *
 * @category    Framework
 * @package     Opus_Db
 *
 */
abstract class Opus_Db_TableGateway extends Zend_Db_Table_Abstract
{
    /**
     * Holds all table gateway instances.
     *
     * @var array  Defaults to array().
     */
    private static $instances = array();

    /**
     * Delivers the singleton instances.
     *
     * @param  mixed  $class The class name of the instance to get.
     *
     * @return Opus_Db_TableGateway
     */
    final public static function getInstance($class) {
        if (!isset(self::$instances[$class])) {
            $object = new $class();
            return self::$instances[$class] = $object;
        } else {
            return self::$instances[$class];
        }
    }

    /**
     * Delivers all currently available singleton instances.
     *
     * @return array
     */
    final public static function getAllInstances() {
        return self::$instances;
    }

    /**
     * Clear database instances.
     */
    final public static function clearInstances() {
        self::$instances = array();
    }

    /**
     * Insert given array into table and ignore duplicate entries.  (Silently
     * skipping insert, if unique constraint has been violated.)
     *
     * @param array $data
     * @return void
     */
    public function insertIgnoreDuplicate($data) {
        $adapter = $this->getAdapter();

        $q_keys = array();
        $q_values = array();
        foreach ($data AS $key => $value) {
            $q_keys[] = $adapter->quoteIdentifier($key);
            $q_values[] = $adapter->quote($value);
        }


        $insert = 'INSERT IGNORE INTO ' . $adapter->quoteTableAs($this->_name) .
                ' (' . implode(', ', $q_keys) . ') ' .
                ' VALUES (' . implode(', ', $q_values) . ') ';

        $adapter->query($insert);
        return;
    }

    /**
     * Delete the table row that matches the given array.  (Silently ignoring
     * deletes of non-existent entries.)
     *
     * @param array $data
     * @return void
     */
    public function deleteWhereArray($data) {
        $adapter = $this->getAdapter();

        $q_clauses = array();
        foreach ($data AS $key => $value) {
            $q_key = $adapter->quoteIdentifier($key);
            $q_value = $adapter->quote($value);
            $q_clauses[] = $q_key . " = " . $q_value;
        }

        $where = implode(" AND ", $q_clauses);
        $this->delete($where);
        return;
    }

    /**
     * FIXME: Constructor should be private due to singleton pattern. This
     * conflicts with the signature of the Zend_Db_Table_Abstract contructor.
     * private function __construct() {}
     */

    /**
     * Singleton classes cannot be cloned!
     *
     * @return void
     */
    final private function __clone() {
    }

    /**
     * Singleton classes should not be put to sleep!
     *
     * @return void
     */
    final private function __sleep() {
    }
}
