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
 * Test case for Opus_Security_RoleRegistry. 
 *
 * @category    Tests
 * @package     Opus_Security
 * 
 * @group       RoleRegistryTest
 */
class Opus_Security_RoleRegistryTest extends PHPUnit_Framework_TestCase {
    
    /**
     * Provide a clean roles table.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('roles');
    }
    
    /**
     * Clear the roles table.
     *
     * @return void
     */
    public function tearDown() {
        TestHelper::clearTable('roles');
    }
    
    /**
     * Test if creating a new role registry.
     *
     * @return void
     */
    public function testCreate() {
        $role = new Opus_Security_RoleRegistry;
    }
  
    /**
     * Test if a persistent role is treated as registered by the registry.
     *
     * @return void
     */ 
    public function testPersistedRoleIsRegistered() {
        // Making a role persistent makes it also available in the registry.
        $roles = new Opus_Db_Roles;
        $roles->insert(array('name' => 'MyRole'));

        $reg = new Opus_Security_RoleRegistry;
        $result = $reg->has('MyRole');
        
        $this->assertTrue($result, 'Persistent Role is not recocnized as registered.');
    }
    
    /**
     * Test if a persited role can be instanciated via
     * the registrys get method.
     *
     * @return void
     */
    public function testPersitedRoleGetsReturnedByGet() {
        // Making a role persistent makes it also available in the registry.
        $roles = new Opus_Db_Roles;
        $roles->insert(array('name' => 'MyRole'));

        $reg = new Opus_Security_RoleRegistry;
        $result = $reg->get('MyRole');
        $this->assertEquals('MyRole', $result->getRoleId(), 'Persisted and retrieved role identifier dont match.');
    }
    
    
    /**
     * Test if a role has a parent role assigned, this
     * parent role is known by the registry.
     *
     * @return void
     */
    public function testPersistedChildInheritsPersistedParentRole() {
        $roles  = new Opus_Db_Roles;
        $parent = $roles->insert(array('name' => 'Parent'));
        $roles->insert(array('name' => 'Child', 'parent' => $parent));
        
        $reg = new Opus_Security_RoleRegistry;
        $parent = $reg->get('Parent');
        $child  = $reg->get('Child');
        
        $result = $reg->inherits($child, $parent);
        $this->assertTrue($result, 'Persisted child Role does not inherit parent Role.');
    }
    
    /**
     * Test a role is to be found by the registry even if it is not a persisted
     * Opus_Security_Role object.
     *
     * @return void
     */
    public function testRegistryFindsRolesThatAreNotOpusSecurityRoleInstances() {
        $roles = new Opus_Db_Roles;
        $roles->insert(array('name' => 'guest'));
        
        $reg = new Opus_Security_RoleRegistry;
        $result = $reg->has('guest');        
        $this->assertTrue($result, 'Role record in the database gets not found by the Registry.');
    }
    
    
    /**
     * Test if a Role's database ID can be retrieved
     *
     * @return void
     */
    public function testGetDatabaseIdForRole() {
        $roles = new Opus_Db_Roles;
        $id = $roles->insert(array('name' => 'guest'));
        
        $reg = new Opus_Security_RoleRegistry;
        $result = $reg->getId('guest');        
        $this->assertEquals($id, $result, 'Role database id and retrieved id do not match.');
    }   
    
}
