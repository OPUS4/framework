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
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model\Xml;

use Opus\Document;
use Opus\Model\Xml;
use Opus\Model\Xml\Version2;
use Opus\Person;
use Opus\Title;
use Opus\TitleAbstract;
use OpusTest\TestAsset\TestCase;

use function date;

/**
 * Test creation XML (version 2) from models and creation of models by valid XML respectivly.
 *
 * @category    Tests
 * @package     Opus\Model
 * @group XmlVersion2Test
 */
class Version2Test extends TestCase
{
    /**
     * Overwrite parent methods.
     */
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    public function testGetVersion()
    {
        $strategy = new Version2();
        $this->assertEquals('2.0', $strategy->getVersion());
    }

    /**
     * First test of xml version 2.
     */
    public function testInitialXmlVersion2()
    {
        $document = new Document();
        $document->setType("doctoral_thesis");

        $document->setLanguage('deu');

        $document->setPublishedDate(date('Y-m-d'));
        $document->setServerState('unpublished');

        $author = new Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $author->setDateOfBirth('1963-06-12');

        $document->addPersonAuthor($author);
        $document->addPersonAuthor($author);

        $title = new Title();
        $title->setLanguage('deu');
        $title->setValue('Creating of tests.');
        $document->addTitleMain($title);

        $abstract = new Title();
        $abstract->setLanguage('eng');
        $abstract->setValue('this should be a lot of text...');
        $document->addTitleAbstract($abstract);

        $omx = new Xml();
        $omx->setModel($document);
        $omx->setStrategy(new Version2());

        $dom = $omx->getDomDocument();
        // $xmlData = $dom->saveXML();

        // easy tests of structure
        $opusTag = $dom->getElementsByTagName('Opus');
        $this->assertEquals(1, $opusTag->length, 'There should be one OpusTag');
        $opusTag = $opusTag->item(0);
        $this->assertTrue($opusTag->hasAttribute('version'), 'OpusTag should have a version attribute.');
        $this->assertEquals('2.0', $opusTag->getAttribute('version'), 'Returned opus version should be "2.0".');

        // count of Language: 1 of document, 1 of title main and 1 title abstract
        $this->assertEquals(3, $opusTag->getElementsByTagName('Language')->length, 'There should be three language informations.');
        $this->assertEquals(1, $opusTag->getElementsByTagName('TitleMain')->length, 'There should be one title main.');
        $this->assertEquals(1, $opusTag->getElementsByTagName('TitleAbstract')->length, 'There should be one title abstract.');
        $this->assertEquals(2, $opusTag->getElementsByTagName('PersonAuthor')->length, 'There should be two person author.');
        $this->assertEquals(2, $opusTag->getElementsByTagName('LastName')->length, 'There should be two last name.');
        // TODO 2 'type' elements come from child models (TitleMain, TitleAbstract)
        $this->assertEquals(3, $opusTag->getElementsByTagName('Type')->length, 'There should be one document type.');
        $this->assertEquals(1, $opusTag->getElementsByTagName('PublishedDate')->length, 'There should be one published date.');
        $this->assertEquals(1, $opusTag->getElementsByTagName('Opus_Document')->length, 'There should be one opus document tag.');
    }

    /**
     * Test if a given model and its serialized version generate same xml output.
     */
    public function testSettingOfXmlShouldBeEqualToSetModel()
    {
        $document = new Document();

        $document->setType("doctoral_thesis");
        $document->setLanguage('deu');

        $publishedDate = date('Y-m-d');

        $document->setPublishedDate($publishedDate);
        $document->setServerState('unpublished');

        $author = new Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $author->setDateOfBirth('1963-06-12');

        $document->addPersonAuthor($author);

        $title = new Title();
        $title->setLanguage('deu');
        $title->setValue('Creating of tests.');
        $document->addTitleMain($title);

        $abstract = new TitleAbstract();
        $abstract->setLanguage('eng');
        $abstract->setValue('this should be a lot of text...');
        $document->addTitleAbstract($abstract);

        // set up serialize
        $strategy = new Version2();
        $omx      = new Xml();
        $omx->setModel($document);
        $omx->setStrategy($strategy);
        $dom = $omx->getDomDocument();

        // serialize
        $xmlData = $dom->saveXML();
        $omx     = new Xml();
        // take first serialize data as source
        $omx->setXml($xmlData);

        $omx->setStrategy($strategy);
        // build a model from xml
        $model = $omx->getModel();

        $this->assertInstanceOf(Document::class, $model, 'Builded model is not of the expected type.');

        $omx = new Xml();
        $omx->setModel($model);
        $omx->setStrategy($strategy);
        $dom2 = $omx->getDomDocument();

        $xmlData2 = $dom2->saveXML();

        $this->assertEquals(
            $xmlData,
            $xmlData2,
            'Setting a model and setting of a serialized model produced not the same.'
        );
    }

    /**
     * Test if correct Type element gets found to determine the document type.
     *
     * @doesNotPerformAssertions
     */
    public function testConstructionFromCorrectTypeElement()
    {
        $docXml = '<?xml version="1.0"?>
            <Opus version="2.0">
              <Opus_Document>
                  <TitleMain>
                      <Value>testtitel</Value>
                      <Language>ger</Language>
                  </TitleMain>

                  <Subject>
                    <Value>foo</Value>
                    <Language>ger</Language>
                    <Type>uncontrolled</Type>
                  </Subject>

                  <Type>test</Type>
              </Opus_Document>
            </Opus>';

        $document = new Document();
        $document->setType("doctoral_thesis");

        $omx = new Xml();
        // take first serialize data as source
        $omx->setXml($docXml);
        $omx->setStrategy(new Version2());
        // build a model from xml
        $model = $omx->getModel();
    }

    /**
     * Regression test deserializer.
     */
    public function testDeserializingComplexModel()
    {
        $xml = '<?xml version="1.0"?>
            <Opus version="2.0">
              <Opus_Document>
                <TitleMain>
                  <Value>testtitel</Value>
                  <Language>ger</Language>
                </TitleMain>
                <CompletedDate>
                    <Year>2020</Year>
                    <Month>05</Month>
                    <Day>19</Day>
                </CompletedDate>
                <PersonAuthor>
                  <AcademicTitle/>
                  <FirstName>Bob</FirstName>
                  <LastName>Foster</LastName>
                  <DateOfBirth>2000-10-06+02:00</DateOfBirth>
                  <PlaceOfBirth>testort</PlaceOfBirth>
                </PersonAuthor>
                <Subject>
                  <Type>uncontrolled</Type>
                  <Value>schlag, wort, noch, eins, dazu</Value>
                  <Language>ger</Language>
                </Subject>
                <Subject>
                  <Type>uncontrolled</Type>
                  <Value>dfdg dddf gs</Value>
                  <Language>eng</Language>
                </Subject>
                <TitleAbstract>
                  <Value>ein Abstract kommt selten allein</Value>
                  <Language>ger</Language>
                </TitleAbstract>
                <PersonAdvisor>
                  <AcademicTitle>Prof.Dr.</AcademicTitle>
                  <FirstName>Max</FirstName>
                  <LastName>Mustermann</LastName>
                  <DateOfBirth/>
                  <PlaceOfBirth/>
                </PersonAdvisor>
                <PersonReferee>
                  <AcademicTitle/>
                  <FirstName>afd</FirstName>
                  <LastName>asdf</LastName>
                  <DateOfBirth/>
                  <PlaceOfBirth/>
                </PersonReferee>
                <Type>diploma_thesis</Type>
                <Language/>
                <Identifier>
                  <Type>other</Type>
                  <Value>urn:nbn:de:bsz:14-ds-1224410027677-29617</Value>
                </Identifier>
                <File>
                  <PathName>1224410027677-2961.pdf</PathName>
                  <SortOrder/>
                  <Label>Volltextdokument (PDF)</Label>
                  <MimeType/>
                  <MimeType>application/pdf</MimeType>
                  <Language/>
                  <FileSize>26298</FileSize>
                  <HashValue>
                    <Type>md5</Type>
                    <Value>babd8fde730fac363f6e55de289f6090</Value>
                  </HashValue>
                </File>
              </Opus_Document>
            </Opus>
            ';

        $omx = new Xml();
        // take first serialize data as source
        $omx->setXml($xml);
        $omx->setStrategy(new Version2());
        // build a model from xml
        $model = $omx->getModel();

        $data = $model->toArray();

        $this->assertEquals(
            [
                [
                    'Value'    => 'testtitel',
                    'Language' => 'ger',
                    'Type'     => null,
                ],
            ],
            $data['TitleMain']
        );

        $this->assertEquals(
            [
                'Year'          => '2020',
                'Month'         => '05',
                'Day'           => '19',
                'Hour'          => null,
                'Minute'        => null,
                'Second'        => null,
                'Timezone'      => null,
                'UnixTimestamp' => 1589846400,
            ],
            $data['CompletedDate']
        );

        // TODO add more checks
    }

    public function testDateXml()
    {
        $document = Document::new();
        $title    = Title::new();
        $title->setLanguage('eng');
        $title->setValue('Document Title');
        $document->addTitleMain($title);
        $document->setCompletedDate('2022-05-19');
        $document->setBelongsToBibliography(true);
        $docId = Document::get($document->store());

        $xml = new Xml();
        $xml->setStrategy(new Version2());
        $xml->setModel($document);
        $dom = $xml->getDomDocument();

        $output = $dom->saveXml();

        $elements = $dom->getElementsByTagName('CompletedDate');
        $this->assertCount(1, $elements);

        $xpath = $this->prepareXpathFromResultString($output);
        $this->assertEquals('2022', $xpath->query('//CompletedDate/Year')->item(0)->textContent);
        $this->assertEquals('05', $xpath->query('//CompletedDate/Month')->item(0)->textContent);
        $this->assertEquals('19', $xpath->query('//CompletedDate/Day')->item(0)->textContent);
        $this->assertEquals('', $xpath->query('//CompletedDate/Hour')->item(0)->textContent);
        $this->assertEquals('1', $xpath->query('//BelongsToBibliography')->item(0)->textContent);
    }
}
