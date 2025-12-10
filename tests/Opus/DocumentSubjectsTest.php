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
 * @copyright   Copyright (c) 2023, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Config;
use Opus\Common\Document;
use Opus\Common\Subject;
use OpusTest\TestAsset\TestCase;

use function strtolower;
use function strtoupper;

/**
 * Test cases for class Opus\Document.
 */
class DocumentSubjectsTest extends TestCase
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

    public function testHasSubject()
    {
        $doc = Document::new();

        $subjectValue = 'OA-Green';

        $doc->addSubject(Subject::new()
            ->setValue($subjectValue)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $this->assertCount(1, $doc->getSubject());

        $this->assertTrue($doc->hasSubject($subjectValue));
        $this->assertTrue($doc->hasSubject(strtolower($subjectValue)));
        $this->assertTrue($doc->hasSubject(strtoupper($subjectValue)));
        $this->assertFalse($doc->hasSubject('OA_Green'));
    }

    public function testHasSubjectWithType()
    {
        $doc = Document::new();

        $subjectValue = 'OA-Green';

        $doc->addSubject(Subject::new()
            ->setValue($subjectValue)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $this->assertCount(1, $doc->getSubject());

        $this->assertTrue($doc->hasSubject($subjectValue, 'uncontrolled'));
        $this->assertFalse($doc->hasSubject($subjectValue, 'swd'));
    }

    public function testHasSubjectCaseSensitive()
    {
        $doc = Document::new();

        $subjectValue = 'OA-Green';

        $doc->addSubject(Subject::new()
            ->setValue($subjectValue)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $this->assertCount(1, $doc->getSubject());

        $this->assertTrue($doc->hasSubject($subjectValue));
        $this->assertFalse($doc->hasSubject(strtolower($subjectValue), null, true));
        $this->assertFalse($doc->hasSubject(strtoupper($subjectValue), null, true));
    }

    public function testHasSubjectWithTypeCaseSensitive()
    {
        $doc = Document::new();

        $subjectValue = 'OA-Green';

        $doc->addSubject(Subject::new()
            ->setValue($subjectValue)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $this->assertCount(1, $doc->getSubject());

        $this->assertTrue($doc->hasSubject($subjectValue, 'uncontrolled'));
        $this->assertFalse($doc->hasSubject(strtolower($subjectValue), 'uncontrolled', true));
        $this->assertFalse($doc->hasSubject(strtoupper($subjectValue), 'uncontrolled', true));
        $this->assertFalse($doc->hasSubject($subjectValue, 'swd'));
        $this->assertFalse($doc->hasSubject(strtolower($subjectValue), 'swd', true));
        $this->assertFalse($doc->hasSubject(strtoupper($subjectValue), 'swd', true));
    }

    public function testRemoveSubject()
    {
        $doc = Document::new();

        $subjectGreen = 'OA-Green';
        $subjectGold  = 'OA-Gold';

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $doc->addSubject(Subject::new()
            ->setValue($subjectGold)
            ->setLanguage('eng')
            ->setType(Subject::TYPE_PSYNDEX));

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject($subjectGold);

        $this->assertCount(1, $doc->getSubject());
        $this->assertEquals($subjectGreen, $doc->getSubject()[0]->getValue());
    }

    public function testRemoveSubjectWithType()
    {
        $doc = Document::new();

        $subjectGreen = 'OA-Green';
        $subjectGold  = 'OA-Gold';

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $doc->addSubject(Subject::new()
            ->setValue($subjectGold)
            ->setLanguage('eng')
            ->setType(Subject::TYPE_PSYNDEX));

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject($subjectGold, Subject::TYPE_UNCONTROLLED);

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject($subjectGold, Subject::TYPE_PSYNDEX);

        $this->assertCount(1, $doc->getSubject());
        $this->assertEquals($subjectGreen, $doc->getSubject()[0]->getValue());
    }

    public function testRemoveSubjectCaseSensitive()
    {
        $doc = Document::new();

        $subjectGreen = 'OA-Green';
        $subjectGold  = 'OA-Gold';

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $doc->addSubject(Subject::new()
            ->setValue($subjectGold)
            ->setLanguage('eng')
            ->setType(Subject::TYPE_PSYNDEX));

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject('oa-gold', null, true);

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject('OA-Gold', null, true);

        $this->assertCount(1, $doc->getSubject());
        $this->assertEquals($subjectGreen, $doc->getSubject()[0]->getValue());
    }

    public function testRemoveSubjectWithTypeCaseSensitive()
    {
        $doc = Document::new();

        $subjectGreen = 'OA-Green';
        $subjectGold  = 'OA-Gold';

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $doc->addSubject(Subject::new()
            ->setValue($subjectGold)
            ->setLanguage('eng')
            ->setType(Subject::TYPE_PSYNDEX));

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject('oa-gold', Subject::TYPE_PSYNDEX, true);

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject('OA-Gold', Subject::TYPE_PSYNDEX, true);

        $this->assertCount(1, $doc->getSubject());
        $this->assertEquals($subjectGreen, $doc->getSubject()[0]->getValue());
    }

    public function testRemoveMultipleSubjectsOfDifferentType()
    {
        $doc = Document::new();

        $subjectGreen = 'OA-Green';

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType(Subject::TYPE_PSYNDEX));

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject($subjectGreen);

        $this->assertCount(0, $doc->getSubject());
    }

    public function testRemoveSameSubjectsForSingleType()
    {
        $doc = Document::new();

        $subjectGreen = 'OA-Green';

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType('uncontrolled'));

        $doc->addSubject(Subject::new()
            ->setValue($subjectGreen)
            ->setLanguage('eng')
            ->setType(Subject::TYPE_PSYNDEX));

        $this->assertCount(2, $doc->getSubject());

        $doc->removeSubject($subjectGreen, 'uncontrolled');

        $this->assertCount(1, $doc->getSubject());
        $this->assertEquals(Subject::TYPE_PSYNDEX, $doc->getSubject()[0]->getType());
    }
}
