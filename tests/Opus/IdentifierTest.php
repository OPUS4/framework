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

use Exception;
use Opus\Common\Config;
use Opus\Common\Identifier;
use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\DocumentFinder;
use Opus\Doi\Generator\DefaultGenerator;
use Opus\Identifier\DoiAlreadyExistsException;
use Opus\Identifier\UrnAlreadyExistsException;
use OpusTest\TestAsset\TestCase;
use Zend_Config;
use Zend_Exception;

use function count;
use function sleep;

class IdentifierTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false, ['documents', 'document_identifiers']);
    }

    /**
     * @param string $urn
     * @return Document
     */
    private function createDocumentWithIdentifierUrn($urn)
    {
        $document = new Document();
        $document->addIdentifier()
                ->setType('urn')
                ->setValue($urn);
        return $document;
    }

    /**
     * Check if exactly one document with testUrn exists
     *
     * @param int    $docId
     * @param string $type
     * @param string $value
     */
    private function checkUniqueIdentifierOnDocument($docId, $type, $value)
    {
        $finder = new DocumentFinder();
        $finder->setIdentifierTypeValue($type, $value);
        $this->assertEquals(1, $finder->count());
        $this->assertContains($docId, $finder->ids());
    }

    public function testCreateDocumentWithUrn()
    {
        $testUrn  = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId    = $document->store();

        // reload and test
        $document    = new Document($docId);
        $identifiers = $document->getIdentifier();

        $this->assertEquals(1, count($identifiers));
        $this->assertEquals('urn', $identifiers[0]->getType());
        $this->assertEquals($testUrn, $identifiers[0]->getValue());
    }

    /**
     * Regression test for OPUSVIER-2289
     *
     * @doesNotPerformAssertions
     */
    public function testFailDoubleUrnForSameDocument()
    {
        $testUrn  = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $document->addIdentifier()
                ->setType('urn')
                ->setValue('nbn:de:kobv:test123');

        try {
            $document->store();
            $this->fail('expected exception');
        } catch (UrnAlreadyExistsException $e) {
        }
    }

    /**
     * Regression test for OPUSVIER-2289
     */
    public function testCreateUrnCollisionViaDocument()
    {
        $testUrn  = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId    = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        try {
            $document->store();
            $this->fail('expected exception');
        } catch (UrnAlreadyExistsException $e) {
        }
    }

    /**
     * Regression test for OPUSVIER-2289
     */
    public function testCreateUrnCollisionViaUsingIdentifier()
    {
        $testUrn  = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId    = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = new Document();
        $document->store();

        $document->addIdentifier()
                ->setType('urn')
                ->setValue($testUrn);

        try {
            $document->getIdentifier(0)->store();
            $this->fail('expected exception');
        } catch (UrnAlreadyExistsException $e) {
        }
    }

    /**
     * Regression test for OPUSVIER-2292 / OPUSVIER-2289
     */
    public function testCreateUrnCollisionUsingAddIdentifierUrn()
    {
        $testUrn  = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId    = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = new Document();
        $document->store();

        $document->addIdentifierUrn()
                ->setValue($testUrn);

        try {
            $document->store();
            $this->fail('expected exception');
        } catch (UrnAlreadyExistsException $e) {
        }
    }

    public function testIsValidDoiPositive()
    {
        $doi = Identifier::new();
        $doi->setType('doi');
        $doi->setValue('12.3456/opustest-789');
        $this->assertTrue($doi->isValidDoi());
    }

    public function testIsValidDoiNegative()
    {
        $doiValuesToProbe = [
            '10.000/äöüß-987',
            '10.000/opus~987',
            '10.000/opus*987',
            '10.000/opus#987',
        ];
        foreach ($doiValuesToProbe as $value) {
            $doi = Identifier::new();
            $doi->setType('doi');
            $doi->setValue($value);
            $this->assertFalse($doi->isValidDoi(), 'expected ' . $value . ' to be an invalid DOI value');
        }
    }

    public function testIsLocalDoiPositiveWithGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/opustest-987');

        $this->assertDoi($docId, '12.3456/opustest-987', true);
    }

    public function testIsLocalDoiPositiveWithGeneratorClassAndMissingPrefixShlash()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '12.3456',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/opustest-987');

        $this->assertDoi($docId, '12.3456/opustest-987', true);
    }

    public function testIsLocalDoiPositiveWithoutGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => '',
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/opustest-987');

        $this->assertDoi($docId, '12.3456/opustest-987', true);
    }

    public function testIsLocalDoiPositiveWithoutGeneratorClassAndMissingPrefixSlash()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => '',
            'prefix'         => '12.3456',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/opustest-987');

        $this->assertDoi($docId, '12.3456/opustest-987', true);
    }

    public function testIsLocalDoiNegativeWithGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/anothersystem-987');

        $this->assertDoi($docId, '12.3456/anothersystem-987', false);
    }

    public function testIsLocalDoiNegativeWithGeneratorClassAlt()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.6543/opustest-987');

        $this->assertDoi($docId, '12.6543/opustest-987', false);
    }

    public function testIsLocalDoiNegativeWithMissingGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => 'Opus\Doi\Generator\MissingGenerator',
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.6543/opustest-987');

        $this->assertDoi($docId, '12.6543/opustest-987', false);
    }

    public function testIsLocalDoiNegativeWithoutGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => '',
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/anothersystem-987');

        $this->assertDoi($docId, '12.3456/anothersystem-987', false);
    }

    public function testIsLocalDoiNegativeWithoutPrefixAndWithoutGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => '',
            'prefix'         => '',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('opustest-987');

        $this->assertDoi($docId, 'opustest-987', false);
    }

    public function testIsLocalDoiNegativeWithoutPrefixAndWithGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('opustest-987');

        $this->assertDoi($docId, 'opustest-987', false);
    }

    public function testIsDoiUniquePositive()
    {
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc1   = $this->createTestDocumentWithDoi('12.3456/opustest-789', false);
        $doc1Id = $doc1->store();

        $doc2 = $this->createTestDocumentWithDoi('12.3456/opustest-890', false);
        $dois = $doc2->getIdentifier();
        $doi  = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('12.3456/opustest-890', $doi->getValue());

        $this->assertTrue($doi->isDoiUnique());
        $this->assertTrue($doi->isDoiUnique($doc1Id));

        $doc2Id = $doc2->store();
        $this->assertNotNull($doc2Id);
    }

    public function testIsDoiUniqueNegative()
    {
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc1   = $this->createTestDocumentWithDoi('12.3456/opustest-789', false);
        $doc1Id = $doc1->store();

        $doc2   = $this->createTestDocumentWithDoi('12.3456/opustest-890', false);
        $doc2Id = $doc2->store();

        $doc3 = $this->createTestDocumentWithDoi('12.3456/opustest-789', false);
        $dois = $doc3->getIdentifier();
        $this->assertCount(1, $dois);

        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('12.3456/opustest-789', $doi->getValue());

        $this->assertFalse($doi->isDoiUnique());
        $this->assertTrue($doi->isDoiUnique($doc1Id));
        $this->assertFalse($doi->isDoiUnique($doc2Id));

        $exceptionToCheck = null;
        try {
            $doc3->store();
        } catch (Exception $e) {
            $exceptionToCheck = $e;
        }
        $this->assertTrue($exceptionToCheck instanceof DoiAlreadyExistsException);
    }

    public function testIsUrnUniquePositive()
    {
        $doc1   = $this->createDocumentWithIdentifierUrn('urn:987654321');
        $doc1Id = $doc1->store();

        $doc2 = $this->createDocumentWithIdentifierUrn('urn:123456789');

        $urns = $doc2->getIdentifier();
        $this->assertCount(1, $urns);
        $urn = $urns[0];
        $this->assertEquals('urn', $urn->getType());
        $this->assertEquals('urn:123456789', $urn->getValue());

        $this->assertTrue($urn->isUrnUnique());
        $this->assertTrue($urn->isUrnUnique($doc1Id));

        $doc2Id = $doc2->store();
        $this->assertNotNull($doc2Id);
    }

    public function testIsUrnUniqueNegative()
    {
        $doc1   = $this->createDocumentWithIdentifierUrn('urn:987654321');
        $doc1Id = $doc1->store();

        $doc2   = $this->createDocumentWithIdentifierUrn('urn:123456789');
        $doc2Id = $doc2->store();

        $doc3 = $this->createDocumentWithIdentifierUrn('urn:987654321');
        $urns = $doc3->getIdentifier();
        $this->assertCount(1, $urns);
        $urn = $urns[0];
        $this->assertEquals('urn', $urn->getType());
        $this->assertEquals('urn:987654321', $urn->getValue());

        $this->assertFalse($urn->isUrnUnique());
        $this->assertTrue($urn->isUrnUnique($doc1Id));
        $this->assertFalse($urn->isUrnUnique($doc2Id));

        $exceptionToCheck = null;
        try {
            $doc3->store();
        } catch (Exception $e) {
            $exceptionToCheck = $e;
        }
        $this->assertTrue($exceptionToCheck instanceof UrnAlreadyExistsException);
    }

    /**
     * @param int    $docId
     * @param string $value
     * @param bool   $isLocal
     * @throws ModelException
     * @throws Zend_Exception
     */
    private function assertDoi($docId, $value, $isLocal)
    {
        $doc  = new Document($docId);
        $dois = $doc->getIdentifier();
        $this->assertCount(1, $dois);

        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals($value, $doi->getValue());
        $this->assertEquals($isLocal, $doi->isLocalDoi());
    }

    /**
     * @param string $value
     * @param bool   $store
     * @return array|Document|string
     * @throws ModelException
     */
    private function createTestDocumentWithDoi($value, $store = true)
    {
        $doc = new Document();

        $doi = Identifier::new();
        $doi->setType('doi');
        $doi->setValue($value);
        $doc->setIdentifier([$doi]);

        if (! $store) {
            return $doc;
        }

        return $doc->store();
    }

    /**
     * @param array $doiConfig
     */
    private function adaptDoiConfiguration($doiConfig)
    {
        Config::get()->merge(new Zend_Config(['doi' => $doiConfig]));
    }

    public function testCheckDoiCollisionFalse()
    {
        $doiConfig = [
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '12.3456/',
            'localPrefix'    => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc1 = $this->createTestDocumentWithDoi('12.3457/opustest-789', false);
        $doc1->store();

        $doc2 = $this->createTestDocumentWithDoi('12.3456/opustest-890', false);
        $doc2->store();

        $doc3 = $this->createTestDocumentWithDoi('12.3457/opustest-789', false);
        $dois = $doc3->getIdentifier();
        $this->assertCount(1, $dois);

        // DOI not local
        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('12.3457/opustest-789', $doi->getValue());

        $this->assertFalse($doi->checkDoiCollision());

        // doi unique
        $doi->setValue('12.3456/opustest-891');

        $this->assertFalse($doi->checkDoiCollision());
    }

    public function testToArray()
    {
        $identifier = Identifier::new();
        $identifier->setValue('123-4563-123');
        $identifier->setType('isbn');
        $identifier->setStatus('registered');
        $identifier->setRegistrationTs('2018-10-11 15:45:21');

        $data = $identifier->toArray();

        $this->assertEquals([
            'Value'          => '123-4563-123',
            'Type'           => 'isbn',
            'Status'         => 'registered',
            'RegistrationTs' => '2018-10-11 15:45:21',
        ], $data);
    }

    public function testFromArray()
    {
        $data = [
            'Value'          => '123-4563-123',
            'Type'           => 'isbn',
            'Status'         => 'registered',
            'RegistrationTs' => '2018-10-11 15:45:21',
        ];

        $identifier = Identifier::new();
        $identifier->updateFromArray($data);

        $this->assertEquals('123-4563-123', $identifier->getValue());
        $this->assertEquals('isbn', $identifier->getType());
        $this->assertEquals('registered', $identifier->getStatus());
        $this->assertEquals('2018-10-11 15:45:21', $identifier->getRegistrationTs());
    }

    public function testModifyingStatusDoesNotChangeServerDateModified()
    {
        $doc        = new Document();
        $identifier = Identifier::new();
        $identifier->setType('old');
        $identifier->setValue('123-45678-123');
        $doc->addIdentifier($identifier);

        $docId = $doc->store();

        $doc = new Document($docId);

        $modified = $doc->getServerDateModified();

        sleep(2);

        $identifier = $doc->getIdentifier(0);

        $this->assertNotEquals('registered', $identifier->getStatus());

        $identifier->setStatus('registered');
        $identifier->store();

        $doc = new Document($docId);

        $this->assertEquals(0, $doc->getServerDateModified()->compare($modified));
        $this->assertEquals('registered', $doc->getIdentifier(0)->getStatus());
    }

    public function testModifyingRegistrationTsDoesNotChangeServerDateModified()
    {
        $doc        = new Document();
        $identifier = Identifier::new();
        $identifier->setType('old');
        $identifier->setValue('123-45678-123');
        $doc->addIdentifier($identifier);

        $docId = $doc->store();

        $doc = new Document($docId);

        $modified = $doc->getServerDateModified();

        sleep(2);

        $identifier = $doc->getIdentifier(0);

        $this->assertNotEquals('registered', $identifier->getStatus());

        $timestamp = '2018-10-12 13:45:21';

        $identifier->setRegistrationTs($timestamp);
        $identifier->store();

        $doc = new Document($docId);

        $this->assertEquals(0, $doc->getServerDateModified()->compare($modified));
        $this->assertEquals($timestamp, $doc->getIdentifier(0)->getRegistrationTs());
    }

    public function testGetFieldnameForType()
    {
        $this->assertEquals('IdentifierPubmed', Identifier::getFieldnameForType('pmid'));
    }

    public function testGetTypeForFieldname()
    {
        $this->assertEquals('pmid', Identifier::getTypeForFieldname('IdentifierPubmed'));
    }

    public function testGetModelType()
    {
        $identifier = Identifier::new();
        $this->assertEquals('identifier', $identifier->getModelType());
    }
}
