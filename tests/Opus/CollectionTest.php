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

class Opus_CollectionTest extends TestCase {

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

    /**
     * SetUp method.  Inherits database cleanup from parent.
     */
    public function setUp() {
        parent::setUp();

        $this->_role_name     = "role-name-" . rand();
        $this->_role_oai_name = "role-oainame-" . rand();

        $this->role_fixture = new Opus_CollectionRole();
        $this->role_fixture->setName($this->_role_name);
        $this->role_fixture->setOaiName($this->_role_oai_name);
        $this->role_fixture->setVisible(1);
        $this->role_fixture->setVisibleBrowsingStart(1);
        $this->role_fixture->store();

        $this->object = $this->role_fixture->addRootCollection();
        $this->object->setTheme('dummy');
        $this->role_fixture->store();
    }

    /**
     * Test constructor.
     */
    public function testConstructorForExistingCollection() {

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

    /** 
     * Test if delete really deletes.
     */
    public function testDeleteNoChildren() {
        $collection_id = $this->object->getId();
        $this->object->delete();

        $this->setExpectedException('Opus_Model_NotFoundException');
        new Opus_Collection($collection_id);
    }

    /**
     * Test if we can retrieve stored themes from the database.
     */
    public function testGetTheme() {
        $this->object->setTheme( 'test-theme' );
        $this->object->store();

        $collection = $this->object;
        $this->assertEquals('test-theme', $collection->getTheme(),
                'After store: Stored theme does not match expectation.');

        $collection = new Opus_Collection($this->object->getId());
        $this->assertEquals('test-theme', $collection->getTheme(),
                'After reload: Stored theme does not match expectation.');
     }

     /**
      * Test if virtual field "GetOaiName" contains the value of "OaiSubset".
      */
     public function testGetOaiName() {
        $this->object->setOaiSubset("subset");
        $collection_id = $this->object->store();

        $collection = new Opus_Collection($collection_id);
        $oai_name = $collection->getOaiSetName();

        $this->assertNotNull($oai_name,
                'Field OaiName must not be null/empty.');
        $this->assertTrue(preg_match("/:subset/", $oai_name) == 1,
                'Field OaiSetName must contain OaiSubset.');
     }

    /**
     * Tests toArray().
     */
    public function testGetChildren() {
        $root = $this->object;

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(0, count($root->getChildren()),
                'Root collection without children should return empty array.');

        $child_1 = $root->addLastChild();
        $root->store();

        // FIXME: We have to reload model to get correct results!
        $root = new Opus_Collection( $root->getId() );

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(1, count($root->getChildren()),
                'Root collection should have one child.');

        $child_2 = $root->addLastChild();
        $root->store();

        $child_1_1 = $child_1->addFirstChild();
        $child_1->store();

        // FIXME: We have to reload model to get correct results!
        $root = new Opus_Collection( $root->getId() );

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(2, count($root->getChildren()),
                'Root collection should have two children.');

    }

    public function testGetDefaultThemeIfSetDefaultTheme() {
        $default_theme = Zend_Registry::get('Zend_Config')->theme;
        $this->assertFalse(empty($default_theme),
                'Could not get theme from config');

        $this->object->setTheme($default_theme);
        $this->object->store();

        $collection = new Opus_Collection($this->object->getId());
        $this->assertEquals($default_theme, $collection->getTheme(),
                'Expect default theme if non set');
    }
    public function testGetDefaultThemeIfSetNullTheme() {
        $this->object->setTheme(null);
        $this->object->store();

        $default_theme = Zend_Registry::get('Zend_Config')->theme;
        $this->assertFalse(empty($default_theme),
                'Could not get theme from config');

        $collection = new Opus_Collection($this->object->getId());
        $this->assertEquals($default_theme, $collection->getTheme(),
                'Expect default theme if non set');
    }

    public function testDocumentIds() {
        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) == 0, 'Expected empty id array');

        $d = new Opus_Document();
        $d->addCollection( $this->object );
        $d->store();

        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) == 1, 'Expected one element in array');
    }

    public function testGetDisplayName() {
        $this->role_fixture->setDisplayBrowsing('Name');
        $this->role_fixture->setDisplayFrontdoor('Number');
        $this->role_fixture->store();

        $this->object->setName('fooblablub');
        $this->object->setNumber('thirteen');
        $this->object->store();

        $collection = new Opus_Collection( $this->object->getId() );
        $this->assertEquals( 'fooblablub', $collection->getDisplayName('browsing') );
        $this->assertEquals( 'thirteen', $collection->getDisplayName('frontdoor') );
    }
}
