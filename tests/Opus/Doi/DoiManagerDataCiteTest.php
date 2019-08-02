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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Class Opus_Doi_DoiManagerDataCiteTest
 *
 * The tests in this class require access to the DataCite testing environment using a username and a passwort. This
 * needs to be setup in the 'config.ini' or 'tests.ini'.
 */
class Opus_Doi_DoiManagerDataCiteTest extends TestCase
{

    /**
     * TODO determine 'skipping' based on configuration (environment)
     */
    public function setUp()
    {
        parent::setUp();
        $this->markTestSkipped(
            'kann nur für manuellen Test verwendet werden, da DataCite-Testumgebung erforderlich' .
            ' (Username und Password werden in config.ini gesetzt)'
        );
    }


    public function testRegisterAndVerifyDocSuccessfully()
    {
        // add url to config to allow creation of frontdoor URLs
        Zend_Registry::get('Zend_Config')->merge(new Zend_Config([
            'url' => 'http://localhost/opus4/'
        ]));

        $this->adaptDoiConfiguration([
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'serviceUrl' => 'https://mds.test.datacite.org'
                ]
            ]
        ]);
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

    public function testVerifySuccessfully()
    {
        // add url to config to allow creation of frontdoor URLs
        Zend_Registry::set(
            'Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(
                new Zend_Config(['url' => 'http://localhost/opus4/'])
            )
        );

        $this->adaptDoiConfiguration([
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'serviceUrl' => 'https://mds.test.datacite.org'
                ]
            ]
        ]);
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

    public function testVerifyFailed()
    {
        // add url to config to allow creation of frontdoor URLs
        Zend_Registry::set(
            'Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(
                new Zend_Config(['url' => 'http://localhost/opus4/'])
            )
        );

        $this->adaptDoiConfiguration([
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'serviceUrl' => 'https://mds.test.datacite.org'
                ]
            ]
        ]);
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

    public function testUpdateLandingPageUrlOfDoiWithExistingDoi()
    {
        $config = Zend_Registry::get('Zend_Config');

        Zend_Registry::set(
            'Zend_Config',
            $config->merge(
                new Zend_Config(['url' => 'http://localhost/opus4'])
            )
        );

        $this->adaptDoiConfiguration([
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'serviceUrl' => 'https://mds.test.datacite.org'
                ]
            ]
        ]);

        $docId = $this->createTestDocWithDoi('10.5072/OPUS4-');
        $this->addRequiredPropsToDoc(new Opus_Document($docId));

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->register($docId, true);
        $this->assertEquals('registered', $doi->getStatus());

        $doi = $doiManager->verify($docId);
        $this->assertEquals('verified', $doi->getStatus());

        $doiManager->updateLandingPageUrlOfDoi(
            '10.5072/OPUS4-' . $docId,
            'http://localhost/opus5/frontdoor/index/index/' . $docId
        );

        $doi = $doiManager->verify($docId);
        $this->assertEquals('registered', $doi->getStatus());

        Zend_Registry::set(
            'Zend_Config',
            $config->merge(
                new Zend_Config(['url' => 'http://localhost/opus5'])
            )
        );

        $this->adaptDoiConfiguration([
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.5072/',
            'localPrefix' => 'OPUS4',
            'registration' => [
                'datacite' => [
                    'serviceUrl' => 'https://mds.test.datacite.org'
                ]
            ]
        ]);

        $doiManager = new Opus_Doi_DoiManager();
        $doi = $doiManager->verify($docId);
        $this->assertEquals('verified', $doi->getStatus());
    }
}
