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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Validate_Isbn13.
 *
 * @category    Tests
 * @package     Opus_Validate
 * 
 * @group       Isbn13Test
 *
 */
class Opus_Validate_Isbn13Test extends TestCase {

    /**
     * Overwrite parent methods.
     */
    public function setUp() {}
    public function tearDown() {}

    /**
     * Data provider for valid arguments.
     *
     * @return array Array of invalid arguments.
     */
    public function validDataProvider() {
        return array(
            array('978-3-86680-192-9'),
            array('978-5-7931-8163-1'),
            array('978-979-3182-63-6'),
            array('978 3 86680 192 9'),
            array('978 5 7931 8163 1'),
            array('978 979 3182 63 6'),
        );
    }

    /**
     * Data provider for invalid arguments.
     *
     * @return array Array of invalid arguments and a message.
     */
    public function invalidDataProvider() {
        return array(
            array(null, 'Null value not rejected'),
            array('',   'Empty string not rejected'),
            array(4711, 'Integer not rejected'),
            array(true, 'Boolean not rejected'),
            array('4711-0815',          'Malformed string not rejected.'),
            array('980-3-86680-192-9',  'Wrong prefix not rejected.'),
            array('978-3-86680-192-5', 'Wrong check digit not rejected.'),
            array('978 3 86680-192-9', 'Mixed separators not rejected.')
        );
    }


    /**
     * Test validation of correct arguments.
     *
     * @param mixed $arg Value to check given by the data provider.
     * @return void
     *
     * @dataProvider validDataProvider
     */
    public function testValidArguments($arg) {
        $validator = new Opus_Validate_Isbn13();
        $result = $validator->isValid($arg);
        
        $codes = $validator->getErrors();
        $msgs  = $validator->getMessages();
        $err   = '';
        foreach ($codes as $code) {
            $err .= '(' . $msgs[$code] . ') ';
        }
        
        $this->assertTrue($result, $arg . ' should pass validation but validator says: ' . $err);
    }

    /**
     * Test validation of incorrect arguments.
     *
     * @param mixed  $arg Invalid value to check given by the data provider.
     * @param string $msg Error message.
     * @return void
     *
     * @dataProvider invalidDataProvider
     */
    public function testInvalidArguments($arg, $msg) {
        $validator = new Opus_Validate_Isbn13();
        $this->assertFalse($validator->isValid($arg), $msg);
    }

}
