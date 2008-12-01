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
     * Return the actual database connection.
     * 
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $pdo = $dba->getConnection();
        $connection = $this->createDefaultDBConnection($pdo, NULL);
        return $connection;
    }

    /**
     * Returns test data to set up the Database before a test is started or after a test finished.
     * 
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet() {
        $dataset = $this->createFlatXMLDataSet(dirname(__FILE__) . '/AbstractDataSet.xml');
        return $dataset;
    }


    /**
     * Drop the Zend_Registry.
     * Prepare the Database.
     *
     * @return void
     */
    public function setUp() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        try {
            $dba->deleteTable('testtable');
        } catch (Exception $ex) {
            // noop
        }
        $dba->createTable('testtable');
        $dba->addField('testtable', array('name' => 'value', 'type' => 'varchar', 'length' => 23));

        // load table data
        parent::setUp();

        // Instantiate the Zend_Db_Table
        $this->dbProvider = new Opus_Model_AbstractTableProvider();
    }

    public function testCreateWithoutArgumentsThrowsException() {
        try {
            $obj = new Opus_Model_AbstractMock();
        } catch (Opus_Model_Exception $ex) {
            return;
        }
        $this->fail('It is possible to instantiate Opus_Model_Abstract without a table gateway.');
    }

    public function testValueAfterLoadById() {
        $obj = new Opus_Model_AbstractMock(1, $this->dbProvider);
        $this->assertTrue("foobar" === $obj->getvalue(), "Expected value to be 'foobar', got '". $obj->getvalue() ."'.\n");
    }

    public function testChangeOfValueAndStore() {
        $obj = new Opus_Model_AbstractMock(1, $this->dbProvider);
        $obj->setvalue('raboof');
        $obj->store();
        $expected = $this->createFlatXMLDataSet(dirname(__FILE__).'/AbstractDataSetAfterChangedValue.xml')->getTable('test_testtable');
        $result = $this->getConnection()->createDataSet()->getTable('test_testtable');
        $this->assertTablesEqual($expected, $result);
    }

}