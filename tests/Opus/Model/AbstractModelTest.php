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

use Opus\Common\Date;
use Opus\Common\Document;
use Opus\Common\Language;
use Opus\Common\Log;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\NotFoundException;
use Opus\Document as DocumentImpl;
use Opus\Identifier;
use Opus\Model\Dependent\Link\DocumentPerson;
use Opus\Model\Field;
use Opus\Model\Properties;
use Opus\Model\PropertiesException;
use Opus\Model\UnknownModelTypeException;
use Opus\Person;
use OpusTest\Model\Mock\AbstractModelMock;
use OpusTest\Model\Mock\ModelWithHiddenField;
use OpusTest\TestAsset\TestCase;
use Zend_Log;
use Zend_Validate_Alnum;
use Zend_Validate_NotEmpty;

use function array_pop;
use function date_format;

/**
 * Test cases for class Opus\Model\AbstractDb.
 *
 * @package Opus\Model
 * @category Tests
 * @group AbstractTest
 */
class AbstractModelTest extends TestCase
{
    /**
     * Test if describe() returns the fieldnames of all previosly added fields.
     */
    public function testDescribeReturnsAllFields()
    {
        $mock = new AbstractModelMock();
        $mock->addField(new Field('Field1'))
            ->addField(new Field('Field2'));
        $fields = $mock->describe();
        $this->assertEquals(['Id', 'Value', 'Field1', 'Field2'], $fields, 'Wrong set of field names returned.');
    }

    /**
     * Test if a field can be defined as internal thus it gets not reported by
     * describe().
     */
    public function testHideInternalField()
    {
        $model  = new ModelWithHiddenField(null);
        $result = $model->describe();
        $this->assertNotContains('HiddenField', $result, 'Field "HiddenField" gets reported.');
    }

    /**
     * Test if an internal field can not be set.
     */
    public function testSetCallToInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->expectException(ModelException::class);
        $model->setHiddenField('value');
    }

    /**
     * Test if an internal field can not be queried.
     */
    public function testGetCallToInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->expectException(ModelException::class);
        $model->getHiddenField();
    }

    /**
     * Test if an internal field can not be added to.
     */
    public function testAddCallToInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->expectException(ModelException::class);
        $model->addHiddenField();
    }

    /**
     * Test if an internal field can not be retrieved.
     */
    public function testGetInternalFieldThrowsException()
    {
        $model = new ModelWithHiddenField(null);
        $this->expectException(ModelException::class);
        $model->getField('HiddenField');
    }

    /**
     * Test if set calls can be done in a flunet interface style.
     * E.g. $model->setField(1)->setAnotherField('Foo');
     */
    public function testFluentInterfaceOnSetCall()
    {
        $model  = new AbstractModelMock();
        $result = $model->setValue('Value');
        $this->assertInstanceOf(
            AbstractModelMock::class,
            $result,
            'No fluent interface after set...() call.'
        );
    }

    /**
     * Test if a call to an unknown model method throws an exception
     * describing exactly this problem - not an "unknown field" exception.
     */
    public function testCallToUnknownMethodThrowsBadMethodCallException()
    {
        $this->expectException('BadMethodCallException');
        $model = new AbstractModelMock();
        $model->notAMethodOfThisClass();
    }

    /**
     * Test if a model can validate its field values.
     */
    public function testValidateModel()
    {
        $model = new AbstractModelMock();
        $model->setValue('FieldValue');

        $field1 = new Field('Field1');
        $field1->setValidator(new Zend_Validate_Alnum());

        $field2 = new Field('Field2');
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
     */
    public function testNotMandatoryFieldsValidateEvenIfEmpty()
    {
        $model = new AbstractModelMock();
        $model->getField('Value')->setMandatory(false);
        $model->setValue('');
        $this->assertTrue($model->isValid(), 'Validation should succeed.');
    }

    /**
     * Test if a model can validate its field values.
     */
    public function testValidationIsSkippedForFieldsWithNoValidator()
    {
        $model = new AbstractModelMock();
        $model->setValue('FieldValue');

        $field1 = new Field('Field1');
        $field1->setValidator(new Zend_Validate_Alnum());

        $model->addField($field1);

        // try successful validation
        $model->setField1('abc123');
        $this->assertTrue($model->isValid(), 'Validation should succeed.');
    }

    /**
     * Test if a validation error list can be retrieved.
     */
    public function testValidationErrorsAreObtainable()
    {
        $model = new AbstractModelMock();
        $model->getField('Value')->setMandatory(true);
        $model->getField('Value')->setValidator(new Zend_Validate_NotEmpty());
        // Model field "Value" is empty.
        $this->assertFalse($model->isValid(), 'Validation should fail.');
        $this->assertNotNull($model->getValidationErrors(), 'Validation errors are not set.');
    }

    /**
     * Test if the returned validation errors are in the form of an
     * associative array mapping fieldnamed to errors.
     */
    public function testValidationErrorsAreObtainablePerField()
    {
        $model = new AbstractModelMock();
        $model->getField('Value')->setMandatory(true);
        $model->getField('Value')->setValidator(new Zend_Validate_NotEmpty());
        $model->isValid();

        $errors = $model->getValidationErrors();
        $this->assertArrayHasKey('Value', $errors, 'Field "Value" is missing in error listing.');
    }

    /**
     * Test if a submodel gets validated by its supermodel when the containing
     * field is set to be mandatory.
     */
    public function testValidationOfSubmodelIfStoredInMandatoryField()
    {
        $submodel = $this->getMockBuilder(AbstractModelMock::class)->getMock();
        $model    = new AbstractModelMock();
        $field    = new Field('Submodel');
        $field->setValueModelClass(AbstractModelMock::class)
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
     */
    public function testValidationOfSubmodelsInMultivalueFields()
    {
        $submodels[] = $this->getMockBuilder(AbstractModelMock::class)->getMock();
        $submodels[] = $this->getMockBuilder(AbstractModelMock::class)->getMock();
        $submodels[] = $this->getMockBuilder(AbstractModelMock::class)->getMock();

        // expect calls to isValid
        foreach ($submodels as $submodel) {
            $submodel->expects($this->once())
                ->method('isValid');
        }

        $model = new AbstractModelMock();
        $field = new Field('Submodels');
        $field->setValueModelClass(AbstractModelMock::class)
            ->setMandatory(true)
            ->setMultiplicity('*')
            ->setValue($submodels);
        $model->addField($field);

        // trigger calls
        $model->isValid();
    }

    /**
     * Test if a submodel validation fault triggers a supermodels validation fault.
     */
    public function testValidationFailsIfSubmodelValidationDoesSo()
    {
        $submodel = $this->getMockBuilder(AbstractModelMock::class)->getMock();
        $model    = new AbstractModelMock();
        $field    = new Field('Submodel');
        $field->setValueModelClass(AbstractModelMock::class)
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
        $doc   = Document::new();
        $field = $doc->getField('Type');
        $this->assertEquals(DocumentImpl::class, $field->getOwningModelClass());
    }

    /**
     * Test if property owningModelClass gets set for field of Opus\Person.
     */
    public function testGetOwningModelClassForFieldOfPerson()
    {
        $person = new Person();
        $field  = $person->getField('FirstName');
        $this->assertEquals(Person::class, $field->getOwningModelClass());
    }

    /**
     * Test if property owningModelClass gets set for link class.
     */
    public function testGetOwningModelClassForFieldOfDocumentPerson()
    {
        $doc    = Document::new();
        $person = new Person();
        $doc->addPerson($person);
        $persons = $doc->getPerson();
        $person  = $persons[0];

        // Test for field that belongs to Opus\Person
        $field = $person->getField('FirstName');
        $this->assertEquals(Person::class, $field->getOwningModelClass());

        // Test for field that belongs to Opus\Model\Dependent\Link\DocumentPerson
        $field = $person->getField('SortOrder');
        $this->assertEquals(DocumentPerson::class, $field->getOwningModelClass());
    }

    public function testGetFieldForUnkownField()
    {
        $doc = Document::new();
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
        $logger = new Zend_Log();

        $model = new AbstractModelMock();

        $model->setLogger($logger);

        $this->assertEquals($logger, $model->getLogger());
        $this->assertNotEquals(Log::get(), $model->getLogger());
    }

    public function testToArrayWithPerson()
    {
        $doc = Document::new();

        $person = new Person();
        $person->setLastName('Testy');

        $link = $doc->addPerson($person);
        $link->setRole('author');

        $doc = Document::get($doc->store());

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
        $doc = Document::new();

        $doc->updateFromArray([
            'Type'    => 'article',
            'Edition' => 'First',
        ]);

        $this->assertEquals('article', $doc->getType());
        $this->assertEquals('First', $doc->getEdition());
    }

    public function testUpdateFromArrayComplexValue()
    {
        $doc = Document::new();

        $doc->updateFromArray([
            'Type'         => 'article',
            'PersonAuthor' => [
                ['LastName' => 'Tester'],
            ],
        ]);

        $this->assertEquals('article', $doc->getType());

        $authors = $doc->getPersonAuthor();

        $this->assertCount(1, $authors);

        $author = $authors[0];

        $this->assertEquals('Tester', $author->getLastName());
    }

    public function testUpdateFromArrayMultipleComplexValues()
    {
        $doc = Document::new();

        $doc->updateFromArray([
            'Type'         => 'article',
            'PersonAuthor' => [
                ['LastName' => 'author1'],
                ['LastName' => 'author2', 'Email' => 'author@example.org'],
            ],
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
            'Year'  => 2018,
            'Month' => 10,
            'Day'   => 12,
        ]);

        $this->assertInstanceOf(Date::class, $date);
        $this->assertEquals('2018-10-12', $date->__toString());
    }

    public function testGetModelType()
    {
        $model = Language::new();

        $this->expectException(
            UnknownModelTypeException::class,
            'Properties not supported for Opus\Language'
        );

        $model->getModelType();
    }

    public function testSetProperty()
    {
        $doc = Document::new();
        $doc->store();

        $key   = 'indexed';
        $value = 'true';

        $doc->setProperty($key, $value);

        $this->assertEquals($value, $doc->getProperty($key));
    }

    public function testGetProperty()
    {
        $doc = Document::new();
        $doc->store();

        $key   = 'indexed';
        $value = 'true';

        $key2   = 'source';
        $value2 = 'sword';

        $doc->setProperty($key, $value);
        $doc->setProperty($key2, $value2);

        $this->assertEquals($value, $doc->getProperty($key));
        $this->assertEquals($value2, $doc->getProperty($key2));
    }

    public function testSetPropertyModelWithoutId()
    {
        $doc = Document::new();

        $key   = 'source';
        $value = 'sword';

        $this->expectException(PropertiesException::class, 'Model ID is null');

        $doc->setProperty($key, $value);
    }

    public function testDeletingModelRemovesProperties()
    {
        $doc        = Document::new();
        $identifier = Identifier::new();
        $identifier->setType('isbn');
        $identifier->setValue('testvalue');
        $doc->addIdentifier($identifier);

        $docId        = $doc->store();
        $identifierId = $identifier->getId();

        $identifier->setProperty('registered', 'true');

        $propertiesService = new Properties();

        $properties = $propertiesService->getProperties($identifier);

        $this->assertCount(1, $properties);
        $this->assertEquals([
            'registered' => 'true',
        ], $properties);

        $doc->setIdentifier([]); // remove/delete identifier
        $doc->store();

        try {
            Identifier::get($identifierId);
            $this->fail('identifier was not deleted');
        } catch (NotFoundException $ex) {
            // document was deleted - everything is fine
        }

        $properties = $propertiesService->getProperties($identifierId, $identifier->getModelType());

        $this->assertCount(0, $properties);
    }

    public function testSettingDocumentDeletedDoesNotRemoveProperties()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $doc->setProperty('prop1', 'value1');
        $doc->setProperty('prop2', 'value2');

        $propertiesService = new Properties();

        $properties = $propertiesService->getProperties($doc);

        $this->assertCount(2, $properties);
        $this->assertEquals([
            'prop1' => 'value1',
            'prop2' => 'value2',
        ], $properties);

        $doc->deleteDocument();

        $doc = Document::get($docId);

        $this->assertEquals(Document::STATE_DELETED, $doc->getServerState());

        $properties = $propertiesService->getProperties($doc);

        $this->assertCount(2, $properties);
        $this->assertEquals([
            'prop1' => 'value1',
            'prop2' => 'value2',
        ], $properties);
    }

    public function testDeletingDocumentRemovesProperties()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $doc->setProperty('prop1', 'value1');
        $doc->setProperty('prop2', 'value2');

        $propertiesService = new Properties();

        $properties = $propertiesService->getProperties($doc);

        $this->assertCount(2, $properties);
        $this->assertEquals([
            'prop1' => 'value1',
            'prop2' => 'value2',
        ], $properties);

        $doc->delete();

        try {
            $doc = Document::get($docId);
            $this->fail('document was not deleted');
        } catch (NotFoundException $ex) {
            // document was deleted - everything is fine
        }

        $properties = $propertiesService->getProperties($docId, $doc->getModelType());

        $this->assertCount(0, $properties);
    }
}
