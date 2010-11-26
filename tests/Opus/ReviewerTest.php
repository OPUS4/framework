<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Framework
 * @package     Opus
 * @author     	Thoralf Klein <thoralf.klein@zib.de>
 * @copyright  	Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: CollectionOld.php -1$
 */
/**
 * Test cases for Opus_Role.
 *
 * @package Opus
 * @category Tests
 * @group RoleTests
 */
class Opus_ReviewerTest extends TestCase {

    private $_collections = array();

    protected function setUp() {
        parent::setUp();

        /*
         * CollectionRole: testrole
         */
        $role = new Opus_CollectionRole();
        $role->setName('testrole');
        $role->setOaiName('testrole');

        $root = $role->addRootCollection();

        $c_0 = $root->addFirstChild()->setNumber('0');
        $c_01 = $c_0->addFirstChild()->setNumber('01');
        $c_011 = $c_01->addFirstChild()->setNumber('011');

        $c_1 = $root->addFirstChild()->setNumber('1');
        $c_11 = $c_1->addFirstChild()->setNumber('11');
        $c_112 = $c_11->addFirstChild()->setNumber('112');
        $c_1120 = $c_112->addFirstChild()->setNumber('1120');
        $c_1120 = $c_112->addFirstChild()->setNumber('1120');

        $c_60 = $root->addFirstChild()->setNumber('60');
        $c_600 = $c_60->addFirstChild()->setNumber('600');
        $c_610 = $c_60->addFirstChild()->setNumber('610');
        $c_620 = $c_60->addFirstChild()->setNumber('620');

        $c_060 = $root->addFirstChild()->setNumber('060');

        $role->store();

        $this->_collections['0'] = new Opus_Collection($c_0->getId());
        $this->_collections['01'] = new Opus_Collection($c_01->getId());
        $this->_collections['011'] = new Opus_Collection($c_011->getId());

        $this->_collections['1'] = new Opus_Collection($c_1->getId());
        $this->_collections['11'] = new Opus_Collection($c_11->getId());
        $this->_collections['112'] = new Opus_Collection($c_112->getId());
        $this->_collections['60'] = new Opus_Collection($c_60->getId());
        $this->_collections['610'] = new Opus_Collection($c_610->getId());

        /*
         * CollectionRole: testrole
         */
        $role = new Opus_CollectionRole();
        $role->setName('testrole2');
        $role->setOaiName('testrole2');

        $root = $role->addRootCollection();

        $c_011 = $root->addFirstChild()->setNumber('011');

        $role->store();

        $this->_collections['u_011'] = new Opus_Collection($c_011->getId());
    }

    /**
     * @todo Add comment.
     */
    public function testInitCollectionsFailedRole() {
        $review_config = array('collections' => array(
                'non-existant' => array(
                    '112' => array('admin'),
                ),
            ),
        );

        $this->setExpectedException('Opus_Model_Exception');
        Opus_Reviewer::init($review_config);
    }

    /**
     * @todo Add comment.
     */
    public function testInitCollectionsFailedCollection() {
        $review_config = array('collections' => array(
                'testrole' => array(
                    '111' => array('admin'),
                ),
            ),
        );

        $this->setExpectedException('Opus_Model_Exception');
        Opus_Reviewer::init($review_config);
    }

    /**
     * @todo Add comment.
     */
    public function testInitCollectionsFailedAmbiguous() {
        $review_config = array('collections' => array(
                'testrole' => array(
                    '1120' => array('admin'),
                ),
            ),
        );

        $this->setExpectedException('Opus_Model_Exception');
        Opus_Reviewer::init($review_config);
    }

    /**
     * @todo Implement testInit().
     */
    public function testInitCollectionsSuccess() {
        $review_config = array('collections' => array(
                'testrole' => array(
                    '01' => array('admin', 'tklein'),
                    '011' => array('foo', 'bar'),
                    '60'  => array('tklein', 'test'),
                ),
            ),
        );

        // Check if initialization works.
        Opus_Reviewer::init($review_config);

        $reviewers = array('admin', 'tklein', 'test', 'foo', 'bar');
        foreach ($reviewers AS $reviewer) {
            $r_object  = new Opus_Reviewer($reviewer);
        }
    }

    /**
     * @todo Add comment.
     */
    public function testConstructFailUninitialized() {
        Opus_Reviewer::init(array());

        $this->setExpectedException('Opus_Model_Exception');
        $reviewer = new Opus_Reviewer('foobar');
    }

    /**
     * @todo Add comment.
     */
    public function testConstruct() {
        $review_config = array('collections' => array(
                'testrole' => array(
                    '011' => array('admin'),
                ),
            ),
        );
        Opus_Reviewer::init($review_config);

        // Check if loading "known" reviewers works.
        $reviewer = new Opus_Reviewer('admin');

        // Check if loading "unknown" reviewers fails.
        $this->setExpectedException('Opus_Model_NotFoundException');
        $reviewer = new Opus_Reviewer('foobar');
    }

    /**
     * @todo Comment...
     */
    public function testFetchAllByCollection() {
        $review_config = array('collections' => array(
                'testrole' => array(
                    '01' => array('admin', 'tklein'),
                    '011' => array('foo', 'bar'),
                    '60'  => array('tklein', 'test'),
                ),
            ),
        );

        // Check if initialization works.
        Opus_Reviewer::init($review_config);

        // Define checks.
        $checks = array(
            '0'   => array(),
            '01'  => array('admin', 'tklein'),
            '011' => array('foo', 'bar'),
            '1'   => array(),
            '11'  => array(),
            '112' => array(),
            '60'  => array('tklein', 'test'),
        );

        foreach ($checks AS $number => $expected_reviewers) {
            $reviewer = Opus_Reviewer::fetchAllByCollection($this->_collections["$number"]);
            $this->assertNotNull($reviewer);
            $this->assertEquals($expected_reviewers, $reviewer);
        }
    }


    /**
     * @todo Comment...
     */
    public function testFetchAllByCollectionUnreferencedRole() {
        $review_config = array('collections' => array(
                'testrole' => array(
                    '01' => array('admin', 'tklein'),
                    '011' => array('foo', 'bar'),
                    '60'  => array('tklein', 'test'),
                ),
            ),
        );

        // Check if initialization works.
        Opus_Reviewer::init($review_config);

        $reviewer = Opus_Reviewer::fetchAllByCollection($this->_collections["u_011"]);
        $this->assertNotNull($reviewer);
        $this->assertEquals(array(), $reviewer);
    }

    /**
     * @todo Comment...
     */
    public function testFetchAllByDocumentSuccess() {
        $doc = new Opus_Document();
        $doc->setServerState('unpublished');
        $doc->setType('article');
        $doc->addCollection($this->_collections["011"]);
        $doc->addCollection($this->_collections["610"]);
        $doc->store();

        $review_config = array('collections' => array(
                'testrole' => array(
                    '01' => array('admin', 'tklein'),
                    '011' => array('foo', 'bar'),
                    '60'  => array('tklein', 'test'),
                ),
            ),
        );

        // Check if initialization works.
        Opus_Reviewer::init($review_config);

        $reviewer = Opus_Reviewer::fetchAllByDocument($doc);
        $this->assertNotNull($reviewer);
        $this->assertContains('foo', $reviewer);
        $this->assertContains('bar', $reviewer);
        $this->assertContains('tklein', $reviewer);
        $this->assertContains('test', $reviewer);
        $this->assertNotContains('admin', $reviewer);
    }

    /**
     * @todo Comment...
     */
    public function testFilterDocumentIds() {
        $doc = new Opus_Document();
        $doc->setServerState('unpublished');
        $doc->setType('article');
        $doc->addCollection($this->_collections["011"]);
        $doc->addCollection($this->_collections["610"]);
        $doc->store();

        $review_config = array('collections' => array(
                'testrole' => array(
                    '01' => array('admin', 'tklein'),
                    '011' => array('foo', 'bar'),
                    '60'  => array('tklein', 'test'),
                ),
            ),
        );

        // Check if initialization works.
        Opus_Reviewer::init($review_config);

        $reviewer = new Opus_Reviewer('tklein');
        $docIds = $reviewer->filterDocumentIds(array($doc->getId()));
        $this->assertContains($doc->getId(), $docIds);
    }

}
