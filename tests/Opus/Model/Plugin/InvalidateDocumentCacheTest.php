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

    protected $collection;
    protected $collectionRole;

    public function setUp() {
        parent::setUp();
        $this->collectionRole = new Opus_CollectionRole();
        $this->collectionRole->setName("role-name-" . rand());
        $this->collectionRole->setOaiName("role-oainame-" . rand());
        $this->collectionRole->setVisible(1);
        $this->collectionRole->setVisibleBrowsingStart(1);
        $this->collectionRole->store();
        try {
            $this->collectionRole->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }

        $this->collection = $this->collectionRole->addRootCollection();
        try {
            $this->collection->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }
        $this->collection->setName('dummy');
        $this->collection->store();
    }

    /**
     * @see {Opus_Model_Plugin_Interface::postStore}
     *
     * TODO split up into smaller tests
     */
    public function testPostStore() {
        $xmlCache = new Opus_Model_Xml_Cache();


        $docIds = array();
        $doc1 = new Opus_Document();
        $doc1->registerPlugin(new Opus_Document_Plugin_XmlCache);
        $docIds[] = $doc1->setType("article")
                ->setServerState('published');

//        $doc1->addLicence($licence);
        $doc1->addCollection($this->collection);
        $doc1->store();


        $this->assertTrue($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected valid cache entry for doc1 after creation id: ' . $doc1->getId());


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

        $domDocument = $xmlCache->get($doc2->getId(), 1);
        $this->assertTrue($domDocument->hasChildNodes(), 'cache entry consists of empty DOM document');        
        $this->assertEquals(1, count($domDocument->childNodes), 'unexpected number of child nodes');
        
        $xpath = new DOMXpath($domDocument);
        $elements = $xpath->query("/Opus/Opus_Document/ServerDateModified");
        $this->assertEquals(1, count($elements), 'unexpected number of matching elements');
        
        $attributes = $elements->item(0)->attributes;
        $this->assertNotNull($attributes, 'element ServerDateModified does not have any attributes');       
        
        $this->assertEquals($doc2->getServerDateModified()->getUnixTimestamp(), $attributes->getNamedItem('UnixTimestamp')->nodeValue, 'unexpected value for attribute UnixTimestamp');
        $this->assertEquals($doc2->getServerDateModified()->getYear(), $attributes->getNamedItem('Year')->nodeValue, 'unexpected value for attribute Year');
        $this->assertEquals($doc2->getServerDateModified()->getMonth(), $attributes->getNamedItem('Month')->nodeValue, 'unexpected value for attribute Month');
        $this->assertEquals($doc2->getServerDateModified()->getDay(), $attributes->getNamedItem('Day')->nodeValue, 'unexpected value for attribute Day');
        $this->assertEquals($doc2->getServerDateModified()->getMinute(), $attributes->getNamedItem('Minute')->nodeValue, 'unexpected value for attribute Minute');
        $this->assertEquals($doc2->getServerDateModified()->getSecond(), $attributes->getNamedItem('Second')->nodeValue, 'unexpected value for attribute Second');
        $this->assertEquals($doc2->getServerDateModified()->getTimezone(), $attributes->getNamedItem('Timezone')->nodeValue, 'unexpected value for attribute Timezone');        
        
        $this->assertTrue($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), "Expected valid cache entry for doc2 after creation. id: " . $doc2->getId());

        $doc3 = new Opus_Document();
        $doc3->registerPlugin(new Opus_Document_Plugin_XmlCache);

        $doc3Id = $docIds[] = $doc3->setType("preprint")
                ->setServerState('unpublished')
                ->store();
        $this->assertTrue($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected valid cache entry for doc3 after creation. id: ' . $doc3->getId());

        $doc4 = new Opus_Document();
        $doc4->registerPlugin(new Opus_Document_Plugin_XmlCache);
        $docIds[] = $doc4->setType("preprint")
                ->setServerState('unpublished');

//        $doc4->addLicence($licence);
        $doc4->addCollection($this->collection);
        $doc4->store();
        $this->assertTrue($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected valid cache entry for doc4 after creation id: ' . $doc4->getId());

        $plugin = new Opus_Model_Plugin_InvalidateDocumentCache();


        // test dependent model
        $title = $doc3->addTitleMain();
        // unregister plugin if registered
        try {
            $title->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }
        $title->setValue('Ein deutscher Titel');
        $title->setLanguage('deu');
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
        // unregister plugin if registered
        try {
            $author->unregisterPlugin('Opus_Model_Plugin_InvalidateDocumentCache');
        } catch (Opus_Model_Exception $ome) {
            
        }
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
        $plugin->postStore($this->collection);
//
        $this->assertFalse($xmlCache->hasValidEntry($doc1->getId(), 1, $doc1->getServerDateModified()), 'Expected cache entry to be deleted after collection');
        $this->assertFalse($xmlCache->hasValidEntry($doc2->getId(), 1, $doc2->getServerDateModified()), 'Expected cache entry to be deleted after collection');
        $this->assertFalse($xmlCache->hasValidEntry($doc3->getId(), 1, $doc3->getServerDateModified()), 'Expected cache entry to be deleted after collection');
        $this->assertFalse($xmlCache->hasValidEntry($doc4->getId(), 1, $doc4->getServerDateModified()), 'Expected cache entry to be deleted after collection');
    }

    /**
     * Make sure the ServerDateModified is updated
     */
    public function testSetServerDateModified() {

        $doc = new Opus_Document();
        $doc->registerPlugin(new Opus_Document_Plugin_XmlCache);
        $doc->setType("article")
                ->setServerState('published');

        $doc->addCollection($this->collection);
        $docId = $doc->store();
        $serverDateModified = $doc->getServerDateModified();

        $plugin = new Opus_Model_Plugin_InvalidateDocumentCache();
        sleep(1);
        $plugin->postStore($this->collection);
        $docReloaded = new Opus_Document($docId);
        $this->assertTrue($docReloaded->getServerDateModified()->getZendDate()->isLater($doc->getServerDateModified()->getZendDate()),
                'Expected serverDateModified to be updated.');
    }

    public function testPreDeleteHasNoEffectIfModelNotStored() {
        
        $doc = new Opus_Document();
        $doc->setType("article")
                ->setServerState('published');
        $docId = $doc->store();
        
        $licence = new Opus_Licence();
        $licence->setLinkLicence('http://licence');
        $licence->setNameLong('Non-Creative Uncommon');
        
        $doc->addLicence($licence);
        
        $serverDateModified = $doc->getServerDateModified();

        $plugin = new Opus_Model_Plugin_InvalidateDocumentCache();
        sleep(1);
        $plugin->preDelete($licence);
        $docReloaded = new Opus_Document($docId);
        $this->assertTrue(0 == ($docReloaded->getServerDateModified()->getZendDate()->compare($doc->getServerDateModified()->getZendDate())),
                'Expected serverDateModified to be updated.');
    }

    public function testCacheInvalidatedOnlyOnce()
    {
        $this->markTestIncomplete('TODO - no assertions (used for manual debugging)');
        $doc = new Opus_Document();

        $patent = new Opus_Patent();
        $patent->setApplication('Test Patent');
        $patent->setCountries('Germany');
        $patent->setNumber('1');

        $doc->addPatent($patent);

        $patent = new Opus_Patent();
        $patent->setApplication('Another Test Patent');
        $patent->setCountries('Germany');
        $patent->setNumber('2');

        $doc->addPatent($patent);

        $patent = new Opus_Patent();
        $patent->setApplication('Third Test Patent');
        $patent->setCountries('Germany');
        $patent->setNumber('3');

        $doc->addPatent($patent);

        $licence = new Opus_Licence();
        $licence->setLanguage('deu');
        $licence->setNameLong('Test Licence');
        $licence->setLinkLicence('http://long.org/licence');
        // $licenceId = $licence->store();

        $doc->addLicence($licence);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $licences = $doc->getLicence();

        $title = $doc->addTitleMain();
        $title->setValue('Document Title');
        $title->setLanguage('eng');

        $doc->store();
    }

    public function testIgnoreCollectionRoleDisplayBrowsing()
    {
        $doc = new Opus_Document();

        $colRole = new Opus_CollectionRole();
        $colRole->setName('TestCol');
        $colRole->setOaiName('TestColOai');
        $root = $colRole->addRootCollection();
        $colRole->store();

        $doc->addCollection($root);
        $doc->store();

        $lastModified = $doc->getServerDateModified()->getUnixTimestamp();

        sleep(2);

        $colRole->setDisplayBrowsing('Name,Number');
        $colRole->store();

        // need to read document from database again
        $doc = new Opus_Document($doc->getId());

        $this->assertEquals(
            $lastModified, $doc->getServerDateModified()->getUnixTimestamp(),
            'ServerDateModified of document should not have changed.'
        );
    }

}

