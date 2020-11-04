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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use Opus\Document;
use Opus\Licence;
use Opus\Model\Xml\Cache;
use OpusTest\TestAsset\TestCase;

/**
 * Test cases for class Opus\Licence.
 *
 * @package Opus
 * @category Tests
 *
 * @group LicenceTest
 */
class LicenceTest extends TestCase
{

    /**
     * Test if a set of licences can be retrieved by getAll().
     *
     * @return void
     */
    public function testRetrieveAllLicences()
    {
        $lics[] = new Licence();
        $lics[] = new Licence();
        $lics[] = new Licence();

        foreach ($lics as $lic) {
            $lic->setNameLong('LongName');
            $lic->setLinkLicence('http://long.org/licence');
            $lic->store();
        }
        $result = Licence::getAll();
        $this->assertEquals(count($lics), count($result), 'Wrong number of objects retrieved.');
    }

    /**
     * Test if the licences display name matches its long name.
     *
     * @return void
     */
    public function testDisplayNameMatchesLongName()
    {
        $lic = new Licence();
        $lic->setNameLong('MyLongName');
        $this->assertEquals($lic->getNameLong(), $lic->getDisplayName(), 'Displayname does not match long name.');
    }

    /**
     * Regression Test for OPUSVIER-1687
     */
    public function testInvalidateDocumentCache()
    {
        $lic = new Licence();
        $lic->setNameLong('MyLongName');
        $lic->setLinkLicence('http://licence.link');

        $doc = new Document();
        $doc->setType("article")
                ->setServerState('published')
                ->setLicence($lic);
        $docId = $doc->store();

        $xmlCache = new Cache();
        $this->assertTrue($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry for document.');
        $lic->setNameLong('EvenLongerName');
        $lic->store();
        $this->assertFalse($xmlCache->hasCacheEntry($docId, 1), 'Expected cache entry removed for document.');
    }


    /**
     * Regression Test for OPUSVIER-3114
     */
    public function testDocumentServerDateModifiedNotUpdatedWithConfiguredFields()
    {
        $fields = ['SortOrder', 'CommentInternal', 'PodAllowed'];

        $licence = new Licence();
        $licenceId = $licence
                ->setNameLong('Test')
                ->setLinkLicence('http://test')
                        ->store();

        $doc = new Document();
        $doc->setType("article")
                ->setServerState('published')
                ->setLicence($licence);
        $docId = $doc->store();

        $serverDateModified = $doc->getServerDateModified();

        sleep(1);

        $licence = new Licence($licenceId);
        foreach ($fields as $fieldName) {
            $oldValue = $licence->{'get' . $fieldName}();
            $licence->{'set' . $fieldName}(1);
            $this->assertNotEquals(
                $licence->{'get' . $fieldName}(),
                $oldValue,
                'Expected different values before and after setting value'
            );
        }

        $licence->store();
        $docReloaded = new Document($docId);

        $this->assertEquals(
            (string)$serverDateModified,
            (string)$docReloaded->getServerDateModified(),
            'Expected no difference in server date modified.'
        );
    }

    public function testStoreName()
    {
        $licence = new Licence();
        $licence->setName('Short name');
        $licence->setNameLong('Long name');
        $licence->setLinkLicence('link');
        $licenceId = $licence->store();

        $licence = new Licence($licenceId);

        $this->assertEquals('Short name', $licence->getName());
        $this->assertEquals('Long name', $licence->getNameLong());
    }

    public function testFetchByName()
    {
        $licence = new Licence();
        $licence->setName('CC BY 4.0');
        $licence->setNameLong('Creative Commons 4.0 - Namensnennung');
        $licence->setLinkLicence('link');
        $licence->store();

        $licence = Licence::fetchByName('CC BY 4.0');

        $this->assertNotNull($licence);
        $this->assertInstanceOf('Opus\Licence', $licence);
    }

    public function testFetchByNameUnknown()
    {
        $licence = new Licence();
        $licence->setName('CC BY 4.0');
        $licence->setNameLong('Creative Commons 4.0 - Namensnennung');
        $licence->setLinkLicence('link');
        $licence->store();

        $licence = Licence::fetchByName('CC BY 3.0');

        $this->assertNull($licence);
    }

    /**
     * @expectedException \Opus\Model\DbConstrainViolationException
     * @expectedExceptionMessage Duplicate entry
     */
    public function testNameUnique()
    {
        $licence = new Licence();
        $licence->setName('CC BY 4.0');
        $licence->setNameLong('Creative Commons 4.0 - Namensnennung');
        $licence->setLinkLicence('link');
        $licence->store();

        $licence = new Licence();
        $licence->setName('CC BY 4.0');
        $licence->setNameLong('Creative Commons 4.0 - Namensnennung 2');
        $licence->setLinkLicence('link 2');
        $licence->store();
    }

    /**
     * Multiple licences can have NULL for column 'name'.
     */
    public function testNameNullNotUnique()
    {
        $licence = new Licence();
        $licence->setNameLong('Creative Commons 4.0 - Namensnennung');
        $licence->setLinkLicence('link');
        $licence->store();

        $licence = new Licence();
        $licence->setNameLong('Creative Commons 4.0 - Namensnennung 2');
        $licence->setLinkLicence('link 2');
        $licence->store();
    }

    public function testToArray()
    {
        $licence = new Licence();

        $licence->setActive(1);
        $licence->setCommentInternal('A comment about this licence.');
        $licence->setDescMarkup('<b>Licence Description Markup</b>');
        $licence->setDescText('Licence Description');
        $licence->setLanguage('eng');
        $licence->setLinkLicence('http://www.example.org/licence');
        $licence->setLinkLogo('http://www.example.org/licence/logo');
        $licence->setLinkSign('http://www.example.org/licence/sign');
        $licence->setMimeType('text/plain');
        $licence->setName('L');
        $licence->setNameLong('Licence');
        $licence->setSortOrder(2);
        $licence->setPodAllowed(1);

        $data = $licence->toArray();

        $this->assertEquals([
            'Active' => 1,
            'CommentInternal' => 'A comment about this licence.',
            'DescMarkup' => '<b>Licence Description Markup</b>',
            'DescText' => 'Licence Description',
            'Language' => 'eng',
            'LinkLicence' => 'http://www.example.org/licence',
            'LinkLogo' => 'http://www.example.org/licence/logo',
            'LinkSign' => 'http://www.example.org/licence/sign',
            'MimeType' => 'text/plain',
            'Name' => 'L',
            'NameLong' => 'Licence',
            'SortOrder' => 2,
            'PodAllowed' => 1
        ], $data);
    }

    public function testFromArray()
    {
        $licence = Licence::fromArray([
            'Active' => 1,
            'CommentInternal' => 'A comment about this licence.',
            'DescMarkup' => '<b>Licence Description Markup</b>',
            'DescText' => 'Licence Description',
            'Language' => 'eng',
            'LinkLicence' => 'http://www.example.org/licence',
            'LinkLogo' => 'http://www.example.org/licence/logo',
            'LinkSign' => 'http://www.example.org/licence/sign',
            'MimeType' => 'text/plain',
            'Name' => 'L',
            'NameLong' => 'Licence',
            'SortOrder' => 2,
            'PodAllowed' => 1
        ]);

        $this->assertEquals(1, $licence->getActive());
        $this->assertEquals('A comment about this licence.', $licence->getCommentInternal());
        $this->assertEquals('<b>Licence Description Markup</b>', $licence->getDescMarkup());
        $this->assertEquals('Licence Description', $licence->getDescText());
        $this->assertEquals('eng', $licence->getLanguage());
        $this->assertEquals('http://www.example.org/licence', $licence->getLinkLicence());
        $this->assertEquals('http://www.example.org/licence/logo', $licence->getLinkLogo());
        $this->assertEquals('http://www.example.org/licence/sign', $licence->getLinkSign());
        $this->assertEquals('text/plain', $licence->getMimeType());
        $this->assertEquals('L', $licence->getName());
        $this->assertEquals('Licence', $licence->getNameLong());
        $this->assertEquals(2, $licence->getSortOrder());
        $this->assertEquals(1, $licence->getPodAllowed());
    }

    public function testUpdateFromArray()
    {
        $licence = new Licence();

        $licence->updateFromArray([
            'Active' => 1,
            'CommentInternal' => 'A comment about this licence.',
            'DescMarkup' => '<b>Licence Description Markup</b>',
            'DescText' => 'Licence Description',
            'Language' => 'eng',
            'LinkLicence' => 'http://www.example.org/licence',
            'LinkLogo' => 'http://www.example.org/licence/logo',
            'LinkSign' => 'http://www.example.org/licence/sign',
            'MimeType' => 'text/plain',
            'Name' => 'L',
            'NameLong' => 'Licence',
            'SortOrder' => 2,
            'PodAllowed' => 1
        ]);

        $this->assertEquals(1, $licence->getActive());
        $this->assertEquals('A comment about this licence.', $licence->getCommentInternal());
        $this->assertEquals('<b>Licence Description Markup</b>', $licence->getDescMarkup());
        $this->assertEquals('Licence Description', $licence->getDescText());
        $this->assertEquals('eng', $licence->getLanguage());
        $this->assertEquals('http://www.example.org/licence', $licence->getLinkLicence());
        $this->assertEquals('http://www.example.org/licence/logo', $licence->getLinkLogo());
        $this->assertEquals('http://www.example.org/licence/sign', $licence->getLinkSign());
        $this->assertEquals('text/plain', $licence->getMimeType());
        $this->assertEquals('L', $licence->getName());
        $this->assertEquals('Licence', $licence->getNameLong());
        $this->assertEquals(2, $licence->getSortOrder());
        $this->assertEquals(1, $licence->getPodAllowed());
    }

    public function testIsUsed()
    {
        $licence = new Licence();
        $licence->updateFromArray([
            'NameLong' => 'Licence',
            'Name' => 'L',
            'LinkLicence' => 'http://www.example.org/licence'
        ]);

        $licence->store();

        $this->assertFalse($licence->isUsed());

        $doc = new Document();
        $doc->addLicence($licence);
        $doc->store();

        $this->assertTrue($licence->isUsed());
    }

    public function testGetDocumentCount()
    {
        $licence = new Licence();
        $licence->updateFromArray([
            'NameLong' => 'Licence',
            'Name' => 'L',
            'LinkLicence' => 'http://www.example.org/licence'
        ]);

        $licence->store();

        $this->assertEquals(0, $licence->getDocumentCount());

        $doc = new Document();
        $doc->addLicence($licence);
        $doc->store();

        $this->assertEquals(1, $licence->getDocumentCount());

        $doc = new Document();
        $doc->addLicence($licence);
        $doc->store();

        $this->assertEquals(2, $licence->getDocumentCount());

        $doc->setLicence(null);
        $doc->store();

        $this->assertEquals(1, $licence->getDocumentCount());
    }
}
