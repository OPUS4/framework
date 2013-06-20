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
 * details. You should        }
 have received a copy of the GNU General Public License
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


class Opus_Util_MetadataImportTest extends PHPUnit_Framework_TestCase {


    private $xml;


    private static $xmlDir;


    public static function setUpBeforeClass() {
        parent::setUpBeforeClass();
        self::$xmlDir = dirname(dirname(dirname(__FILE__))) . '/import/';
    }


    public function testInvalidXmlException() {
        $filename = 'test_import_schemainvalid.xml';
        $this->loadInputFile($filename);
        $importer = new Opus_Util_MetadataImport($this->xml);

        $this->setExpectedException('Opus_Util_MetadataImportInvalidXmlException');
        $importer->run();
    }
    

    public function testNoMetadataImportException() {
        $filename = 'test_import_minimal.xml';
        $this->loadInputFile($filename);
        $importer = new Opus_Util_MetadataImport($this->xml);

        $e = null;
        try {
          $importer->run();
        }
        catch (Opus_Util_MetadataImportInvalidXmlException $ex) {
          $e = $ex;
        }
        catch (Opus_Util_MetadataImportSkippedDocumentsException $ex) {
           $e = $ex;
        }
        $this->assertNull($e, 'unexpected exception was thrown: ' . get_class($e));

    }


    public function testSkippedDocumentsException() {
        $filename = 'test_import_invalid_collectionid.xml';
        $this->loadInputFile($filename);
        $importer = new Opus_Util_MetadataImport($this->xml);

        $this->setExpectedException('Opus_Util_MetadataImportSkippedDocumentsException');
        $importer->run();
    }


    private function loadInputFile($filename) {
        $this->xml = new DOMDocument();
        $this->xml->load(self::$xmlDir . $filename);

    }

}

