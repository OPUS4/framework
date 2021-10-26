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
 * @category    Framework
 * @package     Opus
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2010-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Document;
use Opus\File;
use Opus\Language;
use Opus\Licence;
use Opus\Model\DbException;
use OpusTest\TestAsset\TestCase;

class LanguageTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false, [
            'languages',
            'documents',
            'document_subjects',
            'document_files',
            'document_title_abstracts',
            'document_licences',
            'link_documents_licences'
        ]);
    }

    public function testStoreLanguage()
    {
        $lang = new Language();
        $lang->setPart2B('ger');
        $lang->setPart2T('deu');
        $lang->setPart1('de');
        $lang->setRefName('German');
        $lang->setComment('test comment');
        $lang->store();

        $lang = new Language($lang->getId());

        $this->assertNotNull($lang);
        $this->assertEquals('ger', $lang->getPart2B());
        $this->assertEquals('deu', $lang->getPart2T());
        $this->assertEquals('de', $lang->getPart1());
        $this->assertEquals('German', $lang->getRefName());
        $this->assertEquals('test comment', $lang->getComment());
        $this->assertNull($lang->getScope());
        $this->assertNull($lang->getType());
        $this->assertEquals('0', $lang->getActive());
    }

    public function testGetAll()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setActive(1);
        $lang->store();

        $lang = new Language();
        $lang->setPart2T('deu');
        $lang->setRefName('German');
        $lang->setActive(0);
        $lang->store();

        $languages = Language::getAll();

        $this->assertEquals(2, count($languages));

        $this->assertEquals('English', $languages[0]->getRefName());
        $this->assertEquals('German', $languages[1]->getRefName());
    }

    public function testGetAllActive()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setActive(1);
        $lang->store();

        $lang = new Language();
        $lang->setPart2T('deu');
        $lang->setRefName('German');
        $lang->setActive(0);
        $lang->store();

        $languages = Language::getAllActive();

        $this->assertEquals(1, count($languages));

        $this->assertEquals('English', $languages[0]->getRefName());
    }

    public function testGetDisplayName()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('RefNameEnglish');
        $lang->setActive(1);
        $lang->store();

        $this->assertEquals('RefNameEnglish', $lang->getDisplayName());
    }

    public function testSetScope()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setScope('I');
        $lang->store();

        $lang = new Language($lang->getId());

        $this->assertEquals('I', $lang->getScope());
    }

    public function testSetScopeNull()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setScope(null);
        $lang->store();

        $lang = new Language($lang->getId());

        $this->assertNull($lang->getScope());
    }

    /**
     * TODO No exceptions without STRICT mode for MySQL
     * TODO expectedException Opus\Model\DbException
     * TODO expectedExceptionMessage Data truncated for column 'scope'
     */
    public function testSetScopeInvalid()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setScope('X');

        try {
            $lang->store();
        } catch (DbException $omde) {
            $this->assertContains('Data truncated for column \'scope\'', $omde->getMessage());
        }

        $lang = new Language($lang->getId());

        $this->assertEquals('', $lang->getScope());
    }

    public function testSetType()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setType('H');
        $lang->store();

        $lang = new Language($lang->getId());

        $this->assertEquals('H', $lang->getType());
    }

    public function testSetTypeNull()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setType(null);
        $lang->store();

        $lang = new Language($lang->getId());

        $this->assertNull($lang->getType());
    }

    /**
     * TODO No exceptions without STRICT mode for MySQL
     * TODO expectedException Opus\Model\DbException
     * TODO expectedExceptionMessage Data truncated for column 'type'
     */
    public function testSetTypeInvalid()
    {
        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setType('X');

        try {
            $lang->store();
        } catch (DbException $omde) {
            $this->assertContains('Data truncated for column \'type\'', $omde->getMessage());
        }

        $lang = new Language($lang->getId());

        $this->assertEquals('', $lang->getType());
    }

    public function testGetPart2tForPart1()
    {
        $lang = new Language();
        $lang->setPart2T('deu');
        $lang->setRefName('German');
        $lang->setPart1('de');
        $lang->setType(null);
        $lang->store();

        $lang = new Language();
        $lang->setPart2T('eng');
        $lang->setRefName('English');
        $lang->setPart1('en');
        $lang->setType(null);
        $lang->store();

        $this->assertEquals('deu', Language::getPart2tForPart1('de'));
        $this->assertEquals('eng', Language::getPart2tForPart1('en'));
        $this->assertNull(Language::getPart2tForPart1('ch'));
    }

    public function testToArray()
    {
        $lang = new Language();
        $lang->setActive(1);
        $lang->setPart2B('ger');
        $lang->setPart2T('deu');
        $lang->setRefName('German');
        $lang->setPart1('de');
        $lang->setType('L');
        $lang->setScope('I');
        $lang->setComment('Deutsche Sprache');

        $lang = new Language($lang->store());

        $data = $lang->toArray();

        $this->assertEquals([
            'Comment' => 'Deutsche Sprache',
            'Part2B' => 'ger',
            'Part2T' => 'deu',
            'Part1' => 'de',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'German',
            'Active' => 1
        ], $data);
    }

    public function testFromArray()
    {
        $lang = Language::fromArray([
            'Comment' => 'Deutsche Sprache',
            'Part2B' => 'ger',
            'Part2T' => 'deu',
            'Part1' => 'de',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'German',
            'Active' => 1
        ]);

        $this->assertNotNull($lang);
        $this->assertInstanceOf('Opus\Language', $lang);
        $this->assertEquals('Deutsche Sprache', $lang->getComment());
        $this->assertEquals('ger', $lang->getPart2B());
        $this->assertEquals('deu', $lang->getPart2T());
        $this->assertEquals('de', $lang->getPart1());
        $this->assertEquals('I', $lang->getScope());
        $this->assertEquals('L', $lang->getType());
        $this->assertEquals('German', $lang->getRefName());
        $this->assertEquals(1, $lang->getActive());
    }

    public function testUpdateFromArray()
    {
        $lang = new Language();

        $lang->updateFromArray([
            'Comment' => 'Deutsche Sprache',
            'Part2B' => 'ger',
            'Part2T' => 'deu',
            'Part1' => 'de',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'German',
            'Active' => 1
        ]);

        $this->assertNotNull($lang);
        $this->assertInstanceOf('Opus\Language', $lang);
        $this->assertEquals('Deutsche Sprache', $lang->getComment());
        $this->assertEquals('ger', $lang->getPart2B());
        $this->assertEquals('deu', $lang->getPart2T());
        $this->assertEquals('de', $lang->getPart1());
        $this->assertEquals('I', $lang->getScope());
        $this->assertEquals('L', $lang->getType());
        $this->assertEquals('German', $lang->getRefName());
        $this->assertEquals(1, $lang->getActive());
    }

    public function testGetLanguageCode()
    {
        $lang = new Language();

        $lang->updateFromArray([
            'Comment' => 'Deutsche Sprache',
            'Part2B' => 'ger',
            'Part2T' => 'deu',
            'Part1' => 'de',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'German',
            'Active' => 1
        ]);

        $lang->store();

        $this->assertEquals('ger', Language::getLanguageCode('ger'));
        $this->assertEquals('ger', Language::getLanguageCode('deu'));
    }

    public function testGetLanguageCodeFromPart1()
    {
        $lang = new Language();

        $lang->updateFromArray([
            'Comment' => 'Deutsche Sprache',
            'Part2B' => 'ger',
            'Part2T' => 'deu',
            'Part1' => 'de',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'German',
            'Active' => 1
        ]);

        $lang->store();

        $this->assertEquals('de', Language::getLanguageCode('deu', 'part1'));
    }

    public function testGetUsedLanguages()
    {
        $document = new Document();
        $document->setLanguage('deu');
        $title = $document->addTitleMain();
        $title->setValue('Main Title');
        $title->setLanguage('eng');
        $document->store();

        $document = new Document();
        $document->setLanguage('eng');
        $document->store();

        $languages = Language::getUsedLanguages();

        $this->assertInternalType('array', $languages);
        $this->assertCount(2, $languages);
        $this->assertEquals(['deu', 'eng'], $languages);
    }

    public function testGetUsedLanguagesIncludesLicences()
    {
        Language::clearCache();
        $languages = Language::getUsedLanguages();
        $this->assertEmpty($languages);

        $licence = new Licence();
        $licence->setName('TL');
        $licence->setNameLong('Test Licence');
        $licence->setLinkLicence('http://www.example.org');
        $licence->setLanguage('fra');
        $licence->store();

        Language::clearCache();
        $languages = Language::getUsedLanguages();

        $this->assertInternalType('array', $languages);
        $this->assertCount(1, $languages);
        $this->assertEquals(['fra'], $languages);
    }

    public function testGetUsedLanguagesIncludesFiles()
    {
        Language::clearCache();
        $languages = Language::getUsedLanguages();
        $this->assertEmpty($languages);

        $document = new Document();

        $file = new File();
        $file->setLanguage('spa');
        $file->setPathName('test.txt');
        $document->addFile($file);
        $document->store();

        Language::clearCache();

        $languages = Language::getUsedLanguages();

        $this->assertInternalType('array', $languages);
        $this->assertCount(1, $languages);
        $this->assertEquals(['spa'], $languages);
    }

    public function testGetUsedLanguagesIncludesSubjects()
    {
        Language::clearCache();
        $languages = Language::getUsedLanguages();
        $this->assertEmpty($languages);

        $document = new Document();
        $subject = $document->addSubject();
        $subject->setLanguage('rus');
        $subject->setValue('Keyword');
        $subject->setType('SWD');
        $document->store();

        Language::clearCache();

        $languages = Language::getUsedLanguages();

        $this->assertInternalType('array', $languages);
        $this->assertCount(1, $languages);
        $this->assertEquals(['rus'], $languages);
    }

    public function testGetUsedLanguagesWithoutDuplicates()
    {
        $document = new Document();
        $document->setLanguage('fra');
        $document->store();

        $document = new Document();
        $document->setLanguage('fra');
        $document->store();

        $licence = new Licence();
        $licence->setName('TL');
        $licence->setNameLong('Test Licence');
        $licence->setLinkLicence('http://www.example.org');
        $licence->setLanguage('fra');
        $licence->store();

        Language::clearCache();

        $languages = Language::getUsedLanguages();

        $this->assertInternalType('array', $languages);
        $this->assertCount(1, $languages);
        $this->assertEquals(['fra'], $languages);
    }

    public function testGetUsedLanguagesWithoutNull()
    {
        $document = new Document();
        $document->setLanguage('fra');
        $document->store();

        $document = new Document();
        $document->store();

        Language::clearCache();

        $languages = Language::getUsedLanguages();

        $this->assertInternalType('array', $languages);
        $this->assertCount(1, $languages);
        $this->assertEquals(['fra'], $languages);
    }

    public function testIsUsed()
    {
        $lang = new Language();

        $lang->updateFromArray([
            'Comment' => 'Deutsche Sprache',
            'Part2B' => 'ger',
            'Part2T' => 'deu',
            'Part1' => 'de',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'German',
            'Active' => 1
        ]);

        $lang->store();

        $this->assertFalse($lang->isUsed());

        Language::clearCache();

        $document = new Document();
        $document->setLanguage('deu');
        $document->store();

        $this->assertTrue($lang->isUsed());
    }
}
