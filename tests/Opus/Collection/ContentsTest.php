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
        $adapter->query('DELETE FROM `collections_roles` WHERE `collections_roles_id` = 7081;');
        $adapter->query('INSERT INTO `collections_roles` 
        (`collections_roles_id`, `name`, `visible`) 
        VALUES (7081, "Just to shift test area", 1)
        ;');
        
        $adapter->query('DROP TABLE IF EXISTS collections_contents_7081;');
        $adapter->query('CREATE TABLE collections_contents_7081 (
            `collections_id` INT( 11 ) UNSIGNED NOT NULL ,
            `name` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
            `number` VARCHAR( 3 ) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL ,
            PRIMARY KEY ( `collections_id` )
            ) ENGINE = InnoDB');
        $adapter->query('INSERT INTO `collections_contents_7081`
        (`collections_id`, `name`, `number`)
        VALUES (1,  "Tiere", "000"),
        (2,  "Pflanzen", "000"),
        (3,  "dogs", "000"),
        (4,  "Insekten", "000"),
        (5,  "boef", "000")
        ;');
                $adapter->query('TRUNCATE institutes_contents;');
        $adapter->query('INSERT INTO `institutes_contents`
        (`institutes_id`, `type`, `name`)
        VALUES (0, "Fakultät", "Fakultät A"),
        (1, "Fakultät", "Fakultät A1"),
        (2, "Fakultät", "Fakultät A2"),
        (3, "Fakultät", "Fakultät A2a"),
        (4, "Fakultät", "Fakultät A3")
        ;');
        
    }

    /**
     * TearDown database
     *
     * @return void
     */
    public function tearDown() {
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $adapter->query('DELETE FROM `collections_roles` WHERE `collections_roles_id` = 7081;');
        $adapter->query('DROP TABLE IF EXISTS collections_contents_7081;');
        $adapter->query('TRUNCATE institutes_contents;');
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function validConstructorIDDataProvider() {
        return array(
            array('institute'),
            array(7081),
        );
    }

    /**
     * Data Provider
     *
     * @return array
     */
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
     * Test function
     *
     * @dataProvider validConstructorIDDataProvider
     *
     * @param integer $ID No comment, use your brain.
     */
    public function testCollectionContentsConstructor($ID) {
        $occ = new Opus_Collection_Contents($ID);
        $cont = $occ->getCollectionContents();
        $this->assertTrue(is_array($cont), 'No collectionContents array built by constructor.');
    }

    /**
     * Test function
     * 
     * @dataProvider invalidConstructorIDDataProvider
     *
     * @param integer $ID No comment, use your brain.
     */
    public function testCollectionContentsConstructorInvalidArg($ID) {
        $this->setExpectedException('InvalidArgumentException');
        $ocs = new Opus_Collection_Contents($ID);
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function validUpdateDataProvider() {
        return array(
            array('institute',
                    array('type' => 'Schall und Rauch',
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    
            ),
            array(7081, 
                    array('name' => 'Schall und Rauch', 'number' => '000')
                          ),
            array(7081, 
                    array('name' => 'Schall und Rauch', 'number' => '000')
                          ),
        );
    }

    /**
     * Test function
     *
     * @dataProvider validUpdateDataProvider
     *
     * @param integer $ID           No comment, use your brain.
     * @param integer $contentArray No comment, use your brain.
     */
    public function testCollectionContentsUpdate($ID, $contentArray) {
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->update($contentArray);
        foreach ($contentArray as $contentType => $contentValue) {
            $cont = $occ->getCollectionContents();
            $this->assertArrayHasKey($contentType, $cont, 'collectionContents array does not contain expected contentType ' . $contentType);
        }
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidUpdateDataProvider() {
        return array(
            array('institute',
                    array('eng' => array('institutes_id' => 5,
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
            array('institute',
                    array('eng' => array('institutes_language' => 'ger',
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
            array('institute',
                    array('eng' => array('typ' => 'Schall und Rauch',
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),

            array(7081, 
                    array('fra' => array('collections_id' => 4, 'number' => '000'))),
            array(7081, 
                    array('fra' => array('collections_language' => 'eng', 'number' => '000'))),
            array(7081, 
                    array('fra' => array('name' => 'Schall und Rauch', 'nummer' => '000'))),

            array(7081, 
                    array('fra' => array('name' => 'Schall und Rauch', 'number' => '000'))),
            array(7081, 
                    array('xyz' => array('name' => 'Schall und Rauch', 'number' => '000'))),

            );
    }

    /**
     * Test function
     *
     * @dataProvider invalidUpdateDataProvider
     *
     * @param integer $ID           No comment, use your brain.
     * @param integer $contentArray No comment, use your brain.
     */
    public function testCollectionContentsUpdateInvArg($ID, $contentArray) {
        $this->setExpectedException('InvalidArgumentException');
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        foreach ($contentArray as $language => $record) {
            $occ->update(array($language => $record));
        }
    }

    /**
     * Data Provider
     *
     * @return array
     */
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
     * Test function
     *
     * @dataProvider validLoadDataProvider
     *
     * @param integer $ID      No comment, use your brain.
     * @param integer $coll_id No comment, use your brain.
     */
    public function testLoadCollectionContents($ID, $coll_id) {
        $occ = new Opus_Collection_Contents($ID);
        $pre = count($occ->getCollectionContents());
        $occ->load($coll_id);
        $post = count($occ->getCollectionContents());
        $this->assertGreaterThan($pre, $post, 'Nothing loaded.');
    }

    /**
     * Data Provider
     *
     * @return array
     */
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
     * @param integer $ID      No comment, use your brain.
     * @param integer $coll_id No comment, use your brain.
     */
    public function testLoadCollectionContentsInvArg($ID, $coll_id) {
        $this->setExpectedException('InvalidArgumentException');
        $occ = new Opus_Collection_Contents($ID);
        $occ->load($coll_id);
    }

    /**
     * Test function
     *
     * @dataProvider validUpdateDataProvider
     *
     * @param integer $ID           No comment, use your brain.
     * @param integer $contentArray No comment, use your brain.
     */
    public function testSaveCollectionContents($ID, $contentArray) {
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        $occ->update($contentArray);
        $occ->save();
        $this->assertNotEquals($occ->getCollectionsID(), 0);
        $occ->load($occ->getCollectionsID());
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidSaveDataProvider() {
        return array(
            array('institute',
                    array('eng' => array('type' => 'Schall und Rauch',
                                            'name' => '000',
                                            'postal_address' => 'asdf',
                                            'site' => 'http://www.asdf.de')
                    )
            ),
            array(7081, 
                    array('fra' => array('number' => '000')
                          )),
            array(7081, 
                    array('aar' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'gem' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'abk' => array('name' => 'Schall und Rauch', 'number' => '000'),
                          'eng' => array('name' => 'Schall und Rauch', 'number' => '000')
                          )),
        );
    }

    /**
     * Test function
     *
     * @dataProvider invalidSaveDataProvider
     *
     * @param integer $ID           No comment, use your brain.
     * @param integer $contentArray No comment, use your brain.
     */
    public function testSaveCollectionContentsInvArg($ID, $contentArray) {
        $this->setExpectedException('Exception');
        $coll_id = ($ID==='institute') ? 'institutes_id' : 'collections_id';
        $occ = new Opus_Collection_Contents($ID);
        foreach ($contentArray as $language => $record) {
            $occ->update(array($language => $record));
        }
        $occ->save();
    }
}
