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
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

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
 */
class TestHelper extends Opus_Application_Bootstrap {
    /**
     * Perform basic bootstrapping. Setup environment variables, load
     * configuration and initialize database connection.
     *
     * @return void
     */
    public static function init() {
        
        // For logging base path.
        self::$applicationRootDirectory = dirname(__FILE__);
        
        self::setupEnvironment();
        self::configure(self::CONFIG_TEST, dirname(__FILE__));
        self::setupDatabase();
        self::setupLogging();

        $registry = Zend_Registry::getInstance();
        $locale = new Zend_Locale();
        $availableLanguages = $locale->getLanguageTranslationList();
        asort($availableLanguages);
        $registry->set('Available_Languages', $availableLanguages);
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

    /**
     * Returns true if the underlying operating system is Microsoft Windows (TM).
     *
     * @return boolean True in case of MS Windows; False otherwise.
     */
    public static function isWindows() {
       return (substr(PHP_OS, 0, 3) === 'WIN');
    }

}

// Do test environment initializiation.
TestHelper::init();
