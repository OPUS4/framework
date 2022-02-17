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
 * @category    Tests
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest;

use Opus\Reference;
use OpusTest\TestAsset\TestCase;

use function count;

/**
 * TODO What would be meaningful and useful tests for this class?
 */
class ReferenceTest extends TestCase
{
    public function testConstructor()
    {
        $ref = new Reference();
    }

    public function testGetDefaultsForType()
    {
        $ref = new Reference();

        $defaults = $ref->getField('Type')->getDefault();

        $this->assertEquals(10, count($defaults));
        $this->assertContains('isbn', $defaults);
        $this->assertContains('urn', $defaults);
    }

    public function testGetDefaultsForRelation()
    {
        $ref = new Reference();

        $defaults = $ref->getField('Relation')->getDefault();

        $this->assertEquals(3, count($defaults));
        $this->assertContains('updates', $defaults);
        $this->assertContains('other', $defaults);
    }

    public function testToArray()
    {
        $ref = new Reference();
        $ref->setValue('146');
        $ref->setLabel('Previous version');
        $ref->setRelation('updates');
        $ref->setType('opus4id');

        $data = $ref->toArray();

        $this->assertEquals([
            'Value'    => '146',
            'Label'    => 'Previous version',
            'Relation' => 'updates',
            'Type'     => 'opus4id',
        ], $data);
    }

    public function testFromArray()
    {
        $ref = Reference::fromArray([
            'Value'    => '146',
            'Label'    => 'Previous version',
            'Relation' => 'updates',
            'Type'     => 'opus4id',
        ]);

        $this->assertNotNull($ref);
        $this->assertInstanceOf(Reference::class, $ref);
        $this->assertEquals('146', $ref->getValue());
        $this->assertEquals('Previous version', $ref->getLabel());
        $this->assertEquals('updates', $ref->getRelation());
        $this->assertEquals('opus4id', $ref->getType());
    }

    public function testUpdateFromArray()
    {
        $ref = new Reference();

        $ref->updateFromArray([
            'Value'    => '146',
            'Label'    => 'Previous version',
            'Relation' => 'updates',
            'Type'     => 'opus4id',
        ]);

        $this->assertNotNull($ref);
        $this->assertInstanceOf(Reference::class, $ref);
        $this->assertEquals('146', $ref->getValue());
        $this->assertEquals('Previous version', $ref->getLabel());
        $this->assertEquals('updates', $ref->getRelation());
        $this->assertEquals('opus4id', $ref->getType());
    }
}
