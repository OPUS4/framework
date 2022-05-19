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
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 */

namespace OpusTest\Model\Dependent\Link;

use Opus\Model\Field;
use Opus_Model_Dependent_Link_Mock;
use Opus_Model_Dependent_Link_MockTableGateway;
use OpusTest\TestAsset\TestCase;

use function class_exists;
use function count;
use function get_class;
use function in_array;

/**
 * Test cases for Opus\Model\Dependent\Link\AbstractLinkModel
 *
 * @category    Tests
 * @package     Opus\Model
 * @group       DependentLinkAbstractTest
 */
class AbstractLinkModelTest extends TestCase
{
    /**
     * Overwrite parent methods.
     */
    public function setUp()
    {
    }

    public function tearDown()
    {
    }

    /**
     * Test querying the display name of a linked  model.
     */
    public function testGetDisplayNameThroughLink()
    {
        $model = new AbstractModelMock();
        $model->setDisplayName('AbstractTestMockDisplayName');
        $link = new AbstractLinkModelMock();
        $link->setModelClass(get_class($model));
        $link->setModel($model);
        $result = $link->getDisplayName();
        $this->assertEquals('AbstractTestMockDisplayName', $result, 'Display name of linked model not properly passed.');
    }

    /**
     * Test if the model class name can be retrieved.
     */
    public function testGetModelClass()
    {
        $link = new AbstractLinkModelMock();
        $link->setModelClass('Opus\Model');

        $result = $link->getModelClass();
        $this->assertEquals('Opus\Model', $result, 'Given model class name and retrieved name do not match.');
    }

    /**
     * Test if a call to describe() on a Link Model not only tunnels the call to its
     * dependent but also delivers those fields owned by the link model itself.
     */
    public function testDescribeShowsAdditionalFieldsOfLinkModel()
    {
        $model = new AbstractModelMock();

        $link = new AbstractLinkModelMock();
        $link->setModelClass(AbstractModelMock::class);
        $link->setModel($model);
        $link->addField(new Field('LinkField'));

        $result = $link->describe();
        $this->assertTrue(in_array('LinkField', $result), 'Link models field missing.');
    }

    /**
     * Test if a call to describe() also returns that fields of the linked Model.
     */
    public function testDescribeCallReturnsFieldsOfLinkedModel()
    {
        $model = new AbstractModelMock();
        $model->addField(new Field('AField'));

        $link = new AbstractLinkModelMock();
        $link->setModelClass(AbstractModelMock::class);
        $link->setModel($model);
        $link->addField(new Field('LinkField'));

        $result = $link->describe();
        $this->assertTrue(in_array('AField', $result), 'Linked models field missing.');
    }

    /**
     * Test if a call to describeAll() also returns that fields of the linked Model.
     */
    public function testDescribeAllCallReturnsFieldsOfLinkedModel()
    {
        $model = new AbstractModelMock();
        $model->addField(new Field('AField'));

        $link = new AbstractLinkModelMock();
        $link->setModelClass(AbstractModelMock::class);
        $link->setModel($model);
        $link->addField(new Field('LinkField'));

        $result = $link->describe();
        $this->assertTrue(in_array('AField', $result), 'Linked models field missing.');
    }

    /**
     * Test if a Link Model not only tunnels its set/get calls but also
     * applies them to its very own fields.
     */
    public function testLinkModelFieldsCanBeAccessedViaGetAndSet()
    {
        $link = new AbstractLinkModelMock();
        $link->addField(new Field('FieldValue'));
        $link->setFieldValue('FooBar');
        $this->assertEquals('FooBar', $link->getFieldValue(), 'Link Model field can not be accessed.');
    }

    /**
     * Test if the fields of an actual linked model can be accessed.
     */
    public function testLinkedModelsFieldsCanBeAccessedViaGetAndSet()
    {
        $model = new AbstractModelMock();
        $model->addField(new Field('AField'));

        $link = new AbstractLinkModelMock();
        $link->setModelClass(AbstractModelMock::class);
        $link->setModel($model);

        $link->setAField('FooBar');

        $this->assertEquals('FooBar', $link->getAField(), 'Field access tunneling to model failed.');
    }

    /**
     * Test if the Link Model tunnels add() calls.
     */
    public function testLinkedModelsFieldsCanBeAccessedViaAdd()
    {
        $model = $this->getMockBuilder(AbstractModelMock::class)
            ->setMethods(['__call'])
            ->getMock();

        $model->addField(new Field('Multi'));

        $link = new AbstractLinkModelMock();
        $link->setModelClass(get_class($model));
        $link->setModel($model);

        $model->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('addMulti'), [null]);

        $link->addMulti(null);
    }

    /**
     * Test if describeUntunneled returns only link fields instead of all linked fields.
     */
    public function testDescribeUntunneledReturnsOnlyLinkFields()
    {
        $model = new AbstractModelMock();

        $link = new AbstractLinkModelMock();
        $link->setModelClass(AbstractModelMock::class);
        $link->setModel($model);
        $link->addField(new Field('LinkField'));

        $result = $link->describeUntunneled();

        $this->assertEquals(1, count($result), 'Result should only have one array element.');
        $this->assertEquals('LinkField', $result[0], 'Result should contain only a field "LinkField"');
    }

    /**
     * Test if the identifier of a newly created link model is null
     * if it has not been persisted yet.
     */
    public function testPrimaryKeyOfTransientLinkModelIsNull()
    {
        if (false === class_exists('Opus_Model_Dependent_Link_Mock', false)) {
            eval('
                class Opus_Model_Dependent_Link_Mock extends \Opus\Model\Dependent\Link\AbstractLinkModel {
                    protected function init() { }
                }
            ');
        }
        if (false === class_exists('Opus_Model_Dependent_Link_MockTableRow', false)) {
            eval('
                class Opus_Model_Dependent_Link_MockTableRow extends \Zend_Db_Table_Row {
                    public $id1 = 1000;
                    public $id2 = 2000;
                }
            ');
        }

        if (false === class_exists('Opus_Model_Dependent_Link_MockTableGateway', false)) {
            eval('
                class Opus_Model_Dependent_Link_MockTableGateway extends \Zend_Db_Table {
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

        $mockTableGateway = new Opus_Model_Dependent_Link_MockTableGateway();
        $link             = new Opus_Model_Dependent_Link_Mock(null, $mockTableGateway);

        $this->assertTrue($link->isNewRecord(), 'Link Model should be based on a new record after creation.');
        $this->assertNull($link->getId(), 'Id of Link Model should be null if the Link Model is new,
            no matter what its primary key fields are set up to.');
    }

    /**
     * Test if setting a model changes the modifification status of the link model.
     */
    public function testSettingAModelMarksLinkModelToBeModified()
    {
        $model = new AbstractModelMock();
        $model->setDisplayName('AbstractTestMockDisplayName');
        $link = new AbstractLinkModelMock();
        $link->setModelClass(get_class($model));
        $link->setModel($model);

        $this->assertTrue($link->isModified(), 'Call to setModel() does not set modification flag.');
    }

    /**
     * Test if toArray() on a Opus\Model\Dependent\Link\AbstractLinkModel instance
     * returns all fields of the linked Model and fields of the LinkModel as well.
     */
    public function testToArrayShowsLinkModelFields()
    {
        $model = new AbstractModelMock();
        $link  = new AbstractLinkModelMock();
        $link->setModelClass(get_class($model));
        $link->setModel($model);

        $model->addField(new Field('Value'));
        $model->setValue(4711);

        $link->addField(new Field('LinkModelField'));
        $link->setLinkModelField('Foo');

        $result = $link->toArray();
        $this->assertArrayHasKey('Value', $result, 'Linked Model field is missing.');
        $this->assertArrayHasKey('LinkModelField', $result, 'LinkModel field is missing.');
    }

    /**
     * Regression test for OPUSVIER-2304.
     */
    public function testIsValidChecksLinkedModel()
    {
        $model = new AbstractModelMock();
        $link  = new AbstractLinkModelMock();
        $link->setModelClass(get_class($model));
        $link->setModel($model);

        $this->assertTrue($link->isValid()); // model is valid

        $model->setValid(false);

        $this->assertFalse($link->isValid()); // model is not valid
    }
}
