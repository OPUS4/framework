<?php
/**
 *
 */

// The phpunit testrunner defines the global PHPUnit_MAIN_METHOD to
// configure the method of test execution. When called via php directly
// PHPUnit_MAIN_METHOD is not defined and therefor gets defined to execute
// AllTests:main() to run the suite.
if ( defined('PHPUnit_MAIN_METHOD') === false ) {
    define('PHPUnit_MAIN_METHOD', 'Opus_Search_AllTests::main');
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
class Opus_Search_AllTests {

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
        $suite = new PHPUnit_Framework_TestSuite('Opus Application Framework - Opus_Search');
        return $suite;
    }

}

// Execute the test run if necessary.
if (PHPUnit_MAIN_METHOD === 'Opus_Search_AllTests::main') {
    Opus_Search_AllTests::main();
}
