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
 * Test cases for class Opus_Collection_Information.
 *
 * @category Tests
 * @package  Opus_Collection
 */
class Opus_Collection_InformationTest extends PHPUnit_Framework_TestCase {

    /**
     * SetUp database
     *
     * @return void
     */
    public function setUp() {
        $adapter = Zend_Registry::get('db_adapter');
        
        $adapter->query('DELETE FROM `collections_roles` WHERE collections_roles_id > 7080;');
        for ($i=7081; $i<7111; $i++) {
            $adapter->query("DROP TABLE IF EXISTS collections_replacement_$i;");
            $adapter->query("DROP TABLE IF EXISTS collections_structure_$i;");
            $adapter->query("DROP TABLE IF EXISTS link_documents_collections_$i;");
            $adapter->query("DROP TABLE IF EXISTS collections_contents_$i;");
        }
        
        $adapter->query("INSERT INTO `collections_roles` 
        (`collections_roles_id`,  `name`, `position`, `link_docs_path_to_root`, `visible`) 
        VALUES (7081,  'Just to shift test area', 2, 1, 1)
        ;");
        
        $adapter->query('DROP TABLE IF EXISTS collections_contents_7081;');
        $adapter->query('CREATE TABLE collections_contents_7081 (
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
            `number` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
            PRIMARY KEY ( `collections_id`  ) 
            ) ENGINE = InnoDB');
        $adapter->query("INSERT INTO `collections_contents_7081` 
        (`collections_id`, `name`, `number`) 
        VALUES  (0,  'root', '000'),
                (1,  'A', '000'),
                (2,  'A2', '000'),
                (3,  'A2a', '000'),
                (4,  'A1', '000'),
                (5,  'B', '000'),
                (6, 'B1', '000'),
                (7,  'B3', '000'),
                (8,  'B2', '000'),
                (9,  'X', '000'),
                (10,  'Y', '000'),
                (11,  'Z', '000')
        ;");
        
        $adapter->query('DROP TABLE IF EXISTS collections_structure_7081;');
        $adapter->query('CREATE TABLE IF NOT EXISTS collections_structure_7081 (
              `collections_structure_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` int(10) UNSIGNED NOT NULL ,
              `left` int(10) UNSIGNED NOT NULL ,
              `right` int(10) UNSIGNED NOT NULL ,
              `visible` tinyint(1) NOT NULL default 1,
              PRIMARY KEY (`collections_structure_id`) ,
              INDEX fk_collections_structure_collections_contents_7081 (`collections_id` ASC) ,
              CONSTRAINT `fk_collections_structure_collections_contents_7081`
                FOREIGN KEY (`collections_id` )
                REFERENCES `collections_contents_7081` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;');
        $adapter->query('INSERT INTO `collections_structure_7081` 
        (`collections_id`, `left`, `right`, `visible`) 
        VALUES  (0, 1, 20, 0),
                (1, 2, 9, 1),
                (2, 5, 8, 1),
                (3, 6, 7, 1),
                (4, 3, 4, 1),
                (4, 14, 15, 1),
                (5, 10, 19, 1),
                (6, 11, 12, 1),
                (7, 17, 18, 1),
                (8, 13, 16, 1)
        ;');
        
        $adapter->query('DROP TABLE IF EXISTS collections_replacement_7081;');
        $adapter->query('CREATE  TABLE collections_replacement_7081 (
              `collections_replacement_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` INT UNSIGNED NOT NULL,
              `replacement_for_id` INT UNSIGNED,
              `replacement_by_id` INT UNSIGNED,
              `current_replacement_id` INT UNSIGNED,
                            PRIMARY KEY (`collections_replacement_id`) ,
              INDEX fk_link_collections_7081 (`collections_id` ASC) ,
              INDEX fk_link_collections_replacement_for_7081 (`replacement_for_id` ASC) ,
              INDEX fk_link_collections_replacement_by_7081 (`replacement_by_id` ASC) ,
              INDEX fk_link_collections_current_replacement_7081 (`current_replacement_id` ASC) ,
              CONSTRAINT `fk_link_collections_7081`
                FOREIGN KEY (`collections_id` )
                REFERENCES `collections_contents_7081` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_replacement_for_7081`
                FOREIGN KEY (`replacement_for_id` )
                REFERENCES `collections_contents_7081` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_replacement_by_7081`
                FOREIGN KEY (`replacement_by_id` )
                REFERENCES `collections_contents_7081` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION,
              CONSTRAINT `fk_link_collections_current_replacement_7081`
                FOREIGN KEY (`current_replacement_id` )
                REFERENCES `collections_contents_7081` (`collections_id` )
                ON DELETE NO ACTION
                ON UPDATE NO ACTION)
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;');
        $adapter->query('INSERT INTO `collections_replacement_7081` 
        (`collections_id`, `replacement_for_id`, `replacement_by_id`, `current_replacement_id`) 
        VALUES  (9, NULL, 4, 4),
                (9, NULL, 2, 2),
                (4, 9, NULL, 4),
                (2, 9, NULL, 2),
                (10, NULL, 7, 7),
                (11, NULL, 7, 7),
                (7, 10, NULL, 7),
                (7, 11, NULL, 7)
        ;');
        
        
        
        
        
        
        $adapter->query('CREATE TABLE link_documents_collections_7081 (
            `link_documents_collections_id` INT( 11 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            `documents_id` INT( 11 ) UNSIGNED NOT NULL ,
            PRIMARY KEY ( `link_documents_collections_id` ) 
            ) ENGINE = InnoDB');
        
        $adapter->query('INSERT INTO `link_documents_collections_7081` 
        (`collections_id`, `documents_id`) 
        VALUES  (2, 200),
        (2, 201),
        (2, 202),
        (2, 203),
        (2, 204),
        (3, 302),
        (3, 303),
        (3, 304),
        (4, 400),
        (4, 401),
        (5, 500),
        (5, 501),
        (5, 502),
        (6, 601),
        (6, 602),
        (8, 801),
        (8, 802),
        (8, 803),
        (8, 804),
        (8, 805),
        (7, 701),
        (1, 101),
        (1, 102),
        (1, 103),
        (1, 104),
        (1, 105),
        (1, 106)
        ;');
        
    }
    
    /**
     * TearDown database
     *
     * @return void
     */
    public function tearDown() {
        $adapter = Zend_Registry::get('db_adapter');
        $adapter->query('DELETE FROM `collections_roles` WHERE collections_roles_id > 7080;');
        for ($i=7081; $i<7111; $i++) {
            $adapter->query("DROP TABLE IF EXISTS collections_replacement_$i;");
            $adapter->query("DROP TABLE IF EXISTS collections_structure_$i;");
            $adapter->query("DROP TABLE IF EXISTS link_documents_collections_$i;");
            $adapter->query("DROP TABLE IF EXISTS collections_contents_$i;");
        }
    }



    /**
     * Test function
     * 
     * @return void
     */
    public function testgetAllCollectionRoles() {
        $acr = Opus_Collection_Information::getAllCollectionRoles();
        $this->assertTrue(is_array($acr), 'getAllCollectionRoles() didnt return array');
        $this->assertTrue(count($acr)>0, 'getAllCollectionRoles() returned empty array');
        $this->assertArrayHasKey(7081, $acr, 'getAllCollectionRoles() didnt return expected role');
        
        $acr = Opus_Collection_Information::getAllCollectionRoles(true);
        $this->assertTrue(is_array($acr), 'getAllCollectionRoles(true) didnt return array');
        $this->assertTrue(count($acr)>0, 'getAllCollectionRoles(true) returned empty array');
        $this->assertArrayHasKey(7081, $acr, 'getAllCollectionRoles() didnt return expected role');
        
        $acr = Opus_Collection_Information::getAllCollectionRoles(false);
        $this->assertTrue(is_array($acr), 'getAllCollectionRoles(false) didnt return array');
        $this->assertTrue(count($acr)>0, 'getAllCollectionRoles(false) returned empty array');
        $this->assertArrayHasKey(7081, $acr, 'getAllCollectionRoles() didnt return expected role');
    }
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidgetAllCollectionRolesDataProvider() {
        return array(
        array(777), 
        array('true'), 
        array(null), 
        array(3.25)
        );
    }
    
    /**
     * Test function
     * 
     * @param integer $alsohidden No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetAllCollectionRolesDataProvider
     */
    public function testgetAllCollectionRolesInvArg($alsohidden) {
        $this->setExpectedException('InvalidArgumentException');
        $acr = Opus_Collection_Information::getAllCollectionRoles($alsohidden);
    }
    
    /**
     * Test function
     * 
     * @return void
     */
    public function testgetCollectionRole() {
        $acr = Opus_Collection_Information::getAllCollectionRoles();
        foreach ($acr as $roles_id => $record) {
            $cr = Opus_Collection_Information::getCollectionRole($roles_id); 
            $this->assertTrue(is_array($cr), "getCollectionRole($roles_id) didnt return array");
            $this->assertEquals($cr, $record, "getCollectionRole($roles_id) didnt return expected record.");
        }
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidgetCollectionRoleDataProvider() {
        return array(
        array(777), 
        array(-12), 
        array('x'), 
        array(3.25)
        );
    }
    
    /**
     * Test function
     * 
     * @param integer $roles_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     */
    public function testgetCollectionRoleInvArg($roles_id) {
        $this->setExpectedException('InvalidArgumentException');
        $cr = Opus_Collection_Information::getCollectionRole($roles_id);   
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function validgetSubCollectionsDataProvider() {
        return array(array(1,2),
                    array(2,1),
                    array(3,0),
                    array(4,0),
                    array(5,3),
                    array(6,0),
                    array(7,0),
                    array(8,1),
        );
    }
    
    /**
     * Test function
     * 
     * @param integer $collections_id No comment, use your brain.
     * @param integer $expected       No comment, use your brain.
     * @return void
     * 
     * @dataProvider validgetSubCollectionsDataProvider
     */
    public function testgetSubCollections($collections_id, $expected) {
        $sc = Opus_Collection_Information::getSubCollections(7081, $collections_id);
        $this->assertTrue(is_array($sc), "getSubCollections(7081, $collections_id) didnt return array");
        $this->assertEquals(count($sc), $expected, "getSubCollections(7081, $collections_id) didnt return expected amount of hits ($expected)");
    }
    
    /**
     * Test function
     *
     * @param integer $roles_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     */
    public function testgetSubCollectionsInvRole($roles_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getSubCollections($roles_id);
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     */
    public function testgetSubCollectionsInvArg($collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getSubCollections(7081, $collections_id);
    }
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function validgetPathToRootDataProvider() {
        return array(array(1,1),
                    array(2,1),
                    array(3,1),
                    array(4,2),
                    array(5,1),
                    array(6,1),
                    array(7,1),
                    array(8,1),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $expected       No comment, use your brain.
     * @return void
     * 
     * @dataProvider validgetPathToRootDataProvider
     */
    public function testgetPathToRoot($collections_id, $expected) {
        $ptr = Opus_Collection_Information::getPathToRoot(7081, $collections_id);
        $this->assertTrue(is_array($ptr), "getPathToRoot(7081, $collections_id) didnt return array");
        $this->assertEquals(count($ptr), $expected, "getPathToRoot(7081, $collections_id) didnt return expected amount of hits ($expected)");
        
    }
    
    /**
     * Test function
     *
     * @param integer $roles_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     */
    public function testgetPathToRootInvRole($roles_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getPathToRoot($roles_id, 4);
    }
    
    /**
     * Test function
     * 
     * @param integer $collections_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     */
    public function testgetPathToRootInvArg($collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getPathToRoot(7081, $collections_id);
    }
        
    /**
     * Data Provider
     *
     * @return array
     */
    public function validgetCollectionDataProvider() {
        return array(array(1),
                    array(2),
                    array(3),
                    array(4),
                    array(5),
                    array(6),
                    array(7),
                    array(8),
        );
    }
    
    /**
     * Test function
     * 
     * @param integer $collections_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider validgetCollectionDataProvider
     */
    public function testgetCollection($collections_id) {
        $gc = Opus_Collection_Information::getCollection(7081, $collections_id);
        $this->assertTrue(is_array($gc), "getCollection(7081, $collections_id) didnt return array");
        $this->assertEquals($gc['collections_id'], $collections_id, "getCollection(7081, $collections_id) didnt return expected ID ($collections_id)");
    }
    
    /**
     * Test function
     *
     * @param integer $roles_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     */
    public function testgetCollectionInvRole($roles_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getCollection($roles_id, 4);
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     */
    public function testgetCollectionInvArg($collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getCollection(7081, $collections_id);
    }
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function validnewCollectionTreeDataProvider() {
        return array(
            array(array('name' => 'Testbaum')
                    , false, 0 ,array(array(
                                              'name' => 'name',
                                              'type' => 'VARCHAR',
                                              'length' => 255
                                         ))
            ),
            array(array('name' => 'Testbaum 2')
            , true, 0, array(array(
                                              'name' => 'name',
                                              'type' => 'VARCHAR',
                                              'length' => 255
                                         ), array(
                                              'name' => 'number',
                                              'type' => 'VARCHAR',
                                              'length' => 3
                                         ), array(
                                              'name' => 'hausnummer',
                                              'type' => 'INT',
                                              'length' => 11
                                         ))
            ),
        );
    }
    
    
    /**
     * Test function
     *
     * @param integer $roleArray       No comment, use your brain.
     * @param integer $hidden          No comment, use your brain.
     * @param integer $position        No comment, use your brain.
     * @param integer $content_fields  No comment, use your brain.
     * @return void
     * 
     * @dataProvider validnewCollectionTreeDataProvider
     */
    public function testnewCollectionTree($roleArray, $hidden, $position, $content_fields) {
        $pre = Opus_Collection_Information::getAllCollectionRoles($hidden);
        Opus_Collection_Information::newCollectionTree($roleArray, $content_fields, $position, $hidden);
        $post = Opus_Collection_Information::getAllCollectionRoles($hidden);
        $this->assertGreaterThan(count($pre), count($post), "newCollectionTree didn't insert Role");
    }
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidnewCollectionTreeDataProvider() {
        return array(
            array(array(1 => array('name' => 'Testbaum 1'),
                        2 => array('name' => 'Testtree 1'),
                    ), false, 7081
            ),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), false, 7081
            ),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), false, 0
            ),
            array('x', false, 0),
            array(5, false, 0),
            array(null, false, 0),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), 'x', 0
            ),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), 5, 0
            ),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), null, 0
            ),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), false, 'x'
            ),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), false, 3.5
            ),
            array(array('ger' => 'Testbaum',
                        'eng' => 'Testbaum',
                    ), false, -7
            ),
            
        );
    }
    
    /**
     * Test function
     *
     * @param integer $roleArray No comment, use your brain.
     * @param integer $hidden    No comment, use your brain.
     * @param integer $position  No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidnewCollectionTreeDataProvider
     */
    public function testnewCollectionTreeInvArg($roleArray, $hidden, $position) {
        $this->setExpectedException('Exception');
        Opus_Collection_Information::newCollectionTree($roleArray, $position, $hidden);
    }
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function validnewCollectionDataProvider() {
        return array(
            array(6, 0,  array('name' => 'Testinput 1',
                               'number' =>  '666')
            ),
            array(5, 6,  array('name' => 'Testinput 2',
                               'number' =>  '666')
            ),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $parent_id      No comment, use your brain.
     * @param integer $leftSibling_id No comment, use your brain.
     * @param integer $contentArray   No comment, use your brain.
     * @return void
     * 
     * @dataProvider validnewCollectionDataProvider
     */
    public function testnewCollection($parent_id, $leftSibling_id, $contentArray) {
        
        $pre_subColls = Opus_Collection_Information::getSubCollections(7081, $parent_id);
        $coll_id = Opus_Collection_Information::newCollection(7081, $parent_id, $leftSibling_id, $contentArray);
        $this->assertTrue(is_int($coll_id), "newCollection didn't return integer");
        
        $post_subColls = Opus_Collection_Information::getSubCollections(7081, $parent_id);
        $this->assertGreaterThan(count($pre_subColls), count($post_subColls), "newCollection didn't insert Collection");
        
        $collectionRecord = Opus_Collection_Information::getCollection(7081, $coll_id);
        $this->assertEquals($collectionRecord['collections_id'], $coll_id, "getCollection of newly created collection didnt return expected ID ($coll_id)");
        unset($collectionRecord['collections_id']);
        $this->assertEquals($contentArray, $collectionRecord, "getCollection of newly created collection didn't return expected content");
        
    }
    
    
    
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidSiblingnewCollectionDataProvider() {
        return array(
            array(6, 2,  array('name' => 'Testinput 1',
                               'number' =>  '777')
            ),
            array(3, 10,  array('name' => 'Testinput 2',
                               'number' =>  '777')
            ),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $parent_id      No comment, use your brain.
     * @param integer $leftSibling_id No comment, use your brain.
     * @param integer $contentArray   No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidSiblingnewCollectionDataProvider
     */
    public function testnewCollectionInvSibling($parent_id, $leftSibling_id, $contentArray) {
        $this->setExpectedException('Exception');
        $coll_id = Opus_Collection_Information::newCollection(7081, $parent_id, $leftSibling_id, $contentArray);
    }
    
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidparentnewCollectionDataProvider() {
        return array(
            array(10, 2,  array('name' => 'Testinput 1',
                               'number' =>  '888')
            ),
            array(9, 10,  array('name' => 'Testinput 2',
                               'number' =>  '888')
            ),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $parent_id      No comment, use your brain.
     * @param integer $leftSibling_id No comment, use your brain.
     * @param integer $contentArray   No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidparentnewCollectionDataProvider
     */
    public function testnewCollectionInvParent($parent_id, $leftSibling_id, $contentArray) {
        $this->setExpectedException('Exception');
        $coll_id = Opus_Collection_Information::newCollection(7081, $parent_id, $leftSibling_id, $contentArray);
    }
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidArgumentnewCollectionDataProvider() {
        return array(
            array('x', 2,  array('name' => 'Testinput 1',
                               'number' =>  '777')
            ),
            array(3.2, 10,  array('name' => 'Testinput 2',
                               'number' =>  '777')
            ),
            array(6, 'v',  array('name' => 'Testinput 1',
                               'number' =>  '777')
            ),
            array(3, 5.6,  array('name' => 'Testinput 2',
                               'number' =>  '777')
            ),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $parent_id      No comment, use your brain.
     * @param integer $leftSibling_id No comment, use your brain.
     * @param integer $contentArray   No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidArgumentnewCollectionDataProvider
     */
    public function testnewCollectionInvArgument($parent_id, $leftSibling_id, $contentArray) {
        $this->setExpectedException('InvalidArgumentException');
        $coll_id = Opus_Collection_Information::newCollection(7081, $parent_id, $leftSibling_id, $contentArray);
    }
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function validnewCollectionPositionDataProvider() {
        return array(
            array(1, 6, 0
            ),
            array(5, 1, 4
            ),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $parent_id      No comment, use your brain.
     * @param integer $leftSibling_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider validnewCollectionPositionDataProvider
     */
    public function testnewCollectionPosition($collections_id, $parent_id, $leftSibling_id) {
        
        $pre_subColls = Opus_Collection_Information::getSubCollections(7081, $parent_id);
        Opus_Collection_Information::newCollectionPosition(7081, $collections_id, $parent_id, $leftSibling_id);
        $post_subColls = Opus_Collection_Information::getSubCollections(7081, $parent_id);
        $this->assertGreaterThan(count($pre_subColls), count($post_subColls), "newCollectionPosition didn't insert Collection");
    }
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidStructurenewCollectionPositionDataProvider() {
        return array(
            array(12, 6, 0
            ),
            array(15, 1, 4
            ),
            array(1, 9, 0
            ),
            array(5, 12, 4
            ),
            array(1, 6, 4
            ),
            array(5, 1, 6
            ),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $parent_id      No comment, use your brain.
     * @param integer $leftSibling_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidStructurenewCollectionPositionDataProvider
     */
    public function testnewCollectionPositionInvStructure($collections_id, $parent_id, $leftSibling_id) {
        $this->setExpectedException('Exception');
        $coll_id = Opus_Collection_Information::newCollectionPosition(7081, $collections_id, $parent_id, $leftSibling_id);
    }
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidArgumentnewCollectionPositionDataProvider() {
        return array(
            array('q', 6, 0
            ),
            array(15, 'r', 4
            ),
            array(1, 9, 's'
            ),
            array(-5, 12, 4
            ),
            array(1, 6.2, 4
            ),
            array(5, 1, -6
            ),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $parent_id      No comment, use your brain.
     * @param integer $leftSibling_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidArgumentnewCollectionPositionDataProvider
     */
    public function testnewCollectionPositionInvArg($collections_id, $parent_id, $leftSibling_id) {
        $this->setExpectedException('InvalidArgumentException');
        $coll_id = Opus_Collection_Information::newCollectionPosition(7081, $collections_id, $parent_id, $leftSibling_id);
    }
        
    /**
     * Data Provider
     *
     * @return array
     */
    public function validDeleteCollectionPositionDataProvider() {
        return array(
            array(10, 0),
            array(5, 1),
            array(13, 5),
            array(3, 1),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $left   No comment, use your brain.
     * @param integer $parent No comment, use your brain.
     * @return void
     * 
     * @dataProvider validDeleteCollectionPositionDataProvider
     */
    public function testDeleteCollectionPosition($left, $parent) {
        $pre_subColls = Opus_Collection_Information::getSubCollections(7081, $parent);
        Opus_Collection_Information::deleteCollectionPosition(7081, $left);
        $post_subColls = Opus_Collection_Information::getSubCollections(7081, $parent);
        $this->assertLessThan(count($pre_subColls), count($post_subColls), "deleteCollectionPosition didn't delete anything");
    }
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidLeftDeleteCollectionPositionDataProvider() {
        return array(
            array(12),
            array(9),
            array(32),
            array(0),
            array('l'),
            array(3.1415926),
            array(-32),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $left No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidLeftDeleteCollectionPositionDataProvider
     */
    public function testDeleteCollectionPositionInvLeft($left) {
        $this->setExpectedException('Exception');
        Opus_Collection_Information::deleteCollectionPosition(7081, $left);
    }
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidCollIDDeleteCollectionDataProvider() {
        return array(
            array(0),
            array('l'),
            array(3.1415926),
            array(-32),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidCollIDDeleteCollectionDataProvider
     */
    public function testDeleteCollectionInvCollID($collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Collection_Information::deleteCollection(7081, $collections_id);
    }
    
    
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function nonExistingCollIDDeleteCollectionDataProvider() {
        return array(
            array(32),
            array(50),
            array(7081),
            array(19),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @return void
     * 
     * @dataProvider nonExistingCollIDDeleteCollectionDataProvider
     */
    public function testDeleteCollectionnonExistCollID($collections_id) {
        $this->setExpectedException('Exception');
        Opus_Collection_Information::deleteCollection(7081, $collections_id);
    }
    
    
    
    
    
    
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function validGetAllCollectionDocumentsDataProvider() {
        return array(
            array('x', 27),
            array(1, 16),
            array(2, 8),
            array(3, 3),
            array(4, 2),
            array(5, 13),
            array(6, 2),
            array(7, 1),
            array(8, 7),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $coll_id  No comment, use your brain.
     * @param integer $expected No comment, use your brain.
     * @return void
     * 
     * @dataProvider validGetAllCollectionDocumentsDataProvider
     */
    public function testGetAllCollectionDocuments($coll_id, $expected) {
        $acd = Opus_Collection_Information::getAllCollectionDocuments(7081, $coll_id);
        $this->assertEquals($expected, count($acd), "getAllCollectionDocuments didn't return expected amount of doc IDs.");
    }

    
    
    
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function validAssignDocumentToCollectionDataProvider() {
        return array(
            array(1, 111),
            array(2, 222),
            array(3, 333),
            array(4, 444),
            array(5, 555),
            array(6, 666),
            array(7, 777),
            array(8, 888),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $documents_id   No comment, use your brain.
     * @return void
     * 
     * @dataProvider validAssignDocumentToCollectionDataProvider
     */
    public function testAssignDocumentToCollection($collections_id, $documents_id) {
        $pre = Opus_Collection_Information::getAllCollectionDocuments(7081, $collections_id);
        Opus_Collection_Information::assignDocumentToCollection($documents_id, 7081, $collections_id);
        $post = Opus_Collection_Information::getAllCollectionDocuments(7081, $collections_id);
        $this->assertGreaterThan(count($pre), count($post), "assignDocumentToCollection didn't insert assignment.");
    }
    
    
    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidAssignDocumentToCollectionDataProvider() {
        return array(
            array(1.5, 111),
            array(-2, 222),
            array(0, 333),
            array('xyz', 444),
            array(5, 1.5),
            array(6, -2),
            array(7, 0),
            array(8, 'xyz'),
        );
    }
    
    /**
     * Test function
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $documents_id   No comment, use your brain.
     * @return void
     * 
     * @dataProvider invalidAssignDocumentToCollectionDataProvider
     */
    public function testAssignDocumentToCollectionInvArg($collections_id, $documents_id) {
        $this->setExpectedException('InvalidArgumentException');
        Opus_Collection_Information::assignDocumentToCollection($documents_id, 7081, $collections_id);
    }
    
    
    
}