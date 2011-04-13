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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test for Opus_Security_Realm.
 *
 * @package Opus_Security
 * @category Tests
 *
 * @group RealmTest
 */
class Opus_Security_RealmTest extends TestCase {

    /**
     * Test getting singleton instance.
     *
     * @return void
     */
    public function testGetInstance() {
        $realm = Opus_Security_Realm::getInstance();
        $this->assertNotNull($realm, 'Expected instance');
        $this->assertType('Opus_Security_Realm', $realm, 'Expected object of type Opus_Security_Realm.');
    }
    
    /**
     * Test if a given user account (identity) can be mapped
     * correctly to its assigned role.
     *
     * @return void
     */
    public function testIdentityCanBeMappedToSingleRole() {
        // create account
        $acc = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $accId = $acc->insert(array('login' => 'user', 'password' => md5('useruser')));
        
        // create role
        $rol = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles');
        $rolId = $rol->insert(array('name' => 'role'));
        
        // connect role and account
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_LinkAccountsRoles');
        $lar->insert(array('account_id' => $accId, 'role_id' => $rolId));
        
        // query Realm
        $realm = Opus_Security_Realm::getInstance();
        $this->markTestIncomplete( 'Method does not exist (any more).' );
        $result = $realm->getIdentityRole('user');
        
        $this->assertNotNull($result, 'Expect assigned role.');
        $this->assertEquals('role', $result, 'Wrong role returned.');
    }


    /**
     * Test if a given user account (identity) can be mapped
     * correctly to its assigned roles.
     *
     * @return void
     */
    public function testIdentityCanBeMappedToMultipleRoles() {
        // create account
        $acc = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $accId = $acc->insert(array('login' => 'user', 'password' => md5('useruser')));
        
        // create role
        $rol = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles');
        $rolId[] = $rol->insert(array('name' => 'role1'));
        $rolId[] = $rol->insert(array('name' => 'role2'));
        
        
        // connect role and account
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_LinkAccountsRoles');
        $lar->insert(array('account_id' => $accId, 'role_id' => $rolId[0]));
        $lar->insert(array('account_id' => $accId, 'role_id' => $rolId[1]));
        
        // query Realm
        $realm = Opus_Security_Realm::getInstance();
        $this->markTestIncomplete( 'Method does not exist (any more).' );
        $result = $realm->getIdentityRole('user');
        
        $this->assertNotNull($result, 'Expect assigned role.');
        $this->assertTrue(is_array($result), 'Expect result to be an array of roles.');
        $this->assertTrue(in_array('role1', $result), 'Wrong set of roles returned.');
        $this->assertTrue(in_array('role2', $result), 'Wrong set of roles returned.');
    }
    
    /**
     * Test if null is retrieved if no Roles are assigned to an Account.
     *
     * @return void
     */
    public function testNoRolesReturnedWhenNoRolesAssigned() {
        // create account
        $acc = Opus_Db_TableGateway::getInstance('Opus_Db_Accounts');
        $accId = $acc->insert(array('login' => 'user', 'password' => md5('useruser')));

        $realm = Opus_Security_Realm::getInstance();
        $this->markTestIncomplete( 'Method does not exist (any more).' );
        $result = $realm->getIdentityRole('user');
        $this->assertNull($result, 'Expected null if no roles are assigned.');
   }

    /**
     * Test if exception gets thrown if the specified Account doesnt exist.
     *
     * @return void
     */
    public function testThrowExceptionIfAccountDontExist() {
        $realm = Opus_Security_Realm::getInstance();
        $this->setExpectedException('Opus_Security_Exception');
        $this->markTestIncomplete( 'Method does not exist (any more).' );
        $result = $realm->getIdentityRole('user');
   }
   
   /**
     * Test if a given IP address can be mapped correctly to its assigned role.
     *
     * @return void
     */
    public function testIpCanBeMappedToSingleRole() {
        // create ip address
        $this->markTestIncomplete( 'Class does not exist (any more).' );
        $ip = Opus_Db_TableGateway::getInstance('Opus_Db_Ipadresses');
        $ipId = $ip->insert(array('ipaddress' => '127.0.0.1'));
        
        // create role
        $rol = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles');
        $rolId = $rol->insert(array('name' => 'role'));
        
        // connect role and ip
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_LinkIpaddressesRoles');
        $lar->insert(array('ipaddress_id' => $ipId, 'role_id' => $rolId));
        
        // query Realm
        $realm = Opus_Security_Realm::getInstance();
        $result = $realm->getIpaddressRole('127.0.0.1');
        
        $this->assertNotNull($result, 'Expect assigned role.');
        $this->assertEquals('role', $result, 'Wrong role returned.');
    }

   /**
     * Test if a given IP address can be mapped correctly to its assigned roles.
     *
     * @return void
     */
    public function testIpCanBeMappedToMultipleRoles() {
        // create ip address
        $this->markTestIncomplete( 'Class does not exist (any more).' );
        $ip = Opus_Db_TableGateway::getInstance('Opus_Db_Ipadresses');
        $ipId = $ip->insert(array('ipaddress' => '127.0.0.1'));
        
        // create role
        $rol = Opus_Db_TableGateway::getInstance('Opus_Db_UserRoles');
        $rolId[0] = $rol->insert(array('name' => 'role1'));
        $rolId[1] = $rol->insert(array('name' => 'role2'));
        
        // connect role and ip
        $lar = Opus_Db_TableGateway::getInstance('Opus_Db_LinkIpaddressesRoles');
        $lar->insert(array('ipaddress_id' => $ipId, 'role_id' => $rolId[0]));
        $lar->insert(array('ipaddress_id' => $ipId, 'role_id' => $rolId[1]));
        
        // query Realm
        $realm = Opus_Security_Realm::getInstance();
        $result = $realm->getIpaddressRole('127.0.0.1');
        
        $this->assertNotNull($result, 'Expect assigned role.');
        $this->assertTrue(is_array($result), 'Expect result to be an array of roles.');
        $this->assertTrue(in_array('role1', $result), 'Wrong set of roles returned.');
        $this->assertTrue(in_array('role2', $result), 'Wrong set of roles returned.');
   }


}
