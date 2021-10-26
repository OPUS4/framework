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
 * @package     Opus\Document
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Document\Plugin;

use Opus\Db\DocumentXmlCache;
use Opus\Db\TableGateway;
use Opus\Document;
use OpusTest\TestAsset\TestCase;

/**
 * TODO
 *
 * @category    Framework
 * @package     Opus\Document
 * @subpackage  Plugin
 */
class XmlCacheTest extends TestCase
{

    /**
     * Holds an instance of Opus\Db\DocumentXmlCache.
     *
     * @var DocumentXmlCache
     */
    private $_cacheTable = null;

    /**
     *
     *
     * @return void
     */
    public function setUp()
    {

        parent::setUp();

        $this->clearTables(false, ['document_xml_cache', 'documents']);

        $this->_cacheTable = TableGateway::getInstance('Opus\Db\DocumentXmlCache');
    }

    public function testDisabledCachePlugin()
    {
        $this->markTestSkipped('Cache is re-enabled');
        $doc = new Document();

        $this->setExpectedException('Opus\Model\ModelException');
        $doc->unregisterPlugin('Opus\Document\Plugin\XmlCache');
        $this->fail('Plugin should stay disabled.');
    }

    /**
     *
     *
     * @return void
     */
    public function testCacheEntriesCreatedAfterDocumentIsStored()
    {
        $doc = new Document();
        $doc->setType('test');
        $doc->setServerState('unpublished');

        $result = $this->_cacheTable->fetchAll();
        $beforeStore = $result->count();

        $docId = $doc->store();

        $result = $this->_cacheTable->fetchAll();
        $afterStore = $result->count();
//        $this->assertEquals(0, $afterStore - $beforeStore, 'Expecting same cache entry count.');
        $this->assertEquals(1, $afterStore - $beforeStore, 'Expecting 1 cache entries more.');

        $this->assertEquals($docId, $result[0]['document_id'], 'Expecting right document data for first entry.');
//        $this->assertEquals($docId, $result[1]['document_id'], 'Expecting right document data for second entry.');
    }

    /**
     *
     *
     * @return void
     */
    public function testCacheEntriesAreDeletedAfterDocumentDelete()
    {
        $doc = new Document();
        $doc->setType('test');
        $doc->setServerState('unpublished');

        $docId = $doc->store();

        $result = $this->_cacheTable->fetchAll();
        $beforeDelete = $result->count();

        $doc->delete();

        $result = $this->_cacheTable->fetchAll();
        $afterDelete = $result->count();

//        $this->assertEquals(0, $afterDelete - $beforeDelete, 'Expecting same cache entry count.');
        $this->assertEquals(1, $beforeDelete - $afterDelete, 'Expecting 1 cache entries less.');
    }
}
