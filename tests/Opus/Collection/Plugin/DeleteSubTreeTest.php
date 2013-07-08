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
 * @category    TODO
 * @package     TODO
 * @author      Edouard Simon (edouard.simon@zib.de)
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * 
 */
class Opus_Collection_Plugin_DeleteSubTreeTest extends TestCase {

    /**
     * @var Opus_Collection
     */
    protected $object;

    /**
     * SetUp method.  Inherits database cleanup from parent.
     */
    public function setUp() {
        parent::setUp();

        $this->_role_name = "role-name-" . rand();
        $this->_role_oai_name = "role-oainame-" . rand();

        $this->role_fixture = new Opus_CollectionRole();
        $this->role_fixture->setName($this->_role_name);
        $this->role_fixture->setOaiName($this->_role_oai_name);
        $this->role_fixture->setVisible(1);
        $this->role_fixture->setVisibleBrowsingStart(1);
        $this->role_fixture->store();

        $this->object = $this->role_fixture->addRootCollection();
        $this->object->setTheme('dummy');
        $this->role_fixture->store();
    }

    protected function tearDown() {
        if (is_object($this->role_fixture))
            $this->role_fixture->delete();
        parent::tearDown();
    }

    public function testPreDeleteUpdatesServerDateModifiedOnRelatedDocuments() {

        $root = $this->object;

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(0, count($root->getChildren()), 'Root collection without children should return empty array.');

        $child_1 = $root->addLastChild();
        $root->store();

        $doc1 = new Opus_Document();
        $doc1->addCollection($child_1);
        $docId1 = $doc1->store();
        $doc1ServerDateModified = $doc1->getServerDateModified();


        // FIXME: We have to reload model to get correct results!
        $root = new Opus_Collection($root->getId());

        $this->assertTrue(is_array($root->getChildren()));
        $this->assertEquals(1, count($root->getChildren()), 'Root collection should have one child.');

        $child_2 = $root->addLastChild();
        $root->store();
        $child_2Id = $child_2->getId();

        $doc2 = new Opus_Document();
        $doc2->addCollection($child_2);
        $docId2 = $doc2->store();
        $doc2ServerDateModified = $doc2->getServerDateModified();


        $child_1_1 = $child_1->addFirstChild();
        $child_1->store();
        $child_1_1Id = $child_1_1->getId();
        $doc1_1 = new Opus_Document();
        $doc1_1->addCollection($child_1_1);
        $docId1_1 = $doc1_1->store();
        $doc1_1ServerDateModified = $doc1_1->getServerDateModified();

        $root = new Opus_Collection($root->getId());

        $root->delete();

        $doc1Reloaded = new Opus_Document($docId1);
        $this->assertTrue($doc1Reloaded->getServerDateModified()->getZendDate()->getTimestamp() > $doc1ServerDateModified->getZendDate()->getTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');

        try {
            $child2Reloaded = new Opus_Collection($child_2Id);
            $this->fail('Expected child collection to be deleted');
        } catch (Opus_Model_NotFoundException $e) {
            
        }

        $doc2Reloaded = new Opus_Document($docId2);
        $this->assertTrue($doc2Reloaded->getServerDateModified()->getZendDate()->getTimestamp() > $doc2ServerDateModified->getZendDate()->getTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');

        try {
            $child1_1Reloaded = new Opus_Collection($child_1_1Id);
            $this->fail('Expected child collection to be deleted');
        } catch (Opus_Model_NotFoundException $e) {
            
        }

        $doc1_1Reloaded = new Opus_Document($docId1_1);
        $this->assertTrue($doc1_1Reloaded->getServerDateModified()->getZendDate()->getTimestamp() > $doc1_1ServerDateModified->getZendDate()->getTimestamp(), 'Expected document server_date_modfied to be changed after deletion of collection');

        
    }

}
