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
 */

namespace OpusTest\Doi;

use Opus\Collection;
use Opus\CollectionRole;
use Opus\Common\Config;
use Opus\Common\Identifier;
use Opus\Common\Language;
use Opus\Common\Model\ModelException;
use Opus\Common\Util\File as FileUtil;
use Opus\DnbInstitute;
use Opus\Document;
use Opus\Doi\DataCiteXmlGenerationException;
use Opus\Doi\DataCiteXmlGenerator;
use Opus\File;
use Opus\Person;
use Opus\Title;
use OpusTest\TestAsset\TestCase;
use Zend_Config;

use function fclose;
use function fopen;
use function fwrite;
use function intval;
use function is_array;
use function is_bool;
use function is_string;
use function mkdir;
use function rand;
use function round;
use function stream_get_meta_data;
use function tmpfile;
use function uniqid;

use const DIRECTORY_SEPARATOR;

class DataCiteXmlGeneratorTest extends TestCase
{
    /** @var string */
    protected $srcPath = '';

    /** @var string */
    protected $destPath = '';

    /** @var string */
    protected $path = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->clearTables(false);

        $lang = Language::new();
        $lang->updateFromArray([
            'Comment' => 'Deutsche Sprache',
            'Part2B'  => 'ger',
            'Part2T'  => 'deu',
            'Part1'   => 'de',
            'Scope'   => 'I',
            'Type'    => 'L',
            'RefName' => 'German',
            'Active'  => 1,
        ]);
        $lang->store();

        $lang = Language::new();
        $lang->updateFromArray([
            'Comment' => 'English language',
            'Part2B'  => 'eng',
            'Part2T'  => 'eng',
            'Part1'   => 'en',
            'Scope'   => 'I',
            'Type'    => 'L',
            'RefName' => 'English',
            'Active'  => 1,
        ]);
        $lang->store();

        $config     = Config::get();
        $this->path = $config->workspacePath . DIRECTORY_SEPARATOR . uniqid();

        $this->srcPath = $this->path . DIRECTORY_SEPARATOR . 'src';
        mkdir($this->srcPath, 0777, true);

        $this->destPath = $this->path . DIRECTORY_SEPARATOR . 'dest' . DIRECTORY_SEPARATOR;
        mkdir($this->destPath, 0777, true);
        mkdir($this->destPath . DIRECTORY_SEPARATOR . 'files', 0777, true);

        Config::get()->merge(new Zend_Config([
            'workspacePath' => $this->destPath,
            'checksum'      => [
                'maxVerificationSize' => 1,
            ],
            'doi'           => [
                'prefix'      => '10.2345',
                'localPrefix' => 'opustest',
            ],
        ]));
    }

    public function tearDown(): void
    {
        FileUtil::deleteDirectory($this->path);

        parent::tearDown();
    }

    public function testGenerateMissingFields()
    {
        $doc = new Document();
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $this->expectException(DataCiteXmlGenerationException::class);
        $generator->getXml($doc);
    }

    public function testGenerateWithNonLocalDoi()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        // lokale DOI verändern, so dass sie nicht mehr lokal ist
        $doi = $doc->getIdentifierDoi()[0];
        $doi->setValue('10.2345/nonlocal-' . $docId);
        $doi->store();

        $generator = new DataCiteXmlGenerator();
        $this->expectException(DataCiteXmlGenerationException::class);
        $generator->getXml($doc);

        // DOI wieder lokal machen
        $doi->setValue('10.2345/opustest-' . $docId);
        $doi->store();

        $generator = new DataCiteXmlGenerator();
        $result    = $generator->getXml($doc);
        $this->assertTrue(is_string($result) && $result !== '');

        $result = $generator->getXml($doc, true, true);
        $this->assertTrue(is_string($result) && $result !== '');
    }

    public function testGenerateInvalidXml()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        // DOI löschen, so dass das erzeugte DataCite-XML nicht mehr valide ist
        $doc->setIdentifier([]);
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $result    = $generator->getXml($doc, true, true);
        $this->assertTrue(is_string($result) && $result !== '');

        $result = $generator->getXml($doc, true, false);
        $this->assertTrue(is_string($result) && $result !== '');
    }

    public function testCheckRequiredFieldsLazyPositive()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, true);

        $this->assertTrue(is_bool($result));
        $this->assertTrue($result);
    }

    public function testCheckRequiredFieldsLazyMissingCreator()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        // Autorfeld löschen
        $doc->setPerson([]);
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, true);

        $this->assertIsBool($result);
        $this->assertFalse($result);
    }

    public function testCheckRequiredFieldsNonLazyPositive()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => true,
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => true,
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsNonLazyMissingFields()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        // Autorfeld löschen
        $doc->setPerson([]);
        // DOI löschen
        $doc->setIdentifier([]);
        // TitleMainLöschen
        $doc->setTitleMain([]);
        // Publisher löschen
        $doc->setPublisherName('');
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => 'local_DOI_missing',
            'creators'        => 'creator_missing',
            'titles'          => 'title_missing',
            'publisher'       => 'publisher_missing',
            'publicationYear' => true,
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsNonLazyMissingCreator()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        // Autorfeld löschen
        $doc->setPerson([]);
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => 'creator_missing',
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => true,
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsNonLazyTooManyLocalDOIs()
    {
        $docId = $this->createDocWithRequiredFields();
        // setze zwei lokale DOIs anstatt einer
        $doc  = new Document($docId);
        $dois = $doc->getIdentifier();
        $doi  = Identifier::new();
        $doi->setType('doi');
        $doi->setValue($dois[0]->getValue() . 'x');
        $dois[] = $doi;
        $doc->setIdentifier($dois);
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => 'multiple_local_DOIs',
            'creators'        => true,
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => true,
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsNonLazyTooManyPublishers()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        // entferne PublisherName und setze anschließend zwei ThesisPublisher
        $doc->setPublisherName(null);
        $this->addThesisPublisherHelper($doc);
        $this->addThesisPublisherHelper($doc);
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => true,
            'titles'          => true,
            'publisher'       => 'multiple_publishers',
            'publicationYear' => true,
            'resourceType'    => true,
        ], $result);

        // einen ThesisPublisher wieder entfernen -> sollte wieder gültig sein
        $doc->setThesisPublisher(null);
        $this->addThesisPublisherHelper($doc);
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);
        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => true,
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => true,
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsUnpublishedDoc()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        $doc->setServerState('unpublished');
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => true,
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => true,
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsMissingServerDatePublishedUnpublishedDoc()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        $doc->setServerState('unpublished');
        $doc->setServerDatePublished(null);
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => true,
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => 'publication_date_missing_non_published',
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsMissingServerDatePublishedInPublishedDoc()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        $doc->setServerState('published');
        $doc->setServerDatePublished(null);
        // kein $doc->store() aufrufen, weil sonst serverDatePublished auf das aktuelle Datum gesetzt wird

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => true,
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => 'publication_date_missing',
            'resourceType'    => true,
        ], $result);
    }

    public function testCheckRequiredFieldsInvalidServerDatePublishedInPublishedDoc()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        $doc->setServerState('published');
        $doc->setServerDatePublished('');
        $doc->store();

        $generator = new DataCiteXmlGenerator(false);
        $result    = $generator->checkRequiredFields($doc, false);

        $this->assertTrue(is_array($result));
        $this->assertEquals([
            'identifier'      => true,
            'creators'        => true,
            'titles'          => true,
            'publisher'       => true,
            'publicationYear' => 'publication_year_missing',
            'resourceType'    => true,
        ], $result);
    }

    /**
     * Hilfsfunktion zum Setzen eines ThesisPublisher im übergebenen Dokument.
     *
     * @param Document $doc Dokument, zu dem ThesisPublisher hinzugefügt werden soll.
     */
    private function addThesisPublisherHelper($doc)
    {
        $thesisPublisher = DnbInstitute::new();
        $thesisPublisher->setName('ThesisPublisher');
        $thesisPublisher->setCity('Berlin');
        $thesisPublisher->setIsPublisher(true);
        $doc->addThesisPublisher($thesisPublisher);
    }

    public function testGenerateRequiredFields()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $generator = new DataCiteXmlGenerator();
        $result    = $generator->getXml($doc);

        $this->assertTrue(is_string($result));
    }

    public function testServerDatePublishedForPublishedYear()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $serverDatePublished = $doc->getServerDatePublished();

        $year = $serverDatePublished->getYear();

        $generator = new DataCiteXmlGenerator();
        $result    = $generator->getXml($doc);

        $this->assertStringNotContainsString("<publicationYear>2008</publicationYear>", $result);
        $this->assertStringContainsString("<publicationYear>$year</publicationYear>", $result);
    }

    /**
     * @return int
     * @throws ModelException
     */
    private function createDocWithRequiredFields()
    {
        $doc   = new Document();
        $docId = $doc->store();

        $doi = Identifier::new();
        $doi->setType('doi');
        $doi->setValue('10.2345/opustest-' . $docId);
        $doc->setIdentifier([$doi]);

        $doc->setCompletedYear(2008);
        $doc->setServerState('published');
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

        $doc->setLanguage('deu');

        $doc->store();

        return $docId;
    }

    public function testGetStylesheetPath()
    {
        $generator = new DataCiteXmlGenerator();

        $this->assertEquals(
            APPLICATION_PATH . '/library/Opus/Doi/datacite.xslt',
            $generator->getStylesheetPath()
        );
    }

    public function testGetStylesheetPathConfiguredWithBadPath()
    {
        Config::get()->merge(new Zend_Config([
            'datacite' => ['stylesheetPath' => 'doesnotexist'],
        ]));

        $generator = new DataCiteXmlGenerator();

        $this->assertEquals(
            APPLICATION_PATH . '/library/Opus/Doi/datacite.xslt',
            $generator->getStylesheetPath()
        );
    }

    public function testGetStylesheetPathConfigured()
    {
        $temp = tmpfile();

        fwrite($temp, 'OPUS 4 Framework testdata');

        $path = stream_get_meta_data($temp)['uri'];

        Config::get()->merge(new Zend_Config([
            'datacite' => ['stylesheetPath' => $path],
        ]));

        $generator = new DataCiteXmlGenerator();

        $stylesheetPath = $generator->getStylesheetPath();

        $this->assertEquals($path, $stylesheetPath);
    }

    public function testXmlValidWithMultipleDDC()
    {
        $docId    = $this->createDocWithRequiredFields();
        $document = new Document($docId);

        $role = new CollectionRole();
        $role->setName('ddc');
        $role->setOaiName('ddc');
        $root = $role->addRootCollection();

        $col1 = new Collection();
        $col1->setName('Mathematics');
        $root->addLastChild($col1);

        $col2 = new Collection();
        $col2->setName('Biology');
        $root->addLastChild($col2);

        $role->store();

        $document->addCollection($col1);
        $document->addCollection($col2);

        $document = new Document($document->store());

        $generator = new DataCiteXmlGenerator();

        $output = $generator->getXml($document);

        $this->assertNotEmpty($output);

        $xpath = $this->prepareXpathFromResultString($output);

        $result = $xpath->query('//ns:subjects');
        $this->assertEquals(1, $result->length);

        $result = $xpath->query('//ns:subject');
        $this->assertEquals(2, $result->length);

        $result = $xpath->query('//ns:subject[text()="Mathematics"]');
        $this->assertEquals(1, $result->length);

        $result = $xpath->query('//ns:subject[text()="Biology"]');
        $this->assertEquals(1, $result->length);
    }

    public function testXmlValidWithMultipleIssn()
    {
        $docId    = $this->createDocWithRequiredFields();
        $document = new Document($docId);

        $issn = Identifier::new();
        $issn->setValue('123');

        $document->addIdentifierIssn($issn);

        $issn2 = Identifier::new();
        $issn2->setValue('321');

        $document->addIdentifierIssn($issn2);

        $document = new Document($document->store());

        $generator = new DataCiteXmlGenerator();

        $output = $generator->getXml($document);

        $this->assertNotEmpty($output);

        $xpath = $this->prepareXpathFromResultString($output);

        $result = $xpath->query('//ns:relatedIdentifiers');
        $this->assertEquals(1, $result->length);

        $result = $xpath->query('//ns:relatedIdentifier');
        $this->assertEquals(2, $result->length);

        $result = $xpath->query('//ns:relatedIdentifier[text()="123"]');
        $this->assertEquals(1, $result->length);

        $result = $xpath->query('//ns:relatedIdentifier[text()="321"]');
        $this->assertEquals(1, $result->length);
    }

    public function testLanguageElement()
    {
        $docId    = $this->createDocWithRequiredFields();
        $document = new Document($docId);

        $generator = new DataCiteXmlGenerator();

        $output = $generator->getXml($document);

        $xpath = $this->prepareXpathFromResultString($output);

        $result = $xpath->query('//ns:language[text()="de"]');
        $this->assertEquals(1, $result->length);
    }

    /**
     * The DataCite-XML should not contain files, which are invisible in oai
     * Test if both invisible files are hided
     */
    public function testFileInformationInvisible()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $file = new File();
        $file->setVisibleInOai(0);
        $file->setFileSize('0');
        $file->setMimeType('pdf');
        $doc->addFile($file);

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath        = $this->prepareXpathFromResultString($xml);
        $sizesXpath   = $xpath->query('//ns:size');
        $formatsXpath = $xpath->query('//ns:format');

        $this->assertEquals(0, $sizesXpath->length);
        $this->assertEquals(0, $formatsXpath->length);
    }

    /**
     * Creates a txt-file with random size.
     *
     * @throws\Zend_Exception
     *
     * @return string path of file
     */
    private function createTestFile()
    {
        $filenameNonZero = $this->srcPath . DIRECTORY_SEPARATOR . 'foobar-nonzero.txt';
        $fh              = fopen($filenameNonZero, 'w');

        if ($fh === false) {
            $this->fail("Unable to write file $filenameNonZero.");
        }

        $rand = rand(1000, 100000);
        for ($i = 0; $i < $rand; $i++) {
            fwrite($fh, ".");
        }

        fclose($fh);

        return $filenameNonZero;
    }

    /**
     * The DataCite-XML should not contain files, which are invisible in oai
     * Test if both visible files are shown
     */
    public function testFileInformationVisible()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $filename = $this->createTestFile();

        $file = new File();
        $file->setVisibleInOai(1);
        $file->setTempFile($filename);
        $file->setPathName('copied-foobar-nonzero.txt');
        $doc->addFile($file);
        $doc->store();

        $filename2 = $this->createTestFile();

        $file2 = new File();
        $file2->setVisibleInOai(1);
        $file2->setTempFile($filename2);
        $file2->setPathName('copied-foobar-nonzero_2.txt');
        $doc->addFile($file2);
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);

        $size  = intval(round($file->getFileSize() / 1024));
        $size2 = intval(round($file2->getFileSize() / 1024));

        $mimeType1 = $file->getMimeType();
        $mimeType2 = $file2->getMimeType();

        $sizesXpath1  = $xpath->query("//ns:size[1][text()=\"$size KB\"]");
        $sizesXpath2  = $xpath->query("//ns:size[2][text()=\"$size2 KB\"]");
        $formatXpath1 = $xpath->query("//ns:format[1][text()=\"$mimeType1\"]");
        $formatXpath2 = $xpath->query("//ns:format[2][text()=\"$mimeType2\"]");

        $this->assertEquals(1, $sizesXpath1->length);
        $this->assertEquals(1, $sizesXpath2->length);
        $this->assertEquals(1, $formatXpath1->length);
        $this->assertEquals(1, $formatXpath2->length);
    }

    /**
     * The DataCite-XML should not contain files, which are invisible in oai
     * Test if visible file is shown and invisible file is hided
     */
    public function testMixedFileInformationVisibility()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $filename = $this->createTestFile();

        $file = new File();
        $file->setVisibleInOai(1);
        $file->setTempFile($filename);
        $file->setPathName('copied-foobar-nonzero.txt');
        $doc->addFile($file);
        $doc->store();

        $filename2 = $this->createTestFile();

        $file2 = new File();
        $file2->setVisibleInOai(0);
        $file2->setTempFile($filename2);
        $file2->setPathName('copied-foobar-nonzero_2.txt');
        $doc->addFile($file2);
        $doc->store();

        $mimeType1 = $file->getMimeType();
        $mimeType2 = $file2->getMimeType();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);

        $size  = intval(round($file->getFileSize() / 1024));
        $size2 = intval(round($file2->getFileSize() / 1024));

        $sizesXpath1  = $xpath->query("//ns:size[1][text()=\"$size KB\"]");
        $sizesXpath2  = $xpath->query("//ns:size[2][text()=\"$size2 KB\"]");
        $formatXpath1 = $xpath->query("//ns:format[1][text()=\"$mimeType1\"]");
        $formatXpath2 = $xpath->query("//ns:format[2][text()=\"$mimeType2\"]");

        $this->assertEquals(1, $sizesXpath1->length);
        $this->assertEquals(0, $sizesXpath2->length);
        $this->assertEquals(1, $formatXpath1->length);
        $this->assertEquals(0, $formatXpath2->length);
    }

    /**
     * The DataCite-XML should not contain files, which are invisible in oai
     * Test if if the order of visible and invisible files is not important
     */
    public function testDifferentOrderFileInformationVisibility()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);

        $filename = $this->createTestFile();

        $file = new File();
        $file->setVisibleInOai(0);
        $file->setTempFile($filename);
        $file->setPathName('copied-foobar-nonzero.txt');
        $doc->addFile($file);
        $doc->store();

        $filename2 = $this->createTestFile();

        $file2 = new File();
        $file2->setVisibleInOai(1);
        $file2->setTempFile($filename2);
        $file2->setPathName('copied-foobar-nonzero_2.txt');
        $doc->addFile($file2);
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);

        $size  = intval(round($file->getFileSize() / 1024));
        $size2 = intval(round($file2->getFileSize() / 1024));

        $mimeType1 = $file->getMimeType();
        $mimeType2 = $file2->getMimeType();

        $sizesXpath1  = $xpath->query("//ns:size[2][text()=\"$size KB\"]");
        $sizesXpath2  = $xpath->query("//ns:size[1][text()=\"$size2 KB\"]");
        $formatXpath1 = $xpath->query("//ns:format[2][text()=\"$mimeType1\"]");
        $formatXpath2 = $xpath->query("//ns:format[1][text()=\"$mimeType2\"]");

        $this->assertEquals(1, $sizesXpath2->length);
        $this->assertEquals(0, $sizesXpath1->length);
        $this->assertEquals(1, $formatXpath2->length);
        $this->assertEquals(0, $formatXpath1->length);
    }

    public function testXmlWithCreatorPlaceholder()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        $doc->setPerson(null);
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);
        $node  = $xpath->query('/ns:resource/ns:creators/ns:creator/ns:creatorName');
        $this->assertEquals('(:unav)', $node->item(0)->textContent);
    }

    public function testXmlWithTitlePlaceholder()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        $doc->setTitleMain(null);
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);
        $node  = $xpath->query('/ns:resource/ns:titles/ns:title');
        $this->assertEquals('(:unas)', $node->item(0)->textContent);
    }

    public function testXmlWithPublisherPlaceholder()
    {
        $docId = $this->createDocWithRequiredFields();
        $doc   = new Document($docId);
        $doc->setPublisherName(null);
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);
        $node  = $xpath->query('/ns:resource/ns:publisher');
        $this->assertEquals('(:unav)', $node->item(0)->textContent);
    }

    public function testProperGenerationOfOrcidUrl()
    {
        $docId   = $this->createDocWithRequiredFields();
        $doc     = new Document($docId);
        $authors = $doc->getPersonAuthor();
        $author  = $authors[0];
        $author->setIdentifierOrcid('0000-2222-4444-6666');

        $editor = new Person();
        $editor->setFirstName('John');
        $editor->setLastName('Doe');
        $editor->setIdentifierOrcid('0000-1111-3333-5555');
        $doc->setPersonEditor($editor);

        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);
        $node  = $xpath->query('/ns:resource/ns:creators/ns:creator/ns:nameIdentifier');
        $this->assertEquals('0000-2222-4444-6666', $node->item(0)->textContent);

        $node = $xpath->query('/ns:resource/ns:contributors/ns:contributor/ns:nameIdentifier');
        $this->assertEquals('0000-1111-3333-5555', $node->item(0)->textContent);

        // use editor instead of author
        $doc->setPersonAuthor(null);
        $doc->store();

        $generator = new DataCiteXmlGenerator();
        $xml       = $generator->getXml($doc);

        $xpath = $this->prepareXpathFromResultString($xml);
        $node  = $xpath->query('/ns:resource/ns:creators/ns:creator/ns:nameIdentifier');
        $this->assertEquals('0000-1111-3333-5555', $node->item(0)->textContent);
    }
}
