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
 * @copyright   Copyright (c) 2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Common\Date;
use Opus\Common\Document;
use Opus\Common\Repository;
use Opus\Db\Documents;
use Opus\Db\TableGateway;
use Opus\DocumentRepository;
use OpusTest\TestAsset\TestCase;

use function date;

class DocumentRepositoryTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, [
            'documents',
            'document_title_abstracts',
        ]);
    }

    public function testGetEarliestPublicationDate()
    {
        $documents = Repository::getInstance()->getModelRepository(Document::class);

        $nullDate = $documents->getEarliestPublicationDate();
        $this->assertNull($nullDate, "Expected NULL on empty database.");

        // Insert valid entry through framework.
        $document = Document::new();
        $document->setServerDatePublished('2011-06-01T00:00:00Z');
        $document->store();
        $validDate = $documents->getEarliestPublicationDate();
        $this->assertEquals('2011-06-01', $validDate);

        // Insert invalid entry into database...
        $table = TableGateway::getInstance(Documents::class);
        $table->insert(['server_date_published' => '1234', 'server_date_created' => '1234']);
        $invalidDate = $documents->getEarliestPublicationDate();
        $this->assertNull($invalidDate, "Expected NULL on invalid date.");
    }

    public function testSetServerDateModifiedForDocuments()
    {
        $doc    = Document::new();
        $doc1Id = $doc->store();

        $doc    = Document::new();
        $doc2Id = $doc->store();

        $doc    = Document::new();
        $doc3Id = $doc->store();

        $date = new Date('2016-05-10');

        $documentRepository = new DocumentRepository();
        $documentRepository->setServerDateModifiedForDocuments($date, [$doc1Id, $doc3Id]);

        $doc = Document::get($doc1Id);
        $this->assertEquals(0, $date->compare($doc->getServerDateModified()));

        $doc = Document::get($doc2Id);
        $this->assertNotEquals(0, $date->compare($doc->getServerDateModified()));

        $doc = Document::get($doc3Id);
        $this->assertEquals(0, $date->compare($doc->getServerDateModified()));
    }

    public function testGetSiteLinksInfo()
    {
        // Document with main title matching document language
        $doc   = Document::new();
        $title = $doc->addTitleMain();
        $title->setValue('Title 1');
        $title->setLanguage('eng');
        $doc->setLanguage('eng');
        $doc->setServerState(Document::STATE_PUBLISHED);
        $docId1 = $doc->store();

        // Document without main title
        $doc   = Document::new();
        $title = $doc->addTitleParent();
        $title->setValue('Title 2 Parent');
        $title->setLanguage('deu');
        $doc->setLanguage('deu');
        $doc->setServerState(Document::STATE_PUBLISHED);
        $docId2 = $doc->store();

        // Document with titles matching and not matching document language
        $doc   = Document::new();
        $title = $doc->addTitleMain();
        $title->setValue('Title 3');
        $title->setLanguage('deu');
        $doc->setLanguage('deu');
        $title = $doc->addTitleMain();
        $title->setValue('Title 3 not matching language');
        $title->setLanguage('eng');
        $doc->setServerState(Document::STATE_PUBLISHED);
        $docId3 = $doc->store();

        // Document published in a different year
        $doc   = Document::new();
        $title = $doc->addTitleMain();
        $title->setValue('Title 4');
        $title->setLanguage('deu');
        $doc->setLanguage('deu');
        $doc->setServerState(Document::STATE_PUBLISHED);
        $docId4 = $doc->store();
        $doc->setServerDatePublished('2011-06-01T00:00:00Z');
        $doc->store();

        // Document without main titles matching document language
        $doc   = Document::new();
        $title = $doc->addTitleMain();
        $title->setValue('Title 5 not matching language');
        $title->setLanguage('eng');
        $doc->setLanguage('deu');
        $title = $doc->addTitleMain();
        $title->setValue('Title 5 also not matching language');
        $title->setLanguage('fra');
        $doc->setServerState(Document::STATE_PUBLISHED);
        $docId5 = $doc->store();

        // Document without language
        $doc   = Document::new();
        $title = $doc->addTitleMain();
        $title->setValue('Title 6');
        $title->setLanguage('eng');
        $doc->setServerState(Document::STATE_PUBLISHED);
        $docId6 = $doc->store();

        $doc5   = Document::get($docId5);
        $titles = $doc5->getTitleMain();

        $currentYear = (int) date('Y');

        $documentRepository = new DocumentRepository();
        $info               = $documentRepository->getSiteLinksInfo($currentYear);

        $this->assertCount(4, $info);
        $this->assertEqualsCanonicalizing([
            $docId1 => [$docId1, 'Title 1', 'eng'],
            $docId3 => [$docId3, 'Title 3', 'deu'],
            $docId5 => [$docId5, $titles[0]->getValue(), $titles[0]->getLanguage()],
            $docId6 => [$docId6, 'Title 6', 'eng'],
        ], $info);

        $info = $documentRepository->getSiteLinksInfo(2011);

        $this->assertCount(1, $info);
        $this->assertEqualsCanonicalizing([
            $docId4 => [$docId4, 'Title 4', 'deu'],
        ], $info);
    }
}
