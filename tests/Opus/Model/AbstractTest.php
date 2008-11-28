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
 * @package     Opus_Model
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

require_once dirname(__FILE__) . '/AbstractMock.php';
require_once dirname(__FILE__) . '/AbstractTableProvider.php';

/**
 * Test cases for class Opus_Model_Abstract.
 *
 * @package Opus_Model
 * @category Tests
 * @group AbstractTest
 */
class Opus_Model_AbstractTest extends PHPUnit_Extensions_Database_TestCase {

    protected $dbProvider;

    /**
     * PHPUnit_Extensions_Datbase requires this one.
     */
    protected function getConnection() {
        return $this->createDefaultDBConnection(Zend_Registry::get('db_adapter')->getConnection(), 'sqlite');
    }

    /**
     * PHPUnit_Extensions_Database use these informations to set up the Database before a test is started or after a test finished.
     */
    protected function getDataSet() {
        return $this->createFlatXMLDataSet(dirname(__FILE__).'/AbstractDataSet.xml');
    }


    /**
     * Drop the Zend_Registry.
     * Prepare the Database.
     *
     * @return void
     */
    public function setUp() {
        // unset registry
        Zend_Registry::_unsetInstance();

        // Create new Zend Config to setup the DB.
        $config = new Zend_Config(
            array(
                'db' => array(
                    'adapter' => 'Pdo_Sqlite',
                    'params' => array(
                        'dbname' => ':memory:',
                        'options' => array(Zend_Db::CASE_FOLDING => Zend_Db::CASE_LOWER)
                    )
                )
            ),
            true
        );

        // Save the Config.
        Zend_Registry::set('Zend_Config', $config);

        // Use zend_Db factory to create a database adapter
        // and make it the default for all tables.
        $db = Zend_Db::factory($config->db);
        Zend_Db_Table::setDefaultAdapter($db);
        // Register the adapter within Zend_Registry.
        Zend_Registry::getInstance()->set('db_adapter', $db);

        // Create the model in the db
        $db->getConnection()->exec(
            'CREATE TABLE testtable (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                value varchar(23)
            )'
        );

        // load table data
        parent::setUp();

        // Instantiate the Zend_Db_Table
        $this->dbProvider = new AbstractTableProvider();
    }

    public function testCreateWithoutArgumentsThrowsException() {
        try {
            $obj = new AbstractMock();
        } catch (Opus_Model_Exception $ex) {
            return;
        }
        $this->fail('It is possible to instantiate Opus_Model_Abstract without a table gateway.');
    }

    public function testValueAfterLoadById() {
        $obj = new AbstractMock(1, $this->dbProvider);
        $this->assertTrue("foobar" === $obj->getvalue(), "Expected value to be 'foobar', got '". $obj->getvalue() ."'.\n");
    }

    public function testChangeOfValueAndStore() {
        $obj = new AbstractMock(1, $this->dbProvider);
        $obj->setvalue('raboof');
        $obj->store();
        $xml_dataset = $this->createFlatXMLDataSet(dirname(__FILE__).'/AbstractDataSetAfterChangedValue.xml');
        $this->assertDataSetsEqual($xml_dataset, $this->getConnection()->createDataSet());
    }

}