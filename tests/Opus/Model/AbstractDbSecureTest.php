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
 * Security related test cases for class Opus_Model_AbstractDbSecure.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group AbstractDbSecureTest
 */
class Opus_Model_AbstractDbSecureTest extends PHPUnit_Framework_TestCase {

    /**
     * Actual security realm.
     *
     * @var Opus_Security_Realm
     */
    protected $_realm = null;

    /**
     * Register Acl component, setup roles and resources.
     *
     * @return void
     */
    public function setUp() {
        // Create table for TestModel
        $dba = Zend_Db_Table::getDefaultAdapter();
        try {
            $dba->deleteTable('testtable');
        } catch (Exception $ex) {
            // CodeSniffer dope
            $noop = 12;
        }
        $dba->createTable('testtable');
        $dba->addField('testtable', array('name' => 'value', 'type' => 'varchar', 'length' => 23));

        // Setup Realm
        $this->_realm = Opus_Security_Realm::getInstance();
        $this->_realm->setAcl(new Zend_Acl);

        // Roles
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->getAcl()->addRole($anybody);
        $this->_realm->setRole($anybody);

        // Add model Resource
        $this->_realm->getAcl()->add(new Zend_Acl_Resource('Opus/Model/ModelAbstractDbSecure'));
    }

    /**
     * Remove temporary table.
     * Tear down access control list.
     *
     * @return void
     */
    public function tearDown() {
        Opus_Security_Realm::getInstance()->setAcl(null);
        TestHelper::dropTable('test_testtable');
    }


    /**
     * Test if the permission also gets queried when no standard role
     * is assigned in the security realm.
     *
     * @return void
     */
    public function testPermissonGetsQueriedWhenNoRoleIsSet() {
        $this->_realm->setRole(null);
        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $this->setExpectedException('Opus_Security_Exception');
        $id = $model->store();
    }

    /**
     * Test if persisting a model throws exception if "create" is not permitted.
     *
     * @return void
     */
    public function testCreateThrowsExceptionIfNotPermitted() {
        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $this->setExpectedException('Opus_Security_Exception');
        $id = $model->store();
    }

    /**
     * Test if persisting a model throws exception if "create" is not permitted.
     *
     * @return void
     */
    public function testConstructionFromIdThrowsExceptionIfReadNotPermitted() {
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $id = $model->store();

        $this->setExpectedException('Opus_Security_Exception');
        $model = new Opus_Model_ModelAbstractDbSecure($id);
    }

    /**
     * Test if persisting a model throws exception if "create" is not permitted.
     *
     * @return void
     */
    public function testConstructionFromTableRowThrowsExceptionIfReadNotPermitted() {
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $id = $model->store();

        $table = Opus_Db_TableGateway::getInstance('Opus_Model_AbstractTableProvider');
        $row = $table->find($id)->current();

        $this->setExpectedException('Opus_Security_Exception');
        $model = new Opus_Model_ModelAbstractDbSecure($row);
    }

    /**
     * Test if the model throws an exception on attempt to perform
     * prohibited update of model.
     *
     * @return void
     */
    public function testStoreThrowsExceptionIfUpdateIsNotGranted() {
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $id = $model->store();

        // Grant read access to model
        $this->_realm->getAcl()->allow('anybody', $model, 'read');

        $model = new Opus_Model_ModelAbstractDbSecure($id);
        $model->setValue('FooBar');

        $this->setExpectedException('Opus_Security_Exception');
        $model->store();
    }

    /**
     * Test if an exception is thrown on a store of a model with given id
     * if not permitted.
     *
     * @return void
     */
    public function testStoreThrowsExceptionIfUpdateIsNotGrantedForModelWithId() {
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $model->store();

        // Second try, now on model with given id
        $model->setValue('modified!');
        $this->setExpectedException('Opus_Security_Exception');
        $model->store();
    }


    /**
     * Test if an exception is thrown in delete operation of a model with given id
     * if not permitted.
     *
     * @return void
     */
    public function testDeleteThrowsExceptionIfDeleteIsNotGrantedForModelWithId() {
        // Grant update permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'update');
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $model->store();

        // Attempt to remove model without having the permission to do so.
        $this->setExpectedException('Opus_Security_Exception');
        $model->delete();
    }

    /**
     * Test if a new Resource entry gets registered after successful call to store().
     *
     * @return void
     */
    public function testResourceRegisteredOnStore() {
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo')->store();

        $acl = $this->_realm->getAcl();
        $this->assertTrue($acl->has($model), 'Model resource not registered after store.');
    }


    /**
     * Test format of resource id is class name.
     *
     * @return void
     */
    public function testResourceIdFormat() {
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $id = $model->store();

        $resid = $model->getResourceId();
        $this->assertEquals('Opus/Model/ModelAbstractDbSecure/'.$id, $resid, 'Wrong standard resource id. Expected class name');
    }


    /**
     * Test if the Model registeres itsefl as a resource in the Acl when stored.
     *
     *
     * @return void
     */
    public function testModelRegistersItselfAsResource() {
        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');
        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $id = $model->store();

        $acl = $this->_realm->getAcl();
        $this->assertTrue($acl->has($model), 'Model gets not registered as resource.');
    }

    /**
     * Test if the master resource is set after store.
     *
     * @return void
     */
    public function testMasterResourceIsSetAfterStore() {
        // Create master and register resource
        $master = new Zend_Acl_Resource('MASTER');
        $this->_realm->getAcl()->add($master);
        $this->_realm->setResourceMaster($master);

        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');
        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $id = $model->store();

        // Check if $model is child of $master
        $acl = $this->_realm->getAcl();
        $this->assertTrue($acl->inherits($model, $master), 'Master resource has not been set.');
    }

    /**
     * Test permission can be inherited from master resource. This is rather
     * a Acl test then a AbstractDbSecure test but lets do it anyway for the
     * sake of end-to-end argument :)
     *
     * @return void
     */
    public function testInheritPermissonFromMasterResource() {
        // Create master and register resource
        $master = new Zend_Acl_Resource('MASTER');
        $this->_realm->getAcl()->add($master);
        $this->_realm->setResourceMaster($master);

        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');
        $model = new Opus_Model_ModelAbstractDbSecure;
        $model->setValue('Foo');
        $id = $model->store();

        // Deny everything on the master resource
        $this->_realm->getAcl()->deny('anybody', 'MASTER');

        // Expect read operation to fail
        $this->setExpectedException('Opus_Security_Exception');
        $model = new Opus_Model_ModelAbstractDbSecure($id);
    }

    /**
     * Test if the containing Model passes itself as master resource.
     *
     * @return void
     */
    public function testMasterResourceGetsSetForDependendModels() {
        // Create master and register resource
        $master = new Zend_Acl_Resource('MASTER');
        $this->_realm->getAcl()->add($master);
        $this->_realm->setResourceMaster($master);

        $model = new Opus_Model_ModelAbstractDbSecure;
        $dependend = $this->getMock('Opus_Model_ModelDependentMock');
        $model->setExternalModel($dependend);

        $dependend->expects($this->once())
            ->method('setMasterResource');

        // Grant create permission
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstractDbSecure', 'create');

        $model->store();
    }
}
