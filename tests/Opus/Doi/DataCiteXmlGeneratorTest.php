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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Doi_DataCiteXmlGeneratorTest extends TestCase
{

    protected $src_path = '';
    protected $dest_path = '';
    protected $path = '';

    public function setUp()
    {
        parent::setUp();

        $lang = new Opus_Language();
        $lang->updateFromArray([
            'Comment' => 'Deutsche Sprache',
            'Part2B' => 'ger',
            'Part2T' => 'deu',
            'Part1' => 'de',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'German',
            'Active' => 1
        ]);
        $lang->store();

        $lang = new Opus_Language();
        $lang->updateFromArray([
            'Comment' => 'English language',
            'Part2B' => 'eng',
            'Part2T' => 'eng',
            'Part1' => 'en',
            'Scope' => 'I',
            'Type' => 'L',
            'RefName' => 'English',
            'Active' => 1
        ]);
        $lang->store();

        $config = Zend_Registry::get('Zend_Config');
        $this->path = $config->workspacePath . DIRECTORY_SEPARATOR . uniqid();

        $this->src_path = $this->path . DIRECTORY_SEPARATOR . 'src';
        mkdir($this->src_path, 0777, true);

        $this->dest_path = $this->path . DIRECTORY_SEPARATOR . 'dest' . DIRECTORY_SEPARATOR;
        mkdir($this->dest_path, 0777, true);
        mkdir($this->dest_path . DIRECTORY_SEPARATOR . 'files', 0777, true);

        Zend_Registry::set('Zend_Config', Zend_Registry::get('Zend_Config')->merge(
            new Zend_Config([
                'workspacePath' => $this->dest_path,
                'checksum' => [
                    'maxVerificationSize' => 1,
                ],
            ])
        ));

    }

    public function tearDown()
    {

        Opus_Util_File::deleteDirectory($this->path);

        parent::tearDown();
    }

    public function testGenerateMissingFields()
    {
        $doc = new Opus_Document();
        $doc->store();

        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $this->setExpectedException('Opus_Doi_DataCiteXmlGenerationException');
        $generator->getXml($doc);
    }

    public function testGenerateRequiredFields()
    {
        $doc = new Opus_Document();
        $this->addRequiredPropsToDoc($doc);

        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $result = $generator->getXml($doc);

        $this->assertTrue(is_string($result));
    }

    public function testServerDatePublishedForPublishedYear()
    {
        $doc = new Opus_Document();
        $this->addRequiredPropsToDoc($doc);

        $serverDatePublished = $doc->getServerDatePublished();

        $year = $serverDatePublished->getYear();

        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $result = $generator->getXml($doc);

        $this->assertNotContains("<publicationYear>2008</publicationYear>", $result);
        $this->assertContains("<publicationYear>$year</publicationYear>", $result);
    }

    private function addRequiredPropsToDoc($doc)
    {
        $doi = new Opus_Identifier();
        $doi->setType('doi');
        $doi->setValue('10.2345/opustest-' . $doc->getId());
        $doc->setIdentifier([$doi]);

        $doc->setCompletedYear(2008);
        $doc->setServerState('published');
        $doc->setType('book');
        $doc->setPublisherName('ACME corp');

        $author = new Opus_Person();
        $author->setLastName('Doe');
        $author->setFirstName('John');
        $doc->addPersonAuthor($author);

        $title = new Opus_Title();
        $title->setType('main');
        $title->setValue('Document without meaningful title');
        $title->setLanguage('deu');
        $doc->addTitleMain($title);

        $doc->setLanguage('deu');

        $doc->store();
    }

    public function testGetStylesheetPath()
    {
        $generator = new Opus_Doi_DataCiteXmlGenerator();

        $this->assertEquals(
            APPLICATION_PATH . '/library/Opus/Doi/datacite.xslt',
            $generator->getStylesheetPath()
        );
    }

    public function testGetStylesheetPathConfiguredWithBadPath()
    {
        Zend_Registry::get('Zend_Config')->merge(new Zend_Config([
            'datacite' => ['stylesheetPath' => 'doesnotexist']
        ]));

        $generator = new Opus_Doi_DataCiteXmlGenerator();

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

        Zend_Registry::get('Zend_Config')->merge(new Zend_Config([
            'datacite' => ['stylesheetPath' => $path]
        ]));

        $generator = new Opus_Doi_DataCiteXmlGenerator();

        $stylesheetPath = $generator->getStylesheetPath();

        $this->assertEquals($path, $stylesheetPath);
    }

    public function testXmlValidWithMultipleDDC()
    {
        $document = new Opus_Document();
        $this->addRequiredPropsToDoc($document);

        $role = new Opus_CollectionRole();
        $role->setName('ddc');
        $role->setOaiName('ddc');
        $root = $role->addRootCollection();

        $col1 = new Opus_Collection();
        $col1->setName('Mathematics');
        $root->addLastChild($col1);

        $col2 = new Opus_Collection();
        $col2->setName('Biology');
        $root->addLastChild($col2);

        $role->store();

        $document->addCollection($col1);
        $document->addCollection($col2);

        $document = new Opus_Document($document->store());

        $generator = new Opus_Doi_DataCiteXmlGenerator();

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
        $document = new Opus_Document();
        $this->addRequiredPropsToDoc($document);

        $issn = new Opus_Identifier();
        $issn->setValue('123');

        $document->addIdentifierIssn($issn);

        $issn2 = new Opus_Identifier();
        $issn2->setValue('321');

        $document->addIdentifierIssn($issn2);

        $document = new Opus_Document($document->store());

        $generator = new Opus_Doi_DataCiteXmlGenerator();

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
        $document = new Opus_Document();
        $this->addRequiredPropsToDoc($document);

        $generator = new Opus_Doi_DataCiteXmlGenerator();

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
        $doc = new Opus_Document();
        $this->addRequiredPropsToDoc($doc);

        $file = New Opus_File();
        $file->setVisibleInOai(0);
        $file->setFileSize('0');
        $file->setMimeType('pdf');
        $doc->addFile($file);

        $file = New Opus_File();
        $file->setVisibleInOai(0);
        $file->setFileSize('0');
        $file->setMimeType('pdf');
        $doc->addFile($file);

        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $result = $generator->getXml($doc);

        $this->assertNotContains('<sizes><size>0 KB</size></sizes>', $result);
        $this->assertNotContains('<formats><format>pdf</format></formats>', $result);
    }

    /**
     * Creates a txt-file with random size.
     *
     * @return string path of file
     * @throws Zend_Exception
     */
    private function createTestFile()
    {
        $filename_nonzero = $this->src_path . DIRECTORY_SEPARATOR . 'foobar-nonzero.txt';
        $fh = fopen($filename_nonzero, 'w');

        if ($fh == false) {
            $this->fail("Unable to write file $filename_nonzero.");
        }

        $rand = rand(1000, 100000);
        for ($i = 0; $i < $rand; $i++) {
            fwrite($fh, ".");
        }

        fclose($fh);

        return $filename_nonzero;
    }

    /**
     * The DataCite-XML should not contain files, which are invisible in oai
     * Test if both visible files are shown
     */
    public function testFileInformationVisible()
    {
        $doc = new Opus_Document();
        $this->addRequiredPropsToDoc($doc);

        $filename = $this->createTestFile();

        $file = New Opus_File();
        $file->setVisibleInOai(1);
        $file->setTempFile($filename);
        $file->setPathName('copied-foobar-nonzero.txt');
        $doc->addFile($file);
        $doc->store();

        $filename2 = $this->createTestFile();

        $file2 = New Opus_File();
        $file2->setVisibleInOai(1);
        $file2->setTempFile($filename2);
        $file2->setPathName('copied-foobar-nonzero_2.txt');
        $doc->addFile($file2);
        $doc->store();

        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $result = $generator->getXml($doc);

        $size = intval(round($file->getFileSize() / 1024));
        $size2 = intval(round($file2->getFileSize() / 1024));

        $this->assertContains("<sizes><size>$size KB</size><size>$size2 KB</size></sizes>", $result);
        $this->assertContains('<formats><format>text/plain</format><format>text/plain</format></formats>', $result);
    }

    /**
     * The DataCite-XML should not contain files, which are invisible in oai
     * Test if visible file is shown and invisible file is hided
     */
    public function testMixedFileInformationVisibility()
    {
        $doc = new Opus_Document();
        $this->addRequiredPropsToDoc($doc);

        $filename = $this->createTestFile();

        $file = New Opus_File();
        $file->setVisibleInOai(0);
        $file->setTempFile($filename);
        $file->setPathName('copied-foobar-nonzero.txt');
        $doc->addFile($file);
        $doc->store();

        $filename2 = $this->createTestFile();

        $file2 = New Opus_File();
        $file2->setVisibleInOai(1);
        $file2->setTempFile($filename2);
        $file2->setPathName('copied-foobar-nonzero_2.txt');
        $doc->addFile($file2);
        $doc->store();

        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $result = $generator->getXml($doc);

        $size2 = intval(round($file2->getFileSize() / 1024));

        $this->assertContains("<sizes><size>$size2 KB</size></sizes>", $result);
        $this->assertContains('<formats><format>text/plain</format></formats>', $result);
    }

    /**
     * The DataCite-XML should not contain files, which are invisible in oai
     * Test if if the order of visible and invisible files is not important
     */
    public function testDifferentOrderFileInformationVisibility()
    {
        $doc = new Opus_Document();
        $this->addRequiredPropsToDoc($doc);

        $filename = $this->createTestFile();

        $file = New Opus_File();
        $file->setVisibleInOai(1);
        $file->setTempFile($filename);
        $file->setPathName('copied-foobar-nonzero.txt');
        $doc->addFile($file);
        $doc->store();

        $filename2 = $this->createTestFile();

        $file2 = New Opus_File();
        $file2->setVisibleInOai(0);
        $file2->setTempFile($filename2);
        $file2->setPathName('copied-foobar-nonzero_2.txt');
        $doc->addFile($file2);
        $doc->store();

        $generator = new Opus_Doi_DataCiteXmlGenerator();
        $result = $generator->getXml($doc);

        $size = intval(round($file->getFileSize() / 1024));

        $this->assertContains("<sizes><size>$size KB</size></sizes>", $result);
        $this->assertContains('<formats><format>text/plain</format></formats>', $result);
    }
}
