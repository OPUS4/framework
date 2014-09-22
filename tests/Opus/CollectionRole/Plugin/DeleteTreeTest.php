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
 * @package     Opus_CollectionRole
 * @author      Edouard Simon (edouard.simon@zib.de)
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * 
 */
class Opus_CollectionRole_Plugin_DeleteTreeTest extends TestCase {
    
    public function testPreDelete() {
        $collectionRole = new Opus_CollectionRole();
        $collectionRole->setName('testRole');
        $collectionRole->setOaiName('testRole');
        $collectionRole->setVisible(1);
        $collectionRole->setVisibleBrowsingStart(1);
        $collectionRole->store();

        $root = $collectionRole->addRootCollection();
        $collection = $root->addLastChild();
        $collectionRole->store();


        $d = new Opus_Document();
        $d->setServerState('published');
        $d->addCollection($collection);
        $docId = $d->store();

        $serverDateModifiedBeforeDelete = $d->getServerDateModified();

        $xmlCache = new Opus_Model_Xml_Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        
        $plugin = new Opus_CollectionRole_Plugin_DeleteTree();
        
        sleep(1);
        
        $plugin->preDelete($collectionRole);
        
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');

        $d = new Opus_Document($docId);
        $serverDateModifiedAfter = $d->getServerDateModified();
        $this->assertTrue(
            $serverDateModifiedAfter->getUnixTimestamp() > $serverDateModifiedBeforeDelete->getUnixTimestamp(),
            'Expected document server_date_modfied to be changed after deletion of collection');
    }

    /**
     * Testet, daß die richtigen Collections gelöscht werden und auch nur verknüpfte Dokumente modifiziert werden.
     */
    public function testDeletingOfCollectionRoleUsesCorrectIdForRootCollection() {
        $collectionRole = new Opus_CollectionRole();
        $collectionRole->setName('ColRole1Name');
        $collectionRole->setOaiName('ColRole1OaiName');
        $colRole1Id = $collectionRole->store(); // ID = 1

        $this->assertEquals('1', $colRole1Id, "Unexpected ID");

        $root = $collectionRole->addRootCollection();
        $collection = $root->addLastChild();
        $collectionRole->store();

        $collectionId = $collection->getId();

        $doc = new Opus_Document();

        $doc->addCollection($collection); // associate document with Collection 2 of CollectionRole 1

        $docId = $doc->store();

        $serverDateModified = $doc->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $this->assertEquals('2', $collectionId);

        $collectionRole = new Opus_CollectionRole();
        $collectionRole->setName('ColRole2Name');
        $collectionRole->setOaiName('ColRole2OaiName');
        $colRole2Id = $collectionRole->store();

        $this->assertEquals('2', $colRole2Id);

        $collectionRole->delete(); // deleting CollectionRole 2 should not affect document

        // make sure collection 2 still exists
        $collection = new Opus_Collection($collectionId);

        // make sure document ServerDateModified wasn't changed
        $doc = new Opus_Document($docId);

        $this->assertEquals($serverDateModified, $doc->getServerDateModified()->getUnixTimestamp(),
            "ServerDateModified of unassigned document was changed.");
    }

}
