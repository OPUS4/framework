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
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * TODO
 *
 * @category    Framework
 * @package     Opus_Document
 * @subpackage  Plugin
 */
class Opus_Document_Plugin_XmlCacheTest extends TestCase {

    /**
     * Holds an instance of Opus_Db_DocumentXmlCache.
     *
     * @var Opus_Db_DocumentXmlCache
     */
    private $_cacheTable = null;

    /**
     *
     *
     * @return void
     */
    public function setUp() {

        parent::setUp();

        $this->_cacheTable = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentXmlCache');

    }

    public function testDisabledCachePlugin() {
        $doc = new Opus_Document();

        $this->setExpectedException('Opus_Model_Exception');
        $doc->unregisterPlugin('Opus_Document_Plugin_XmlCache');
        $this->fail('Plugin should stay disabled.');
    }

    /**
     *
     *
     * @return void
     */
    public function testCacheEntriesCreatedAfterDocumentIsStored() {
        $doc = new Opus_Document();
        $doc->setType('test');
        $doc->setServerState('unpublished');

        $result = $this->_cacheTable->fetchAll();
        $beforeStore = $result->count();

        $docId = $doc->store();

        $result = $this->_cacheTable->fetchAll();
        $afterStore = $result->count();

        $this->assertEquals(0, $afterStore - $beforeStore, 'Expecting same cache entry count.');
//        $this->assertEquals(1, $afterStore - $beforeStore, 'Expecting 1 cache entries more.');

//        $this->assertEquals($docId, $result[0]['document_id'], 'Expecting right document data for first entry.');
//        $this->assertEquals($docId, $result[1]['document_id'], 'Expecting right document data for second entry.');
    }

    /**
     *
     *
     * @return void
     */
    public function testCacheEntriesAreDeletedAfterDocumentDelete() {
        $doc = new Opus_Document();
        $doc->setType('test');
        $doc->setServerState('unpublished');

        $docId = $doc->store();

        $result = $this->_cacheTable->fetchAll();
        $beforeDelete = $result->count();

        $doc->deletePermanent();

        $result = $this->_cacheTable->fetchAll();
        $afterDelete = $result->count();

        $this->assertEquals(0, $afterDelete - $beforeDelete, 'Expecting same cache entry count.');
//        $this->assertEquals(1, $beforeDelete - $afterDelete, 'Expecting 1 cache entries less.');

    }

}

