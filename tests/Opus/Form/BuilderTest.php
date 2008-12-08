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
 * @package     Opus_Form
 * @author      Ralf Claußnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Form_Builder.
 *
 * @category Tests
 * @package  Opus_Form
 *
 * @group    FormBuilderTest
 */
class Opus_Form_BuilderTest extends PHPUnit_Framework_TestCase {

    /**
     * Test fixture holding an instance of Opus_Form_BuilderTest_Model.
     *
     * @var Opus_Model_Abstract
     */
    protected $_model = null;


    /**
     * Test fixture holding an instance of the Opus_Form_BuilderTest_DbModel table gateway.
     *
     * @var Zend_Db_Table_Interface
     */
    protected $_table = null;

    /**
     * Instance of the class under test.
     *
     * @var Opus_Form_Builder
     */
    protected $_builder = null;

    /**
     * Set up test fixtures and tables.
     *
     * @return void
     */
    public function setUp() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        if ($dba->isExistent('dbmodel') === true) {
            $dba->deleteTable('dbmodel');
        }
        $dba->createTable('dbmodel');
        $dba->addField('dbmodel', array('name' => 'simple_field', 'type' => 'varchar', 'length' => 50));

        $this->_model = new Opus_Form_BuilderTest_Model(null, new Opus_Form_BuilderTest_DbModel);
        $this->_builder = new Opus_Form_Builder();
    }

    /**
     * Test of creating a Zend Form.
     *
     * @return void
     */
    public function testCreateFormFromDocument() {
        $form = $this->_builder->build($this->_model);
        $this->assertType('Zend_Form', $form);
        $elements = $form->getElements();
        $this->assertArrayHasKey('SimpleField', $elements, 'Field "SimpleField" is missing in form.');
    }

    /**
     * Test if the serialized model is correctly stored within the form.
     *
     * @return void
     */
    public function testModelIsSerializedCorrectly() {
        $form = $this->_builder->build($this->_model);
        $serializedModel = base64_encode(bzcompress(serialize($this->_model)));
        $serializedModelFromForm = $form->getElement('__model')->getValue();
        $this->assertEquals($serializedModel, $serializedModelFromForm, 'Model serialization has failures.');
    }

    /**
     * Test if the value of a field is set in the generated form.
     *
     * @return void
     */
    public function testFieldValueIsSetInForm() {
        $this->_model->setSimpleField('Testvalue!');
        $form = $this->_builder->build($this->_model);
        $value = $form->getElement('SimpleField')->getValue();
        $this->assertEquals('Testvalue!', $value, 'Field value has not been set correctly.');
    }

    /**
     * Test if a field has a validator
     *
     * @return void
     */
    public function testFieldHasAValidator() {
        $this->_model->setSimpleField('ValidatorTestName');
        $field = $this->_model->getField('SimpleField');

        $field->setValidator(new Zend_Validate_Alnum());
        $form = $this->_builder->build($this->_model);
        $value = $form->getElement('SimpleField')->getValidator('Zend_Validate_Alnum');
        $this->assertType('Zend_Validate_Alnum', $value, 'Field does not have correct validator');
    }

    /**
     * Test, if a field could have more than one validator (validator chain!)
     *
     * @return void
     */
    public function testFieldHasCorrectValidators() {
        $this->_model->setSimpleField('ValidatorTestName');
        $field = $this->_model->getField('SimpleField');

        $val1 = new Zend_Validate_Alnum();
        $val2 = new Zend_Validate_Date();

        $chain = new Zend_Validate();
        $chain->addValidator($val1)->addValidator($val2);

        $field->setValidator($chain);
        $form = $this->_builder->build($this->_model);
        $value = $form->getElement('SimpleField')->getValidator('Zend_Validate');
        $this->assertEquals($chain, $value, 'Field does not have correct validators');
    }

}
