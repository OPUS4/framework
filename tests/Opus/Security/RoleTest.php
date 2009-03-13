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
 * @package     Opus_Security
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test case for Opus_Security_Role. 
 *
 * @category    Tests
 * @package     Opus_Security
 * 
 * @group       RoleTest
 */
class Opus_Security_RoleTest extends PHPUnit_Framework_TestCase {
    
    
    /**
     * Provide a clean roles table.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('roles');
    }
    
    /**
     * Purge test data.
     *
     * @return void
     */
    public function tearDown() {
        TestHelper::clearTable('roles');
    }
    
    /**
     * Test if creating a new role.
     *
     * @return void
     */
    public function testCreate() {
        $role = new Opus_Security_Role;
    }
    
    /**
     * Test if a role object is invalid when it has no name assigned.
     *
     * @return void
     */
    public function testRoleIsInvalidWithoutName() {
        $role = new Opus_Security_Role;
        $this->assertFalse($role->isValid(), 'Role without a name should be invalid.');
    }
 
    /**
     * Test set up and assignment of a parent role object.
     *
     * @return void
     */
    public function testSetParentRole() {
        $parent = new Opus_Security_Role;
        $parent->setName('Parent');
        
        $child = new Opus_Security_Role;
        $child->setParent($parent);
        $child->setName('Child');
        
        $result = $child->getParent();
        
        $this->assertEquals($parent, $result, 'Wrong parent role object retrieved.');
    }
    
    
    
    /**
     * Test set up and assignment of multiple parent role objects.
     *
     * @return void
     */
    public function testAssignASetOfParentRoles() {
        $this->markTestSkipped('Not implemented yet for DB model.');
    
        $parents = array();
        for($i=0; $i<10; $i++) {
            $parent = new Opus_Security_Role;
            $parent->setName('Role' . $i);
            $parents[] = $parent;
        }
        
        $child = new Opus_Security_Role;
        $child->setName('Child');
        
        foreach ($parents as $parent) {
            $child->addParent($parent);
        }
        
        $result = $child->getParent();
        
        $this->assertFalse(empty($result), 'Expect non-empty array.');
        $this->assertEquals(10, count($result), 'Expect 10 elements in array.');
    }
    
    
    /**
     * Test set up and assignment of multiple parent role objects.
     *
     * @return void
     */
    public function testRoleImplementsZendAclRoleInterface() {
        $role = new Opus_Security_Role;
        $role->setName('Role');
        
        $this->assertTrue($role instanceof Zend_Acl_Role_Interface, 'Interface implementation expected.');
    }
    
    /**
     * Test if a role identifier contains class- and role name.
     *
     * @return void
     */
    public function testRoleIdentifierContainsClassname() {
        $role = new Opus_Security_Role;
        $role->setName('MyRole');

        $rid = $role->getRoleId();
        $this->assertEquals('Opus/Security/Role/MyRole', $rid, 'Wrong role identifier returned.');
    }
    
    /**
     * Test if a role identifier contains class- and role name and id.
     *
     * @return void
     */
    public function testRoleIdentifierContainsIdAfterStore() {
        $role = new Opus_Security_Role;
        $role->setName('MyRole');
        $id = $role->store();

        $rid = $role->getRoleId();
        $this->assertEquals('Opus/Security/Role/MyRole/' . $id, $rid, 'Wrong role identifier returned.');
    }
    
    /**
     * Test set up and assignment of multiple parent role objects.
     *
     * @return void
     */
    public function testRoleExtendsOpusModelAbstractDb() {
        $role = new Opus_Security_Role;
        $this->assertTrue($role instanceof Opus_Model_AbstractDb, 'AbstractDb implementation expected.');
    }
 
    /**
     * Test if model is properly configured for beeing persisted.
     *
     * @return void
     */   
    public function testStoreRoleModel() {
        $role = new Opus_Security_Role;
        $role->setName('MyRole');
        $id = $role->store();
        
        $role = new Opus_Security_Role($id);
        $this->assertEquals('MyRole', $role->getName(), 'Wrong Role model returned.');
    }

    /**
     * Test if model can be properly stored and retrieved
     * with a parent role assigned.
     *
     * @return void
     */   
    public function testStoreFetchRoleWithParent() {
        $parent = new Opus_Security_Role;
        $parent->setName('Parent');
        $parent->store();

        $role = new Opus_Security_Role;
        $role->setName('MyRole')
            ->setParent($parent);
        $id = $role->store();
        
        $role = new Opus_Security_Role($id);
        $this->assertEquals('MyRole', $role->getName(), 'Wrong Role model returned.');
        $this->assertNotNull($role->getParent(), 'Parent object not loaded.');
        $this->assertEquals($parent->getResourceId(), $role->getParent()->getResourceId(), 'Wrong Role model returned.');
    }

    
    /**
     * Test retrieve a role by id.
     *
     * @return void
     */   
    public function testRetrieveByRoleId() {
        $role = new Opus_Security_Role;
        $role->setName('MyRole');
        $role->store();
        $roleId = $role->getRoleId();
        
        $role = Opus_Security_Role::getByRoleId($roleId);
        $this->assertNotNull($role, 'No model object returned.');
        $this->assertTrue($role instanceof Opus_Security_Role, 'Wrong type returned.');
        $this->assertEquals($roleId, $role->getRoleId(), 'Role ids dont match.');
    }
    
    /**
     * Test check existence of model by role by id.
     *
     * @return void
     */   
    public function testQueryExistenceByRoleId() {
        $role = new Opus_Security_Role;
        $role->setName('MyRole');
        $role->store();
        $roleId = $role->getRoleId();
        
        $exists = Opus_Security_Role::isRoleIdExistent($roleId);
        $this->assertTrue($exists, 'Model should be existent.');
    }
    
}
