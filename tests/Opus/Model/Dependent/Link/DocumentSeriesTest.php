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
 * @category    Framework
 * @package     Tests
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Model_Dependent_Link_DocumentSeriesTest extends TestCase {

    public function testAssignDocSortOrder() {
        $s = new Opus_Series();
        $s->setTitle('test_series');
        $s->store();

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('I.');
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue($series[0]->getDocSortOrder() === '0');

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('II.');
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue($series[0]->getDocSortOrder() === '1');

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('IV.')->setDocSortOrder(4);
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue($series[0]->getDocSortOrder() === '4');

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('V.');
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue($series[0]->getDocSortOrder() === '5');

        $d = new Opus_Document();
        $d->addSeries($s)->setNumber('III.')->setDocSortOrder(3);
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue($series[0]->getDocSortOrder() === '3');

        $series[0]->setDocSortOrder(10);
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertTrue($series[0]->getDocSortOrder() === '10');

        $series[0]->setDocSortOrder(null);
        $d->store();

        $d = new Opus_Document($d->getId());
        $series = $d->getSeries();
        $this->assertFalse($series[0]->getDocSortOrder() === '11');
        $this->assertTrue($series[0]->getDocSortOrder() === '6');
    }
    
}