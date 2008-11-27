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
                    <field wrong_attr="not_a_valid_fieldname"/>
                </documenttype>')
        );
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
     * Test if an InvalidArgumentException occurs when instanciating with invalid arguments.
     *
     * @param mixed  $arg Constructor parameter.
     * @param string $msg Error message.
     * @return void
     *
     * @dataProvider invalidCreationDataProvider
     */
    public function testCreateWithInvalidArgumentThrowsException($arg, $msg) {
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
                    <field name="Language" multiplicity="*" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="CompletedYear" languageoption="off" />
                        <field name="CompletedDate" languageoption="off" />
                    </mandatory>
                </documenttype>';
        try {
            $type = new Opus_Document_Type($xml);
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
     * Creating a type with an invalid filename throws exception that points
     * to a file problem.
     *
     * @return void
     */
    public function testCreateWithWrongFilenameThrowsFileException() {
        $this->setExpectedException('InvalidArgumentException');        
        $xml = '../xml/nofile.xml';
        $type = new Opus_Document_Type($xml);
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
     * Test if all field definitions come with their default options set.
     *
     * @return void
     */
    public function testDefaultOptions() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="Language" multiplicity="*" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="CompletedYear" languageoption="off" />
                        <field name="CompletedDate" languageoption="off" />
                    </mandatory>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();
        foreach ($fields as $fieldname => $fielddef) {
            $this->assertArrayHasKey('multiplicity', $fielddef);
            $this->assertArrayHasKey('languageoption', $fielddef);
            $this->assertArrayHasKey('mandatory', $fielddef);
        }
    }
















    //
    //    /**
    //     * Loop through all declared fields and request their corresponding validators
    //     * using the datatype specified.
    //     * Check if returned validator class matches the datatype.
    //     *
    //     * @return void
    //     */
    //    public function testGetValidatorsByFieldType() {
    //        $this->markTestSkipped('Method gone because of refactoring.');
    //
    //        $fields = Opus_Document_Type::getAvailableFields();
    //        foreach ($fields as $fname => $fdesc) {
    //            $validator = Opus_Document_Type::getValidatorFor($fdesc['type']);
    //            $this->checkType($fdesc['type'], $validator);
    //        }
    //    }
    //
    //    /**
    //     * Helper function for testGetValidatorsByFieldType() and testGetValidatorsByFieldName().
    //     * Checks field-type, validator pair.
    //     *
    //     * @param mixed $type      Opus_Document_Type constant.
    //     * @param mixed $validator Object to validate if it is a correct validator instance.
    //     * @return void
    //     */
    //    private function checkType($type, $validator) {
    //        if (is_null($validator) === false) {
    //            $this->assertTrue($validator instanceof Zend_Validate_Interface,
    //                'Returned object does not implement Zend_Validate_Interface');
    //
    //            switch ($type) {
    //                case Opus_Document_Type::DT_NUMBER:
    //                    $expected = 'Zend_Validate_Int';
    //                    break;
    //
    //                case Opus_Document_Type::DT_DATE:
    //                    $expected = 'Zend_Validate_Date';
    //                    break;
    //
    //                case Opus_Document_Type::DT_LANGUAGE:
    //                    $expected = 'Opus_Validate_Locale';
    //                    break;
    //
    //                case Opus_Document_Type::DT_ISBN_10:
    //                    $expected = 'Opus_Validate_Isbn10';
    //                    break;
    //
    //                case Opus_Document_Type::DT_ISBN_13:
    //                    $expected = 'Opus_Validate_Isbn13';
    //                    break;
    //
    //                case Opus_Document_Type::DT_DOCUMENTTYPE:
    //                    $expected = 'Opus_Validate_DocumentType';
    //                    break;
    //
    //                case Opus_Document_Type::DT_REVIEWTYPE:
    //                    $expected = 'Opus_Validate_ReviewType';
    //                    break;
    //
    //                case Opus_Document_Type::DT_NOTESCOPE:
    //                    $expected = 'Opus_Validate_NoteScope';
    //                    break;
    //
    //                case Opus_Document_Type::DT_BOOLEAN:
    //                    $expected = 'Opus_Validate_Boolean';
    //                    break;
    //
    //                default:
    //                    $expected = 'Opus_Validate_ComplexType';
    //                    break;
    //            }
    //            $this->assertType($expected, $validator, 'Returned object is not a ' . $expected . ' instance.');
    //        }
    //    }
    //
    //
    //
    //
    //
    //
    //
    //
    //    /**
    //     * Prove that the core field definitions can not be modified by using a reference.
    //     *
    //     * @return void
    //     */
    //    public function testFieldDefinitionsIsNotReference() {
    //        $this->markTestSkipped('Method gone because of refactoring.');
    //
    //        $fields1 = Opus_Document_Type::getAvailableFields();
    //        $fields1['WRITE'] = 'THROUGH';
    //
    //        $fields2 = Opus_Document_Type::getAvailableFields();
    //        $this->assertNotEquals($fields1, $fields2, 'Reference to internal field returned.');
    //    }



}
