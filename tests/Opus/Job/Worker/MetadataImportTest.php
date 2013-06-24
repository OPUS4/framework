<?php
/*
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
 * @category    Framework Unit Test
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @copyright   Copyright (c) 2008-2013, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Job_Worker_MetadataImportTest extends TestCase {


    private $documentImported;
    
    private $filename;
    
    private $job;

    private $worker;

    private $xml;

    private $xmlDir;
    
    
    public function setUp() {
        parent::setUp();
	$this->documentImported = false;
        $this->job = new Opus_Job();
        $this->worker = new Opus_Job_Worker_MetadataImport();
	$this->xml = null;
	$this->xmlDir = dirname(dirname(dirname(dirname(__FILE__)))) . '/import/';

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

    
    public function testActivationLabel() {
         $this->assertEquals(Opus_Job_Worker_MetadataImport::LABEL, $this->worker->getActivationLabel());
    }


    public function testWrongLabelException() {
        $this->job->setLabel('wrong-label');
        $this->job->setData(array('xml' => $this->xml));
	$this->setExpectedException('Opus_Job_Worker_InvalidJobException');
        $this->worker->work($this->job);
    }
    
    
     public function testMissingDataException() {
        $this->job->setLabel('opus-metadata-import-notification');
	$this->setExpectedException('Opus_Job_Worker_InvalidJobException');
        $this->worker->work($this->job);
    }   


     public function testIncompleteDataException() {
        $this->job->setLabel('opus-metadata-import-notification');
        $this->job->setData(array('xml' => $this->xml));
	$this->setExpectedException('Opus_Job_Worker_InvalidJobException');
        $this->worker->work($this->job);
    }   


     public function testInvalidXmlException() {
        $this->filename = 'test_import_schemainvalid.xml';
        $this->loadInputFile();
        $this->job->setLabel('opus-metadata-import-notification');
        $this->job->setData(array('xml' => $this->xml->saveXML()));
	$this->setExpectedException('Opus_Util_MetadataImportInvalidXmlException');
        $this->worker->work($this->job);
    }   


     public function testSkippedDocumentException() {
        $this->filename = 'test_import_invalid_collectionid.xml';
        $this->loadInputFile();    
        $this->job->setLabel('opus-metadata-import-notification');
        $this->job->setData(array('xml' => $this->xml->saveXML()));
	$this->setExpectedException('Opus_Util_MetadataImportSkippedDocumentsException');
        $this->worker->work($this->job);
    }   
    
    
     public function testImportValidXml() {
        $this->filename = 'test_import_minimal.xml';
        $this->loadInputFile();     
        $this->job->setLabel('opus-metadata-import-notification');
        $this->job->setData(array('xml' => $this->xml->saveXML()));
        
	$e = null;
	try {
            $this->worker->work($this->job);
        } catch (Exception $ex) {
            $e = $ex;
        }
        $this->assertNull($e, 'unexpected exception was thrown: ' . get_class($e));	

	$this->documentImported = true;
    }  
    
    
    private function loadInputFile() {
        $this->xml = new DOMDocument();
        $this->xml->load($this->xmlDir .  $this->filename);
    }   
}
