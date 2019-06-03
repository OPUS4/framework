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
 * @author      Julian Heise (heise@zib.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Document_Plugin_IdentifierUrnTest extends TestCase
{

    public function testAutoGenerateUrn()
    {
        $model = new Opus_Document();
        $model->setServerState('published');
        $model->store();

        $this->assertEquals(0, count($model->getIdentifier()));
        $this->assertEquals(0, count($model->getIdentifierUrn()));

        $model->addFile()->setVisibleInOai(0);
        $model->addFile()->setVisibleInOai(1);

        $plugin = new Opus_Document_Plugin_IdentifierUrn();
        $plugin->postStoreInternal($model);

        $this->assertTrue(
            $model->hasField('Identifier'),
            'Model does not have field "Identifier"'
        );
        $urns = $model->getIdentifier();

        $this->assertNotNull($urns, 'IdentifierUrn is NULL');
        $this->assertEquals(1, count($urns));
        $this->assertEquals('urn', $urns[0]->getType());

        $config = Zend_Registry::get('Zend_Config');
        $urnItem = new Opus_Identifier_Urn($config->urn->nid, $config->urn->nss);
        $checkDigit = $urnItem->getCheckDigit($model->getId());
        $urnString = 'urn:' . $config->urn->nid . ':' . $config->urn->nss . '-' . $model->getId() . $checkDigit;

        $this->assertEquals($urnString, $urns[0]->getValue());
    }

    /**
     * Regression test for OPUSVIER-2252 - don't assign URN if not "published"
     * Check both fields: Identifier and IdentifierUrn.
     */
    public function testAutoGenerateUrnSkippedIfNotPublished()
    {
        $model = new Opus_Document();
        $model->setServerState('unpublished');
        $model->store();

        $model->addFile()->setVisibleInOai(0);
        $model->addFile()->setVisibleInOai(1);

        $this->assertTrue(
            $model->hasField('IdentifierUrn'),
            'Model does not have field "IdentifierUrn"'
        );
        $urns = $model->getIdentifierUrn();

        $this->assertNotNull($urns, 'IdentifierUrn is NULL');
        $this->assertEquals(0, count($urns));

        $this->assertTrue(
            $model->hasField('Identifier'),
            'Model does not have field "Identifier"'
        );
        $identifiers = $model->getIdentifier();

        $this->assertNotNull($identifiers, 'Identifier is NULL');
        $this->assertEquals(0, count($identifiers));
    }

    /**
     * Regression test for OPUSVIER-2445 - don't assign URN if no visible file
     */
    public function testAutoGenerateUrnSkippedIfPublishedAndNoVisibleFiles()
    {
        $model = new Opus_Document();
        $model->setServerState('published');
        $model->addFile()->setVisibleInOai(0);

        $plugin = new Opus_Document_Plugin_IdentifierUrn();
        $plugin->postStoreInternal($model);

        $this->assertEquals(0, count($model->getIdentifier()));
        $this->assertEquals(0, count($model->getIdentifierUrn()));
    }

    /**
     * Test urnAlreadyPresent in isolation
     */
    public function testUrnAlreadyPresent()
    {
        $plugin = new Opus_Document_Plugin_IdentifierUrn();

        $model = new Opus_Document();
        $this->assertFalse($plugin->urnAlreadyPresent($model));

        $model = new Opus_Document();
        $model->addIdentifier()->setType('foo');
        $this->assertFalse($plugin->urnAlreadyPresent($model));

        $model = new Opus_Document();
        $model->addIdentifier()->setType('urn');
        $this->assertTrue($plugin->urnAlreadyPresent($model));

        $model = new Opus_Document();
        $model->addIdentifierUrn();
        $this->assertTrue($plugin->urnAlreadyPresent($model));
    }

    /**
     * Test allowUrnOnThisDocument in isolation
     */
    public function testAllowUrnOnThisDocument()
    {
        $plugin = new Opus_Document_Plugin_IdentifierUrn();

        $model = new Opus_Document();
        $this->assertFalse($plugin->allowUrnOnThisDocument($model));

        $model = new Opus_Document();
        $model->addFile()->setVisibleInOai(0);
        $this->assertFalse($plugin->allowUrnOnThisDocument($model));

        $model = new Opus_Document();
        $model->addFile()->setVisibleInOai(1);
        $this->assertTrue($plugin->allowUrnOnThisDocument($model));
    }

    /**
     * ein bereits veröffentlichtes Dokument ohne URN soll beim erneuten Speichern keine URN erhalten
     */
    public function testOPUSVIER3994wPublishedDoc()
    {
        $doc = new Opus_Document();
        $doc->setServerState('published');
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertEmpty($doc->getIdentifier());

        $this->addFileToDoc($doc);
        $doc->store();

        $urnConfig = [
            'autoCreate' => 1
        ];
        $this->adaptUrnConfiguration($urnConfig);

        $doc = new Opus_Document($docId);
        // Änderung eines Wertes, damit store-Methode tatsächlich aufgerufen wird
        $doc->setPageFirst('1');
        // provoziere einen Statusübergang von published nach published
        $doc->setServerState('published');
        $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertEmpty($doc->getIdentifier());
    }

    /**
     * ein noch nicht veröffentlichtes Dokument ohne URN soll beim erneuten Speichern eine URN erhalten
     */
    public function testOPUSVIER3994wUnpublishedDoc()
    {
        $doc = new Opus_Document();
        $this->addFileToDoc($doc);
        $doc->setServerState('unpublished');
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertEmpty($doc->getIdentifier());

        $urnConfig = [
            'autoCreate' => 1,
            'nss' => 'nss',
            'nid' => 'nid'
        ];
        $this->adaptUrnConfiguration($urnConfig);

        $doc = new Opus_Document($docId);
        // Änderung eines Wertes, damit store-Methode tatsächlich aufgerufen wird
        $doc->setPageFirst('1');
        $doc->setServerState('published');
        $doc->store();
        $this->assertNotEmpty($doc->getIdentifier());

        $urn = $doc->getIdentifier()[0];
        $this->assertEquals('urn', $urn->getType());
        $urnPrefix = 'urn:nid:nss-' . $docId;
        $this->assertTrue(substr($urn->getValue(), 0, strlen($urnPrefix)) === $urnPrefix);
    }

    /**
     * mehrfacher Aufruf der setServerState-Methode mit unterschiedlichen Werten
     */
    public function testOPUSVIER3994multipleSetter()
    {
        $doc = new Opus_Document();
        $this->addFileToDoc($doc);
        $doc->setServerState('unpublished');
        $docId = $doc->store();

        $doc = new Opus_Document($docId);
        $this->assertEmpty($doc->getIdentifier());

        $urnConfig = [
            'autoCreate' => 1,
            'nss' => 'nss',
            'nid' => 'nid'
        ];
        $this->adaptUrnConfiguration($urnConfig);

        $doc = new Opus_Document($docId);
        // Änderung eines Wertes, damit store-Methode tatsächlich aufgerufen wird
        $doc->setPageFirst('1');
        $doc->setServerState('published');
        $doc->setServerState('unpublished');
        $doc->store();

        // es sollte keine URN erzeugt worden sein, weil sich der serverState effektiv nicht geändert hat
        $this->assertEmpty($doc->getIdentifier());

        $doc = new Opus_Document($docId);
        // Änderung eines Wertes, damit store-Methode tatsächlich aufgerufen wird
        $doc->setPageFirst('2');
        $doc->setServerState('unpublished');
        $doc->setServerState('published');
        $doc->store();

        // es sollte eine URN erzeugt worden sein, weil sich der serverState effektiv geändert hat
        $this->assertNotEmpty($doc->getIdentifier());

        $urn = $doc->getIdentifier()[0];
        $this->assertEquals('urn', $urn->getType());
        $urnPrefix = 'urn:nid:nss-' . $docId;
        $this->assertTrue(substr($urn->getValue(), 0, strlen($urnPrefix)) === $urnPrefix);
    }

    /**
     * ein neu gespeichertes Dokument bekommt beim Veröffentlichen eine URN, sofern autoCreate aktiviert
     */
    public function testOPUSVIER3994publishedDocGetsURN()
    {
        $urnConfig = [
            'autoCreate' => 1,
            'nss' => 'nss',
            'nid' => 'nid'
        ];
        $this->adaptUrnConfiguration($urnConfig);

        $doc = new Opus_Document();
        $this->addFileToDoc($doc);
        $doc->setServerState('unpublished');
        $doc->setServerState('published');
        $docId = $doc->store();

        $doc = new Opus_Document($docId);

        $this->assertNotEmpty($doc->getIdentifier());

        $urn = $doc->getIdentifier()[0];
        $this->assertEquals('urn', $urn->getType());
        $urnPrefix = 'urn:nid:nss-' . $docId;
        $this->assertTrue(substr($urn->getValue(), 0, strlen($urnPrefix)) === $urnPrefix);
    }

    private function adaptUrnConfiguration($urnConfig)
    {
        Zend_Registry::set(
            'Zend_Config',
            Zend_Registry::get('Zend_Config')->merge(new Zend_Config(['urn' => $urnConfig]))
        );
    }

    private function addFileToDoc(Opus_Document $doc)
    {
        $visibleFile = new Opus_File();
        $visibleFile->setPathName('visible_file.txt');
        $visibleFile->setVisibleInOai(true);

        $doc->addFile($visibleFile);
    }
}
