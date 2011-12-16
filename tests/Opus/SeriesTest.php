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
     * Test if a document set can be retrieved by getAll().
     *
     */
    public function testCreateRetrieveAndDeleteSets() {
        $result = Opus_Series::getAll();
        $this->assertEquals(0, count($result), 'Wrong number of objects retrieved.');
        
        $numberOfSetsToCreate = 3;
        $ids = array();
        for ($i = 0; $i < $numberOfSetsToCreate; $i++) {
            $set = new Opus_Series();
            $set->setTitle('New document set ' . $i);
            $set->store();
            array_push($ids, $set->getId());
        }
        $result = Opus_Series::getAll();
        $this->assertEquals($numberOfSetsToCreate, count($result), 'Wrong number of objects retrieved.');

        // cleanup
        foreach ($ids as $id) {
            $s = new Opus_Series($id);
            $s->delete();
        }

        $result = Opus_Series::getAll();
        $this->assertEquals(0, count($result), 'Wrong number of objects retrieved.');
    }

    public function testAssignSetToDocumentWithoutNumber() {
        $d = new Opus_Document();
        $d->store();
        $s = new Opus_Series();
        $s->setTitle('foo');
        $d->addSeries($s);
        $d->store();

        // cleanup
        //$s->delete();
        $d->deletePermanent();
    }

    public function testAssignSetToDocumentWithNumber() {        
        $d = new Opus_Document();
        $d->store();
        
        $s = new Opus_Series();
        $s->setTitle('foo');
        $l = $d->addSeries($s);
        $l->setNumber(1);
        $d->store();

        // cleanup
       // $s->delete();
        $d->deletePermanent();
    }

}
