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
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * TODO Test für das rekursive Speichern von Children
 */

namespace OpusTest;

use Exception;
use InvalidArgumentException;
use Opus\Common\Collection;
use Opus\Common\CollectionInterface;
use Opus\Common\CollectionRole;
use Opus\Common\CollectionRoleInterface;
use Opus\Common\Config;
use Opus\Common\Model\NotFoundException;
use Opus\Db\Collections;
use Opus\Document;
use Opus\Model\Xml\Cache;
use OpusTest\Model\Plugin\AbstractPluginMock;
use OpusTest\TestAsset\NestedSetValidator;
use OpusTest\TestAsset\TestCase;

use function count;
use function in_array;
use function is_array;
use function is_object;
use function rand;
use function sleep;
use function sort;

class CollectionTest extends TestCase
{
    /** @var CollectionRoleInterface */
    protected $roleFixture;

    /** @var string */
    protected $roleName = "";

    /** @var string */
    protected $roleOaiName = "";

    /** @var CollectionInterface */
    protected $object;

    /**
     * SetUp method.  Inherits database cleanup from parent.
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(true, [
            'documents',
            'collections_roles',
            'collections',
            'link_documents_collections',
        ]);

        $this->roleName    = "role-name-" . rand();
        $this->roleOaiName = "role-oainame-" . rand();

        $this->roleFixture = CollectionRole::new();
        $this->roleFixture->setName($this->roleName);
        $this->roleFixture->setOaiName($this->roleOaiName);
        $this->roleFixture->setVisible(1);
        $this->roleFixture->setVisibleBrowsingStart(1);
        $this->roleFixture->store();

        $this->object = $this->roleFixture->addRootCollection();
        $this->object->setTheme('dummy');
        $this->roleFixture->store();
    }

    protected function tearDown(): void
    {
        if (is_object($this->roleFixture)) {
            $this->roleFixture->delete();
        }

        parent::tearDown();
    }

    public function testConstructorForExistingCollection()
    {
        $this->assertNotNull($this->object->getId(), 'Collection storing failed: should have an Id.');
        $this->assertNotNull($this->object->getRoleId(), 'Collection storing failed: should have an RoleId.');

        // Check, if we can create the object for this Id.
        $collectionId = $this->object->getId();
        $collection   = Collection::get($collectionId);

        $this->assertNotNull($collection, 'Collection construction failed: collection is null.');
        $this->assertNotNull($collection->getId(), 'Collection storing failed: should have an Id.');
        $this->assertNotNull($collection->getRoleId(), 'Collection storing failed: should have an RoleId.');
    }

    /**
     * Test if delete really deletes.
     */
    public function testDeleteNoChildren()
    {
        $collectionId = $this->object->getId();
        $this->object->delete();

        $this->expectException(NotFoundException::class);
        Collection::get($collectionId);
    }

    /**
     * Test if we can retrieve stored themes from the database.
     */
    public function testGetTheme()
    {
        $this->object->setTheme('test-theme');
        $this->object->store();

        $collection = $this->object;
        $this->assertEquals('test-theme', $collection->getTheme(), 'After store: Stored theme does not match expectation.');

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('test-theme', $collection->getTheme(), 'After reload: Stored theme does not match expectation.');
    }

    /**
     * Test if virtual field "GetOaiName" contains the value of "OaiSubset".
     */
    public function testGetOaiName()
    {
        $this->object->setOaiSubset("subset");
        $this->assertEquals('subset', $this->object->getOaiSubset());

        $collectionId = $this->object->store();
        $this->assertNotNull($collectionId);

        $collection = Collection::get($collectionId);
        $this->assertEquals('subset', $this->object->getOaiSubset());
    }

    /**
     * Test if "store()" returns primary key of current object.
     */
    public function testStoreReturnsId()
    {
        $collectionId = $this->object->store();
        $this->assertNotNull($collectionId);

        $testObject = Collection::get($collectionId);
        $this->assertEquals($this->object->getRoleId(), $testObject->getRoleId());
    }

    public function testGetChildren()
    {
        $root = $this->object;

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(0, count($root->getChildren()), 'Root collection without children should return empty array.');

        $child1 = $root->addLastChild();
        $root->store();

        // FIXME: We have to reload model to get correct results!
        $root = Collection::get($root->getId());

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(1, count($root->getChildren()), 'Root collection should have one child.');

        $root->addLastChild();
        $root->store();

        $child1->addFirstChild();
        $child1->store();

        // FIXME: We have to reload model to get correct results!
        $root = Collection::get($root->getId());

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(2, count($root->getChildren()), 'Root collection should have two children.');
    }

    public function testGetDefaultThemeIfSetDefaultTheme()
    {
        $defaultTheme = Config::get()->theme;
        $this->assertFalse(empty($defaultTheme), 'Could not get theme from config');

        $this->object->setTheme($defaultTheme);
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals($defaultTheme, $collection->getTheme(), 'Expect default theme if non set');
    }

    public function testGetDefaultThemeIfSetNullTheme()
    {
        $this->object->setTheme(null);
        $this->object->store();

        $defaultTheme = Config::get()->theme;
        $this->assertFalse(empty($defaultTheme), 'Could not get theme from config');

        $collection = Collection::get($this->object->getId());
        $this->assertEquals($defaultTheme, $collection->getTheme(), 'Expect default theme if non set');
    }

    public function testGetDocumentIds()
    {
        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) === 0, 'Expected empty id array');

        $d = new Document();
        $d->addCollection($this->object);
        $d->store();

        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) === 1, 'Expected one element in array');
    }

    public function testGetDocumentIdsMaxElements()
    {
        $docIds = $this->object->getDocumentIds();
        $this->assertTrue(count($docIds) === 0, 'Expected empty id array');

        $max       = 4;
        $storedIds = [];
        for ($i = 0; $i < $max; $i++) {
            $d = new Document();
            $d->addCollection($this->object);
            $d->store();

            $storedIds[] = $d->getId();
        }

        // Add some published documents.
        $max                = 4;
        $storedPublishedIds = [];
        for ($i = 0; $i < $max; $i++) {
            $d = new Document();
            $d->addCollection($this->object);
            $d->setServerState('published');
            $d->store();

            $storedIds[]          = $d->getId();
            $storedPublishedIds[] = $d->getId();
        }

        // Check if getDocumentIds returns *all* documents.
        $collection = Collection::get($this->object->getId());
        $docIds     = $collection->getDocumentIds();
        $this->assertEquals(2 * $max, count($docIds), 'Expected ' . (2 * $max) . ' element in array');

        sort($storedIds);
        sort($docIds);
        $this->assertEquals($storedIds, $docIds);

        // Check if getDocumentIds returns only published documents.
        $publishedIds = $collection->getPublishedDocumentIds();
        $this->assertEquals($max, count($publishedIds), 'Expected ' . $max . ' element in array');

        sort($storedPublishedIds);
        sort($publishedIds);
        $this->assertEquals($storedPublishedIds, $publishedIds);
    }

    public function testGetDisplayName()
    {
        $this->roleFixture->setDisplayBrowsing('Name');
        $this->roleFixture->setDisplayFrontdoor('Number');
        $this->roleFixture->store();

        $this->object->setName('fooblablub');
        $this->object->setNumber('thirteen');
        $this->object->store();

        $collection = Collection::get($this->object->getId());

        $this->assertEquals($this->roleFixture->getId(), $collection->getRoleId());

        $role = $collection->getRole();

        $this->assertEquals($this->roleFixture->getId(), $role->getId());
        $this->assertEquals('Name', $role->getDisplayBrowsing());
        $this->assertEquals('Number', $role->getDisplayFrontdoor());

        $this->assertEquals('fooblablub', $collection->getDisplayName('browsing'));
        $this->assertEquals('thirteen', $collection->getDisplayName('frontdoor'));
    }

    public function testGetDisplayFrontdoor()
    {
        $this->roleFixture->setDisplayBrowsing('Name');
        $this->roleFixture->setDisplayFrontdoor('Number');
        $this->roleFixture->store();

        $this->object->setName('fooblablub');
        $this->object->setNumber('thirteen');
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('thirteen', $collection->getDisplayFrontdoor());

        $this->roleFixture->setDisplayFrontdoor('Number, Name');
        $this->roleFixture->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('thirteen fooblablub', $collection->getDisplayFrontdoor());
    }

    public function testGetDisplayNameForBrowsingContextWithoutArg()
    {
        $this->roleFixture->setDisplayBrowsing('Name');
        $this->roleFixture->store();

        $this->object->setName('fooblablub');
        $this->object->setNumber('thirteen');
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('fooblablub', $collection->getDisplayNameForBrowsingContext());
    }

    public function testGetDisplayNameForBrowsingContextWithArg()
    {
        $this->roleFixture->setDisplayBrowsing('Name');
        $this->roleFixture->store();

        $this->object->setName('fooblablub');
        $this->object->setNumber('thirteen');
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('fooblablub', $collection->getDisplayNameForBrowsingContext($this->roleFixture));
    }

    public function testGetNumberAndNameIsIndependentOfDiplayBrowsingName()
    {
        $this->roleFixture->setDisplayBrowsing('Name');
        $this->roleFixture->setDisplayFrontdoor('Number');
        $this->roleFixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('number name', $collection->getNumberAndName());
    }

    public function testGetNumberAndNameIsIndependetOfDisplayBrowsingNumber()
    {
        $this->roleFixture->setDisplayBrowsing('Number');
        $this->roleFixture->setDisplayFrontdoor('Number');
        $this->roleFixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('number name', $collection->getNumberAndName());
    }

    public function testGetNumberAndNameIsIndependetOfDisplayBrowsingNameNumber()
    {
        $this->roleFixture->setDisplayBrowsing('Name,Number');
        $this->roleFixture->setDisplayFrontdoor('Number');
        $this->roleFixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('number name', $collection->getNumberAndName());
    }

    public function testGetNumberAndNameWithDelimiterArg()
    {
        $this->roleFixture->setDisplayBrowsing('Number');
        $this->roleFixture->setDisplayFrontdoor('Number');
        $this->roleFixture->store();

        $this->object->setName('name');
        $this->object->setNumber('number');
        $this->object->store();

        $collection = Collection::get($this->object->getId());
        $this->assertEquals('number - name', $collection->getNumberAndName(' - '));
    }

    public function testGetNumSubTreeEntries()
    {
        $this->object->setVisible(1);
        $this->object->store();

        $this->assertEquals(0, $this->object->getNumSubtreeEntries(), 'Initially, collection should have zero entries.');

        $d1 = new Document();
        $d1->setServerState('unpublished');
        $d1->addCollection($this->object);
        $d1->store();

        $this->assertEquals(0, $this->object->getNumSubtreeEntries(), 'Collection has one entry, but no published.');

        $d1->setServerState('published');
        $d1->store();

        $this->assertEquals(1, $this->object->getNumSubtreeEntries(), 'Collection has one published entry.');
    }

    public function testGetNumSubTreeEntriesExcludeInvisibleSubtrees()
    {
        $this->markTestSkipped('TODO - problem not fixed yet');

        $this->object->setVisible(1);
        $this->object->store();

        $colA = Collection::new();
        $colA->setName('colA');
        $colA->setVisible(1);

        $colB = Collection::new();
        $colB->setName('colB');
        $colB->setVisible(1);

        $colC = Collection::new();
        $colC->setName('colC');
        $colC->setVisible(1);

        $this->object->addFirstChild($colA);
        $colA->store();
        $this->object->store();

        $this->object->addLastChild($colB);
        $colB->store();
        $this->object->store();

        $colB->addFirstChild($colC);
        $colC->store();
        $colB->store();

        $doc = new Document();
        $doc->setServerState('published');
        $doc->addCollection($colA);
        $doc->store();

        $doc = new Document();
        $doc->setServerState('published');
        $doc->addCollection($colB);
        $doc->store();

        $doc = new Document();
        $doc->setServerState('published');
        $doc->addCollection($colC);
        $doc->store();

        $this->assertEquals(3, $this->object->getNumSubtreeEntries());
        $this->assertEquals(1, $colA->getNumSubtreeEntries());
        $this->assertEquals(2, $colB->getNumSubtreeEntries());
        $this->assertEquals(1, $colC->getNumSubtreeEntries());

        // make node B and therefore childnode C invisible
        $colB->setVisible(0);
        $colB->store();

        // only node A should count now
        $this->assertEquals(1, $this->object->getNumSubtreeEntries());
        $this->assertEquals(1, $colA->getNumSubtreeEntries());
        $this->assertEquals(0, $colB->getNumSubtreeEntries()); // invisible
        $this->assertEquals(0, $colC->getNumSubtreeEntries()); // parent invisible
    }

    public function testDeleteCollectionFromDocumentDoesNotDeleteCollection()
    {
        $this->object->setVisible(1);
        $collectionId = $this->object->store();

        $d = new Document();
        $d->addCollection($this->object);
        $docId = $d->store();

        $d = new Document($docId);
        $c = $d->getCollection();
        $this->assertEquals(1, count($c));

        $d->setCollection([]);
        $d->store();

        $collection = Collection::get($collectionId);
    }

    public function testGettingIdOfParentNode()
    {
        $this->object->setVisible(1);
        $collectionId = $this->object->store();

        $child = $this->object->addFirstChild();
        $child->store();

        $this->assertEquals($collectionId, $child->getParentNodeId());
    }

    public function testDeleteNonRootCollectionWithChild()
    {
        $root = $this->object;

        $child = $root->addLastChild();
        $root->store();

        // FIXME: We have to reload model to get correct results!
        $root = Collection::get($root->getId());
        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(1, count($root->getChildren()), 'Root collection should have one child.');

        $child->addFirstChild();
        $child->store();

        $child->delete();

        // FIXME: We have to reload model to get correct results!
        $root = Collection::get($root->getId());
        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(0, count($root->getChildren()), 'Root collection should have no child.');
    }

    public function testGetDisplayNameWithIncompatibleRole()
    {
        $collRole = CollectionRole::new();
        $collRole->setDisplayBrowsing('Number');
        $collRole->setDisplayFrontdoor('Number');
        $collRole->setName('name');
        $collRole->setOaiName('oainame');
        $collRole->store();

        $this->roleFixture->setDisplayBrowsing('Name');
        $this->roleFixture->setDisplayFrontdoor('Number');
        $this->roleFixture->store();

        $this->object->store();

        $coll = Collection::get($this->object->getId());

        $e = null;
        try {
            $coll->getDisplayName('browsing', $collRole);
        } catch (Exception $e) {
            $collRole->delete();
            $coll->delete();
        }

        $this->assertTrue($e instanceof InvalidArgumentException);
    }

    public function testGetVisibleChildren()
    {
        $this->object->store();

        // add two children: one of them (the first child) is invisible
        $coll1 = Collection::new();
        $coll1->setVisible('1');
        $this->object->addFirstChild($coll1);
        $coll1->store();

        $coll2 = Collection::new();
        $coll2->setVisible('0');
        $this->object->addFirstChild($coll2);
        $coll2->store();

        $this->object->store();

        $children = $this->object->getVisibleChildren();
        $this->assertEquals(1, count($children));
        $this->assertEquals($coll1->getId(), $children[0]->getId());

        $children = $this->object->getChildren();
        $this->assertEquals(2, count($children));
        $this->assertEquals($coll2->getId(), $children[0]->getId());
        $this->assertEquals($coll1->getId(), $children[1]->getId());
    }

    public function testHasVisibleChildren()
    {
        $this->object->store();

        $this->assertFalse($this->object->hasVisibleChildren());
        $this->assertFalse($this->object->hasChildren());

        $coll = Collection::new();
        $coll->setVisible('0');
        $this->object->addFirstChild($coll);
        $coll->store();
        $this->object->store();

        $this->assertFalse($this->object->hasVisibleChildren());
        $this->assertTrue($this->object->hasChildren());

        $coll = Collection::new();
        $coll->setVisible('1');
        $this->object->addFirstChild($coll);
        $coll->store();
        $this->object->store();

        $this->assertTrue($this->object->hasVisibleChildren());
        $this->assertTrue($this->object->hasChildren());
    }

    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache()
    {
        $d = new Document();
        $d->addCollection($this->object);
        $docId = $d->store();

        $xmlCache = new Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $this->object->setName('test');
        $this->object->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }

    /**
     * Regression Test for OPUSVIER-2935
     */
    public function testInvalidateDocumentCacheOnDelete()
    {
        $d = new Document();
        $d->addCollection($this->object);
        $docId                          = $d->store();
        $serverDateModifiedBeforeDelete = $d->getServerDateModified();

        $xmlCache = new Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');

        sleep(1);

        $this->object->delete();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');

        $d                       = new Document($docId);
        $serverDateModifiedAfter = $d->getServerDateModified();

        $this->assertTrue($serverDateModifiedAfter->getTimestamp() > $serverDateModifiedBeforeDelete->getTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');
    }

    /**
     * Regression-Test for OPUSVIER-2937
     *
     * Hook only gets called if object has been stored (persisted) in database.
     */
    public function testPreDeletePluginHookGetsCalled()
    {
        $pluginMock = new AbstractPluginMock();

        $this->assertTrue(empty($pluginMock->calledHooks), 'expected empty array');

        $collection = Collection::new();
        $collection->registerPlugin($pluginMock);

        $this->roleFixture->getRootCollection()->addFirstChild($collection);

        $collection->store();
        $collection->delete();

        $this->assertTrue(
            in_array('OpusTest\Model\Plugin\AbstractPluginMock::preDelete', $pluginMock->calledHooks),
            'expected call to preDelete hook'
        );
    }

    public function testPreDeletePluginHookGetsCalledOnlyForStoredObject()
    {
        $pluginMock = new AbstractPluginMock();

        $this->assertTrue(empty($pluginMock->calledHooks), 'expected empty array');

        $collection = Collection::new();
        $collection->registerPlugin($pluginMock);
        $collection->delete();

        $this->assertFalse(
            in_array('AbstractPluginMock::preDelete', $pluginMock->calledHooks),
            'expected no call to preDelete hook'
        );
    }

    /**
     * Regression Test for OPUSVIER-3145
     *
     * @doesNotPerformAssertions
     */
    public function testStoreCollection()
    {
        $collectionRole = CollectionRole::new();
        $collectionRole->setName('Test');
        $collectionRole->setOaiName('Test');
        $collection = $collectionRole->addRootCollection();
        $collectionRole->store();
        // changing certain fields currently results in Exception
        $collection->setVisible(true);
        $collection->store();
    }

    /**
     * Regression Test for OPUSVIER-3114
     */
    public function testDocumentServerDateModifiedNotUpdatedWithConfiguredFields()
    {
        $fields = ['Theme', 'OaiSubset'];

        $doc = new Document();
        $doc->setType("article")
                ->setServerState('published')
                ->addCollection($this->object);
        $docId = $doc->store();

        $serverDateModified = $doc->getServerDateModified();

        sleep(1);

        $collection = $this->roleFixture->getRootCollection();

        foreach ($fields as $fieldName) {
            $oldValue = $collection->{'get' . $fieldName}();
            $collection->{'set' . $fieldName}(1);
            $this->assertNotEquals($collection->{'get' . $fieldName}(), $oldValue, 'Expected different values before and after setting value');
        }

        $collection->store();
        $docReloaded = new Document($docId);
        $this->assertEquals((string) $serverDateModified, (string) $docReloaded->getServerDateModified(), 'Expected no difference in server date modified.');
    }

    public function testGetSetVisiblePublish()
    {
        $collection = $this->roleFixture->getRootCollection();
        $collection->setVisiblePublish(1);
        $cId        = $collection->store();
        $collection = Collection::get($cId);
        $this->assertEquals(1, $collection->getVisiblePublish());
        $collection->setVisiblePublish(0);
        $cId        = $collection->store();
        $collection = Collection::get($cId);
        $this->assertEquals(0, $collection->getVisiblePublish());
    }

    /**
     * Regression Test for OPUSVIER-2726
     */
    public function testMoveBeforePrevSibling()
    {
        $this->setUpFixtureForMoveTests();

        $colId = 1; // $this->object->getId();

        $root     = Collection::get($colId);
        $children = $root->getChildren();

        $this->assertEquals('test3', $children[2]->getNumber(), 'Test fixture was modified.');
        $this->assertEquals('test4', $children[3]->getNumber(), 'Test fixture was modified.');

        $collection = Collection::get(8);
        $this->assertEquals('test4', $collection->getNumber(), 'Test fixture was modified.');

        $collection->moveBeforePrevSibling();

        $root     = Collection::get($colId);
        $children = $root->getChildren();
        $this->assertEquals(7, count($children));

        $this->assertEquals('test4', $children[2]->getNumber());

        $childrenOfTest4 = $children[2]->getChildren();

        $this->assertEquals('test4.1', $childrenOfTest4[0]->getNumber());
        $this->assertEquals('test4.2', $childrenOfTest4[1]->getNumber());

        $this->assertEquals('test3', $children[3]->getNumber());

        $childrenOfTest3 = $children[3]->getChildren();
        $this->assertEquals('test3.1', $childrenOfTest3[0]->getNumber());
        $this->assertEquals('test3.2', $childrenOfTest3[1]->getNumber());

        $childrenOfTest32 = $childrenOfTest3[1]->getChildren();
        $this->assertEquals('test3.2.1', $childrenOfTest32[0]->getNumber());

        $this->validateNestedSet();
    }

    /**
     * Regression Test for OPUSVIER-2726
     */
    public function testMoveAfterNextSibling()
    {
        $this->setUpFixtureForMoveTests();

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals($children[3]->getNumber(), 'test4');
        $this->assertEquals($children[4]->getNumber(), 'test5');

        $collection = Collection::get(8);
        $this->assertEquals($collection->getNumber(), 'test4', 'Test fixture was modified.');

        $collection->moveAfterNextSibling();

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals($children[3]->getNumber(), 'test5');
        $this->assertEquals(count($children[3]->getChildren()), 1);
        $this->assertEquals($children[4]->getNumber(), 'test4');
        $this->assertEquals(count($children[4]->getChildren()), 2);

        $this->validateNestedSet();
    }

    public function testNestedSet()
    {
        $this->setUpFixtureForMoveTests();

        $this->validateNestedSet();
    }

    /**
     *   1,1,NULL     ,NULL,      NULL,1,28,NULL,0,1
     *   2,1,test     , Testeintrag   ,NULL,2,3,1,0,1
     *   3,1,test2    ,"Testeintrag 2",NULL,4,5,1,0,1
     *   4,1,test3    ,"Testeintrag 3",NULL,6,13,1,0,1
     *   5,1,test3.1  ,"Testeintrag 3.1",NULL,7,8,4,0,1
     *   6,1,test3.2  ,"Testeintrag 3.2",NULL,9,12,4,0,1
     *   7,1,test3.2.1,"Testeintrag 3.2.1",NULL,10,11,6,0,1
     *   8,1,test4    ,"Testeintrag 4",NULL,14,19,1,0,1
     *   9,1,test4.1  ,"Testeintrag 4.1",NULL,15,16,8,0,1
     *  10,1,test4.2  ,"Testeintrag 4.2",NULL,17,18,8,0,1
     *  11,1,test5    ,"Testeintrag 5",NULL,20,23,1,0,1
     *  12,1,test5.1  ,"Testeintrag 5.1",NULL,21,22,11,0,1
     *  13,1,test6    ,"Testeintrag 6",NULL,24,25,1,0,1
     *  14,1,test7    ,"Testeintrag 7",NULL,26,27,1,0,1
     */
    protected function setUpFixtureForMoveTests()
    {
        $root = $this->object;

        $children   = [];
        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag');
        $children[count($children) - 1]->setNumber('test');

        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 2');
        $children[count($children) - 1]->setNumber('test2');

        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 3');
        $children[count($children) - 1]->setNumber('test3');

        $children[] = $children[count($children) - 1]->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 3.1');
        $children[count($children) - 1]->setNumber('test3.1');

        $children[] = $children[count($children) - 2]->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 3.2');
        $children[count($children) - 1]->setNumber('test3.2');

        $children[] = $children[count($children) - 1]->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 3.2.1');
        $children[count($children) - 1]->setNumber('test3.2.1');

        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 4');
        $children[count($children) - 1]->setNumber('test4');

        $children[] = $children[count($children) - 1]->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 4.1');
        $children[count($children) - 1]->setNumber('test4.1');

        $children[] = $children[count($children) - 2]->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 4.2');
        $children[count($children) - 1]->setNumber('test4.2');

        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 5');
        $children[count($children) - 1]->setNumber('test5');

        $children[] = $children[count($children) - 1]->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 5.1');
        $children[count($children) - 1]->setNumber('test5.1');

        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 6');
        $children[count($children) - 1]->setNumber('test6');

        $children[] = $root->addLastChild();
        $children[count($children) - 1]->setName('Testeintrag 7');
        $children[count($children) - 1]->setNumber('test7');

        $root->store();
    }

    /**
     * Test für OPUSVIER-3308.
     */
    public function testHasVisiblePublishChildren()
    {
        $this->object->store();

        $this->assertFalse($this->object->hasVisiblePublishChildren());
        $this->assertFalse($this->object->hasChildren());

        $coll = Collection::new();
        $coll->setVisiblePublish('0');
        $coll->setVisible('0');
        $this->object->addFirstChild($coll);
        $coll->store();
        $this->object->store();

        $this->assertFalse($this->object->hasVisiblePublishChildren());
        $this->assertTrue($this->object->hasChildren());

        $coll = Collection::new();
        $coll->setVisiblePublish('0');
        $coll->setVisible('1');
        $this->object->addFirstChild($coll);
        $coll->store();
        $this->object->store();

        $this->assertFalse($this->object->hasVisiblePublishChildren());
        $this->assertTrue($this->object->hasChildren());

        $coll = Collection::new();
        $coll->setVisiblePublish('1');
        $coll->setVisible('1');
        $this->object->addFirstChild($coll);
        $coll->store();
        $this->object->store();

        $this->assertTrue($this->object->hasVisiblePublishChildren());
        $this->assertTrue($this->object->hasChildren());
    }

    public function testHasVisiblePublishChildrenFalseIfNotVisible()
    {
        $this->object->store();

        $this->assertFalse($this->object->hasVisiblePublishChildren());
        $this->assertFalse($this->object->hasChildren());

        $coll = Collection::new();
        $coll->setVisiblePublish('1');
        $coll->setVisible('0');
        $this->object->addFirstChild($coll);
        $coll->store();
        $this->object->store();

        $this->assertFalse($this->object->hasVisiblePublishChildren());
        $this->assertTrue($this->object->hasChildren());
    }

    /**
     * Test für OPUSVIER-3308.
     */
    public function testGetVisiblePublishChildren()
    {
        $this->object->store();

        // add two children: one of them (the first child) is invisible
        $coll1 = Collection::new();
        $coll1->setVisiblePublish('1');
        $coll1->setVisible('1');
        $this->object->addFirstChild($coll1);
        $coll1->store();

        $coll2 = Collection::new();
        $coll2->setVisiblePublish('0');
        $coll2->setVisible('0');
        $this->object->addFirstChild($coll2);
        $coll2->store();

        $coll3 = Collection::new();
        $coll3->setVisiblePublish('1');
        $coll3->setVisible('0');
        $this->object->addFirstChild($coll3);
        $coll3->store();

        $coll4 = Collection::new();
        $coll4->setVisiblePublish('0');
        $coll4->setVisible('1');
        $this->object->addFirstChild($coll4);
        $coll4->store();

        $this->object->store();

        $children = $this->object->getVisiblePublishChildren();
        $this->assertEquals(1, count($children));
        $this->assertEquals($coll1->getId(), $children[0]->getId());

        $children = $this->object->getChildren();
        $this->assertEquals(4, count($children));
        $this->assertEquals($coll4->getId(), $children[0]->getId());
        $this->assertEquals($coll3->getId(), $children[1]->getId());
        $this->assertEquals($coll2->getId(), $children[2]->getId());
        $this->assertEquals($coll1->getId(), $children[3]->getId());
    }

    public function testMoveToPositionUp()
    {
        $this->setUpFixtureForMoveTests();

        $root     = Collection::get(1);
        $children = $root->getChildren();
        $this->assertEquals(7, count($children));

        $this->assertEquals('test3', $children[2]->getNumber(), 'Test fixture was modified.');
        $this->assertEquals('test4', $children[3]->getNumber(), 'Test fixture was modified.');

        $collection = Collection::get(8);
        $this->assertEquals('test4', $collection->getNumber(), 'Test fixture was modified.');

        $collection->moveToPosition(1);

        $root     = Collection::get(1);
        $children = $root->getChildren();
        $this->assertEquals(7, count($children));

        $this->assertEquals('test4', $children[1]->getNumber());
        $this->assertEquals('test2', $children[2]->getNumber());
        $this->assertEquals('test3', $children[3]->getNumber());

        $childrenOfTest4 = $children[1]->getChildren();

        $this->assertEquals('test4.1', $childrenOfTest4[0]->getNumber());
        $this->assertEquals('test4.2', $childrenOfTest4[1]->getNumber());

        $childrenOfTest3 = $children[3]->getChildren();
        $this->assertEquals('test3.1', $childrenOfTest3[0]->getNumber());
        $this->assertEquals('test3.2', $childrenOfTest3[1]->getNumber());

        $childrenOfTest32 = $childrenOfTest3[1]->getChildren();
        $this->assertEquals('test3.2.1', $childrenOfTest32[0]->getNumber());

        $this->validateNestedSet();
    }

    public function testMoveToPositionDown()
    {
        $this->setUpFixtureForMoveTests();

        $collection = Collection::get(4);

        $this->assertEquals('test3', $collection->getNumber());

        $collection->moveToPosition(5);

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals('test3', $children[4]->getNumber());
        $this->assertEquals('test4', $children[2]->getNumber());
        $this->assertEquals('test6', $children[5]->getNumber());
        $this->assertEquals('test7', $children[6]->getNumber());

        $this->validateNestedSet();
    }

    public function testMoveToStart()
    {
        $this->setUpFixtureForMoveTests();

        $collection = Collection::get(11);

        $this->assertEquals('test5', $collection->getNumber());

        $collection->moveToStart();

        $root = Collection::get(1);

        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals('test5', $children[0]->getNumber());
        $this->assertEquals('test', $children[1]->getNumber());

        $this->validateNestedSet();
    }

    public function testMoveToEnd()
    {
        $this->setUpFixtureForMoveTests();

        $collection = Collection::get(8);

        $this->assertEquals('test4', $collection->getNumber());

        $collection->moveToEnd();

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals('test5', $children[3]->getNumber());
        $this->assertEquals('test7', $children[5]->getNumber());
        $this->assertEquals('test4', $children[6]->getNumber());

        $this->validateNestedSet();
    }

    public function testSortChildrenByName()
    {
        $this->setUpFixtureForMoveTests();

        $root = Collection::get(1);

        $root->sortChildrenByName(true);

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals('test7', $children[0]->getNumber());
        $this->assertEquals('test4', $children[3]->getNumber());
        $this->assertEquals('test', $children[6]->getNumber());

        $this->validateNestedSet();

        $root->sortChildrenByName();

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals('test', $children[0]->getNumber());
        $this->assertEquals('test5', $children[4]->getNumber());
        $this->assertEquals('test7', $children[6]->getNumber());

        $this->validateNestedSet();
    }

    public function testSortChildrenByNumber()
    {
        $this->setUpFixtureForMoveTests();

        $root = Collection::get(1);

        $root->sortChildrenByName(true);

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals('test7', $children[0]->getNumber());
        $this->assertEquals('test4', $children[3]->getNumber());
        $this->assertEquals('test', $children[6]->getNumber());

        $this->validateNestedSet();

        $root->sortChildrenByName();

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertEquals(7, count($children));

        $this->assertEquals('test', $children[0]->getNumber());
        $this->assertEquals('test5', $children[4]->getNumber());
        $this->assertEquals('test7', $children[6]->getNumber());

        $this->validateNestedSet();
    }

    public function testSortChildrenBySpecifiedOrder()
    {
        $this->setUpFixtureForMoveTests();

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertCount(7, $children);

        $root->applySortOrderOfChildren([4, 11, 3, 14, 2, 8, 13]);

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertCount(7, $children);

        $this->assertEquals('test3', $children[0]->getNumber());
        $this->assertEquals('test5', $children[1]->getNumber());
        $this->assertEquals('test2', $children[2]->getNumber());
        $this->assertEquals('test7', $children[3]->getNumber());
        $this->assertEquals('test', $children[4]->getNumber());
        $this->assertEquals('test4', $children[5]->getNumber());
        $this->assertEquals('test6', $children[6]->getNumber());

        $this->validateNestedSet();
    }

    public function testSortChildrenBySpecifiedOrderBadId()
    {
        $this->setUpFixtureForMoveTests();

        $root     = Collection::get(1);
        $children = $root->getChildren();

        $this->assertCount(7, $children);

        $this->expectException(InvalidArgumentException::class, 'is no child of');

        $root->applySortOrderOfChildren([4, 11, 3, 16, 2, 8, 13]);
    }

    /**
     * Verifies that the NestedSet structure is still valid.
     */
    protected function validateNestedSet()
    {
        $table = new Collections();

        $select = $table->select()->where('role_id = ?', 1)->order('left_id ASC');

        $rows = $table->fetchAll($select);

        $this->assertEquals(14, count($rows));

        $this->assertEquals(1, $rows[0]->left_id);
        $this->assertEquals(28, $rows[0]->right_id);

        $validator = new NestedSetValidator($table);

        $this->assertTrue($validator->validate(1));
    }

    public function testDeletedCollectionRemovedFromDocument()
    {
        $role = CollectionRole::new();
        $role->setName('foobar-name');
        $role->setOaiName('foobar-oainame');
        $role->store();

        $root = $role->addRootCollection();
        $role->store();

        $doc = new Document();
        $doc->setServerState('published');
        $docId = $doc->store();

        $doc = new Document($docId);
        $doc->addCollection($root);
        $doc->store();

        $documents = Collection::fetchCollectionIdsByDocumentId($docId);

        $this->assertCount(1, $documents);
        $this->assertContains($root->getId(), $documents);

        $root->delete();

        $documents = Collection::fetchCollectionIdsByDocumentId($docId);

        $this->assertCount(0, $documents);
    }

    public function testHandlingOfNullValues()
    {
        $collection = $this->object;

        $collection->setNumber(null);
        $collection->setOaiSubset(null);

        $collection->store();

        $collection = Collection::get($collection->getId());

        $this->assertNull($collection->getNumber());
        $this->assertNull($collection->getOaiSubset());
    }

    public function testGetDisplayNameForRootCollection()
    {
        $role = CollectionRole::new();
        $role->setName('foobar-name');
        $role->setOaiName('foobar-oainame');
        $role->store();

        $role->addRootCollection();
        $roleId = $role->store();

        // new instanciation is necessary before root collection can access role object properly
        $role = CollectionRole::get($roleId);
        $root = $role->getRootCollection();

        $this->assertEquals('foobar-name', $role->getDisplayName());
        $this->assertEquals('', $root->getDisplayName());
    }

    public function testGetDisplayNameForRootCollectionWithNameSet()
    {
        $role = CollectionRole::new();
        $role->setName('foobar-name');
        $role->setOaiName('foobar-oainame');
        $role->store();

        $root = $role->addRootCollection();
        $root->setName('rootcol');
        $roleId = $role->store();

        $this->assertEquals('rootcol', $root->getDisplayName());
    }

    public function testIsVisible()
    {
        $this->object->setVisible('1');
        $this->object->store();

        $colA = Collection::new();
        $colA->setName('colA');
        $colA->setVisible('0');
        $this->object->addFirstChild($colA);
        $colA->store();
        $this->object->store();

        $colB = Collection::new();
        $colB->setName('colB');
        $colB->setVisible('1');
        $colA->addFirstChild($colB);
        $colB->store();
        $colA->store();

        $this->assertEquals(0, $colA->getVisible());
        $this->assertEquals(1, $colB->getVisible());

        $this->assertFalse($colB->isVisible());

        $colA->setVisible('1');
        $colA->store();

        $this->assertTrue($colB->isVisible());
    }

    public function testIsVisibleForUnstoredCollection()
    {
        $coll = Collection::new();

        $this->assertFalse($coll->isVisible()); // field 'visible' = null

        $coll->setVisible(1);

        $this->assertTrue($coll->isVisible());

        $coll->setVisible(0);

        $this->assertFalse($coll->isVisible());
    }

    public function testToArray()
    {
        $root = $this->object;

        $role = $this->roleFixture;

        $role->setDisplayFrontdoor('Number');
        $role->setDisplayBrowsing('Name');

        $root->setName('colA');
        $root->setNumber('VI');
        $root->setVisible('1');
        $root->setOaiSubset('testoai');

        $role = CollectionRole::get($role->store());

        $root = $role->getRootCollection();

        $data = $root->toArray();

        $this->assertEquals([
            'Id'                   => $root->getId(),
            'RoleId'               => $role->getId(),
            'RoleName'             => $role->getName(),
            'Name'                 => 'colA',
            'Number'               => 'VI',
            'OaiSubset'            => 'testoai',
            'RoleDisplayFrontdoor' => 'Number',
            'RoleDisplayBrowsing'  => 'Name',
            'DisplayFrontdoor'     => 'VI',
            'DisplayBrowsing'      => 'colA',
        ], $data);
    }

    public function testToArrayForChildCollection()
    {
        $root = $this->object;
        $role = $this->roleFixture;

        $role->setDisplayFrontdoor('Number');
        $role->setDisplayBrowsing('Name');

        $root->setName('TestCol');
        $root->setNumber('6');
        $root->setVisible(0);

        $role = CollectionRole::get($role->store());

        $root = $role->getRootCollection();

        $col = Collection::fromArray([
            'Name'             => 'OPUS',
            'Number'           => '2',
            'OaiSubset'        => 'opus4',
            'DisplayBrowsing'  => 1,
            'DisplayFrontdoor' => 1,
        ]);

        $root->addFirstChild($col);

        $root->store();

        $children = $root->getVisibleChildren();

        $this->assertCount(1, $children);

        $col = $children[0];

        $data = $col->toArray();

        $this->assertEquals([
            'Id'                   => $col->getId(),
            'RoleId'               => $role->getId(),
            'RoleName'             => $role->getName(),
            'DisplayBrowsing'      => 'OPUS',
            'DisplayFrontdoor'     => 2,
            'OaiSubset'            => 'opus4',
            'Name'                 => 'OPUS',
            'Number'               => '2',
            'RoleDisplayBrowsing'  => 'Name',
            'RoleDisplayFrontdoor' => 'Number',
        ], $data);
    }

    public function testFromArray()
    {
        $col = Collection::fromArray([
            'Name'           => 'OPUS',
            'Number'         => '4',
            'OaiSubset'      => 'opus4',
            'Visible'        => 1,
            'VisiblePublish' => 1,
        ]);

        $this->assertNotNull($col);
        $this->assertInstanceOf(CollectionInterface::class, $col);

        $this->assertEquals('OPUS', $col->getName());
        $this->assertEquals('4', $col->getNumber());
        $this->assertEquals('opus4', $col->getOaiSubset());
        $this->assertEquals('1', $col->getVisible());
        $this->assertEquals('1', $col->getVisiblePublish());
        $this->assertNull($col->getId());
    }

    public function testUpdateFromArray()
    {
        $col = Collection::new();

        $col->updateFromArray([
            'Name'           => 'OPUS',
            'Number'         => '4',
            'OaiSubset'      => 'opus4',
            'Visible'        => 1,
            'VisiblePublish' => 1,
        ]);

        $this->assertNotNull($col);
        $this->assertInstanceOf(CollectionInterface::class, $col);

        $this->assertEquals('OPUS', $col->getName());
        $this->assertEquals('4', $col->getNumber());
        $this->assertEquals('opus4', $col->getOaiSubset());
        $this->assertEquals('1', $col->getVisible());
        $this->assertEquals('1', $col->getVisiblePublish());
    }

    /**
     * If a collection already exists an object for that collection should be created.
     */
    public function testFromArrayUseExistingCollection()
    {
        $collectionRole = CollectionRole::new();
        $collectionRole->setName('Test');
        $collectionRole->setOaiName('Test');
        $collection = $collectionRole->addRootCollection();
        $collectionRole->store();

        $colId = $collection->getId();

        $col = Collection::fromArray([
            'Id' => $colId,
        ]);

        $this->assertEquals($col->getId(), $colId);
    }

    public function testFromArrayUsingExistingIdWithChangedValues()
    {
        $root = $this->object;

        $root->setName('TestName');
        $root->setNumber('TestNumber');
        $root->store();

        $colId  = $root->getId();
        $roleId = $root->getRoleId();

        $col = Collection::fromArray([
            'Id'     => $colId,
            'Name'   => 'ChangedName',
            'Number' => 'ChangedNumber',
        ]);

        $this->assertNotNull($col);
        $this->assertInstanceOf(CollectionInterface::class, $col);
        $this->assertEquals($colId, $col->getId());
        $this->assertEquals($roleId, $col->getRoleId());

        // TODO update not supported right now (handling of roleId!)
        // $this->assertEquals('ChangedName', $col->getName());
        // $this->assertEquals('ChangedNumber', $col->getNumber());
    }

    public function testFromArrayUsingUnknownId()
    {
        $col = Collection::fromArray([
            'Id'             => 99,
            'Name'           => 'OPUS',
            'Number'         => '4',
            'OaiSubset'      => 'opus4',
            'Visible'        => 1,
            'VisiblePublish' => 1,
        ]);

        $this->assertNull($col->getId());
    }

    public function testFromArrayUsingExistingIdWithMismatchedRoleId()
    {
        $this->markTestIncomplete();
    }

    public function testFromArrayForNewCollectionUsingExistingRole()
    {
        $this->markTestIncomplete();
    }

    public function testFromArrayForExistingCollection()
    {
        $col1 = $this->object;

        $col1->setName('RootCol');
        $colId = $col1->store();

        $col = Collection::fromArray([
            'Id' => $colId,
        ]);

        $this->assertNotNull($col);
        $this->assertEquals($colId, $col->getId());
        $this->assertEquals('RootCol', $col->getName());
    }

    public function testIsRoot()
    {
        $col     = $this->object;
        $colRole = $this->roleFixture;

        $this->assertSame($col, $colRole->getRootCollection());

        $this->assertTrue($col->isRoot());

        $subCol = $col->addFirstChild();
        $subCol->setName('subcol');
        $subCol->store();

        $this->assertEquals($colRole->getId(), $subCol->getRoleId());

        $this->assertFalse($subCol->isRoot());
    }

    public function testGetRoleName()
    {
        $roleName = 'TestRole';

        $role = CollectionRole::new();
        $role->setName($roleName);
        $role->setOaiName('TestRoleOai');

        $root = $role->addRootCollection();
        $root->setName('RootCol');

        $roleId = $role->store();

        $role = CollectionRole::get($roleId);
        $this->assertEquals($roleName, $role->getName());

        $root = $role->getRootCollection();
        $this->assertEquals($roleId, $root->getRoleId());
        $this->assertEquals($roleName, $root->getRoleName());
    }

    public function testRemoveDocuments()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $col2 = $root->addLastChild();
        $col2->setName('col2');
        $col3 = $root->addLastChild();
        $col3->setName('col3');
        $role->store();

        $doc = Document::new();
        $doc->addCollection($col1);
        $doc->addCollection($col2);
        $doc->addCollection($col3);
        $docId        = $doc->store();
        $dateModified = $doc->getServerDateModified();

        $doc = Document::new();
        $doc->addCollection($col1);
        $docId2        = $doc->store();
        $dateModified2 = $doc->getServerDateModified();

        $doc = Document::new();
        $doc->addCollection($col1);
        $docId3        = $doc->store();
        $dateModified3 = $doc->getServerDateModified();

        $doc         = Document::get($docId);
        $collections = $doc->getCollection();
        $this->assertCount(3, $collections);

        sleep(2);

        $col1->removeDocuments([$docId, $docId3]);

        $doc         = Document::get($docId);
        $collections = $doc->getCollection();
        $this->assertCount(2, $collections);
        $newDateModified = $doc->getServerDateModified();
        $this->assertNotEquals($dateModified, $newDateModified);
        $this->assertEquals(-1, $dateModified->compare($newDateModified));

        $documents = $col1->getDocumentIds();
        $this->assertCount(1, $documents);
        $this->assertContains($docId2, $documents);

        $doc2             = Document::get($docId2);
        $newDateModified2 = $doc2->getServerDateModified();
        $this->assertEquals($dateModified2, $newDateModified2);

        $doc3             = Document::get($docId3);
        $newDateModified3 = $doc3->getServerDateModified();
        $this->assertNotEquals($dateModified3, $newDateModified3);
        $this->assertEquals(-1, $dateModified3->compare($newDateModified3));
    }

    public function testRemoveDocumentsDoNotUpdateLastModified()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $role->store();

        $doc = Document::new();
        $doc->addCollection($col1);
        $docId        = $doc->store();
        $dateModified = $doc->getServerDateModified();

        sleep(2);

        $col1->removeDocuments([$docId], false);

        $doc         = Document::get($docId);
        $collections = $doc->getCollection();
        $this->assertCount(0, $collections);
        $newDateModified = $doc->getServerDateModified();
        $this->assertEquals($dateModified, $newDateModified);
    }

    public function testRemoveDocumentsAll()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $role->store();

        $doc = Document::new();
        $doc->addCollection($col1);
        $docId        = $doc->store();
        $lastModified = $doc->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $docId2        = $doc2->store();
        $lastModified2 = $doc->getServerDateModified();

        $documents = $col1->getDocumentIds();
        $this->assertCount(2, $documents);

        sleep(2);

        $col1->removeDocuments();

        $documents = $col1->getDocumentIds();
        $this->assertCount(0, $documents);

        $doc  = Document::get($docId);
        $doc2 = Document::get($docId2);

        $this->assertNotEquals($lastModified, $doc->getServerDateModified());
        $this->assertNotEquals($lastModified2, $doc2->getServerDateModified());
    }

    public function testRemoveDocumentsAllDoNotUpdateLastModified()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $role->store();

        $doc = Document::new();
        $doc->addCollection($col1);
        $docId        = $doc->store();
        $lastModified = $doc->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $docId2        = $doc2->store();
        $lastModified2 = $doc2->getServerDateModified();

        $documents = $col1->getDocumentIds();
        $this->assertCount(2, $documents);

        sleep(2);

        $col1->removeDocuments(null, false);

        $documents = $col1->getDocumentIds();
        $this->assertCount(0, $documents);

        $doc  = Document::get($docId);
        $doc2 = Document::get($docId2);

        $this->assertEquals($lastModified, $doc->getServerDateModified());
        $this->assertEquals($lastModified2, $doc2->getServerDateModified());
    }

    public function testCopyDocuments()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $col2 = $root->addLastChild();
        $col2->setName('col2');
        $role->store();

        $doc1 = Document::new();
        $doc1->addCollection($col1);
        $docId1        = $doc1->store();
        $lastModified1 = $doc1->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $docId2        = $doc2->store();
        $lastModified2 = $doc2->getServerDateModified();

        $this->assertCount(2, $col1->getDocumentIds());
        $this->assertCount(0, $col2->getDocumentIds());

        sleep(2);

        $col1->copyDocuments($col2->getId());

        $doc1 = Document::get($docId1);
        $doc2 = Document::get($docId2);

        $this->assertCount(2, $col1->getDocumentIds());
        $this->assertCount(2, $col2->getDocumentIds());
        $this->assertEquals($col1->getDocumentIds(), $col2->getDocumentIds());
        $this->assertEquals(-1, $lastModified1->compare($doc1->getServerDateModified()));
        $this->assertEquals(-1, $lastModified2->compare($doc2->getServerDateModified()));
    }

    public function testCopyDocumentsDoNotUpdateLastModified()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $col2 = $root->addLastChild();
        $col2->setName('col2');
        $role->store();

        $doc1 = Document::new();
        $doc1->addCollection($col1);
        $docId1        = $doc1->store();
        $lastModified1 = $doc1->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $docId2        = $doc2->store();
        $lastModified2 = $doc2->getServerDateModified();

        $this->assertCount(2, $col1->getDocumentIds());
        $this->assertCount(0, $col2->getDocumentIds());

        sleep(2);

        $col1->copyDocuments($col2->getId(), false);

        $doc1 = Document::get($docId1);
        $doc2 = Document::get($docId2);

        $this->assertCount(2, $col1->getDocumentIds());
        $this->assertCount(2, $col2->getDocumentIds());
        $this->assertEquals($col1->getDocumentIds(), $col2->getDocumentIds());
        $this->assertEquals(0, $lastModified1->compare($doc1->getServerDateModified()));
        $this->assertEquals(0, $lastModified2->compare($doc2->getServerDateModified()));
    }

    public function testCopyDocumentsIgnoreDuplicates()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $col2 = $root->addLastChild();
        $col2->setName('col2');
        $role->store();

        $doc1 = Document::new();
        $doc1->addCollection($col1);
        $docId1        = $doc1->store();
        $lastModified1 = $doc1->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $doc2->addCollection($col2); // Document 2 ist already present in second collection
        $docId2        = $doc2->store();
        $lastModified2 = $doc2->getServerDateModified();

        $this->assertCount(2, $col1->getDocumentIds());
        $this->assertCount(1, $col2->getDocumentIds());

        sleep(2);

        $col1->copyDocuments($col2->getId());

        $doc1 = Document::get($docId1);
        $doc2 = Document::get($docId2);

        $this->assertCount(2, $col1->getDocumentIds());
        $this->assertCount(2, $col2->getDocumentIds());
        $this->assertEquals($col1->getDocumentIds(), $col2->getDocumentIds());
        $this->assertEquals(-1, $lastModified1->compare($doc1->getServerDateModified()));
        $this->assertEquals(0, $lastModified2->compare($doc2->getServerDateModified())); // was not copied
    }

    public function testMoveDocuments()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $col2 = $root->addLastChild();
        $col2->setName('col2');
        $role->store();

        $doc1 = Document::new();
        $doc1->addCollection($col1);
        $docId1        = $doc1->store();
        $lastModified1 = $doc1->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $docId2        = $doc2->store();
        $lastModified2 = $doc2->getServerDateModified();

        $documentIds = $col1->getDocumentIds();

        $this->assertCount(2, $documentIds);
        $this->assertCount(0, $col2->getDocumentIds());

        sleep(2);

        $col1->moveDocuments($col2->getId());

        $doc1 = Document::get($docId1);
        $doc2 = Document::get($docId2);

        $this->assertCount(0, $col1->getDocumentIds());
        $this->assertCount(2, $col2->getDocumentIds());
        $this->assertEquals($documentIds, $col2->getDocumentIds());
        $this->assertEquals(-1, $lastModified1->compare($doc1->getServerDateModified()));
        $this->assertEquals(-1, $lastModified2->compare($doc2->getServerDateModified()));
    }

    public function testMoveDocumentsDoNotUpdateLastModified()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $col2 = $root->addLastChild();
        $col2->setName('col2');
        $role->store();

        $doc1 = Document::new();
        $doc1->addCollection($col1);
        $docId1        = $doc1->store();
        $lastModified1 = $doc1->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $docId2        = $doc2->store();
        $lastModified2 = $doc2->getServerDateModified();

        $documentIds = $col1->getDocumentIds();

        $this->assertCount(2, $documentIds);
        $this->assertCount(0, $col2->getDocumentIds());

        sleep(2);

        $col1->moveDocuments($col2->getId(), false);

        $doc1 = Document::get($docId1);
        $doc2 = Document::get($docId2);

        $this->assertCount(0, $col1->getDocumentIds());
        $this->assertCount(2, $col2->getDocumentIds());
        $this->assertEquals($documentIds, $col2->getDocumentIds());
        $this->assertEquals($lastModified1, $doc1->getServerDateModified());
        $this->assertEquals($lastModified2, $doc2->getServerDateModified());
    }

    public function testMoveDocumentsIgnoreDuplicates()
    {
        $role = CollectionRole::new();
        $role->setName('TestRole');
        $role->setOaiName('TestRoleOai');
        $root = $role->addRootCollection();
        $col1 = $root->addLastChild();
        $col1->setName('col1');
        $col2 = $root->addLastChild();
        $col2->setName('col2');
        $role->store();

        $doc1 = Document::new();
        $doc1->addCollection($col1);
        $docId1        = $doc1->store();
        $lastModified1 = $doc1->getServerDateModified();

        $doc2 = Document::new();
        $doc2->addCollection($col1);
        $doc2->addCollection($col2);
        $docId2        = $doc2->store();
        $lastModified2 = $doc2->getServerDateModified();

        $documentIds = $col1->getDocumentIds();

        $this->assertCount(2, $documentIds);
        $this->assertCount(1, $col2->getDocumentIds());

        sleep(2);

        $col1->moveDocuments($col2->getId());

        $doc1 = Document::get($docId1);
        $doc2 = Document::get($docId2);

        $this->assertCount(0, $col1->getDocumentIds());
        $this->assertCount(2, $col2->getDocumentIds());
        $this->assertEquals($documentIds, $col2->getDocumentIds());
        $this->assertEquals(-1, $lastModified1->compare($doc1->getServerDateModified()));
        $this->assertEquals(-1, $lastModified2->compare($doc2->getServerDateModified()));
    }

    public function testGetVisibleReturnsBoolValue()
    {
        $root = $this->object;

        $this->assertIsBool($root->getVisible());
        $this->assertFalse($root->getVisible());

        $root->setVisible(true);

        $this->assertTrue($root->getVisible());
    }

    public function testGetVisiblePublishReturnsBoolValue()
    {
        $root = $this->object;

        $this->assertIsBool($root->getVisiblePublish());
        $this->assertFalse($root->getVisiblePublish());

        $root->setVisiblePublish(true);

        $this->assertTrue($root->getVisiblePublish());
    }
}
