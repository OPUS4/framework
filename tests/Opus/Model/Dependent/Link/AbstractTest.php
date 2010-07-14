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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for Opus_Model_Dependent_Link_Abstract
 *
 * @category    Tests
 * @package     Opus_Model
 *
 * @group       DependentLinkAbstractTest
 */
class Opus_Model_Dependent_Link_AbstractTest extends PHPUnit_Framework_TestCase {



    /**
     * Test querying the display name of a linked  model.
     *
     * @return void
     */
    public function testGetDisplayNameThroughLink() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel();
        $model->setDisplayName('AbstractTestMockDisplayName');
        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass(get_class($model));
        $link->setModel($model);
        $result = $link->getDisplayName();
        $this->assertEquals('AbstractTestMockDisplayName', $result, 'Display name of linked model not properly passed.');
    }

    /**
     * Test if the model class name can be retrieved.
     *
     * @return void
     */
    public function testGetModelClass() {
        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass('Opus_Model');

        $result = $link->getModelClass();
        $this->assertEquals('Opus_Model', $result, 'Given model class name and retrieved name do not match.');
    }

    /**
     * Test if a call to describe() on a Link Model not only tunnels the call to its
     * dependent but also delivers those fields owned by the link model itself.
     *
     * @return void
     */
    public function testDescribeShowsAdditionalFieldsOfLinkModel() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel;

        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass('Opus_Model_Dependent_Link_AbstractTestModel');
        $link->setModel($model);
        $link->addField(new Opus_Model_Field('LinkField'));

        $result = $link->describe();
        $this->assertTrue(in_array('LinkField', $result), 'Link models field missing.');
    }

    /**
     * Test if a call to describe() also returns that fields of the linked Model.
     *
     * @return void
     */
    public function testDescribeCallReturnsFieldsOfLinkedModel() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel;
        $model->addField(new Opus_Model_Field('AField'));

        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass('Opus_Model_Dependent_Link_AbstractTestModel');
        $link->setModel($model);
        $link->addField(new Opus_Model_Field('LinkField'));

        $result = $link->describe();
        $this->assertTrue(in_array('AField', $result), 'Linked models field missing.');
    }

    /**
     * Test if a call to describeAll() also returns that fields of the linked Model.
     *
     * @return void
     */
    public function testDescribeAllCallReturnsFieldsOfLinkedModel() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel;
        $model->addField(new Opus_Model_Field('AField'));

        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass('Opus_Model_Dependent_Link_AbstractTestModel');
        $link->setModel($model);
        $link->addField(new Opus_Model_Field('LinkField'));

        $result = $link->describe();
        $this->assertTrue(in_array('AField', $result), 'Linked models field missing.');
    }


    /**
     * Test if a Link Model not only tunnels its set/get calls but also
     * applies them to its very own fields.
     *
     * @return void
     */
    public function testLinkModelFieldsCanBeAccessedViaGetAndSet() {
        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->addField(new Opus_Model_Field('FieldValue'));
        $link->setFieldValue('FooBar');
        $this->assertEquals('FooBar', $link->getFieldValue(), 'Link Model field can not be accessed.');
    }

    /**
     * Test if the fields of an actual linked model can be accessed.
     *
     * @return void
     */
    public function testLinkedModelsFieldsCanBeAccessedViaGetAndSet() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel;
        $model->addField(new Opus_Model_Field('AField'));

        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass('Opus_Model_Dependent_Link_AbstractTestModel');
        $link->setModel($model);

        $link->setAField('FooBar');

        $this->assertEquals('FooBar', $link->getAField(), 'Field access tunneling to model failed.');
    }

    /**
     * Test if the Link Model tunnels add() calls.
     *
     * @return void
     */
    public function testLinkedModelsFieldsCanBeAccessedViaAdd() {
        $model = $this->getMock('Opus_Model_Dependent_Link_AbstractTestModel', array('__call'));
        $model->addField(new Opus_Model_Field('Multi'));

        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass(get_class($model));
        $link->setModel($model);

        $model->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('addMulti'), array(null));

        $link->addMulti(null);
    }

    /**
     * Test if describeUntunneled returns only link fields instead of all linked fields.
     *
     * @return void
     */
    public function testDescribeUntunneledReturnsOnlyLinkFields() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel;

        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass('Opus_Model_Dependent_Link_AbstractTestModel');
        $link->setModel($model);
        $link->addField(new Opus_Model_Field('LinkField'));

        $result = $link->describeUntunneled();

        $this->assertEquals(1, count($result), 'Result should only have one array element.');
        $this->assertEquals('LinkField', $result[0], 'Result should contain only a field "LinkField"');
    }
    
    /**
     * Test if the identifier of a newly created link model is null
     * if it has not been persisted yet.
     *
     * @return void
     */
    public function testPrimaryKeyOfTransientLinkModelIsNull() {
        if (false === class_exists('Opus_Model_Dependent_Link_Mock', false)) {
            eval('
                class Opus_Model_Dependent_Link_Mock
                extends Opus_Model_Dependent_Link_Abstract {
                    protected function _init() { }
                }
            ');
        }
        if (false === class_exists('Opus_Model_Dependent_Link_MockTableRow', false)) {
            eval('
                class Opus_Model_Dependent_Link_MockTableRow
                extends Zend_Db_Table_Row {
                    public $id1 = 1000;
                    public $id2 = 2000;
                }
            ');
        }
        
        if (false === class_exists('Opus_Model_Dependent_Link_MockTableGateway', false)) {
            eval('
                class Opus_Model_Dependent_Link_MockTableGateway
                extends Zend_Db_Table {
                    protected function _setup() {}
                    protected function _init() {}
                    public function createRow(array $data = array(), $defaultSource = null) {
                        $row = new Opus_Model_Dependent_Link_MockTableRow(array(\'table\' => $this));
                        return $row;
                    }
                    public function info($key = null) {
                        return array(\'primary\' => array(\'id1\',\'id2\'));
                    }
                }
            ');
        }
        
        $mockTableGateway = new Opus_Model_Dependent_Link_MockTableGateway;
        $link = new Opus_Model_Dependent_Link_Mock(null, $mockTableGateway);

        $this->assertTrue($link->isNewRecord(), 'Link Model should be based on a new record after creation.');
        $this->assertNull($link->getId(), 'Id of Link Model should be null if the Link Model is new,
            no matter what its primary key fields are set up to.');
    }
    
    /**
    * Test if setting a model changes the modifification status of the link model.
    *
    * @return void
    */
    public function testSettingAModelMarksLinkModelToBeModified() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel();
        $model->setDisplayName('AbstractTestMockDisplayName');
        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel();
        $link->setModelClass(get_class($model));
        $link->setModel($model);
        
        $this->assertTrue($link->isModified(), 'Call to setModel() does not set modification flag.');
    }
    
    /**
     * Test if toArray() on a Opus_Model_Dependent_Link_Abstract instance
     * returns all fields of the linked Model and fields of the LinkModel as well.
     *
     * @return void
     */   
    public function testToArrayShowsLinkModelFields() {
        $model = new Opus_Model_Dependent_Link_AbstractTestModel;
        $link = new Opus_Model_Dependent_Link_AbstractTestLinkModel;
        $link->setModelClass(get_class($model));
        $link->setModel($model);
        
        $model->addField(new Opus_Model_Field('Value'));
        $model->setValue(4711);
        
        $link->addField(new Opus_Model_Field('LinkModelField'));
        $link->setLinkModelField('Foo');
    
        $result = $link->toArray();
        $this->assertArrayHasKey('Value', $result, 'Linked Model field is missing.');
        $this->assertArrayHasKey('LinkModelField', $result, 'LinkModel field is missing.');
    }
    
}
