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
 * @category    Tests
 * @package     Opus_Doi
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Doi_DoiManagerTest extends TestCase {

    public function testConstructor() {
        Zend_Registry::set('Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(
                new Zend_Config(array('url' => 'http://localhost/opus4'))));
        $this->adaptDoiConfiguration(array('prefix' => ''));
        $doiManager = new Opus_Doi_DoiManager();
        $this->assertNotNull($doiManager);
    }

    public function testConstructorAlt() {
        Zend_Registry::set('Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(
                new Zend_Config(array('url' => 'http://localhost/opus4/'))));
        $this->adaptDoiConfiguration(array('prefix' => ''));
        $doiManager = new Opus_Doi_DoiManager();
        $this->assertNotNull($doiManager);
    }

    public function testRegisterMissingArg() {
        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_DoiException');
        $doiManager->register(null);
    }

    public function testRegisterInvalidArg() {
        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_DoiException');
        $doiManager->register('999');
    }

    public function testRegisterDocIdAsdArg() {
        $doc = new Opus_Document();
        $docId = $doc->store();

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register($docId);
        $this->assertNull($doi);
    }

    public function testRegisterDocWithoutDoi() {
        $doc = new Opus_Document();
        $docId = $doc->store();

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register(new Opus_Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithExternalDoi() {
        $this->adaptDoiConfiguration(array('prefix' => '10.3456/'));
        $docId = $this->createTestDocWithDoi('23.4567/');

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register(new Opus_Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithExternalDoiAndMissingConfig() {
        $docId = $this->createTestDocWithDoi('23.4567/');

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register(new Opus_Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithLocalRegisteredDoi() {
        $this->adaptDoiConfiguration(array('prefix' => '10.3456/'));
        $docId = $this->createTestDocWithDoi('10.3456/', 'registered');

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register(new Opus_Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithLocalNonUniqueDoi() {
        $this->adaptDoiConfiguration(array('prefix' => '10.3456/'));
        $doc1Id = $this->createTestDocWithDoi('10.3456/');

        $doc2Id = $this->createTestDocWithDoi('10.3456/');
        $doc2 = new Opus_Document($doc2Id);
        $identifiers = $doc2->getIdentifier();
        $doi = $identifiers[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.3456/' . $doc2Id, $doi->getValue());

        // change value to create a DOI conflict
        $doi->setValue('10.3456/' . $doc1Id);
        $doc2->setIdentifier(array($doi));

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register($doc2);
        $this->assertNull($doi);
    }

    public function testRegisterDocWithMissingProps() {
        $this->adaptDoiConfiguration(array('prefix' => '10.3456/'));
        $docId = $this->createTestDocWithDoi('10.3456/');

        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_RegistrationException');
        $doi = $doiManager->register(new Opus_Document($docId));
    }

    public function testRegisterDocWithRequiredPropsButMissingConfig() {
        $this->adaptDoiConfiguration(array('prefix' => '10.3456/'));
        $docId = $this->createTestDocWithDoi('10.3456/');

        $this->addRequiredPropsToDoc(new Opus_Document($docId));

        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_RegistrationException');
        $doi = $doiManager->register(new Opus_Document($docId));
    }

    public function testRegisterDocWithRequiredPropsButCompleteConfig() {
        $this->adaptDoiConfiguration(array(
            'prefix' => '10.3456/',
            'registration' => array(
                'datacite' => array(
                    'username' => 'test',
                    'password' => 'secret',
                    'serviceUrl' => 'http://localhost'
                )
            )));
        $docId = $this->createTestDocWithDoi('10.3456/');

        $this->addRequiredPropsToDoc(new Opus_Document($docId));

        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_RegistrationException');
        $doi = $doiManager->register(new Opus_Document($docId));
    }

    public function testRegisterAndVerifyDocSuccessfully() {
        $this->markTestSkipped('kann nur für manuellen Test verwendet werden, da DataCite-Testumgebung erforderlich (Username und Password werden in config.ini gesetzt)');

        // add url to config to allow creation of frontdoor URLs
        Zend_Registry::set('Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(
                new Zend_Config(array('url' => 'http://localhost/opus4/'))));

        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => array(
                'datacite' => array(
                    'serviceUrl' => 'https://mds.test.datacite.org'
                )
            )));
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');

        $this->addRequiredPropsToDoc(new Opus_Document($docId));

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register(new Opus_Document($docId), true);
        $this->assertNotNull($doi);

        $doc = new Opus_Document($docId);
        $dois = $doc->getIdentifier();
        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $doi->getValue());
        $this->assertEquals('registered', $doi->getStatus());
        $this->assertNotNull($doi->getRegistrationTs());

        $status = $doiManager->verifyRegistered();
        $this->assertFalse($status->isNoDocsToProcess());
        $statusOfDoc = $status->getDocsWithDoiStatus()[$docId];
        $this->assertNotNull($statusOfDoc);
        $this->assertFalse($statusOfDoc['error']);
    }

    public function testRegisterPendingWithoutDocs() {
        $doiManager = new Opus_Doi_DoiManager();
        $status = $doiManager->registerPending();
        $this->assertTrue($status->isNoDocsToProcess());
    }

    public function testRegisterPendingWithDocWithWrongServerState() {
        $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new Opus_Doi_DoiManager();
        $status = $doiManager->registerPending();
        $this->assertTrue($status->isNoDocsToProcess());
    }

    public function testRegisterPendingWithDoc() {
        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4')
        );

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new Opus_Doi_DoiManager();
        $status = $doiManager->registerPending(null);
        $this->assertFalse($status->isNoDocsToProcess());

        $statusOfDoc = $status->getDocsWithDoiStatus()[$docId];
        $this->assertNotNull($statusOfDoc);
        $this->assertTrue($statusOfDoc['error']);
    }

    public function testVerifyRegistered() {
        $doiManager = new Opus_Doi_DoiManager();
        $status = $doiManager->verifyRegistered();

        $this->assertTrue($status->isNoDocsToProcess());
    }

    public function testVerifyRegisteredBefore() {
        $this->adaptDoiConfiguration(array(
                'prefix' => '10.5072/',
                'localPrefix' => 'OPUS4')
        );

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new Opus_Doi_DoiManager();
        $status = $doiManager->verifyRegisteredBefore();

        $this->assertFalse($status->isNoDocsToProcess());
        $statusOfDoc = $status->getDocsWithDoiStatus()[$docId];
        $this->assertNotNull($statusOfDoc);
        $this->assertTrue($statusOfDoc['error']);
    }

    public function testVerifyWithUnknownDocId() {
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->verify('999');
        $this->assertNull($result);
    }

    public function testVerifyWithDocWithoutDoi() {
        $doc = new Opus_Document();
        $docId = $doc->store();

        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->verify($docId);
        $this->assertNull($result);
    }

    public function testVerifyWithUnregisteredDoi() {
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->verify($docId);
        $this->isNull($result);
    }

    public function testVerifyWithVerifiedDoiWithoutReverification() {
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'verified');
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->verify($docId, false);
        $this->assertNull($result);
    }

    public function testVerifyWithVerifiedDoiWithReverification() {
        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => array(
                'datacite' => array(
                    'username' => 'test',
                    'password' => 'secret',
                    'serviceUrl' => 'http://localhost'
                )
            )));
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'verified');
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->verify($docId, true);

        $this->assertNotNull($result);
        $this->assertEquals('doi', $result->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $result->getValue());
        // Status-Downgrade prüfen
        $this->assertEquals('registered', $result->getStatus());
    }

    public function testVerifyWithRegisteredDoiAndMissingConfig() {
        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4')
        );

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered');
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->verify($docId);

        $this->assertNotNull($result);
        $this->assertEquals('doi', $result->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $result->getValue());
        $this->assertEquals('registered', $result->getStatus());
    }

    public function testVerifyBeforeFilterPositive() {
        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => array(
                'datacite' => array(
                    'username' => 'test',
                    'password' => 'secret',
                    'serviceUrl' => 'http://localhost'
                )
            )));

        $dateTimeZone = new DateTimeZone(date_default_timezone_get());
        $dateTime = new DateTime('now', $dateTimeZone);
        $currentDate = $dateTime->format('Y-m-d H:i:s');
        
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered', $currentDate);

        $doiManager = new Opus_Doi_DoiManager();
        $dateTime = $dateTime->add(new DateInterval('PT1H'));
        $oneHourAfterCurrentDate = $dateTime->format('Y-m-d H:i:s');
        $result = $doiManager->verify($docId, true, $oneHourAfterCurrentDate);

        $this->assertNotNull($result);
        $this->assertEquals('doi', $result->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $result->getValue());
        $this->assertEquals('registered', $result->getStatus());
    }

    public function testVerifyBeforeFilterNegative() {
        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => array(
                'datacite' => array(
                    'username' => 'test',
                    'password' => 'secret',
                    'serviceUrl' => 'http://localhost'
                )
            )));

        $dateTimeZone = new DateTimeZone(date_default_timezone_get());
        $dateTime = new DateTime('now', $dateTimeZone);
        $currentDate = $dateTime->format('Y-m-d H:i:s');

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered', $currentDate);

        $doiManager = new Opus_Doi_DoiManager();
        $dateTime = $dateTime->sub(new DateInterval('PT1H'));
        $oneHourBeforeCurrentDate = $dateTime->format('Y-m-d H:i:s');
        $result = $doiManager->verify($docId, true, $oneHourBeforeCurrentDate);

        $this->assertNull($result);
    }

    public function testVerifySuccessfully() {
        $this->markTestSkipped('kann nur für manuellen Test verwendet werden, da DataCite-Testumgebung erforderlich (Username und Password werden in config.ini gesetzt)');

        // add url to config to allow creation of frontdoor URLs
        Zend_Registry::set('Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(
                new Zend_Config(array('url' => 'http://localhost/opus4/'))));

        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => array(
                'datacite' => array(
                    'serviceUrl' => 'https://mds.test.datacite.org'
                )
            )));
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');

        $this->addRequiredPropsToDoc(new Opus_Document($docId));

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register(new Opus_Document($docId), true);
        $this->assertNotNull($doi);

        $doi = $doiManager->verify($docId);
        $this->assertNotNull($doi);

        $doc = new Opus_Document($docId);
        $dois = $doc->getIdentifier();
        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $doi->getValue());
        $this->assertEquals('verified', $doi->getStatus());
        $this->assertNotNull($doi->getRegistrationTs());
    }

    public function testVerifyFailed() {
        $this->markTestSkipped('kann nur für manuellen Test verwendet werden, da DataCite-Testumgebung erforderlich (Username und Password werden in config.ini gesetzt)');

        // add url to config to allow creation of frontdoor URLs
        Zend_Registry::set('Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(
                new Zend_Config(array('url' => 'http://localhost/opus4/'))));

        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => array(
                'datacite' => array(
                    'serviceUrl' => 'https://mds.test.datacite.org'
                )
            )));
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'verified');

        $this->addRequiredPropsToDoc(new Opus_Document($docId));

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->verify($docId);

        $this->assertNotNull($doi);

        $doc = new Opus_Document($docId);
        $dois = $doc->getIdentifier();
        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $doi->getValue());
        $this->assertEquals('verified', $doi->getStatus());
    }

    public function testGetAllEmptyResult() {
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->getAll();
        $this->assertEmpty($result);
    }

    public function testGetAllOneExternalDoi() {
        $this->createTestDocWithDoi('10.5072');
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->getAll();
        $this->assertEmpty($result);
    }

    public function testGetAll() {
        $this->adaptDoiConfiguration(array(
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4')
        );
        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new Opus_Doi_DoiManager();
        $result = $doiManager->getAll();
        $this->assertCount(1, $result);
    }

    public function testGetAllStatusFiltered() {
        $this->adaptDoiConfiguration(array(
                'prefix' => '10.5072/',
                'localPrefix' => 'OPUS4')
        );

        $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered');

        $doiManager = new Opus_Doi_DoiManager();

        $result = $doiManager->getAll('registered');
        $this->assertCount(1, $result);

        $result = $doiManager->getAll();
        $this->assertCount(1, $result);

        $result = $doiManager->getAll('verified');
        $this->assertEmpty($result);

        $result = $doiManager->getAll('unregistered');
        $this->assertEmpty($result);

        $this->createTestDocWithDoi('10.5072/OPUS4-');
        $result = $doiManager->getAll('unregistered');
        $this->assertCount(1, $result);
    }

    public function testGenerateNewDoiMissingConfig() {
        $doc = new Opus_Document();
        $doc->store();
        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_DoiException');
        $doiManager->generateNewDoi($doc);
    }

    public function testGenerateNewDoiMissingGeneratorClass() {
        $this->adaptDoiConfiguration(array(
            'generatorClass' => 'Opus_Doi_Generator_MissingGenerator')
        );

        $doc = new Opus_Document();
        $doc->store();
        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_DoiException');
        $doiManager->generateNewDoi($doc);
    }

    public function testGenerateNewDoiInvalidDocId() {
        $this->adaptDoiConfiguration(array(
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator')
        );

        $doc = new Opus_Document();
        $doc->store();
        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_DoiException');
        $doiManager->generateNewDoi('999');
    }

    public function testGenerateNewDoiWithDocId() {
        $this->adaptDoiConfiguration(array(
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4')
        );

        $doc = new Opus_Document();
        $docId = $doc->store();
        $doiManager = new Opus_Doi_DoiManager();
        $doiValue = $doiManager->generateNewDoi($docId);

        $this->assertEquals('10.5072/OPUS4-' . $docId, $doiValue);
    }

    public function testGenerateNewDoiWithDoc() {
        $this->adaptDoiConfiguration(array(
                'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
                'prefix' => '10.5072/',
                'localPrefix' => 'OPUS4')
        );

        $doc = new Opus_Document();
        $docId = $doc->store();
        $doiManager = new Opus_Doi_DoiManager();
        $doiValue = $doiManager->generateNewDoi(new Opus_Document($docId));

        $this->assertEquals('10.5072/OPUS4-' . $docId, $doiValue);
    }

    public function testDeleteMetadataForDOIDocWithoutDoi() {
        $doc = new Opus_Document();
        $doc->store();

        $doiManager = new Opus_Doi_DoiManager();
        $doiManager->deleteMetadataForDOI($doc);
    }

    public function testDeleteMetadataForDOIDocWithExternalDoi() {
        $this->adaptDoiConfiguration(array(
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4')
        );

        $docId = $this->createTestDocWithDoi('10.9999/system-');

        $doiManager = new Opus_Doi_DoiManager();
        $doiManager->deleteMetadataForDOI(new Opus_Document($docId));
    }

    public function testDeleteMetadataForDOIDocWithLocalDoi() {
        $this->adaptDoiConfiguration(array(
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4')
        );

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');

        $doiManager = new Opus_Doi_DoiManager();
        $doiManager->deleteMetadataForDOI(new Opus_Document($docId));
    }

    public function testDeleteMetadataForDOIDocWithLocalRegisteredDoi() {
        $this->adaptDoiConfiguration(array(
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => array(
                'datacite' => array(
                    'username' => 'test',
                    'password' => 'secret',
                    'serviceUrl' => 'http://localhost')
                )
            )
        );

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered');

        $doiManager = new Opus_Doi_DoiManager();
        $doiManager->deleteMetadataForDOI(new Opus_Document($docId));
    }

    public function testDeleteMetadataForDOIDocWithLocalVerifiedDoi() {
        $this->adaptDoiConfiguration(array(
                'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
                'prefix' => '10.5072/',
                'localPrefix' => 'OPUS4',
                'registration' => array(
                    'datacite' => array(
                        'username' => 'test',
                        'password' => 'secret',
                        'serviceUrl' => 'http://localhost')
                )
            )
        );

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'verified');

        $doiManager = new Opus_Doi_DoiManager();
        $doiManager->deleteMetadataForDOI(new Opus_Document($docId));
    }

    public function testUpdateLandingPageUrlOfDoiWithMissingConfig() {
        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_DoiException');
        $doiManager->updateLandingPageUrlOfDoi('10.5072/OPUS4-999', 'http://localhost/frontdoor/999');
    }

    public function testUpdateLandingPageUrlOfDoi() {
        $this->adaptDoiConfiguration(array(
                'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
                'prefix' => '10.5072/',
                'localPrefix' => 'OPUS4',
                'registration' => array(
                    'datacite' => array(
                        'username' => 'test',
                        'password' => 'secret',
                        'serviceUrl' => 'http://localhost')
                )
            )
        );

        $doiManager = new Opus_Doi_DoiManager();
        $this->setExpectedException('Opus_Doi_DoiException');
        $doiManager->updateLandingPageUrlOfDoi('10.5072/OPUS4-999', 'http://localhost/frontdoor/999');
    }

    public function testUpdateLandingPageUrlOfDoiWithExistingDoi() {
        $this->markTestSkipped('kann nur für manuellen Test verwendet werden, da DataCite-Testumgebung erforderlich (Username und Password werden in config.ini gesetzt)');

        $config = Zend_Registry::get('Zend_Config');

        Zend_Registry::set('Zend_Config',
            $config->merge(
                new Zend_Config(array('url' => 'http://localhost/opus4'))));

        $this->adaptDoiConfiguration(array(
                'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
                'prefix' => '10.5072/',
                'localPrefix' => 'OPUS4',
                'registration' => array(
                    'datacite' => array(
                        'serviceUrl' => 'https://mds.test.datacite.org')
                )
            )
        );

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $this->addRequiredPropsToDoc(new Opus_Document($docId));

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register($docId, true);
        $this->assertEquals('registered', $doi->getStatus());

        $doi = $doiManager->verify($docId);
        $this->assertEquals('verified', $doi->getStatus());

        $doiManager->updateLandingPageUrlOfDoi('10.5072/OPUS4-' . $docId, 'http://localhost/opus5/frontdoor/index/index/' . $docId);

        $doi = $doiManager->verify($docId);
        $this->assertEquals('registered', $doi->getStatus());

        Zend_Registry::set('Zend_Config',
            $config->merge(
                new Zend_Config(array('url' => 'http://localhost/opus5'))));

        $this->adaptDoiConfiguration(array(
                'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
                'prefix' => '10.5072/',
                'localPrefix' => 'OPUS4',
                'registration' => array(
                    'datacite' => array(
                        'serviceUrl' => 'https://mds.test.datacite.org')
                )
            )
        );

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->verify($docId);
        $this->assertEquals('verified', $doi->getStatus());
    }

    private function adaptDoiConfiguration($doiConfig) {
        Zend_Registry::set('Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(new Zend_Config(array('doi' => $doiConfig))));
    }

    private function addRequiredPropsToDoc($doc) {
        $doc->setCompletedYear(2018);
        $doc->setServerState('unpublished');
        $doc->setType('book');
        $doc->setPublisherName('ACME corp');

        $author = new Opus_Person();
        $author->setLastName('Doe');
        $author->setFirstName('John');
        $doc->addPersonAuthor($author);

        $title = new Opus_Title();
        $title->setType('main');
        $title->setValue('Document without meaningful title');
        $doc->addTitleMain($title);

        $doc->store();
    }

    private function createTestDocWithDoi($doiPrefix, $status = null, $registrationTs = null) {
        $doc = new Opus_Document();
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $doi = new Opus_Identifier();
        $doi->setType('doi');
        $doi->setValue($doiPrefix . $docId);
        if (!is_null($status)) {
            $doi->setStatus($status);
        }
        if (!is_null($registrationTs)) {
            $doi->setRegistrationTs($registrationTs);
        }
        $doc->setIdentifier(array($doi));
        $doc->store();

        return $docId;
    }

}