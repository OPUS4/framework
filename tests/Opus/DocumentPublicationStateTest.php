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
 * @copyright   Copyright (c) 2024, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Config;
use Opus\Common\Document;
use Opus\Model\DbException;
use OpusTest\TestAsset\TestCase;

/**
 * Test cases for class Opus\Document and field PublicationState.
 */
class DocumentPublicationStateTest extends TestCase
{
    /**
     * Set up test fixture.
     */
    public function setUp(): void
    {
        // Set up a mock language list.
        $list = ['de' => 'Test_Deutsch', 'en' => 'Test_Englisch', 'fr' => 'Test_Französisch'];
        Config::getInstance()->setAvailableLanguages($list);

        parent::setUp();

        $this->clearTables(false);
    }

    public function tearDown(): void
    {
        $document = Document::new();
        $document->setDefaultPlugins(null);

        parent::tearDown();
    }

    public function testDefaultPublicationStateNull()
    {
        $doc = Document::new();

        $doc = Document::get($doc->store());

        $this->assertNull($doc->getPublicationState());
    }

    public function testStorePublicationState()
    {
        $doc = Document::new();

        $doc->setPublicationState('submittedVersion');

        $docId = $doc->store();

        $doc = Document::get($docId);

        $this->assertEquals('submittedVersion', $doc->getPublicationState());
    }

    public function testStorePublicationStateInvalidValue()
    {
        $doc = Document::new();

        $doc->setPublicationState('invalidState');

        $this->expectException(DbException::class);
        $this->expectExceptionMessage('truncated for column \'publication_state\'');

        $doc->store();
    }
}
