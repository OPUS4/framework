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
}
