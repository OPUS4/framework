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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Model_Abstract.
 *
 * @package Opus_Model
 * @category Tests
 * 
 * @group AbstractTest
 */
class Opus_Model_AbstractTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * Instance of the concrete table model for Opus_Model_AbstractMock.
     *
     * @var Opus_Model_AbstractTableProvider
     */
    protected $dbProvider = null;
    
    /**
     * Provides test data as stored in AbstractDataSet.xml.
     *
     * @return array Array containing arrays of id and value pairs.
     */
    public function abstractDataSetDataProvider() {
        return array(
            array(1, 'foobar'),
            array(3, 'foo'),
            array(4, 'bar'),
            array(5, 'bla'),
            array(8, 'blub')
        );
    }
    
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

    /**
     * Test if creating a model instance without passing a database model
     * fails with throwing an exception. 
     *
     * @return void
     */
    public function testCreateWithoutArgumentsThrowsException() {
        try {
            $obj = new Opus_Model_AbstractMock();
        } catch (Opus_Model_Exception $ex) {
            return;
        }
        $this->fail('It is possible to instantiate Opus_Model_Abstract without a table gateway.');
    }

    /**
     * Test if loading a model instance from the database devlivers the expected value. 
     *
     * @return void
     * 
     * @dataProvider abstractDataSetDataProvider
     */
    public function testValueAfterLoadById($test_testtable_id, $value) {
        $obj = new Opus_Model_AbstractMock($test_testtable_id, $this->dbProvider);
        $result = $obj->getValue();
        $this->assertEquals($value,$result, "Expected Value to be $value, got '" . $result . "'");
    }

    /**
     * Test if changing a models value and storing it is reflected in the database.
     *
     * @return void
     */
    public function testChangeOfValueAndStore() {
        $obj = new Opus_Model_AbstractMock(1, $this->dbProvider);
        $obj->setValue('raboof');
        $obj->store();
        $expected = $this->createFlatXMLDataSet(dirname(__FILE__).'/AbstractDataSetAfterChangedValue.xml')->getTable('test_testtable');
        $result = $this->getConnection()->createDataSet()->getTable('test_testtable');
        $this->assertTablesEqual($expected, $result);
    }
    
    
    /**
     * Test if describe() returns the fieldnames of all previosly added fields.
     *
     * @return void
     */
    public function testDescribeReturnsAllFields() {
        $mock = new Opus_Model_AbstractMock(null, $this->dbProvider);
        $mock->addField(new Opus_Model_Field('Field1'))
            ->addField(new Opus_Model_Field('Field2'));
        $fields = $mock->describe();
        $this->assertEquals(array('Value', 'Field1', 'Field2'), $fields, 'Wrong set of field names returned.');
    }
    

    /**
     * Test if no validator is assigned to a field when the there is no
     * Opus_Validate_<Fieldname> class.
     *
     * @return void
     */
    public function testNoDefaultValidatorForFields() {
        $mock = new Opus_Model_AbstractMock(null, $this->dbProvider);
        $mock->addField(new Opus_Model_Field('NoVal'));
        $field = $mock->getField('NoVal');
        $this->assertNull($field->getValidator(), 'No validator expected.');
    }
    
    /**
     * Test if custom validator instances can be added to fields.
     *
     * @return void
     */
    public function testAddingCustomValidators() {
        $mock = new Opus_Model_AbstractMock(null, $this->dbProvider);
        $field = $mock->getField('Value');
        $this->assertNotNull($field->getValidator(), 'Validator instance missing.');
        $this->assertType('Opus_Model_ValidateTest_Value', $field->getValidator(), 'Validator is of wrong type.');
    }
    
    /**
     * Test if no filter is assigned to a field when the there is no
     * Opus_Filter_<Fieldname> class.
     *
     * @return void
     */
    public function testNoDefaultFilterForFields() {
        $mock = new Opus_Model_AbstractMock(null, $this->dbProvider);
        $mock->addField(new Opus_Model_Field('NoFil'));
        $field = $mock->getField('NoFil');
        $this->assertNull($field->getFilter(), 'No filter expected.');
    }
    
    /**
     * Test if custom filter instances can be added to fields.
     *
     * @return void
     */
    public function testAddingCustomFilters() {
        $mock = new Opus_Model_AbstractMock(null, $this->dbProvider);
        $field = $mock->getField('Value');
        $this->assertNotNull($field->getFilter(), 'Filter instance missing.');
    }
    
    /**
     * Test if an added filter gets executed within it filter chain.
     *
     * @return void
     */
    public function testIfFilterIsExecuted() {
        $mock = new Opus_Model_AbstractMock(null, $this->dbProvider);
        $field = $mock->getField('Value');
        $filterChain = $field->getFilter();
        $result = $filterChain->filter('ABC');
        $this->assertEquals('abc', $result, 'Filter has propably not been executed.');
    }

    
    /**
     * Test if a call to store() does not happen when it has not been modified.
     *
     * @return void
     */
    public function testIfFieldIsNotStoredWhenUnmodified() {
        // A record with id 1 is created by setUp() using AbstractDataSet.xml
        $mock = new Opus_Model_AbstractMock(1, $this->dbProvider);
        $field = $mock->getField('Value');
        $oldval = $mock->getValue();
        
        // Override the original field "Value" with a mocked version
        // to detect calls to getValue()
        $fieldClassName = get_class($field);
        $mockField = $this->getMock($fieldClassName, array('getValue'), array('Value'));
        $mock->addField($mockField);

        // Clear modified flag just to be sure
        $mockField->clearModified();
        
        // Expect getValue not to be called
        $mockField->expects($this->never())->method('getValue');
        
        $mock->store();
    }

    /**
     * Test if fields get their modified status set back to false after beeing
     * filled with values from the database.
     *
     * @return void
     */
    public function testFieldsAreUnmodifiedWhenFreshFromDatabase() {
        // A record with id 1 is created by setUp() using AbstractDataSet.xml
        $mock = new Opus_Model_AbstractMock(1, $this->dbProvider);
        $field = $mock->getField('Value');
        $this->assertFalse($field->isModified(), 'Field should not be marked as modified when fetched from database.');
    }
    
    /**
     * Test if the modified status of fields gets cleared after the model
     * stored them.
     * 
     * @return void
     *
     */
    public function testFieldsModifiedStatusGetsClearedAfterStore() {
        // A record with id 1 is created by setUp() using AbstractDataSet.xml
        $mock = new Opus_Model_AbstractMock(1, $this->dbProvider);
        $mock->setValue('Change has come to America!');
        $mock->store();
        
        $field = $mock->getField('Value');
        $this->assertFalse($field->isModified(), 'Field should not be marked as modified after storing to database.');
    }
    
    
    /**
     * Test if a field can be marked as hidden thus it gets not reported by
     * describe().
     *
     * @return void
     */
    public function testFieldDescriptionHideable() {
        $model = new Opus_Model_ModelWithHiddenField(null, $this->dbProvider);
        $result = $model->describe();
        $this->assertNotContains('HiddenField', $result, 'Field "HiddenField" gets reported.');        
    }
    
}