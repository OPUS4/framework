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
 * @package     Opus_Collection
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Collection_Builder.
 *
 * @category    Tests
 * @package     Opus_Collection
 *
 * @group       CollectionBuilderTest
 *
 */
class Opus_Collection_BasicRoleTests extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var Opus_CollectionRole
     */
    protected $object;

    /**
     * Static helper for creating random objects.
     *
     * @return Opus_CollectionRole
     */
    public static function createRandomObject() {
        $name = "name-" . rand();
        $oai_name = "oainame-" . rand();

        // Object is not stored.
        $object = new Opus_CollectionRole();
        $object->setName($name);
        $object->setOaiName($oai_name);

        return $object;

    }

    /**
     * Sets up the fixture.  Method is called before each test.
     */
    public function setUp() {
        // Object is not stored.
        $this->object = self::createRandomObject();

    }

    /**
     * Tests delete method and if object is really deleted.
     */
    public function testDelete() {

        $this->assertTrue($this->object->isNewRecord(),
                'CollectionRole isNewRecord check failed on new record.');
        $this->object->delete();

        $role_id = $this->object->store();
        $this->assertNotNull($role_id,
                'CollectionRole role_id return value check on stored record.');
        $this->assertFalse($this->object->isNewRecord(),
                'CollectionRole isNewRecord check failed on new record.');

        $this->object->delete();

        $this->setExpectedException('Opus_Model_NotFoundException');
        new Opus_CollectionRole($role_id);

    }

    /**
     * Tests store method and if object can be reloaded after storing.
     */
    public function testStore() {

        $this->assertTrue($this->object->isNewRecord(),
                'CollectionRole isNewRecord check failed on new record.');

        $role_id = $this->object->store();
        $this->assertNotNull($role_id,
                'CollectionRole role_id return value check on stored record.');
        $this->assertNotNull($this->object->getId(),
                'CollectionRole getId check on stored record.');
        $this->assertTrue($role_id === $this->object->getId(),
                'CollectionRole->store return value check failed.');
        $this->assertFalse($this->object->isNewRecord(),
                'CollectionRole isNewRecord check failed on stored record.');

        $role = new Opus_CollectionRole($this->object->getId());
        $this->assertTrue(is_object($role),
                'CollectionRole reloading failed.');
        $this->assertFalse(is_null($role->getId()),
                'CollectionRole getId check on stored record.');
        $this->assertFalse($role->isNewRecord(),
                'CollectionRole isNewRecord check failed on reloaded record.');
        $this->assertFalse($role->isModified(),
                'CollectionRole isModified check failed on reloaded record.');

        $this->object->delete();

    }

    /**
     * Tests setting of name field.
     */
    public function testSetName() {
        $name = "set-name-" . rand();

        $this->object->setName($name);
        $this->assertTrue(($this->object->getName() === $name),
                'CollectionRole name check failed.');

        $this->object->store();
        $role = new Opus_CollectionRole($this->object->getId());
        $this->assertTrue(($role->getName() === $name),
                'CollectionRole name check on reloaded object failed.');

        $this->object->delete();

    }

    /**
     * Tests getDisplayName().
     */
    public function testGetDisplayName() {
        $this->assertNotNull($this->object->getDisplayName(),
                'CollectionRole getDisplayName most NOT be null.');

    }

    /**
     * @todo Implement testToArray().
     */
    public function testToArray() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );

    }

    /**
     * Tests fetchByName().
     */
    public function testFetchByName() {
        $this->assertTrue($this->object->isNewRecord(),
                'CollectionRole isNewRecord check failed on new record.');

        // Expecting null for current name, since its not stored in db.
        $role = Opus_CollectionRole::fetchByName($this->object->getName());
        $this->assertNull($role,
                'Role should not exists.');

        // Expecting null for current name, since its not stored in db.
        $this->object->store();
        $role = Opus_CollectionRole::fetchByName($this->object->getName());
        $this->assertNotNull($role,
                'Role should exist.');
        $this->assertTrue($role instanceof Opus_CollectionRole,
                'Returned object has wrong class.');

        $this->object->delete();

    }

    /**
     * Tests fetchByOaiName().
     */
    public function testFetchByOaiName() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );

    }

    /**
     * Tests fetchAll().
     */
    public function testFetchAll() {

        $name = $this->object->getName();

        // Check fetchAll works even if object is unstored.
        $roles = Opus_CollectionRole::fetchAll();
        $roles_count_old = count($roles);
        $this->assertTrue(is_array($roles), "Array return value expected.");

        // Check fetchAll works after storing *and* contains the object.
        $this->object->store();
        $roles = Opus_CollectionRole::fetchAll();
        $this->assertTrue(is_array($roles), "Array return value expected.");
        $this->assertTrue(count($roles) > $roles_count_old, "Increasing count expected.");

        $seen = false;
        foreach ($roles AS $role) {
            if ($role->getId() === $this->object->getId()) {
                $seen = true;
                $this->assertTrue($role->getName() === $name, "CollectionRole has wrong name.");
            }
        }

        $this->assertTrue($seen, "CollectionRole is not visible in fetchAll.");

    }

    /**
     * @todo Implement testGetOaiSetNames().
     */
    public function testGetOaiSetNames() {
        // TODO: Check error handling for empty DisplayName!
        $this->object->setDisplayOai('Number');

        // List of set names on unstored object
        $setnames = $this->object->getOaiSetNames();
        $this->assertTrue(is_array($setnames), "Expected OaiSetNames array.");

        // List of set names on stored object
        $this->object->store();
        $setnames = $this->object->getOaiSetNames();
        $this->assertTrue(is_array($setnames), "Expected OaiSetNames array.");

        $this->object->delete();
    }

    /**
     * Tests getAttributes().
     */
    public function testGetAttributes() {
        $attributes = $this->object->getAttributes();

        $this->assertTrue(is_array($attributes), "Expected attributes array.");
    }

    /**
     * @todo Implement testExistsDocumentIdsInSet().
     */
    public function testExistsDocumentIdsInSet() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );

    }

    /**
     * Tests getDocumentIdsInSet().  Currently only tests empty set.
     */
    public function testGetDocumentIdsInSet() {
        $this->object->setDisplayOai('Number');
        $this->object->store();

        $oaiSetName = $this->object->getOaiName();
        $sets = Opus_CollectionRole::getDocumentIdsInSet("$oaiSetName:foo");

        $this->assertTrue(is_array($sets), "Expected array return value.");
        $this->assertTrue(count($sets) === 0, "Expected empty array.");
    }

    /**
     * @todo Implement testGetSubCollection().
     */
    public function testGetSubCollection() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );

    }

    /**
     * @todo Implement test_storeRootNode().
     */
    public function test_storeRootNode() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );

    }

    /**
     * @todo Implement testGetParents().
     */
    public function testGetParents() {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
                'This test has not been implemented yet.'
        );

    }

    /**
     * Check if setting position works
     */
    public function testPosition() {

        $this->object->store();

        // Populate database with dummy objects.
        $i_max = rand(5, 10);
        for ($i = 0; $i < $i_max; $i++) {
            $object = self::createRandomObject();
            $object->store();
        }

        // Check if setPosition works properly.
        $num_roles = count(Opus_CollectionRole::fetchAll());
        $check_positions = array(1, $num_roles, round((1 + $num_roles) / 2), 1);

        foreach ($check_positions AS $position) {
            $this->object->setPosition($position);
            $this->object->store();

            // Reload object, otherwise the result will be trivial.
            $role = new Opus_CollectionRole($this->object->getId());
            $this->assertTrue(($role->getPosition() === "$position"), 'CollectionRole position check failed.');
        }

    }

    /**
     * Initializes the current element with random data and checks if it will
     * be stored correctly.
     */
    public function testRoleData() {
        // Initialize with data.
        $role = $this->object;

        $role->setLinkDocsPathToRoot('both');
        $role->setDisplayBrowsing('Number, Name');
        $role->setDisplayFrontdoor('Name');
        $role->setDisplayOai('Number');

        $role->setVisible(1);
        $role->setVisibleBrowsingStart(1);
        $role->setVisibleFrontdoor(0);
        $role->setVisibleOai(1);

        $role->setPosition(1);

        $role->store();

        $this->assertFalse(is_null($role->getId()), 'CollectionRole storing failed: should have an Id.');

        // Restore object, validate.
        $role_id = $role->getId();
        $role = new Opus_CollectionRole($role_id);

        $this->assertNotNull($role->getName(), 'CollectionRole name check failed.');
        $this->assertNotNull($role->getOaiName(), 'CollectionRole oai_name check failed.');

        $this->assertTrue(($role->getLinkDocsPathToRoot() === 'both'), 'CollectionRole link_docs_path_to_root check failed.');
        $this->assertTrue(($role->getDisplayBrowsing() === 'Number, Name'), 'CollectionRole display_browsing check failed.');
        $this->assertTrue(($role->getDisplayFrontdoor() === 'Name'), 'CollectionRole display_frontdoor check failed.');
        $this->assertTrue(($role->getDisplayOai() === 'Number'), 'CollectionRole display_oai check failed.');

        $this->assertTrue(($role->getVisible() === '1'), 'CollectionRole visible check failed.');
        $this->assertTrue(($role->getVisibleBrowsingStart() === '1'), 'CollectionRole visible_browsing_start check failed.');
        $this->assertTrue(($role->getVisibleFrontdoor() === '0'), 'CollectionRole visible_frontdoor check failed.');
        $this->assertTrue(($role->getVisibleOai() === '1'), 'CollectionRole visible_oai check failed.');

        $this->assertTrue(($role->getPosition() === '1'), 'CollectionRole position check failed.');

    }

}
