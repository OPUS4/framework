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
        (`collections_roles_id`,  `name`, `visible`) 
        VALUES (7081,  'Just to shift test area', 1)
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
     */
    public function testgetAllCollectionRoles() {
        $acr = Opus_Collection_Information::getAllCollectionRoles();
        $this->assertTrue(is_array($acr), 'getAllCollectionRoles() didnt return array');
        $this->assertTrue(sizeof($acr)>0, 'getAllCollectionRoles() returned empty array');
        $this->assertArrayHasKey(7081, $acr, 'getAllCollectionRoles() didnt return expected role');
        
        Opus_Collection_Information::getAllCollectionRoles(true);
        $this->assertTrue(is_array($acr), 'getAllCollectionRoles(true) didnt return array');
        $this->assertTrue(sizeof($acr)>0, 'getAllCollectionRoles(true) returned empty array');
        $this->assertArrayHasKey(7081, $acr, 'getAllCollectionRoles() didnt return expected role');
        
        Opus_Collection_Information::getAllCollectionRoles(false);
        $this->assertTrue(is_array($acr), 'getAllCollectionRoles(false) didnt return array');
        $this->assertTrue(sizeof($acr)>0, 'getAllCollectionRoles(false) returned empty array');
        $this->assertArrayHasKey(7081, $acr, 'getAllCollectionRoles() didnt return expected role');
    }
    
    /**
     * Test function
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
        array(0), 
        array(3.25)
        );
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     *
     * @param integer $roles_id No comment, use your brain.
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
     * @dataProvider validgetSubCollectionsDataProvider
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $expected       No comment, use your brain.
     */
    public function testgetSubCollections($collections_id, $expected) {
        $sc = Opus_Collection_Information::getSubCollections(7081, $collections_id);
        $this->assertTrue(is_array($sc), "getSubCollections(7081, $collections_id) didnt return array");
        $this->assertEquals(count($sc), $expected, "getSubCollections(7081, $collections_id) didnt return expected amount of hits ($expected)");
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     *
     * @param integer $roles_id No comment, use your brain.
     */
    public function testgetSubCollectionsInvRole($roles_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getSubCollections($roles_id);
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     *
     * @param integer $collections_id No comment, use your brain.
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
     * @dataProvider validgetPathToRootDataProvider
     *
     * @param integer $collections_id No comment, use your brain.
     * @param integer $expected       No comment, use your brain.
     */
    public function testgetPathToRoot($collections_id, $expected) {
        $ptr = Opus_Collection_Information::getPathToRoot(7081, $collections_id);
        $this->assertTrue(is_array($ptr), "getPathToRoot(7081, $collections_id) didnt return array");
        $this->assertEquals(count($ptr), $expected, "getPathToRoot(7081, $collections_id) didnt return expected amount of hits ($expected)");
        
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     *
     * @param integer $roles_id No comment, use your brain.
     */
    public function testgetPathToRootInvRole($roles_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getPathToRoot($roles_id, 4);
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     *
     * @param integer $collections_id No comment, use your brain.
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
     * @dataProvider validgetCollectionDataProvider
     *
     * @param integer $collections_id No comment, use your brain.
     */
    public function testgetCollection($collections_id) {
        $gc = Opus_Collection_Information::getCollection(7081, $collections_id);
        $this->assertTrue(is_array($gc), "getCollection(7081, $collections_id) didnt return array");
        $this->assertEquals(count($gc), 1, "getCollection(7081, $collections_id) didnt return expected amount of hits (1)");
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     *
     * @param integer $roles_id No comment, use your brain.
     */
    public function testgetCollectionInvRole($roles_id) {
        $this->setExpectedException('InvalidArgumentException');
        $sc = Opus_Collection_Information::getCollection($roles_id, 4);
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidgetCollectionRoleDataProvider
     *
     * @param integer $collections_id No comment, use your brain.
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
                    , false, 0
            ),
            array(array('name' => 'Testbaum 2')
            , true, 0
            ),
        );
    }
    
    /**
     * Test function
     * 
     * @dataProvider validnewCollectionTreeDataProvider
     *
     * @param integer $roleArray No comment, use your brain.
     * @param integer $hidden    No comment, use your brain.
     * @param integer $position  No comment, use your brain.
     */
    public function testnewCollectionTree($roleArray, $hidden, $position) {
        $pre = Opus_Collection_Information::getAllCollectionRoles($hidden);
        Opus_Collection_Information::newCollectionTree($roleArray, $position, $hidden);
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
            );
    }
    
    /**
     * Test function
     * 
     * @dataProvider invalidnewCollectionTreeDataProvider
     *
     * @param integer $roleArray No comment, use your brain.
     * @param integer $hidden    No comment, use your brain.
     * @param integer $position  No comment, use your brain.
     */
    public function testnewCollectionTreeInvArg($roleArray, $hidden, $position) {
        $this->setExpectedException('Exception');
        Opus_Collection_Information::newCollectionTree($roleArray, $position, $hidden);
    }
}