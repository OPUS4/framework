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
 */

namespace OpusTest;

use Opus\Collection;
use Opus\CollectionRole;
use Opus\Common\Document;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\NotFoundException;
use Opus\Model\Xml\Cache;
use OpusTest\Model\Plugin\AbstractPluginMock;
use OpusTest\TestAsset\TestCase;

use function count;
use function in_array;
use function is_array;
use function is_object;
use function rand;
use function round;
use function sleep;

/**
 * Test cases for class Opus\CollectionRole.
 */
class CollectionRoleTest extends TestCase
{
    /** @var CollectionRole */
    protected $object;

    /**
     * Static helper for creating random objects.
     *
     * @return CollectionRole
     */
    public static function createRandomObject()
    {
        $name    = "name-" . rand();
        $oaiName = "oainame-" . rand();

        // Object is not stored.
        $object = new CollectionRole();
        $object->setName($name);
        $object->setOaiName($oaiName);

        return $object;
    }

    /**
     * Sets up the fixture.  Method is called before each test.
     */
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(true, [
            'collections_roles',
            'collections',
            'documents',
            'link_documents_collections',
        ]);

        // Create first dummy role, just to make sure ID=1 exists.
        self::createRandomObject()->store();

        // Object is not stored.
        $this->object = self::createRandomObject();
    }

    /**
     * Tests delete method and if object is really deleted.
     */
    public function testDelete()
    {
        $this->assertTrue(
            $this->object->isNewRecord(),
            'CollectionRole isNewRecord check failed on new record.'
        );
        $this->object->delete();

        $roleId = $this->object->store();
        $this->assertNotNull(
            $roleId,
            'CollectionRole roleId return value check on stored record.'
        );
        $this->assertFalse(
            $this->object->isNewRecord(),
            'CollectionRole isNewRecord check failed on new record.'
        );

        $this->object->delete();

        $this->expectException(NotFoundException::class);
        new CollectionRole($roleId);
    }

    /**
     * Tests delete method and if object is really deleted.
     */
    public function testDeleteRoleWithDocuments()
    {
        $root       = $this->object->addRootCollection();
        $collection = $root->addLastChild();
        $this->object->store();

        $d = Document::new();
        $d->setServerState('published');
        $d->addCollection($collection);
        $d->store();

        $roleId = $this->object->getId();
        $role   = new CollectionRole($roleId);
        $role->delete();

        $this->expectException(NotFoundException::class);
        new CollectionRole($roleId);
    }

    /**
     * Tests store method and if object can be reloaded after storing.
     */
    public function testStore()
    {
        $this->assertTrue(
            $this->object->isNewRecord(),
            'CollectionRole isNewRecord check failed on new record.'
        );

        $roleId = $this->object->store();
        $this->assertNotNull(
            $roleId,
            'CollectionRole roleId return value check on stored record.'
        );
        $this->assertNotNull(
            $this->object->getId(),
            'CollectionRole getId check on stored record.'
        );
        $this->assertTrue(
            $roleId === $this->object->getId(),
            'CollectionRole->store return value check failed.'
        );
        $this->assertFalse(
            $this->object->isNewRecord(),
            'CollectionRole isNewRecord check failed on stored record.'
        );

        $role = new CollectionRole($this->object->getId());
        $this->assertTrue(
            is_object($role),
            'CollectionRole reloading failed.'
        );
        $this->assertNotNull($role->getId(), 'CollectionRole getId check on stored record.');
        $this->assertFalse(
            $role->isNewRecord(),
            'CollectionRole isNewRecord check failed on reloaded record.'
        );
        $this->assertFalse(
            $role->isModified(),
            'CollectionRole isModified check failed on reloaded record.'
        );

        $this->object->delete();
    }

    /**
     * Tests setting of name field.
     */
    public function testSetName()
    {
        $name = "set-name-" . rand();

        $this->object->setName($name);
        $this->assertTrue(
            $this->object->getName() === $name,
            'CollectionRole name check failed.'
        );

        $this->object->store();
        $role = new CollectionRole($this->object->getId());
        $this->assertTrue(
            $role->getName() === $name,
            'CollectionRole name check on reloaded object failed.'
        );

        $this->object->delete();
    }

    /**
     * Tests getDisplayName().
     */
    public function testGetDisplayName()
    {
        $this->assertNotNull(
            $this->object->getDisplayName(),
            'CollectionRole getDisplayName most NOT be null.'
        );
    }

    /**
     * @param CollectionRole $role
     */
    protected function prepateCollectionRole($role)
    {
        $role->setPosition(3);
        $role->setVisible(1);
        $role->setVisibleBrowsingStart(1);
        $role->setVisibleFrontdoor(1);
        $role->setVisibleOai(1);
        $role->setDisplayBrowsing('name');
        $role->setDisplayFrontdoor('number,name');
        $role->setIsClassification(0);
        $role->setAssignRoot(1);
        $role->setAssignLeavesOnly(0);
        $role->setHideEmptyCollections(1);
        $role->setLanguage('en');
    }

    /**
     * Tests toArray().
     */
    public function testToArrayNoRootCollection()
    {
        $role = $this->object;

        $this->prepateCollectionRole($role);

        $data = $role->toArray();

        $this->assertInternalType('array', $data);
        $this->assertCount(count($role->describe()), $data); // one entry for every field
        $this->assertEquals([
            'Name'                 => $role->getName(),
            'OaiName'              => $role->getOaiName(),
            'Position'             => 3,
            'Visible'              => 1,
            'VisibleBrowsingStart' => 1,
            'VisibleFrontdoor'     => 1,
            'VisibleOai'           => 1,
            'DisplayBrowsing'      => 'name',
            'DisplayFrontdoor'     => 'number,name',
            'IsClassification'     => 0,
            'AssignRoot'           => 1,
            'AssignLeavesOnly'     => 0,
            'RootCollection'       => null,
            'HideEmptyCollections' => 1,
            'Language'             => 'en',
        ], $data);
    }

    public function testToArrayWithRootCollection()
    {
        $role = $this->object;

        $this->prepateCollectionRole($role);

        $role->addRootCollection();

        $root = $role->getRootCollection();

        $role = new CollectionRole($role->store());

        $data = $role->toArray();

        $this->assertInternalType('array', $data);
        $this->assertCount(count($role->describe()), $data); // one entry for every field
        $this->assertEquals([
            'Name'                 => $role->getName(),
            'OaiName'              => $role->getOaiName(),
            'Position'             => '3',
            'Visible'              => '1',
            'VisibleBrowsingStart' => '1',
            'VisibleFrontdoor'     => '1',
            'VisibleOai'           => '1',
            'DisplayBrowsing'      => 'name',
            'DisplayFrontdoor'     => 'number,name',
            'IsClassification'     => '0',
            'AssignRoot'           => '1',
            'AssignLeavesOnly'     => '0',
            'RootCollection'       => [],
            'HideEmptyCollections' => 1,
            'Language'             => 'en',
        ], $data);
    }

    public function testToArrayWithRootCollectionAndChildren()
    {
        $role = $this->object;

        $this->prepateCollectionRole($role);

        $role->addRootCollection();
        $role->store();

        $root = $role->getRootCollection();
        $col  = $root->addLastChild();
        $col->setName('Col1');
        $col2 = $root->addLastChild();
        $col2->setName('Col2');

        // children of children will not be part of array output
        $col2a = $col2->addLastChild();
        $col2a->setName('Col2a');

        $root->store();

        // FIXME: We have to reload model to get correct results!
        $role = new CollectionRole($role->getId());

        $data = $role->toArray();

        $this->assertInternalType('array', $data);
        $this->assertEquals([
            'Name'                 => $role->getName(),
            'OaiName'              => $role->getOaiName(),
            'Position'             => '3',
            'Visible'              => '1',
            'VisibleBrowsingStart' => '1',
            'VisibleFrontdoor'     => '1',
            'VisibleOai'           => '1',
            'DisplayBrowsing'      => 'name',
            'DisplayFrontdoor'     => 'number,name',
            'IsClassification'     => '0',
            'AssignRoot'           => '1',
            'AssignLeavesOnly'     => '0',
            'RootCollection'       => [
                ['Id' => $col->getId(), 'Name' => $col->getName()],
                ['Id' => $col2->getId(), 'Name' => $col2->getName()],
            ],
            'HideEmptyCollections' => 1,
            'Language'             => 'en',
        ], $data);
    }

    /**
     * Tests fetchByName().
     */
    public function testFetchByName()
    {
        $this->assertTrue(
            $this->object->isNewRecord(),
            'CollectionRole isNewRecord check failed on new record.'
        );

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        // Expecting null for current name, since its not stored in db.
        $role = $collectionRoleRepository->fetchByName($this->object->getName());
        $this->assertNull(
            $role,
            'Role should not exists.'
        );

        // Expecting null for current name, since its not stored in db.
        $this->object->store();
        $role = $collectionRoleRepository->fetchByName($this->object->getName());
        $this->assertNotNull(
            $role,
            'Role should exist.'
        );
        $this->assertTrue(
            $role instanceof CollectionRole,
            'Returned object has wrong class.'
        );

        $this->object->delete();
    }

    public function testFetchByOaiName()
    {
        $role = $this->object;
        $role->setOaiName('test');
        $role->store();

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        $fetched = $collectionRoleRepository->fetchByOaiName('test');

        $this->assertInstanceOf(CollectionRole::class, $fetched);
    }

    /**
     * Tests fetchByOaiName().
     */
    public function testFetchByOaiNameNoMatch()
    {
        $role = $this->object;
        $role->store();

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        $fetched = $collectionRoleRepository->fetchByOaiName('test');

        $this->assertNull($fetched);
    }

    /**
     * Tests fetchAll().
     */
    public function testFetchAll()
    {
        $name = $this->object->getName();

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        // Check fetchAll works even if object is unstored.
        $roles         = $collectionRoleRepository->fetchAll();
        $rolesCountOld = count($roles);
        $this->assertTrue(is_array($roles), "Array return value expected.");

        // Check fetchAll works after storing *and* contains the object.
        $this->object->store();
        $roles = $collectionRoleRepository->fetchAll();
        $this->assertTrue(is_array($roles), "Array return value expected.");
        $this->assertTrue(count($roles) > $rolesCountOld, "Increasing count expected.");

        $seen = false;
        foreach ($roles as $role) {
            if ($role->getId() === $this->object->getId()) {
                $seen = true;
                $this->assertTrue($role->getName() === $name, "CollectionRole has wrong name.");
            }
        }

        $this->assertTrue($seen, "CollectionRole is not visible in fetchAll.");
    }

    public function testGetOaiSetNames()
    {
        // List of set names on unstored object
        $setnames = $this->object->getOaiSetNames();
        $this->assertTrue(is_array($setnames), "Expected OaiSetNames array.");
        $this->assertEmpty($setnames);

        // List of set names on stored object
        $this->object->store();
        $setnames = $this->object->getOaiSetNames();
        $this->assertTrue(is_array($setnames), "Expected OaiSetNames array.");
        $this->assertEmpty($setnames);
    }

    public function testGetOaiSetNamesRootCollectionWithDocument()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();
        $root->setOaiSubset('test');
        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($root);
        $doc->store();

        $setnames = $role->getOaiSetNames();

        $this->assertInternalType('array', $setnames);
        $this->assertCount(1, $setnames);
        $this->assertEquals('test', $setnames[0]['oai_subset']);
    }

    public function testGetOaiSetNamesMultipleSets()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();

        $col1 = $root->addLastChild();
        $col1->setOaiSubset('col1');

        $col2 = $root->addLastChild();
        $col2->setOaiSubset('col2');

        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($col1);
        $doc->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($col2);
        $doc->store();

        $setnames = $role->getOaiSetNames();

        $this->assertInternalType('array', $setnames);
        $this->assertCount(2, $setnames);
        $this->assertEquals('col1', $setnames[0]['oai_subset']);
        $this->assertEquals(1, $setnames[0]['count']);
        $this->assertEquals('col2', $setnames[1]['oai_subset']);
        $this->assertEquals(1, $setnames[1]['count']);
    }

    public function testGetOaiSetNamesMultipleDocs()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();

        $col = $root->addLastChild();
        $col->setOaiSubset('test');

        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($col);
        $doc->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($col);
        $doc->store();

        $setnames = $role->getOaiSetNames();

        $this->assertInternalType('array', $setnames);
        $this->assertCount(1, $setnames);
        $this->assertEquals('test', $setnames[0]['oai_subset']);
        $this->assertEquals(2, $setnames[0]['count']);
    }

    public function testGetOaiSetNamesCollectionWithoutOaiSubset()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();
        $col  = $root->addLastChild();
        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($col);
        $doc->store();

        $setnames = $role->getOaiSetNames();

        $this->assertInternalType('array', $setnames);
        $this->assertCount(0, $setnames);
    }

    public function testGetOaiSetNamesCollectionNotVisible()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();
        $col  = $root->addLastChild();
        $col->setOaiSubset('test');
        $col->setVisible(0);
        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($col);
        $doc->store();

        $setnames = $role->getOaiSetNames();

        $this->assertInternalType('array', $setnames);
        $this->assertCount(0, $setnames);
    }

    public function testGetOaiSetNamesDoNotIncludeEmptyCollections()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();

        $colEmpty = $root->addLastChild();
        $colEmpty->setOaiSubset('empty');

        $colDocs = $root->addLastChild();
        $colDocs->setOaiSubset('docs');

        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($colDocs);
        $doc->store();

        $setnames = $role->getOaiSetNames();

        $this->assertInternalType('array', $setnames);
        $this->assertCount(1, $setnames);
        $this->assertEquals('docs', $setnames[0]['oai_subset']);
    }

    public function testFetchAllOaiSetNames()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();

        $colEmpty = $root->addLastChild();
        $colEmpty->setOaiSubset('empty');

        $colDocs = $root->addLastChild();
        $colDocs->setOaiSubset('docs');

        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($colDocs);
        $doc->store();

        $setnames = $role->getAllOaiSetNames();

        $this->assertInternalType('array', $setnames);
        $this->assertCount(2, $setnames);
        $this->assertEquals('empty', $setnames[0]['oai_subset']);
        $this->assertEquals('docs', $setnames[1]['oai_subset']);
    }

    /**
     * @todo Implement testExistsDocumentIdsInSet().
     */
    public function testExistsDocumentIdsInSet()
    {
        $role = $this->object;
        $role->store();

        $root       = $role->addRootCollection();
        $collection = $root->addFirstChild();
        $collection->setOaiSubset('foo');
        $role->store();

        $oaiSet = $role->getOaiName() . ':' . $collection->getOaiSubset();

        $this->assertFalse($role->existsDocumentIdsInSet($oaiSet));

        $d = Document::new();
        $d->setServerState('published');
        $d->addCollection($collection);
        $d->store();

        $this->assertTrue($role->existsDocumentIdsInSet($oaiSet));
    }

    /**
     * Tests getDocumentIdsInSet().  Currently only tests empty set.
     */
    public function testGetDocumentIdsInSet()
    {
        $this->object->store();

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        $oaiSetName = $this->object->getOaiName();
        $sets       = $collectionRoleRepository->getDocumentIdsInSet("$oaiSetName:foo");

        $this->assertTrue(is_array($sets), "Expected array return value.");
        $this->assertTrue(count($sets) === 0, "Expected empty array.");
    }

    /**
     * Check if setting position works
     */
    public function testPosition()
    {
        $this->object->store();

        // Populate database with dummy objects.
        $iMax = rand(5, 10);
        for ($i = 0; $i < $iMax; $i++) {
            $object = self::createRandomObject();
            $object->store();
        }

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        // Check if setPosition works properly.
        $numRoles       = count($collectionRoleRepository->fetchAll());
        $checkPositions = [1, $numRoles, round((1 + $numRoles) / 2), 1];

        foreach ($checkPositions as $position) {
            $this->object->setPosition($position);
            $this->object->store();

            // Reload object, otherwise the result will be trivial.
            $role = new CollectionRole($this->object->getId());
            $this->assertTrue(
                $role->getPosition() === "$position",
                'CollectionRole position check failed.'
            );
        }
    }

    /**
     * Initializes the current element with random data and checks if it will
     * be stored correctly.
     */
    public function testRoleData()
    {
        // Initialize with data.
        $role = $this->object;

        $role->setDisplayBrowsing('Number, Name');
        $role->setDisplayFrontdoor('Name');

        $role->setVisible(1);
        $role->setVisibleBrowsingStart(1);
        $role->setVisibleFrontdoor(0);
        $role->setVisibleOai(1);

        $role->setPosition(1);

        $role->store();

        $this->assertNotNull($role->getId(), 'CollectionRole storing failed: should have an Id.');

        // Restore object, validate.
        $roleId = $role->getId();
        $role   = new CollectionRole($roleId);

        $this->assertNotNull($role->getName(), 'CollectionRole name check failed.');
        $this->assertNotNull($role->getOaiName(), 'CollectionRole oai_name check failed.');

        $this->assertTrue($role->getDisplayBrowsing() === 'Number, Name', 'CollectionRole display_browsing check failed.');
        $this->assertTrue($role->getDisplayFrontdoor() === 'Name', 'CollectionRole display_frontdoor check failed.');

        $this->assertTrue($role->getVisible() === '1', 'CollectionRole visible check failed.');
        $this->assertTrue($role->getVisibleBrowsingStart() === '1', 'CollectionRole visible_browsing_start check failed.');
        $this->assertTrue($role->getVisibleFrontdoor() === '0', 'CollectionRole visible_frontdoor check failed.');
        $this->assertTrue($role->getVisibleOai() === '1', 'CollectionRole visible_oai check failed.');

        $this->assertTrue($role->getPosition() === '1', 'CollectionRole position check failed.');
    }

    /**
     * Test if first storing role, then adding root collection and finally
     * storing root works.
     */
    public function testAddRootCollectionStoringRoleStoringChild()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();
        $root->store();

        $root = new Collection($root->getId());
        $this->assertEquals(
            $root->getRoleId(),
            $role->getId(),
            'Root->getRoleId must be equal Role->getId'
        );

        $role    = new CollectionRole($role->getId());
        $rootNew = $role->getRootCollection();
        $this->assertEquals(
            $rootNew->getId(),
            $root->getId(),
            'Root->getId must be equal Root->Reload->getId'
        );
    }

    /**
     * Test if first creating role, then adding root collection and finally
     * storing role works.
     */
    public function testAddRootCollectionStoringRoleOnly()
    {
        $role = $this->object;
        $root = $role->addRootCollection();
        $role->store();

        $root = new Collection($root->getId());
        $this->assertEquals(
            $root->getRoleId(),
            $role->getId(),
            'Root->getRoleId must be equal Role->getId'
        );

        $role    = new CollectionRole($role->getId());
        $rootNew = $role->getRootCollection();
        $this->assertEquals(
            $rootNew->getId(),
            $root->getId(),
            'Root->getId must be equal Root->Reload->getId'
        );
    }

    /**
     * Test adding externally created root collection and finally storing role.
     */
    public function testAddNewUnstoredRootCollectionStoringRole()
    {
        $role = $this->object;
        $root = new Collection();
        $role->addRootCollection($root);
        $role->store();

        $root = new Collection($root->getId());
        $this->assertEquals(
            $root->getRoleId(),
            $role->getId(),
            'Root->getRoleId must be equal Role->getId'
        );

        $role    = new CollectionRole($role->getId());
        $rootNew = $role->getRootCollection();
        $this->assertEquals(
            $rootNew->getId(),
            $root->getId(),
            'Root->getId must be equal Root->Reload->getId'
        );
    }

    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache()
    {
        $root       = $this->object->addRootCollection();
        $collection = $root->addLastChild();
        $this->object->store();

        $d = Document::new();
        $d->setServerState('published');
        $d->addCollection($collection);
        $docId = $d->store();

        $xmlCache = new Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $this->object->setDisplayFrontdoor(true);
        $this->object->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }

    /**
     * Regression Test for OPUSVIER-2935
     */
    public function testInvalidateDocumentCacheOnDelete()
    {
        $root       = $this->object->addRootCollection();
        $collection = $root->addLastChild();
        $this->object->store();

        $d = Document::new();
        $d->setServerState('published');
        $d->addCollection($collection);
        $docId = $d->store();

        $serverDateModifiedBeforeDelete = $d->getServerDateModified();

        $xmlCache = new Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');

        sleep(1);

        $this->object->delete();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');

        $d                       = Document::get($docId);
        $serverDateModifiedAfter = $d->getServerDateModified();
        $this->assertTrue($serverDateModifiedAfter->getUnixTimestamp() > $serverDateModifiedBeforeDelete->getUnixTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');
    }

    /**
     * Regression-Test for OPUSVIER-2937
     *
     * Hook gets only called if object has been stored in database.
     */
    public function testPreDeletePluginHookGetsCalled()
    {
        $pluginMock = new AbstractPluginMock();

        $this->assertTrue(empty($pluginMock->calledHooks), 'expected empty array');

        $collectionRole = new CollectionRole();
        $collectionRole->setName('test');
        $collectionRole->setOaiName('oainame');
        $collectionRole->registerPlugin($pluginMock);
        $collectionRole->store();
        $collectionRole->delete();

        $this->assertTrue(
            in_array('OpusTest\Model\Plugin\AbstractPluginMock::preDelete', $pluginMock->calledHooks),
            'expected call to preDelete hook'
        );
    }

    public function testPreDeletePluginHookNotCalledIfObjectNotStored()
    {
        $pluginMock = new AbstractPluginMock();

        $this->assertTrue(empty($pluginMock->calledHooks), 'expected empty array');

        $collectionRole = new CollectionRole();
        $collectionRole->registerPlugin($pluginMock);
        $collectionRole->delete();

        $this->assertFalse(in_array(
            'OpusTest\Model\Plugin\AbstractPluginMock::preDelete',
            $pluginMock->calledHooks
        ), 'expected no call to preDelete hook');
    }

    public function testFetchAllOaiEnabledRoles()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();
        $root->setOaiSubset('test');
        $role->store();

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        $roles = $collectionRoleRepository->fetchAllOaiEnabledRoles();

        $this->assertInternalType('array', $roles);
        $this->assertCount(0, $roles);

        $role->setOaiName('role');
        $role->setVisibleOai(true);
        $role->store();

        $roles = $collectionRoleRepository->fetchAllOaiEnabledRoles();

        $this->assertInternalType('array', $roles);
        $this->assertCount(0, $roles);

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($root);
        $doc->store();

        $roles = $collectionRoleRepository->fetchAllOaiEnabledRoles();

        $this->assertInternalType('array', $roles);
        $this->assertCount(1, $roles);
        $this->assertEquals('role', $roles[0]['oai_name']);
    }

    public function testFetchAllOaiEnabledRolesNotVisible()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();

        $role->setOaiName('role');
        $role->setVisibleOai(true);
        $role->setVisible(0);
        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($root);
        $doc->store();

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        $roles = $collectionRoleRepository->fetchAllOaiEnabledRoles();

        $this->assertInternalType('array', $roles);
        $this->assertEmpty($roles);
    }

    public function testFetchAllOaiEnabledRolesWithCollection()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();

        $col = $root->addLastChild();
        $col->setOaiSubset('test');

        $role->setOaiName('role');
        $role->setVisibleOai(true);
        $role->setVisible(1);
        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($col);
        $doc->store();

        $roles = $this->getCollectionRoleRepository()->fetchAllOaiEnabledRoles();

        $this->assertInternalType('array', $roles);
        $this->assertCount(1, $roles);
    }

    /**
     * Return roles as enabled that have documents associated with root collection without oaisubset value.
     */
    public function testFetchAllOaiEnabledRolesForRootCollection()
    {
        $role = $this->object;
        $role->store();

        $role->setVisibleOai(1);
        $root = $role->addRootCollection();
        $role->store();

        $doc = Document::new();
        $doc->setServerState('published');
        $doc->addCollection($root);
        $doc->store();

        $roles = $this->getCollectionRoleRepository()->fetchAllOaiEnabledRoles();

        $this->assertInternalType('array', $roles);
        $this->assertCount(1, $roles);
    }

    public function testGetCollectionByOaiSubset()
    {
        $role = $this->object;
        $role->store();

        $root = $role->addRootCollection();

        $col = new Collection();
        $col->setName('test-collection');
        $col->setOaiSubset('open_access');

        $root->addFirstChild($col);
        $role->store();

        $result = $role->getCollectionByOaiSubset('open_access');

        $this->assertInstanceOf(Collection::class, $result);
        $this->assertEquals($col->getId(), $result->getId());
    }

    public function testGetCollectionByOaiSubsetNoMatch()
    {
        $role = $this->object;
        $role->store();

        $result = $role->getCollectionByOaiSubset('open_access');

        $this->assertNull($result);
    }

    public function testGetLastPosition()
    {
        $role = $this->object;
        $role->store();

        $collectionRoleRepository = $this->getCollectionRoleRepository();

        $result = $collectionRoleRepository->getLastPosition();

        $this->assertNotNull($result);
        $this->assertInternalType('int', $result);
        $this->assertEquals(0, $result);

        $role->setPosition(10);
        $role->store();

        $result = $collectionRoleRepository->getLastPosition();

        $this->assertEquals(10, $result);

        $role2 = new CollectionRole();
        $role2->setName('TestCol2');
        $role2->setOaiName('col2oai');
        $role2->setPosition(20);
        $role2->store();

        $result = $collectionRoleRepository->getLastPosition();

        $this->assertEquals(20, $result);
    }

    public function testStoreIsClassification()
    {
        $role   = $this->object;
        $roleId = $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(0, $role->getIsClassification());

        $role->setIsClassification(1);
        $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(1, $role->getIsClassification());

        $role->setIsClassification(0);
        $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(0, $role->getIsClassification());
    }

    public function testStoreAssignRoot()
    {
        $role   = $this->object;
        $roleId = $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(0, $role->getAssignRoot());

        $role->setAssignRoot(1);
        $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(1, $role->getAssignRoot());

        $role->setAssignRoot(0);
        $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(0, $role->getAssignRoot());
    }

    public function testStoreAssignLeavesOnly()
    {
        $role   = $this->object;
        $roleId = $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(0, $role->getAssignLeavesOnly());

        $role->setAssignLeavesOnly(1);
        $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(1, $role->getAssignLeavesOnly());

        $role->setAssignLeavesOnly(0);
        $role->store();

        $role = new CollectionRole($roleId);
        $this->assertEquals(0, $role->getAssignLeavesOnly());
    }

    /**
     * Eine Änderung von hideEmptyCollections auf einer CollectionRole soll
     * keine Auswirkungen auf den Wert von serverDateModified von mit der
     * CollectionRole verknüpften Dokumente haben.
     *
     * @throws ModelException
     */
    public function testChangeOfHideEmptyCollectionsDoesNotAffectDocuments()
    {
        $collRole = new CollectionRole();
        $collRole->setName('Test');
        $collRole->setOaiName('Test');
        $collRole->setRootCollection(new Collection());
        $collRoleId = $collRole->store();

        $collRole = new CollectionRole($collRoleId);
        $doc      = Document::new();
        $doc->addCollection($collRole->getRootCollection());
        $docId = $doc->store();

        $doc = Document::get($docId);

        $serverDateModified = $doc->getServerDateModified();

        $collRole->setHideEmptyCollections(1);
        $collRole->store();

        $doc = Document::get($docId);
        $this->assertEquals($doc->getServerDateModified(), $serverDateModified);
    }

    /**
     * Validierung von gültigen CollectionRole Namen (OPUSVIER-4022)
     */
    public function testValidCollectionRoleName()
    {
        $collRole = new CollectionRole();
        $collRole->setName('abcABC012_3-4_5-6');
        $collRole->setOaiName('foo');
        $collRoleId = $collRole->store();

        $this->assertTrue($collRoleId > 0);
    }

    /**
     * @return string[][]
     */
    public function invalidCollectionRoleNameDataProvider()
    {
        return [
            ['a b '],
            ['a,b'],
            ['ä'],
            ['a:b'],
            ['a;b'],
            ['_a'],
            ['-a'],
            ['0a'],
            ['0_1'],
            ['0-1'],
        ];
    }

    /**
     * Validierung von ungültigen Namen für CollectionRoles (OPUSVIER-4022)
     *
     * @param string $invalidName
     * @dataProvider invalidCollectionRoleNameDataProvider
     */
    public function testInvalidCollectionRoleName($invalidName)
    {
        $collRole = new CollectionRole();
        $collRole->setName($invalidName);
        $collRole->setOaiName('foo');

        $this->expectException(ModelException::class, 'invalid data');

        $collRole->store();
    }

    /**
     * @return CollectionRole
     */
    protected function getCollectionRoleRepository()
    {
        return new CollectionRole();
    }
}
