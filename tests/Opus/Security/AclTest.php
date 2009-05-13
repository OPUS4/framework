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
     * Table adapter to rules table.
     *
     * @var Zend_Db_Table
     */
    protected $_rules = null;

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
        TestHelper::clearTable('rules');
        $this->_rules = new Opus_Db_Rules();
        TestHelper::clearTable('resources');
        $this->_resources = new Opus_Db_Resources();
        TestHelper::clearTable('roles');
        $this->_roles = new Opus_Db_Roles();
    }

    /**
     * Purge test data.
     *
     * @return void
     */
    public function tearDown() {
        TestHelper::clearTable('rules');
        TestHelper::clearTable('resources');
    }

    /**
     * Test if rules table is initially empty.
     *
     * @return void
     */
    public function testRulesTableIsInitiallyEmpty() {
        $rowset = $this->_rules->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'Rules table is not initially empty.');
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
     * Test if roles table is initially empty.
     *
     * @return void
     */
    public function testRolesTableIsInitiallyEmpty() {
        $rowset = $this->_roles->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'Roles table is not initially empty.');
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
     * Test if role is registered after adding to the Acl.
     * @return unknown_type
     */
    public function testRoleExistsAfterAddingToAcl() {
        $acl = new Opus_Security_Acl();
        $role = new Zend_Acl_Role('me');
        $acl->addRole($role);
        $this->assertTrue($acl->hasRole($role), 'Role is not registered after adding to Acl.');
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
     * Test if a resource will be removed from acl and from database.
     *
     * @return void
     */
    public function testRemovingResourceFromAcl() {
        // Store artificial resource id
        $resourceId = 'MyResource';
        $this->_resources->insert(array('name' => $resourceId));

        $acl = new Opus_Security_Acl();
        $acl->remove($resourceId);

        $this->assertFalse($acl->has($resourceId), 'Resource was not removed from the Acl.');

        $rowset = $this->_resources->fetchAll($this->_resources->select()
            ->where('name = ?', $resourceId));
        $this->assertEquals(0, $rowset->count(), 'A resource was removed from the acl but is still persisted.');


    }

    /**
     * Test if Method remove the resource and all its descendants.
     *
     * @return void
     */
    public function testRemoveMethodRemovesDescendants() {
        $allDocumentsId = $this->_resources->insert(array('name' => 'AllDocuments'));
        $pubDocumentsId = $this->_resources->insert(array('name' => 'PublicDocuments', 'parent_id' => $allDocumentsId));
        $clnDocumentsId = $this->_resources->insert(array('name' => 'ClientDocuments', 'parent_id' => $allDocumentsId));
        for ($i = 0; $i<5 ; $i++) {
            $doc = $this->_resources->insert(array('name' => "Opus/Document/$i", 'parent_id' => $clnDocumentsId));
        }
        for ($i = 5; $i<10 ; $i++) {
            $doc = $this->_resources->insert(array('name' => "Opus/Document/$i", 'parent_id' => $pubDocumentsId));
        }

        $acl = new Opus_Security_Acl();
        $acl->remove('AllDocuments');

        $acl = new Opus_Security_Acl();
        $this->assertFalse($acl->has('AllDocuments'), 'Resource could not be removed.');
        $this->assertFalse($acl->has('PublicDocuments'), 'Child of a resource was not removed.');
        $this->assertFalse($acl->has('ClientDocuments'), 'Child of a resource was not removed.');
        for ($i =0; $i<10; $i++) {
            $this->assertFalse($acl->has("Opus/Document/$i"), 'Grandchild of a resource was not removed.');
        }

        $rowset = $this->_resources->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'A removed resource or one of its descendants is still persisted.');
    }

    /**
     * Test if all ressources can be deleted from Acl.
     *
     * @return void
     */
    public function testRemoveAllMethodRemovesResourcesInDB() {
        // setting up ressources
        $this->__setUpComplexResourceSetting();

        $acl = new Opus_Security_Acl();
        $acl->removeAll();

        $rowset = $this->_resources->fetchAll();
        $this->assertEquals(0, $rowset->count(), 'Resources were still persisted after removeAll was called.');
    }

    /**
     * Test if a role ist stored in the database after adding to the Acl.
     *
     * FIXME: Create a MockUp for the RoleRegistry. Don't check if roles will be stored
     * in the DB, check if acl calls the methods of the RoleRegistry correctly.
     *
     * @return void
     */
    public function testRoleIsPersistedAfterAddingToAcl() {
        $acl = new Opus_Security_Acl();
        $role = new Zend_Acl_Role('me');
        $acl->AddRole($role);
        $rowset = $this->_roles->fetchAll($this->_roles->select()
            ->where('name = ?', $role->getRoleId()));
        $this->assertEquals(1, $rowset->count(), 'A role was not stored in the DB after adding to the ACL. This can be realted to the ACL or the RoleRegistry.');
    }

    /**
     * Test if a role will be removed from database if it is removed from the Acl.
     *
     * FIXME: Create Mockup for the RoleRegistry and check if it's calle by the Acl correctly.
     *
     * @return void
     */
    public function testRoleIsRemovedFromDBIfRemovedFromAcl() {
        $this->markTestSkipped('Skip test until RoleRegistry can remove roles from db.');

        // Store artificial resource id
        $roleId = 'me';
        $this->_roles->insert(array('name' => $roleId));

        $acl = new Opus_Security_Acl();
        $acl->removeRole(new Zend_Acl_Role($roleId));

        $rowset = $this->_roles->fetchAll($this->_roles->select()
            ->where('name = ?', $roleId));
        $this->assertEquals(0, $rowset->count(), 'A role was removed from the acl but is still persisted.');
    }

    /**
     * Test if all roles can be deleted from Acl.
     *
     * FIXME: Create Mockup for the RoleRegistry and check if it's calle by the Acl correctly.
     *
     * @return void
     */
    public function testRemoveRoleAllMethodRemovesRolesFromRoleRegistry() {
        $this->markTestSkipped('Skip test until RoleRegistry can remove roles from db.');

        // setting up ressources
        $this->__setUpComplexResourceSetting();

        $acl = new Opus_Security_Acl();
        $acl->removeRoleAll();

        $rowset = $this->_roles->fetchAll();

        $this->assertEquals(0, $rowset->count(), 'Roles were still persisted after removeAll was called.');
    }

    /**
     * Test if method has() loads resources from the database.
     *
     * @return void
     */
    public function testHasMethodLoadsResources() {
        // Store artificial resource id
        $this->_resources->insert(array('name' => 'MyResource'));

        $acl = new Opus_Security_Acl();
        $hasResource = $acl->has('MyResource');
        $this->assertTrue($hasResource, 'Acl does not load resources from database.');
    }

    /**
     * Test if method hasRole() loads roles from the database.
     *
     * FIXME: Create a MockUp for the RoleRegistry. Don't load roles from the DB,
     * check if acl calls the methods of the RoleRegistry correctly.
     *
     * @return void
     */
    public function testHasRoleMethodLoadsRoles() {
        // Store artificial resource id
        $this->_roles->insert(array('name' => 'me'));

        $acl = new Opus_Security_Acl();
        $this->assertTrue($acl->hasRole(new Zend_Acl_Role('me')), 'Acl does not load roles from RoleRegistry or there is an error in the RoleRegistry.');
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
     * Test if an parent relationship of resources gets persisted.
     *
     * @return void
     */
    public function testParentRelationshipBetweenResourcesIsPersisted() {
        $acl = new Opus_Security_Acl;
        $resource = new Zend_Acl_Resource('MyResource');
        $parent = new Zend_Acl_Resource('MyParent');
        $acl->add($parent);
        $acl->add($resource, $parent);

        $acl = new Opus_Security_Acl;
        $this->assertTrue($acl->inherits($resource, $parent), 'Parent relation ship is not persistent.');
    }

    /**
     * Test if an parent relationship of roles gets persisted.
     *
     * @return void
     */
    public function testParentRelationshipBetweenRolesIsPersisted() {
        $acl = new Opus_Security_Acl;
        $resource = new Zend_Acl_Role('me');
        $parent = new Zend_Acl_Role('MyParent');
        $acl->addRole($parent);
        $acl->addRole($resource, $parent);

        $acl = new Opus_Security_Acl;
        $this->assertTrue($acl->inheritsRole($resource, $parent), 'Parent relation ship between roles is not persistent.');
    }

    /**
     * Test if a rule gets persisted.
     *
     * @return
     */
    public function testRuleGetsPersisted() {
        // Set up role and resource
        $role = new Zend_Acl_Role('me');
        $resource = new Zend_Acl_Resource('MyResource');

        // Create Acl
        $acl = new Opus_Security_Acl;
        $acl->addRole($role);
        $acl->add($resource);

        // Allow permission
        $acl->allow($role, $resource, 'sendToMars');

        // Expect permisson to be persisted
        $rowset = $this->_rules->fetchAll();
        $this->assertEquals(1, $rowset->count(), 'Rule has not been persisted.');
    }

    /**
     * Test if a rule gets loaded from the database.
     *
     * @return void
     */
    public function testRuleGetLoadedFromDatabase() {
        // Set up role
        $roleId = $this->_roles->insert(array('name' => 'JamesBond'));

        // ...and resource
        $row = $this->_resources->createRow();
        $row->name = 'BadGuy';
        $resourceId = $row->save();

        // Set up rule entry
        $row = $this->_rules->createRow();
        $row->role_id = $roleId;
        $row->resource_id = $resourceId;
        $row->privilege = 'kill';
        $row->granted = true;
        $row->save();

        // Create Acl
        $acl = new Opus_Security_Acl;

        // Expect permission to be granted
        $granted = $acl->isAllowed('JamesBond', 'BadGuy', 'kill');
        $this->assertTrue($granted, 'Expect persisted permission to be granted by Acl.');
    }

    /**
     * Test if a rule on a resource gets inherited to child resources.
     *
     * @return void
     */
    public function testHasRuleResourceInheritance() {
        // Set up role
        $roleId = $this->_roles->insert(array('name' => 'JamesBond'));

        // Resources
        $row = $this->_resources->createRow();
        $row->name = 'BadGuy';
        $rid = $row->save();

        $row = $this->_resources->createRow();
        $row->name = 'VeryBadGuy';
        $row->parent_id = $rid;
        $row->save();

        // Set up rule entry on 'BadGuy'
        $row = $this->_rules->createRow();
        $row->role_id = $roleId;
        $row->resource_id = $rid;
        $row->privilege = 'kill';
        $row->granted = true;
        $row->save();

        // Create Acl
        $acl = new Opus_Security_Acl;

        // Expect permission to be granted on child resource
        $granted = $acl->isAllowed('JamesBond', 'VeryBadGuy', 'kill');
        $this->assertTrue($granted, 'Expect inherited permission to be granted by Acl.');
   }

   /**
    * Test if rules will be inherited between resources
    *
    * @return void
    */
   public function testIfResourcesInheritsRules() {
        // Set up role
        $this->_roles->insert(array('name' => 'ChuckNorris'));

        // Acl
        $acl = new Opus_Security_Acl;

        // Resources
        $timeAndSpace = new Zend_Acl_Resource('TimeAndSpace');
        $earthWindAndFire = new Zend_Acl_Resource('EarthWindAndFire');
        $acl->add($timeAndSpace);
        $acl->add($earthWindAndFire, $timeAndSpace);

        $acl->allow('ChuckNorris', $timeAndSpace);

        // Expect Chuck Norris to have control over time and space...
        $this->assertTrue($acl->isAllowed('ChuckNorris', $timeAndSpace), 'Access to resource has not been granted.');
        // ...and earth, wind and fire as well :)
        $this->assertTrue($acl->isAllowed('ChuckNorris', $earthWindAndFire), 'Access to child resource has not been granted.');
   }


   /**
    * Test if everything can be granted to a Superrole
    *
    * @return void
    */
   public function testEverythingGrantedToSuperroleIfResourcIsInDatabase() {
        // Set up role
        $this->_roles->insert(array('name' => 'JamesBond'));

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
        $acl->allow('JamesBond');

        // Expect super-user to have control over everything
        $this->assertTrue($acl->isAllowed('JamesBond', 'A'), 'Access to resource has not been granted.');
        $this->assertTrue($acl->isAllowed('JamesBond', 'B'), 'Access to resource has not been granted.');
   }


    /**
     * Helper function for complex resource and rules setting.
     *
     * @return void
     */
    private function __setUpComplexResourceSetting() {
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

        $this->_rules->insert(array('role_id' => $guestId, 'resource_id' => $pubDocumentsId,
            'privilege' => 'read', 'granted' => true));
        $this->_rules->insert(array('role_id' => $clientId, 'resource_id' => $clnDocumentsId,
            'privilege' => 'read', 'granted' => true));
        $this->_rules->insert(array('role_id' => $adminId, 'resource_id' => $allDocumentsId,
            'privilege' => 'read', 'granted' => true));
    }

    /**
     * Test that Acl queries to the database are not issued more then once.
     *
     * @return void
     */
    public function testTablesGetQueriedOnlyOnce() {
        $this->markTestSkipped('Fix yet not implemented in Opus_Security_Acl.');

        $this->__setUpComplexResourceSetting();

        // Set up Acl and ask for permission of guest to read Opus/Document/3 which should be permitted
        $acl = new Opus_Security_Acl();

        // Turn on database profiler
        $db = Zend_Db_Table::getDefaultAdapter();
        $db->getProfiler()->setEnabled(true)
            ->setFilterQueryType(Zend_Db_Profiler::SELECT);

        // Submit the query
        $granted = $acl->isAllowed('guest', 'Opus/Document/3', 'read');

        // Count SELECT queries to "resources" table
        $profiles = $db->getProfiler()->getQueryProfiles();
        $selects = array();
        foreach ($profiles as $profile) {
            $query = $profile->getQuery();
            if (true === array_key_exists($query, $selects)) {
                $selects[$query]++;
            } else {
                $selects[$query] = 1;
            }
        }

        // Assert call-once for each query
        foreach ($selects as $stm => $calls) {
            $this->assertEquals(1, $calls, "More then one call to $stm.");
        }
    }
}
