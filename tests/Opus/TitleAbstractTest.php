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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Collection
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest;

use Opus\Model2\Title;
use Opus\TitleAbstract;
use OpusTest\TestAsset\TestCase;

// TODO: Should be removed or replaced due to the new Model2/Title class
class TitleAbstractTest extends TestCase
{
    public function testConstruct()
    {
        $abstract = new TitleAbstract();

        $this->assertEquals('abstract', $abstract->getType());
        $this->assertNull($abstract->getLanguage());
        $this->assertNull($abstract->getValue());
    }

    public function testToArray()
    {
        $abstract = new TitleAbstract();

        $abstract->setLanguage('deu');
        $abstract->setValue('Deutscher Haupttitel');

        $data = $abstract->toArray();

        $this->assertEquals([
            'Language' => 'deu',
            'Type'     => 'abstract',
            'Value'    => 'Deutscher Haupttitel',
        ], $data);
    }

    public function testFromArray()
    {
        $abstract = TitleAbstract::fromArray([
            'Language' => 'deu',
            'Type'     => 'abstract',
            'Value'    => 'Deutscher Haupttitel',
        ]);

        $this->assertNotNull($abstract);
        $this->assertInstanceOf(Title::class, $abstract);

        $this->assertEquals('deu', $abstract->getLanguage());
        $this->assertEquals('abstract', $abstract->getType());
        $this->assertEquals('Deutscher Haupttitel', $abstract->getValue());
    }

    public function testFromArrayWithoutType()
    {
        $abstract = TitleAbstract::fromArray([
            'Language' => 'deu',
            'Value'    => 'Deutscher Haupttitel',
        ]);

        $this->assertNotNull($abstract);
        $this->assertInstanceOf(Title::class, $abstract);

        $this->assertEquals('deu', $abstract->getLanguage());
        $this->assertEquals('abstract', $abstract->getType());
        $this->assertEquals('Deutscher Haupttitel', $abstract->getValue());
    }

    public function testUpdateFromArray()
    {
        $abstract = new TitleAbstract();

        $abstract->updateFromArray([
            'Language' => 'deu',
            'Type'     => 'abstract',
            'Value'    => 'Deutscher Haupttitel',
        ]);

        $this->assertNotNull($abstract);
        $this->assertInstanceOf(Title::class, $abstract);

        $this->assertEquals('deu', $abstract->getLanguage());
        $this->assertEquals('abstract', $abstract->getType());
        $this->assertEquals('Deutscher Haupttitel', $abstract->getValue());
    }

    public function testUpdateFromArrayWithoutType()
    {
        $abstract = new TitleAbstract();

        $abstract->updateFromArray([
            'Language' => 'deu',
            'Value'    => 'Deutscher Haupttitel',
        ]);

        $this->assertNotNull($abstract);
        $this->assertInstanceOf(Title::class, $abstract);

        $this->assertEquals('deu', $abstract->getLanguage());
        $this->assertEquals('abstract', $abstract->getType());
        $this->assertEquals('Deutscher Haupttitel', $abstract->getValue());
    }
}
