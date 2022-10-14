<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Iprange;
use OpusTest\TestAsset\TestCase;

class IprangeTest extends TestCase
{
    /**
     * @var    Iprange
     * @access protected
     */
    protected $object;

    protected function setUp()
    {
        parent::setUp();
        $this->clearTables(false, ['ipranges']);
        $this->object = Iprange::new();
    }

    /**
     * @todo Implement testGetAll().
     */
    public function testGetAll()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    /**
     * @todo Implement testGetDisplayName().
     */
    public function testGetDisplayName()
    {
        // Remove the following lines when you implement this test.
        $this->markTestIncomplete(
            'This test has not been implemented yet.'
        );
    }

    public function testSetStartingIp()
    {
        $ipRange = Iprange::new();

        $ipRange->setStartingIp('127.0.0.1');

        $this->assertEquals('127.0.0.1', $ipRange->getStartingIp());
    }

    public function testStoreIprange()
    {
        $iprange = Iprange::new();
        $iprange->setStartingIp('127.0.0.1');
        $iprange->setEndingIp('127.0.0.100');
        $iprange->setName('Test Range');
        $rangeId = $iprange->store();

        $iprange = Iprange::get($rangeId);

        $this->assertEquals('127.0.0.1', $iprange->getStartingIp());
        $this->assertEquals('127.0.0.100', $iprange->getEndingIp());
        $this->assertEquals('Test Range', $iprange->getName());
        $this->assertCount(0, $iprange->getRole());
    }
}
