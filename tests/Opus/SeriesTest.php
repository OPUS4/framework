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
 * @author      Susanne Gottwald <gottwald@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Series.
 *
 * @package Opus
 * @category Tests
 *
 * @group SeriesTest
 */
class Opus_SeriesTest extends TestCase {

    /**
     * Test if a document series can be retrieved by getAll().
     *
     */
    public function testCreateRetrieveAndDeleteSets() {
        $this->assertEquals(0, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');
        
        $numberOfSetsToCreate = 3;
        $ids = array();
        for ($i = 0; $i < $numberOfSetsToCreate; $i++) {
            $set = new Opus_Series();
            $set->setTitle('New document set ' . $i);
            $set->store();
            array_push($ids, $set->getId());
        }

        $this->assertEquals($numberOfSetsToCreate, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');

        // cleanup
        foreach ($ids as $id) {
            $s = new Opus_Series($id);
            $s->delete();
        }

        $this->assertEquals(0, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');
    }

    public function testAssignSetToDocumentWithoutNumber() {        
        $d = new Opus_Document();
        $d->store();
        
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document($d->getId());
        $d->addSeries($s);

        // TODO: uncomment the following line after resolving OPUSVIER-2033
        // $this->setExpectedException('Opus_Model_Exception');
        $d->store();
        
        $this->assertEquals(1, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');

        // cleanup
        $d->deletePermanent();
        $s->delete();
    }

    public function testAssignSetToDocumentWithNumber() {        
        $d = new Opus_Document();
        $d->store();
        
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document($d->getId());
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $this->assertEquals(1, count(Opus_Series::getAll()), 'Wrong number of objects retrieved.');

        // cleanup        
        $d->deletePermanent();
        $s->delete();
    }


    /**
     * 
     * "CRUD-completness tests on Opus_Series"
     *
     */
    
    public function testCreateSeriesWithoutTitle() {
        $s = new Opus_Series();
        $this->setExpectedException('Opus_Model_Exception');
        $s->store();
    }

    public function testCreateSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getTitle() === 'foo');
    }

    public function testUpdateSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $s = new Opus_Series($s->getId());
        $s->setTitle('bar');
        $s->store();

        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getTitle() === 'bar');
    }

    public function testDeleteSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $id = $s->getId();
        $s = new Opus_Series($s->getId());
        $this->assertTrue($s->getTitle() === 'foo');

        $s->delete();
        
        $this->setExpectedException('Opus_Model_NotFoundException');
        $s = new Opus_Series($id);
    }

    
    /**
     *
     * tests in conjunction with class Opus_Model_Dependent_Link_DocumentSeries
     * 
     */

    public function testAssignDocumentToSeriesTwice() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($s)->setNumber('2');

        $this->setExpectedException('Opus_Model_Exception');
        $d->store();       
    }

    public function testAssignDocumentToSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $t = new Opus_Series();
        $t->setTitle('bar');
        $t->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->addSeries($t)->setNumber('2');
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue(count($series) === 2);

        $s = $series[0];
        $this->assertTrue($s->getTitle() === 'foo');
        
        $s = $series[1];
        $this->assertTrue($s->getTitle() === 'bar');        
    }


    public function testDeleteReferencedSeries() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $this->assertTrue(count($d->getSeries()) === 1);

        $s->delete();

        $d = new Opus_Document($d->getId());
        $this->assertTrue(count($d->getSeries()) === 0);
    }

    public function testDeleteSeriesAssignment() {
        $s = new Opus_Series();
        $s->setTitle('foo');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('1');
        $d->store();

        $this->assertTrue(count($d->getSeries()) === 1);

        $d->setSeries(null);
        $d->store();

        $d = new Opus_Document($d->getId());
        $this->assertTrue(count($d->getSeries()) === 0);
    }

}
