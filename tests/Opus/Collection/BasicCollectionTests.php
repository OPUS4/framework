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
class Opus_Collection_BasicCollectionTests extends PHPUnit_Framework_TestCase {

    /**
     * @var Opus_CollectionRole
     */
    protected $role_fixture;
    protected $_role_name     = "";
    protected $_role_oai_name = "";

    /**
     * @var Opus_Collection
     */
    protected $object;


    public function setUp() {
        $this->_role_name     = "role-name-" . rand();
        $this->_role_oai_name = "role-oainame-" . rand();

        $this->role_fixture = new Opus_CollectionRole();
        $this->role_fixture->setName($this->_role_name);
        $this->role_fixture->setOaiName($this->_role_oai_name);
        $this->role_fixture->setVisible(1);
        $this->role_fixture->setVisibleBrowsingStart(1);
        $this->role_fixture->store();
        $role_id = $this->role_fixture->getId();

        $this->object = new Opus_Collection();
        $this->object->setRoleId( $role_id );
        $this->object->store();
    }

    public function testCollectionConstructor() {
        $this->assertNotNull($this->object->getId(),
                'Collection storing failed: should have an Id.');
        $this->assertNotNull($this->object->getRoleId(),
                'Collection storing failed: should have an RoleId.');

        // Check, if we can create the object for this Id.
        $collection_id = $this->object->getId();
        $collection = new Opus_Collection( $collection_id );

        $this->assertNotNull($collection,
                'Collection construction failed: collection is null.');
        $this->assertNotNull($collection->getId(),
                'Collection storing failed: should have an Id.');
        $this->assertNotNull($collection->getRoleId(),
                'Collection storing failed: should have an RoleId.');
    }

    public function testCollectionDelete() {
        $this->object->delete();
    }

    public function testGetTheme() {
        $theme = $this->object->getTheme();

        $this->assertNotNull($theme,
                'Theme must not be null.');
        $this->assertFalse(empty($theme),
                'Theme must not be empty.');
     }

}
