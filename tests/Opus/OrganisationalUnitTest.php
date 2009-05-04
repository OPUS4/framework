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
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_OrganisationalUnit.
 *
 * @package Opus
 * @category Tests
 *
 * @group OrganisationalUnitTest
 *
 */
class Opus_OrganisationalUnitTest extends PHPUnit_Framework_TestCase {

    
    /**
     * Drop all collection related tables from the database.
     *
     * @return void
     */
    private function __dropCollectionTables() {
        $dba = Zend_Registry::get('db_adapter');
        $tables = $dba->listTables();

        // filter collection tables
        $linkTables = array();
        $contentTables = array();
        $replacementTables = array();
        $structureTables = array();
        foreach ($tables as $table) {
            if ( 1 === preg_match('/^link_documents_collections_/', $table) ) {
                $linkTables[] = $table;
            } 
            if ( 1 === preg_match('/^collections_contents_/', $table) ) {
                $contentTables[] = $table;
            }
            if ( 1 === preg_match('/^collections_replacement_/', $table) ) {
                $replacementTables[] = $table;
            }
            if ( 1 === preg_match('/^collections_structure_/', $table) ) {
                $structureTables[] = $table;
            }
        }

        // truncate collections_roles
        TestHelper::clearTable('collections_roles');
        
        // drop tables in right order
        $dropTables = array();
        $dropTables = array_merge($dropTables, $structureTables);
        $dropTables = array_merge($dropTables, $replacementTables);
        $dropTables = array_merge($dropTables, $linkTables);
        $dropTables = array_merge($dropTables, $contentTables);
        $dropTables[] = 'oa_id_map';
        foreach ($dropTables as $table) {
            $dba->query('drop table if exists ' . $table);
        }
        
    }


    /**
     * Set up test fixture.
     *
     * @return void
     */
    public function setUp() {
        $this->__dropCollectionTables();
        Opus_Collection_Information::cleanup();
    }

    /**
     * Tear down test fixture.
     *
     * @return void
     */
    public function tearDown() {
        $this->__dropCollectionTables();
        Opus_Collection_Information::cleanup();        
        $reg = Zend_Registry::getInstance();
        if ($reg->isRegistered('Opus_Db_OaIdMap')) {
            $reg->offsetUnset('Opus_Db_OaIdMap');
        }
    }

    /**
     * Test if an instance of Opus_OrganisationlUnit can be created.
     *
     * @return void
     */
    public function testCreation() {
        $ou = new Opus_OrganisationalUnit;
    }
    
    /**
     * Test that persisting an Organisational Unit returns an Id number.
     *
     * @return void
     */
    public function testPersistingReturnsId() {
        $ou = new Opus_OrganisationalUnit;
        $ou->setName('FooOrganisation');
        $id = $ou->store();
        $this->assertTrue(is_int($id), 'Store does not return an integer Id.');
    }
    
    /**
     * Test if an Organisational Unit can be retrieved from a datastore by
     * passing its storage Id to the constructor.
     *
     * @return void
     */
    public function testConstructionById() {
        $ou = new Opus_OrganisationalUnit;
        $ou->setName('SLUB')
            ->setPostalAddress('Zellescher Weg 18')
            ->setHomepage('www.slub-dresden.de');
        $id = $ou->store();
        
        $this->assertNotNull($id, 'Returned Id is not expected to be null.');

        $ou2 = new Opus_OrganisationalUnit($id);
        
        $this->assertEquals($ou2->toArray(), $ou->toArray(), 'Retrieved object is different from stored object.');
    }
    
    
    /**
     * Test if a Organisational Unit Model can be loaded, modified and stored.
     *
     * @return void
     */
    public function testStoringOfModifiedModel() {
        $ou = new Opus_OrganisationalUnit;
        $ou->setName('BULS');
        $id = $ou->store();
        
        $ou2 = new Opus_OrganisationalUnit($id);
        $ou2->setName('SLUB');
        $ou2->store();
        
        $ou3 = new Opus_OrganisationalUnit($id);
        $this->assertEquals($ou2->toArray(), $ou3->toArray(), 'Retrieved object is different from stored object.');
    }
    
    
    /**
     * The Model dynamicly creates an CollectionRole object when storing.
     * This test ensures that only a single CollectionRole object is created.
     *
     * @return void
     */
    public function testMultipleObjectsOnlyOneCollectionRole() {
        $ou = new Opus_OrganisationalUnit;
        $ou->setName('Org1')->store();
        
        $ou2 = new Opus_OrganisationalUnit;
        $ou2->setName('Org2')->store();
        
        $roles = Opus_Collection_Information::getAllCollectionRoles();
        $this->assertEquals(1, count($roles), 'More than one CollectionRole object dynamicly created.');        
    }
    
    /**
     * Test if adding a child Organisational Unit works.
     *
     * @return void
     */
    public function testAddSubdivision() {
        $ou = new Opus_OrganisationalUnit;
        $ou->setName('Org1')->store();
        $child = $ou->addSubdivision('SubDivision');

        $subs = $ou->getSubdivisions();
        $this->assertNotNull($subs, 'No subdivisions returned.');
        $this->assertEquals(1, count($subs), 'Expect exactly one subdivision.');
        $this->assertArrayHasKey('SubDivision', $subs, 'Returned array is missing key for added subdivision.');

        $sub = $subs['SubDivision'];
        $this->assertTrue($sub instanceof Opus_OrganisationalUnit, 'Returned subdivision model has wrong type.');
    }
    
    /**
     * Test if appended subdivision is still appended after storing the Organisational Unit model.
     *
     * @return void
     */
    public function testGetSubdivisionsFromDatabase() {
        $ou = new Opus_OrganisationalUnit;
        $ou->setName('Org1');
        $id = $ou->store();

        $child = $ou->addSubdivision('SubDivision');

        $ou = new Opus_OrganisationalUnit($id);
        $subs = $ou->getSubdivisions();
        $this->assertNotNull($subs, 'No subdivisions returned.');
        $this->assertEquals(1, count($subs), 'Expect exactly one subdivision.');
        $this->assertArrayHasKey('SubDivision', $subs, 'Returned array is missing key for added subdivision.');

        $sub = $subs['SubDivision'];
        $this->assertTrue($sub instanceof Opus_OrganisationalUnit, 'Returned subdivision model has wrong type.');
    }
    
    /**
     * Test if multiple sub-sub devisions can be appended.
     *
     * @return void
     */
    public function testAddSubSubDivision() {
        $ou = new Opus_OrganisationalUnit;
        $ou->setName('Org1');
        $ou->store();

        $sub = $ou->addSubdivision('SubDivision');
        $subsub = $sub->addSubdivision('SubSubDivision');
        
        $ous = $ou->getSubdivisions();
        $subs = $sub->getSubdivisions();
        $subsubs = $subsub->getSubdivisions();
        
        $this->assertEquals(1, count($ous), 'Expect one subdivision object for root Organisational Unit.');        
        $this->assertEquals(1, count($subs), 'Expect one subdivision object for sub Organisational Unit.');        
        $this->assertEquals(0, count($subsubs), 'Expect no subdivision objects for sub-sub Organisational Unit.');        
    }
}


