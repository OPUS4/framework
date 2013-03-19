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
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Licence.
 *
 * @package Opus
 * @category Tests
 *
 * @group LicenceTest
 */
class Opus_LicenceTest extends TestCase {

    /**
     * Test if a set of licences can be retrieved by getAll().
     *
     * @return void
     */
    public function testRetrieveAllLicences() {
        $lics[] = new Opus_Licence();
        $lics[] = new Opus_Licence();
        $lics[] = new Opus_Licence();
        
        foreach ($lics as $lic) {
            $lic->setNameLong('LongName');
            $lic->setLinkLicence('http://long.org/licence');
            $lic->store();
        }
        $result = Opus_Licence::getAll();
        $this->assertEquals(count($lics), count($result), 'Wrong number of objects retrieved.');
    }

    /**
     * Test if the licences display name matches its long name.
     *
     * @return void
     */
    public function testDisplayNameMatchesLongName() {
        $lic = new Opus_Licence();
        $lic->setNameLong('MyLongName');
        $this->assertEquals($lic->getNameLong(), $lic->getDisplayName(), 'Displayname does not match long name.');
    }
    
    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache() {

        $lic = new Opus_Licence();
        $lic->setNameLong('MyLongName');
        $lic->setLinkLicence('http://licence.link');

        $doc = new Opus_Document();
        $doc->setType("article")
                ->setServerState('published')
                ->setLicence($lic);
        $docId = $doc->store();

        $xmlCache = new Opus_Model_Xml_Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $lic->setNameLong('EvenLongerName');
        $lic->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }


}
