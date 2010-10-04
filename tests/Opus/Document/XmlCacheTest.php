<?php
/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: XmlCacheTest.php 5765 2010-06-07 14:15:00Z claussni $
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

        $this->assertEquals(1, $afterStore - $beforeStore, 'Expecting 1 cache entries more.');

        $this->assertEquals($docId, $result[0]['document_id'], 'Expecting right document data for first entry.');
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

        $this->assertEquals(1, $beforeDelete - $afterDelete, 'Expecting 1 cache entries less.');

    }

}

