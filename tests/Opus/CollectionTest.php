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

    protected function tearDown() {
        $this->role_fixture->delete();
        parent::tearDown();
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
        $this->assertEquals('subset', $this->object->getOaiSubset());

        $collection_id = $this->object->store();
        $this->assertNotNull($collection_id);

        $collection = new Opus_Collection($collection_id);
        $this->assertEquals('subset', $this->object->getOaiSubset());
     }

     /**
      * Test if "store()" returns primary key of current object.
      */
     public function testStoreReturnsId() {
        $collection_id = $this->object->store();
        $this->assertNotNull($collection_id);

        $test_object = new Opus_Collection($collection_id);
        $this->assertEquals($this->object->getRoleId(), $test_object->getRoleId());
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

    public function testGetDocumentIds() {
        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) == 0, 'Expected empty id array');

        $d = new Opus_Document();
        $d->addCollection( $this->object );
        $d->store();

        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) == 1, 'Expected one element in array');
    }

    public function testGetDocumentIdsMaxElements() {
        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) == 0, 'Expected empty id array');

        $max = 4;
        $storedIds = array();
        for ($i = 0; $i < $max; $i++) {
            $d = new Opus_Document();
            $d->addCollection( $this->object );
            $d->store();

            $storedIds[] = $d->getId();
        }

        // Add some published documents.
        $max = 4;
        $storedPublishedIds = array();
        for ($i = 0; $i < $max; $i++) {
            $d = new Opus_Document();
            $d->addCollection( $this->object );
            $d->setServerState('published');
            $d->store();

            $storedIds[] = $d->getId();
            $storedPublishedIds[] = $d->getId();
        }

        // Check if getDocumentIds returns *all* documents.
        $collection = new Opus_Collection( $this->object->getId() );
        $docIds = $collection->getDocumentIds();
        $this->assertEquals(2*$max, count($docIds), 'Expected '.(2*$max).' element in array');

        sort($storedIds);
        sort($docIds);
        $this->assertEquals($storedIds, $docIds);

        // Check if getDocumentIds returns only published documents.
        $publishedIds = $collection->getPublishedDocumentIds();
        $this->assertEquals($max, count($publishedIds), 'Expected '.$max.' element in array');

        sort($storedPublishedIds);
        sort($publishedIds);
        $this->assertEquals($storedPublishedIds, $publishedIds);
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

    public function testGetDisplayNameForBrowsingContextWithoutArg() {
        $this->role_fixture->setDisplayBrowsing('Name');
        $this->role_fixture->store();

        $this->object->setName('fooblablub');
        $this->object->setNumber('thirteen');
        $this->object->store();

        $collection = new Opus_Collection( $this->object->getId() );
        $this->assertEquals('fooblablub', $collection->getDisplayNameForBrowsingContext());
    }

    public function testGetDisplayNameForBrowsingContextWithArg() {
        $this->role_fixture->setDisplayBrowsing('Name');
        $this->role_fixture->store();

        $this->object->setName('fooblablub');
        $this->object->setNumber('thirteen');
        $this->object->store();
        
        $collection = new Opus_Collection( $this->object->getId() );
        $this->assertEquals('fooblablub', $collection->getDisplayNameForBrowsingContext($this->role_fixture));
    }

    public function testGetNumberAndNameIsIndependentOfDiplayBrowsingName() {
        $this->role_fixture->setDisplayBrowsing('Name');
        $this->role_fixture->setDisplayFrontdoor('Number');
        $this->role_fixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = new Opus_Collection( $this->object->getId() );
        $this->assertEquals('number name', $collection->getNumberAndName());
    }

    public function testGetNumberAndNameIsIndependetOfDisplayBrowsingNumber() {
        $this->role_fixture->setDisplayBrowsing('Number');
        $this->role_fixture->setDisplayFrontdoor('Number');
        $this->role_fixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = new Opus_Collection( $this->object->getId() );
        $this->assertEquals('number name', $collection->getNumberAndName());
    }

    public function testGetNumberAndNameIsIndependetOfDisplayBrowsingNameNumber() {
        $this->role_fixture->setDisplayBrowsing('Name,Number');
        $this->role_fixture->setDisplayFrontdoor('Number');
        $this->role_fixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = new Opus_Collection( $this->object->getId() );
        $this->assertEquals('number name', $collection->getNumberAndName());
    }

    public function testGetNumberAndNameWithDelimiterArg() {
        $this->role_fixture->setDisplayBrowsing('Number');
        $this->role_fixture->setDisplayFrontdoor('Number');
        $this->role_fixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = new Opus_Collection( $this->object->getId() );
        $this->assertEquals('number - name', $collection->getNumberAndName(' - '));
    }

    public function testGetNumSubTreeEntries() {
        $this->object->setVisible(1);
        $this->object->store();

        $this->assertEquals(0, $this->object->getNumSubtreeEntries(),
                'Initially, collection should have zero entries.');

        $d1 = new Opus_Document();
        $d1->setServerState('unpublished');
        $d1->addCollection($this->object);
        $d1->store();

        $this->assertEquals(0, $this->object->getNumSubtreeEntries(),
                'Collection has one entry, but no published.');

        $d1->setServerState('published');
        $d1->store();

        $this->assertEquals(1, $this->object->getNumSubtreeEntries(),
                'Collection has one published entry.');
    }

    public function testDeleteCollectionFromDocumentDoesNotDeleteCollection() {
        $this->object->setVisible(1);
        $collectionId = $this->object->store();

        $d = new Opus_Document();
        $d->addCollection( $this->object );
        $docId = $d->store();

        $d = new Opus_Document( $docId );
        $c = $d->getCollection();
        $this->assertEquals(1, count($c));

        $d->setCollection(array());
        $d->store();

        $collection = new Opus_Collection($collectionId);
    }

    public function testGettingIdOfParentNode() {
        $this->object->setVisible(1);
        $collectionId = $this->object->store();

        $child = $this->object->addFirstChild();
        $child->store();

        $this->assertEquals($collectionId, $child->getParentNodeId());
    }

    public function testDeleteNonRootCollectionWithChild() {
        $root = $this->object;

        $child = $root->addLastChild();
        $root->store();

        // FIXME: We have to reload model to get correct results!
        $root = new Opus_Collection($root->getId());
        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(1, count($root->getChildren()), 'Root collection should have one child.');

        $child->addFirstChild();
        $child->store();

        $child->delete();

        // FIXME: We have to reload model to get correct results!
        $root = new Opus_Collection($root->getId());
        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(0, count($root->getChildren()), 'Root collection should have no child.');
    }

    public function testGetDisplayNameWithIncompatibleRole() {
        $collRole = new Opus_CollectionRole();
        $collRole->setDisplayBrowsing('Number');
        $collRole->setDisplayFrontdoor('Number');
        $collRole->setName('name');
        $collRole->setOaiName('oainame');
        $collRole->store();

        $this->role_fixture->setDisplayBrowsing('Name');
        $this->role_fixture->setDisplayFrontdoor('Number');
        $this->role_fixture->store();

        $this->object->store();

        $coll = new Opus_Collection( $this->object->getId() );

        $e = null;
        try {
            $coll->getDisplayName('browsing', $collRole);
        }
        catch (Exception $e) {
            $collRole->delete();
            $coll->delete();
        }

        $this->assertTrue($e instanceof InvalidArgumentException);
    }

    public function testGetVisibleChildren() {
        $this->object->store();

        // add two children: one of them is invisible
        $coll1 = new Opus_Collection();
        $coll1->setVisible('1');
        $this->object->addFirstChild($coll1);        
        $coll1->store();

        $coll2 = new Opus_Collection();
        $coll2->setVisible('0');
        $this->object->addFirstChild($coll2);
        $coll2->store();

        $this->object->store();

        $this->assertEquals(1, count($this->object->getVisibleChildren()));
        $this->assertEquals(2, count($this->object->getChildren()));
    }


}
