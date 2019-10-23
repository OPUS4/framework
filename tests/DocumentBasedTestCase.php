<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category    Application
 * @author      Thomas Urban <thomas.urban@cepharum.de>
 * @copyright   Copyright (c) 2009-2015, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class DocumentBasedTestCase extends TestCase
{

    private $created = [];

    protected static $documentPropertySets = [
        'article' => [
            'Type' => 'article',
            'Language' => 'deu',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation' => 'Creating, Inc.',
            'ThesisDateAccepted' => '1901-01-01',
            'Edition' => 2,
            'Issue' => 3,
            'Volume' => 1,
            'PageFirst' => 1,
            'PageLast' => 297,
            'PageNumber' => 297,
            'CompletedYear' => 1960,
            'CompletedDate' => '1901-01-01',
            'BelongsToBibliography' => 0,
            'TitleMain' => [
                'Value' => 'Test Main Article',
                'Type' => 'main',
                'Language' => 'deu'
            ]
        ],
        'book' => [
            'Type' => 'book',
            'Language' => 'de',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation' => 'Creating, Inc.',
            'ThesisDateAccepted' => '1999-12-31',
            'Edition' => 2,
            'Issue' => 3,
            'Volume' => 1,
            'PageFirst' => 1,
            'PageLast' => 465,
            'PageNumber' => 465,
            'CompletedYear' => 1996,
            'CompletedDate' => '1996-10-02',
            'BelongsToBibliography' => 1,
            'EmbargoDate' => '2010-01-04',
            'PersonAuthor' => [
                'Opus_Person', [
                    'AcademicTitle' => 'Prof.',
                    'FirstName' => 'Jane',
                    'LastName' => 'Doe',
                ]
            ]
        ],
        'monograph' => [
            'Type' => 'monograph',
            'Language' => 'eng',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation' => 'Creating, Inc.',
            'ThesisDateAccepted' => '1999-12-31',
            'Edition' => 2,
            'Issue' => 1,
            'Volume' => 2,
            'PageFirst' => 1,
            'PageLast' => 465,
            'PageNumber' => 465,
            'CompletedYear' => 1996,
            'CompletedDate' => '1996-10-02',
            'BelongsToBibliography' => 1,
            'EmbargoDate' => '2010-01-04',
            'PersonAuthor' => [
                'Opus_Person', [
                    'FirstName' => 'John',
                    'LastName' => 'Doe',
                ]
            ],
            'TitleMain' => [
                'Value' => 'A Monograph On Indexing',
                'Type' => 'main',
                'Language' => 'eng'
            ]
        ],
        'report' => [
            'Type' => 'report',
            'Language' => 'fra',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation' => 'Creating, Inc.',
            'ThesisDateAccepted' => '1999-12-31',
            'Edition' => 2,
            'Issue' => 1,
            'Volume' => 2,
            'PageFirst' => 1,
            'PageLast' => 465,
            'PageNumber' => 465,
            'CompletedYear' => 1996,
            'CompletedDate' => '1996-10-02',
            'BelongsToBibliography' => 1,
            'EmbargoDate' => '2010-01-04'
        ]
    ];



    /**
     * @return array
     */
    public static function documentPropertiesProvider()
    {
        return self::$documentPropertySets;
    }

    /**
     * @param $name
     * @return mixed
     */
    public static function getDocumentDescriptionByName($name)
    {
        if (! array_key_exists($name, self::$documentPropertySets)) {
            throw new InvalidArgumentException("unknown document description");
        }

        return self::$documentPropertySets[$name];
    }

    /**
     * Creates document in local storage (SQL DB) according to provided
     * description.
     *
     * @param array $documentProperties map of a document's properties into values
     * @return Opus_Document created document
     * @throws Exception
     */
    protected function createDocument($documentProperties = null)
    {
        if (is_null($documentProperties)) {
            $documentProperties = self::$documentPropertySets['article'];
        } if (is_string($documentProperties)) {
            $documentProperties = self::$documentPropertySets[$documentProperties];
        }

        $document = new Opus_Document();


        /*
		 * set all defined internal properties of document
		 */

        foreach ($documentProperties as $property => $value) {
            if (! is_array($value)) {
                // got value of some document-internal field
                $method = 'set' . $property;
                $document->$method( $value );
            }
        }

        $document->store();

        $this->created[] = $document;


        /*
		 * add all defined dependent models of document
		 */

        foreach ($documentProperties as $property => $value) {
            if (is_array($value)) {
                // got some document-external/dependent field
                if (array_key_exists(0, $value) && is_string($value[0]) && is_array($value[1])) {
                    $pre   = new ReflectionClass($value[0]);
                    $value = $value[1];
                } else {
                    $pre = false;
                }

                $copy = array_values($value);
                if (! is_array($copy[0])) {
                    $value = [ $value ];
                }

                $adder = 'add' . $property;

                // add another dependent model for every given description
                foreach ($value as $set) {
                    /** @var Opus_Model_Dependent_Abstract $related */
                    if ($pre) {
                        $related = $pre->newInstance();
                    } else {
                        $related = $document->$adder();
                    }

                    foreach ($set as $name => $value) {
                        $setter = 'set' . $name;
                        $related->$setter( $value );
                    }

                    $related->store();

                    $this->created[] = $related;

                    if ($pre) {
                        $document->$adder( $related );
                    }
                }
            }
        }

        // store document again e.g. for updating related caches
        $document->store();

        return $document;
    }

    public function qualifyTestFilename($filename)
    {
        return APPLICATION_PATH . '/tests/fulltexts/' . basename($filename);
    }

    public function getTestFile($filename)
    {
        return file_get_contents($this->qualifyTestFilename($filename));
    }

    public function addFileToDocument(Opus_Document $document, $filename, $label, $visibleInFrontdoor)
    {
        $file = $document->addFile();
        $file->setTempFile($this->qualifyTestFilename($filename));
        $file->setPathName($filename);
        $file->setLabel($label);
        $file->setVisibleInFrontdoor($visibleInFrontdoor ? '1' : '0');

        $document->store();

        return $document;
    }

    /**
     * Manages to delete all documents created in a test run.
     */
    public function tearDown()
    {
        parent::tearDown();

        $cache = new Opus_Model_Xml_Cache(false);
        $files = APPLICATION_PATH . '/tests/workspace/files/';

        foreach ($this->created as $model) {
            /** @var Opus_Model_AbstractDb $model */
            if ($model instanceof Opus_Document) {
                // drop any model XML cached on document to delete next
                $cache->remove($model->getId());

                // clear all files related to that document
                $docFiles = $files . $model->getId();
                if (is_dir($docFiles) && is_readable($docFiles)) {
                    foreach (glob($docFiles . '/*') as $file) {
                        if (is_readable($file)) {
                            unlink($file);
                        }
                    }
                }
            }

            $model->delete();
        }
    }
}
