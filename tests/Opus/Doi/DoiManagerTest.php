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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Doi
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Doi;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;
use Opus\Config;
use Opus\Document;
use Opus\Doi\DoiException;
use Opus\Doi\DoiManager;
use Opus\Doi\Generator\DefaultGenerator;
use Opus\Doi\RegistrationException;
use Opus\Identifier;
use Opus\Person;
use Opus\Title;
use OpusTest\TestAsset\TestCase;
use Zend_Config;
use Zend_Log;

use function date_default_timezone_get;
use function file_get_contents;
use function fsockopen;

use const DIRECTORY_SEPARATOR;

class DoiManagerTest extends TestCase
{
    public function setUp()
    {
        parent::setUp();

        $this->clearTables(false);
    }

    public function testConstructor()
    {
        Config::get()->merge(
            new Zend_Config(['url' => 'http://localhost/opus4'])
        );
        $this->adaptDoiConfiguration(['prefix' => '']);
        $doiManager = new DoiManager();
        $this->assertNotNull($doiManager);
    }

    public function testConstructorAlt()
    {
        Config::get()->merge(
            new Zend_Config(['url' => 'http://localhost/opus4/'])
        );
        $this->adaptDoiConfiguration(['prefix' => '']);
        $doiManager = new DoiManager();
        $this->assertNotNull($doiManager);
    }

    public function testGetInstance()
    {
        $manager = DoiManager::getInstance();
        $this->assertNotNull($manager);
        $this->assertInstanceOf(DoiManager::class, $manager);

        $manager2 = DoiManager::getInstance();
        $this->assertSame($manager, $manager2);
    }

    public function testGetDoiLogger()
    {
        $doiManager = new DoiManager();
        $doiLogger  = $doiManager->getDoiLogger();

        $this->assertNotNull($doiLogger);
        $this->assertInstanceOf(Zend_Log::class, $doiLogger);
    }

    /**
     * TODO Use helper function from OPUSVIER-4400 to read file
     */
    public function testGetDoiLoggerFilters()
    {
        $doiManager = new DoiManager();
        $doiLogger  = $doiManager->getDoiLogger();

        $debugMessage = 'debug level message';
        $doiLogger->debug($debugMessage);

        $config  = Config::get();
        $path    = $config->workspacePath . DIRECTORY_SEPARATOR . 'log' . DIRECTORY_SEPARATOR . 'opus-doi.log';
        $content = file_get_contents($path);

        $this->assertContains($debugMessage, $content);
    }

    public function testRegisterMissingArg()
    {
        $doiManager = new DoiManager();
        $this->setExpectedException(DoiException::class);
        $doiManager->register(null);
    }

    public function testRegisterInvalidArg()
    {
        $doiManager = new DoiManager();
        $this->setExpectedException(DoiException::class);
        $doiManager->register('999');
    }

    public function testRegisterDocIdAsdArg()
    {
        $doc   = new Document();
        $docId = $doc->store();

        $doiManager = new DoiManager();
        $doi        = $doiManager->register($docId);
        $this->assertNull($doi);
    }

    public function testRegisterDocWithoutDoi()
    {
        $doc   = new Document();
        $docId = $doc->store();

        $doiManager = new DoiManager();
        $doi        = $doiManager->register(new Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithExternalDoi()
    {
        $this->adaptDoiConfiguration(['prefix' => '10.3456/']);
        $docId = $this->createTestDocWithDoi('23.4567/');

        $doiManager = new DoiManager();
        $doi        = $doiManager->register(new Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithExternalDoiAndMissingConfig()
    {
        $docId = $this->createTestDocWithDoi('23.4567/');

        $doiManager = new DoiManager();
        $doi        = $doiManager->register(new Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithLocalRegisteredDoi()
    {
        $this->adaptDoiConfiguration(['prefix' => '10.3456/']);
        $docId = $this->createTestDocWithDoi('10.3456/', 'registered');

        $doiManager = new DoiManager();
        $doi        = $doiManager->register(new Document($docId));
        $this->assertNull($doi);
    }

    public function testRegisterDocWithLocalNonUniqueDoi()
    {
        $this->adaptDoiConfiguration(['prefix' => '10.3456/']);
        $doc1Id = $this->createTestDocWithDoi('10.3456/');

        $doc2Id      = $this->createTestDocWithDoi('10.3456/');
        $doc2        = new Document($doc2Id);
        $identifiers = $doc2->getIdentifier();
        $doi         = $identifiers[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.3456/' . $doc2Id, $doi->getValue());

        // change value to create a DOI conflict
        $doi->setValue('10.3456/' . $doc1Id);
        $doc2->setIdentifier([$doi]);

        $doiManager = new DoiManager();
        $doi        = $doiManager->register($doc2);
        $this->assertNull($doi);
    }

    public function testRegisterDocWithMissingProps()
    {
        $this->adaptDoiConfiguration(['prefix' => '10.3456/']);
        $docId = $this->createTestDocWithDoi('10.3456/');

        $doiManager = new DoiManager();
        $this->setExpectedException(RegistrationException::class);
        $doi = $doiManager->register(new Document($docId));
    }

    public function testRegisterDocWithRequiredPropsButMissingConfig()
    {
        $this->adaptDoiConfiguration(['prefix' => '10.3456/']);
        $docId = $this->createTestDocWithDoi('10.3456/');

        $this->addRequiredPropsToDoc(new Document($docId));

        $doiManager = new DoiManager();
        $this->setExpectedException(RegistrationException::class);
        $doi = $doiManager->register(new Document($docId));
    }

    public function testRegisterDocWithRequiredPropsButCompleteConfig()
    {
        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org',
        ]));
        $this->adaptDoiConfiguration([
            'prefix'       => '10.3456/',
            'registration' => [
                'datacite' => [
                    'username'   => 'test',
                    'password'   => 'secret',
                    'serviceUrl' => 'http://localhost',
                ],
            ],
        ]);
        $docId = $this->createTestDocWithDoi('10.3456/');

        $this->addRequiredPropsToDoc(new Document($docId));

        $doiManager = new DoiManager();
        $this->setExpectedException(RegistrationException::class);
        $doi = $doiManager->register(new Document($docId));
    }

    public function testRegisterPendingWithoutDocs()
    {
        $doiManager = new DoiManager();
        $status     = $doiManager->registerPending();
        $this->assertTrue($status->isNoDocsToProcess());
    }

    public function testRegisterPendingWithDocWithWrongServerState()
    {
        $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new DoiManager();
        $status     = $doiManager->registerPending();
        $this->assertTrue($status->isNoDocsToProcess());
    }

    public function testRegisterPendingWithDoc()
    {
        // add url to config to allow creation of frontdoor URLs
        Config::get()->merge(new Zend_Config([
            'url' => 'http://localhost/opus4/',
        ]));

        $this->adaptDoiConfiguration([
            'prefix'      => '10.5072/',
            'localPrefix' => 'OPUS4',
        ]);

        $docId      = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new DoiManager();
        $status     = $doiManager->registerPending(null);
        $this->assertFalse($status->isNoDocsToProcess());

        $statusOfDoc = $status->getDocsWithDoiStatus()[$docId];
        $this->assertNotNull($statusOfDoc);
        $this->assertTrue($statusOfDoc['error']);
    }

    public function testVerifyRegistered()
    {
        $doiManager = new DoiManager();
        $status     = $doiManager->verifyRegistered();

        $this->assertTrue($status->isNoDocsToProcess());
    }

    public function testVerifyRegisteredBefore()
    {
        // add url to config to allow creation of frontdoor URLs
        Config::get()->merge(new Zend_Config([
            'url' => 'http://localhost/opus4/',
        ]));

        $this->adaptDoiConfiguration([
            'prefix'      => '10.5072/',
            'localPrefix' => 'OPUS4',
        ]);

        $docId      = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered');
        $doiManager = new DoiManager();
        $status     = $doiManager->verifyRegisteredBefore();

        $this->assertFalse($status->isNoDocsToProcess());
        $statusOfDoc = $status->getDocsWithDoiStatus()[$docId];
        $this->assertNotNull($statusOfDoc);
        $this->assertTrue($statusOfDoc['error']);
    }

    public function testVerifyWithUnknownDocId()
    {
        $doiManager = new DoiManager();
        $result     = $doiManager->verify('999');
        $this->assertNull($result);
    }

    public function testVerifyWithDocWithoutDoi()
    {
        $doc   = new Document();
        $docId = $doc->store();

        $doiManager = new DoiManager();
        $result     = $doiManager->verify($docId);
        $this->assertNull($result);
    }

    public function testVerifyWithUnregisteredDoi()
    {
        $docId      = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new DoiManager();
        $result     = $doiManager->verify($docId);
        $this->isNull($result);
    }

    public function testVerifyWithVerifiedDoiWithoutReverification()
    {
        $docId      = $this->createTestDocWithDoi('10.5072/OPUS4-', 'verified');
        $doiManager = new DoiManager();
        $result     = $doiManager->verify($docId, false);
        $this->assertNull($result);
    }

    public function testVerifyWithVerifiedDoiWithReverificationReachableHost()
    {
        $this->verifyWithVerifiedDoiWithReverification('example.org', '80');
    }

    public function testVerifyWithVerifiedDoiWithReverificationUneachableHost()
    {
        $this->verifyWithVerifiedDoiWithReverification('example.org', '54321');
    }

    /**
     * @param string $hostname
     * @param string $port
     */
    public function verifyWithVerifiedDoiWithReverification($hostname, $port)
    {
        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org',
        ]));

        $this->adaptDoiConfiguration([
            'prefix'       => '10.5072/',
            'localPrefix'  => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'username'   => 'test',
                    'password'   => 'secret',
                    'serviceUrl' => "http://$hostname:$port",
                ],
            ],
        ]);

        $docId      = $this->createTestDocWithDoi('10.5072/OPUS4-', 'verified');
        $doiManager = new DoiManager();
        $result     = $doiManager->verify($docId, true);

        $this->assertNotNull($result);
        $this->assertEquals('doi', $result->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $result->getValue());

        $fp = null;
        try {
            $fp = fsockopen($hostname, $port, $errno, $errstr, 5);
        } catch (Exception $e) {
            $fp = false;
        }

        if (! $fp) {
            // wenn keine Netzwerkverbindung zu DataCite hergestellt werden kann,
            // dann wird der DOI-Registrierungsstatus des Dokuments nicht angetastet
            $this->assertEquals('verified', $result->getStatus());
        } else {
            // Status-Downgrade muss erfolgt sein: prüfe, ob das der Fall ist
            $this->assertEquals('registered', $result->getStatus());
        }
    }

    public function testVerifyWithRegisteredDoiAndMissingConfig()
    {
        $this->adaptDoiConfiguration([
            'prefix'      => '10.5072/',
            'localPrefix' => 'OPUS4',
        ]);

        $docId      = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered');
        $doiManager = new DoiManager();
        $result     = $doiManager->verify($docId);

        $this->assertNotNull($result);
        $this->assertEquals('doi', $result->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $result->getValue());
        $this->assertEquals('registered', $result->getStatus());
    }

    public function testVerifyBeforeFilterPositive()
    {
        $this->adaptDoiConfiguration([
            'prefix'       => '10.5072/',
            'localPrefix'  => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'username'   => 'test',
                    'password'   => 'secret',
                    'serviceUrl' => 'http://localhost',
                ],
            ],
        ]);

        $dateTimeZone = new DateTimeZone(date_default_timezone_get());
        $dateTime     = new DateTime('now', $dateTimeZone);
        $currentDate  = $dateTime->format('Y-m-d H:i:s');

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered', $currentDate);

        $doiManager              = new DoiManager();
        $dateTime                = $dateTime->add(new DateInterval('PT1H'));
        $oneHourAfterCurrentDate = $dateTime->format('Y-m-d H:i:s');
        $result                  = $doiManager->verify($docId, true, $oneHourAfterCurrentDate);

        $this->assertNotNull($result);
        $this->assertEquals('doi', $result->getType());
        $this->assertEquals('10.5072/OPUS4-' . $docId, $result->getValue());
        $this->assertEquals('registered', $result->getStatus());
    }

    public function testVerifyBeforeFilterNegative()
    {
        $this->adaptDoiConfiguration([
            'prefix'       => '10.5072/',
            'localPrefix'  => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'username'   => 'test',
                    'password'   => 'secret',
                    'serviceUrl' => 'http://localhost',
                ],
            ],
        ]);

        $dateTimeZone = new DateTimeZone(date_default_timezone_get());
        $dateTime     = new DateTime('now', $dateTimeZone);
        $currentDate  = $dateTime->format('Y-m-d H:i:s');

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered', $currentDate);

        $doiManager               = new DoiManager();
        $dateTime                 = $dateTime->sub(new DateInterval('PT1H'));
        $oneHourBeforeCurrentDate = $dateTime->format('Y-m-d H:i:s');
        $result                   = $doiManager->verify($docId, true, $oneHourBeforeCurrentDate);

        $this->assertNull($result);
    }

    public function testGetAllEmptyResult()
    {
        $doiManager = new DoiManager();
        $result     = $doiManager->getAll();
        $this->assertEmpty($result);
    }

    public function testGetAllOneExternalDoi()
    {
        $this->createTestDocWithDoi('10.5072');
        $doiManager = new DoiManager();
        $result     = $doiManager->getAll();
        $this->assertEmpty($result);
    }

    public function testGetAll()
    {
        $this->adaptDoiConfiguration([
            'prefix'      => '10.5072/',
            'localPrefix' => 'OPUS4',
        ]);
        $docId      = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $doiManager = new DoiManager();
        $result     = $doiManager->getAll();
        $this->assertCount(1, $result);
    }

    public function testGetAllStatusFiltered()
    {
        $this->adaptDoiConfiguration([
            'prefix'      => '10.5072/',
            'localPrefix' => 'OPUS4',
        ]);

        $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered');

        $doiManager = new DoiManager();

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

    public function testGenerateNewDoiMissingConfig()
    {
        $doc = new Document();
        $doc->store();
        $doiManager = new DoiManager();
        $this->setExpectedException(DoiException::class);
        $doiManager->generateNewDoi($doc);
    }

    public function testGenerateNewDoiMissingGeneratorClass()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => 'Opus\Doi\Generator\MissingGenerator',
        ]);

        $doc = new Document();
        $doc->store();
        $doiManager = new DoiManager();
        $this->setExpectedException(DoiException::class);
        $doiManager->generateNewDoi($doc);
    }

    public function testGenerateNewDoiInvalidDocId()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
        ]);

        $doc = new Document();
        $doc->store();
        $doiManager = new DoiManager();
        $this->setExpectedException(DoiException::class);
        $doiManager->generateNewDoi('999');
    }

    public function testGenerateNewDoiWithDocId()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '10.5072/',
            'localPrefix'    => 'OPUS4',
        ]);

        $doc        = new Document();
        $docId      = $doc->store();
        $doiManager = new DoiManager();
        $doiValue   = $doiManager->generateNewDoi($docId);

        $this->assertEquals('10.5072/OPUS4-' . $docId, $doiValue);
    }

    public function testGenerateNewDoiWithDoc()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '10.5072/',
            'localPrefix'    => 'OPUS4',
        ]);

        $doc        = new Document();
        $docId      = $doc->store();
        $doiManager = new DoiManager();
        $doiValue   = $doiManager->generateNewDoi(new Document($docId));

        $this->assertEquals('10.5072/OPUS4-' . $docId, $doiValue);
    }

    public function testDeleteMetadataForDoiDocWithoutDoi()
    {
        $doc = new Document();
        $doc->store();

        $doiManager = new DoiManager();
        $doiManager->deleteMetadataForDoi($doc);
    }

    public function testDeleteMetadataForDoiDocWithExternalDoi()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '10.5072/',
            'localPrefix'    => 'OPUS4',
        ]);

        $docId = $this->createTestDocWithDoi('10.9999/system-');

        $doiManager = new DoiManager();
        $doiManager->deleteMetadataForDoi(new Document($docId));
    }

    public function testDeleteMetadataForDoiDocWithLocalDoi()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '10.5072/',
            'localPrefix'    => 'OPUS4',
        ]);

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');

        $doiManager = new DoiManager();
        $doiManager->deleteMetadataForDoi(new Document($docId));
    }

    public function testDeleteMetadataForDoiDocWithLocalRegisteredDoi()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '10.5072/',
            'localPrefix'    => 'OPUS4',
            'registration'   => [
                'datacite' => [
                    'username'   => 'test',
                    'password'   => 'secret',
                    'serviceUrl' => 'http://localhost',
                ],
            ],
        ]);

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'registered');

        $doiManager = new DoiManager();
        $doiManager->deleteMetadataForDoi(new Document($docId));
    }

    public function testDeleteMetadataForDoiDocWithLocalVerifiedDoi()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '10.5072/',
            'localPrefix'    => 'OPUS4',
            'registration'   => [
                'datacite' => [
                    'username'   => 'test',
                    'password'   => 'secret',
                    'serviceUrl' => 'http://localhost',
                ],
            ],
        ]);

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-', 'verified');

        $doiManager = new DoiManager();
        $doiManager->deleteMetadataForDoi(new Document($docId));
    }

    public function testUpdateLandingPageUrlOfDoiWithMissingConfig()
    {
        $doiManager = new DoiManager();
        $this->setExpectedException(DoiException::class);
        $doiManager->updateLandingPageUrlOfDoi('10.5072/OPUS4-999', 'http://localhost/frontdoor/999');
    }

    public function testUpdateLandingPageUrlOfDoi()
    {
        $this->adaptDoiConfiguration([
            'generatorClass' => DefaultGenerator::class,
            'prefix'         => '10.5072/',
            'localPrefix'    => 'OPUS4',
            'registration'   => [
                'datacite' => [
                    'username'   => 'test',
                    'password'   => 'secret',
                    'serviceUrl' => 'http://localhost',
                ],
            ],
        ]);

        $doiManager = new DoiManager();
        $this->setExpectedException(DoiException::class);
        $doiManager->updateLandingPageUrlOfDoi('10.5072/OPUS4-999', 'http://localhost/frontdoor/999');
    }

    /**
     * @param Zend_Config $doiConfig
     */
    private function adaptDoiConfiguration($doiConfig)
    {
        Config::get()->merge(new Zend_Config(['doi' => $doiConfig]));
    }

    /**
     * @param Document $doc
     */
    private function addRequiredPropsToDoc($doc)
    {
        $doc->setCompletedYear(2018);
        $doc->setServerState('unpublished');
        $doc->setType('book');
        $doc->setPublisherName('ACME corp');

        $author = new Person();
        $author->setLastName('Doe');
        $author->setFirstName('John');
        $doc->addPersonAuthor($author);

        $title = new Title();
        $title->setType('main');
        $title->setValue('Document without meaningful title');
        $title->setLanguage('deu');
        $doc->addTitleMain($title);

        $doc->store();
    }

    /**
     * @param string      $doiPrefix
     * @param null|string $status
     * @param null|string $registrationTs
     * @return int
     * @throws ModelException
     */
    private function createTestDocWithDoi($doiPrefix, $status = null, $registrationTs = null)
    {
        $doc   = new Document();
        $docId = $doc->store();

        $doc = new Document($docId);
        $doi = new Identifier();
        $doi->setType('doi');
        $doi->setValue($doiPrefix . $docId);
        if ($status !== null) {
            $doi->setStatus($status);
        }
        if ($registrationTs !== null) {
            $doi->setRegistrationTs($registrationTs);
        }
        $doc->setIdentifier([$doi]);
        $doc->store();

        return $docId;
    }

    public function testGetLandingPageBaseUrl()
    {
        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org/',
            'doi' => [
                'landingPageBaseUri' => '/frontdoor/index/index/docId',
            ],
        ]));

        $manager = new DoiManager();

        $this->assertNotNull($manager->getLandingPageBaseUrl());
        $this->assertEquals('http://www.example.org/frontdoor/index/index/docId/', $manager->getLandingPageBaseUrl());
    }

    public function testGetLangingPageBaseUrlConfiguredForShortUrl()
    {
        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org/',
            'doi' => [
                'landingPageBaseUri' => '',
            ],
        ]));

        $manager = new DoiManager();

        $this->assertNotNull($manager->getLandingPageBaseUrl());
        $this->assertEquals('http://www.example.org/', $manager->getLandingPageBaseUrl());
    }

    public function testGetLandingPageBaseUrlWithoutRepositoryUrl()
    {
        $manager = new DoiManager();

        $this->setExpectedException(
            DoiException::class,
            'No URL for repository configured. Cannot generate landing page URL.'
        );
        $manager->getLandingPageBaseUrl();
    }

    public function testGetLandingPageUrlOfDoc()
    {
        $doc   = new Document();
        $docId = $doc->store();

        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org/',
            'doi' => [
                'landingPageBaseUri' => '/frontdoor/index/index/docId/',
            ],
        ]));

        $manager = new DoiManager();

        $this->assertEquals(
            "http://www.example.org/frontdoor/index/index/docId/$docId",
            $manager->getLandingPageUrlOfDoc($doc)
        );
    }

    public function testGetLandingPageUrlOfDocConfiguredWithoutSlashes()
    {
        $doc   = new Document();
        $docId = $doc->store();

        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org/',
            'doi' => [
                'landingPageBaseUri' => 'frontdoor/index/index/docId',
            ],
        ]));

        $manager = new DoiManager();

        $this->assertEquals(
            "http://www.example.org/frontdoor/index/index/docId/$docId",
            $manager->getLandingPageUrlOfDoc($doc)
        );
    }

    public function testGetLandingPageUrlOfDocForShortUrl()
    {
        $doc   = new Document();
        $docId = $doc->store();

        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org/',
            'doi' => [
                'landingPageBaseUri' => null,
            ],
        ]));

        $manager = new DoiManager();

        $this->assertEquals(
            "http://www.example.org/$docId",
            $manager->getLandingPageUrlOfDoc($doc)
        );
    }

    public function testGetLandingPageUrlOfDocForId()
    {
        Config::get()->merge(new Zend_Config([
            'url' => 'http://www.example.org/',
            'doi' => [
                'landingPageBaseUri' => '/frontdoor/index/index/docId/',
            ],
        ]));

        $docId = 17;

        $manager = new DoiManager();

        $this->assertEquals(
            "http://www.example.org/frontdoor/index/index/docId/$docId",
            $manager->getLandingPageUrlOfDoc($docId)
        );
    }
}
