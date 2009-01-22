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
 * @package     Opus_Collections
 * @author      Tobias Tappe <tobias.tappe@uni-bielefeld.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Collection_Replacement.
 *
 * @category Tests
 * @package  Opus_Collection
 *
 * @group    ReplacementTest
 */
class Opus_Collection_ReplacementTest extends PHPUnit_Framework_TestCase {

    /**
     * SetUp database
     *
     * @return void
     */
    public function setUp() {



        $adapter = Zend_Db_Table::getDefaultAdapter();
        $adapter->query('DELETE FROM `collections_roles` WHERE `collections_roles_id` = 7081;');
        $adapter->query("INSERT INTO `collections_roles`
        (`collections_roles_id`, `name`, `visible`)
        VALUES (7081, 'Just to shift test area', 1)
        ;");

        $adapter->query('DROP TABLE IF EXISTS collections_replacement_7081;');
        $adapter->query('CREATE  TABLE collections_replacement_7081 (
              `collections_replacement_id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `collections_id` INT UNSIGNED NOT NULL,
              `replacement_for_id` INT UNSIGNED,
              `replacement_by_id` INT UNSIGNED,
              `current_replacement_id` INT UNSIGNED,
              PRIMARY KEY (`collections_replacement_id`))
            ENGINE = InnoDB
            DEFAULT CHARACTER SET = utf8
            COLLATE = utf8_general_ci
            PACK_KEYS = 0
            ROW_FORMAT = DEFAULT;');
        $adapter->query('INSERT INTO `collections_replacement_7081`
        (`collections_id`, `replacement_for_id`, `replacement_by_id`, `current_replacement_id`)
        VALUES (7, 3, 12, 12),
        (3, NULL, 7, 12),
        (12, 7, NULL, 12),
        (5, NULL, 10, 14),
        (5, NULL, 11, 11),
        (10, 5, 14, 14),
        (11, 5, NULL, 11),
        (14, 10, NULL, 14),
        (4, NULL, 13, 13),
        (6, NULL, 13, 13),
        (13, 4, NULL, 13),
        (13, 6, NULL, 13)
        ;');
        $adapter->query('TRUNCATE link_persons_documents;');
        $adapter->query('TRUNCATE link_institutes_documents;');
        $adapter->query('TRUNCATE institutes_replacement;');
        $adapter->query('TRUNCATE institutes_contents;');
        $adapter->query("INSERT INTO `institutes_contents`
        (`institutes_id`, `type`, `name`)
        VALUES (3, 'Fakultät', 'Fakultät 3'),
        (4, 'Fakultät', 'Fakultät 4'),
        (5, 'Fakultät', 'Fakultät 5'),
        (6, 'Fakultät', 'Fakultät 6'),
        (7, 'Fakultät', 'Fakultät 7'),
        (10, 'Fakultät', 'Fakultät X'),
        (11, 'Fakultät', 'Fakultät XI'),
        (12, 'Fakultät', 'Fakultät XII'),
        (13, 'Fakultät', 'Fakultät XIIV'),
        (14, 'Fakultät', 'Fakultät XIV'),
        (15, 'Fakultät', 'Fakultät XV'),
        (16, 'Fakultät', 'Fakultät XVI'),
        (17, 'Fakultät', 'Fakultät XVII'),
        (18, 'Fakultät', 'Fakultät IIXX')
        ;");
        $adapter->query('INSERT INTO `institutes_replacement`
        (`institutes_id`, `replacement_for_id`, `replacement_by_id`, `current_replacement_id`)
        VALUES (7, 3, 12, 12),
        (3, NULL, 7, 12),
        (12, 7, NULL, 12),
        (5, NULL, 10, 14),
        (5, NULL, 11, 11),
        (10, 5, 14, 14),
        (11, 5, NULL, 11),
        (14, 10, NULL, 14),
        (4, NULL, 13, 13),
        (6, NULL, 13, 13),
        (13, 4, NULL, 13),
        (13, 6, NULL, 13)
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
        $adapter->query('DROP TABLE IF EXISTS collections_replacement_7081;');
        $adapter->query('TRUNCATE institutes_replacement;');
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
     * @param integer $ID No comment, use your brain.
     * @return void
     *
     * @dataProvider validConstructorIDDataProvider
     */
    public function testCollectionReplacementConstructor($ID) {
        $ocr = new Opus_Collection_Replacement($ID);
        $ident = $ocr->getCollectionsIdentifier();
        $this->assertFalse(empty($ident), 'collectionsIdentifier not set by constructor.');
    }

    /**
     * Test function
     *
     * @param integer $ID No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidConstructorIDDataProvider
     */
    public function testCollectionReplacementConstructorInvalidArg($ID) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function validGetIDDataProvider() {
        return array(
            array('institute', 7),
            array(7081, 5),
            array(7081, 4),
        );
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidGetIDDataProvider() {
        return array(
            array('institute', -2),
            array(7081, 5.3),
            array(7081, 'no'),

            array(7081, 2),
            array(7081, 8),
            array(7081, 18),


        );
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider validGetIDDataProvider
     */
    public function testgetReplacementRecords($ID, $collections_id) {
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getReplacementRecords($collections_id);
        $this->assertTrue(is_array($replacements), 'No array returned by getReplacementRecords.');
        $this->assertTrue(count($replacements)>0, 'Empty array returned by getReplacementRecords.');
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider validGetIDDataProvider
     */
    public function testDelete($ID, $collections_id) {
        $ocr = new Opus_Collection_Replacement($ID);
        $pre = count($ocr->getReplacementRecords($collections_id));
        $ocr->delete($collections_id);
        $post = count($ocr->getReplacementRecords($collections_id));
        $this->assertTrue($post === ($pre+1), 'Delete entry not created.');
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidGetIDDataProvider
     */
    public function testgetReplacementRecordsInvArg($ID, $collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getReplacementRecords($collections_id);
    }


    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidDeleteDataProvider() {
        return array(
            array('institute', -2),
            array(7081, 5.3),
            array(7081, 'no'),


        );
    }


    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidDeleteDataProvider
     */
    public function testDeleteInvArg($ID, $collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
        $ocr->delete($collections_id);
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider validGetIDDataProvider
     */
    public function testgetCurrent($ID, $collections_id) {
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getCurrent($collections_id);
        $this->assertTrue(is_array($replacements), 'No array returned by getCurrent.');
        $this->assertTrue(count($replacements)>0, 'Empty array returned by getCurrent.');
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidGetIDDataProvider
     */
    public function testgetCurrentInvArg($ID, $collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getCurrent($collections_id);
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider validGetIDDataProvider
     */
    public function testgetAncestor($ID, $collections_id) {
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getAncestor($collections_id);
        $this->assertTrue(is_array($replacements), 'No array returned by getAncestor.');
        $this->assertTrue(count($replacements)>0, 'Empty array returned by getAncestor.');
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidGetIDDataProvider
     */
    public function testgetAncestorInvArg($ID, $collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getAncestor($collections_id);
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider validGetIDDataProvider
     */
    public function testgetReplacement($ID, $collections_id) {
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getReplacement($collections_id);
        $this->assertTrue(is_array($replacements), 'No array returned by getReplacement.');
        $this->assertTrue(count($replacements)>0, 'Empty array returned by getReplacement.');
    }

    /**
     * Test function
     *
     * @param integer $ID             No comment, use your brain.
     * @param integer $collections_id No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidGetIDDataProvider
     */
    public function testgetReplacementInvArg($ID, $collections_id) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
        $replacements = $ocr->getReplacement($collections_id);
    }


    /**
     * Data Provider
     *
     * @return array
     */
    public function validSplitDataProvider() {
        return array(
            array('institute', 11, 15, 16),
            array('institute', 14, 17, 18),
            array(7081, 12, 20, 21),
            array(7081, 13, 23, 24),
        );
    }

    /**
     * Test function
     *
     * @param integer $ID                  No comment, use your brain.
     * @param integer $collections_id_old  No comment, use your brain.
     * @param integer $collections_id_new1 No comment, use your brain.
     * @param integer $collections_id_new2 No comment, use your brain.
     * @return void
     *
     * @dataProvider validSplitDataProvider
     */
    public function testSplit($ID, $collections_id_old, $collections_id_new1, $collections_id_new2) {
        $ocr = new Opus_Collection_Replacement($ID);
        $pre_old = count($ocr->getReplacementRecords($collections_id_old));
        $ocr->split($collections_id_old, $collections_id_new1, $collections_id_new2);
        $post_old = count($ocr->getReplacementRecords($collections_id_old));
        $post_new1 = count($ocr->getReplacementRecords($collections_id_new1));
        $post_new2 = count($ocr->getReplacementRecords($collections_id_new2));
        $this->assertTrue($post_old === ($pre_old+2), 'Replacement of splitted collection not properly written.');
        $this->assertTrue($post_new1 === 1, 'First replacement for splitted collection not properly written.');
        $this->assertTrue($post_new2 === 1, 'Second replacement for splitted collection not properly written.');
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidSplitDataProvider() {
        return array(
            array('institute', -2, 15, 16),
            array('institute', 14, 5.6, 18),
            array(7081, 12, 20, 'no'),
            array(7081, 11, 23, 3.2),
        );
    }

    /**
     * Test function
     *
     * @param integer $ID                  No comment, use your brain.
     * @param integer $collections_id_old  No comment, use your brain.
     * @param integer $collections_id_new1 No comment, use your brain.
     * @param integer $collections_id_new2 No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidSplitDataProvider
     */
    public function testSplitInvArg($ID, $collections_id_old, $collections_id_new1, $collections_id_new2) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
        $ocr->split($collections_id_old, $collections_id_new1, $collections_id_new2);
    }


    /**
     * Data Provider
     *
     * @return array
     */
    public function validMergeDataProvider() {
        return array(
            array('institute', 12, 14, 16),
            array('institute', 14, 13, 18),
            array(7081, 13, 11, 21),
            array(7081, 12, 13, 24),
        );
    }

    /**
     * Test function
     *
     * @param integer $ID                  No comment, use your brain.
     * @param integer $collections_id_old1 No comment, use your brain.
     * @param integer $collections_id_old2 No comment, use your brain.
     * @param integer $collections_id_new  No comment, use your brain.
     * @return void
     *
     * @dataProvider validMergeDataProvider
     */
    public function testMerge($ID, $collections_id_old1, $collections_id_old2, $collections_id_new) {
        $ocr = new Opus_Collection_Replacement($ID);
        $pre_old1 = count($ocr->getReplacementRecords($collections_id_old1));
        $pre_old2 = count($ocr->getReplacementRecords($collections_id_old2));
        $ocr->merge($collections_id_old1, $collections_id_old2, $collections_id_new);
        $post_old1 = count($ocr->getReplacementRecords($collections_id_old1));
        $post_old2 = count($ocr->getReplacementRecords($collections_id_old2));
        $post_new = count($ocr->getReplacementRecords($collections_id_new));
        $this->assertTrue($post_old1 === ($pre_old1+1), 'Replacement of merged first collection not properly written.');
        $this->assertTrue($post_old2 === ($pre_old2+1), 'Replacement of merged second collection not properly written.');
        $this->assertTrue($post_new === 2, 'Replacement for merged collections not properly written.');
    }

    /**
     * Data Provider
     *
     * @return array
     */
    public function invalidMergeDataProvider() {
        return array(
            array('institute', -2, 14, 16),
            array('institute', 14, 2.5, 18),
            array(7081, 13, 11, 'yes'),
            array(7081, 12, -89, 24),
        );
    }

    /**
     * Test function
     *
     * @param integer $ID                  No comment, use your brain.
     * @param integer $collections_id_old1 No comment, use your brain.
     * @param integer $collections_id_old2 No comment, use your brain.
     * @param integer $collections_id_new  No comment, use your brain.
     * @return void
     *
     * @dataProvider invalidMergeDataProvider
     */
    public function testMergeInvArg($ID, $collections_id_old1, $collections_id_old2, $collections_id_new) {
        $this->setExpectedException('InvalidArgumentException');
        $ocr = new Opus_Collection_Replacement($ID);
        $ocr->merge($collections_id_old1, $collections_id_old2, $collections_id_new);
    }
}