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

use ClassWithDeleteMethod;
use fieldTestInspector;
use InvalidArgumentException;
use Opus\Common\Date;
use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\Model\Field;
use OpusTest\Model\Mock\ModelAbstractDbMock;
use OpusTest\Model\Mock\ModelDependentMock;
use OpusTest\TestAsset\TestCase;
use stdClass;

use function get_class;
use function is_array;

/**
 * Test cases for class Opus\Model\Field.
 *
 * @category Tests
 * @package  Opus\Model
 * @group    FieldTest
 */
class FieldTest extends TestCase
{
    /**
     * Overwrite parent methods.
     */
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    /**
     * Date provider for invalid setMultiplicity() arguments test.
     *
     * @return array
     */
    public function invalidSetMultiplicityValuesDataProvider()
    {
        return [
            ['0'],
            ['1'],
            [0],
            [-1],
            ['a'],
            ['z'],
            [''],
            [' '],
            [true],
            [false],
            [565676.234],
            [-0.0435],
            [new InvalidArgumentException()],
            [[1, 2, 3, 4]],
        ];
    }

    /**
     * Data provider for function name and corresponding data that is ought
     * to be interpreted as boolean.
     *
     * @return array
     */
    public function setterGetterCallDataProvider()
    {
        return [
            ['Mandatory', 'true', true],
            ['Mandatory', 0, false],
            ['Mandatory', 'yes', true],
            ['Mandatory', 'True', true],
            ['Mandatory', false, false],
            ['Textarea', 'true', true],
            ['Textarea', 1, true],
            ['Textarea', 'True', true],
            ['Textarea', false, false],
            ['Selection', 'true', true],
            ['Selection', 1, true],
            ['Selection', 'yes', true],
            ['Selection', false, false],
        ];
    }

    /**
     * Test if the class name of a model can be retrieved from the field.
     */
    public function testNameOfValueClassCanBeRetrieved()
    {
        $field = new Field('MyField');
        $field->setValueModelClass('Opus_Model_AbstractMock');
        $classname = $field->getValueModelClass();
        $this->assertEquals('Opus_Model_AbstractMock', $classname, 'Wrong class name returned.');
    }

    /**
     * Test that the returned model class name is empty if the field value
     * is not an model instance.
     */
    public function testNameOfValueClassIsEmptyIfNoModelClassIsSet()
    {
        $field = new Field('MyField');
        $field->setValue('no_object');
        $classname = $field->getValueModelClass();
        $this->assertNull($classname, 'Class name returned when no model instance is set as value.');
    }

    /**
     * Test if a field is set to have single value it never returns an array
     * as its value.
     */
    public function testSinglevaluedFieldOnlyHasSingleValue()
    {
        $field = new Field('MyField');
        $field->setMultiplicity(1);
        $result = $field->getValue();
        $this->assertFalse(is_array($result), 'Returned value should not be an array.');
    }

    /**
     * Test if a field is set to have multiple values it always returns an array
     * as its value.
     */
    public function testMultivaluedFieldOnlyHasArrayValue()
    {
        $field = new Field('MyField');
        $field->setMultiplicity('*');
        $result = $field->getValue();
        $this->assertTrue(is_array($result), 'Returned value is not an array.');
    }

    /**
     * Test if a field returns right multiplicity
     */
    public function testSinglevaluedFieldMultiplicity()
    {
        $field = new Field('MyField');
        $field->setMultiplicity(1);
        $this->assertFalse($field->hasMultipleValues(), 'Field should not allow multiple values.');
    }

    /**
     * Test if a field is set to have single value it does not accept an array as
     * its input value.
     */
    public function testSingleValuedFieldTakesSingleValue()
    {
        $field = new Field('MyField');
        $field->setMultiplicity(1);
        $this->expectException('InvalidArgumentException');
        $field->setValue(['single', 'sungle', 'sangle']);
    }

    /**
     * Test if only valid integer values greater zero or "*" can be set
     * as multiplicity.
     *
     * @param string $value
     * @dataProvider invalidSetMultiplicityValuesDataProvider
     */
    public function testInputValuesForMultiplicityAreIntegerOrStar($value)
    {
        $this->expectException('InvalidArgumentException');
        $field = new Field('MyField');
        $field->setMultiplicity($value);
    }

    /**
     * Test if a specific value can be obtained from a multivalued field by
     * specifying an array index.
     */
    public function testGetSpecificIndexFromMultivalueField()
    {
        $field = new Field('MyField');
        $field->setMultiplicity('*');
        $field->setValue([1, 2, 'Hallo']);
        $this->assertEquals(1, $field->getValue(0), 'Wrong value on index 0.');
        $this->assertEquals(2, $field->getValue(1), 'Wrong value on index 1.');
        $this->assertEquals('Hallo', $field->getValue(2), 'Wrong value on index 2.');
    }

    /**
     * Test if the modified flag of a field is set to false.
     */
    public function testModifiedFlagIsNotSetInitially()
    {
        $field  = new Field('MyField');
        $result = $field->isModified();
        $this->assertFalse($result, 'Modified flag is initially true.');
    }

    /**
     * Test if the modified falg is indeed set to true if a call to setValue()
     * gives a new value to the field.
     */
    public function testModifiedFlagIsSetAfterSettingNewValue()
    {
        $field = new Field('MyField');
        $field->setValue('MyValue');
        $after = $field->isModified();
        $this->assertTrue($after, 'Modified flag has has not been set.');
    }

    /**
     * Test if the modified flag can be set back to false again.
     */
    public function testModifiedFlagIsClearable()
    {
        $field = new Field('MyField');
        $field->setValue('MyValue');
        $field->clearModified();
        $after = $field->isModified();
        $this->assertFalse($after, 'Modified flag has has not been cleared.');
    }

    /**
     * Test if the modified flag is set to true after a call to setValue()
     * with the current value of the field.
     */
    public function testModifiedFlagRemainsAfterSettingSameValueAgain()
    {
        $field  = new Field('MyField');
        $before = $field->isModified();
        $field->setValue($field->getValue());
        $after = $field->isModified();
        $this->assertEquals($before, $after, 'Modified flag has changed.');
    }

    /**
     * Test if modified flag can be triggered.
     */
    public function testModifiedFlagCanBeTriggerdViaSetModified()
    {
        $field = new Field('MyField');
        $field->clearModified();
        $field->setModified();
        $this->assertTrue($field->isModified(), 'Modified flag has not changed.');
    }

    /**
     * Test setting of default values
     */
    public function testSetDefault()
    {
        $field = new Field('MyField');
        $array = ['my', 'default', 'values'];
        $field->setDefault($array);
        $result = $field->getDefault();
        $this->assertEquals($array, $result, 'Wrong default value returned');
    }

    /**
     * Test if setting the selection flag clear other flags.
     */
    public function testSelectionFlagClearsOtherFlags()
    {
        $field = new Field('MyField');
        $field->setCheckbox(true);
        $field->setTextarea(true);
        $field->setSelection(true);

        $this->assertTrue($field->isSelection(), 'Selection flag does not get set.');
        $this->assertFalse($field->isTextarea(), 'Textarea flag does not get cleared when selection is set.');
        $this->assertFalse($field->isCheckbox(), 'Checkbox flag does not get cleared when selection is set.');
    }

    /**
     * Test if setting the textarea flag clear other flags.
     */
    public function testTextareaFlagClearsOtherFlags()
    {
        $field = new Field('MyField');
        $field->setCheckbox(true);
        $field->setSelection(true);
        $field->setTextarea(true);

        $this->assertTrue($field->isTextarea(), 'Textarea flag does not get set.');
        $this->assertFalse($field->isSelection(), 'Selection flag does not get cleared when selection is set.');
        $this->assertFalse($field->isCheckbox(), 'Checkbox flag does not get cleared when selection is set.');
    }

    /**
     * Test if setting the checkbox flag clear other flags.
     */
    public function testCheckboxFlagClearsOtherFlags()
    {
        $field = new Field('MyField');
        $field->setTextarea(true);
        $field->setSelection(true);
        $field->setCheckbox(true);

        $this->assertTrue($field->isCheckbox(), 'Checkbox flag does not get set.');
        $this->assertFalse($field->isTextarea(), 'Textarea flag does not get cleared when selection is set.');
        $this->assertFalse($field->isSelection(), 'Selection flag does not get cleared when selection is set.');
    }

    /**
     * Test that only real boolean values can be passed to flag functions.
     *
     * @param string $func
     * @param mixed  $input
     * @param mixed  $output
     * @dataProvider setterGetterCallDataProvider
     */
    public function testSetterGetterTypeCastingInputValues($func, $input, $output)
    {
        $field = new Field('MyField');

        $setCallname = 'set' . $func;
        $getCallname = 'is' . $func;

        $field->$setCallname($input);
        $result = $field->$getCallname();

        $this->assertEquals($output, $result, 'Retrieved value considered wrong.');
    }

    /**
     * Test if setting object references uses a weaker comparison method
     * to ensure that objects with same attribute values are treated as equal
     * even if they are different instances.
     */
    public function testWeakComparisonForObjectReferences()
    {
        $field = new Field('MyField');

        $obj1 = new Field('Message');
        $obj2 = new Field('Message');

        $field->setValue($obj1);
        $field->clearModified();

        $field->setValue($obj2);
        $this->assertFalse($field->isModified(), 'Assigning equal objects should not raise modified flag.');
    }

    public function testComparisonForOpusDateObjects()
    {
        $field = new Field('MyField');

        $date1 = new Date('2018-10-14');
        $date2 = new Date('2018-10-14');

        $field->setValue($date1);
        $field->clearModified();

        $field->setValue($date2);
        $this->assertFalse($field->isModified(), 'Assigning equal date should not raise modified flag.');
    }

    /**
     * Test if setting a non-object value enforces strong comparision
     * including type checking.
     */
    public function testStrongComparisionForNonObjectsValues()
    {
        $field = new Field('MyField');

        $val1 = true;
        $val2 = 'true';

        $field->setValue($val1);
        $field->clearModified();

        $field->setValue($val2);
        $this->assertTrue($field->isModified(), 'Assigning unequal types should raise modified flag.');
    }

    /**
     * Test if new values can be added to present values of a multivalued field.
     */
    public function testAddingValueToMultivaluedFields()
    {
        $field = new Field('MyField');
        $field->setMultiplicity('*');

        $field->setValue([1, 2, 3, 4]);
        $field->addValue(15);

        $this->assertEquals([1, 2, 3, 4, 15], $field->getValue(), 'Value has not been added.');
    }

    /**
     * Test if a whole array can be added to a multivalued field.
     */
    public function testAddingArrayValuesToMultivaluedField()
    {
        $field = new Field('MyField');
        $field->setMultiplicity('*');

        $field->setValue([1, 2, 3, 4]);
        $field->addValue([15, 16, 17]);

        $this->assertEquals([1, 2, 3, 4, 15, 16, 17], $field->getValue(), 'Values have not been added.');
    }

    /**
     * Test if values can be added to an uninitialized field.
     */
    public function testAddingValuesToEmptyField()
    {
        $field = new Field('MyField');
        $field->setMultiplicity('*');
        $field->addValue([15, 16, 17]);
        $this->assertEquals([15, 16, 17], $field->getValue(), 'Values have not been added.');
    }

    /**
     * Test if values can be added to an uninitialized non-multiple field.
     */
    public function testAddingValuesToNonMultipleField()
    {
        $this->expectException('InvalidArgumentException');
        $field = new Field('MyField');
        $field->setMultiplicity('1');
        $field->addValue([15, 16, 17]);
    }

    /**
     * Test if single value can be added to an uninitialized non-multiple field.
     */
    public function testAddingSingleValueToNonMultipleField()
    {
        $field = new Field('MyField');
        $field->setMultiplicity(1);
        $field->addValue(15);
        $this->assertEquals(15, $field->getValue(), 'Value has not been added.');
    }

    /**
     * Test if adding multiple values raises the modified flag.
     */
    public function testAddingValuesSetsModifiedFlag()
    {
        $field = new Field('MyField');
        $field->setMultiplicity('*');
        $field->clearModified();
        $field->addValue([15, 16, 17]);
        $this->assertTrue($field->isModified(), 'Adding values should raise "modified" flag.');
    }

    /**
     * Test if attempt to add more values than allowed throws an exception.
     */
    public function testAddingMoreValuesThenAllowedThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        $field = new Field('MyField');
        $field->setMultiplicity(3);
        $field->addValue([15, 16, 17, 18]);
    }

    /**
     * Test if setting multi-value fields to null clears field properly.
     */
    public function testSetMultivalueFieldToNull()
    {
        $field = new Field('MultiValField');
        $field->setMultiplicity('*');
        $field->setValue(['a', 'b', 'c']);
        $field->setValue(null);
        $value = $field->getValue();
        $this->assertTrue(empty($value), 'Multivalue field not cleared after setting to null.');
    }

    /**
     * Test if delete() is not issued if the field value is not an Opus\Model\Dependent_*
     */
    public function testSetFieldValueToNullDoesNotTriggerDeleteWithNoDependentModel()
    {
        $clazz = 'class ClassWithDeleteMethod { public $trigger = false; public function delete() { $this->trigger = true; } }';
        eval($clazz);
        $obj   = new ClassWithDeleteMethod();
        $field = new Field('SomeField');
        $field->setValue($obj);
        $field->setValue(null);

        $this->assertFalse($obj->trigger, 'Delete method has been called on non Opus\Model\Dependent\AbstractDependentModel class.');
    }

    /**
     * Test if setting a field containing a dependent model to "null" issues
     * a delete() request on that model.
     */
    public function testSetDependentModelFieldToNullRemovesModelFromDatabase()
    {
        // create field referencing the mockup model
        $depmo = new ModelDependentMock();
        $field = new Field('ExternalModel');
        $field->setValueModelClass(get_class($depmo));
        $field->setValue($depmo);

        // issue the test
        $field->setValue(null);

        // assert that delete() has been called
        $this->assertTrue($depmo->deleteHasBeenCalled, 'Setting value to null does not delete referenced dependent model.');
    }

    /**
     * Test if setting a multivalue field containing dependent models to "null" issues
     * a delete() request to all these model.
     */
    public function testSetDependentModelMultivalueFieldToNullRemovesModelsFromDatabase()
    {
        // create field referencing the mockup models
        $depmo[] = new ModelDependentMock();
        $depmo[] = new ModelDependentMock();
        $depmo[] = new ModelDependentMock();

        $field = new Field('ExternalModels');
        $field->setMultiplicity('*');
        $field->setValueModelClass(ModelDependentMock::class);
        $field->setValue($depmo);

        // issue the test
        $field->setValue(null);

        // assert that delete() has been called
        $this->assertTrue($depmo[0]->deleteHasBeenCalled, 'Setting value to null does not delete referenced dependent models.');
        $this->assertTrue($depmo[1]->deleteHasBeenCalled, 'Setting value to null does not delete referenced dependent models.');
        $this->assertTrue($depmo[2]->deleteHasBeenCalled, 'Setting value to null does not delete referenced dependent models.');
    }

    /**
     * Test if Field reports modification if contained Model is modified.
     */
    public function testIsModifiedReturnsTrueIfReferencedModelHasBeenModified()
    {
        $model = new ModelAbstractDbMock();
        $model->addField(new Field('FooField'));

        $field = new Field('myfield');
        $field->setValueModelClass(get_class($model));
        $field->setValue($model);
        $field->clearModified();

        // set modification
        $model->getField('FooField')->setValue('Bar');

        // assert modified field
        $this->assertTrue($field->isModified(), 'Field is not marked as modified.');
    }

    /**
     * Test if Field reports modification if contained models are arrays.
     * Regression test for OPUSVIER-2261.
     */
    public function testIsModifiedReturnsTrueIfArrayContainsModifiedModel()
    {
        $model1 = new ModelAbstractDbMock();
        $model1->addField(new Field('FooField'));
        $this->assertFalse($model1->isModified(), 'Model1 should not be marked as modified.');

        $model2 = new ModelAbstractDbMock();
        $model2->addField(new Field('FooField'));
        $this->assertFalse($model2->isModified(), 'Model2 should not be marked as modified.');

        $field = new Field('myfield');
        $field->setMultiplicity('*')
            ->setValueModelClass(get_class($model1))
            ->setValue([$model1, $model2])
            ->clearModified();
        $this->assertFalse($field->isModified(), 'Field should not be marked as modified.');

        $model2->setFooField('bla');
        $this->assertTrue($field->isModified(), 'Field is not marked as modified.');
    }

    /**
     * Test if an exception occurs if value of unexpected type is set.
     */
    public function testValueOfUnexpectedTypeThrowsException()
    {
        $field = new Field('myfield');
        $field->setValueModelClass('Date');
        $this->expectException(ModelException::class);
        $field->setValue(new stdClass());
    }

    /**
     * Test if an excpetion occurs if value of unexpected type is set.
     */
    public function testValueOfUncastableDataThrowsException()
    {
        $field = new Field('myfield');
        $field->setValueModelClass(Document::class);

        try {
            $field->setValue('Foo');
            $this->fail('Missing exception!');
        } catch (ModelException $ome) {
            $this->assertStringStartsWith("Failed to cast value 'Foo'", $ome->getMessage());
        }
    }

    /**
     * Test if a value gets casted to the fields valueModelClass if possible.
     */
    public function testSetterValueGetsCasted()
    {
        $field = new Field('myfield');
        $field->setValueModelClass(Date::class);
        try {
            $field->setValue('10.11.1979');
        } catch (ModelException $ome) {
            $this->fail('No type check excpetion expected: ' . $ome->getMessage());
        }
        $result = $field->getValue();

        $this->assertTrue($result instanceof Date, 'Value has not been casted to valueModelClass object.');
    }

    /**
     * Test if a pending delete operation is collected on every delete of a
     * dependent Model.
     */
    public function testDeleteCollectsPendingOperations()
    {
        // create field referencing the mockup model
        $depmo = new ModelDependentMock();

        eval('
            class fieldTestInspector extends \Opus\Model\Field {
                public function getPendingDeletes() {
                    return $this->pendingDeletes;
                }
            }
        ');

        $field = new fieldTestInspector('ExternalModel');
        $field->setValueModelClass(get_class($depmo));
        $field->setValue($depmo);

        // issue the test
        $field->setValue(null);

        // assert that there is a pending operation
        $deletes = $field->getPendingDeletes();
        $this->assertFalse(empty($deletes), 'No pending delete operations generated.');
    }

    /**
     * Test if pending deletes get executed.
     */
    public function testDoPendingDeletesLoopsModelsAndDoesDelete()
    {
        // create field referencing the mockup models
        $depmo[] = new ModelDependentMock();
        $depmo[] = new ModelDependentMock();
        $depmo[] = new ModelDependentMock();

        $field = new Field('ExternalModels');
        $field->setMultiplicity('*');
        $field->setValueModelClass(ModelDependentMock::class);
        $field->setValue($depmo);

        // issue the test
        $field->setValue(null);
        $field->doPendingDeleteOperations();

        // assert that delete() has been called
        $this->assertTrue($depmo[0]->doDeleteHasBeenCalled, 'Setting value to null does not delete referenced dependent models.');
        $this->assertTrue($depmo[1]->doDeleteHasBeenCalled, 'Setting value to null does not delete referenced dependent models.');
        $this->assertTrue($depmo[2]->doDeleteHasBeenCalled, 'Setting value to null does not delete referenced dependent models.');
    }

    public function testSetAndGetOwningModelClass()
    {
        $field = new Field('Test');
        $field->setOwningModelClass('Opus_Test');
        $this->assertEquals('Opus_Test', $field->getOwningModelClass());
    }

    public function testSetBoolean()
    {
        $field = new Field('VisibleInOai');

        $field->setValue(false);

        $this->assertEquals(0, $field->getValue());
        $this->assertEquals(false, $field->getValue());
        $this->assertInternalType('int', $field->getValue());

        $field->setValue(true);

        $this->assertEquals(1, $field->getValue());
        $this->assertEquals(true, $field->getValue());
        $this->assertInternalType('int', $field->getValue());

        $field->setValue(0);

        $this->assertEquals(0, $field->getValue());
        $this->assertEquals(false, $field->getValue());
        $this->assertInternalType('int', $field->getValue());

        $field->setValue(1);

        $this->assertEquals(1, $field->getValue());
        $this->assertEquals(true, $field->getValue());
        $this->assertInternalType('int', $field->getValue());
    }
}
