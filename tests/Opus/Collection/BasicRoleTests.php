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
    protected $fixture;

    protected $_name     = "";
    protected $_oai_name = "";

    public function setUp() {
        $this->_name     = "name-" . rand();
        $this->_oai_name = "oainame-" . rand();

        $this->fixture  = new Opus_CollectionRole();
        $this->fixture->setName($this->_name);
        $this->fixture->setOaiName($this->_oai_name);
        $this->fixture->store();
    }

    public function testRoleConstructor() {
        $this->assertFalse(is_null($this->fixture->getId()), 'CollectionRole storing failed: should have an Id.');

        // Check, if we can create the object for this Id.
        $role_id = $this->fixture->getId();
        $role = new Opus_CollectionRole( $role_id );

        $this->assertFalse(is_null($role), 'CollectionRole construction failed: role is null.');
        $this->assertFalse(is_null($role->getId()), 'CollectionRole storing failed: should have an Id.');
        $this->assertTrue(($role->getName() === $this->_name), 'CollectionRole name check failed.');
        $this->assertTrue(($role->getOaiName() === $this->_oai_name), 'CollectionRole oai_name check failed.');
    }

    public function testRoleDelete() {
        $role_id = $this->fixture->getId();

        // Second step: Restore object, delete.
        $role = new Opus_CollectionRole( $role_id );
        $role->delete();

        // Third step: Create deleted object - should fail.
        $this->setExpectedException('Zend_Db_Table_Rowset_Exception');
        $role = new Opus_CollectionRole( $role_id );

        $this->assertTrue(is_null($role), 'CollectionRole construction failed: role is NOT null.');
    }

    public function testRoleData() {
        // Initialize with data.
        $role = $this->fixture;

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
        $role = new Opus_CollectionRole( $role_id );

        $this->assertTrue(($role->getName() === $this->_name), 'CollectionRole name check failed.');
        $this->assertTrue(($role->getOaiName() === $this->_oai_name), 'CollectionRole oai_name check failed.');

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

    public function testRoleFetchAll() {

        // Check if Role is visible in fetchAll
        $roles = Opus_CollectionRole::fetchAll();
        $seen = false;
        foreach ($roles AS $role) {
            if ( $role->getId() === $this->fixture->getId() ) {
                $seen = true;
                $this->assertTrue( $role->getName() === $this->_name, "CollectionRole has wrong name." );
            }
        }

        $this->assertTrue( true === $seen , "CollectionRole is not visible in fetchAll." );
    }

    public function testPosition() {

        // Check if setPosition works properly.
        $num_roles = count( Opus_CollectionRole::fetchAll() );
        $check_positions = array( 1, $num_roles, round((1+$num_roles)/2), 1 );
        
        foreach ($check_positions AS $position) {
            $this->fixture->setPosition($position);
            $this->fixture->store();

            $role = new Opus_CollectionRole($this->fixture->getId());
            $this->assertTrue(($role->getPosition() === "$position"), 'CollectionRole position check failed.');
        }

    }

}
