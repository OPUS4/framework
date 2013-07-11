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
 * @author      Edouard Simon (edouard.simon@zib.de)
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * 
 */
class Opus_Collection_Plugin_DeleteSubTreeTest extends TestCase {

    public function testPreDelete() {

        $collectionRole = new Opus_CollectionRole();
        $collectionRole->setName('testRole');
        $collectionRole->setOaiName('testRole');
        $collectionRole->setVisible(1);
        $collectionRole->setVisibleBrowsingStart(1);
        $collectionRole->store();
        $collection = $collectionRole->addRootCollection();

        $childCollection = $collection->addFirstChild();
        $grandChildCollection = $childCollection->addFirstChild();
        $child2Collection = $collection->addLastChild();

        $collectionId = $collection->store();
        $childCollectionId = $childCollection->getId();
        $child2CollectionId = $child2Collection->getId();
        $grandChildCollectionId = $grandChildCollection->getId();

        $doc1 = new Opus_Document();
        $doc1->addCollection($childCollection);
        $docId1 = $doc1->store();
        $doc1ServerDateModified = $doc1->getServerDateModified();

        $doc2 = new Opus_Document();
        $doc2->addCollection($grandChildCollection);
        $docId2 = $doc2->store();
        $doc2ServerDateModified = $doc2->getServerDateModified();

        $doc3 = new Opus_Document();
        $doc3->addCollection($child2Collection);
        $docId3 = $doc3->store();
        $doc3ServerDateModified = $doc3->getServerDateModified();

        $collectionReloaded = new Opus_Collection($collectionId);

        $childrenBefore = $collection->getChildren();
        $this->assertEquals(2, count($childrenBefore), 'Expected two children');

        $plugin = new Opus_Collection_Plugin_DeleteSubTree();

        sleep(1);
        
        $plugin->preDelete($collection);

        $childrenAfter = $collectionReloaded->getChildren();
        $this->assertEquals(0, count($childrenAfter), 'Expected no child');

        try {
            new Opus_Collection($collectionId);
            $this->fail('expected collection to be deleted');
        } catch (Opus_Model_NotFoundException $e) {
            
        }
        try {
            new Opus_Collection($childCollectionId);
            $this->fail('expected collection to be deleted');
        } catch (Opus_Model_NotFoundException $e) {
            
        }
        try {
            new Opus_Collection($child2CollectionId);
            $this->fail('expected collection to be deleted');
        } catch (Opus_Model_NotFoundException $e) {
            
        }
        try {
            new Opus_Collection($grandChildCollectionId);
            $this->fail('expected collection to be deleted');
        } catch (Opus_Model_NotFoundException $e) {
            
        }

        $doc1Reloaded = new Opus_Document($docId1);
        $this->assertTrue($doc1Reloaded->getServerDateModified()->getUnixTimestamp() > $doc1ServerDateModified->getUnixTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');

        $doc2Reloaded = new Opus_Document($docId2);
        $this->assertTrue($doc2Reloaded->getServerDateModified()->getUnixTimestamp() > $doc2ServerDateModified->getUnixTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');

        $doc3Reloaded = new Opus_Document($docId3);
        $this->assertTrue($doc3Reloaded->getServerDateModified()->getUnixTimestamp() > $doc3ServerDateModified->getUnixTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');
    }

}
