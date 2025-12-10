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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Document;
use Opus\Common\Subject;
use Opus\Common\SubjectInterface;
use OpusTest\TestAsset\TestCase;

class SubjectTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, [
            'documents',
            'document_subjects',
        ]);
    }

    public function testGetMatchingSubjects()
    {
        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $subject->setExternalKey('comext');
        $doc->store();

        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('Com');

        $this->assertCount(1, $values);
        $this->assertIsArray($values);

        $value = $values[0];

        $this->assertArrayHasKey('value', $value);
        $this->assertArrayHasKey('extkey', $value);
        $this->assertEquals('Computer', $value['value']);
        $this->assertEquals('comext', $value['extkey']);
    }

    /**
     * Test should return only one subject not both if value is escaped properly.
     */
    public function testGetMatchingSubjectsSqlInjection()
    {
        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $subject->setExternalKey('comext');
        $doc->store();

        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('cam or 1=1');
        $doc->store();

        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('cam or 1=1');

        $this->assertCount(1, $values);
        $this->assertIsArray($values);
    }

    public function testGetMatchingSubjectsGroup()
    {
        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $doc->store();

        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $doc->store();

        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('com');

        $this->assertCount(1, $values);
    }

    public function testGetMatchingSubjectsGroupDifferentExternalKey()
    {
        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $subject->setExternalKey('comext');
        $doc->store();

        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $doc->store();

        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('com');

        $this->assertCount(2, $values);
    }

    public function testGetMatchingSubjectsType()
    {
        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $doc->store();

        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('uncontrolled');
        $subject->setValue('Communication');
        $doc->store();

        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('com', 'uncontrolled');

        $this->assertCount(1, $values);

        $value = $values[0];

        $this->assertArrayHasKey('value', $value);
        $this->assertArrayHasKey('extkey', $value);

        $this->assertEquals('Communication', $value['value']);
    }

    public function testGetMatchingSubjectsTypeNull()
    {
        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $doc->store();

        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('uncontrolled');
        $subject->setValue('Communication');
        $doc->store();

        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('com', null);

        $this->assertCount(2, $values);
    }

    public function testGetMatchingSubjectsNull()
    {
        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects(null);

        $this->assertIsArray($values);
        $this->assertCount(0, $values);
    }

    public function testGetMatchingSubjectsEmpty()
    {
        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('');

        $this->assertIsArray($values);
        $this->assertCount(0, $values);
    }

    public function testGetMatchingSubjectsLimit()
    {
        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Computer');
        $doc->store();

        $doc     = Document::new();
        $subject = $doc->addSubject();
        $subject->setType('swd');
        $subject->setValue('Communication');
        $doc->store();

        $subjectRepository = Subject::getModelRepository();

        $values = $subjectRepository->getMatchingSubjects('com', 'swd', null);

        $this->assertCount(2, $values);

        $values = $subjectRepository->getMatchingSubjects('com', 'swd', 1);

        $this->assertCount(1, $values);
    }

    public function testToArray()
    {
        $subject = Subject::new();

        $subject->setLanguage('deu');
        $subject->setType(Subject::TYPE_SWD);
        $subject->setExternalKey('key:Schlagwort');
        $subject->setValue('Schlagwort');

        $data = $subject->toArray();

        $this->assertEquals([
            'Language'    => 'deu',
            'Type'        => 'swd',
            'ExternalKey' => 'key:Schlagwort',
            'Value'       => 'Schlagwort',
        ], $data);
    }

    public function testFromArray()
    {
        $subject = Subject::fromArray([
            'Language'    => 'deu',
            'Type'        => 'swd',
            'ExternalKey' => 'key:Schlagwort',
            'Value'       => 'Schlagwort',
        ]);

        $this->assertNotNull($subject);
        $this->assertInstanceOf(SubjectInterface::class, $subject);

        $this->assertEquals('deu', $subject->getLanguage());
        $this->assertEquals('swd', $subject->getType());
        $this->assertEquals('key:Schlagwort', $subject->getExternalKey());
        $this->assertEquals('Schlagwort', $subject->getValue());
    }

    public function testUpdateFromArray()
    {
        $subject = Subject::new();

        $subject->updateFromArray([
            'Language'    => 'deu',
            'Type'        => 'swd',
            'ExternalKey' => 'key:Schlagwort',
            'Value'       => 'Schlagwort',
        ]);

        $this->assertNotNull($subject);
        $this->assertInstanceOf(SubjectInterface::class, $subject);

        $this->assertEquals('deu', $subject->getLanguage());
        $this->assertEquals('swd', $subject->getType());
        $this->assertEquals('key:Schlagwort', $subject->getExternalKey());
        $this->assertEquals('Schlagwort', $subject->getValue());
    }
}
