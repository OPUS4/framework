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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Tests
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Model\Dependent\Link;

use Opus\Document;
use Opus\Model\Dependent\Link\DocumentSeries;
use Opus\Series;
use OpusTest\TestAsset\TestCase;

class DocumentSeriesTest extends TestCase
{
    public function testAssignDocSortOrder()
    {
        $s = new Series();
        $s->setTitle('test_series');
        $s->store();

        $d = new Document();
        $d->addSeries($s)->setNumber('I.');
        $d->store();

        $d      = new Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(0, $series[0]->getDocSortOrder());

        $d = new Document();
        $d->addSeries($s)->setNumber('II.');
        $d->store();

        $d      = new Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(1, $series[0]->getDocSortOrder());

        $d = new Document();
        $d->addSeries($s)->setNumber('IV.')->setDocSortOrder(4);
        $d->store();

        $d      = new Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(4, $series[0]->getDocSortOrder());

        $d = new Document();
        $d->addSeries($s)->setNumber('V.');
        $d->store();

        $d      = new Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(5, $series[0]->getDocSortOrder());

        $d = new Document();
        $d->addSeries($s)->setNumber('III.')->setDocSortOrder(3);
        $d->store();

        $d      = new Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(3, $series[0]->getDocSortOrder());

        $series[0]->setDocSortOrder(10);
        $d->store();

        $d      = new Document($d->getId());
        $series = $d->getSeries();
        $this->assertEquals(10,$series[0]->getDocSortOrder());

        $series[0]->setDocSortOrder(null);
        $d->store();

        $d      = new Document($d->getId());
        $series = $d->getSeries();
        $this->assertNotEquals(11, $series[0]->getDocSortOrder());
        $this->assertEquals(6, $series[0]->getDocSortOrder());
    }

    public function testToArray()
    {
        $seriesLink = new DocumentSeries();

        $seriesLink->setModel(new Series()); // Fields are proxied for Opus\Series object
        $seriesLink->setNumber('VI');
        $seriesLink->setDocSortOrder(4);
        $seriesLink->setTitle('Schriftenreihe');
        $seriesLink->setInfobox('Beschreibung');
        $seriesLink->setVisible(1);
        $seriesLink->setSortOrder(2);

        $data = $seriesLink->toArray();

        $this->assertEquals([
            'Title'        => 'Schriftenreihe',
            'Infobox'      => 'Beschreibung',
            'Visible'      => 1,
            'SortOrder'    => 2,
            'Number'       => 'VI',
            'DocSortOrder' => 4,
        ], $data);
    }

    public function testFromArray()
    {
        $seriesLink = DocumentSeries::fromArray([
            'Title'        => 'Schriftenreihe',
            'Infobox'      => 'Beschreibung',
            'Visible'      => 1,
            'SortOrder'    => 2,
            'Number'       => 'VI',
            'DocSortOrder' => 4,
        ]);

        $this->assertNotNull($seriesLink);
        $this->assertInstanceOf(DocumentSeries::class, $seriesLink);

        $this->assertEquals('Schriftenreihe', $seriesLink->getTitle());
        $this->assertEquals('Beschreibung', $seriesLink->getInfobox());
        $this->assertEquals(1, $seriesLink->getVisible());
        $this->assertEquals(2, $seriesLink->getSortOrder());
        $this->assertEquals('VI', $seriesLink->getNumber());
        $this->assertEquals(4, $seriesLink->getDocSortOrder());
    }

    public function testUpdateFromArray()
    {
        $seriesLink = new DocumentSeries();

        $seriesLink->updateFromArray([
            'Title'        => 'Schriftenreihe',
            'Infobox'      => 'Beschreibung',
            'Visible'      => 1,
            'SortOrder'    => 2,
            'Number'       => 'VI',
            'DocSortOrder' => 4,
        ]);

        $this->assertNotNull($seriesLink);
        $this->assertInstanceOf(DocumentSeries::class, $seriesLink);

        $this->assertEquals('Schriftenreihe', $seriesLink->getTitle());
        $this->assertEquals('Beschreibung', $seriesLink->getInfobox());
        $this->assertEquals(1, $seriesLink->getVisible());
        $this->assertEquals(2, $seriesLink->getSortOrder());
        $this->assertEquals('VI', $seriesLink->getNumber());
        $this->assertEquals(4, $seriesLink->getDocSortOrder());
    }
}
