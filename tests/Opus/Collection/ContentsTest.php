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
 * @category	Tests
 * @package		Opus_Collections
 * @author     	Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright  	Copyright (c) 2008, OPUS 4 development team
 * @license    	http://www.gnu.org/licenses/gpl.html General Public License
 * @version    	$Id$
 */

/**
 * Test cases for class Opus_Collection_Structure.
 *
 * @category Tests
 * @package  Opus_Collection
 */
class Opus_Collection_ContentsTest extends PHPUnit_Framework_TestCase {

    /**
     * SetUp database 
     *
     * @return void
     */
    public function setUp() {

        $adapter = Zend_Db_Table::getDefaultAdapter();
        $adapter->query("DROP TABLE IF EXISTS collections_contents_7081;");
        $adapter->query('CREATE TABLE collections_contents_7081 (
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            `collections_language` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT "ger",
            `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
            `number` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
            PRIMARY KEY ( `collections_id` , `collections_language` ) 
            ) ENGINE = InnoDB');
        $adapter->query("INSERT INTO `collections_contents_7081` 
        (`collections_id`, `collections_language`, `name`, `number`) 
        VALUES (1, 'ger', 'Tiere', '000'),
        (2, 'ger', 'Pflanzen', '000'),
        (3, 'eng', 'dogs', '000'),
        (4, 'ger', 'Insekten', '000'),
        (5, 'fra', 'boef', '000')
        ;");
        $adapter->query("TRUNCATE institutes_contents;");
        $adapter->query("INSERT INTO `institutes_contents` 
        (`institutes_id`, `institutes_language`, `type`, `name`) 
        VALUES (0, 'ger', 'Fakultät', 'Fakultät A'),
        (1, 'ger', 'Fakultät', 'Fakultät A1'),
        (2, 'ger', 'Fakultät', 'Fakultät A2'),
        (3, 'ger', 'Fakultät', 'Fakultät A2a'),
        (4, 'ger', 'Fakultät', 'Fakultät A3')
        ;");
        
    }

    public function tearDown() {
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $adapter->query("DROP TABLE IF EXISTS collections_contents_7081;");
        $adapter->query("TRUNCATE institutes_contents;");
    }

    public function validConstructorIDDataProvider() {
        return array(
            array('institute'),
            array(7081),
        );
    }
    
    public function invalidConstructorIDDataProvider() {
        return array(
            array('institut'),
            array(-5),
            array(0),
            array(-5.25),
            array(27.3),
            array(array(7)),
        );
    }
    
    /**
     *
     * @dataProvider validConstructorIDDataProvider
     *
     */
    public function testCollectionContentsConstructor($ID) {
        $occ = new Opus_Collection_Contents($ID);
        $this->assertTrue(isset($occ->collectionContents), 'No collectionContents array built by constructor.');
        $this->assertTrue(isset($occ->collections_contents_info), 'No collections_contents_info array built by constructor.');
    }
    
    /**
     *
     * @dataProvider invalidConstructorIDDataProvider
     *
     */
    public function testCollectionContentsConstructorInvalidArg($ID) {
        $this->setExpectedException('InvalidArgumentException');
        $ocs = new Opus_Collection_Contents($ID);
    }
    
    public function validCreateDataProvider() {
        return array(
            array('institute', array('ger', 'eng')),
            array(7081, array('fra')),
            array(7081, array('ger', 'eng', 'aar', 'abk')),
            array(7081, array()),
        );
    }
    
    /**
     *
     * @dataProvider validCreateDataProvider
     *
     */
    public function testCollectionContentsCreate($ID, $languageArray) {
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->create();
        $this->assertType('array', $occ->collectionContents, 'collectionContents array not created properly');
        $occ->create($languageArray);
        foreach ($languageArray as $languageCode) {
            $this->assertArrayHasKey($languageCode, $occ->collectionContents, 'collectionContents array does not contain expected language '.$languageCode);
        }
    }
    
    public function invalidCreateDataProvider() {
        return array(
            array('institute', array('gr', 'en')),
            array(7081, array(0, 2)),
            array(7081, array('germ', 'engl', 'aard', 'abku')),
        );
    }

    /**
     *
     * @dataProvider invalidCreateDataProvider
     *
     */
    public function testCollectionContentsCreateInvArg($ID, $languageArray) {
        $this->setExpectedException('InvalidArgumentException');
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->create($languageArray);
    }
    
    public function validUpdateDataProvider() {
        return array(
            array('institute', 
                    array('ger', 'eng'), 
                    array('eng' => array('type' => 'Schall und Rauch', 
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de'),
                          'ger' => array('type' => 'Sound and Fog', 
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
            array(7081, array('fra'), 
                    array('fra' => array('name' => 'Schall und Rauch', 'number' => '000')
                          )),
            array(7081, array('ger', 'eng', 'aar', 'abk'), 
                    array('aar' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'ger' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'abk' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'eng' => array('name' => 'Schall und Rauch', 'number' => '000')
                          )),
        );
    }
    
    /**
     *
     * @dataProvider validUpdateDataProvider
     *
     */
    public function testCollectionContentsUpdate($ID, $languageArray, $contentArray) {
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->create($languageArray);
        foreach ($contentArray as $language => $record) {
            $occ->update(array($language => $record));   
        }
        foreach ($contentArray as $languageCode => $contentRecord) {
            $this->assertArrayHasKey($languageCode, $occ->collectionContents, 'collectionContents array does not contain expected language '.$languageCode);
            foreach ($contentRecord as $contentType => $contentValue) {
                $this->assertArrayHasKey($contentType, $occ->collectionContents[$languageCode], 'collectionContents array does not contain expected contentType '.$contentType);
            }
        }
    }
    
    public function invalidUpdateDataProvider() {
        return array(
            array('institute', 
                    array('ger', 'eng'), 
                    array('eng' => array('institutes_id' => 5, 
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
            array('institute', 
                    array('ger', 'eng'), 
                    array('eng' => array('institutes_language' => 'ger', 
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
            array('institute', 
                    array('ger', 'eng'), 
                    array('eng' => array('typ' => 'Schall und Rauch', 
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
                    
            array(7081, array('fra'), 
                    array('fra' => array('collections_id' => 4, 'number' => '000'))),
            array(7081, array('fra'), 
                    array('fra' => array('collections_language' => 'eng', 'number' => '000'))),
            array(7081, array('fra'), 
                    array('fra' => array('name' => 'Schall und Rauch', 'nummer' => '000'))),
                     
            array(7081, array('ger', 'eng', 'aar', 'abk'), 
                    array('fra' => array('name' => 'Schall und Rauch', 'number' => '000'))),
            array(7081, array('ger', 'eng', 'aar', 'abk'), 
                    array('xyz' => array('name' => 'Schall und Rauch', 'number' => '000'))),
                    
            );
    }
    
    /**
     *
     * @dataProvider invalidUpdateDataProvider
     *
     */
    public function testCollectionContentsUpdateInvArg($ID, $languageArray, $contentArray) {
        $this->setExpectedException('InvalidArgumentException');
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->create($languageArray);
        foreach ($contentArray as $language => $record) {
            $occ->update(array($language => $record));   
        }
    }
    
    

    public function validLoadDataProvider() {
        return array(
            array('institute', 1),
            array('institute', 2),
            array('institute', 3),
            array('institute', 4),
            array(7081, 1),
            array(7081, 2),
            array(7081, 3),
            array(7081, 4),
        );
    }
    
    /**
     *
     * @dataProvider validLoadDataProvider
     *
     */
    public function testLoadCollectionContents($ID, $coll_id) {
        $occ = new Opus_Collection_Contents($ID);
        $pre = sizeof($occ->collectionContents);
        $occ->load($coll_id);
        $post = sizeof($occ->collectionContents);
        $this->assertGreaterThan($pre, $post, 'Nothing loaded.');
    }
    
    public function invalidLoadDataProvider() {
        return array(
            array('institute', 5),
            array('institute', 'x'),
            array(7081, 0),
            array(7081, 'y'),
        );
    }
    
    /**
     *
     * @dataProvider invalidLoadDataProvider
     *
     */
    public function testLoadCollectionContentsInvArg($ID, $coll_id) {
        $this->setExpectedException('InvalidArgumentException');
        $occ = new Opus_Collection_Contents($ID);
        $occ->load($coll_id);
    }
    
    

    /**
     *
     * @dataProvider validUpdateDataProvider
     *
     */     
    public function testSaveCollectionContents($ID, $languageArray, $contentArray) {
        
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->create($languageArray);
        foreach ($contentArray as $language => $record) {
            $occ->update(array($language => $record));   
        }
        $occ->save();
        $this->assertNotEquals($occ->collections_id, 0);
        $occ->load($occ->collections_id);
    }
    
    public function invalidSaveDataProvider() {
        return array(
            array('institute', 
                    array('ger', 'eng'), 
                    array('eng' => array('type' => 'Schall und Rauch', 
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
            array(7081, array('fra'), 
                    array('fra' => array('number' => '000')
                          )),
            array(7081, array('ger', 'eng', 'aar', 'abk'), 
                    array('aar' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'gem' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'abk' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'eng' => array('name' => 'Schall und Rauch', 'number' => '000')
                          )),
        );
    }
    
    /**
     *
     * @dataProvider invalidSaveDataProvider
     *
     */     
    public function testSaveCollectionContentsInvArg($ID, $languageArray, $contentArray) {
        $this->setExpectedException('Exception');
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->create($languageArray);
        foreach ($contentArray as $language => $record) {
            $occ->update(array($language => $record));   
        }
        $occ->save();
    }
    
    











}
