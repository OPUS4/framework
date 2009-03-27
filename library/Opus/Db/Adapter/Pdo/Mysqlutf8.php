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
 * @package     Opus_Db
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Tobias Tappe (tobias.tappe@uni-bielefeld.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Extend standard PDO MySQL adapter to use UTF-8 strings by passing
 * 'SET NAMES uft8' via query. This adapter can be found by Zend_Db::factory()
 * as 'PDO_MYSQLUTF8' adapter.
 *
 * @category    Framework
 * @package     Opus_Db
 *
 */
class Opus_Db_Adapter_Pdo_Mysqlutf8 extends Zend_Db_Adapter_Pdo_Mysql implements Opus_Db_Adapter_AlterSchemaInterface
{
    /**
     * Contain table prefix
     *
     * @var string
     */
    protected $_tableprefix = 'test_';


    /**
     * Number of transaction start attempts.
     *
     * @var int
     */
    protected $_runningTransactions = 0;

    /**
     * Modifies standard connection behavior to use UTF-8.
     *
     * @return void
     */
    protected function _connect()
    {
        // if we already have a PDO object, no need to re-connect.
        if ( is_null($this->_connection) === false ) {
            return;
        }

        parent::_connect();

        // set connection to utf8
        $this->query('SET NAMES utf8');
    }



    /**
     * Override to implement transaction start counting.
     *
     * If a transaction is already running, no new one will be started.
     *
     * @return bool True
     */
    protected function _beginTransaction() {
        if ($this->_runningTransactions < 1) {
            parent::_beginTransaction();
        }
        $this->_runningTransactions++;
        return true;
    }

    /**
     * Decrease transaction counter and issue commit.
     *
     * @return bool True
     */
    protected function _commit() {
        if ($this->_runningTransactions < 2) {
            // Check for values < 2 to not mask errors on misuse of commit()
            parent::_commit();
        }
        $this->_runningTransactions--;
        return true;
    }

    /**
     * Decrease transaction counter and issue rollback.
     *
     * @return bool True
     */
    protected function _rollback() {
        if ($this->_runningTransactions < 2) {
            // Check for values < 2 to not mask errors on misuse of rollback()
            parent::_rollback();
        }
        $this->_runningTransactions--;
        return true;
    }



    /**
     * Validate a name
     *
     * @param string $name Contains the name for Validation
     * @return boolean
     */
    private static function isValidName($name) {
        $pattern = '/^[a-zA-Z0-9][a-zA-Z0-9_]*$/';
        if (preg_match($pattern, $name) === 0) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks for a valid table and optionally field name.
     * Returns false on invalid names or nonexisting tables / fields.
     *
     * @param string $tablename Contains table name
     * @param string $fieldname (Optional) Contains field name
     * @throws Exception Exception on empty table
     * @return boolean
     */
    public function isExistent($tablename, $fieldname = null) {
        if (self::isValidName($tablename) === false) {
            return false;
        }
        // table name is valid, add tableprefix
        $tablename = strtolower($this->_tableprefix . $tablename);
        // check for table inside database
        if (in_array($tablename, $this->listTables()) === false) {
            return false;
        }
        // is optional field name set
        if (empty($fieldname) === false) {
            if (self::isValidName($fieldname) === false) {
                return false;
            }
            // get informations about specific table
            $tableinfo = $this->describeTable(strtolower($tablename));
            if (empty($tableinfo) === true) {
                // this should never happen
                throw new Exception('Got empty table description.');
            }
            // is specific field in table
            $result = array_key_exists(strtolower($fieldname), $tableinfo);
            return $result;
        }

        return true;
    }

    /**
     * Set a new valid table prefix. A underline sign is added automaticly
     * if last char of a name is now underline.
     *
     * @param string $name Contains the name for table prefix
     * @return bool true on successfully changing table prefix
     */
    public function setTablePrefix($name)
    {
        // Unsetting the table prefix
        if (true === empty($name)) {
            $this->_tableprefix = '';
            return true;
        }
        // check for a valid table name
        if (self::isValidName($name) === true) {
            $this->_tableprefix = strtolower($name);
            if ($name[(strlen($name) - 1)] !== '_') {
                $this->_tableprefix .= '_';
            }
            return true;
        }
        return false;
    }

    /**
     * Create a table with the table name with _id added as primary key.
     *
     * @param string $name Contains the name for table and primary key
     * @throws Exception Exception at invalid name or already existing table
     * @return boolean true on success
     */
    public function createTable($name) {
        // check for a valid table name
        if (self::isValidName($name) === false) {
            throw new Exception('Used a invalid name as table name.');
        }
        // create name
        $name = $this->_tableprefix . strtolower($name);
        // build sql query
        $stmt = 'CREATE TABLE ' . $this->_quoteIdentifier($name)
              . ' ( ' . $this->_quoteIdentifier($name . '_id') . ' INT NOT NULL  AUTO_INCREMENT, '
              . ' PRIMARY KEY ( ' . $this->_quoteIdentifier($name . '_id') . ' ))';
        try {
            $this->query($stmt);
        } catch (Exception $e) {
            throw new Exception('Tried to create a already existing table! Error reason: ' . $e->getMessage());
        }
        // return true on success
        return true;
    }

    /**
     * Delete a table. Tableprefix is added automaticly
     *
     * @param string $name Contains the table name fro dropping
     * @throws Exception Exception on non valid name or non-existing table
     * @return bool true on success
     */
    public function deleteTable($name) {
        // check for a valid table name
        if (self::isValidName($name) === false) {
            throw new Exception('Non-valid name for a table.');
        }
        // build sql query
        $stmt = 'DROP TABLE ' . $this->_quoteIdentifier($this->_tableprefix . strtolower($name));
        try {
            $this->query($stmt);
        } catch (Exception $e) {
            throw new Exception('Tried to drop a non-existing table! Error reason: ' . $e->getMessage());
        }
        // return true on success
        return true;
    }

    /**
     * Empty a table.
     *
     * @param string $name Contains the table name for emptying
     * @throws Exception Exception on non valid name or non-existing table
     * @return bool true on success
     */
    public function truncateTable($name) {
        // check for a valid table name
        if (self::isValidName($name) === false) {
            throw new Exception('Non-valid name for a table.');
        }
        // build sql query
        $stmt = 'TRUNCATE TABLE ' . $this->_quoteIdentifier(strtolower($name));
        try {
            $this->query($stmt);
        } catch (Exception $e) {
            throw new Exception('Tried to empty a non-existing table! Error reason: ' . $e->getMessage());
        }
        // return true on success
        return true;
    }

    /**
     * Adds a field to a table
     *
     * Array(
     *     'name' => '...',
     *     'type' => ... ONLY types INT, VARCHAR, TEXT
     *     'length' => ... needed for VARCHAR, optional INT, should integer value
     *     'tableref' => 'table_name' ... TODO not implemented yet, should raise an exception if destination table doesn't contain a primary key
     * );
     *
     * @param string $table    Contains name of table
     * @param array  $fielddef Contains an array of elements
     * @throws Exception Exception on invalid data
     * @return boolean
     */
    public function addField($table, array $fielddef) {
        // check for a vaild table contains afterwards table name with table prefix!
        if ($this->isExistent($table) === false) {
            throw new Exception('Table \'' . $table . '\' doesn\'t exists.');
        }
        if (empty($fielddef) === true) {
            throw new Exception('No data transmitted.');
        }
        if (array_key_exists('name', $fielddef) === false) {
            throw new Exception('Field name missing.');
        }
        if ($this->isExistent($table, $fielddef['name']) === true) {
            throw new Exception('Table contain already a field with this name.');
        }
        if (array_key_exists('type', $fielddef) === false) {
            throw new Exception('Field type missing.');
        }
        // add table prefix
        $table = $this->_tableprefix . $table;
        // start creating sql statement
        $stmt = 'ALTER TABLE ' . $this->_quoteIdentifier($table)
              . ' ADD COLUMN ' . $this->_quoteIdentifier(strtolower($fielddef['name']));
        switch (strtoupper($fielddef['type'])) {
            case 'INT':
                // length defined?
                if (array_key_exists('length', $fielddef) === true) {
                    // length empty?
                    if (empty($fielddef['length']) === false) {
                        // check for integer value
                        if (is_int($fielddef['length']) === false) {
                            throw new Exception('Length value for INT must be an integer value.');
                        } else {
                            $stmt .= ' INT(' . $fielddef['length'] . ') ';
                        }
                    } else {
                        $stmt .= ' INT ';
                    }
                } else {
                    $stmt .= ' INT ';
                }
                break;

            case 'VARCHAR':
                // length must be defined
                if (array_key_exists('length', $fielddef) === false) {
                    throw new Exception('Field type VARCHAR needs length information.');
                }
                // empty value?
                if (empty($fielddef['length']) === true) {
                    throw new Exception('Empty value for length of field type VARCHAR.');
                }
                // length must be a integer value
                if (is_int($fielddef['length']) === false) {
                    throw new Exception('Length value for VARCHAR must be an integer value.');
                }
                // lenght should be between 0 and 255 chars long
                if (($fielddef['length'] < 0) or ($fielddef['length'] > 255)) {
                    throw new Exception('Length should be between 0 and 255 chars long.');
                }
                $stmt .= ' VARCHAR(' . $fielddef['length'] . ')';
                break;

            case 'TEXT':
                $stmt .= ' TEXT ';
                break;

            default:
                throw new Exception('Invalid field type transmitted. Only INT, VARCHAR and TEXT are supported.');
                break;
        }

        $stmt .= ';';
        try {
            $this->query($stmt);
        } catch (Exception $e) {
            throw new Exception('Error during adding a field. Error reason: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Delete a field from a table
     *
     * @param string $table Contains table name without prefix
     * @param string $name  Contains to removing field name
     * @throws Exception Exception on invalid names
     * @return boolean
     */
    public function removeField($table, $name) {
        // check for a vaild table contains afterwards table name with table prefix!
        if ($this->isExistent($table) === false) {
            throw new Exception('Table \'' . $table . '\' doesn\'t exists.');
        }
        // check for a valid field name
        if ($this->isExistent($table, $name) === false) {
            throw new Exception('Specific field \'' . $name . '\' not found in table.');
        }
        // add table prefix
        $table = $this->_tableprefix . $table;
        // get table informations
        $tableinfo = $this->describeTable($table);
        // check for primary key which shouldn't be removed
        if ($tableinfo[$name]['PRIMARY'] === true) {
            throw new Exception('Tried to remove a primary key from the table.');
        }
        // build sql query
        $stmt = 'ALTER TABLE ' . $this->_quoteIdentifier($table)
              . ' DROP COLUMN ' . $this->_quoteIdentifier($name);
        try {
            $this->query($stmt);
        } catch (Exception $e) {
            throw new Exception('Error during delete a field. Error reason: ' . $e->getMessage());
        }

        return true;
    }

    /**
     * Create database tables "collections_contents_X", "collections_replacement_X" and
     * "collections_structure_X" where X is the current roles_id.
     *
     * @param array(array) $content_fields (Optional) Array with collection_role database records.
     * @param int          $roles_id       (Optional) The database table postfix.
     * @throws  Exception On failed database access.
     * @return void
     */
    public function createCollectionDatabaseTables(array $content_fields = array(array(
                                              'name' => 'name',
                                              'type' => 'VARCHAR',
                                              'length' => 255
                                         )), $roles_id = null) {

        $tabellenname = 'link_documents_collections_' . $roles_id;
        $query = 'CREATE TABLE IF NOT EXISTS ' . $this->quoteIdentifier($tabellenname) . ' (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            `documents_id` INT( 11 ) UNSIGNED NOT NULL ,
            PRIMARY KEY ( `id` )
            ) ENGINE = InnoDB';

        try {
            $this->query($query);
        } catch (Exception $e) {
            throw new Exception('Error creating collection document linking table: ' . $e->getMessage());
        }


        $tabellenname = 'collections_contents_' . $roles_id;
        $query = 'CREATE TABLE IF NOT EXISTS ' . $this->quoteIdentifier($tabellenname) . ' (
            `id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
            PRIMARY KEY ( `id` )
            ) ENGINE = InnoDB';

        try {
            $this->query($query);
            $this->setTablePrefix('');
            foreach ($content_fields as $content_field) {
                $this->addField($tabellenname, $content_field);
            }
        } catch (Exception $e) {
            throw new Exception('Error creating collection content table: ' . $e->getMessage());
        }

        $tabellenname = 'collections_replacement_' . $roles_id;
        $query = 'CREATE  TABLE IF NOT EXISTS ' . $this->quoteIdentifier($tabellenname) . ' (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` INT UNSIGNED NOT NULL,
              `replacement_for_id` INT UNSIGNED,
              `replacement_by_id` INT UNSIGNED,
              `current_replacement_id` INT UNSIGNED,
              PRIMARY KEY (`id`) ,
              INDEX fk_link_collections_' . $roles_id . ' (`collections_id` ASC) ,
              INDEX fk_link_collections_replacement_for_' . $roles_id . ' (`replacement_for_id` ASC) ,
              INDEX fk_link_collections_replacement_by_' . $roles_id . ' (`replacement_by_id` ASC) ,
              INDEX fk_link_collections_current_replacement_' . $roles_id . ' (`current_replacement_id` ASC) ,
              CONSTRAINT `fk_link_collections_' . $roles_id . '`
                FOREIGN KEY (`collections_id` )
                REFERENCES `collections_contents_' . $roles_id . '` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_replacement_for_' . $roles_id . '`
                FOREIGN KEY (`replacement_for_id` )
                REFERENCES `collections_contents_' . $roles_id . '` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_replacement_by_' . $roles_id . '`
                FOREIGN KEY (`replacement_by_id` )
                REFERENCES `collections_contents_' . $roles_id . '` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_current_replacement_' . $roles_id . '`
                FOREIGN KEY (`current_replacement_id` )
                REFERENCES `collections_contents_' . $roles_id . '` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB';
        try {
            $this->query($query);
        } catch (Exception $e) {
            throw new Exception('Error creating collection replacement table: ' . $e->getMessage());
        }

        $tabellenname = 'collections_structure_' . $roles_id;
        $query = 'CREATE  TABLE IF NOT EXISTS ' . $this->quoteIdentifier($tabellenname) . ' (
              `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` int(10) UNSIGNED NOT NULL ,
              `left` int(10) UNSIGNED NOT NULL ,
              `right` int(10) UNSIGNED NOT NULL ,
              `visible` tinyint(1) NOT NULL default 1,
              PRIMARY KEY (`id`) ,
              INDEX fk_collections_structure_collections_contents_' . $roles_id . ' (`collections_id` ASC) ,
              CONSTRAINT `fk_collections_structure_collections_contents_' . $roles_id . '`
                FOREIGN KEY (`collections_id` )
                REFERENCES `collections_contents_' . $roles_id . '` (`id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB';
        try {
            $this->query($query);
        } catch (Exception $e) {
            throw new Exception('Error creating collection structure table: ' . $e->getMessage());
        }
    }

}
