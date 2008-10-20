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
 * @package     Opus_Validate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Validate_ComplexType.
 *
 * @category    Tests
 * @package     Opus_Validate
 *
 * @group       ComplexType
 *
 */
class Opus_Validate_ComplexTypeTest extends PHPUnit_Framework_TestCase {


    /**
     * Initialized validator.
     *
     * @var Opus_Validate_ComplexType
     */
    private $__fixture = null;

    /**
     * Common field type definiton.
     *
     * @var array
     */
    private $__fieldef = array(
        'yesno'   => array('type' => Opus_Document_Type::DT_BOOLEAN),
        'integer' => array('type' => Opus_Document_Type::DT_NUMBER),
        'text'    => array('type' => Opus_Document_Type::DT_TEXT),
        'date'    => array('type' => Opus_Document_Type::DT_DATE)
    );

    /**
     * Provider for valid field data.
     *
     * @return array Valid data samples.
     */
    public function validDataProvider() {
        return array(
            array(array('yesno' => true, 'integer' => 4711)),
            array(array('yesno' => false, 'integer' => -100)),
            array(array('text' => 'no validation')),
            array(array('date' => '1999-12-12'))
        );
    }

    /**
     * Provider for invalid field data.
     *
     * @return array Invalid data samples.
     */
    public function invalidDataProvider() {
        return array(
            array(array('yesno' => null, 'integer' => 'cuatro mil setecientos y once')),

            /*
             * Setting 'integer' to true will make the test fail because
             * of a validation bug in Zend_Validate_Int (ZF 1.6.0). The
             * issue has been reported: http://framework.zend.com/issues/browse/ZF-4303
             */
            array(array('yesno' => 'true', 'integer' => false)),
            array(array('date' => 'novaliddate'))
        );
    }

    /**
     * Provider for invalid field type description.
     *
     * @return array Invalid field types.
     */
    public function malformedTypeDataProvider() {
        return array(
            array(array('type' => Opus_Document_Type::DT_BOOLEAN),
                  array('type' => Opus_Document_Type::DT_NUMBER)),
            array('yesno'   => array('tipo' => Opus_Document_Type::DT_BOOLEAN),
                  'integer' => array('type')),
            array(array(12 => 'twelve'))
        );
    }



    /**
     * Set up test fixture.
     *
     * @return void
     */
    public function setUp() {
        $this->__fixture = new Opus_Validate_ComplexType($this->__fieldef);
    }


    /**
     * Test is creation with empty arguments throws InvalidArgumentException.
     *
     * @return void
     */
    public function testCreationWithEmptyArgument() {
        $this->setExpectedException('InvalidArgumentException');
        $obj = new Opus_Validate_ComplexType('');
    }

    /**
     * Test is creation with malformed arguments throws InvalidArgumentException.
     *
     * @param mixed $fielddef Malformed field definition.
     * @return void
     * 
     * @dataProvider malformedTypeDataProvider
     */
    public function testCreationWithMalformedArgument($fielddef) {
        $this->setExpectedException('InvalidArgumentException');
        $obj = new Opus_Validate_ComplexType($fielddef);
    }


    /**
     * Test successfull validation.
     *
     * @param mixed $data Field data.
     * @return void
     *
     * @dataProvider validDataProvider
     */
    public function testValidateGoodData($data) {
        $result = $this->__fixture->isValid($data);
        $this->assertTrue($result, 'Data should pass validation.');
    }

    /**
     * Test successfull rejection of invalid data.
     *
     * @param mixed $data Field data.
     * @return void
     *
     * @dataProvider invalidDataProvider
     */
    public function testValidateBadData($data) {
        $result = $this->__fixture->isValid($data);
        $this->assertFalse($result, 'Data should not pass validation.');
    }


}
