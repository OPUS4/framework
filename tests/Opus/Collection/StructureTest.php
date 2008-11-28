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
class Opus_Collection_StructureTest extends PHPUnit_Framework_TestCase {

    /**
     * SetUp database 
     *
     * @return void
     */
    public function setUp() {

        $adapter = Zend_Db_Table::getDefaultAdapter();
        $adapter->setTablePrefix('test_');
        $adapter->query("DROP TABLE IF EXISTS collections_structure_7081;");
        $adapter->query("CREATE TABLE IF NOT EXISTS collections_structure_7081 (
              `collections_structure_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` int(10) UNSIGNED NOT NULL ,
              `left` int(10) UNSIGNED NOT NULL ,
              `right` int(10) UNSIGNED NOT NULL ,
              `visible` tinyint(1) NOT NULL default 1,
              PRIMARY KEY (`collections_structure_id`) )
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;");
        $adapter->query("INSERT INTO `collections_structure_7081` 
        (`collections_id`, `left`, `right`, `visible`) 
        VALUES (0, 1, 10, 0),
        (1, 2, 3, 1),
        (2, 4, 7, 1),
        (3, 5, 6, 1),
        (4, 8, 9, 1)
        ;");
        $adapter->query("TRUNCATE institutes_contents;");
        $adapter->query("TRUNCATE institutes_structure;");
        $adapter->query("INSERT INTO `institutes_contents` 
        (`institutes_id`, `institutes_language`, `type`, `name`) 
        VALUES (0, 'ger', 'Fakultät', 'Fakultät A'),
        (1, 'ger', 'Fakultät', 'Fakultät A1'),
        (2, 'ger', 'Fakultät', 'Fakultät A2'),
        (3, 'ger', 'Fakultät', 'Fakultät A2a'),
        (4, 'ger', 'Fakultät', 'Fakultät A3')
        ;");
        /*institutes_language
varchar(3)  utf8_general_ci  Nein   Zeige nur unterschiedliche Werte    Ändern    Löschen    Primärschlüssel    Unique    Index    Volltext 
type   
  type
varchar(50)  utf8_general_ci  Nein   Zeige nur unterschiedliche Werte    Ändern    Löschen    Primärschlüssel    Unique    Index    Volltext 
name   
  name*/
        $adapter->query("INSERT INTO `institutes_structure` 
        (`institutes_id`, `left`, `right`, `visible`) 
        VALUES (0, 1, 10, 0),
        (1, 2, 3, 1),
        (2, 4, 7, 1),
        (3, 5, 6, 1),
        (4, 8, 9, 1)
        ;");
        
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
    public function testCollectionStructureConstructor($ID) {
        $ocs = new Opus_Collection_Structure($ID);
        $this->assertTrue(isset($ocs->collectionStructure), 'No collectionStructure array built by constructor.');
        $this->assertTrue(isset($ocs->collections_structure_info), 'No collections_structure_info array built by constructor.');
    }
    
    /**
     *
     * @dataProvider invalidConstructorIDDataProvider
     *
     */
    public function testCollectionStructureConstructorInvalidArg($ID) {
        $this->setExpectedException('InvalidArgumentException');
        $ocs = new Opus_Collection_Structure($ID);
    }
        
    /**
     *
     * @dataProvider validConstructorIDDataProvider
     *
     */
    public function testCreateCollectionStructure($ID) {
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $ocs = new Opus_Collection_Structure($ID);
        $ocs->create();
        $this->assertEquals(array(1 => array($coll_id => 0,
                                            'left' => 1,
                                            'right' => 2,
                                            'visible' => 0)), 
                            $ocs->collectionStructure, 'collectionStructure array not created properly');
    }
        
    /**
     *
     * @dataProvider validConstructorIDDataProvider
     *
     */
    public function testLoadCollectionStructure($ID) {
        $ocs = new Opus_Collection_Structure($ID);
        $pre = sizeof($ocs->collectionStructure);
        $ocs->load();
        $post = sizeof($ocs->collectionStructure);
        $this->assertGreaterThan($pre, $post, 'Nothing loaded.');
    }

    // ##########################################################################
    
    public function validStructureDataProvider() {
        return array(
            array('institute', 7, 1, 0),
            array(7081, 5, 0, 0),
            array(7081, 6, 0, 1),
            );
    }
    
    /**
     *
     * @dataProvider validStructureDataProvider
     *
     */     
    public function testInsertCollectionStructure($ID, $collections_id, $parent, $leftSibling) {
        $ocs = new Opus_Collection_Structure($ID);
        $ocs->load();
        $pre = sizeof($ocs->collectionStructure);
        
        $ocs->insert($collections_id, $parent, $leftSibling);
        
        $post = sizeof($ocs->collectionStructure);
        $this->assertGreaterThan($pre, $post);
    }
    
    public function invalidStructureDataProvider() {
        return array(
            array('institute', -7, 1, 2),
            array(7081, 6, -1, 4),
            array(7081, 0, 1, -2),
            array(7081, 1, 7081, 0),
            );
    }
    
    /**
     *
     * @dataProvider invalidStructureDataProvider
     *
     */
    public function testInsertCollectionStructureInvArg($ID, $collections_id, $parent, $leftSibling) {
        $this->setExpectedException('InvalidArgumentException');
        $ocs = new Opus_Collection_Structure($ID);
        $ocs->load();
        $ocs->insert($collections_id, $parent, $leftSibling);
    }
    
    /**
     *
     * @dataProvider validStructureDataProvider
     *
     */     
    public function testSaveCollectionStructure($ID, $collections_id, $parent, $leftSibling) {
        $ocs = new Opus_Collection_Structure($ID);
        $ocs->load();
        $pre = sizeof($ocs->collectionStructure);
        $ocs->insert($collections_id, $parent, $leftSibling);
        $ocs->save();
        $ocs->load();
        $post = sizeof($ocs->collectionStructure);
        $this->assertGreaterThan($pre, $post, 'Nothing saved.');
    }
    
    public function validLeftDataProvider() {
        return array(
            array(1),
            array(2),
            array(4),
            array(5),
            array(8),
        );
    }
    
    
    /**
     *
     * @dataProvider validLeftDataProvider
     *
     */     
    public function testDeleteCollectionStructure($left) {
        $ocs = new Opus_Collection_Structure(7081);
        $ocs->load();
        $pre = sizeof($ocs->collectionStructure);
        $ocs->delete($left);
        $post = sizeof($ocs->collectionStructure);
        $this->assertGreaterThan($post, $pre, 'Nothing deleted.');
    }
    
    public function invalidLeftDataProvider() {
        return array(
            array(-11),
            array(2.3),
            array(6),
            array(10),
            array('x'),
        );
    }
    
    
    /**
     *
     * @dataProvider invalidLeftDataProvider
     *
     */     
    public function testDeleteCollectionStructureInvArg($left) {
        $this->setExpectedException('InvalidArgumentException');
        $ocs = new Opus_Collection_Structure(7081);
        $ocs->load();
        $ocs->delete($left);
    }
    
      
    
    
    
    
    
}