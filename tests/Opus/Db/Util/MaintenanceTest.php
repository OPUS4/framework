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
 * @copyright   Copyright (c) 2026, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Db\Util;

use Opus\Common\Document;
use Opus\Db\Util\Maintenance;
use OpusTest\TestAsset\TestCase;

class MaintenanceTest extends TestCase
{
    /** @var Maintenance */
    private $maintenance;

    /** @var array */
    private $documents;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false);

        $this->maintenance = new Maintenance();

        $doc = Document::new();
        $doc->setCompletedDate('2026-01-24');
        $this->documents[0] = $doc->store();

        $doc = Document::new();
        $doc->setCompletedDate('2024-02-17T00:00:00+01:00');
        $this->documents[1] = $doc->store();

        $doc = Document::new();
        $doc->setPublishedDate('2022-03-21T00:00:00+01:00');
        $this->documents[2] = $doc->store();

        $doc = Document::new();
        $doc->setEmbargoDate('2025-10-20');
        $this->documents[3] = $doc->store();
    }

    public function testFixDateValues()
    {
        $this->maintenance->fixDateValues();

        $dates = $this->maintenance->checkDateValues();

        $this->assertCount(0, $dates);

        $doc = Document::get($this->documents[0]);
        $this->assertEquals('2026-01-24', $doc->getCompletedDate());

        $doc = Document::get($this->documents[1]);
        $this->assertEquals('2024-02-17', $doc->getCompletedDate());

        $doc = Document::get($this->documents[2]);
        $this->assertEquals('2022-03-21', $doc->getPublishedDate());

        $doc = Document::get($this->documents[3]);
        $this->assertEquals('2025-10-20', $doc->getEmbargoDate());
    }

    public function testCheckDateValues()
    {
        $dates = $this->maintenance->checkDateValues();

        $this->assertCount(2, $dates);
        $this->assertArrayHasKey('completed_date', $dates);
        $this->assertArrayHasKey('published_date', $dates);
        $this->assertEquals(1, $dates['completed_date']);
        $this->assertEquals(1, $dates['published_date']);
    }
}
