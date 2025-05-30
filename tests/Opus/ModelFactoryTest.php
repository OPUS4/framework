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

use Opus\Common\Document as CommonDocument;
use Opus\Common\DocumentInterface;
use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\DocumentRepository;
use Opus\EnrichmentKey;
use Opus\ModelFactory;
use OpusTest\TestAsset\TestCase;

class ModelFactoryTest extends TestCase
{
    public function tearDown(): void
    {
        $this->clearTables(false, ['documents']);

        parent::tearDown();
    }

    public function testCreate()
    {
        $model = CommonDocument::create();

        $this->assertInstanceOf(DocumentInterface::class, $model);
        $this->assertInstanceOf(Document::class, $model);
    }

    public function testGet()
    {
        $doc = new Document();
        $doc->setType('article');
        $docId = $doc->store();

        $model = CommonDocument::get($docId);

        $this->assertEquals($docId, $model->getId());
        $this->assertEquals('article', $model->getType());
    }

    public function testGetRepository()
    {
        $modelFactory = new ModelFactory();

        $documentRepository = $modelFactory->getRepository('Document');

        $this->assertInstanceOf(DocumentRepository::class, $documentRepository);
    }

    public function testGetRepositoryFallbackToModelClass()
    {
        $modelFactory = new ModelFactory();

        $personRepository = $modelFactory->getRepository('EnrichmentKey');

        $this->assertInstanceOf(EnrichmentKey::class, $personRepository);
    }

    public function testGetRepositoryUnknownType()
    {
        $modelFactory = new ModelFactory();

        $this->expectException(ModelException::class);
        $this->expectExceptionMessage('Model class not found: Opus\UnknownModel');

        $modelFactory->getRepository('UnknownModel');
    }

    public function testGetTableGatewayClass()
    {
        $modelFactory = new ModelFactory();

        $gatewayClass = $modelFactory->getTableGatewayClass('Document');

        $this->assertEquals($gatewayClass, Document::getTableGatewayClass());
    }
}
