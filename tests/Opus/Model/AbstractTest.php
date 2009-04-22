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
 * Test cases for class Opus_Model_AbstractDb.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group AbstractTest
 */
class Opus_Model_AbstractTest extends PHPUnit_Framework_TestCase {

    /**
     * Test if describe() returns the fieldnames of all previosly added fields.
     *
     * @return void
     */
    public function testDescribeReturnsAllFields() {
        $mock = new Opus_Model_ModelAbstract;
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
        $mock = new Opus_Model_ModelAbstract;
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
        $mock = new Opus_Model_ModelAbstract;
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
        $mock = new Opus_Model_ModelAbstract;
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
        $mock = new Opus_Model_ModelAbstract;
        $field = $mock->getField('Value');
        $this->assertNotNull($field->getFilter(), 'Filter instance missing.');
    }

    /**
     * Test if an added filter gets executed within it filter chain.
     *
     * @return void
     */
    public function testIfFilterIsExecuted() {
        $mock = new Opus_Model_ModelAbstract;
        $field = $mock->getField('Value');
        $filterChain = $field->getFilter();
        $result = $filterChain->filter('ABC');
        $this->assertEquals('abc', $result, 'Filter has propably not been executed.');
    }

    /**
     * Test if a field can be marked as hidden thus it gets not reported by
     * describe().
     *
     * @return void
     */
    public function testFieldDescriptionHideable() {
        $model = new Opus_Model_ModelWithHiddenField(null);
        $result = $model->describe();
        $this->assertNotContains('HiddenField', $result, 'Field "HiddenField" gets reported.');
    }
    /**
     * Test if the default display name of a model is returned.
     *
     * @return void
     */
    public function testDefaultDisplayNameIsReturned() {
        $obj = new Opus_Model_ModelAbstract;
        $result = $obj->getDisplayName();
        $this->assertEquals('Opus_Model_ModelAbstract', $result, 'Default display name not properly formed.');
    }

    /**
     * Test default getDisplayName() result of Opus_Model_Abstract
     * is the class name.
     *
     * @return void
     */
    public function testAbstractDisplayName() {
        $model = new Opus_Model_ModelWithHiddenField;
        $dspln = $model->getDisplayName();
        $this->assertEquals('Opus_Model_ModelWithHiddenField', $dspln, 'Expected class name.');
    }




    /**
     * Test if set calls can be done in a flunet interface style.
     * E.g. $model->setField(1)->setAnotherField('Foo');
     *
     * @return void
     */
    public function testFluentInterfaceOnSetCall() {
        $model = new Opus_Model_ModelAbstract;

        $result = $model->setValue('Value');
        $this->assertType('Opus_Model_ModelAbstract', $result, 'No fluent interface after set...() call.');
    }


    /**
     * Test if a call to an unknown model method throws an exception
     * describing exactly this problem - not an "unknown field" exception.
     *
     * @return void
     */
    public function testCallToUnknownMethodThrowsBadMethodCallException() {
        $this->setExpectedException('BadMethodCallException');
        $model = new Opus_Model_ModelAbstract;
        $model->notAMethodOfThisClass();
    }

    /**
     * Test if a model can validate its field values.
     *
     * @return void
     */
    public function testValidateModel() {
        $model = new Opus_Model_ModelAbstract;
        $model->setValue('FieldValue');

        $field1 = new Opus_Model_Field('Field1');
        $field1->setValidator(new Zend_Validate_Alnum());

        $field2 = new Opus_Model_Field('Field2');
        $field2->setValidator(new Zend_Validate_NotEmpty());
        $field2->setMandatory(true);

        $model->addField($field1)->addField($field2);

        // try a failing
        $this->assertFalse($model->isValid(), 'Validation should fail.');

        // try successful validation
        $model->setField1('abc123');
        $model->setField2('notempty');
        $this->assertTrue($model->isValid(), 'Validation should succeed.');
    }
    
    /**
     * Test if fields that are not marked as mandatory can remain
     * empty but survive validation.
     *
     * @return void
     */
    public function testNotMandatoryFieldsValidateEvenIfEmpty() {
        $model = new Opus_Model_ModelAbstract;
        $model->getField('Value')->setMandatory(false);
        $model->setValue('');
        $this->assertTrue($model->isValid(), 'Validation should succeed.');
    }

    /**
     * Test if a model can validate its field values.
     *
     * @return void
     */
    public function testValidationIsSkippedForFieldsWithNoValidator() {
        $model = new Opus_Model_ModelAbstract;
        $model->setValue('FieldValue');

        $field1 = new Opus_Model_Field('Field1');
        $field1->setValidator(new Zend_Validate_Alnum());

        $model->addField($field1);

        // try successful validation
        $model->setField1('abc123');
        $this->assertTrue($model->isValid(), 'Validation should succeed.');
    }

    /**
     * Test if a validation error list can be retrieved.
     *
     * @return void
     */
    public function testValidationErrorsAreObtainable() {
        $model = new Opus_Model_ModelAbstract;
        $model->getField('Value')->setMandatory(true);
        // Model field "Value" is empty.
        $this->assertFalse($model->isValid(), 'Validation should fail.');
        $this->assertNotNull($model->getValidationErrors(), 'Validation errors are not set.');
    }

    /**
     * Test if the returned validation errors are in the form of an
     * associative array mapping fieldnamed to errors.
     *
     * @return void
     */
    public function testValidationErrorsAreObtainablePerField() {
        $model = new Opus_Model_ModelAbstract;

        $model->isValid();
        $errors = $model->getValidationErrors();

        $this->assertArrayHasKey('Value', $errors, 'Field "Value" is missing in error listing.');
    }
    
    /**
     * Test if a models fields have their modified flag cleared after creation
     * of the model.
     *
     * @return void
     */
    public function testFieldsSetToUnmodifiedAfterInit() {
        $model = new Opus_Model_ModelAbstract;
        $field = $model->getField('Value');
        $this->assertFalse($field->isModified(), 'Modified flag has not been cleared.');
    }

}
