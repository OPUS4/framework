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
 * @group AbstractTest
 */
class Opus_Model_AbstractTest extends TestCase {

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
        $this->assertEquals(array('Id', 'Value', 'Field1', 'Field2'), $fields, 'Wrong set of field names returned.');
    }



    /**
     * Test if a field can be defined as internal thus it gets not reported by
     * describe().
     *
     * @return void
     */
    public function testHideInternalField() {
        $model = new Opus_Model_ModelWithHiddenField(null);
        $result = $model->describe();
        $this->assertNotContains('HiddenField', $result, 'Field "HiddenField" gets reported.');
    }

    /**
     * Test if an internal field can not be set.
     *
     * @return void
     */
    public function testSetCallToInternalFieldThrowsException() {
        $model = new Opus_Model_ModelWithHiddenField(null);
        $this->setExpectedException('Opus_Model_Exception');
        $model->setHiddenField('value');
    }

    /**
     * Test if an internal field can not be queried.
     *
     * @return void
     */
    public function testGetCallToInternalFieldThrowsException() {
        $model = new Opus_Model_ModelWithHiddenField(null);
        $this->setExpectedException('Opus_Model_Exception');
        $model->getHiddenField();
    }

    /**
     * Test if an internal field can not be added to.
     *
     * @return void
     */
    public function testAddCallToInternalFieldThrowsException() {
        $model = new Opus_Model_ModelWithHiddenField(null);
        $this->setExpectedException('Opus_Model_Exception');
        $model->addHiddenField();
    }

    /**
     * Test if an internal field can not be retrieved.
     *
     * @return void
     */
    public function testGetInternalFieldThrowsException() {
        $model = new Opus_Model_ModelWithHiddenField(null);
        $this->setExpectedException('Opus_Model_Exception');
        $model->getField('HiddenField');
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
        $model->getField('Value')->setValidator(new Zend_Validate_NotEmpty());
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
        $model->getField('Value')->setMandatory(true);
        $model->getField('Value')->setValidator(new Zend_Validate_NotEmpty());
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

    /**
     * Test if a submodel gets validated by its supermodel when the containing
     * field is set to be mandatory.
     *
     * @return void
     */
    public function testValidationOfSubmodelIfStoredInMandatoryField() {
        $submodel = $this->getMock('Opus_Model_ModelAbstract');
        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('Submodel');
        $field->setValueModelClass('Opus_Model_ModelAbstract')
            ->setMandatory(true)
            ->setValue($submodel);
        $model->addField($field);

        // expect call to isValid
        $submodel->expects($this->once())
            ->method('isValid');

        // trigger call
        $model->isValid();
    }


    /**
     * Test if validation of submodels gets triggers for each model in
     * a multivalue field.
     *
     * @return void
     */
    public function testValidationOfSubmodelsInMultivalueFields() {
        $submodels[] = $this->getMock('Opus_Model_ModelAbstract');
        $submodels[] = $this->getMock('Opus_Model_ModelAbstract');
        $submodels[] = $this->getMock('Opus_Model_ModelAbstract');

        // expect calls to isValid
        foreach ($submodels as $submodel) {
            $submodel->expects($this->once())
                ->method('isValid');
        }

        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('Submodels');
        $field->setValueModelClass('Opus_Model_ModelAbstract')
            ->setMandatory(true)
            ->setMultiplicity('*')
            ->setValue($submodels);
        $model->addField($field);

        // trigger calls
        $model->isValid();
    }


    /**
     * Test if a submodel validation fault triggers a supermodels validation fault.
     *
     * @return void
     */
    public function testValidationFailsIfSubmodelValidationDoesSo() {
        $submodel = $this->getMock('Opus_Model_ModelAbstract');
        $model = new Opus_Model_ModelAbstract;
        $field = new Opus_Model_Field('Submodel');
        $field->setValueModelClass('Opus_Model_ModelAbstract')
            ->setMandatory(true)
            ->setValue($submodel);
        $model->addField($field);

        // expect call to isValid (wich will return false)
        $submodel->expects($this->any())
            ->method('isValid')
            ->will($this->returnValue(false));

        $result = $model->isValid();
        $this->assertFalse($result, 'Validation should fail because submodel validation failes.');
    }


    /**
     * Test if the modified flag of a field is set to false if no field has changed.
     *
     * @return void
     */
    public function testModifiedFlagIsNotSetInitially() {
        $model = new Opus_Model_ModelAbstract;
        $result = $model->isModified();
        $this->assertFalse($result, 'Modified flag is initially true.');
    }

    /**
     * Test if modified flag can be triggered by setModified().
     *
     * @return void
     */
    public function testModifiedFlagCanBeTriggerdViaSetModified() {
        $model = new Opus_Model_ModelAbstract;
        $model->clearModified();
        $model->setModified();
        $this->assertTrue($model->isModified(), 'Modified flag has not changed.');
    }

    /**
     * Test if modified flag can be triggered by changing a fields value.
     *
     * @return void
     */
    public function testModifiedFlagCanBeTriggerdCallToSetMethod() {
        $model = new Opus_Model_ModelAbstract;
        $model->clearModified();
        $model->setValue('new value');
        $this->assertTrue($model->isModified(), 'Modified flag has not changed.');
    }

    /**
     * Test if the modified flag can be set back to false again.
     *
     * @return void
     */
    public function testModifiedFlagIsClearable() {
        $model = new Opus_Model_ModelAbstract;
        $model->setValue('new value');
        $model->clearModified();
        $after = $model->isModified();
        $this->assertFalse($after, 'Modified flag has has not been cleared.');
    }



}
