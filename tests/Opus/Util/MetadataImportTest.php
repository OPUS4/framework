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
 * @package     Opus_Util
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */
class Opus_Util_MetadataImportTest extends TestCase {

    private $documentImported;
    private $filename;
    private $xml;
    private $xmlDir;

    public function setUp() {
        parent::setUp();
        $this->documentImported = false;
        $this->xmlDir = dirname(dirname(dirname(__FILE__))) . '/import/';
    }

    public function tearDown() {
        if ($this->documentImported) {
            $ids = Opus_Document::getAllIds();
            $last_id = array_pop($ids);
            $doc = new Opus_Document($last_id);
            $doc->deletePermanent();
        }
        parent::tearDown();
    }

    public function testInvalidXmlExceptionWhenNotWellFormed() {
        $importer = new Opus_Util_MetadataImport('This ist no XML');
        $this->setExpectedException('Opus_Util_MetadataImportInvalidXmlException');
        $importer->run();
    }

    public function testInvalidXmlExceptionWhenNotWellFormedWithFile() {
        $importer = new Opus_Util_MetadataImport($this->xmlDir . 'test_import_badformed.xml', true);
        $this->setExpectedException('Opus_Util_MetadataImportInvalidXmlException');
        $importer->run();
    }

    public function testInvalidXmlException() {
        $this->filename = 'test_import_schemainvalid.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $this->setExpectedException('Opus_Util_MetadataImportInvalidXmlException');
        $importer->run();
    }

    public function testNoMetadataImportException() {
        $this->filename = 'test_import_minimal.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $e = null;
        try {
            $importer->run();
        } catch (Opus_Util_MetadataImportInvalidXmlException $ex) {
            $e = $ex;
        } catch (Opus_Util_MetadataImportSkippedDocumentsException $ex) {
            $e = $ex;
        }
        $this->assertNull($e, 'unexpected exception was thrown: ' . get_class($e));

        $this->documentImported = true;
    }

    public function testSkippedDocumentsException() {
        $this->filename = 'test_import_invalid_collectionid.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $this->setExpectedException('Opus_Util_MetadataImportSkippedDocumentsException');
        $importer->run();
    }

    /**
     * Test for document update
     */
    public function testUpdateDocument() {
        $this->filename = 'test_import_minimal.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $importer->run();
        try {
            $importedDoc = new Opus_Document(1);
            $titleMain = $importedDoc->getTitleMain();
            $this->assertEquals('La Vie un Rose', $titleMain[0]->getValue());
        } catch (Opus_Model_NotFoundException $e) {
            $this->fail("Import failed");
        }

        $this->filename = 'test_import_minimal_update1.xml';
        $this->loadInputFile();

        $importer = new Opus_Util_MetadataImport($this->xml);
        $importer->run();

        $updatedDoc = new Opus_Document(1);
        $titleMain = $updatedDoc->getTitleMain();
        $abstracts = $updatedDoc->getTitleAbstract();

        $this->assertEquals('La Vie en Rose', $titleMain[0]->getValue(), "Update failed");
        $this->assertEquals(1, count($abstracts), 'Expected 1 abstract after update');
    }

    /**
     * Regression Test for OPUSVIER-3211
     */
    public function testUpdateKeepField() {
        $this->filename = 'test_import_minimal.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $importer->run();


        $this->filename = 'test_import_minimal_update1.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $importer->run();
        try {
            $importedDoc = new Opus_Document(1);
            $titleMain = $importedDoc->getTitleMain();
            $this->assertEquals('La Vie en Rose', $titleMain[0]->getValue());
        } catch (Opus_Model_NotFoundException $e) {
            $this->fail("Import failed");
        }

        $this->filename = 'test_import_minimal_update2.xml';
        $this->loadInputFile();

        $importer = new Opus_Util_MetadataImport($this->xml);
        $importer->keepFieldsOnUpdate(array('TitleAbstract'));
        $importer->run();

        $updatedDoc = new Opus_Document(1);
        $abstracts = $updatedDoc->getTitleAbstract();

        $this->assertEquals(2, count($abstracts), 'Expected 2 abstracts after update');
    }

    /**
     * Regression Test for OPUSVIER-3204
     */
    public function testSkippedDocumentsExceptionOnUpdateDoesNotDestroyExistingDocument() {
        $this->filename = 'test_import_minimal.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $importer->run();
        try {
            $importedDoc = new Opus_Document(1);
            $titleMain = $importedDoc->getTitleMain();
            $this->assertEquals('La Vie un Rose', $titleMain[0]->getValue());
        } catch (Opus_Model_NotFoundException $e) {
            $this->fail("Import failed");
        }
        $this->filename = 'test_import_minimal_corrupted_update.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);
        try {
            $importer->run();
        } catch (Opus_Model_NotFoundException $e) {
            $this->fail("Document was deleted during update.");
        } catch (Opus_Util_MetadataImportSkippedDocumentsException $e) {
            // expected exception
        } catch (Exception $e) {
            $this->fail('unexpected exception was thrown: ' . get_class($e));
        }

        $updatedDoc = new Opus_Document(1);
        $titleMain = $updatedDoc->getTitleMain();
        $this->assertNotEmpty($titleMain, 'Existing Document was corrupted on failed update attempt.');
    }

    private function loadInputFile() {
        $doc = new DOMDocument();
        $doc->load($this->xmlDir . $this->filename);
        $this->xml = $doc->saveXML();
    }

    /*
     * testet ob true/false als Wert für BelongsToBibliography und allowEmailContact akzeptiert wird
     */
    public function testRegression2570() {
        $this->filename = 'test_import_regression2570.xml';
        $this->loadInputFile();
        $importer = new Opus_Util_MetadataImport($this->xml);

        $this->setExpectedException('Opus_Util_MetadataImportSkippedDocumentsException');
        $importer->run();
    }
}
