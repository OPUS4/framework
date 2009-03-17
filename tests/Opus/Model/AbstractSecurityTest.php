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
 * Security related test cases for class Opus_Model_Abstract.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group AbstractSecurityTest
 */
class Opus_Model_AbstractSecurityTest extends PHPUnit_Framework_TestCase {

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
        // Setup Realm
        $this->_realm = Opus_Security_Realm::getInstance();
    
    
        // Create access control list
        $this->_realm->setAcl(new Zend_Acl);
        
        // Roles
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->getAcl()->addRole($anybody);
        
        // Resources
        $this->_realm->getAcl()->add(new Zend_Acl_Resource('Opus/Model/ModelAbstract'));
    }
    
    /**
     * Tear down access control list.
     *
     * @return void
     */
    public function tearDown() {
        Opus_Security_Realm::getInstance()->setAcl(null);
    }

    /**
     * Test if the model throws an exception on attempt to perform
     * prohibited creation of model.
     *
     * @return void
     */
    public function testModelThrowsExceptionIfCreationIsProhibited() {
        // Set current role        
        $this->_realm->setRole(new Zend_Acl_Role('anybody'));
        $this->setExpectedException('Opus_Security_Exception');
        $model = new Opus_Model_ModelAbstract;
    }
    
    /**
     * Test if the model throws an exception on attempt to perform
     * prohibited creation by xml of model.
     *
     * @return void
     */
    public function testFromXmlThrowsExceptionIfCreationIsProhibited() {
        // Set current role        
        $this->_realm->setRole(new Zend_Acl_Role('anybody'));
        $this->setExpectedException('Opus_Security_Exception');
        $model = Opus_Model_ModelAbstract::fromXml('<Opus_Model_ModelAbstract/>');
    }
    
    /**
     * Test if the model behaves normal on attempt to perform an
     * permitted create operation.
     *
     * @return void
     */
    public function testModelCreatesIfCreationIsPermitted() {
        // Set current role        
        $this->_realm->setRole(new Zend_Acl_Role('anybody'));
        
        // Allow "anybody" to create models.
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstract', 'create');
        
        $model = new Opus_Model_ModelAbstract;
        $this->assertNotNull($model, 'Creation of a model should be permitted.');
    }
    
    /**
     * Test if the model behaves normal on attempt to perform an
     * permitted create operation as a specific Role.
     *
     * @return void
     */
    public function testModelCreatesIfCreationIsPermittedForSpecialRole() {
        // Set current role
        $anybody = new Zend_Acl_Role('anybody');
        $submitter = new Zend_Acl_Role('submitter');
        $this->_realm->getAcl()->addRole($submitter, $anybody);
        $this->_realm->setRole($submitter);
        
        // Allow "submitter" to create models.
        $this->_realm->getAcl()->allow('submitter', 'Opus/Model/ModelAbstract', 'create');
        
        $model = new Opus_Model_ModelAbstract;
        $this->assertNotNull($model, 'Creation of a model should be permitted.');
    }
    
    /**
     * If 'edit' is not granted, set...() call throws exception.
     *
     * @return void
     */
    public function testSetThrowExceptionWhenEditNotPermitted() {
        // Set current role
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->setRole($anybody);
        
        // Allow create operation.
        $this->_realm->getAcl()->allow($anybody, 'Opus/Model/ModelAbstract', 'create');
        $model = new Opus_Model_ModelAbstract;
         
        $this->setExpectedException('Opus_Security_Exception');
        $model->setValue('thisCallShouldRaiseException');
    }

    /**
     * If 'edit' is not granted, add...() call throws exception.
     *
     * @return void
     */
    public function testAddThrowExceptionWhenEditNotPermitted() {
        // Set current role
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->setRole($anybody);
        
        // Allow create operation.
        $this->_realm->getAcl()->allow($anybody, 'Opus/Model/ModelAbstract', 'create');
        $model = new Opus_Model_ModelAbstract;
         
        $this->setExpectedException('Opus_Security_Exception');
        $model->addValue();
    }

    /**
     * Test if the fromXml() throws exception if the model can not
     * be edited by the current role.
     *
     * @return void
     */
    public function testFromXmlThrowsExceptionIfEditProhibited() {
        // Set current role        
        $this->_realm->setRole(new Zend_Acl_Role('anybody'));

        // Allow create operation.
        $this->_realm->getAcl()->allow('anybody', 'Opus/Model/ModelAbstract', 'create');

        $this->setExpectedException('Opus_Security_Exception');
        $model = Opus_Model_ModelAbstract::fromXml('<Opus_Model_ModelAbstract Value="Foo"/>');
    }

    /**
     * If 'read' is not granted, get...() call throws exception.
     *
     * @return void
     */
    public function testGetThrowExceptionWhenReadNotPermitted() {
        // Set current role
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->setRole($anybody);
        
        // Allow create operation.
        $this->_realm->getAcl()->allow($anybody, 'Opus/Model/ModelAbstract', 'create');
        $model = new Opus_Model_ModelAbstract;
         
        $this->setExpectedException('Opus_Security_Exception');
        $val = $model->getValue();
    }

    /**
     * If 'read' is not granted, get...() call throws exception.
     *
     * @return void
     */
    public function testGetFieldThrowExceptionWhenReadNotPermitted() {
        // Set current role
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->setRole($anybody);
        
        // Allow create operation.
        $this->_realm->getAcl()->allow($anybody, 'Opus/Model/ModelAbstract', 'create');
        $model = new Opus_Model_ModelAbstract;
         
        $this->setExpectedException('Opus_Security_Exception');
        $val = $model->getField('Value');
    }

    /**
     * If 'read' is not granted, toArray() call throws exception.
     *
     * @return void
     */
    public function testToArrayThrowExceptionWhenReadNotPermitted() {
        // Set current role
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->setRole($anybody);
        
        // Allow create operation.
        $this->_realm->getAcl()->allow($anybody, 'Opus/Model/ModelAbstract', 'create');
        $model = new Opus_Model_ModelAbstract;
         
        $this->setExpectedException('Opus_Security_Exception');
        $val = $model->toArray();
    }

    /**
     * If 'read' is not granted, toXml() call throws exception.
     *
     * @return void
     */
    public function testToXmlThrowExceptionWhenReadNotPermitted() {
        // Set current role
        $anybody = new Zend_Acl_Role('anybody');
        $this->_realm->setRole($anybody);
        
        // Allow create operation.
        $this->_realm->getAcl()->allow($anybody, 'Opus/Model/ModelAbstract', 'create');
        $model = new Opus_Model_ModelAbstract;
         
        $this->setExpectedException('Opus_Security_Exception');
        $val = $model->toXml();
    }

}
