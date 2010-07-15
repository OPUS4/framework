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
 * @package     Opus
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
    define('PHPUnit_MAIN_METHOD', 'Opus_AllTests::main');
}

// Use the TestHelper to setup Zend specific environment.
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Main test suite for grouping and executing all subsequent test suites.
 *
 * @category    Tests
 * @package     Opus
 */
class Opus_AllTests {

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
     * @return PHPUnit_Framework_TestSuite The suite.
     */
    public static function suite() {
        $suite = new PHPUnit_Framework_TestSuite('Opus Application Framework - Opus');
        $suite->addTest(Opus_Collection_AllTests::suite());
        $suite->addTest(Opus_Db_AllTests::suite());
        $suite->addTest(Opus_Document_AllTests::suite());
        $suite->addTest(Opus_Identifier_AllTests::suite());
        $suite->addTest(Opus_Licence_AllTests::suite());
        $suite->addTest(Opus_Mail_AllTests::suite());
        $suite->addTest(Opus_Model_AllTests::suite());
        $suite->addTest(Opus_Security_AllTests::suite());
        $suite->addTest(Opus_Statistic_AllTests::suite());
        $suite->addTest(Opus_Translate_AllTests::suite());
        $suite->addTest(Opus_Validate_AllTests::suite());

        $suite->addTestSuite('Opus_DocumentTest');
        $suite->addTestSuite('Opus_LicenceTest');
        $suite->addTestSuite('Opus_PersonTest');

        // FIXME: Moved Search_Test to end because it could cause segmentation faults.
        $suite->addTest(Opus_Search_AllTests::suite());

        return $suite;
    }

}

// Execute the test run if necessary.
if (PHPUnit_MAIN_METHOD === 'Opus_AllTests::main') {
    Opus_AllTests::main();
}
