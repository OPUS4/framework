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
 * @group    BuilderTest
 */
class Opus_Form_BuilderTest extends PHPUnit_Framework_TestCase {

    /**
     * Test of creating a Zend Form.
     *
     * @return void
     */
    public function testCreateForm() {
        $xmltype= '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="institute" />
                </documenttype>';
        $xmllayout = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="test">
                    <field name="institute" />
                </page>
            </formlayout>';

        $type = new Opus_Document_Type($xmltype);
        $layout = Opus_Form_Layout::fromXml($xmllayout);
        $form = Opus_Form_Builder::createForm($type, $layout);
        $this->assertType('Zend_Form', $form);

    }

    /**
     * Test if a form contain correct elements.
     *
     * @return void
     */
    public function testFormContainsElements() {
        $xmltype= '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="institute" />
                </documenttype>';
        $xmllayout = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="test">
                    <field name="institute" />
                </page>
            </formlayout>';

        $type = new Opus_Document_Type($xmltype);
        $layout = Opus_Form_Layout::fromXml($xmllayout);
        $form = Opus_Form_Builder::createForm($type, $layout);
        $expected = array('submit', 'form');
        $this->assertEquals($expected, array_keys($form->getElements()));
    }

    /**
     * Test if a form contain correct subform.
     *
     * @return void
     */
    public function testFormContainSubform() {
        $xmltype= '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="institute" />
                </documenttype>';
        $xmllayout = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="test">
                    <field name="institute" />
                </page>
            </formlayout>';

        $type = new Opus_Document_Type($xmltype);
        $layout = Opus_Form_Layout::fromXml($xmllayout);
        $form = Opus_Form_Builder::createForm($type, $layout);
        $expected = array('test');
        $this->assertEquals($expected, array_keys($form->getSubForms()));
    }

    /**
     * Test if a subform contain correct elements.
     *
     * @return void
     */
    public function testSubFormContainsElements() {
        $xmltype= '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="institute" />
                </documenttype>';
        $xmllayout = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="test">
                    <field name="institute" />
                </page>
            </formlayout>';

        $type = new Opus_Document_Type($xmltype);
        $layout = Opus_Form_Layout::fromXml($xmllayout);
        $form = Opus_Form_Builder::createForm($type, $layout);
        $subform = $form->getSubForm('test');
        $expected = array('institute');
        $this->assertEquals($expected, array_keys($subform->getElements()));
    }

    /**
     * Test to build a more complex form
     *
     * @return void
     */
    public function testCreateComplexForm() {
        $xmltype= '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="person_author" multiplicity="*" mandatory="yes" />
                    <field name="person_advisor" />
                    <field name="completed_date" />
                    <field name="institute" multiplicity="2" />
                    <field name="publisher_name" mandatory="yes" multiplicity="*" />
                    <field name="publisher_university" mandatory="yes" />
                </documenttype>';
        $xmllayout = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="author">
                    <group name="g1">
                        <field name="person_author" />
                        <field name="person_advisor" />
                    </group>
                    <field name="institute" />
                    <field name="completed_date" />
                </page>
                <page name="publisher">
                    <group name="g2">
                        <field name="publisher_name" />
                        <field name="publisher_university" />
                    </group>
                </page>
            </formlayout>';
        $type = new Opus_Document_Type($xmltype);
        $layout = Opus_Form_Layout::fromXml($xmllayout);
        $form = Opus_Form_Builder::createForm($type, $layout);
        // form check
        $this->assertType('Zend_Form', $form);
        $subforms = $form->getSubForms();
        $expected = array('author', 'publisher');
        $this->assertEquals($expected, array_keys($subforms));
        $elements = $form->getElements();
        $expected = array('submit', 'form');
        $this->assertEquals($expected, array_keys($elements));
        // check subform author
        $nestedsubforms = $form->author->getSubForms();
        $expected = array('g1', 'institute');
        $this->assertEquals($expected, array_keys($nestedsubforms));
        $nestedsubforms = $form->author->g1->getSubForms();
        $expected = array('person_author', 'person_advisor');
        $this->assertEquals($expected, array_keys($nestedsubforms));
        $elements = $form->author->getElements();
        $expected = array('completed_date');
        $this->assertEquals($expected, array_keys($elements));
        // check subform publisher
        $nestedsubforms = $form->publisher->getSubForms();
        $expected = array('g2');
        $this->assertEquals($expected, array_keys($nestedsubforms));
        $elements = $form->publisher->g2->getElements();
        $expected = array('publisher_name', 'publisher_university');
        $this->assertEquals($expected, array_keys($elements));
    }

    /**
     * Test if an empty value could be used as an element data
     *
     * @return void
     */
    public function testEmptyElementdataForSingleElement() {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Form_BuilderDelegateHelper::generateSingleElementDelegate('', array());
    }

    /**
     * Test if element data is a string
     *
     * @return void
     */
    public function testNonStringDataOnElementdataForSingleElement() {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Form_BuilderDelegateHelper::generateSingleElementDelegate(1, array());
    }

    /**
     * Test if typeinfo could be an empty array
     *
     * @return void
     */
    public function testEmptyArrayOnTypeinfoForSingleElement() {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Form_BuilderDelegateHelper::generateSingleElementDelegate('test', array());
    }

    /**
     * Test what happend if typeinfo does not have key value mandatory
     *
     * @return void
     */
    public function testEmptyMandatoryOnTypeInfo() {
        $result = Opus_Form_BuilderDelegateHelper::generateSingleElementDelegate('testname', array('type' => 'test'));
        $this->assertEquals(false, $result['mandatory']);
    }

    /**
     * Test to ensure typefields is not an empty array.
     *
     * @return void
     */
    public function testEmptyArrayOnTypefieldsForSubElements() {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Form_BuilderDelegateHelper::generateSubElementsDelegate(array('test'), array());
    }

    /**
     * Test that typeinfo is an array.
     *
     * @return void
     */
    public function testEmptyArrayOnTypeinfoInsideSubElements() {
        $this->setExpectedException('Opus_Form_Exception');
        Opus_Form_BuilderDelegateHelper::generateSubElementsDelegate(array('test'), array('test' => 'blub'));
    }

    /**
     * Raise an exception if an element is not available in typefield definition
     *
     * @return void
     */
    public function testElementNotAvailableInTypefields() {
        $this->setExpectedException('Opus_Form_Exception');
        Opus_Form_BuilderDelegateHelper::generateSubElementsDelegate(array('test2'), array('test' => 'blub'));
    }

    /**
     * Test what happened if keypattern is not found
     *
     * @return void
     */
    public function testNothingFoundOnFindPathToKey() {
        $haystack = array('key' => 'value');
        $result = Opus_Form_BuilderDelegateHelper::findPathToKeyDelegate('test', $haystack);
        $this->assertNull($result);
    }

    /**
     * Test that a deep search on haystack works.
     *
     * @return void
     */
    public function testDeepSearchOnFindPathToKey() {
        $haystack = array('key' => 'value', 'key2' => array('name' => 'test2', 'deeper' => array('test' => 'info')));
        $result = Opus_Form_BuilderDelegateHelper::findPathToKeyDelegate('test', $haystack);
        $expected = array('deeper', 'key2');
        $this->assertEquals($expected, $result);
    }

    /**
     * Tries to recreate a form.
     *
     * @return void
     */
    public function testRecreateForm() {
        $xmltype= '<?xml version="1.0" encoding="UTF-8" ?>
                <documenttype name="doctoral_thesis"
                    xmlns="http://schemas.opus.org/documenttype"
                    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                    <field name="institute" />
                </documenttype>';
        $xmllayout = '<?xml version="1.0" encoding="UTF-8"?>
            <formlayout name="general" xmlns="http://schemas.opus.org/formlayout"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
                <page name="test">
                    <field name="institute" />
                </page>
            </formlayout>';

        $type = new Opus_Document_Type($xmltype);
        $layout = Opus_Form_Layout::fromXml($xmllayout);
        $form = Opus_Form_Builder::createForm($type, $layout);

        $data = $form->getValues();
        $new_form = Opus_Form_Builder::recreateForm($data);
        $this->assertType('Zend_Form', $new_form);
    }

    /**
     * Test if adding a field works as expected
     *
     * @return void
     */
    public function testAddAdditionalField() {
        $daten = array(
            'test'=> array(
                'institute' => array(
                    1 => array(
                        'institute' => ''
                    ),
                    'add_institute' => '+'
                )
            ),
            'form' => 'a:1:{i:0;a:2:{s:4:"name";s:4:"test";s:8:"elements";a:1:{i:0;a:5:{s:4:"name";s:9:"institute";s:3:"add";b:1;s:3:"seq";i:1;s:8:"maxmulti";s:1:"*";s:8:"elements";a:1:{i:0;a:2:{s:4:"name";i:1;s:8:"elements";a:1:{i:0;a:4:{s:4:"name";s:9:"institute";s:4:"type";s:4:"text";s:9:"validator";i:180;s:9:"mandatory";s:2:"no";}}}}}}}}'
        );
        $form = Opus_Form_Builder::recreateForm($daten);
        // general form check
        $this->assertType('Zend_Form', $form);
        $subforms = $form->getSubForms();
        $expected = array('test');
        $this->assertEquals($expected, array_keys($subforms));
        $elements = $form->getElements();
        $expected = array('submit', 'form');
        $this->assertEquals($expected, array_keys($elements));
        // subform 'test' checks
        $nestedsubforms = $form->test->getSubForms();
        $expected = array('institute');
        $this->assertEquals($expected, array_keys($nestedsubforms));
        $nestedsubforms = $form->test->institute->getSubForms();
        $expected = array(1, 2);
        $this->assertEquals($expected, array_keys($nestedsubforms));
    }

    /**
     * Test if removing a field works as expected
     *
     * @return void
     */
    public function testRemoveAdditionalField() {
        $daten = array(
            'test'=> array(
                'institute' => array(
                    1 => array(
                        'institute' => ''
                    ),
                    2 => array(
                        'institute' => '',
                        'remove_institute_2' => '-'
                    )
                )
            ),
            'form' => 'a:1:{i:0;a:2:{s:4:"name";s:4:"test";s:8:"elements";a:1:{i:0;a:5:{s:4:"name";s:9:"institute";s:3:"add";b:1;s:3:"seq";i:2;s:8:"maxmulti";s:1:"*";s:8:"elements";a:2:{i:0;a:2:{s:4:"name";i:1;s:8:"elements";a:1:{i:0;a:4:{s:4:"name";s:9:"institute";s:4:"type";s:4:"text";s:9:"validator";i:180;s:9:"mandatory";s:2:"no";}}}i:1;a:3:{s:4:"name";i:2;s:8:"elements";a:1:{i:0;a:4:{s:4:"name";s:9:"institute";s:4:"type";s:4:"text";s:9:"validator";i:180;s:9:"mandatory";s:2:"no";}}s:6:"remove";b:1;}}}}}}'
        );
        $form = Opus_Form_Builder::recreateForm($daten);
        // general form check
        $this->assertType('Zend_Form', $form);
        $subforms = $form->getSubForms();
        $expected = array('test');
        $this->assertEquals($expected, array_keys($subforms));
        $elements = $form->getElements();
        $expected = array('submit', 'form');
        $this->assertEquals($expected, array_keys($elements));
        // subform 'test' checks
        $nestedsubforms = $form->test->getSubForms();
        $expected = array('institute');
        $this->assertEquals($expected, array_keys($nestedsubforms));
        $nestedsubforms = $form->test->institute->getSubForms();
        $expected = array(1);
        $this->assertEquals($expected, array_keys($nestedsubforms));
    }
}