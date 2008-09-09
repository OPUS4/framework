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
 * @package     Opus_Document
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Document_Storage.
 *
 * @category    Tests
 * @package     Opus_Document
 *
 */
class Opus_Document_StorageTest extends PHPUnit_Framework_TestCase {


    /**
     * Setup test environment
     *
     * @return void
     */
    public function setUp() {
        /* $registry = Zend_Registry::getInstance();
         $adapter = $registry->get('db_adapter');
         $adapter->deleteTable('documents');*/
        TestHelper::clearTable('document_title_abstracts');
        TestHelper::clearTable('documents');
        TestHelper::clearTable('licences');
        
    }

    /**
     * Cleanup test environment
     *
     * @return void
     */
    public function tearDown() {
    }

    /**
     * Adds data of a - nonvalid - document (all fields are filled) to database table `documents`
     *
     * @return void
     */
    public function testAddToDocuments() {
        //$this->fail('Opus_Db_Licences is not defined yet.');
        $licences = new Opus_Db_Licences();
        $licencesId = $licences->insert(array('name_long' => 'test licence'));
        //TODO assertions for licence

        $data =
        array(
                'licences_id' => $licencesId,
                'range_id' => '123',
                'completed_date' => '2008-01-01',
                'completed_year' => '2008',
                'contributing_corporation' => 'test corporation',
                'creating_corporation' => 'test corporation',
                'date_accepted' => '2008-01-01',
                'document_type' => 'article',
                'edition' => '1',
                'issue' => '1/2008',
                'language' => 'de',
                'non_institute_affiliation' => 'foreign institute',
                'page_first' => '1',
                'page_last' => '100',
                'page_number' => '100',
                'publication_status' => '1',
                'published_date' => '2008-01-01',
                'published_year' => '2008',
                'publisher_name' => 'test publisher',
                'publisher_place' => 'Saarbrücken',
                'publisher_university' => '1',
                'reviewed' => 'open',
                'server_date_modified' => '2008-01-01',
                'server_date_published' => '2008-01-01',
                'server_date_unlocking' => '2008-01-01',
                'server_date_valid' => '2008-01-01',
                'source' => 'teststring',
                'swb_id' => '12345',
                'vg_wort_pixel_url' => 'vg_wort_uri',
                'volume' => '2008'
                );

        $storage = new Opus_Document_Storage($data);
        $id = $storage->saveDocumentData();
        $documents = new Opus_Db_Documents();
        $rowSet = $documents->find($id);
        $this->assertTrue($rowSet instanceof Zend_Db_Table_Rowset_Abstract);
        $this->assertTrue($rowSet->count() == 1, 'Expected 1 dataset in table');
        $row = $rowSet->current();
        $rowData = $row->toArray();
        $this->assertEquals($rowData['licences_id'], $licencesId);
        $this->assertEquals($rowData['range_id'], '123');
        $this->assertContains('2008-01-01', $rowData['completed_date']);
        $this->assertEquals($rowData['completed_year'], '2008');
        $this->assertEquals($rowData['contributing_corporation'], 'test corporation');
        $this->assertEquals($rowData['creating_corporation'], 'test corporation');
        $this->assertContains('2008-01-01', $rowData['date_accepted']);
        $this->assertEquals($rowData['document_type'], 'article');
        $this->assertEquals($rowData['edition'], '1');
        $this->assertEquals($rowData['issue'], '1/2008');
        $this->assertEquals($rowData['language'], 'de');
        $this->assertEquals($rowData['non_institute_affiliation'], 'foreign institute');
        $this->assertEquals($rowData['page_first'], '1');
        $this->assertEquals($rowData['page_last'], '100');
        $this->assertEquals($rowData['page_number'], '100');
        $this->assertEquals($rowData['publication_status'], '1');
        $this->assertContains('2008-01-01', $rowData['published_date']);
        $this->assertEquals($rowData['published_year'], '2008');
        $this->assertEquals($rowData['publisher_name'], 'test publisher');
        $this->assertEquals($rowData['publisher_place'], 'Saarbrücken');
        $this->assertEquals($rowData['publisher_university'], '1');
        $this->assertEquals($rowData['reviewed'], 'open');
        $this->assertContains('2008-01-01', $rowData['server_date_modified']);
        $this->assertContains('2008-01-01', $rowData['server_date_published']);
        $this->assertContains('2008-01-01', $rowData['server_date_unlocking']);
        $this->assertContains('2008-01-01', $rowData['server_date_valid']);
        $this->assertEquals($rowData['source'], 'teststring');
        $this->assertEquals($rowData['swb_id'], '12345');
        $this->assertEquals($rowData['vg_wort_pixel_url'], 'vg_wort_uri');
        $this->assertEquals($rowData['volume'], '2008');
    }
    
    public function testAddTitle() {
        $data =
        array(
                'document_type' => 'article',
                array('title_abstract_type' => 'main', 'title_abstract_value' => 'main title', 'title_abstract_language' => 'de')
        );
        $storage = new Opus_Document_Storage($data);
        $id = $storage->saveDocumentData();
        
        $title_abstract = new Opus_Db_DocumentTitleAbstracts();
        $where = $title_abstract->getAdapter()->quoteInto('documents_id = ?', $id);
        $row = $title_abstract->fetchRow($where);
        $this->assertTrue($row instanceof Zend_Db_Table_Row_Abstract, 'Object of type Zend_Db_Table_Row_Abstract expected');
        $this->assertEquals($row->title_abstract_type, 'main');
        $this->assertEquals($row->title_abstract_value, 'main title');
        $this->assertEquals($row->title_abstract_language, 'de');
    }
    
    
}
