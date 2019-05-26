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
 * @package     Opus_Document_Plugin
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Document_Plugin_IdentifierDoiTest extends TestCase
{

    const ENRICHMENT_KEY_NAME = 'opus.doi.autoCreate';

    private function setupEnrichmentKey()
    {
        $enrichmentKey = new Opus_EnrichmentKey();
        $enrichmentKey->setName(self::ENRICHMENT_KEY_NAME);
        $enrichmentKey->store();
    }

    private function adaptDoiConfiguration($doiConfig)
    {
        Zend_Registry::set(
            'Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(new Zend_Config(['doi' => $doiConfig]))
        );
    }

    private function createMinimalDocument($enrichmentValue = null)
    {
        $model = new Opus_Document();
        $model->setServerState('published');

        if (! is_null($enrichmentValue)) {
            $this->setupEnrichmentKey();

            $enrichment = new Opus_Enrichment();
            $enrichment->setKeyName(self::ENRICHMENT_KEY_NAME);
            $enrichment->setValue($enrichmentValue);

            $enrichments = [];
            $enrichments[] = $enrichment;
            $model->setEnrichment($enrichments);
        }

        $docId = $model->store();
        return $docId;
    }

    /**
     * Überprüft, dass Konfigurationseinstellung doi.autoCreate korrekt angewendet wird.
     */
    public function testDisabledAutoCreationOfDoiInConfig()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => 0
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createMinimalDocument();

        $this->assertNoGeneratedDoi($docId);
    }

    /**
     * Überprüft, dass Konfigurationseinstellung doi.autoCreate korrekt angewendet wird.
     */
    public function testDisabledAutoCreationOfDoiInConfigAlt()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => false
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createMinimalDocument();

        $this->assertNoGeneratedDoi($docId);
    }

    /**
     * Überprüft, dass Enrichment opus.doi.autoCreate die Konfigurationseinstellung überschreibt.
     */
    public function testDisabledAutoCreationOfDoi()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => 1
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createMinimalDocument('false');

        $this->assertNoGeneratedDoi($docId, 1);
    }

    /**
     * Überprüft, dass Konfigurationseinstellung doi.autoCreate korrekt angewendet wird.
     */
    public function testEnabledAutoCreationOfDoiInConfig()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => 1
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createMinimalDocument();

        $this->assertGeneratedDoi($docId);
    }

    /**
     * Überprüft, dass Konfigurationseinstellung doi.autoCreate korrekt angewendet wird.
     */
    public function testEnabledAutoCreationOfDoiInConfigAlt()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => true
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createMinimalDocument();

        $this->assertGeneratedDoi($docId);
    }

    /**
     * Überprüft, dass Enrichment opus.doi.autoCreate die Konfigurationseinstellung überschreibt.
     */
    public function testEnabledAutoCreationOfDoi()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => 0
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createMinimalDocument('true');

        $this->assertGeneratedDoi($docId, 1);
    }

    private function assertNoGeneratedDoi($docId, $numOfEnrichments = 0)
    {
        $model = new Opus_Document($docId);
        $this->assertEquals(0, count($model->getIdentifier()));
        $this->assertEquals(0, count($model->getIdentifierDoi()));
        $this->assertEquals($numOfEnrichments, count($model->getEnrichment()));

        $dois = $model->getIdentifier();
        $this->assertEmpty($dois);
    }

    private function assertGeneratedDoi($docId, $numOfEnrichments = 0)
    {
        $model = new Opus_Document($docId);
        $this->assertEquals(1, count($model->getIdentifier()));
        $this->assertEquals(1, count($model->getIdentifierDoi()));
        $this->assertEquals($numOfEnrichments, count($model->getEnrichment()));

        $dois = $model->getIdentifier();
        $this->assertCount(1, $dois);

        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.000/opustest-' . $docId, $doi->getValue());
    }

    public function testSkipGenerationIfDoiAlreadyExists()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => 1
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc = new Opus_Document();
        $doc->setServerState('unpublished');

        $doi = new Opus_Identifier();
        $doi->setType('doi');
        $doi->setValue('1234');
        $dois = [];
        $dois[] = $doi;
        $doc->setIdentifier($dois);

        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $doc->setServerState('published');
        $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertCount(1, $doc->getIdentifier());
        $this->assertCount(1, $doc->getIdentifierDoi());

        $dois = $doc->getIdentifier();
        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('1234', $doi->getValue());
        $this->assertNull($doi->getStatus());
    }

    /**
     * Aktuell wird beim Löschen eines Dokuments mit einer lokalen DOI
     * lediglich der Metadatensatz bei DataCite als "inactive" markiert.
     * Der Status der lokalen DOI wird nicht verändert.
     *
     */
    public function testHandleDeleteEvent()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'autoCreate' => 1,
            'doi.registration.datacite.serviceUrl' => 'localhost'
        ];
        $this->adaptDoiConfiguration($doiConfig);
        $docId = $this->createMinimalDocument();

        $doc = new Opus_Document($docId);
        // simuliere eine erfolgreiche DOI-Registrierung durch Setzen des Status auf registered
        $dois = $doc->getIdentifier();
        $doi = $dois[0];
        $doi->setStatus('registered');
        $doc->setIdentifier($dois);
        $doc->store();

        $doc = new Opus_Document($docId);
        $doc->delete();

        $doc = new Opus_Document($docId);
        $dois = $doc->getIdentifier();
        $this->assertCount(1, $dois);
        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.000/opustest-' . $docId, $doi->getValue());
        $this->assertEquals('registered', $doi->getStatus());
    }

    public function testDoiGenerationWithMissingGenerationClass()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_MissingGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'autoCreate' => 1,
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $docId = $this->createMinimalDocument();

        $doc = new Opus_Document($docId);
        $identifiers = $doc->getIdentifier();
        $this->assertEmpty($identifiers);
    }

    public function testDoiRegistration()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'autoCreate' => 1,
            'registerAtPublish' => 1,
            'doi.registration.datacite.serviceUrl' => 'localhost'
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $docId = $this->createMinimalDocument();

        $doc = new Opus_Document($docId);
        $dois = $doc->getIdentifier();
        $this->assertCount(1, $dois);

        $doi = $dois[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.000/opustest-' . $docId, $doi->getValue());
        $this->assertNull($doi->getStatus());
    }

    public function testDoiRegistrationWithMissingLocalDoi()
    {
        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'autoCreate' => 0,
            'registerAtPublish' => 1,
            'doi.registration.datacite.serviceUrl' => 'localhost'
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $docId = $this->createMinimalDocument();

        $doc = new Opus_Document($docId);
        $this->assertEmpty($doc->getIdentifier());
    }

    /**
     * ein bereits veröffentlichtes Dokument ohne DOI soll beim erneuten Speichern keine DOI erhalten
     */
    public function testOPUSVIER3994wPublishedDoc()
    {
        $docId = $this->createMinimalDocument();
        $doc = new Opus_Document($docId);
        $this->assertEmpty($doc->getIdentifier());

        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => 1
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc = new Opus_Document($docId);
        // provoziere einen Statusübergang von published nach published
        $doc->setServerState('published');
        $doc->store();
        $this->assertEmpty($doc->getIdentifier());
    }

    /**
     * ein noch nicht veröffentlichtes Dokument ohne DOI soll beim erneuten Speichern eine DOI erhalten
     */
    public function testOPUSVIER3994wUnpublishedDoc()
    {
        $doc = new Opus_Document();
        $doc->setServerState('unpublished');
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertEmpty($doc->getIdentifier());

        $doiConfig = [
            'generatorClass' => 'Opus_Doi_Generator_DefaultGenerator',
            'prefix' => '10.000/',
            'localPrefix' => 'opustest',
            'registerAtPublish' => 0,
            'autoCreate' => 1
        ];
        $this->adaptDoiConfiguration($doiConfig);

        $doc = new Opus_Document($docId);
        $doc->setServerState('published');
        $doc->store();
        $this->assertNotEmpty($doc->getIdentifier());

        $doi = $doc->getIdentifier()[0];
        $this->assertEquals('doi', $doi->getType());
        $this->assertEquals('10.000/opustest-' . $docId, $doi->getValue());
    }
}
