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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model;

use Opus\Common\Model\ModelException;
use Opus\Common\Model\Plugin\AbstractPlugin;
use Opus\Db\TableGateway;
use Opus\Model\AbstractDb;
use Opus\Model\Dependent\Link\AbstractLinkModel;
use Opus\Model\Field;
use OpusTest\Model\Mock\AbstractDbMock;
use OpusTest\Model\Mock\AbstractTableProvider;
use OpusTest\Model\Mock\CheckFieldOrderDummyClass;
use OpusTest\Model\Mock\ModelAbstractDbMock;
use OpusTest\Model\Mock\ModelDefiningAbstractExternalField;
use OpusTest\Model\Mock\ModelDefiningExternalField;
use PHPUnit\Framework\TestCase;
use Zend_Db_Table;
use Zend_Validate_Date;

use function class_exists;
use function count;
use function get_class;
use function is_array;

/**
 * Test cases for class Opus\Model\AbstractDb.
 *
 * @package Opus\Model
 * @category Tests
 * @group AbstractDbTest
 * phpcs:disable
 */
class AbstractDbTest extends TestCase
{
    /**
     * Instance of the concrete table model for OpusTest\Model\Mock\AbstractDbMock.
     *
     * @var AbstractTableProvider
     */
    protected $dbProvider;

    /**
     * Provides test data.
     *
     * @return array Array containing arrays of id and value pairs.
     */
    public static function abstractDataSetDataProvider()
    {
        return [
            [1, 'foobar'],
            [3, 'foo'],
            [4, 'bar'],
            [5, 'bla'],
            [8, 'blub'],
        ];
    }

    /**
     * Prepare the Database.
     */
    public function setUp(): void
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->query('DROP TABLE IF EXISTS testtable');
        $dba->query('CREATE TABLE testtable (
            testtable_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            value        VARCHAR(255))');

        // load table data
        foreach (self::abstractDataSetDataProvider() as $row) {
            $dba->query("INSERT INTO testtable (testtable_id, value) VALUES ({$row[0]}, \"{$row[1]}\")");
        }

        parent::setUp();

        // Instantiate the\Zend_Db_Table
        $this->dbProvider = TableGateway::getInstance(AbstractTableProvider::class);
    }

    /**
     * Remove temporary table.
     */
    public function tearDown(): void
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->query('DROP TABLE IF EXISTS testtable');
    }

    /**
     * Test if an call to add...() throws an exception if the 'through' definition for
     * external fields holding models is invalid.
     */
    public function testAddWithoutProperLinkModelClassThrowsException()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();
        $this->expectException(ModelException::class);
        $mockup->addLazyExternalModel();
    }

    /**
     * Test if get on abstract model, defined as external field, throws an
     * exception.
     */
    public function testGetAbstractModelInExternalFieldThrowsException()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningAbstractExternalField();
        $this->expectException(ModelException::class);
        $return = $mockup->getLazyAbstractModel();
    }

    /**
     * Test if setting a field containing a link model to null removes link
     * model.
     */
    public function testSetLinkModelFieldToNullRemovesLinkModel()
    {
        $model = new ModelDefiningExternalField();

        $abstractMock = new ModelAbstractDbMock();
        $model->setExternalModel($abstractMock);
        $model->setExternalModel(null);
        $field = $model->getField('ExternalModel');

        $this->assertNull($field->getValue(), 'Link model field value is not null.');
    }

    /**
     * Test if a link model is the field value of an external field that uses
     * the 'through' option.
     */
    public function testLinkModelIsFieldValueWhenUsingThroughOption()
    {
        $model = new ModelDefiningExternalField();

        $abstractMock = new ModelAbstractDbMock();
        $external     = $model->setExternalModel($abstractMock);
        $field        = $model->getField('ExternalModel');
        $fieldvalue   = $field->getValue();
        $this->assertTrue($fieldvalue instanceof AbstractLinkModel, 'Field value is not a link model.');
    }

    /**
     * Test if a linkes model can be retrieved if the standard
     * get<Fieldname>() accessor is called on the containing model.
     */
    public function testGetLinkedModelWhenQueryModel()
    {
        // construct mockup class
        eval('
            class testGetLinkedModelWhenQueryModel_Link extends \Opus\Model\Dependent\Link\AbstractLinkModel {
                protected $modelClass = \'OpusTest\Model\Mock\ModelAbstractDbMock\';
                public function __construct() {}
                protected function init() {}
                public function delete() {}
            }
        
            class testGetLinkedModelWhenQueryModel extends \Opus\Model\AbstractDb {
                    protected static $tableGatewayClass = \'OpusTest\Model\Mock\AbstractTableProvider\';
                    protected $externalFields = [
                        \'LinkField\' => [
                            \'model\' => \'OpusTest\Model\Mock\ModelAbstractDbMock\',
                            \'through\' => \'testGetLinkedModelWhenQueryModel_Link\'
                        ]
                    ];
                    protected function init() {
                        $this->addField(new \Opus\Model\Field(\'LinkField\'));
                    }
                }
        
        ');

        $mock        = new \testGetLinkedModelWhenQueryModel();
        $linkedModel = new ModelAbstractDbMock();
        $mock->setLinkField($linkedModel);

        $this->assertInstanceOf('testGetLinkedModelWhenQueryModel_Link', $mock->getLinkField(), 'Returned linked model has wrong type.');
    }

    /**
     * Test if a linkes model can be retrieved if the standard
     * get<Fieldname>() accessor is called on the containing model.
     */
    public function testGetMultipleLinkedModelWhenQueryModel()
    {
        // construct mockup class
        eval('
            class testGetMultipleLinkedModelWhenQueryModel_Link
                extends \Opus\Model\Dependent\Link\AbstractLinkModel {
                protected $modelClass = \'OpusTest\Model\Mock\ModelAbstractDbMock\';
                public function __construct() {}
                protected function init() {}
                public function delete() {}
            }
    
            class testGetMultipleLinkedModelWhenQueryModel
                extends \Opus\Model\AbstractDb {
                    protected static $tableGatewayClass = \'OpusTest\Model\Mock\AbstractTableProvider\';
                    protected $externalFields = [
                        \'LinkField\' => [
                            \'model\' => \'OpusTest\Model\Mock\ModelAbstractDbMock\',
                            \'through\' => \'testGetMultipleLinkedModelWhenQueryModel_Link\'
                        ]
                    ];
                    protected function init() {
                        $field = new \Opus\Model\Field(\'LinkField\');
                        $field->setMultiplicity(2);
                        $this->addField($field);
                    }
                }
        ');

        $mock        = new \testGetMultipleLinkedModelWhenQueryModel();
        $linkedModel = new ModelAbstractDbMock();
        $mock->addLinkField($linkedModel);

        $this->assertTrue(is_array($mock->getLinkField()), 'Returned value is not an array.');
        $this->assertInstanceOf('testGetMultipleLinkedModelWhenQueryModel_Link', $mock->getLinkField(0), 'Returned linked model has wrong type.');
    }

    /**
     * Test if loading a model instance from the database devlivers the expected value.
     *
     * @param int   $testtableId Id of dataset to load.
     * @param mixed $value        Expected Value.
     * @dataProvider abstractDataSetDataProvider
     */
    public function testValueAfterLoadById($testtableId, $value)
    {
        $obj    = new AbstractDbMock($testtableId);
        $result = $obj->getValue();
        $this->assertEquals($value, $result, "Expected Value to be $value, got '" . $result . "'");
    }

    /**
     * Test if changing a models value and storing it is reflected in the database.
     */
    public function testChangeOfValueAndStore()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $obj = new AbstractDbMock(1);
        $obj->setValue('raboof');
        $obj->store();

        $expected = [
            ['testtable_id' => 1, 'value' => 'raboof'],
            ['testtable_id' => 3, 'value' => 'foo'],
            ['testtable_id' => 4, 'value' => 'bar'],
            ['testtable_id' => 5, 'value' => 'bla'],
            ['testtable_id' => 8, 'value' => 'blub']
        ];

        $result = $dba->fetchAll('SELECT * FROM testtable');
        $this->assertEquals($expected, $result);
    }

    /**
     * Test if a call to store() does not happen when the Model has not been modified.
     */
    public function testIfModelIsNotStoredWhenUnmodified()
    {
        // A record with id 1 is created by setUp()
        // So create a mocked Model to detect certain calls
        $mock = $this->getMockBuilder(AbstractDbMock::class)
            ->onlyMethods(['_storeInternalFields', '_storeExternalFields'])
            ->setConstructorArgs([1])
            ->getMock();

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
     */
    public function testFieldsAreUnmodifiedWhenFreshFromDatabase()
    {
        // A record with id 1 is created by setUp() using AbstractDataSet.xml
        $mock  = new AbstractDbMock(1);
        $field = $mock->getField('Value');
        $this->assertFalse($field->isModified(), 'Field should not be marked as modified when fetched from database.');
    }

    /**
     * Test if the modified status of fields gets cleared after the model
     * stored them.
     */
    public function testFieldsModifiedStatusGetsClearedAfterStore()
    {
        eval('
            class testFieldsModifiedStatusGetsClearedAfterStore
                extends \Opus\Model\AbstractDb {

                protected static $tableGatewayClass = \'OpusTest\Model\Mock\AbstractTableProvider\';

                protected $externalFields = array(
                    \'ExternalField1\' => array(),
                    \'ExternalField2\' => array(),
                );

                protected function init() {
                    $this->addField(new \Opus\Model\Field(\'Value\'));
                    $this->addField(new \Opus\Model\Field(\'ExternalField1\'));
                    $this->addField(new \Opus\Model\Field(\'ExternalField2\'));
                }

                public function getId() {
                    return 1;
                }

                public function _storeExternalField1() {}
                public function _storeExternalField2() {}
                public function _fetchExternalField1() {}
                public function _fetchExternalField2() {}

            }
        ');

        $mock = new \testFieldsModifiedStatusGetsClearedAfterStore();
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
     */
    public function testDeletion()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $obj      = new AbstractDbMock(1);
        $preCount = $dba->fetchOne('SELECT count(*) FROM testtable');
        $obj->delete();
        $postCount = $dba->fetchOne('SELECT count(*) FROM testtable');
        $this->assertEquals($postCount, $preCount - 1, 'Object persists allthough it was deleted.');
    }

    /**
     * Test if the default display name of a model is returned.
     */
    public function testDefaultDisplayNameIsReturned()
    {
        $obj    = new AbstractDbMock(1);
        $result = $obj->getDisplayName();
        $this->assertEquals('OpusTest\Model\Mock\AbstractDbMock#1', $result, 'Default display name not properly formed.');
    }

    /**
     * Test if zero model entities would be retrieved by static getAll()
     * on an empty database.
     */
    public function testGetAllEntitiesReturnsEmptyArrayOnEmtpyDatabase()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->query('TRUNCATE testtable');

        $result = AbstractDbMock::getAllFrom(AbstractDbMock::class, AbstractTableProvider::class);
        $this->assertTrue(empty($result), 'Empty table should not deliver any objects.');
    }

    /**
     * Test if all model instances can be retrieved.
     */
    public function testGetAllEntities()
    {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->query('TRUNCATE testtable');

        $entities[0] = new AbstractDbMock();
        $entities[0]->setValue('SatisfyValidator');
        $entities[1] = new AbstractDbMock();
        $entities[1]->setValue('SatisfyValidator');
        $entities[2] = new AbstractDbMock();
        $entities[2]->setValue('SatisfyValidator');

        foreach ($entities as $entity) {
            $entity->store();
        }

        $results = AbstractDbMock::getAllFrom(AbstractDbMock::class, AbstractTableProvider::class);
        $this->assertEquals(count($entities), count($results), 'Incorrect number of instances delivered.');
        $this->assertEquals($entities[0]->toArray(), $results[0]->toArray(), 'Entities fetched differ from entities stored.');
        $this->assertEquals($entities[1]->toArray(), $results[1]->toArray(), 'Entities fetched differ from entities stored.');
        $this->assertEquals($entities[2]->toArray(), $results[2]->toArray(), 'Entities fetched differ from entities stored.');
    }

    /**
     * Test if the model of a field specified as lazy external is not loaded on
     * initialization.
     */
    public function testLazyExternalModelIsNotLoadedOnInitialization()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        // Query the mock
        $this->assertNotContains(
            'LazyExternalModel',
            $mockup->loadExternalHasBeenCalledOn,
            'The lazy external field got loaded.'
        );
    }

    /**
     * Test if the loading of an external model is not executed before
     * an explicit call to get...() when the external field's fetching
     * mode has been set to 'lazy'.
     */
    public function testExternalModelLoadingIsSuspendedUntilGetCall()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        // Check that _loadExternal has not yet been called
        $this->assertNotContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field does get loaded initially.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to getField().
     */
    public function testExternalModelLoadingTiggeredByGetFieldCall()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        $field = $mockup->getField('LazyExternalModel');

        // Check that _loadExternal has not yet been called
        $this->assertContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after getField().');
        $this->assertNotNull($field, 'No field object returned.');
    }

    /**
     * Test that lazy fetching does not happen more than once.
     */
    public function testExternalModelLoadingByGetFieldCallHappensOnlyOnce()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        // First call to get.
        $field = $mockup->getField('LazyExternalModel');

        // Clear out mock up status
        $mockup->loadExternalHasBeenCalledOn = [];

        // Second call to get should not call _loadExternal again.
        $field = $mockup->getField('LazyExternalModel');

        // Check that _loadExternal has not yet been called
        $this->assertNotContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is called more than once.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to get...().
     */
    public function testExternalModelLoadingTiggeredByGetCall()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        $mockup->getLazyExternalModel();

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after get call.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to set...().
     */
    public function testExternalModelLoadingTiggeredBySetCall()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        $mockup->setLazyExternalModel(null);

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after set call.');
    }

    /**
     * Test if suspended loading of external models gets triggered by
     * a call to add...().
     */
    public function testExternalModelLoadingTiggeredByAddCall()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        try {
            $mockup->addLazyExternalModel();
        } catch (ModelException $ex) {
            // Expect exception because of missing link model class
            $noop = 42;
        }

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after add call.');
    }

    /**
     * Test if a call to toArray() triggers lazy fetching mechanism.
     */
    public function testToArrayCallTriggersLazyFetching()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        $mockup->toArray();

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after toArray() call.');
    }

    /**
     * Test if a call to toXml() triggers lazy fetching mechanism.
     */
    public function testToXmlCallTriggersLazyFetching()
    {
        // Build a mockup to observe calls to _loadExternal
        $mockup = new ModelDefiningExternalField();

        $mockup->toXml();

        // Check that _loadExternal has been called
        $this->assertContains('LazyExternalModel', $mockup->loadExternalHasBeenCalledOn, 'The "lazy fetch" external field is not loaded after toXml() call.');
    }

    /**
     * Test if multiple calls to store do not change the record.
     */
    public function testStoreIsIdempotend()
    {
        // Create persistent model
        $model = new AbstractDbMock();
        $model->setValue('Foo');
        $id1 = $model->store();

        // Retrieve stored model value from the database table
        $row  = $this->dbProvider->find($id1)->current();
        $val1 = $row->value;

        // Trigger a new store
        $id2 = $model->store();

        // Check the value again
        $row  = $this->dbProvider->find($id2)->current();
        $val2 = $row->value;

        $this->assertEquals($id1, $id2, 'Store function is not idempotend to identifiers.');
        $this->assertEquals($val1, $val2, 'Store function is not idempotend to values.');
    }

    /**
     * Test if an Exception is thrown is the model to be stored does not
     * validiate its data to be correct.
     */
    public function testStoreThrowsExceptionIfModelHasInvalidData()
    {
        // Create persistent model
        $model = new AbstractDbMock();

        // Inject failing Validator
        $model->getField('Value')->setValidator(new Zend_Validate_Date());
        $model->setValue('InvalidDate');

        // trigger Exception
        $this->expectException(ModelException::class);
        $id = $model->store();
    }

    /**
     * Test if modified flags of external fields get not cleared while
     * storing internal fields.
     */
    public function testDontClearExternalFieldsModifiedFlagBeforeStoring()
    {
        // construct mockup class
        eval('
            class testStoreClearsModifiedFlagOfInternalFieldsOnly extends \Opus\Model\AbstractDb 
            {

                protected static $tableGatewayClass = \'OpusTest\Model\Mock\AbstractTableProvider\';

                protected $externalFields = array(
                    \'ExternalField\' => array(
                        \'model\' => \'OpusTest\Model\Mock\AbstractDbMock\')
                );

                protected function init() {
                    $this->addField(new \Opus\Model\Field(\'Value\'));
                    $this->addField(new \Opus\Model\Field(\'ExternalField\'));
                }

            }
        ');

        // instanciate mockup
        $model = new \testStoreClearsModifiedFlagOfInternalFieldsOnly();

        // mock external field
        $mockFieldExternalModel = $this->getMockBuilder(Field::class)
            ->onlyMethods(['clearModified'])
            ->setConstructorArgs(['ExternalField'])
            ->getMock();

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
     */
    public function testNewlyCreatedModelCanBeStoredWhenNotModified()
    {
        $model = new AbstractDbMock();
        $id    = $model->store();
        $this->assertNotNull($id, 'Expect newly created but unmodified model to be stored.');
    }

    /**
     * Test is isNewRecord() returns false after successful store.
     */
    public function testIsNewRecordIsFalseAfterStore()
    {
        $model = new AbstractDbMock();
        $id    = $model->store();
        $this->assertFalse($model->isNewRecord(), 'Expect stored model not to be marked as new record.');
    }

    /**
     * Test if a second call to store() directly after a successful store()
     * does not execute anything.
     */
    public function testIfStoreTwiceAttemptDoesNotExecuteASecondStore()
    {
        $model                         = new AbstractDbMock();
        $id                            = $model->store();
        $model->postStoreHasBeenCalled = false;
        $id                            = $model->store();
        $this->assertFalse($model->postStoreHasBeenCalled, 'Second store issued on non modified model.');
    }

    /**
     * Test if loading a dependent model from database also sets the corresponding
     * parent id to these models.
     */
    public function testParentIdGetPropagatedToDependentModelsOnLoading()
    {
        eval('
            class testParentIdGetPropagatedToDependentModelsOnLoading extends \Opus\Model\AbstractDb {
                protected static $tableGatewayClass = \'\OpusTest\Model\Mock\AbstractTableProvider\';
                protected $externalFields = array(
                    \'ExternalField1\' => array(
                        \'model\' => \'OpusTest\Model\Mock\ModelDependentMock\'),
                );
                protected function init() {
                    $this->addField(new \Opus\Model\Field(\'Value\'));
                    $this->addField(new \Opus\Model\Field(\'ExternalField1\'));
                }
                protected function _fetchExternalField1() {
                    return new OpusTest\Model\Mock\ModelDependentMock();
                }
            }
        ');

        $model = new \testParentIdGetPropagatedToDependentModelsOnLoading(1);

        $this->assertTrue($model->getExternalField1()->setParentIdHasBeenCalled, 'No parent id was set on fetching dependent model.');
    }

    /**
     * Test if loading a dependent model from database also sets the corresponding
     * parent id to these models.
     */
    public function testParentIdGetPropagatedToDependentModelsOnAddModel()
    {
        eval('
            class testParentIdGetPropagatedToDependentModelsOnAdd extends \Opus\Model\AbstractDb {
                protected static $tableGatewayClass = \'OpusTest\Model\Mock\AbstractTableProvider\';
                protected $externalFields = array(
                    \'ExternalField1\' => array(
                        \'model\' => \'OpusTest\Model\Mock\ModelDependentMock\'),
                );
                protected function init() {
                    $this->addField(new \Opus\Model\Field(\'Value\'));
                    $this->addField(new \Opus\Model\Field(\'ExternalField1\'));
                }
                protected function _fetchExternalField1() {
                    return array();
                }
            }
        ');

        $model = new \testParentIdGetPropagatedToDependentModelsOnAdd(1);

        $this->assertNotNull($model->getId());

        $externalField = $model->addExternalField1();
        $this->assertTrue(
            $externalField->setParentIdHasBeenCalled,
            'No parent id was set on adding dependent model.'
        );
    }

   /**
    * Provide names of plugin methods to be called with a given method.
    *
    * @return array Method names.
    */
    public static function pluginCallnameProvider()
    {
        return [
            ['store', 'preStore'],
            ['store', 'postStore'],
            ['store', 'postStoreInternal'],
            ['store', 'postStoreExternal'],
//            array('delete', 'preDelete'), // only called for stored objects
//            array('delete', 'postDelete'), // only called for stored objects
        ];
    }

    /**
     * Test if an registered Plugin gets called.
     *
     * @param string $call
     * @param mixed  $expect
     * @dataProvider pluginCallnameProvider
     */
    public function testRegisteredPluginGetsCalled($call, $expect)
    {
        // create mock plugin to register method calls
        $plugin = $this->getMockBuilder(AbstractPlugin::class)->getMock();

        // define expectation
        $getsCalled = $plugin->expects($this->once())->method($expect);

        // create test model register plugin with it
        $model = new AbstractDbMock(null, null, [$plugin]);

        // need to clone object because it gets altered by store/delete calls
        $getsCalled->with($model);

        // trigger plugin behavior
        $model->$call();
    }

    /**
     * Test if preFetch hook gets called.
     */
    public function testRegisteredPluginPreFetchGetsCalledOnCreation()
    {
        // create mock plugin to register method calls
        $plugin = $this->getMockBuilder(AbstractPlugin::class)->getMock();

        // define expectation
        $plugin->expects($this->once())
                ->method('preFetch');

        // create test model register plugin with it
        $model = new AbstractDbMock(null, null, [$plugin]);
    }

    /**
     * Test if postDelete hook gets called.
     */
    public function testRegisteredPluginPostDeleteGetsCalledOnCreation()
    {
        // create mock plugin to register method calls
        $plugin = $this->getMockBuilder(AbstractPlugin::class)->getMock();

        // create persistent test model
        $model = new AbstractDbMock();
        $id    = $model->store();
        $model = new AbstractDbMock($id);

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
     */
    public function testUnregisterPluginByClassname()
    {
        $model = new AbstractDbMock();

        $plugin = $this->getMockBuilder(AbstractPlugin::class)
            ->onlyMethods(['postStoreInternal'])
            ->getMock();

        $plugin->expects($this->never())
                ->method('postStoreInternal');

        $model->registerPlugin($plugin);
        $model->unregisterPlugin(get_class($plugin));

        $id = $model->store();
    }

    /**
     * Test if a registered plugin can be unregistered by instance.
     */
    public function testUnregisterPluginByInstance()
    {
        $model = new AbstractDbMock();

        $plugin = $this->getMockBuilder(AbstractPlugin::class)
            ->onlyMethods(['postStoreInternal'])
            ->getMock();

        $plugin->expects($this->never())
                ->method('postStoreInternal');

        $model->registerPlugin($plugin);
        $model->unregisterPlugin($plugin);

        $id = $model->store();
    }

    /**
     * Unregistering a plugin that does not exist should throw an exception.
     */
    public function testUnregisteringPluginThatDoesNotExistShouldNotThrowException()
    {
        $model = new AbstractDbMock();

        $plugin = $this->getMockBuilder(AbstractPlugin::class)
            ->onlyMethods(['postStoreInternal'])
            ->getMock();

        $plugin->expects($this->never())
                ->method('postStoreInternal');

        $model->registerPlugin($plugin);

        $model->unregisterPlugin('foobar');
    }

    /**
     * Test if the modified flag of a field is set to false if no field has changed.
     */
    public function testModifiedFlagIsNotSetInitially()
    {
        $model  = new AbstractDbMock();
        $result = $model->isModified();
        $this->assertFalse($result, 'Modified flag is initially true.');
    }

    /**
     * Test if modified flag can be triggered by changing a fields value.
     */
    public function testModifiedFlagCanBeTriggerdCallToSetMethod()
    {
        $model = new AbstractDbMock();
        $model->getField('Value')->clearModified();
        $model->setValue('new value');
        $this->assertTrue($model->isModified(), 'Modified flag has not changed.');
    }

    /**
     * Test if modified flag can be triggered by changing  field values of
     * sub models.
     */
    public function testModifiedFlagCanBeTriggerdByChangingSubmodel()
    {
        $model    = new AbstractDbMock();
        $submodel = new AbstractDbMock();
        $field    = new Field('Submodel');
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
     */
    public function testModifiedFlagIsClearable()
    {
        $model = new AbstractDbMock();
        $model->setValue('new value');
        $model->getField('Value')->clearModified();
        $after = $model->isModified();
        $this->assertFalse($after, 'Modified flag has has not been cleared.');
    }

    /**
     * Test if updating a Model with its very own field values does not
     * affect the modification state of the Model.
     */
    public function testUpdateFromModelWithSameValuesRendersModelUnmodified()
    {
        $clazzname = 'Opus_Model_AbstractTest_MockModel_3';
        $clazz     = 'class ' . $clazzname . ' extends \Opus\Model\AbstractDb {
            protected static $tableGatewayClass = \'OpusTest\Model\Mock\AbstractTableProvider\';
            protected function init() {
                $this->addField(new \Opus\Model\Field("Value"));
            }
        }';
        if (false === class_exists($clazzname, false)) {
            eval($clazz);
        }

        // original model
        $m1 = new $clazzname();
        $m1->setValue('Foo');
        $m1->getField('Value')->clearModified();

        // update model of same type
        $m2 = new $clazzname();
        $m2->setValue('Foo');

        // do the update
        $m1->updateFrom($m2);

        // check values
        $this->assertFalse($m1->isModified(), 'Modification flag has been triggered.');
    }

    /**
     * Test if a models fields have their modified flag cleared after creation
     * of the model.
     */
    public function testFieldsSetToUnmodifiedAfterInit()
    {
        $model = new AbstractDbMock();
        $field = $model->getField('Value');
        $this->assertFalse($field->isModified(), 'Modified flag has not been cleared.');
    }

    public function testValuesAreTrimmed()
    {
        $model = new AbstractDbMock();
        $model->setValue(' Test ');
        $modelId = $model->store();

        $model = new AbstractDbMock($modelId);
        $this->assertEquals('Test', $model->getValue());
    }

    /**
     * Test if a model retrieves its external fields in the right order
     */
    public function testFieldsInitializedInWrongOrder()
    {
        $model = new CheckFieldOrderDummyClass();

        $this->assertEquals('bar', $model->getBefore());
        $this->assertEquals('baz', $model->getAfter());
    }

    public function testNameConversionMethods()
    {
        $fieldnamesToColumns = [
            ['Type', 'type'],
            ['Role', 'role'],
            ['SortOrder', 'sort_order'],
            ['LeftId', 'left_id'],
        ];

        foreach ($fieldnamesToColumns as $pair) {
            $fieldname = $pair[0];
            $colname   = $pair[1];

            $this->assertEquals($colname, AbstractDb::convertFieldnameToColumn($fieldname));
            $this->assertEquals($fieldname, AbstractDb::convertColumnToFieldname($colname));
        }
    }
}
