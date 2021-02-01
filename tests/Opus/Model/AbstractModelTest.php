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
 * @package     Opus\Model
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model;

use Opus\Date;
use Opus\Document;
use Opus\Language;
use Opus\Model\Field;
use Opus\Model\PropertiesException;
use Opus\Model\UnknownModelTypeException;
use Opus\Person;
use OpusTest\Model\Mock\AbstractModelMock;
use OpusTest\Model\Mock\ModelWithHiddenField;
use OpusTest\TestAsset\TestCase;

/**
 * Test cases for class Opus\Model\AbstractDb.
 *
 * @package Opus\Model
 * @category Tests
 *
 * @group AbstractTest
 */
class AbstractModelTest extends TestCase
{

    /**
     * Test if describe() returns the fieldnames of all previosly added fields.
     *
     * @return void
     */
    public function testDescribeReturnsAllFields()
    {
        $mock = new AbstractModelMock;
        $mock->addField(new Field('Field1'))
            ->addField(new Field('Field2'));
        $fields = $mock->describe();
        $this->assertEquals(['Id', 'Value', 'Field1', 'Field2'], $fields, 'Wrong set of field names returned.');
    }

    /**
     * Test if a field can be defined as internal thus it gets not reported by
     * describe().
     *
     * @return void
     */
    public function testHideInternalField()
    {
        $model = new ModelWithHiddenField(null);
        $result = $model->describe();
        $this->assertNotContains('HiddenField', $result, 'Field "HiddenField" gets reported.');
    }

    /**
     * Test if an internal field can not be set.
     *
     * @return void
     */
    public function testSetCallToInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->setExpectedException('Opus\Model\ModelException');
        $model->setHiddenField('value');
    }

    /**
     * Test if an internal field can not be queried.
     *
     * @return void
     */
    public function testGetCallToInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->setExpectedException('Opus\Model\ModelException');
        $model->getHiddenField();
    }

    /**
     * Test if an internal field can not be added to.
     *
     * @return void
     */
    public function testAddCallToInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->setExpectedException('Opus\Model\ModelException');
        $model->addHiddenField();
    }

    /**
     * Test if an internal field can not be retrieved.
     *
     * @return void
     */
    public function testGetInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->setExpectedException('Opus\Model\ModelException');
        $model->getField('HiddenField');
    }

    /**
     * Test if set calls can be done in a flunet interface style.
     * E.g. $model->setField(1)->setAnotherField('Foo');
     *
     * @return void
     */
    public function testFluentInterfaceOnSetCall()
    {
        $model = new AbstractModelMock;
        $result = $model->setValue('Value');
        $this->assertInstanceOf(
            'OpusTest\Model\Mock\AbstractModelMock',
            $result,
            'No fluent interface after set...() call.'
        );
    }


    /**
     * Test if a call to an unknown model method throws an exception
     * describing exactly this problem - not an "unknown field" exception.
     *
     * @return void
     */
    public function testCallToUnknownMethodThrowsBadMethodCallException()
    {
        $this->setExpectedException('BadMethodCallException');
        $model = new AbstractModelMock;
        $model->notAMethodOfThisClass();
    }

    /**
     * Test if a model can validate its field values.
     *
     * @return void
     */
    public function testValidateModel()
    {
        $model = new AbstractModelMock;
        $model->setValue('FieldValue');

        $field1 = new Field('Field1');
        $field1->setValidator(new \Zend_Validate_Alnum());

        $field2 = new Field('Field2');
        $field2->setValidator(new \Zend_Validate_NotEmpty());
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
    public function testNotMandatoryFieldsValidateEvenIfEmpty()
    {
        $model = new AbstractModelMock;
        $model->getField('Value')->setMandatory(false);
        $model->setValue('');
        $this->assertTrue($model->isValid(), 'Validation should succeed.');
    }

    /**
     * Test if a model can validate its field values.
     *
     * @return void
     */
    public function testValidationIsSkippedForFieldsWithNoValidator()
    {
        $model = new AbstractModelMock;
        $model->setValue('FieldValue');

        $field1 = new Field('Field1');
        $field1->setValidator(new \Zend_Validate_Alnum());

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
    public function testValidationErrorsAreObtainable()
    {
        $model = new AbstractModelMock;
        $model->getField('Value')->setMandatory(true);
        $model->getField('Value')->setValidator(new \Zend_Validate_NotEmpty());
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
    public function testValidationErrorsAreObtainablePerField()
    {
        $model = new AbstractModelMock;
        $model->getField('Value')->setMandatory(true);
        $model->getField('Value')->setValidator(new \Zend_Validate_NotEmpty());
        $model->isValid();

        $errors = $model->getValidationErrors();
        $this->assertArrayHasKey('Value', $errors, 'Field "Value" is missing in error listing.');
    }

    /**
     * Test if a submodel gets validated by its supermodel when the containing
     * field is set to be mandatory.
     *
     * @return void
     */
    public function testValidationOfSubmodelIfStoredInMandatoryField()
    {
        $submodel = $this->getMock('OpusTest\Model\Mock\AbstractModelMock');
        $model = new AbstractModelMock();
        $field = new Field('Submodel');
        $field->setValueModelClass('OpusTest\Model\Mock\AbstractModelMock')
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
    public function testValidationOfSubmodelsInMultivalueFields()
    {
        $submodels[] = $this->getMock('OpusTest\Model\Mock\AbstractModelMock');
        $submodels[] = $this->getMock('OpusTest\Model\Mock\AbstractModelMock');
        $submodels[] = $this->getMock('OpusTest\Model\Mock\AbstractModelMock');

        // expect calls to isValid
        foreach ($submodels as $submodel) {
            $submodel->expects($this->once())
                ->method('isValid');
        }

        $model = new AbstractModelMock;
        $field = new Field('Submodels');
        $field->setValueModelClass('OpusTest\Model\Mock\AbstractModelMock')
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
    public function testValidationFailsIfSubmodelValidationDoesSo()
    {
        $submodel = $this->getMock('OpusTest\Model\Mock\AbstractModelMock');
        $model = new AbstractModelMock;
        $field = new Field('Submodel');
        $field->setValueModelClass('OpusTest\Model\Mock\AbstractModelMock')
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
     * Test if property owningModelClass gets set for field of Opus\Document.
     */
    public function testGetOwningModelClassForFieldOfDocument()
    {
        $doc = new Document();
        $field = $doc->getField('Type');
        $this->assertEquals('Opus\Document', $field->getOwningModelClass());
    }

    /**
     * Test if property owningModelClass gets set for field of Opus\Person.
     */
    public function testGetOwningModelClassForFieldOfPerson()
    {
        $person = new Person();
        $field = $person->getField('FirstName');
        $this->assertEquals('Opus\Person', $field->getOwningModelClass());
    }

    /**
     * Test if property owningModelClass gets set for link class.
     */
    public function testGetOwningModelClassForFieldOfDocumentPerson()
    {
        $doc = new Document();
        $person = new Person();
        $doc->addPerson($person);
        $persons = $doc->getPerson();
        $person = $persons[0];

        // Test for field that belongs to Opus\Person
        $field = $person->getField('FirstName');
        $this->assertEquals('Opus\Person', $field->getOwningModelClass());

        // Test for field that belongs to Opus\Model\Dependent\Link\DocumentPerson
        $field = $person->getField('SortOrder');
        $this->assertEquals('Opus\Model\Dependent\Link\DocumentPerson', $field->getOwningModelClass());
    }

    public function testGetFieldForUnkownField()
    {
        $doc = new Document();
        $this->assertNull($doc->getField('FieldDoesNotExist'));
    }

    public function testGetLogger()
    {
        $model = new AbstractModelMock();

        $logger = $model->getLogger();

        $this->assertNotNull($logger);
        $this->assertEquals(Log::get(), $logger);
    }

    public function testSetLogger()
    {
        $logger = new \Zend_Log();

        $model = new AbstractModelMock();

        $model->setLogger($logger);

        $this->assertEquals($logger, $model->getLogger());
        $this->assertNotEquals(Log::get(), $model->getLogger());
    }

    public function testToArrayWithPerson()
    {
        $doc = new Document();

        $person = new Person();
        $person->setLastName('Testy');

        $link = $doc->addPerson($person);
        $link->setRole('author');

        $doc = new Document($doc->store());

        $data = $doc->toArray();

        $this->assertCount(63, $data);

        $this->assertArrayNotHasKey('id', $data); // database id to part of array
        $this->assertArrayHasKey('PersonAuthor', $data);

        $authors = $data['PersonAuthor'];

        $this->assertCount(1, $authors);

        $author = array_pop($authors);

        $this->assertArrayHasKey('LastName', $author);
        $this->assertEquals('Testy', $author['LastName']);
    }

    public function testUpdateFromArray()
    {
        $doc = new Document();

        $doc->updateFromArray([
            'Type' => 'article',
            'Edition' => 'First'
        ]);

        $this->assertEquals('article', $doc->getType());
        $this->assertEquals('First', $doc->getEdition());
    }

    public function testUpdateFromArrayComplexValue()
    {
        $doc = new Document();

        $doc->updateFromArray([
            'Type' => 'article',
            'PersonAuthor' => [
                ['LastName' => 'Tester']
            ]
        ]);

        $this->assertEquals('article', $doc->getType());

        $authors = $doc->getPersonAuthor();

        $this->assertCount(1, $authors);

        $author = $authors[0];

        $this->assertEquals('Tester', $author->getLastName());
    }

    public function testUpdateFromArrayMultipleComplexValues()
    {
        $doc = new Document();

        $doc->updateFromArray([
            'Type' => 'article',
            'PersonAuthor' => [
                ['LastName' => 'author1'],
                ['LastName' => 'author2', 'Email' => 'author@example.org']
            ]
        ]);

        $this->assertEquals('article', $doc->getType());

        $authors = $doc->getPersonAuthor();

        $this->assertCount(2, $authors);

        $author1 = $authors[0];

        $this->assertEquals('author1', $author1->getLastName());
        $this->assertEquals(1, $author1->getSortOrder());

        $author2 = $authors[1];

        $this->assertEquals('author2', $author2->getLastName());
        $this->assertEquals(2, $author2->getSortOrder());
        $this->assertEquals('author@example.org', $author2->getEmail());
    }

    public function testUpdateFromArrayForLinkToLicence()
    {
        $this->markTestIncomplete('not implemented yet');
    }

    public function testClearFields()
    {
        $date = new Date();
        $date->setFromString('2018-05-11T22:35:11Z');

        $this->assertEquals('2018-05-11 22:35:11', date_format($date->getDateTime(), 'Y-m-d H:i:s'));

        $date->clearFields();

        $this->assertNull($date->getYear());
        $this->assertNull($date->getMonth());
        $this->assertNull($date->getDay());
        $this->assertNull($date->getHour());
        $this->assertNull($date->getMinute());
        $this->assertNull($date->getSecond());
        $this->assertNull($date->getTimezone());
        $this->assertNull($date->getUnixTimestamp());
    }

    public function testFromArray()
    {
        $date = Date::fromArray([
            'Year' => 2018,
            'Month' => 10,
            'Day' => 12
        ]);

        $this->assertInstanceOf('Opus\Date', $date);
        $this->assertEquals('2018-10-12', $date->__toString());
    }

    public function testGetModelType()
    {
        $model = new Language();

        $this->setExpectedException(
            UnknownModelTypeException::class,
            'Properties not supported for Opus\Language'
        );

        $model->getModelType();
    }

    public function testSetProperty()
    {
        $doc = new Document();
        $doc->store();

        $key = 'indexed';
        $value = 'true';

        $doc->setProperty($key, $value);

        $this->assertEquals($value, $doc->getProperty($key));
    }

    public function testGetProperty()
    {
        $doc = new Document();
        $doc->store();

        $key = 'indexed';
        $value = 'true';

        $key2 = 'source';
        $value2 = 'sword';

        $doc->setProperty($key, $value);
        $doc->setProperty($key2, $value2);

        $this->assertEquals($value, $doc->getProperty($key));
        $this->assertEquals($value2, $doc->getProperty($key2));
    }

    public function testSetPropertyModelWithoutId()
    {
        $doc = new Document();

        $key = 'source';
        $value = 'sword';

        $this->setExpectedException(PropertiesException::class, 'Model ID is null');

        $doc->setProperty($key, $value);
    }

    public function testDeleteModelRemovesProperties()
    {
        $this->markTestIncomplete();
    }

    public function testDeleteDocumentDoesNotRemoveProperties()
    {
        $this->markTestIncomplete();
    }

    public function testDeleteDocumentPermanentlyRemovesProperties()
    {
        $this->markTestIncomplete();
    }
}
