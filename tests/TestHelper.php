<?php
/**
 * Defines global options and identifiers that are to be utilized in each
 * and every test case.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @category    Tests
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id$
 */


// Define global constants for test suite setup.
define('SQL_DROP_SCRIPT_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'drop.sql');
define('SQL_CREATE_SCRIPT_PATH', dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'db' . DIRECTORY_SEPARATOR . 'create.sql');

// Configure include path.
set_include_path('.' . PATH_SEPARATOR
            . PATH_SEPARATOR . dirname(__FILE__)
            . PATH_SEPARATOR . dirname(dirname(__FILE__)) . '/library'
            . PATH_SEPARATOR . get_include_path());

// Zend_Loader is'nt available yet. We have to do a require_once
// in order to find the bootstrap class.
require_once 'Opus/Application/Bootstrap.php';

/**
 * This class provides a static initializiation method for setting up
 * a test environment including php include path, configuration and
 * database setup.
 *
 * @category    Tests
 * @package     Opus_Application_Framework
 */
class TestHelper extends Opus_Application_Bootstrap {
    /**
     * Perform basic bootstrapping. Setup environment variables, load
     * configuration and initialize database connection.
     *
     * @return void
     */
    public static function init() {
        self::setupEnvironment();
        self::configure(self::CONFIG_TEST);
        self::setupDatabase();
    }

    /**
     * Use the standard database adapter to remove all records from
     * a table.
     *
     * @param string $tablename Name of the table to be cleared.
     * @return void
     */
    public static function clearTable($tablename) {
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $tablename = $adapter->quoteIdentifier($tablename);
        $adapter->query("DELETE FROM $tablename");
    }

    /**
     * Use standard database adapater to drop a table.
     *
     * @param string $tablename Name of the table to be dropped.
     * @return void
     */
    public static function dropTable($tablename) {
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $tablename = $adapter->quoteIdentifier($tablename);
        $adapter->query("DROP TABLE IF EXISTS $tablename ");
    }

}

// Do test environment initializiation.
TestHelper::init();
