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
 * @package     Opus_Model
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2009-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test creation XML (version 2) from models and creation of models by valid XML respectivly.
 *
 * @category    Tests
 * @package     Opus_Model
 *
 * @group XmlVersion2Test
 */
class Opus_Model_Xml_Version2Test extends TestCase {

    /**
     * Overwrite parent methods.
     */
    public function setUp() {}
    public function tearDown() {}

    /**
     * First test of xml version 2.
     *
     * @return void
     */
    public function testInitialXmlVersion2() {
        $document = new Opus_Document();
        $document->setType("doctoral_thesis");

        $document->setLanguage('deu');

        $document->setPublishedDate(date('Y-m-d'));
        $document->setServerState('unpublished');

        $author = new Opus_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $author->setDateOfBirth('1963-06-12');

        $document->addPersonAuthor($author);
        $document->addPersonAuthor($author);

        $title = new Opus_Title();
        $title->setLanguage('deu');
        $title->setValue('Creating of tests.');
        $document->addTitleMain($title);

        $abstract = new Opus_Title();
        $abstract->setLanguage('eng');
        $abstract->setValue('this should be a lot of text...');
        $document->addTitleAbstract($abstract);

        $omx = new Opus_Model_Xml();
        $omx->setModel($document);
        $omx->setStrategy(new Opus_Model_Xml_Version2);

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
     *
     * @return void
     */
    public function testSettingOfXmlShouldBeEqualToSetModel() {
        $document = new Opus_Document();
        $document->setType("doctoral_thesis");

        $document->setLanguage('deu');

        $document->setPublishedDate(date('Y-m-d'));
        $document->setServerState('unpublished');

        $author = new Opus_Person();
        $author->setFirstName('Ludwig');
        $author->setLastName('Wittgenstein');
        $author->setDateOfBirth('1963-06-12');

        $document->addPersonAuthor($author);
        $document->addPersonAuthor($author);

        $title = new Opus_Title();
        $title->setLanguage('deu');
        $title->setValue('Creating of tests.');
        $document->addTitleMain($title);

        $abstract = new Opus_Title();
        $abstract->setLanguage('eng');
        $abstract->setValue('this should be a lot of text...');
        $document->addTitleAbstract($abstract);

        // set up serialize
        $strategy = new Opus_Model_Xml_Version2;
        $omx = new Opus_Model_Xml();
        $omx->setModel($document);
        $omx->setStrategy($strategy);
        $dom = $omx->getDomDocument();
        
        // serialize
        $xmlData = $dom->saveXML();
        $omx = new Opus_Model_Xml();
        // take first serialize data as source
        $omx->setXml($xmlData);
        
        $omx->setStrategy($strategy);
        // build a model from xml
        $model = $omx->getModel();

        $this->assertType('Opus_Document', $model, 'Builded model is not of the expected type.');

        $omx = new Opus_Model_Xml;
        $omx->setModel($model);
        $omx->setStrategy($strategy);
        $dom2 = $omx->getDomDocument();
        $this->assertEquals($xmlData, $dom2->saveXML(), 'Setting a model and setting of a serialized model produced not the same.');
    }
    
    
    /**
     * Test if correct Type element gets found to determine the document type.
     *
     * @return void
     */
    public function testConstructionFromCorrectTypeElement() {
        $docXml = '<?xml version="1.0"?>
            <Opus version="2.0">
              <Opus_Document>
                  <TitleMain>
                      <Value>testtitel</Value>
                      <Language>ger</Language>
                  </TitleMain>

                  <SubjectUncontrolled>
                    <Value>foo</Value>
                    <Language>ger</Language>
                    <Type>uncontrolled</Type>
                  </SubjectUncontrolled>

                  <Type>test</Type>
              </Opus_Document>
            </Opus>';
            
        $document = new Opus_Document();
        $document->setType("doctoral_thesis");

        $omx = new Opus_Model_Xml();
        // take first serialize data as source
        $omx->setXml($docXml);
        $omx->setStrategy(new Opus_Model_Xml_Version2);
        // build a model from xml
        $model = $omx->getModel();
    }    
    
    /**
     * Regression test deserializer.
     *
     * @return void
     */
    public function testDeserializingComplexModel() {
        $this->markTestIncomplete();
    
        $xml = '<?xml version="1.0"?>
            <Opus version="2.0">
              <Opus_Document>
                <TitleMain>
                  <Value>testtitel</Value>
                  <Language>ger</Language>
                </TitleMain>
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
                <DateAccepted>2008-10-07+02:00</DateAccepted>
                <Type>diploma_thesis</Type>
                <Language/>
                <Identifier>
                  <Type>other</Type>
                  <Value>urn:nbn:de:bsz:14-ds-1224410027677-29617</Value>
                </Identifier>
                <VgWortOpenKey/>
                <File>
                  <PathName>1224410027677-2961.pdf</PathName>
                  <SortOrder/>
                  <Label>Volltextdokument (PDF)</Label>
                  <FileType/>
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
            
        $omx = new Opus_Model_Xml();
        // take first serialize data as source
        $omx->setXml($xml);
        $omx->setStrategy(new Opus_Model_Xml_Version2);
        // build a model from xml
        $model = $omx->getModel();
    }
    
}

