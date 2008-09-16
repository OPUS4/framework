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
     * XML documenttype description with no selected fields.
     *
     * @var string
     */
    private $__xml_nofields = '
        <documenttype name="doctoral_thesis"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
        </documenttype>
    ';

    /**
     * Data provider for invalid creation arguments.
     *
     * @return array Array of invalid creation arguments and an error message.
     */
    public function invalidCreationDataProvider() {
        return array(
            array('','Empty string not rejected.'),
            array(null,'Empty string not rejected.'),
            array('/filethatnotexists.foo','Invalid filename not rejected.'),
            array(new Exception(),'Wrong object type not rejected.'),
        );
    }

    /**
     * Test if an InvalidArgumentException occurs.
     *
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
     * Test if all Opus available field descriptions can be retrieved.
     *
     * @return void
     */
    public function testAllFields() {
        $result = Opus_Document_Type::getAvailableFields();
        $this->assertFalse(empty($result), 'No field definitions returned.');
    }

    /**
     * Loop through all declared fields and request their corresponding validators.
     * Check if returned validator class matches the fields datatype.
     *
     * @return void
     */
    public function testGetSimpleValidatorsByFieldName() {
        $fields = Opus_Document_Type::getAvailableFields();
        foreach ($fields as $fname => $fdesc) {
            $validator = Opus_Document_Type::getValidatorFor($fname);

            if (is_null($validator) === false) {
                $this->assertTrue($validator instanceof Zend_Validate_Interface,
                'Returned object does not implement Zend_Validate_Interface');

                switch ($fdesc['type']) {
                    case Opus_Document_Type::DT_NUMBER:
                        $expected = 'Zend_Validate_Int';
                        break;
                    case Opus_Document_Type::DT_DATE:
                        $expected = 'Opus_Validate_InstanceOf';
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
                }

                $this->assertType($expected, $validator, 'Returned object is not a ' . $expected . ' instance.');
            }
        }
    }

    /**
     * Loop through all declared fields and request their corresponding validators
     * using the datatype specified.
     * Check if returned validator class matches the datatype.
     *
     * @return void
     */
    public function testGetSimpleValidatorsByFieldType() {
        $fields = Opus_Document_Type::getAvailableFields();
        foreach ($fields as $fname => $fdesc) {
            $validator = Opus_Document_Type::getValidatorFor($fdesc['type']);

            if (is_null($validator) === false) {
                $this->assertTrue($validator instanceof Zend_Validate_Interface,
                'Returned object does not implement Zend_Validate_Interface');

                switch ($fdesc['type']) {
                    case Opus_Document_Type::DT_NUMBER:
                        $expected = 'Zend_Validate_Int';
                        break;
                    case Opus_Document_Type::DT_DATE:
                        $expected = 'Opus_Validate_InstanceOf';
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
                }

                $this->assertType($expected, $validator, 'Returned object is not a ' . $expected . ' instance.');
            }
        }
    }


}
