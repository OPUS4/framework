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
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2010-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_IdentifierTest extends TestCase
{

    private function createDocumentWithIdentifierUrn($urn)
    {
        $document = new Opus_Document();
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
        $finder = new Opus_DocumentFinder();
        $finder->setIdentifierTypeValue($type, $value);
        $this->assertEquals(1, $finder->count());
        $this->assertContains($docId, $finder->ids());
    }

    function testCreateDocumentWithUrn()
    {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId = $document->store();

        // reload and test
        $document = new Opus_Document($docId);
        $identifiers = $document->getIdentifier();

        $this->assertEquals(1, count($identifiers));
        $this->assertEquals('urn', $identifiers[0]->getType());
        $this->assertEquals($testUrn, $identifiers[0]->getValue());
    }

    /**
     * Regression test for OPUSVIER-2289
     */
    function testFailDoubleUrnForSameDocument()
    {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $document->addIdentifier()
                ->setType('urn')
                ->setValue('nbn:de:kobv:test123');

        try {
            $document->store();
            $this->fail('expected exception');
        } catch (Opus_Identifier_UrnAlreadyExistsException $e) {
        }
    }

    /**
     * Regression test for OPUSVIER-2289
     */
    function testCreateUrnCollisionViaDocument()
    {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        try {
            $document->store();
            $this->fail('expected exception');
        } catch (Opus_Identifier_UrnAlreadyExistsException $e) {
        }
    }

    /**
     * Regression test for OPUSVIER-2289
     */
    function testCreateUrnCollisionViaUsingIdentifier()
    {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = new Opus_Document();
        $document->store();

        $document->addIdentifier()
                ->setType('urn')
                ->setValue($testUrn);

        try {
            $document->getIdentifier(0)->store();
            $this->fail('expected exception');
        } catch (Opus_Identifier_UrnAlreadyExistsException $e) {
        }
    }

    /**
     * Regression test for OPUSVIER-2292 / OPUSVIER-2289
     */
    function testCreateUrnCollisionUsingAddIdentifierUrn()
    {
        $testUrn = 'nbn:de:kobv:test123';
        $document = $this->createDocumentWithIdentifierUrn($testUrn);
        $docId = $document->store();

        $this->checkUniqueIdentifierOnDocument($docId, 'urn', $testUrn);

        // create second document with testUrn
        $document = new Opus_Document();
        $document->store();

        $document->addIdentifierUrn()
                ->setValue($testUrn);

        try {
            $document->store();
            $this->fail('expected exception');
        } catch (Opus_Identifier_UrnAlreadyExistsException $e) {
        }
    }

    public function testIsValidDoiPositive()
    {
        $doi = new Opus_Identifier();
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
            '10.000/opus#987'];
        foreach ($doiValuesToProbe as $value) {
            $doi = new Opus_Identifier();
            $doi->setType('doi');
            $doi->setValue($value);
            $this->assertFalse($doi->isValidDoi(), 'expected ' . $value . ' to be an invalid DOI value');
        }
    }

    public function testIsLocalDoiPositiveWithGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/opustest-987');

        $this->assertDoi($docId, '12.3456/opustest-987', true);
    }

    public function testIsLocalDoiPositiveWithGeneratorClassAndMissingPrefixShlash()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '12.3456',
            'localPrefix' => 'opustest',
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
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
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
            'prefix' => '12.3456',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/opustest-987');

        $this->assertDoi($docId, '12.3456/opustest-987', true);
    }

    public function testIsLocalDoiNegativeWithGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.3456/anothersystem-987');

        $this->assertDoi($docId, '12.3456/anothersystem-987', false);
    }

    public function testIsLocalDoiNegativeWithGeneratorClassAlt()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('12.6543/opustest-987');

        $this->assertDoi($docId, '12.6543/opustest-987', false);
    }

    public function testIsLocalDoiNegativeWithMissingGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_MissingGenerator',
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
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
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
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
            'prefix' => '',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('opustest-987');

        $this->assertDoi($docId, 'opustest-987', false);
    }

    public function testIsLocalDoiNegativeWithoutPrefixAndWithGeneratorClass()
    {
        // adapt configuration to allow detection local DOIs
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createTestDocumentWithDoi('opustest-987');

        $this->assertDoi($docId, 'opustest-987', false);
    }

    public function testIsDoiUniquePositive()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc1 = $this->createTestDocumentWithDoi('12.3456/opustest-789', false);
        $doc1Id = $doc1->store();

        $doc2 = $this->createTestDocumentWithDoi('12.3456/opustest-890', false);
        $dois = $doc2->getIdentifier();
        $doi = $dois[0];
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
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc1 = $this->createTestDocumentWithDoi('12.3456/opustest-789', false);
        $doc1Id = $doc1->store();

        $doc2 = $this->createTestDocumentWithDoi('12.3456/opustest-890', false);
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
        $this->assertTrue($exceptionToCheck instanceof Opus_Identifier_DoiAlreadyExistsException);
    }

    public function testIsUrnUniquePositive()
    {
        $doc1 = $this->createDocumentWithIdentifierUrn('urn:987654321');
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
        $doc1 = $this->createDocumentWithIdentifierUrn('urn:987654321');
        $doc1Id = $doc1->store();

        $doc2 = $this->createDocumentWithIdentifierUrn('urn:123456789');
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
        $this->assertTrue($exceptionToCheck instanceof Opus_Identifier_UrnAlreadyExistsException);
    }

    private function assertDoi($docId, $value, $isLocal)
    {
        $doc = new Opus_Document($docId);
        $dois = $doc->getIdentifier();
        $this->assertCount(1, $dois);

        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals($value, $doi->getValue());
        $this->assertEquals($isLocal, $doi->isLocalDoi());
    }

    private function createTestDocumentWithDoi($value, $store = true)
    {
        $doc = new Opus_Document();

        $doi = new Opus_Identifier();
        $doi->setType('doi');
        $doi->setValue($value);
        $doc->setIdentifier([$doi]);

        if (! $store) {
            return $doc;
        }

        $docId = $doc->store();
        return $docId;
    }

    private function adaptDoiConfiguration($doiConfig)
    {
        Zend_Registry::set(
            'Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(new Zend_Config(['doi' => $doiConfig]))
        );
    }

    public function testCheckDoiCollisionFalse()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '12.3456/',
            'localPrefix' => 'opustest',
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
        $identifier = new Opus_Identifier();
        $identifier->setValue('123-4563-123');
        $identifier->setType('isbn');
        $identifier->setStatus('registered');
        $identifier->setRegistrationTs('2018-10-11 15:45:21');

        $data = $identifier->toArray();

        $this->assertEquals([
            'Value' => '123-4563-123',
            'Type' => 'isbn',
            'Status' => 'registered',
            'RegistrationTs' => '2018-10-11 15:45:21'
        ], $data);
    }

    public function testFromArray()
    {
        $data = [
            'Value' => '123-4563-123',
            'Type' => 'isbn',
            'Status' => 'registered',
            'RegistrationTs' => '2018-10-11 15:45:21'
        ];

        $identifier = new Opus_Identifier();
        $identifier->updateFromArray($data);

        $this->assertEquals('123-4563-123', $identifier->getValue());
        $this->assertEquals('isbn', $identifier->getType());
        $this->assertEquals('registered', $identifier->getStatus());
        $this->assertEquals('2018-10-11 15:45:21', $identifier->getRegistrationTs());
    }

    public function testModifyingStatusDoesNotChangeServerDateModified()
    {
        $doc = new Opus_Document();
        $identifier = new Opus_Identifier();
        $identifier->setType('old');
        $identifier->setValue('123-45678-123');
        $doc->addIdentifier($identifier);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $modified = $doc->getServerDateModified();

        sleep(2);

        $identifier = $doc->getIdentifier(0);

        $this->assertNotEquals('registered', $identifier->getStatus());

        $identifier->setStatus('registered');
        $identifier->store();

        $doc = new Opus_Document($docId);

        $this->assertEquals(0, $doc->getServerDateModified()->compare($modified));
        $this->assertEquals('registered', $doc->getIdentifier(0)->getStatus());
    }

    public function testModifyingRegistrationTsDoesNotChangeServerDateModified()
    {
        $doc = new Opus_Document();
        $identifier = new Opus_Identifier();
        $identifier->setType('old');
        $identifier->setValue('123-45678-123');
        $doc->addIdentifier($identifier);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $modified = $doc->getServerDateModified();

        sleep(2);

        $identifier = $doc->getIdentifier(0);

        $this->assertNotEquals('registered', $identifier->getStatus());

        $timestamp = '2018-10-12 13:45:21';

        $identifier->setRegistrationTs($timestamp);
        $identifier->store();

        $doc = new Opus_Document($docId);

        $this->assertEquals(0, $doc->getServerDateModified()->compare($modified));
        $this->assertEquals($timestamp, $doc->getIdentifier(0)->getRegistrationTs());
    }
}
