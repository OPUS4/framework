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
 * @package     Opus_Document
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Document_Type.
 *
 * @category    Tests
 * @package     Opus_Document
 *
 * @group       TypeTest
 *
 */
class Opus_Document_TypeTest extends PHPUnit_Framework_TestCase {

    
    /**
     * Drop the Zend_Registry.
     *
     * @return void
     */
    public function setUp() {
        Zend_Registry::_unsetInstance();
    }
    
    
    
    /**
     * Data provider for invalid creation arguments.
     *
     * @return array Array of invalid creation arguments and an error message.
     */
    public function invalidCreationDataProvider() {
        return array(
        array('','Empty string not rejected.'),
        array(null,'Null not rejected.'),
        array('/filethatnotexists.foo','Invalid filename not rejected.'),
        array(new Exception(),'Wrong object type not rejected.'),
        );
    }



    /**
     * Data provider for valid field-value pairs.
     *
     * @return array Array of valid field-value pairs.
     */
    public function validFieldDataProvider() {
        return array(
            array('completed_year', '1965'),
            array('completed_date', '1999-12-12'),
            array('document_type', 'article'),
            array('language', 'en'),
            array('identifier_isbn', '978-3-7657-2780-1'),
            array('reviewed', 'peer'),
            array('title_abstract', array('value' => 'This document is all about...', 'language' => 'de')),
            array('subject_swd', array('value' => 'ABC', 'language' => 'fr', 'external_key' => 'FOO')),
            array('note', array('message' => 'This one is good.', 'creator' => 'Doe, John', 'scope' => 'public')),
            array('person_author', array('first_name' => 'John', 'last_name' => 'Doe'))
        );
    }

    /**
     * Data provider for invalid field-value pairs.
     *
     * @return array Array of invalid field-value pairs.
     */
    public function invalidFieldDataProvider() {
        return array(
            array('completed_year', null),
            array('completed_date', new Exception()),
            array('document_type', 'who cares!'),
            array('language', 'üöä'),
            array('identifier_isbn', '978-3-:)-7657-2780-1'),
            array('reviewed', '-> deer'),
            array('note', array('message' => 'This one is good.', 'creator' => 'Doe, John', 'scope' => '!internal!'))
        );
    }


    
    /**
     * Date provider for returning compound fieldnames and expected validator type names.
     *
     * @return array Array of field names and expected validator names.
     * 
     */
    public function compoundFieldNameDataProvider() {
        return array(
            array('note.scope', 'Opus_Validate_NoteScope')
        );
    }
    
    /**
     * Data provider for field and option names and values.
     *
     * @return array Array of field and option names and associated values.
     * 
     */
    public function optionConstraintDataProvider() {
        return array(
            array('source','multiplicity', '*', '1'),
            array('source','multiplicity', '12', '1'),
            array('institute','multiplicity', '12', '12'),
            array('source','languageoption', 'on', 'off')
        );
    }
    
    /**
     * Return invalid XML descriptions.
     * 
     * @return array Array of invalid XML type descriptions.
     */
    public function invalidXmlDataProvider() {
        return array(
        array('<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <not_a_valid_tag/>
                </documenttype>'),
        array('<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="not_a_valid_fieldname"/>
                </documenttype>')
        );
    }
    
    /**
     * Test if an InvalidArgumentException occurs.
     *
     * @param mixed  $arg Constructor parameter.
     * @param string $msg Error message.
     * @return void
     *
     * @dataProvider invalidCreationDataProvider
     */
    public function testCreateWithEmptyArgumentThrowsException($arg, $msg) {
        try {
            $obj = new Opus_Document_Type($arg);
        } catch (InvalidArgumentException $ex) {
            return;
        }
        $this->fail($msg);
    }

    /**
     * Create a document type by parsing an XML string.
     *
     * @return void
     */
    public function testCreateByXmlString() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" multiplicity="*" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="completed_year" languageoption="off" />
                        <field name="completed_date" languageoption="off" />
                    </mandatory>
                </documenttype>';
        try {
            $type = new Opus_Document_Type($xml);
        } catch (Exception $ex) {
            $this->fail('Creation failed: ' . $ex->getMessage());
        }
    }

    /**
     * Create a document type by parsing an XML file.
     *
     * @return void
     */
    public function testCreateByXmlFile() {
        $xml = dirname(__FILE__) . '/TypeTest.xml';
        try {
            $type = new Opus_Document_Type($xml);
        } catch (Exception $ex) {
            $this->fail('Creation failed: ' . $ex->getMessage());
        }
    }


    /**
     * Create a document type by providing a DOMDocument.
     *
     * @return void
     */
    public function testCreateByXmlDomDocument() {
        $file = dirname(__FILE__) . '/TypeTest.xml';
        $dom = new DOMDocument();
        $dom->load($file);
        try {
            $type = new Opus_Document_Type($dom);
        } catch (Exception $ex) {
            $this->fail('Creation failed: ' . $ex->getMessage());
        }
    }

    /**
     * Expect an exception when passing an invalid XML source.
     *
     * @param string $xml XML type description.
     * @return void
     * 
     * @dataProvider invalidXmlDataProvider
     */
    public function testCreateWithValidationErrors($xml) {
        $this->setExpectedException('Opus_Document_Exception');
        $type = new Opus_Document_Type($xml);
    }


    /**
     * Test if all Opus available field descriptions can be retrieved.
     *
     * @return void
     */
    public function testGetAllFields() {
        $result = Opus_Document_Type::getAvailableFields();
        $this->assertFalse(empty($result), 'No field definitions returned.');
    }

    /**
     * Loop through all declared fields and request their corresponding validators.
     * Check if returned validator class matches the fields datatype.
     *
     * @return void
     */
    public function testGetValidatorsByFieldName() {
        $fields = Opus_Document_Type::getAvailableFields();
        foreach ($fields as $fname => $fdesc) {
            $validator = Opus_Document_Type::getValidatorFor($fname);
            $this->checkType($fdesc['type'], $validator);
        }    
    }

    /**
     * Loop through all declared fields and request their corresponding validators
     * using the datatype specified.
     * Check if returned validator class matches the datatype.
     *
     * @return void
     */
    public function testGetValidatorsByFieldType() {
        $fields = Opus_Document_Type::getAvailableFields();
        foreach ($fields as $fname => $fdesc) {
            $validator = Opus_Document_Type::getValidatorFor($fdesc['type']);
            $this->checkType($fdesc['type'], $validator);            
        }
    }

    /**
     * Helper function for testGetValidatorsByFieldType() and testGetValidatorsByFieldName().
     * Checks field-type, validator pair.
     *
     * @param mixed $type      Opus_Document_Type constant.
     * @param mixed $validator Object to validate if it is a correct validator instance.
     * @return void
     */
    private function checkType($type, $validator) {
        if (is_null($validator) === false) {
            $this->assertTrue($validator instanceof Zend_Validate_Interface,
                'Returned object does not implement Zend_Validate_Interface');

            switch ($type) {
                case Opus_Document_Type::DT_NUMBER:
                    $expected = 'Zend_Validate_Int';
                    break;

                case Opus_Document_Type::DT_DATE:
                    $expected = 'Zend_Validate_Date';
                    break;

                case Opus_Document_Type::DT_LANGUAGE:
                    $expected = 'Opus_Validate_Locale';
                    break;

                case Opus_Document_Type::DT_ISBN_10:
                    $expected = 'Opus_Validate_Isbn10';
                    break;

                case Opus_Document_Type::DT_ISBN_13:
                    $expected = 'Opus_Validate_Isbn13';
                    break;

                case Opus_Document_Type::DT_DOCUMENTTYPE:
                    $expected = 'Opus_Validate_DocumentType';
                    break;

                case Opus_Document_Type::DT_REVIEWTYPE:
                    $expected = 'Opus_Validate_ReviewType';
                    break;

                case Opus_Document_Type::DT_NOTESCOPE:
                    $expected = 'Opus_Validate_NoteScope';
                    break;

                case Opus_Document_Type::DT_BOOLEAN:
                    $expected = 'Opus_Validate_Boolean';
                    break;

                default:
                    $expected = 'Opus_Validate_ComplexType';
                    break;
            }
            $this->assertType($expected, $validator, 'Returned object is not a ' . $expected . ' instance.');
        }
    }
    
    /**
     * Test if the validator for a field contained within a complex field
     * can be retrieved by referencing its name in a <complex>.<field> schema.
     *
     * @param string $exp_fieldname Compound fieldname.
     * @param string $exp_validator Expected validator class name.
     * @return void
     * 
     * @dataProvider compoundFieldNameDataProvider
     */
    public function testGetValidatorForCompoundFieldName($exp_fieldname, $exp_validator) {
        $validator = Opus_Document_Type::getValidatorFor($exp_fieldname);
        $this->assertNotNull($validator, 'No validator returned.');
        $this->assertType($exp_validator, $validator, 'Returned object is not a ' . $exp_validator . ' instance.');
    }

    /**
     * Test if attempt to retrieve an validator for an unknown fieldname throws an
     * InvalidArgumentException().
     * 
     * @return void
     *
     */
    public function testGetValidatorForUnknownFieldThrowsException() {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Document_Type::getValidatorFor('Ernie&Bert');
    }
    
    /**
     * Test if declared fields can be retrieved.
     *
     * @return void
     */
    public function testGetDefinedFields() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" multiplicity="*" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="completed_year" languageoption="off" />
                        <field name="completed_date" languageoption="off" />
                    </mandatory>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();

        $expected = array('language', 'completed_year', 'completed_date');
        foreach ($expected as $e_fieldname) {
            $this->assertArrayHasKey($e_fieldname, $fields, 'Expected field ' . $e_fieldname . ' is missing.');
        }
    }
    
    
    /**
     * Test if the sub fields of a complex field can be retrieved.
     *
     * @return void
     */
    public function testGetSubFieldsOfDefinedComplexField() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="person_author" />
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();
        $person_author = $fields['person_author'];
        $this->assertArrayHasKey('fields', $person_author, 'Sub fields expected.');
    }

    /**
     * Test if the type of a complex field can be retrieved.
     *
     * @return void
     */
    public function testGetTypeOfDefinedComplexField() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="person_author" />
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();
        $person_author = $fields['person_author'];
        $this->assertArrayHasKey('type', $person_author, 'Type definition expected.');
    }
    
    
    /**
     * Test if correct field value passes validation.
     *
     * @param string $fieldname Name of a field.
     * @param string $value     Value to validate against the field's type
     * @return void
     *
     * @dataProvider validFieldDataProvider
     */
    public function testCorrectFieldPassesValidation($fieldname, $value) {
        $result = Opus_Document_Type::validate(array($fieldname => $value));
        $msg = $fieldname . '=>' . $value . ' should be validated as correct.';
        $this->assertTrue($result, $msg);
    }

    /**
     * Test if an empty data value is rejected.
     *
     * @return void
     */
    public function testValidateEmptyArray() {
        $array = array();
        $result = Opus_Document_Type::validate($array);
        $this->assertEquals(false, $result);
    }

    /**
     * Test if incorrect field values get rejected.
     *
     * @param string $fieldname Name of a field.
     * @param string $value     Value to validate against the field's type
     * @return void
     *
     * @dataProvider invalidFieldDataProvider
     */
    public function testIncorrectFieldRejectedByValidation($fieldname, $value) {
        $result = Opus_Document_Type::validate(array($fieldname => $value));
        $msg = $fieldname . '=>' . $value . ' should be validated as wrong.';
        $this->assertFalse($result, $msg);
    }
    
    /**
     * Test if use of an invalid fieldname throws an exception
     * when validating data.
     *
     * @return void
     */
    public function testValidationOfInvalidFieldNameThrowsException() {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Document_Type::validate(array('novalidfieldname' => 'somevalue'));
    }

    
    /**
     * Test if the name of the document type can be retrieved.
     *
     * @return void
     */
    public function testGetName() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" multiplicity="*" languageoption="off" mandatory="yes" />
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $this->assertEquals('doctoral_thesis', $type->getName(), 'Name returned is wrong.');         
    }
    
    
    /**
     * Test if no type is registered initially.
     *
     * @return void
     */
    public function testRegistryIsInitiallyEmpty() {
        $registry = Zend_Registry::getInstance();
        $this->assertFalse($registry->isRegistered(Opus_Document_Type::ZEND_REGISTRY_KEY), 'Registry is not initially empty.');
    }
    
    /**
     * Test if successfully creating a type registers it in the Zend Registry. 
     *
     * @return void
     */
    public function testTypeGetsRegisteredInZendRegistry() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" multiplicity="*" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="completed_year" languageoption="off" />
                        <field name="completed_date" languageoption="off" />
                    </mandatory>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        
        // Check if the type is registered.
        $registry = Zend_Registry::getInstance();
        $registered = $registry->get(Opus_Document_Type::ZEND_REGISTRY_KEY);
        $this->assertArrayHasKey('doctoral_thesis', $registered, 'Document type has not been registered.');
    }
    
    /**
     * Test if a type specification gets overwritten when another one gets registered
     * under the same name.  
     *
     * @return void
     */
    public function testTypeOverrideInRegistry() {
        $xml1 = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" multiplicity="*" languageoption="off" mandatory="yes" />
                </documenttype>';
        $type1 = new Opus_Document_Type($xml1);
        $xml2 = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" multiplicity="*" languageoption="off" mandatory="yes" />
                </documenttype>';
        $type2 = new Opus_Document_Type($xml2);
        
        // Check if the type2 is registered.
        $registry = Zend_Registry::getInstance();
        $registered = $registry->get(Opus_Document_Type::ZEND_REGISTRY_KEY);
        $result = $registered['doctoral_thesis'];                
        $this->assertNotSame($type1, $result, 'Second attempt to register type did not override the old type.');
        $this->assertSame($type2, $result, 'Second attempt to register type did not override the old type.');
    }
    
    /**
     * Test if the languageoption can be queried when initially specified in
     * the types describing xml.
     *
     * @return void
     */
    public function testGetLanguageOptionWhenGivenByXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" multiplicity="*" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="completed_year" languageoption="off" />
                        <field name="completed_date" languageoption="off" />
                    </mandatory>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();
        
        $this->assertArrayHasKey('languageoption', $fields['language'], 'Languageoption attribute is missing.');
        $this->assertEquals('off', $fields['language']['languageoption'], 'Languageoption attribute has wrong value.');
    }

    /**
     * Test if the multiplicity attribute can be queried when initially specified in
     * the types describing xml.
     *
     * @return void
     */
    public function testGetMultiplicityWhenGivenByXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="institute" multiplicity="12" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="completed_year" languageoption="off" />
                        <field name="completed_date" languageoption="off" />
                    </mandatory>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();
        
        $this->assertArrayHasKey('multiplicity', $fields['institute'], 'Multiplicity attribute is missing.');
        $this->assertEquals('12', $fields['institute']['multiplicity'], 'Multiplicity attribute has wrong value.');
    }

    
    /**
     * Test if the mandatory attribute can be queried when initially specified in
     * the types describing xml.
     *
     * @return void
     */
    public function testGetMandatoryWhenGivenByXml() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" mandatory="yes" />
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();
        
        $this->assertArrayHasKey('mandatory', $fields['language'], 'Mandatory attribute is missing.');
        $this->assertEquals('yes', $fields['language']['mandatory'], 'Mandatory attribute has wrong value.');
    }
    
    
    /**
     * Test if the languageoption and multiplicity can be queried when *not* initially 
     * specified in the types describing xml.
     *
     * @return void
     */
    public function testGetOptionsWhenDefault() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="language" />
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();

        // Languageoption default is "off".
        $this->assertArrayHasKey('languageoption', $fields['language'], 'Languageoption attribute is missing.');
        $this->assertEquals('off', $fields['language']['languageoption'], 'Languageoption attribute has wrong value.');

        // Multiplicity default is "1".
        $this->assertArrayHasKey('multiplicity', $fields['language'], 'Multiplicity attribute is missing.');
        $this->assertEquals('1', $fields['language']['multiplicity'], 'Multiplicity attribute has wrong value.');
        
        // Mandatory default is "no".
        $this->assertArrayHasKey('mandatory', $fields['language'], 'Mandatory attribute is missing.');
        $this->assertEquals('no', $fields['language']['mandatory'], 'Mandatory attribute has wrong value.');
    }
    
    
    /**
     * Test if field option values in document type specifications are restricted
     * to the actual datatypes option limits. E.g. if a datatype is given a
     * multiplicity of 1, it has to be ensured that no document type specification
     * sets the multiplicity of that particular field to "*".  
     *
     * @param string $field    Name of a field.
     * @param string $option   Name of an field option.
     * @param string $value    Option value assigned in document type definition.
     * @param string $expected Expected outcome for option value.
     * @return void
     * 
     * @dataProvider optionConstraintDataProvider
     */
    public function testContraintOptionsCannotGoBeyondDatatypeLimits($field, $option, $value, $expected) {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="' . $field . '" ' . $option . '="' . $value . '"/>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();
        
        $this->assertEquals($expected, $fields[$field][$option],
            '"' . $option . '" attribute value exceeds the possibilities of the datatype.');
        
    }
    
    /**
     * Test if all field definitions come with their default options set.
     *
     * @return void
     */
    public function testDefaultOptions() {
        $fields = Opus_Document_Type::getAvailableFields();
        $this->optionCheckHelper($fields);
    }
    
    /**
     * Check if every field has its option values set. 
     *
     * @param array $fields Array of field definitions.
     * @return boolean|string If all options are set correctly true is returned.
     *                        If not, the name of the field with a missing default option is returned.
     */
    private function optionCheckHelper(array $fields) {
        foreach ($fields as $fieldname => $fielddef) {
            $subresult = true;
            if (array_key_exists('fields', $fielddef) === true) {
                $subresult = $this->optionCheckHelper($fielddef['fields']);
            }
            if ((array_key_exists('multiplicity', $fielddef) === false) 
                or (array_key_exists('languageoption', $fielddef) === false)
                or ($subresult !== true)) {
                    $this->fail('Default option missing for: ' . $fieldname);
                }
        }
        return true;
    }
    
    /**
     * Prove that the core field definitions can not be modified by using a reference.
     *
     * @return void
     */
    public function testFieldDefinitionsIsNotReference() {
        $fields1 = Opus_Document_Type::getAvailableFields();
        $fields1['WRITE'] = 'THROUGH';
         
        $fields2 = Opus_Document_Type::getAvailableFields();
        $this->assertNotEquals($fields1, $fields2, 'Reference to internal field returned.');
    }
    
    
    
}
