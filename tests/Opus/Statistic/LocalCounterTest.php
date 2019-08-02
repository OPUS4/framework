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
 * @package     Opus_Statistic
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test for Opus_Statistic_LocalCounter.
 *
 * @package Opus_Statistic
 * @category Tests
 *
 * @group LocalCounterTest
 */
class Opus_Statistic_LocalCounterTest extends TestCase
{

    /**
     * Document to count on :)
     *
     * @var Opus_Document
     */
    protected $_document = null;

    /**
     * Provide clean documents and statistics table and remove temporary files.
     * Create document for counting.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        $path = Zend_Registry::get('temp_dir') . '~localstat.xml';
        @unlink($path);

        $this->_document = new Opus_Document();
        $this->_document->setType("doctoral_thesis");
        $this->_document->store();

        //setting server globals
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'bla';
        $_SERVER['REDIRECT_STATUS'] = 200;
    }

    /**
     * Clean up tables, remove temporary files.
     *
     * @return void
     */
    public function tearDown()
    {
        parent::tearDown();

        $path = Zend_Registry::get('temp_dir') . '~localstat.xml';
        @unlink($path);
    }

    /**
     * Test getting singleton instance.
     *
     * @return void
     */
    public function testGetInstance()
    {
        $lc = Opus_Statistic_LocalCounter::getInstance();
        $this->assertNotNull($lc, 'Expected instance');
        $this->assertInstanceOf('Opus_Statistic_LocalCounter', $lc, 'Expected object of type Opus_Statistic_LocalCounter.');
    }

    /**
     * Simulate single click and check if the document counter gets increased.
     *
     * @return void
     */
    public function testCountSingleClick()
    {
        //$this->markTestIncomplete('Test and CUT still under development.');

        $docId = $this->_document->getId();

        // issue counting request
        $lc = Opus_Statistic_LocalCounter::getInstance();
        $lc->count($docId, 1, 'files');

        // check database table for counting value
        $ods = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentStatistics');
        $rows = $ods->fetchAll()->toArray();

        $this->assertEquals(1, count($rows), 'Expect 1 statistic entry.');

        foreach ($rows as $row) {
            //$this->assertFalse(true,print_r($row, true));
            $this->assertEquals(1, $row['count'], 'Expect exactly one view to this document');
            $this->assertEquals('files', $row['type'], 'Expect type = \'files\'');
        }
    }

    public function testCountSingleFrontdoorClick()
    {
        //$this->markTestIncomplete('Test and CUT still under development.');

        $docId = $this->_document->getId();

        // issue counting request
        $lc = Opus_Statistic_LocalCounter::getInstance();
        $lc->count($docId, null, 'frontdoor');

        // check database table for counting value
        $ods = Opus_Db_TableGateway::getInstance("Opus_Db_DocumentStatistics");
        $rows = $ods->fetchAll()->toArray();

        $this->assertEquals(1, count($rows), 'Expect 1 statistic entry.');

        foreach ($rows as $row) {
            $this->assertEquals(1, $row['count'], 'Expect exactly one view to this document');
            $this->assertEquals('frontdoor', $row['type'], 'Expect type = \'frontdoor\'');
        }
    }
}
