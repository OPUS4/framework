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
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Plugin creating and deleting xml cache entries.
 *
 * @category    Framework
 * @package     Opus_Document_Plugin
 * @uses        Opus_Model_Plugin_Abstract
 */
class Opus_Model_Plugin_InvalidateDocumentCacheTest extends TestCase {

    /**
     * @see {Opus_Model_Plugin_Interface::postStore}
     */
    public function testPostStore() {
        $xmlCache = new Opus_Model_Xml_Cache();

        $collectionRole = new Opus_CollectionRole();
        $collectionRole->setName("role-name-" . rand());
        $collectionRole->setOaiName("role-oainame-" . rand());
        $collectionRole->setVisible(1);
        $collectionRole->setVisibleBrowsingStart(1);
        $collectionRole->store();
        try {
            $collectionRole->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }

        $collection = $collectionRole->addRootCollection();
        try {
            $collection->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }
        $collection->setTheme('dummy');
        $collectionId = $collection->store();

        $docIds = array();
        $doc1 = new Opus_Document();
        $doc1->registerPlugin(new Opus_Document_Plugin_XmlCache);
        $docIds[] = $doc1->setType("article")
                ->setServerState('published');

//        $doc1->addLicence($licence);
        $doc1->addCollection($collection);
        $doc1->store();


        $this->assertTrue($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected valid cache entry for doc1 after creation id: '.$doc1->getId());


        $doc2 = new Opus_Document();
        $doc2->registerPlugin(new Opus_Document_Plugin_XmlCache);

        $docIds[] = $doc2->setType("article")
                ->setServerState('unpublished');

        $author = new Opus_Person();
        try {
            $author->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }

        $author->setFirstName('Karl');
        $author->setLastName('Tester');
        $author->setDateOfBirth('1857-11-26');
        $author->setPlaceOfBirth('Genf');
        $authorId = $author->store();

        $doc2->addPersonAuthor($author);
        $doc2->store();

        $this->assertTrue($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), "Expected valid cache entry for doc2 after creation. id: ".$doc2->getId());

        $doc3 = new Opus_Document();
        $doc3->registerPlugin(new Opus_Document_Plugin_XmlCache);

        $docIds[] = $doc3->setType("preprint")
                ->setServerState('unpublished')
                ->store();
        $this->assertTrue($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected valid cache entry for doc3 after creation. id: '.$doc3->getId());

        $doc4 = new Opus_Document();
        $doc4->registerPlugin(new Opus_Document_Plugin_XmlCache);
        $docIds[] = $doc4->setType("preprint")
                ->setServerState('unpublished');

//        $doc4->addLicence($licence);
        $doc4->addCollection($collection);
        $doc4->store();
        $this->assertTrue($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected valid cache entry for doc4 after creation id: '.$doc4->getId());

        $plugin = new Opus_Model_Plugin_InvalidateDocumentCache();


        // test dependent model
        $title = $doc3->addTitleMain();
        // unregister plugin if registered
        try {
            $title->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }
        $title->setValue('Ein deutscher Titel');
        $titleId = $title->store();

        $this->assertTrue($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected valid cache entry before title');
        $this->assertTrue($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), 'Expected valid cache entry before title');
        $this->assertTrue($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected valid cache entry before title');
        $this->assertTrue($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected valid cache entry before title');


        $plugin->postStore($title);

        $this->assertTrue($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected valid cache entry after title');
        $this->assertTrue($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), 'Expected valid cache entry after title');
        $this->assertFalse($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected cache entry to be deleted after title');
        $this->assertTrue($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected valid cache entry after title');


//        // test linked model
        //person
        $author = new Opus_Person($authorId);
        $author->setFirstName('Fritz');
        $author->store();

        $this->assertTrue($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected valid cache entry before person');
        $this->assertTrue($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), 'Expected valid cache entry before person');
        $this->assertFalse($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected cache entry to be deleted before person');
        $this->assertTrue($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected valid cache entry before person');

        $plugin->postStore($author);

        $this->assertTrue($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected valid cache entry after person');
        $this->assertFalse($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), 'Expected cache entry to be deleted after person');
        $this->assertFalse($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected cache entry to be deleted after person');
        $this->assertTrue($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected valid cache entry after person');
//        
        $plugin->postStore($collection);
//
        $this->assertFalse($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected cache entry to be deleted after collection');
        $this->assertFalse($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), 'Expected cache entry to be deleted after collection');
        $this->assertFalse($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected cache entry to be deleted after collection');
        $this->assertFalse($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected cache entry to be deleted after collection');
    }
    
    public function testPreDelete() {
        $this->markTestIncomplete('preDelete currently equal to postStore, so no specific testing required');
    }

}

