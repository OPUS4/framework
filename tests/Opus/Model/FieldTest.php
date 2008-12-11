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
 * @author      Ralf Claußnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Model_Field.
 *
 * @category Tests
 * @package  Opus_Model
 *
 * @group    FieldTest
 */
class Opus_Model_FieldTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Date provider for invalid setMultiplicity() arguments test.
     * 
     * @return array
     */
    public function invalidSetMultiplicityValuesDataProvider() {
        return array(
            array('0'),array('1'),array(0),array(-1),array('a'),
            array('z'),array(''),array(' '),array(true),array(false),
            array(565676.234),array(-0.0435),array(new InvalidArgumentException()),
            array(array(1,2,3,4))
        );
    }
    
    /**
     * Test if the class name of a model can be retrieved from the field.
     *
     * @return void
     */
    public function testNameOfValueClassCanBeRetrieved() {
        $field = new Opus_Model_Field('MyField');
        $field->setValueModelClass('Opus_Model_AbstractMock');
        $classname = $field->getValueModelClass();
        $this->assertEquals('Opus_Model_AbstractMock', $classname, 'Wrong class name returned.');
    }
    
    /**
     * Test that the returned model class name is empty if the field value
     * is not an model instance.
     *
     * @return void
     */
    public function testNameOfValueClassIsEmptyIfNoModelClassIsSet() {
        $field = new Opus_Model_Field('MyField');
        $field->setValue('no_object');
        $classname = $field->getValueModelClass();
        $this->assertNull($classname, 'Class name returned when no model instance is set as value.');
    }
    
    
    /**
     * Test if a field is set to have single value it never returns an array
     * as its value.
     *
     * @return void
     */
    public function testSinglevaluedFieldOnlyHasSingleValue() {
        $field = new Opus_Model_Field('MyField');
        $field->setMultiplicity(1);
        $result = $field->getValue();
        $this->assertFalse(is_array($result), 'Returned value should not be an array.');
    }
    
    
    /**
     * Test if a field is set to have multiple values it always returns an array
     * as its value.
     *
     * @return void
     */
    public function testMultivaluedFieldOnlyHasArrayValue() {
        $field = new Opus_Model_Field('MyField');
        $field->setMultiplicity('*');
        $result = $field->getValue();
        $this->assertTrue(is_array($result), 'Returned value is not an array.');
    }
    
    /**
     * Test if a field is set to have single value it does not accept an array as
     * its input value.
     *
     * @return void
     */
    public function testSinglevaluedFieldTakesSingleValue() {
        $field = new Opus_Model_Field('MyField');
        $field->setMultiplicity(1);
        $this->setExpectedException('InvalidArgumentException');
        $field->setValue(array('single', 'sungle', 'sangle'));
    }
    
    /**
     * Test if only valid integer values greater zero or "*" can be set
     * as multiplicity.
     *
     * @return void
     * 
     * @dataProvider invalidSetMultiplicityValuesDataProvider
     */
    public function testInputValuesForMultiplicityAreIntegerOrStar($value) {
        $this->setExpectedException('InvalidArgumentException');
        $field = new Opus_Model_Field('MyField');
        $field->setMultiplicity($value);
    }

    /**
     * Test if a specific value can be obtained from a multivalued field by
     * specifying an array index. 
     *
     * @return void
     */
    public function testGetSpecificIndexFromMultivalueField() {
        $field = new Opus_Model_Field('MyField');
        $field->setMultiplicity('*');
        $field->setValue(array(1,2,'Hallo'));
        $this->assertEquals(1, $field->getValue(0), 'Wrong value on index 0.');
        $this->assertEquals(2, $field->getValue(1), 'Wrong value on index 1.');
        $this->assertEquals('Hallo', $field->getValue(2), 'Wrong value on index 2.');
    }
    
    /**
     * Test if the modified flag of a field is set to false. 
     *
     * @return void
     */
    public function testModifiedFlagIsNotSetInitially() {
        $field = new Opus_Model_Field('MyField');
        $result = $field->isModified();
        $this->assertFalse($result, 'Modified flag is initially true.');
    }
    
    /**
     * Test if the modified falg is indeed set to true if a call to setValue()
     * gives a new value to the field.
     *
     * @return void
     */
    public function testModifiedFlagIsSetAfterSettingNewValue () {
        $field = new Opus_Model_Field('MyField');
        $field->setValue('MyValue');
        $after = $field->isModified();
        $this->assertTrue($after, 'Modified flag has has not been set.');
    }
    
    /**
     * Test if the modified flag can be set back to false again.
     *
     * @return void
     */
    public function testModifiedFlagIsClearable() {
        $field = new Opus_Model_Field('MyField');
        $field->setValue('MyValue');
        $field->clearModified();
        $after = $field->isModified();
        $this->assertFalse($after, 'Modified flag has has not been cleared.');
    }
    
    /**
     * Test if the modified flag is set to true after a call to setValue()
     * with the current value of the field.
     *
     * @return void
     */
    public function testModifiedFlagRemainsAfterSettingSameValueAgain() {
        $field = new Opus_Model_Field('MyField');
        $before = $field->isModified();
        $field->setValue($field->getValue());
        $after = $field->isModified();
        $this->assertEquals($before, $after, 'Modified flag has changed.');
    }
    
    /**
     * Test setting of default values
     *
     * @return void
     */
    public function testSetDefault() {
        $field = new Opus_Model_Field('MyField');
        $array = array('my', 'default', 'values');
        $field->setDefault($array);
        $result = $field->getDefault();
        $this->assertEquals($array, $result, 'Wrong default value returned');
    }
    
    /**
     * Test if setting the selection flag clear the textarea flag.
     *
     * @return void
     */
    public function testSelectionFlagClearsTextareaFlag() {
        $field = new Opus_Model_Field('MyField');
        $field->setTextarea(true);
        $field->setSelection(true);
        
        $this->assertTrue($field->getSelection(), 'Selection flag does not get set.');
        $this->assertFalse($field->getTextarea(), 'Textarea flag does not get cleared when selection is set.');
    }

    /**
     * Test if setting the textarea flag clear the selection flag.
     *
     * @return void
     */
    public function testTextareaFlagClearsSelectionFlag() {
        $field = new Opus_Model_Field('MyField');
        $field->setSelection(true);
        $field->setTextarea(true);
        
        $this->assertTrue($field->getTextarea(), 'Textarea flag does not get set.');
        $this->assertFalse($field->getSelection(), 'Selection flag does not get cleared when selection is set.');
    }
    
    
}
