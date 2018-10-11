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
}
