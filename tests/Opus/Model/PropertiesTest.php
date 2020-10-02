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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Class Opus_Model_PropertiesTest
 *
 * TODO test database adapter problems - is the exception caught and another thrown?
 */
class Opus_Model_PropertiesTest extends TestCase
{

    public function testRegisterType()
    {
        $type = 'document';

        $properties = new Opus_Model_Properties();

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

        $properties = new Opus_Model_Properties();

        $properties->registerType($type);
        $properties->registerType($type);

        $types = $properties->getTypes();

        $this->assertCount(1, $types);
        $this->assertContains($type, $types);
    }

    public function testUnregisterType()
    {
        $type = 'identifier';
        $type2 = 'document';

        $properties = new Opus_Model_Properties();

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
        $type = 'document';
        $unknownType = 'patent';

        $properties = new Opus_Model_Properties();

        $properties->registerType($type);

        $this->setExpectedException(Opus_Model_UnknownModelTypeException::class, $unknownType);

        $properties->unregisterType($unknownType);
    }

    public function testUnregisterTypeRemovesModelProperties()
    {
        $properties = new Opus_Model_Properties();

        $docType = 'document';
        $key = 'testkey';
        $personType = 'person';

        $properties->registerType($docType);
        $properties->registerType($personType);
        $properties->registerKey($key);

        $doc = new Opus_Document();
        $doc->store();

        $person = new Opus_Person();
        $person->setLastName('Doe');
        $person->store();

        $properties->setProperty($doc, $key, 'docvalue');
        $properties->setProperty($person, $key, 'personvalue');

        $this->assertEquals([
            $key => 'docvalue'
        ], $properties->getProperties($doc));

        $this->assertEquals([
            $key => 'personvalue'
        ], $properties->getProperties($person));

        $properties->unregisterType($docType);

        $this->assertEquals([
            $key => 'personvalue'
        ], $properties->getProperties($person));

        // register type again so it can be checked if there are properties left
        $properties->registerType($docType);

        $this->assertEquals([], $properties->getProperties($doc));
    }

    public function testGetTypes()
    {
        $properties = new Opus_Model_Properties();

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
        $properties = new Opus_Model_Properties();

        $types = $properties->getTypes();

        $this->assertInternalType('array', $types);
        $this->assertCount(0, $types);
    }

    public function testRegisterKey()
    {
        $key = 'extracted';

        $properties = new Opus_Model_Properties();

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

        $properties = new Opus_Model_Properties();

        $properties->registerKey($key);
        $properties->registerKey($key);

        $keys = $properties->getKeys();

        $this->assertCount(1, $keys);
        $this->assertContains($key, $keys);
    }

    public function validKeyProvider()
    {
        return [
            ['test'],
            ['t'],
            ['test12'],
            ['test.key'],
            ['key12.23'],
            ['test.key.with.dots']
        ];
    }

    /**
     * @param $key
     * @throws Zend_Db_Adapter_Exception
     * @dataProvider validKeyProvider
     */
    public function testRegisterKeyValidKey($key)
    {
        $properties = new Opus_Model_Properties();

        $properties->registerKey($key);

        $this->assertContains($key, $properties->getKeys());
    }

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
            ['test.key.with.dots.']
        ];
    }

    /**
     * @param $key
     * @throws Zend_Db_Adapter_Exception
     * @dataProvider invalidKeyProvider
     */
    public function testRegisterKeyInvalidKey($key)
    {
        $properties = new Opus_Model_Properties();

        $this->setExpectedException(InvalidArgumentException::class, $key);

        $properties->registerKey($key);
    }

    public function testUnregisterKey()
    {
        $key = 'extracted';
        $key2 = 'source';

        $properties = new Opus_Model_Properties();

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
        $key = 'extracted';
        $key2 = 'source';

        $properties = new Opus_Model_Properties();

        $properties->registerType('document');
        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = new Opus_Document();
        $model->store();

        $properties->setProperty($model, $key, 'yes');
        $properties->setProperty($model, $key2, 'sword');

        $this->assertEquals([
            $key => 'yes',
            $key2 => 'sword'
        ], $properties->getProperties($model));

        $properties->unregisterKey($key);

        $this->assertEquals([
            $key2 => 'sword'
        ], $properties->getProperties($model));
    }

    public function testUnregisterUnknownKey()
    {
        $properties = new Opus_Model_Properties();

        $key = 'unknown';

        $this->setExpectedException(Opus_Model_UnknownPropertyKeyException::class, $key);

        $properties->unregisterKey($key);
    }

    public function testGetKeys()
    {
        $properties = new Opus_Model_Properties();

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
        $properties = new Opus_Model_Properties();

        $keys = $properties->getKeys();

        $this->assertInternalType('array', $keys);
        $this->assertCount(0, $keys);
    }

    public function testGetProperties()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');
        $properties->registerKey('key1');
        $properties->registerKey('key2');

        $model = new Opus_Document();
        $model = new Opus_Document($model->store());

        $model2 = new Opus_Document();
        $model2->store();

        $properties->setProperty($model, 'key1', 'value1');
        $properties->setProperty($model, 'key2', 'value2');
        $properties->setProperty($model2, 'key1', 'value1b');

        $props = $properties->getProperties($model);

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2'
        ], $props);
    }

    public function testGetPropertiesUnknownModel()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');

        $model = new Opus_Document();
        $model->store();

        $props = $properties->getProperties($model);

        $this->assertInternalType('array', $props);
        $this->assertCount(0, $props);
    }

    public function testSetProperty()
    {
        $properties = new Opus_Model_Properties();

        $model = new Opus_Document();
        $model->store();

        $model2 = new Opus_Document();
        $model2->store();

        $key = 'extracted';
        $value = 'error';
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
        $properties = new Opus_Model_Properties();

        $key = 'testkey';

        $properties->registerKey($key);

        $this->setExpectedException(InvalidArgumentException::class, 'Model argument must not be null');

        $properties->setProperty(null, $key, 'testvalue');
    }

    public function testSetPropertyKeyNull()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');

        $model = new Opus_Document();
        $model->store();

        $this->setExpectedException(InvalidArgumentException::class, 'Key argument must not be null');

        $properties->setProperty($model, null, 'testvalue');
    }

    public function testSetPropertyModelAndKeyNull()
    {
        $properties = new Opus_Model_Properties();

        $this->setExpectedException(InvalidArgumentException::class, 'Model argument must not be null');

        $properties->setProperty(null, null, 'testvalue');
    }

    public function testSetPropertyUnknownModel()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testKey';

        $properties->registerKey($key);

        $model = new Opus_Version(); // Not a Opus_Model_Abstract class

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus_Model_PropertySupportInterface'
        );

        $properties->setProperty($model, $key, 'testvalue');
    }

    public function testSetPropertyUnknownKey()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');
        $properties->registerKey('knownkey');

        $model = new Opus_Document();
        $model->store();

        $this->setExpectedException(Opus_Model_UnknownPropertyKeyException::class, 'unknownKey');

        $properties->setProperty($model, 'unknownKey', 'testvalue');
    }

    public function testSetPropertyUnknownModelAndUnknownKey()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testKey'; // not registered

        $model = new Opus_Version(); // Not a Opus_Model_Abstract class

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus_Model_PropertySupportInterface'
        );

        $properties->setProperty($model, $key, 'testvalue');
    }

    public function testSetPropertyForModelWithoutId()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';

        $model = new Opus_Document();

        $properties->registerType('document');
        $properties->registerKey($key);

        $this->setExpectedException(Opus_Model_PropertiesException::class, 'Model ID is null');

        $properties->setProperty($model, $key, 'testvalue');
    }

    public function testGetProperty()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';
        $value = 'testvalue';
        $value2 = 'testvalue2';

        $model = new Opus_Document();
        $model->store();

        $model2 = new Opus_Document();
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
        $properties = new Opus_Model_Properties();

        $key = 'testkey';
        $key2 = 'key2';

        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = new Opus_Document();
        $model->store();

        $properties->registerType('document');
        $properties->setProperty($model, $key2, 'testvalue');

        $this->assertNull($properties->getProperty($model, $key));
    }

    public function testGetPropertyUnknownModel()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';

        $properties->registerType('document');
        $properties->registerKey($key);

        $model = new Opus_Document();
        $model->store();

        $this->assertNull($properties->getProperty($model, $key));
    }

    public function testGetPropertyUnknownKey()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');

        $model = new Opus_Document();
        $model->store();

        $unknownKey = 'unknownKey';

        $this->setExpectedException(Opus_Model_UnknownPropertyKeyException::class, $unknownKey);

        $properties->getProperty($model, $unknownKey);
    }

    public function testGetPropertyUnsupportedModel()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testKey';

        $properties->registerKey($key);

        $model = new Opus_Version(); // Does not implement interface

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus_Model_PropertySupportInterface'
        );

        $properties->getProperty($model, $key);
    }

    public function testGetPropertyForModelWithoutId()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';

        $model = new Opus_Document();

        $properties->registerType('document');
        $properties->registerKey($key);

        $this->setExpectedException(Opus_Model_PropertiesException::class, 'Model ID is null');

        $properties->getProperty($model, $key);
    }

    public function testRemoveProperties()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');
        $properties->registerKey('key1');
        $properties->registerKey('key2');

        $model = new Opus_Document();
        $model->store();

        $model2 = new Opus_Document();
        $model2->store();

        $properties->setProperty($model, 'key1', 'value1');
        $properties->setProperty($model, 'key2', 'value2');
        $properties->setProperty($model2, 'key1', 'value1b');

        $this->assertEquals([
            'key1' => 'value1',
            'key2' => 'value2'
        ], $properties->getProperties($model));

        $properties->removeProperties($model);

        $this->assertEquals([], $properties->getProperties($model));

        // check that properties of other model did not get removed
        $this->assertEquals([
            'key1' => 'value1b'
        ], $properties->getProperties($model2));
    }

    public function testRemovePropertiesUnknownModel()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');

        $model = new Opus_Document();
        $model->store();

        // model does not have properties (is not known in table) - nothing should happen
        $properties->removeProperties($model);

        $this->assertEquals([], $properties->getProperties($model));
    }

    public function testRemovePropertiesUnsupportedModel()
    {
        $properties = new Opus_Model_Properties();

        $model = new Opus_Version();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus_Model_PropertySupportInterface'
        );

        $properties->getProperties($model);
    }

    public function testRemovePropertiesForModelWithoutId()
    {
        $properties = new Opus_Model_Properties();

        $model = new Opus_Document();

        $properties->registerType('document');

        $this->setExpectedException(Opus_Model_PropertiesException::class, 'Model ID is null');

        $properties->removeProperties($model);
    }

    public function testRemoveProperty()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';
        $key2 = 'key2';

        $properties->registerType('document');
        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = new Opus_Document();
        $model->store();

        $model2 = new Opus_Document();
        $model2->store();

        $properties->setProperty($model, $key, 'testvalue');
        $properties->setProperty($model, $key2, 'value2');
        $properties->setProperty($model2, $key, 'testvalue2');

        $this->assertEquals([
            $key => 'testvalue',
            $key2 => 'value2'
        ], $properties->getProperties($model));

        $properties->removeProperty($model, $key);

        $this->assertEquals([
            $key2 => 'value2'
        ], $properties->getProperties($model));

        // check that property did not get remove from other model
        $this->assertEquals([
            $key => 'testvalue2'
        ], $properties->getProperties($model2));
    }

    public function testSetPropertyNullRemovesProperty()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';
        $key2 = 'key2';

        $properties->registerType('document');
        $properties->registerKey($key);
        $properties->registerKey($key2);

        $model = new Opus_Document();
        $model->store();

        $properties->setProperty($model, $key, 'testvalue');
        $properties->setProperty($model, $key2, 'value2');

        $this->assertEquals([
            $key => 'testvalue',
            $key2 => 'value2'
        ], $properties->getProperties($model));

        $properties->setProperty($model, $key, null);

        $this->assertEquals([
            $key2 => 'value2'
        ], $properties->getProperties($model));
    }

    public function testRemovePropertyUnknownModel()
    {
        $properties = new Opus_Model_Properties();

        $properties->registerType('document');

        $key = 'testkey';

        $properties->registerKey($key);

        $model = new Opus_Document();
        $model->store();

        $model2 = new Opus_Document();
        $model2->store();

        $properties->setProperty($model2, $key, 'testvalue');

        // noting should happen, so it isn't clear what to assert
        $properties->removeProperty($model, $key);

        // assert that the key has not been removed from other model
        $this->assertEquals('testvalue', $properties->getProperty($model2, $key));
    }

    public function testRemovePropertyUnsupportedModel()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';

        $properties->registerKey($key);

        $model = new Opus_Version();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'Model argument must be of type Opus_Model_PropertySupportInterface'
        );

        $properties->removeProperty($model, $key);
    }

    public function testRemovePropertyUnknownKey()
    {
        $properties = new Opus_Model_Properties();

        $unknownKey = 'testkey';

        $properties->registerType('document');

        $model = new Opus_Document();
        $model->store();

        $this->setExpectedException(Opus_Model_UnknownPropertyKeyException::class, $unknownKey);

        $properties->removeProperty($model, $unknownKey);
    }

    public function testRemovePropertyModelWithoutId()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';

        $model = new Opus_Document();

        $properties->registerType('document');
        $properties->registerKey($key);

        $this->setExpectedException(Opus_Model_PropertiesException::class, 'Model ID is null');

        $properties->removeProperty($model, $key);
    }

    /**
     * TODO Is this functionality necessary?
     */
    public function testFindModels()
    {
        $properties = new Opus_Model_Properties();

        $key = 'testkey';
        $value = 'testvalue';

        $properties->registerType('document');
        $properties->registerKey($key);

        $expected = [];

        $model = new Opus_Document();
        $modelId = $model->store();
        $properties->setProperty($model, $key, $value);

        $result = $properties->findModels($key, $value);

        $this->markTestIncomplete();
    }

    public function testFindModelsWithType()
    {
        $this->markTestIncomplete();
    }

    public function testRenameKey()
    {
        $properties = new Opus_Model_Properties();

        $oldKey = 'oldkey';
        $newKey = 'newkey';

        $properties->registerType('document');
        $properties->registerKey($oldKey);

        $this->assertEquals([
            $oldKey
        ], $properties->getKeys());

        $model = new Opus_Document();
        $model->store();

        $properties->setProperty($model, $oldKey, 'testvalue');

        $this->assertEquals([
            $oldKey => 'testvalue'
        ], $properties->getProperties($model));

        $properties->renameKey($oldKey, $newKey);

        $this->assertEquals([
            $newKey
        ], $properties->getKeys());

        $this->assertEquals([
            $newKey => 'testvalue'
        ], $properties->getProperties($model));
    }

    public function testRenameKeyInvalidNewKey()
    {
        $properties = new Opus_Model_Properties();

        $oldKey = 'oldkey';
        $invalidKey = 'invalid..key';

        $properties->registerType('document');
        $properties->registerKey($oldKey);

        $this->assertEquals([
            $oldKey
        ], $properties->getKeys());

        $model = new Opus_Document();
        $model->store();

        $properties->setProperty($model, $oldKey, 'testvalue');

        $this->assertEquals([
            $oldKey => 'testvalue'
        ], $properties->getProperties($model));

        $this->setExpectedException(InvalidArgumentException::class, $invalidKey);

        $properties->renameKey($oldKey, $invalidKey);
    }

    public function testGetKeyId()
    {
        $reflect = new ReflectionClass(Opus_Model_Properties::class);
        $method = $reflect->getMethod('getKeyId');
        $method->setAccessible(true);

        $properties = new Opus_Model_Properties();

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
        $properties = new Opus_Model_Properties();

        $this->assertFalse($properties->isAutoRegisterTypeEnabled());
    }

    public function testSetAutoRegisterTypeEnabled()
    {
        $properties = new Opus_Model_Properties();

        $properties->setAutoRegisterTypeEnabled(true);

        $this->assertTrue($properties->isAutoRegisterTypeEnabled());
    }

    public function testSetAutoRegisterTypeEnabledWithNullArgument()
    {
        $properties = new Opus_Model_Properties();

        $this->setExpectedException(InvalidArgumentException::class, 'Argument must not be null');

        $properties->setAutoRegisterTypeEnabled(null);
    }

    public function testSetAutoRegisterTypeEnabledWithBadArgument()
    {
        $properties = new Opus_Model_Properties();

        $this->setExpectedException(InvalidArgumentException::class, 'Argument must be boolean');

        $properties->setAutoRegisterTypeEnabled('123');
    }

    public function testIsAutoRegisterKeyEnabled()
    {
        $properties = new Opus_Model_Properties();

        $this->assertFalse($properties->isAutoRegisterKeyEnabled());
    }

    public function testSetAutoRegisterKeyEnabled()
    {
        $properties = new Opus_Model_Properties();

        $properties->setAutoRegisterKeyEnabled(true);

        $this->assertTrue($properties->isAutoRegisterKeyEnabled());
    }

    public function testSetAutoRegisterKeyEnabledWithNullArgument()
    {
        $properties = new Opus_Model_Properties();

        $this->setExpectedException(InvalidArgumentException::class, 'Argument must not be null');

        $properties->setAutoRegisterKeyEnabled(null);
    }

    public function testSetAutoRegisterKeyEnabledWithBadArgument()
    {
        $properties = new Opus_Model_Properties();

        $this->setExpectedException(InvalidArgumentException::class, 'Argument must be boolean');

        $properties->setAutoRegisterKeyEnabled('test');
    }

    public function testSetPropertyAutoRegisterTypeEnabled()
    {
        $properties = new Opus_Model_Properties();
        $properties->setAutoRegisterTypeEnabled(true);

        $key = 'testkey';
        $value = 'testvalue';

        $properties->registerKey($key);

        $model = new Opus_Document();
        $model->store();

        $this->assertEquals([], $properties->getTypes());

        $properties->setProperty($model, $key, $value);

        $this->assertEquals($value, $properties->getProperty($model, $key));

        $this->assertEquals([
            'document'
        ], $properties->getTypes());
    }

    public function testSetPropertyAutoRegisterKeyEnabled()
    {
        $properties = new Opus_Model_Properties();
        $properties->setAutoRegisterKeyEnabled(true);

        $key = 'testkey';
        $value = 'testvalue';

        $properties->registerType('document');

        $model = new Opus_Document();
        $model->store();

        $this->assertEquals([], $properties->getKeys());

        $properties->setProperty($model, $key, $value);

        $this->assertEquals($value, $properties->getProperty($model, $key));

        $this->assertEquals([
            $key
        ], $properties->getKeys());
    }

    public function testSetPropertyAutoRegisterEnabled()
    {
        $properties = new Opus_Model_Properties();
        $properties->setAutoRegisterTypeEnabled(true);
        $properties->setAutoRegisterKeyEnabled(true);

        $key = 'testkey';
        $value = 'testvalue';

        $model = new Opus_Document();
        $model->store();

        $this->assertEquals([], $properties->getKeys());
        $this->assertEquals([], $properties->getTypes());

        $properties->setProperty($model, $key, $value);

        $this->assertEquals($value, $properties->getProperty($model, $key));

        $this->assertEquals([
            $key
        ], $properties->getKeys());

        $this->assertEquals([
            'document'
        ], $properties->getTypes());
    }
}
