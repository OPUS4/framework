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
     * Xml document type description for simple document type.
     *
     * @var string
     */
    protected $_simpleXmlType =
        '<?xml version="1.0" encoding="UTF-8" ?>
         <documenttype name="simple"
            xmlns="http://schemas.opus.org/documenttype"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
            <field name="Language" />
         </documenttype>';
    
    
    /**
     * Test fixture holdig an instance of Opus_Document_Type.
     *
     * @var Opus_Document_Type
     */
    protected $_simpleType = null;

    
    /**
     * Test fixture holding an instance of Opus_Model_Document.
     * 
     * @var Opus_Model_Document
     */
    protected $_simpleDocument = null;

    
    /**
     * Instance of the class under test.
     *
     * @var Opus_Form_Builder
     */
    protected $_builder = null;
    
    /**
     * Set up test fixtures.
     *
     * @return void
     */
    public function setUp() {
        $this->_simpleType = new Opus_Document_Type($this->_simpleXmlType);
        $this->_simpleDocument = new Opus_Model_Document(null, $this->_simpleType);
        $this->_builder = new Opus_Form_Builder(); 
    }
    
    /**
     * Test of creating a Zend Form.
     *
     * @return void
     */
    public function testCreateFormFromDocument() {
        $form = $this->_builder->build($this->_simpleDocument);
        $this->assertType('Zend_Form', $form);
        $elements = $form->getElements();
        $this->assertArrayHasKey('Language', $elements, 'Language field is missing in form.');
    }
    
    /**
     * Test if the serialized model is correctly stored within the form.
     *
     * @return void
     */
    public function testModelIsSerializedCorrectly() {
        $form = $this->_builder->build($this->_simpleDocument);
        $serializedModel = base64_encode(bzcompress(serialize($this->_simpleDocument)));
        $serializedModelFromForm = $form->getElement('__model')->getValue(); 
        $this->assertEquals($serializedModel, $serializedModelFromForm, 'Model serialization has failures.');
    }

    /**
     * Test if a subform is generated for the dependent field TitleMain.
     *
     * @return void
     */
    public function testSubformIsGeneratedForDependentTitleField() {
        $doc = $this->_simpleDocument;
        $field = new Opus_Model_Field('TitleMain');
        $field->setValue(new Opus_Model_Dependent_Title(null, new Opus_Db_DocumentTitleAbstracts()));
        $doc->addField($field);
        $form = $this->_builder->build($doc);
        $subform = $form->getSubForm('TitleMain');
        $this->assertNotNull($subform, 'No sub form with name "TitleMain" generated.');
    }
    
}
