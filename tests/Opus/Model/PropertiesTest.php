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
 * @copyright   Copyright (c) 2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model;

use InvalidArgumentException;
use Opus\Common\Identifier;
use Opus\Document;
use Opus\Model\Properties;
use Opus\Model\PropertiesException;
use Opus\Model\UnknownModelTypeException;
use Opus\Model\UnknownPropertyKeyException;
use Opus\Person;
use Opus\Version;
use OpusTest\TestAsset\TestCase;
use ReflectionClass;
use Zend_Db_Adapter_Exception;

/**
 * TODO test database adapter problems - is the exception caught and another thrown?
 */
class PropertiesTest extends TestCase
{
    /** @var Properties */
    protected $properties;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, [
            'documents',
            'model_properties',
            'model_types',
            'propertykeys',
            'persons',
            'document_identifiers',
            'link_persons_documents',
        ]);

        $this->properties = new Properties();
    }

    public function testRegisterType()
    {
        $type = 'document';

        $properties = $this->properties;

        $types = $properties->getTypes();

        $this->assertNotContains($type, $types);

        $properties->registerType($type);

        $types = $properties->getTypes();

        $this->assertCount(1, $types);
        $this->assertContains($type, $types);

        $type2 = 'person';

        $properties->registerType($type2);

        $types = $properties->getTypes();

        $this->assertCount(2, $types);
        $this->assertContains($type, $types);
        $this->assertContains($type2, $types);
    }

    public function testRegisterTypeNotDuplicatedIfRegisteredTwice()
    {
        $type = 'person';

        $properties = $this->properties;

        $properties->registerType($type);
        $properties->registerType($type);

        $types = $properties->getTypes();

        $this->assertCount(1, $types);
        $this->assertContains($type, $types);
    }

    public function testUnregisterType()
    {
        $type  = 'identifier';
        $type2 = 'document';

        $properties = $this->properties;

        $properties->registerType($type);
        $properties->registerType($type2);

        $types = $properties->getTypes();

        $this->assertContains($type, $types);
        $this->assertContains($type2, $types);

        $properties->unregisterType($type);

        $types = $properties->getTypes();

        $this->assertNotContains($type, $types);
        $this->assertContains($type2, $types);
    }

    public function testUnregisterTypeUnknownType()
    {
        $type        = 'document';
        $unknownType = 'patent';

        $properties = $this->properties;

        $properties->registerType($type);

        $this->expectException(UnknownModelTypeException::class, $unknownType);

        $properties->unregisterType($unknownType);
    }

    public function testUnregisterTypeRemovesModelProperties()
    {
        $properties = $this->properties;

        $docType    = 'document';
        $key        = 'testkey';
        $personType = 'person';

        $properties->registerType($docType);
        $properties->registerType($personType);
        $properties->registerKey($key);

        $doc = Document::new();
        $doc->store();

        $person = Person::new();
        $person->setLastName('Doe');
        $person->store();

        $properties->setProperty($doc, $key, 'docvalue');
        $properties->setProperty($person, $key, 'personvalue');

        $this->assertEquals([
            $key => 'docvalue',
        ], $properties->getProperties($doc));

        $this->assertEquals([
            $key => 'personvalue',
        ], $properties->getProperties($person));

        $properties->unregisterType($docType);

        $this->assertEquals([
            $key => 'personvalue',
        ], $properties->getProperties($person));

        // register type again so it can be checked if there are properties left
        $properties->registerType($docType);

        $this->assertEquals([], $properties->getProperties($doc));
    }

    public function testGetTypes()
    {
        $properties = $this->properties;

        $properties->registerType('document');
        $properties->registerType('person');
        $properties->registerType('identifier');

        $types = $properties->getTypes();

        $this->assertCount(3, $types);
        $this->assertContains('document', $types);
        $this->assertContains('person', $types);
        $this->assertContains('identifier', $types);
    }

    public function testGetTypesReturnsEmptyArray()
    {
        $properties = $this->properties;

        $types = $properties->getTypes();

        $this->assertIsArray($types);
        $this->assertCount(0, $types);
    }

    public function testRegisterKey()
    {
        $key = 'extracted';

        $properties = $this->properties;

        $keys = $properties->getKeys();

        $this->assertNotContains($key, $keys);

        $properties->registerKey($key);

        $keys = $properties->getKeys();

        $this->assertCount(1, $keys);
        $this->assertContains($key, $keys);

        $key2 = 'source';

        $properties->registerKey($key2);

        $keys = $properties->getKeys();

        $this->assertCount(2, $keys);
        $this->assertContains($key, $keys);
        $this->assertContains($key2, $keys);
    }

    public function testRegisterKeyNotDuplicatedIfRegisteredTwice()
    {
        $key = 'extracted';

        $properties = $this->properties;

        $properties->registerKey($key);
        $properties->registerKey($key);

        $keys = $properties->getKeys();

        $this->assertCount(1, $keys);
        $this->assertContains($key, $keys);
    }

    /**
     * @return string[][]
     */
    public function validKeyProvider()
    {
        return [
            ['test'],
            ['t'],
            ['test12'],
            ['test.key'],
            ['key12.23'],
            ['test.key.with.dots'],
        ];
    }

    /**
     * @param string $key
     * @throws Zend_Db_Adapter_Exception
     * @dataProvider validKeyProvider
     */
    public function testRegisterKeyValidKey($key)
    {
        $properties = $this->properties;

        $properties->registerKey($key);

        $this->assertContains($key, $properties->getKeys());
    }

    /**
     * @return string[][]
     */
    public function invalidKeyProvider()
    {
        return [
            ['123'],
            ['test-key'],
            ['test_key'],
            ['test@key'],
            [''],
            ['2test'],
            ['test..key'],
            ['test.key.'],
            ['test.key.with.dots.'],
        ];
    }

    /**
     * @param string $key
     * @throws Zend_Db_Adapter_Exception
     * @dataProvider invalidKeyProvider
     */
    public function testRegisterKeyInvalidKey($key)
    {
        $properties = $this->properties;

        $this->expectException(InvalidArgumentException::class, $key);

        $properties->registerKey($key);
    }

    public function testUnregisterKey()
    {
        $key  = 'extracted';
        $key2 = 'source';

        $properties = $this->properties;

        $properties->registerKey($key);
        $properties->registerKey($key2);

        $keys = $properties->getKeys();

        $this->assertCount(2, $keys);
        $this->assertContains($key, $keys);
        $this->assertContains($key2, $keys);

        $properties->unregisterKey($key);

        $keys = $properties->getKeys();

        $this->assertCount(1, $keys);
        $this->assertNotContains($key, $keys);
        $this->assertContains($key2, $keys);
    }

    public function testUnregisterKeyRemovesModelProperties()
    {
        $key  = 'extracted';
        $key2 = 'source';

        $properties = $this->properties;

        $properties->registerType('document');
        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = Document::new();
        $model->store();

        $properties->setProperty($model, $key, 'yes');
        $properties->setProperty($model, $key2, 'sword');

        $this->assertEquals([
            $key  => 'yes',
            $key2 => 'sword',
        ], $properties->getProperties($model));

        $properties->unregisterKey($key);

        $this->assertEquals([
            $key2 => 'sword',
        ], $properties->getProperties($model));
    }

    public function testUnregisterUnknownKey()
    {
        $properties = $this->properties;

        $key = 'unknown';

        $this->expectException(UnknownPropertyKeyException::class, $key);

        $properties->unregisterKey($key);
    }

    public function testGetKeys()
    {
        $properties = $this->properties;

        $properties->registerKey('key1');
        $properties->registerKey('key2');
        $properties->registerKey('key3');

        $keys = $properties->getKeys();

        $this->assertCount(3, $keys);
        $this->assertContains('key1', $keys);
        $this->assertContains('key2', $keys);
        $this->assertContains('key3', $keys);
    }

    public function testGetKeysReturnsEmptyArray()
    {
        $properties = $this->properties;

        $keys = $properties->getKeys();

        $this->assertIsArray($keys);
        $this->assertCount(0, $keys);
    }

    public function testGetProperties()
    {
        $properties = $this->properties;

        $properties->registerType('document');
        $properties->registerKey('key1');
        $properties->registerKey('key2');

        $model = Document::new();
        $model = Document::get($model->store());

        $model2 = Document::new();
        $model2->store();

        $properties->setProperty($model, 'key1', 'value1');
        $properties->setProperty($model, 'key2', 'value2');
        $properties->setProperty($model2, 'key1', 'value1b');

        $props = $properties->getProperties($model);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
        ], $props);
    }

    public function testGetPropertiesUnknownModel()
    {
        $properties = $this->properties;

        $properties->registerType('document');

        $model = Document::new();
        $model->store();

        $props = $properties->getProperties($model);

        $this->assertIsArray($props);
        $this->assertCount(0, $props);
    }

    public function testGetPropertiesWithIdAndType()
    {
        $this->resetDatabase();

        $doc        = Document::new();
        $identifier = Identifier::new();
        $identifier->setType('isbn');
        $identifier->setValue('testisbn');
        $doc->addIdentifier($identifier);
        $docId        = $doc->store();
        $identifierId = $identifier->getId();

        $this->assertEquals($docId, $identifierId); // both are the first objects of their type

        $properties = $this->properties;
        $properties->registerType('document');
        $properties->registerType('identifier');

        $doc->setProperty('key1', 'value1');
        $doc->setProperty('key2', 'value2');
        $identifier->setProperty('key1', 'idValue1');

        $props = $properties->getProperties($docId, 'document');

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
        ], $props);

        $props = $properties->getProperties($identifierId, 'identifier');

        $this->assertEquals([
            'key1' => 'idValue1',
        ], $props);
    }

    public function testGetPropertiesWithIdAndBadType()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $properties = $this->properties;
        $properties->registerType('document');

        $doc->setProperty('key1', 'value1');
        $doc->setProperty('key2', 'value2');

        $this->expectException(UnknownModelTypeException::class);

        $properties->getProperties($docId, 'identifier');
    }

    public function testGetPropertiesWithUnknownId()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $properties = $this->properties;
        $properties->registerType('document');

        $doc->setProperty('key1', 'value1');
        $doc->setProperty('key2', 'value2');

        $props = $properties->getProperties($docId + 1, 'document');

        $this->assertIsArray($props);
        $this->assertEmpty($props);
    }

    public function testGetPropertiesWithBadId()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $properties = $this->properties;
        $properties->registerType('document');

        $doc->setProperty('key1', 'value1');
        $doc->setProperty('key2', 'value2');

        $this->expectException(InvalidArgumentException::class);

        $properties->getProperties(1.5, 'document');
    }

    public function testSetProperty()
    {
        $properties = $this->properties;

        $model = Document::new();
        $model->store();

        $model2 = Document::new();
        $model2->store();

        $key    = 'extracted';
        $value  = 'error';
        $value2 = 'value2';

        $properties->registerType('document');
        $properties->registerKey($key);

        $this->assertNull($properties->getProperty($model, $key));
        $this->assertNull($properties->getProperty($model2, $key));

        $properties->setProperty($model, $key, $value);
        $properties->setProperty($model2, $key, $value2);

        $this->assertEquals($value, $properties->getProperty($model, $key));
        $this->assertEquals($value2, $properties->getProperty($model2, $key));
    }

    public function testSetPropertyModelNull()
    {
        $properties = $this->properties;

        $key = 'testkey';

        $properties->registerKey($key);

        $this->expectException(InvalidArgumentException::class, 'Model argument must not be null');

        $properties->setProperty(null, $key, 'testvalue');
    }

    public function testSetPropertyKeyNull()
    {
        $properties = $this->properties;

        $properties->registerType('document');

        $model = Document::new();
        $model->store();

        $this->expectException(InvalidArgumentException::class, 'Key argument must not be null');

        $properties->setProperty($model, null, 'testvalue');
    }

    public function testSetPropertyModelAndKeyNull()
    {
        $properties = $this->properties;

        $this->expectException(InvalidArgumentException::class, 'Model argument must not be null');

        $properties->setProperty(null, null, 'testvalue');
    }

    public function testSetPropertyUnknownModel()
    {
        $properties = $this->properties;

        $key = 'testKey';

        $properties->registerKey($key);

        $model = new Version(); // Not a \Opus\Model\AbstractModel class

        $this->expectException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus\Model\PropertySupportInterface'
        );

        $properties->setProperty($model, $key, 'testvalue');
    }

    public function testSetPropertyUnknownKey()
    {
        $properties = $this->properties;

        $properties->registerType('document');
        $properties->registerKey('knownkey');

        $model = Document::new();
        $model->store();

        $this->expectException(UnknownPropertyKeyException::class, 'unknownKey');

        $properties->setProperty($model, 'unknownKey', 'testvalue');
    }

    public function testSetPropertyUnknownModelAndUnknownKey()
    {
        $properties = $this->properties;

        $key = 'testKey'; // not registered

        $model = new Version(); // Not a \Opus\Model\AbstractModel class

        $this->expectException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus\Model\PropertySupportInterface'
        );

        $properties->setProperty($model, $key, 'testvalue');
    }

    public function testSetPropertyForModelWithoutId()
    {
        $properties = $this->properties;

        $key = 'testkey';

        $model = Document::new();

        $properties->registerType('document');
        $properties->registerKey($key);

        $this->expectException(PropertiesException::class, 'Model ID is null');

        $properties->setProperty($model, $key, 'testvalue');
    }

    public function testGetProperty()
    {
        $properties = $this->properties;

        $key    = 'testkey';
        $value  = 'testvalue';
        $value2 = 'testvalue2';

        $model = Document::new();
        $model->store();

        $model2 = Document::new();
        $model2->store();

        $properties->registerType('document');
        $properties->registerKey($key);
        $properties->registerKey('key2');

        $properties->setProperty($model, $key, $value);
        $properties->setProperty($model, 'key2', 'value2');
        $properties->setProperty($model2, $key, $value2);

        $this->assertEquals($value, $properties->getProperty($model, $key));
        $this->assertEquals($value2, $properties->getProperty($model2, $key));
    }

    public function testGetPropertyNotSet()
    {
        $properties = $this->properties;

        $key  = 'testkey';
        $key2 = 'key2';

        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = Document::new();
        $model->store();

        $properties->registerType('document');
        $properties->setProperty($model, $key2, 'testvalue');

        $this->assertNull($properties->getProperty($model, $key));
    }

    public function testGetPropertyUnknownModel()
    {
        $properties = $this->properties;

        $key = 'testkey';

        $properties->registerType('document');
        $properties->registerKey($key);

        $model = Document::new();
        $model->store();

        $this->assertNull($properties->getProperty($model, $key));
    }

    public function testGetPropertyUnknownKey()
    {
        $properties = $this->properties;

        $properties->registerType('document');

        $model = Document::new();
        $model->store();

        $unknownKey = 'unknownKey';

        $this->expectException(UnknownPropertyKeyException::class, $unknownKey);

        $properties->getProperty($model, $unknownKey);
    }

    public function testGetPropertyUnsupportedModel()
    {
        $properties = $this->properties;

        $key = 'testKey';

        $properties->registerKey($key);

        $model = new Version(); // Does not implement interface

        $this->expectException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus\Model\PropertySupportInterface'
        );

        $properties->getProperty($model, $key);
    }

    public function testGetPropertyForModelWithoutId()
    {
        $properties = $this->properties;

        $key = 'testkey';

        $model = Document::new();

        $properties->registerType('document');
        $properties->registerKey($key);

        $this->expectException(PropertiesException::class, 'Model ID is null');

        $properties->getProperty($model, $key);
    }

    public function testRemoveProperties()
    {
        $properties = $this->properties;

        $properties->registerType('document');
        $properties->registerKey('key1');
        $properties->registerKey('key2');

        $model = Document::new();
        $model->store();

        $model2 = Document::new();
        $model2->store();

        $properties->setProperty($model, 'key1', 'value1');
        $properties->setProperty($model, 'key2', 'value2');
        $properties->setProperty($model2, 'key1', 'value1b');

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2',
        ], $properties->getProperties($model));

        $properties->removeProperties($model);

        $this->assertEquals([], $properties->getProperties($model));

        // check that properties of other model did not get removed
        $this->assertEquals([
            'key1' => 'value1b',
        ], $properties->getProperties($model2));
    }

    public function testRemovePropertiesUnknownModel()
    {
        $properties = $this->properties;

        $properties->registerType('document');

        $model = Document::new();
        $model->store();

        // model does not have properties (is not known in table) - nothing should happen
        $properties->removeProperties($model);

        $this->assertEquals([], $properties->getProperties($model));
    }

    public function testRemovePropertiesUnsupportedModel()
    {
        $properties = $this->properties;

        $model = new Version();

        $this->expectException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus\Model\PropertySupportInterface'
        );

        $properties->getProperties($model);
    }

    public function testRemovePropertiesForModelWithoutId()
    {
        $properties = $this->properties;

        $model = Document::new();

        $properties->registerType('document');

        $this->expectException(PropertiesException::class, 'Model ID is null');

        $properties->removeProperties($model);
    }

    public function testRemovePropertiesWithModelIdAndType()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $doc->setProperty('key1', 'value1');
        $doc->setProperty('key2', 'value2');

        $this->assertEquals('value1', $doc->getProperty('key1'));
        $this->assertEquals('value2', $doc->getProperty('key2'));

        $properties = $this->properties;
        $properties->removeProperties($docId, $doc->getModelType());

        $this->assertNull($doc->getProperty('key1'));
        $this->assertNull($doc->getProperty('key2'));
    }

    public function testRemovePropertiesWithBadModelIdAndType()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $doc->setProperty('key1', 'value1');
        $doc->setProperty('key2', 'value2');

        $this->assertEquals('value1', $doc->getProperty('key1'));
        $this->assertEquals('value2', $doc->getProperty('key2'));

        $this->expectException(InvalidArgumentException::class);

        $properties = $this->properties;
        $properties->removeProperties(1.5, $doc->getModelType());
    }

    public function testRemovePropertiesWithModelIdAndBadType()
    {
        $doc   = Document::new();
        $docId = $doc->store();

        $doc->setProperty('key1', 'value1');
        $doc->setProperty('key2', 'value2');

        $this->assertEquals('value1', $doc->getProperty('key1'));
        $this->assertEquals('value2', $doc->getProperty('key2'));

        $this->expectException(UnknownModelTypeException::class);

        $properties = $this->properties;
        $properties->removeProperties($docId, 'icecream');
    }

    public function testRemoveProperty()
    {
        $properties = $this->properties;

        $key  = 'testkey';
        $key2 = 'key2';

        $properties->registerType('document');
        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = Document::new();
        $model->store();

        $model2 = Document::new();
        $model2->store();

        $properties->setProperty($model, $key, 'testvalue');
        $properties->setProperty($model, $key2, 'value2');
        $properties->setProperty($model2, $key, 'testvalue2');

        $this->assertEquals([
            $key  => 'testvalue',
            $key2 => 'value2',
        ], $properties->getProperties($model));

        $properties->removeProperty($model, $key);

        $this->assertEquals([
            $key2 => 'value2',
        ], $properties->getProperties($model));

        // check that property did not get remove from other model
        $this->assertEquals([
            $key => 'testvalue2',
        ], $properties->getProperties($model2));
    }

    public function testSetPropertyNullRemovesProperty()
    {
        $properties = $this->properties;

        $key  = 'testkey';
        $key2 = 'key2';

        $properties->registerType('document');
        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = Document::new();
        $model->store();

        $properties->setProperty($model, $key, 'testvalue');
        $properties->setProperty($model, $key2, 'value2');

        $this->assertEquals([
            $key  => 'testvalue',
            $key2 => 'value2',
        ], $properties->getProperties($model));

        $properties->setProperty($model, $key, null);

        $this->assertEquals([
            $key2 => 'value2',
        ], $properties->getProperties($model));
    }

    public function testRemovePropertyUnknownModel()
    {
        $properties = $this->properties;

        $properties->registerType('document');

        $key = 'testkey';

        $properties->registerKey($key);

        $model = Document::new();
        $model->store();

        $model2 = Document::new();
        $model2->store();

        $properties->setProperty($model2, $key, 'testvalue');

        // noting should happen, so it isn't clear what to assert
        $properties->removeProperty($model, $key);

        // assert that the key has not been removed from other model
        $this->assertEquals('testvalue', $properties->getProperty($model2, $key));
    }

    public function testRemovePropertyUnsupportedModel()
    {
        $properties = $this->properties;

        $key = 'testkey';

        $properties->registerKey($key);

        $model = new Version();

        $this->expectException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus\Model\PropertySupportInterface'
        );

        $properties->removeProperty($model, $key);
    }

    public function testRemovePropertyUnknownKey()
    {
        $properties = $this->properties;

        $unknownKey = 'testkey';

        $properties->registerType('document');

        $model = Document::new();
        $model->store();

        $this->expectException(UnknownPropertyKeyException::class, $unknownKey);

        $properties->removeProperty($model, $unknownKey);
    }

    public function testRemovePropertyModelWithoutId()
    {
        $properties = $this->properties;

        $key = 'testkey';

        $model = Document::new();

        $properties->registerType('document');
        $properties->registerKey($key);

        $this->expectException(PropertiesException::class, 'Model ID is null');

        $properties->removeProperty($model, $key);
    }

    /**
     * TODO Is this functionality necessary?
     */
    public function testFindModelsTypeNull()
    {
        $properties = $this->properties;

        $key   = 'testkey';
        $value = 'testvalue';

        $properties->registerType('document');
        $properties->registerKey($key);

        $expected = [];

        $model   = Document::new();
        $modelId = $model->store();
        $properties->setProperty($model, $key, $value);

        $result = $properties->findModels($key, $value);

        // var_dump($result);

        $this->markTestIncomplete();
    }

    public function testFindModelsWithType()
    {
        $properties = $this->properties;

        $key   = 'testkey';
        $value = 'testvalue';

        $properties->registerType('document');
        $properties->registerKey($key);

        $expected = [];

        $model   = Document::new();
        $modelId = $model->store();
        $properties->setProperty($model, $key, $value);

        $result = $properties->findModels($key, $value, $model->getModelType());

        // var_dump($result);

        $this->markTestIncomplete();
    }

    public function testRenameKey()
    {
        $properties = $this->properties;

        $oldKey = 'oldkey';
        $newKey = 'newkey';

        $properties->registerType('document');
        $properties->registerKey($oldKey);

        $this->assertEquals([
            $oldKey,
        ], $properties->getKeys());

        $model = Document::new();
        $model->store();

        $properties->setProperty($model, $oldKey, 'testvalue');

        $this->assertEquals([
            $oldKey => 'testvalue',
        ], $properties->getProperties($model));

        $properties->renameKey($oldKey, $newKey);

        $this->assertEquals([
            $newKey,
        ], $properties->getKeys());

        $this->assertEquals([
            $newKey => 'testvalue',
        ], $properties->getProperties($model));
    }

    public function testRenameKeyInvalidNewKey()
    {
        $properties = $this->properties;

        $oldKey     = 'oldkey';
        $invalidKey = 'invalid..key';

        $properties->registerType('document');
        $properties->registerKey($oldKey);

        $this->assertEquals([
            $oldKey,
        ], $properties->getKeys());

        $model = Document::new();
        $model->store();

        $properties->setProperty($model, $oldKey, 'testvalue');

        $this->assertEquals([
            $oldKey => 'testvalue',
        ], $properties->getProperties($model));

        $this->expectException(InvalidArgumentException::class, $invalidKey);

        $properties->renameKey($oldKey, $invalidKey);
    }

    public function testGetKeyId()
    {
        $reflect = new ReflectionClass(Properties::class);
        $method  = $reflect->getMethod('getKeyId');
        $method->setAccessible(true);

        $properties = $this->properties;

        $key1 = 'key1';
        $key2 = 'key2';

        $properties->registerKey($key1);
        $properties->registerKey($key2);

        $result = $method->invoke($properties, $key1);
        $this->assertEquals(1, $result);

        $result = $method->invoke($properties, $key2);
        $this->assertEquals(2, $result);
    }

    public function testIsAutoRegisterTypeEnabled()
    {
        $properties = $this->properties;

        $this->assertFalse($properties->isAutoRegisterTypeEnabled());
    }

    public function testSetAutoRegisterTypeEnabled()
    {
        $properties = $this->properties;

        $properties->setAutoRegisterTypeEnabled(true);

        $this->assertTrue($properties->isAutoRegisterTypeEnabled());
    }

    public function testSetAutoRegisterTypeEnabledWithNullArgument()
    {
        $properties = $this->properties;

        $this->expectException(InvalidArgumentException::class, 'Argument must not be null');

        $properties->setAutoRegisterTypeEnabled(null);
    }

    public function testSetAutoRegisterTypeEnabledWithBadArgument()
    {
        $properties = $this->properties;

        $this->expectException(InvalidArgumentException::class, 'Argument must be boolean');

        $properties->setAutoRegisterTypeEnabled('123');
    }

    public function testIsAutoRegisterKeyEnabled()
    {
        $properties = $this->properties;

        $this->assertFalse($properties->isAutoRegisterKeyEnabled());
    }

    public function testSetAutoRegisterKeyEnabled()
    {
        $properties = $this->properties;

        $properties->setAutoRegisterKeyEnabled(true);

        $this->assertTrue($properties->isAutoRegisterKeyEnabled());
    }

    public function testSetAutoRegisterKeyEnabledWithNullArgument()
    {
        $properties = $this->properties;

        $this->expectException(InvalidArgumentException::class, 'Argument must not be null');

        $properties->setAutoRegisterKeyEnabled(null);
    }

    public function testSetAutoRegisterKeyEnabledWithBadArgument()
    {
        $properties = $this->properties;

        $this->expectException(InvalidArgumentException::class, 'Argument must be boolean');

        $properties->setAutoRegisterKeyEnabled('test');
    }

    public function testSetPropertyAutoRegisterTypeEnabled()
    {
        $properties = $this->properties;
        $properties->setAutoRegisterTypeEnabled(true);

        $key   = 'testkey';
        $value = 'testvalue';

        $properties->registerKey($key);

        $model = Document::new();
        $model->store();

        $this->assertEquals([], $properties->getTypes());

        $properties->setProperty($model, $key, $value);

        $this->assertEquals($value, $properties->getProperty($model, $key));

        $this->assertEquals([
            'document',
        ], $properties->getTypes());
    }

    public function testSetPropertyAutoRegisterKeyEnabled()
    {
        $properties = $this->properties;
        $properties->setAutoRegisterKeyEnabled(true);

        $key   = 'testkey';
        $value = 'testvalue';

        $properties->registerType('document');

        $model = Document::new();
        $model->store();

        $this->assertEquals([], $properties->getKeys());

        $properties->setProperty($model, $key, $value);

        $this->assertEquals($value, $properties->getProperty($model, $key));

        $this->assertEquals([
            $key,
        ], $properties->getKeys());
    }

    public function testSetPropertyAutoRegisterEnabled()
    {
        $properties = $this->properties;

        $properties->setAutoRegisterTypeEnabled(true);
        $properties->setAutoRegisterKeyEnabled(true);

        $key   = 'testkey';
        $value = 'testvalue';

        $model = Document::new();
        $model->store();

        $this->assertEquals([], $properties->getKeys());
        $this->assertEquals([], $properties->getTypes());

        $properties->setProperty($model, $key, $value);

        $this->assertEquals($value, $properties->getProperty($model, $key));

        $this->assertEquals([
            $key,
        ], $properties->getKeys());

        $this->assertEquals([
            'document',
        ], $properties->getTypes());
    }
}
