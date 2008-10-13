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
 * @category    Tests
 * @package     Opus_Validate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

// The phpunit testrunner defines the global PHPUnit_MAIN_METHOD to
// configure the method of test execution. When called via php directly
// PHPUnit_MAIN_METHOD is not defined and therefor gets defined to execute
// AllTests:main() to run the suite.
if ( defined('PHPUnit_MAIN_METHOD') === false ) {
    define('PHPUnit_MAIN_METHOD', 'Opus_Validate_AllTests::main');
}

// Use the TestHelper to setup Zend specific environment.
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Main test suite for testing custom validators.
 *
 * @category    Tests
 * @package     Opus_Validate
 */
class Opus_Validate_AllTests {

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
        $suite = new PHPUnit_Framework_TestSuite('Opus Application Framework - Opus_Validate');
        $suite->addTestSuite('Opus_Validate_BooleanTest');
        $suite->addTestSuite('Opus_Validate_ComplexTypeTest');
        $suite->addTestSuite('Opus_Validate_DocumentTypeTest');
        $suite->addTestSuite('Opus_Validate_Isbn10Test');
        $suite->addTestSuite('Opus_Validate_Isbn13Test');
        $suite->addTestSuite('Opus_Validate_LocaleTest');
        $suite->addTestSuite('Opus_Validate_MateDecoratorTest');
        $suite->addTestSuite('Opus_Validate_NoteScopeTest');
        $suite->addTestSuite('Opus_Validate_ReviewTypeTest');
        return $suite;
    }

}

// Execute the test run if necessary.
if (PHPUnit_MAIN_METHOD === 'Opus_Validate_AllTests::main') {
    Opus_Validate_AllTests::main();
}
