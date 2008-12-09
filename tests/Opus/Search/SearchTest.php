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
 * @package     Opus_Search
 * @author      Oliver Marahrens (o.marahrens@tu-harburg.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: $
 */

/**
 * Test cases for class Query.
 *
 * @category    Tests
 * @package     Opus_Search
 *
 * @group       TypeTest
 *
 */
class Opus_Search_SearchTest extends PHPUnit_Framework_TestCase {


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
    public function testQuery($arg, $msg) {
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
    public function testIndex() {
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
     * Test if loading an file that cannot be loaded as xml file for
     * any reason leads to an exception.
     *
     * @return void
     */
    public function testLoadInvalidFileThrowsException() {
        $this->setExpectedException('InvalidArgumentException');
        $xml = 'TypeTest.php';
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


    /**
     * Test if successfully creating a type registers it in the Zend Registry.
     *
     * @return void
     */
    public function testTypeGetsRegisteredInZendRegistry() {
        $xml1 = dirname(__FILE__) . '/TypeTest.xml';
        $type1 = new Opus_Document_Type($xml1);
        $typename = $type1->getName();
        $xml2 = dirname(__FILE__) . '/TypeTest.xml';
        $type2 = new Opus_Document_Type($xml2);

        // Check if the type2 is registered.
        $registry = Zend_Registry::getInstance();
        $registered = $registry->get(Opus_Document_Type::ZEND_REGISTRY_KEY);
        $result = $registered[$typename];
        $this->assertNotSame($type1, $result, 'Second attempt to register type did not override the old type.');
        $this->assertSame($type2, $result, 'Second attempt to register type did not override the old type.');
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
                    <field name="Language" multiplicity="*" languageoption="off" mandatory="yes" />
                </documenttype>';
        $type1 = new Opus_Document_Type($xml1);
        $xml2 = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="Language" multiplicity="*" languageoption="off" mandatory="yes" />
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
                    <field name="Language" multiplicity="*" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="CompletedYear" languageoption="off" />
                        <field name="CompletedDate" languageoption="off" />
                    </mandatory>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();

        $this->assertArrayHasKey('languageoption', $fields['Language'], 'Languageoption attribute is missing.');
        $this->assertEquals('off', $fields['Language']['languageoption'], 'Languageoption attribute has wrong value.');
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
                    <field name="Institute" multiplicity="12" languageoption="off" mandatory="yes" />
                    <mandatory type="one-at-least">
                        <field name="CompletedYear" languageoption="off" />
                        <field name="CompletedDate" languageoption="off" />
                    </mandatory>
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();

        $this->assertArrayHasKey('multiplicity', $fields['Institute'], 'Multiplicity attribute is missing.');
        $this->assertEquals('12', $fields['Institute']['multiplicity'], 'Multiplicity attribute has wrong value.');
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
                    <field name="Language" mandatory="yes" />
                </documenttype>';
        $type = new Opus_Document_Type($xml);
        $fields = $type->getFields();

        $this->assertArrayHasKey('mandatory', $fields['Language'], 'Mandatory attribute is missing.');
        $this->assertEquals('yes', $fields['Language']['mandatory'], 'Mandatory attribute has wrong value.');
    }

    /**
     * Test if the type parser error message is correct. 
     *
     * @return 
     */
    public function testAppropriateErrorMessageOnXmlSchemaViolations() {
        $xml = '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="error1" mandatory="error2" />
                </documenttype>';
        try {
            $type = new Opus_Document_Type($xml);
            $this->fail('Invalid document type description gets parsed without error.');
        } catch(Opus_Document_Exception $ex) {
            $message = $ex->getMessage();
            $this->assertRegExp('/\'error1\' is not a valid value of the atomic type/', $message);
            $this->assertRegExp('/\'error2\' is not a valid value of the atomic type/', $message);
            $this->assertRegExp('/The value \'error1\' is not an element of the set/', $message);
        }
    }
    
    /**
     * Test if a document type file can be loaded by inferencing the filename
     * from the types name. 
     *
     * @return void
     */
    public function testGetDocumentTypeFileByTypeName() {
        Opus_Document_Type::setXmlDoctypePath(dirname(__FILE__));
        $type = new Opus_Document_Type('TypeTest');
        $this->assertNotNull($type);
    }
    
}