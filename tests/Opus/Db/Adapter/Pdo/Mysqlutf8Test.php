<?php
/**
 * Test cases for Site entity.
 *
 * @package     Opus_Application_Framework
 * @subpackage  Db_Adapter_Pdo
 *
 * @group       Mysqlutf8Test
 */
class Opus_Db_Adapter_Pdo_Mysqlutf8Test extends PHPUnit_Framework_TestCase {

    /** Ensure a clean database table.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::dropTable('test_timmy');
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->setTablePrefix('test_');
    }

    /**
     * Test of creating a table
     *
     * @return void
     *
     */
    public function testCreateTable()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $this->assertEquals(true, $dba->createTable('timmy'));
    }

    /**
     * Test of creation an already existing Table
     *
     * @return void
     *
     */
    public function testCreateAlreadyExistingTable()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        try {
            $dba->createTable('timmy');
            $dba->createTable('timmy');
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of creation a table with an invalid name
     *
     * @return void
     *
     */
    public function testCreateTableWithInvalidName()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        try {
            $dba->createTable('timmäää');
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of dropping an table
     *
     * @return void
     *
     */
    public function testDropTable()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $this->assertEquals(true, $dba->deleteTable('timmy'));
    }

    /**
     * Test of dropping a non-existing table
     *
     * @return void
     *
     */
    public function testDropNonExistingTable()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        try {
            $dba->deleteTable('timmy');
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Try to drop a table with an invalid name.
     *
     * @return void
     */
    public function testDropTableWithInvalidName()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        try {
            $dba->deleteTable('timmäää');
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of setting table prefix to a new name
     *
     * @return void
     */
    public function testsetTablePrefix()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $this->assertEquals(true, $dba->setTablePrefix('timmy'));
    }

    /**
     * Test of setting table prefix to a new name. Name contains a underline
     * as last character sign.
     *
     * @return void
     */
    public function testsetTablePrefixWithEndingUnderline()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $this->assertEquals(true, $dba->setTablePrefix('timmy_'));
    }

    /**
     * Test of setting table prefix with an invalid name.
     *
     * @return void
     */
    public function testsetTablePrefixWithInvalidName()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $this->assertEquals(false, $dba->setTablePrefix('timmäää'));
    }

    /**
     * Test of adding a field without field defintion
     *
     * @return void
     */
    public function testAddFieldWithoutFielddefinition()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array();
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a field without field name
     *
     * @return void
     */
    public function testAddFieldWithoutFieldname()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('type' => 'INT');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a field with a already existing field name
     *
     * @return void
     */
    public function testAddFieldWithAlreadyExistingFieldname()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' =>'test1',  'type' => 'INT');
        $dba->addField('timmy', $fielddef);
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a field with an invalid field name
     *
     * @return void
     */
    public function testAddFieldWithInvalidFieldname()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'timmäää');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a field without a type definition
     *
     * @return void
     */
    public function testAddFieldWithoutType()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding an integer field
     *
     * @return void
     */
    public function testAddFieldTypeInt()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'INT');
        $this->assertEquals(true, $dba->addField('timmy', $fielddef));
    }

    /**
     * Test of adding an integer field with an invalid table name
     *
     * @return void
     */
    public function testAddFieldWithInvalidTableName()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $fielddef = array('name' => 'test1', 'type' => 'INT', 'length' => '');
        try {
                $dba->addField('timmäää', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding an integer field with nonexisting table
     *
     * @return void
     */
    public function testAddFieldWithoutExistingTable()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $fielddef = array('name' => 'test1', 'type' => 'INT', 'length' => '');
        try {
                $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding an integer field with empty length argument
     *
     * @return void
     */
    public function testAddFieldTypeIntWithEmptyLengthArgument()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'INT', 'length' => '');
        $this->assertEquals(true, $dba->addField('timmy', $fielddef));
    }

    /**
     * Test of adding an integer field with an length argument
     *
     * @return void
     */
    public function testAddFieldTypeIntWithLength()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'INT', 'length' => 5);
        $this->assertEquals(true, $dba->addField('timmy', $fielddef));
    }

    /**
     * Test of adding an integer field with an invalid length argument
     *
     * @return void
     */
    public function testAddFieldTypeIntWithInvalidLengthArgument()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'INT', 'length' => '5');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a VARCHAR field
     *
     * @return void
     */
    public function testAddFieldTypeVarChar()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'VARCHAR', 'length' => 5);
        $this->assertEquals(true, $dba->addField('timmy', $fielddef));
    }

    /**
     * Test of adding a VARCHAR field without length argument
     *
     * @return void
     */
    public function testAddFieldTypeVarCharWithoutLengthArgument()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'VARCHAR');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a VARCHAR field with empty length argument
     *
     * @return void
     */
    public function testAddFieldTypeVarCharWithEmptyLengthArgument()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'VARCHAR', 'length' => '');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a VARCHAR field with an invalid length argument
     *
     * @return void
     */
    public function testAddFieldTypeVarCharWithInvalidLengthArgument()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'VARCHAR', 'length' => '5');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of adding a TEXT field
     *
     * @return void
     */
    public function testAddFieldTypeText()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'TEXT');
        $this->assertEquals(true, $dba->addField('timmy', $fielddef));
    }

    /**
     * Test of adding a unknown field type
     *
     * @return void
     */
    public function testAddFieldTypeUnknown()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'BLUBB');
        try {
            $dba->addField('timmy', $fielddef);
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test if an exception is thrown when query execution failes.
     *
     * @return void
     */
    public function testAddFieldThrowExecutionException() {
        // Get the default adapter.
        $adapter = Zend_Db_Table::getDefaultAdapter();
        // Determine its real classname.
        $classname = get_class($adapter);

        // Retrieve database configuration.
        $config = Zend_Registry::get('Zend_Config');
        $dbconf = $config->db->params->toArray();

        // Go on with testing.
        $dba = $adapter;
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'INT');

        // Put a mockkup in place, mocking the query method.
        $dba = $this->getMock($classname, array('query'), array($dbconf));
        $dba->expects($this->once())
        ->method('query')
        ->will($this->throwException(new Exception('Failed!!!')));

        // This shall throw an exception.
        $this->setExpectedException('Exception');
        $dba->addField('timmy', $fielddef);
    }

    /**
     * Test of removing a field
     *
     * @return void
     */
    public function testRemoveField()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'INT');
        $dba->addField('timmy', $fielddef);
        $this->assertEquals(true, $dba->removeField('timmy', 'test1'));
    }

    /**
     * Test if an exception is thrown when query execution failes.
     *
     * @return void
     */
    public function testRemoveFieldThrowExecutionException() {
        // Get the default adapter.
        $adapter = Zend_Db_Table::getDefaultAdapter();
        // Determine its real classname.
        $classname = get_class($adapter);

        // Retrieve database configuration.
        $config = Zend_Registry::get('Zend_Config');
        $dbconf = $config->db->params->toArray();

        // Go on with testing.
        $dba = $adapter;
        $dba->createTable('timmy');
        $fielddef = array('name' => 'test1', 'type' => 'INT');
        $dba->addField('timmy', $fielddef);

        // Put a mockkup in place, mocking the query method.
        $dba = $this->getMock($classname, array('query'), array($dbconf));
        $dba->expects($this->once())
            ->method('query')
            ->will($this->throwException(new Exception('Failed!!!')));

        // This shall throw an exception.
        $this->setExpectedException('Exception');
        $dba->removeField('timmy', 'test1');
    }



    /**
     * Test of removing a non existing field
     *
     * @return  void
     */
    public function testRemoveNonexistingField()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        try {
            $dba->removeField('timmy', 'test1');
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }

    /**
     * Test of removing a field with primary key
     *
     * @return void
     */
    public function testRemovePrimaryField()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->createTable('timmy');
        try {
            $dba->removeField('timmy', 'test_timmy_id');
        } catch (Exception $e) {
            return;
        }
        $this->fail('An expected exception has not been raised.');
    }
}