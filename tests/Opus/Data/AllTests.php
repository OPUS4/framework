<?php
/**
 * Defines the Opus Application Framework library test suite for the Opus_Data
 * subpackage.
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

// The phpunit testrunner defines the global PHPUnit_MAIN_METHOD to
// configure the method of test execution. When called via php directly
// PHPUnit_MAIN_METHOD is not defined and therefor gets defined to execute
// AllTests:main() to run the suite.
if ( defined('PHPUnit_MAIN_METHOD') === false ) {
    define('PHPUnit_MAIN_METHOD', 'Opus_Data_AllTests::main');
}

// Use the TestHelper to setup Zend specific environment.
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Main test suite for testing database access and models.
 *
 * @category    Tests
 * @package     Opus_Application_Framework
 * @subpackage  Data
 */
class Opus_Data_AllTests {

    /**
     * If the test class is called directly via php command the test
     * run gets startet in this method.
     *
     * @return void
     */
    public static function main() {
        PHPUnit_TextUI_TestRunner::run(self::suite());
    }

    /**
     * Construct and return the test suite.
     *
     * WARNING: <b>This will drop and recreate the whole database.</b>
     *
     * @return PHPUnit_Framework_TestSuite The suite.
     */
    public static function suite() {
        self::setupDatabase();

        $suite = new PHPUnit_Framework_TestSuite('Opus Application Framework - Opus_Data');
        $suite->addTest(Opus_Data_Model_AllTests::suite());
        return $suite;
    }

    /**
     * Load external drop and create scripts to setup a clean
     * database schema.
     *
     * @return void
     */
    protected static function setupDatabase() {
        $sql_drop = file_get_contents(SQL_DROP_SCRIPT_PATH);
        $sql_create = file_get_contents(SQL_CREATE_SCRIPT_PATH);

        $dba = Zend_Db_Table::getDefaultAdapter();
        self::executeSqlScript($dba, $sql_drop);
        self::executeSqlScript($dba, $sql_create);
    }

    /**
     * Separate single sql commands from a given script and
     * execute them in batch.
     *
     * @param Zend_Db_Adapter_Abstract $dba Database adapter to use.
     * @param string                   $sql Sql script.
     * @return void
     */
    protected static function executeSqlScript(Zend_Db_Adapter_Abstract $dba, $sql) {
        // strip comments
        $sql = preg_replace('/--.*/', '', $sql);
        // strip newline
        $sql = preg_replace('/(\n|\r)/', '', $sql);
        // tokenize commands and execute in batch
        $commands = explode(';',$sql);
        foreach ($commands as $command) {
            $command = trim($command);
            if (strlen($command) > 0) {
                $dba->query($command);
            }
        }
    }


}

// Execute the test run if necessary.
if (PHPUnit_MAIN_METHOD === 'Opus_Data_AllTests::main') {
    Opus_Data_AllTests::main();
}
