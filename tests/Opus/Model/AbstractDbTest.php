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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Model_AbstractDb.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group AbstractDbTest
 */
class Opus_Model_AbstractDbTest extends PHPUnit_Extensions_Database_TestCase {

    /**
     * Instance of the concrete table model for Opus_Model_ModelAbstractDb.
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
        $connection = $this->createDefaultDBConnection($pdo, null);
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
        $dba->query('DROP TABLE IF EXISTS testtable');
        $dba->query('CREATE TABLE testtable (
            testtable_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            value        VARCHAR(255))');

        // load table data
        parent::setUp();

        // Instantiate the Zend_Db_Table
        $this->dbProvider = Opus_Db_TableGateway::getInstance('Opus_Model_AbstractTableProvider');
    }

    /**
     * Remove temporary table.
     *
     * @return void
     */
    public function tearDown() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->query('DROP TABLE IF EXISTS testtable');
    }

    /**
     * Test if an call to add...() throws an exception if the 'through' definition for
     * external fields holding models is invalid.
     *
     * @return void
     */
    public function testAddWithoutProperLinkModelClassThrowsException() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();
        $this->setExpectedException('Opus_Model_Exception');
        $mockup->addLazyExternalModel();
    }

    /**
     * Test if get on abstract model, defined as external field, throws an
     * exception.
     */
    public function testGetAbstractModelInExternalFieldThrowsException() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningAbstractExternalField();
        $this->setExpectedException('Opus_Model_Exception');
        $return = $mockup->getLazyAbstractModel();
    }

    /**
     * Test if setting a field containing a link model to null removes link
     * model.
     *
     * @return void
     */
    public function testSetLinkModelFieldToNullRemovesLinkModel() {
        $model = new Opus_Model_ModelDefiningExternalField;

        $abstractMock = new Opus_Model_ModelAbstractDbMock;
        $model->setExternalModel($abstractMock);
        $model->setExternalModel(null);
        $field = $model->getField('ExternalModel');

        $this->assertNull($field->getValue(), 'Link model field value is not null.');
    }

    /**
     * Test if a link model is the field value of an external field that uses
     * the 'through' option.
     *
     * @return void
     */
    public function testLinkModelIsFieldValueWhenUsingThroughOption() {
        $model = new Opus_Model_ModelDefiningExternalField();

        $abstractMock = new Opus_Model_ModelAbstractDbMock;
        $external = $model->setExternalModel($abstractMock);
        $field = $model->getField('ExternalModel');
        $fieldvalue = $field->getValue();
        $this->assertTrue($fieldvalue instanceof Opus_Model_Dependent_Link_Abstract, 'Field value is not a link model.');
    }

    /**
     * Test if a linkes model can be retrieved if the standard
     * get<Fieldname>() accessor is called on the containing model.
     *
     * @return void
     */
    public function testGetLinkedModelWhenQueryModel() {
        // construct mockup class
        $clazzez = '

        class testGetLinkedModelWhenQueryModel_Link
            extends Opus_Model_Dependent_Link_Abstract {
            protected $_modelClass = \'Opus_Model_ModelAbstractDbMock\';
            public function __construct() {}
            protected function _init() {}
            public function delete() {}
        }

        class testGetLinkedModelWhenQueryModel
            extends Opus_Model_AbstractDb {
                protected static $_tableGatewayClass = \'Opus_Model_AbstractTableProvider\';
                protected $_externalFields = array(
                    \'LinkField\' => array(
                        \'model\' => \'Opus_Model_ModelAbstractDbMock\',
                        \'through\' => \'testGetLinkedModelWhenQueryModel_Link\')
                );
                protected function _init() {
                    $this->addField(new Opus_Model_Field(\'LinkField\'));
                }
            }

        ';
        eval($clazzez);

        $mock = new testGetLinkedModelWhenQueryModel;
        $linkedModel = new Opus_Model_ModelAbstractDbMock();
        $mock->setLinkField($linkedModel);

        $this->assertInstanceOf('testGetLinkedModelWhenQueryModel_Link', $mock->getLinkField(), 'Returned linked model has wrong type.');
    }

    /**
     * Test if a linkes model can be retrieved if the standard
     * get<Fieldname>() accessor is called on the containing model.
     *
     * @return void
     */
    public function testGetMultipleLinkedModelWhenQueryModel() {
        // construct mockup class
        $clazzez = '

        class testGetMultipleLinkedModelWhenQueryModel_Link
            extends Opus_Model_Dependent_Link_Abstract {
            protected $_modelClass = \'Opus_Model_ModelAbstractDbMock\';
            public function __construct() {}
            protected function _init() {}
            public function delete() {}
        }

        class testGetMultipleLinkedModelWhenQueryModel
            extends Opus_Model_AbstractDb {
                protected static $_tableGatewayClass = \'Opus_Model_AbstractTableProvider\';
                protected $_externalFields = array(
                    \'LinkField\' => array(
                        \'model\' => \'Opus_Model_ModelAbstractDbMock\',
                        \'through\' => \'testGetMultipleLinkedModelWhenQueryModel_Link\')
                );
                protected function _init() {
                    $field = new Opus_Model_Field(\'LinkField\');
                    $field->setMultiplicity(2);
                    $this->addField($field);
                }
            }

        ';
        eval($clazzez);

        $mock = new testGetMultipleLinkedModelWhenQueryModel;
        $linkedModel = new Opus_Model_ModelAbstractDbMock();
        $mock->addLinkField($linkedModel);

        $this->assertTrue(is_array($mock->getLinkField()), 'Returned value is not an array.');
        $this->assertInstanceOf('testGetMultipleLinkedModelWhenQueryModel_Link', $mock->getLinkField(0), 'Returned linked model has wrong type.');
    }


    /**
     * Test if loading a model instance from the database devlivers the expected value.
     *
     * @param integer $testtable_id Id of dataset to load.
     * @param mixed   $value        Expected Value.
     * @return void
     *
     * @dataProvider abstractDataSetDataProvider
     */
    public function testValueAfterLoadById($testtable_id, $value) {
        $obj = new Opus_Model_ModelAbstractDb($testtable_id);
        $result = $obj->getValue();
        $this->assertEquals($value,$result, "Expected Value to be $value, got '" . $result . "'");
    }

    /**
     * Test if changing a models value and storing it is reflected in the database.
     *
     * @return void
     */
    public function testChangeOfValueAndStore() {
        $obj = new Opus_Model_ModelAbstractDb(1);
        $obj->setValue('raboof');
        $obj->store();
        $expected = $this->createFlatXMLDataSet(dirname(__FILE__) . '/AbstractDataSetAfterChangedValue.xml')->getTable('testtable');
        $result = $this->getConnection()->createDataSet()->getTable('testtable');
        $this->assertTablesEqual($expected, $result);
    }

    /**
     * Test if a call to store() does not happen when the Model has not been modified.
     *
     * @return void
     */
    public function testIfModelIsNotStoredWhenUnmodified() {
        // A record with id 1 is created by setUp() using AbstractDataSet.xml
        // So create a mocked Model to detect certain calls
        $mock = $this->getMock('Opus_Model_ModelAbstractDb',
            array('_storeInternalFields', '_storeExternalFields'),
            array(1));

        // Clear modified flag just to be sure
        $mock->getField('Value')->clearModified();

        // Expect getValue never to be called
        $mock->expects($this->never())->method('_storeInternalFields');
        $mock->expects($this->never())->method('_storeExternalFields');

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
        $mock = new Opus_Model_ModelAbstractDb(1);
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
        $clazz = '
            class testFieldsModifiedStatusGetsClearedAfterStore
                extends Opus_Model_AbstractDb {

                protected static $_tableGatewayClass = \'Opus_Model_AbstractTableProvider\';

                protected $_externalFields = array(
                    \'ExternalField1\' => array(),
                    \'ExternalField2\' => array(),
                );

                protected function _init() {
                    $this->addField(new Opus_Model_Field(\'Value\'));
                    $this->addField(new Opus_Model_Field(\'ExternalField1\'));
                    $this->addField(new Opus_Model_Field(\'ExternalField2\'));
                }

                public function getId() {
                    return 1;
                }

                public function _storeExternalField1() {}
                public function _storeExternalField2() {}
                public function _fetchExternalField1() {}
                public function _fetchExternalField2() {}

            }';
        eval($clazz);
        $mock = new testFieldsModifiedStatusGetsClearedAfterStore;
        $mock->setValue('foobar');
        $mock->setExternalField1('foo');
        $mock->setExternalField2('bar');
        $mock->store();

        $field = $mock->getField('Value');
        $this->assertFalse($field->isModified(), 'Field should not be marked as modified after storing to database.');
        $field = $mock->getField('ExternalField1');
        $this->assertFalse($field->isModified(), 'Field should not be marked as modified after storing to database.');
        $field = $mock->getField('ExternalField2');
        $this->assertFalse($field->isModified(), 'Field should not be marked as modified after storing to database.');
    }

    /**
     * Test if model deletion is reflected in database.
     *
     * @return void
     */
    public function testDeletion() {
        $obj = new Opus_Model_ModelAbstractDb(1);
        $preCount = $this->getConnection()->createDataSet()->getTable('testtable')->getRowCount();
        $obj->delete();
        $postCount = $this->getConnection()->createDataSet()->getTable('testtable')->getRowCount();
        $this->assertEquals($postCount, ($preCount - 1), 'Object persists allthough it was deleted.');
    }

    /**
     * Test if the default display name of a model is returned.
     *
     * @return void
     */
    public function testDefaultDisplayNameIsReturned() {
        $obj = new Opus_Model_ModelAbstractDb(1);
        $result = $obj->getDisplayName();
        $this->assertEquals('Opus_Model_ModelAbstractDb#1', $result, 'Default display name not properly formed.');
    }

    /**
     * Test if zero model entities would be retrieved by static getAll()
     * on an empty database.
     *
     * @return void
     */
    public function testGetAllEntitiesReturnsEmptyArrayOnEmtpyDatabase() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->query('TRUNCATE testtable');

        $result = Opus_Model_ModelAbstractDb::getAllFrom('Opus_Model_ModelAbstractDb', 'Opus_Model_AbstractTableProvider');
        $this->assertTrue(empty($result), 'Empty table should not deliver any objects.');
    }

    /**
     * Test if all model instances can be retrieved.
     *
     * @return void
     */
    public function testGetAllEntities() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->query('TRUNCATE testtable');

        $entities[0] = new Opus_Model_ModelAbstractDb(); $entities[0]->setValue('SatisfyValidator');
        $entities[1] = new Opus_Model_ModelAbstractDb(); $entities[1]->setValue('SatisfyValidator');
        $entities[2] = new Opus_Model_ModelAbstractDb(); $entities[2]->setValue('SatisfyValidator');

        foreach ($entities as $entity) {
            $entity->store();
        }

        $results = Opus_Model_ModelAbstractDb::getAllFrom('Opus_Model_ModelAbstractDb', 'Opus_Model_AbstractTableProvider');
        $this->assertEquals(count($entities), count($results), 'Incorrect number of instances delivered.');
        $this->assertEquals($entities[0]->toArray(), $results[0]->toArray(), 'Entities fetched differ from entities stored.');
        $this->assertEquals($entities[1]->toArray(), $results[1]->toArray(), 'Entities fetched differ from entities stored.');
        $this->assertEquals($entities[2]->toArray(), $results[2]->toArray(), 'Entities fetched differ from entities stored.');
    }

    /**
     * Test if the model of a field specified as lazy external is not loaded on
     * initialization.
     *
     * @return void
     */
    public function testLazyExternalModelIsNotLoadedOnInitialization() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        // Query the mock
        $this->assertNotContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn,
                'The lazy external field got loaded.');
    }

    /**
     * Test if the loading of an external model is not executed before
     * an explicit call to get...() when the external field's fetching
     * mode has been set to 'lazy'.
     *
     * @return void
     */
    public function testExternalModelLoadingIsSuspendedUntilGetCall() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        // Check that _loadExternal has not yet been called
        $this->assertNotContains('LazyExternalModel' ,$mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field does get loaded initially.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to getField().
     *
     * @return void
     */
    public function testExternalModelLoadingTiggeredByGetFieldCall() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        $field = $mockup->getField('LazyExternalModel');

        // Check that _loadExternal has not yet been called
        $this->assertContains('LazyExternalModel' ,$mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after getField().');
        $this->assertNotNull($field, 'No field object returned.');
    }

    /**
     * Test that lazy fetching does not happen more than once.
     *
     * @return void
     */
    public function testExternalModelLoadingByGetFieldCallHappensOnlyOnce() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        // First call to get.
        $field = $mockup->getField('LazyExternalModel');

        // Clear out mock up status
        $mockup->loadExternalHasBeenCalledOn = array();

        // Second call to get should not call _loadExternal again.
        $field = $mockup->getField('LazyExternalModel');


        // Check that _loadExternal has not yet been called
        $this->assertNotContains('LazyExternalModel' ,$mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is called more than once.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to get...().
     *
     * @return void
     */
    public function testExternalModelLoadingTiggeredByGetCall() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        $mockup->getLazyExternalModel();

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel' ,$mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after get call.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to set...().
     *
     * @return void
     */
    public function testExternalModelLoadingTiggeredBySetCall() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        $mockup->setLazyExternalModel(null);

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel' ,$mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after set call.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to add...().
     *
     * @return void
     */
    public function testExternalModelLoadingTiggeredByAddCall() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        try {
            $mockup->addLazyExternalModel();
        }
        catch (Opus_Model_Exception $ex) {
            // Expect exception because of missing link model class
            $noop = 42;
        }

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after add call.');
    }

    /**
     * Test if a call to toArray() triggers lazy fetching mechanism.
     *
     * @return void
     */
    public function testToArrayCallTriggersLazyFetching() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        $mockup->toArray();

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel' ,$mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after toArray() call.');
    }

    /**
     * Test if a call to toXml() triggers lazy fetching mechanism.
     *
     * @return void
     */
    public function testToXmlCallTriggersLazyFetching() {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new Opus_Model_ModelDefiningExternalField();

        $mockup->toXml();

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel' ,$mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after toXml() call.');
    }

    /**
     * Test if multiple calls to store do not change the record.
     *
     * @return void
     */
    public function testStoreIsIdempotend() {
        // Create persistent model
        $model = new Opus_Model_ModelAbstractDb;
        $model->setValue('Foo');
        $id1 = $model->store();

        // Retrieve stored model value from the database table
        $row = $this->dbProvider->find($id1)->current();
        $val1 = $row->value;

        // Trigger a new store
        $id2 = $model->store();

        // Check the value again
        $row = $this->dbProvider->find($id2)->current();
        $val2 = $row->value;

        $this->assertEquals($id1, $id2, 'Store function is not idempotend to identifiers.');
        $this->assertEquals($val1, $val2, 'Store function is not idempotend to values.');
    }

    /**
     * Test if an Exception is thrown is the model to be stored does not
     * validiate its data to be correct.
     *
     * @return void
     */
    public function testStoreThrowsExceptionIfModelHasInvalidData() {
        // Create persistent model
        $model = new Opus_Model_ModelAbstractDb;

        // Inject failing Validator
        $model->getField('Value')->setValidator(new Zend_Validate_Date());
        $model->setValue('InvalidDate');

        // trigger Exception
        $this->setExpectedException('Opus_Model_Exception');
        $id = $model->store();
    }

    /**
     * Test if modified flags of external fields get not cleared while
     * storing internal fields.
     *
     * @return void
     */
    public function testDontClearExternalFieldsModifiedFlagBeforeStoring() {
        // construct mockup class
        $clazz = '
            class testStoreClearsModifiedFlagOfInternalFieldsOnly
            extends Opus_Model_AbstractDb {

                protected static $_tableGatewayClass = \'Opus_Model_AbstractTableProvider\';

                protected $_externalFields = array(
                    \'ExternalField\' => array(
                        \'model\' => \'Opus_Model_ModelAbstractDbMock\')
                );

                protected function _init() {
                    $this->addField(new Opus_Model_Field(\'Value\'));
                    $this->addField(new Opus_Model_Field(\'ExternalField\'));
                }

            }';
        eval($clazz);

        // instanciate mockup
        $model = new testStoreClearsModifiedFlagOfInternalFieldsOnly;

        // mock external field
        $mockFieldExternalModel = $this->getMock('Opus_Model_Field',
            array('clearModified'), array('ExternalField'));
        $model->addField($mockFieldExternalModel);

        // clear and set modified flags respectivly
        $model->getField('ExternalField')->clearModified();
        $model->setValue('XYZ');

        // expect clearModified to be called only once on external field
        $mockFieldExternalModel->expects($this->once())
            ->method('clearModified');

        // trigger behavior
        $model->store();
    }

    /**
     * Test if a new model can be stored even is no modification happend to the instance.
     *
     * @return void
     */
    public function testNewlyCreatedModelCanBeStoredWhenNotModified() {
        $model = new Opus_Model_ModelAbstractDb;
        $id = $model->store();
        $this->assertNotNull($id, 'Expect newly created but unmodified model to be stored.');
    }

    /**
     * Test is isNewRecord() returns false after successful store.
     *
     * @return void
     */
    public function testIsNewRecordIsFalseAfterStore() {
        $model = new Opus_Model_ModelAbstractDb;
        $id = $model->store();
        $this->assertFalse($model->isNewRecord(), 'Expect stored model not to be marked as new record.');
    }


    /**
     * Test if a second call to store() directly after a successful store()
     * does not execute anything.
     *
     * @return void
     */
    public function testIfStoreTwiceAttemptDoesNotExecuteASecondStore() {
        $model = new Opus_Model_ModelAbstractDb;
        $id = $model->store();
        $model->postStoreHasBeenCalled = false;
        $id = $model->store();
        $this->assertFalse($model->postStoreHasBeenCalled, 'Second store issued on non modified model.');
    }

    /**
     * Test if loading a dependent model from database also sets the corresponding
     * parent id to these models.
     *
     * @return void
     */
    public function testParentIdGetPropagatedToDependentModelsOnLoading() {
        $clazz = '
            class testParentIdGetPropagatedToDependentModelsOnLoading
                extends Opus_Model_AbstractDb {
                protected static $_tableGatewayClass = \'Opus_Model_AbstractTableProvider\';
                protected $_externalFields = array(
                    \'ExternalField1\' => array(
                        \'model\' => \'Opus_Model_ModelDependentMock\'),
                );
                protected function _init() {
                    $this->addField(new Opus_Model_Field(\'Value\'));
                    $this->addField(new Opus_Model_Field(\'ExternalField1\'));
                }
                protected function _fetchExternalField1() {
                    return new Opus_Model_ModelDependentMock;
                }
            }';
        eval($clazz);
        $model = new testParentIdGetPropagatedToDependentModelsOnLoading(1);

        $this->assertTrue($model->getExternalField1()->setParentIdHasBeenCalled, 'No parent id was set on fetching dependent model.');
    }

    /**
     * Test if loading a dependent model from database also sets the corresponding
     * parent id to these models.
     *
     * @return void
     */
    public function testParentIdGetPropagatedToDependentModelsOnAddModel() {
        $clazz = '
            class testParentIdGetPropagatedToDependentModelsOnAdd
                extends Opus_Model_AbstractDb {
                protected static $_tableGatewayClass = \'Opus_Model_AbstractTableProvider\';
                protected $_externalFields = array(
                    \'ExternalField1\' => array(
                        \'model\' => \'Opus_Model_ModelDependentMock\'),
                );
                protected function _init() {
                    $this->addField(new Opus_Model_Field(\'Value\'));
                    $this->addField(new Opus_Model_Field(\'ExternalField1\'));
                }
                protected function _fetchExternalField1() {
                    return array();
                }
            }';
        eval($clazz);
        $model = new testParentIdGetPropagatedToDependentModelsOnAdd(1);
        $this->assertNotNull($model->getId());

        $externalField = $model->addExternalField1();
        $this->assertTrue($externalField->setParentIdHasBeenCalled,
                'No parent id was set on adding dependent model.');
    }

   /**
     * Provide names of plugin methods to be called with a given method.
     *
     * @return array Method names.
     */
    public function pluginCallnameProvider() {
        return array(
            array('store', 'preStore'),
            array('store', 'postStore'),
            array('store', 'postStoreInternal'),
            array('store', 'postStoreExternal'),
//            array('delete', 'preDelete'), // only called for stored objects
//            array('delete', 'postDelete'), // only called for stored objects
        );
    }

    /**
     * Test if an registered Plugin gets called.
     *
     * @return void
     *
     * @dataProvider pluginCallnameProvider
     */
    public function testRegisteredPluginGetsCalled($call, $expect) {
        // create mock plugin to register method calls
        $plugin = $this->getMock('Opus_Model_Plugin_Abstract');

        // define expectation
        $getsCalled = $plugin->expects($this->once())->method($expect);

        // create test model register plugin with it
        $model = new Opus_Model_ModelAbstractDb(null, null, array($plugin));

        // need to clone object because it gets altered by store/delete calls
        $getsCalled->with($model);

        // trigger plugin behavior
        $model->$call();
    }

    /**
     * Test if preFetch hook gets called.
     *
     * @return void
     */
    public function testRegisteredPluginPreFetchGetsCalledOnCreation() {
        // create mock plugin to register method calls
        $plugin = $this->getMock('Opus_Model_Plugin_Abstract');

        // define expectation
        $plugin->expects($this->once())
                ->method('preFetch');

        // create test model register plugin with it
        $model = new Opus_Model_ModelAbstractDb(null, null, array($plugin));
    }

    /**
     * Test if postDelete hook gets called.
     *
     * @return void
     */
    public function testRegisteredPluginPostDeleteGetsCalledOnCreation() {
        // create mock plugin to register method calls
        $plugin = $this->getMock('Opus_Model_Plugin_Abstract');

        // create persistent test model
        $model = new Opus_Model_ModelAbstractDb();
        $id = $model->store();
        $model = new Opus_Model_ModelAbstractDb($id);

        // define expectation
        $plugin->expects($this->once())
                ->method('postDelete')
                ->with($id);

        // trigger plugin behavior
        $model->registerPlugin($plugin);
        $model->delete();
    }

    /**
     * Test if a registered plugin can be unregistered by its class name.
     *
     * @return void
     */
    public function testUnregisterPluginByClassname() {
        $model = new Opus_Model_ModelAbstractDb;

        $plugin = $this->getMock('Opus_Model_Plugin_Abstract',
                        array('postStoreInternal'));
        $plugin->expects($this->never())
                ->method('postStoreInternal');

        $model->registerPlugin($plugin);
        $model->unregisterPlugin(get_class($plugin));

        $id = $model->store();
    }

    /**
     * Test if a registered plugin can be unregistered by instance.
     *
     * @return void
     */
    public function testUnregisterPluginByInstance() {
        $model = new Opus_Model_ModelAbstractDb;

        $plugin = $this->getMock('Opus_Model_Plugin_Abstract',
                        array('postStoreInternal'));
        $plugin->expects($this->never())
                ->method('postStoreInternal');

        $model->registerPlugin($plugin);
        $model->unregisterPlugin($plugin);

        $id = $model->store();
    }

    /**
     * Unregistering a plugin that does not exist should throw an exception.
     *
     * @return void
     */
    public function testUnregisteringPluginThatDoesNotExistShouldNotThrowException() {
        $model = new Opus_Model_ModelAbstractDb;

        $plugin = $this->getMock('Opus_Model_Plugin_Abstract',
                        array('postStoreInternal'));
        $plugin->expects($this->never())
                ->method('postStoreInternal');

        $model->registerPlugin($plugin);

        $model->unregisterPlugin('foobar');
    }


    /**
     * Test if the modified flag of a field is set to false if no field has changed.
     *
     * @return void
     */
    public function testModifiedFlagIsNotSetInitially() {
        $model = new Opus_Model_ModelAbstractDb;
        $result = $model->isModified();
        $this->assertFalse($result, 'Modified flag is initially true.');
    }

    /**
     * Test if modified flag can be triggered by changing a fields value.
     *
     * @return void
     */
    public function testModifiedFlagCanBeTriggerdCallToSetMethod() {
        $model = new Opus_Model_ModelAbstractDb;
        $model->getField('Value')->clearModified();
        $model->setValue('new value');
        $this->assertTrue($model->isModified(), 'Modified flag has not changed.');

    }

    /**
     * Test if modified flag can be triggered by changing  field values of
     * sub models.
     *
     * @return void
     */
    public function testModifiedFlagCanBeTriggerdByChangingSubmodel() {
        $model = new Opus_Model_ModelAbstractDb;
        $submodel = new Opus_Model_ModelAbstractDb;
        $field = new Opus_Model_Field('Submodel');
        $field->setValueModelClass(get_class($submodel));
        $field->setValue($submodel);
        $model->addField($field);
        $model->getField('Value')->clearModified();

        $model->getSubmodel()->setValue('new value');

        $this->assertTrue($model->getSubmodel()->isModified(), 'Modified flag has not changed for field.');
        $this->assertTrue($model->isModified(), 'Modified flag has not changed for model.');

    }

    /**
     * Test if the modified flag can be set back to false again.
     *
     * @return void
     */
    public function testModifiedFlagIsClearable() {
        $model = new Opus_Model_ModelAbstractDb;
        $model->setValue('new value');
        $model->getField('Value')->clearModified();
        $after = $model->isModified();
        $this->assertFalse($after, 'Modified flag has has not been cleared.');

    }

    /**
     * Test if updating a Model with its very own field values does not
     * affect the modification state of the Model.
     *
     * @return void
     */
    public function testUpdateFromModelWithSameValuesRendersModelUnmodified() {
        $clazzname = 'Opus_Model_AbstractTest_MockModel_3';
        $clazz = 'class ' . $clazzname . ' extends Opus_Model_AbstractDb {
            protected static $_tableGatewayClass = \'Opus_Model_AbstractTableProvider\';
            protected function _init() {
                $this->addField(new Opus_Model_Field("Value"));
            }
        }';
        if (false === class_exists($clazzname, false)) {
            eval($clazz);
        }

        // original model
        $m1 = new $clazzname;
        $m1->setValue('Foo');
        $m1->getField('Value')->clearModified();

        // update model of same type
        $m2 = new $clazzname;
        $m2->setValue('Foo');

        // do the update
        $m1->updateFrom($m2);

        // check values
        $this->assertFalse($m1->isModified(), 'Modification flag has been triggered.');

    }

    /**
     * Test if a models fields have their modified flag cleared after creation
     * of the model.
     *
     * @return void
     */
    public function testFieldsSetToUnmodifiedAfterInit() {
        $model = new Opus_Model_ModelAbstractDb;
        $field = $model->getField('Value');
        $this->assertFalse($field->isModified(), 'Modified flag has not been cleared.');

    }

    public function testValuesAreTrimmed()
    {
        $model = new Opus_Model_ModelAbstractDb();
        $model->setValue(' Test ');
        $modelId = $model->store();

        $model = new Opus_Model_ModelAbstractDb($modelId);
        $this->assertEquals('Test', $model->getValue());
    }

    /**
     * Test if a model retrieves its external fields in the right order
     */
    public function testFieldsInitializedInWrongOrder() {
        $model = new Opus_Model_CheckFieldOrderDummyClass();

        $this->assertEquals('bar', $model->getBefore());
        $this->assertEquals('baz', $model->getAfter());
    }

    public function testNameConversionMethods() {
        $fieldnamesToColumns = array(
            array('Type', 'type'),
            array('Role', 'role'),
            array('SortOrder', 'sort_order'),
            array('LeftId', 'left_id'),
        );

        foreach ($fieldnamesToColumns AS $pair) {
            $fieldname = $pair[0];
            $colname   = $pair[1];

            $this->assertEquals($colname,   Opus_Model_AbstractDb::convertFieldnameToColumn($fieldname));
            $this->assertEquals($fieldname, Opus_Model_AbstractDb::convertColumnToFieldname($colname));
        }

    }

}
