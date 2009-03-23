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
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test case for Opus_Security_Acl.
 *
 * @category    Tests
 * @package     Opus_Security
 *
 * @group       AclTest
 */
class Opus_Security_AclTest extends PHPUnit_Framework_TestCase {

    /**
     * Table adapter to accounts table.
     *
     * @var Zend_Db_Table
     */
    protected $_privileges = null;

    /**
     * Table adapter to resources table.
     *
     * @var Zend_Db_Table
     */
    protected $_resources = null;
    
    /**
     * Table adapter to roles table.
     *
     * @var Zend_Db_Table
     */
    protected $_roles = null;

    /**
     * Set up table adapter.
     *
     * @return void
     */
    public function setUp() {
        TestHelper::clearTable('privileges');
        $this->_privileges = new Opus_Db_Privileges();
        TestHelper::clearTable('resources');
        $this->_resources = new Opus_Db_Resources();
        TestHelper::clearTable('roles');
        $this->_roles = new Opus_Db_Resources();
    }

    /**
     * Purge test data.
     *
     * @return void
     */
    public function tearDown() {
        TestHelper::clearTable('privileges');
        TestHelper::clearTable('resources');
        TestHelper::clearTable('roles');
    }

    /**
     * Test if privileges table is initially empty.
     *
     * @return void
     */
    public function testPrivilegeTableIsInitiallyEmpty() {
        $rowset = $this->_privileges->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'Privileges table is not initially empty.');
    }

    /**
     * Test if resources table is initially empty.
     *
     * @return void
     */
    public function testResourcesTableIsInitiallyEmpty() {
        $rowset = $this->_resources->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'Resoucres table is not initially empty.');
    }

    /**
     * Test initalization of Opus_Security_Acl and if Opus_Security_Acl extends Zend_Acl.
     *
     * @return void
     */
    public function testOpusSecurityAclExtendsZendAcl() {
        $acl = new Opus_Security_Acl;
        $this->assertTrue($acl instanceof Zend_Acl, 'Opus_Security_Acl is not an instance of Zend_Acl!');
    }

    /**
     * Test if a resources is registered after adding to the Acl.
     *
     * @return void
     */
    public function testResourceExistsAfterAddingToAcl() {
        $acl = new Opus_Security_Acl();
        $resource = new Zend_Acl_Resource('MyResource');
        $acl->add($resource);
        $hasResource = $acl->has($resource);
        $this->assertTrue($hasResource, 'Resource is not registered after adding to Acl.');
    }


    /**
     * Test if a resources is stored in the database after adding to the Acl.
     *
     * @return void
     */
    public function testResourceIsPersistedAfterAddingToAcl() {
        $acl = new Opus_Security_Acl();
        $resource = new Zend_Acl_Resource('MyResource');
        $acl->add($resource);
        $rowset = $this->_resources->fetchAll($this->_resources->select()
            ->where('name = ?', $resource->getResourceId()));
        $this->assertEquals(1, $rowset->count(), 'Opus_Security_Acl does not store resources in the DB.');
    }

    /**
     * Test if method has() loads resources from the database.
     *
     * @return void
     */
    public function testHasMethodLoadsResources() {
        // Store artificial resource id
        $this->_resources->insert(array(
            'name' => 'MyResource'));

        $acl = new Opus_Security_Acl();
        $hasResource = $acl->has('MyResource');
        $this->assertTrue($hasResource, 'Acl does not load resources from database.');
    }

    /**
     * Test if a parent resource can be set.
     *
     * @return void
     */
    public function testParentResourceGetsAdded() {
        $acl = new Opus_Security_Acl;
        $resource = new Zend_Acl_Resource('MyResource');
        $parent = new Zend_Acl_Resource('MyParent');
        $acl->add($parent);
        $acl->add($resource, $parent);

        $this->assertTrue($acl->inherits($resource, $parent), 'Parent relation ship is wrong.');
    }

    /**
     * Test if an parent relationship gets persisted.
     *
     * @return void
     */
    public function testParentRelationshipIsPersisted() {
        $acl = new Opus_Security_Acl;
        $resource = new Zend_Acl_Resource('MyResource');
        $parent = new Zend_Acl_Resource('MyParent');
        $acl->add($parent);
        $acl->add($resource, $parent);

        $acl = new Opus_Security_Acl;
        $this->assertTrue($acl->inherits($resource, $parent), 'Parent relation ship is not persistent.');
    }
    
    
    /**
     * Test if a granted privileg gets persisted.
     *
     * @return
     */
    public function testPrivilegGetsPersisted() {
        $this->markTestSkipped('Persisting of allow/deny rules not implemented yet.');
        
        // Set up role and resource
        $role = new Opus_Security_Role();
        $roleId = $role->setName('me')->store();
        $resource = new Zend_Acl_Resource('MyResource');

        // Create Acl
        $acl = new Opus_Security_Acl;
        $acl->add($resource);
        
        // Allow permission
        $acl->allow($role, $resource, 'sendToMars');
        
        // Expect permisson to be persisted
        $rowset = $this->_privileges->fetchAll();
        $this->assertEquals(1, $rowset->count(), 'Privileg has not been persisted.');
    }
    
    /**
     * Test if a privilege gets loaded from the database.
     *
     * @return void
     */
    public function testPrivilegeGetLoadedFromDatabase() {
        // Set up role 
        $jamesBond = new Opus_Security_Role();
        $roleId = $jamesBond->setName('JamesBond')->store();
        
        // ...and resource
        $row = $this->_resources->createRow();
        $row->name = 'BadGuy';
        $resourceId = $row->save();
        
        // Set up privilege entry
        $row = $this->_privileges->createRow();
        $row->role_id = $roleId;
        $row->resource_id = $resourceId;
        $row->privilege = 'kill';
        $row->granted = true;
        $row->save();
        
        // Create Acl
        $acl = new Opus_Security_Acl;
       
        // Expect permission to be granted
        $granted = $acl->isAllowed($jamesBond, 'BadGuy', 'kill');
        $this->assertTrue($granted, 'Expect persisted permission to be granted by Acl.');
    }
 
    /**
     * Test if a privilege on a resource gets inherited to child resources.
     *   
     * @return void
     */
    public function testHasPrivilegeResourceInheritance() {
        // Set up role 
        $jamesBond = new Opus_Security_Role();
        $roleId = $jamesBond->setName('JamesBond')->store();
        
        // Resources
        $row = $this->_resources->createRow();
        $row->name = 'BadGuy';
        $rid = $row->save();
        
        $row = $this->_resources->createRow();
        $row->name = 'VeryBadGuy';
        $row->parent_id = $rid;
        $row->save();
        
        // Set up privilege entry on 'BadGuy'
        $row = $this->_privileges->createRow();
        $row->role_id = $roleId;
        $row->resource_id = $rid;
        $row->privilege = 'kill';
        $row->granted = true;
        $row->save();
        
        // Create Acl
        $acl = new Opus_Security_Acl;
       
        // Expect permission to be granted on child resource
        $granted = $acl->isAllowed($jamesBond, 'VeryBadGuy', 'kill');
        $this->assertTrue($granted, 'Expect inherited permission to be granted by Acl.');
   }
   
   /**
    * Test if all privileges can be granted to a Role
    *
    * @return void
    */
   public function testAllPrivilegesGrantedToSuperrole() {
        // Set up role 
        $chuckNorris = new Opus_Security_Role;
        $chuckNorris->setName('ChuckNorris')->store();

        // Acl
        $acl = new Opus_Security_Acl;
        
        // Resources
        $timeAndSpace = new Zend_Acl_Resource('TimeAndSpace');
        $earthWindAndFire = new Zend_Acl_Resource('EarthWindAndFire', $timeAndSpace);   
        $acl->add($timeAndSpace);
        $acl->add($earthWindAndFire);        
                
        // This would not be necessary if Chuck Norris runs the script
        // because he is already allowed everything
        $acl->allow($chuckNorris);
        
        // Expect Chuck Norris to have control over time and space...
        $this->assertTrue($acl->isAllowed($chuckNorris, $timeAndSpace), 'Access to resource has not been granted.');
        // ...and earth, wind and fire as well :)
        $this->assertTrue($acl->isAllowed($chuckNorris, $earthWindAndFire), 'Access to resource has not been granted.');
   }


   /**
    * Test if all privileges can be granted to a Role
    *
    * @return void
    */
   public function testAllPrivilegesGrantedToSuperroleIfResourcesInDatabase() {
        // Set up role 
        $superUser = new Opus_Security_Role;
        $superUser->setName('SuperUser')->store();
        
        // Resources
        $row = $this->_resources->createRow();
        $row->name = 'A';
        $rid = $row->save();
        
        $row = $this->_resources->createRow();
        $row->name = 'B';
        $row->parent_id = $rid;
        $row->save();
                
        // Acl
        $acl = new Opus_Security_Acl;
        $acl->allow($superUser);
        
        // Expect super-user to have control over everything
        $this->assertTrue($acl->isAllowed($superUser, 'A'), 'Access to resource has not been granted.');
        $this->assertTrue($acl->isAllowed($superUser, 'B'), 'Access to resource has not been granted.');
   }

    /**
     * Test that Acl queries the database tables for Resources and Roles
     * not more then one time per request.
     *
     * @return void
     */
    public function testRolesResourcesGetQueriedOnlyOnce() {
        // Set up a complex resource setting
        $allDocumentsId = $this->_resources->insert(array('name' => 'AllDocuments'));
        $pubDocumentsId = $this->_resources->insert(array('name' => 'PublicDocuments', 'parent_id' => $allDocumentsId));
        $clnDocumentsId = $this->_resources->insert(array('name' => 'ClientDocuments', 'parent_id' => $allDocumentsId));
        for ($i = 0; $i<5 ; $i++) {
            $doc = $this->_resources->insert(array('name' => "Opus/Document/$i", 'parent_id' => $clnDocumentsId)); 
        }
        for ($i = 5; $i<10 ; $i++) {
            $doc = $this->_resources->insert(array('name' => "Opus/Document/$i", 'parent_id' => $pubDocumentsId)); 
        }
        
        $guestId  = $this->_roles->insert(array('name' => 'guest'));
        $clientId = $this->_roles->insert(array('name' => 'client'));
        $adminId = $this->_roles->insert(array('name' => 'admin'));
        
        $this->_privileges->insert(array('role_id' => $guestId, 'resource_id' => $pubDocuments, 
            'privilege' => 'read', 'granted' => true));
        $this->_privileges->insert(array('role_id' => $clientId, 'resource_id' => $clnDocuments
            'privilege' => 'read', 'granted' => true));
        $this->_privileges->insert(array('role_id' => $adminId, 'resource_id' => $allDocuments,
            'privilege' => 'read', 'granted' => true));
        
        // Turn on database profiler
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->getProfiler()->setEnabled(true);
        
        // Set up Acl and ask for permission of guest to read Opus/Document/3 which should be permitted
        $acl = new Opus_Security_Acl();
        $acl->isAllowed();
        
    }   
   
}
