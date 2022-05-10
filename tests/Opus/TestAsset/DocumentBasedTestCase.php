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
 * @copyright   Copyright (c) 2009-2015, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Application
 * @author      Thomas Urban <thomas.urban@cepharum.de>
 */

namespace OpusTest\TestAsset;

use Exception;
use InvalidArgumentException;
use Opus\Common\Config;
use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\Model\AbstractDb;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Xml\Cache;
use Opus\Person;
use ReflectionClass;

use function array_key_exists;
use function array_values;
use function basename;
use function file_get_contents;
use function glob;
use function is_array;
use function is_dir;
use function is_readable;
use function is_string;
use function unlink;

class DocumentBasedTestCase extends TestCase
{
    private $created = [];

    protected static $documentPropertySets = [
        'article'   => [
            'Type'                    => 'article',
            'Language'                => 'deu',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation'     => 'Creating, Inc.',
            'ThesisDateAccepted'      => '1901-01-01',
            'Edition'                 => 2,
            'Issue'                   => 3,
            'Volume'                  => 1,
            'PageFirst'               => 1,
            'PageLast'                => 297,
            'PageNumber'              => 297,
            'CompletedYear'           => 1960,
            'CompletedDate'           => '1901-01-01',
            'BelongsToBibliography'   => 0,
            'TitleMain'               => [
                'Value'    => 'Test Main Article',
                'Type'     => 'main',
                'Language' => 'deu',
            ],
        ],
        'book'      => [
            'Type'                    => 'book',
            'Language'                => 'de',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation'     => 'Creating, Inc.',
            'ThesisDateAccepted'      => '1999-12-31',
            'Edition'                 => 2,
            'Issue'                   => 3,
            'Volume'                  => 1,
            'PageFirst'               => 1,
            'PageLast'                => 465,
            'PageNumber'              => 465,
            'CompletedYear'           => 1996,
            'CompletedDate'           => '1996-10-02',
            'BelongsToBibliography'   => 1,
            'EmbargoDate'             => '2010-01-04',
            'PersonAuthor'            => [
                Person::class,
                [
                    'AcademicTitle' => 'Prof.',
                    'FirstName'     => 'Jane',
                    'LastName'      => 'Doe',
                ],
            ],
        ],
        'monograph' => [
            'Type'                    => 'monograph',
            'Language'                => 'eng',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation'     => 'Creating, Inc.',
            'ThesisDateAccepted'      => '1999-12-31',
            'Edition'                 => 2,
            'Issue'                   => 1,
            'Volume'                  => 2,
            'PageFirst'               => 1,
            'PageLast'                => 465,
            'PageNumber'              => 465,
            'CompletedYear'           => 1996,
            'CompletedDate'           => '1996-10-02',
            'BelongsToBibliography'   => 1,
            'EmbargoDate'             => '2010-01-04',
            'PersonAuthor'            => [
                Person::class,
                [
                    'FirstName' => 'John',
                    'LastName'  => 'Doe',
                ],
            ],
            'TitleMain'               => [
                'Value'    => 'A Monograph On Indexing',
                'Type'     => 'main',
                'Language' => 'eng',
            ],
        ],
        'report'    => [
            'Type'                    => 'report',
            'Language'                => 'fra',
            'ContributingCorporation' => 'Contributing, Inc.',
            'CreatingCorporation'     => 'Creating, Inc.',
            'ThesisDateAccepted'      => '1999-12-31',
            'Edition'                 => 2,
            'Issue'                   => 1,
            'Volume'                  => 2,
            'PageFirst'               => 1,
            'PageLast'                => 465,
            'PageNumber'              => 465,
            'CompletedYear'           => 1996,
            'CompletedDate'           => '1996-10-02',
            'BelongsToBibliography'   => 1,
            'EmbargoDate'             => '2010-01-04',
        ],
    ];

    /**
     * @return array
     */
    public static function documentPropertiesProvider()
    {
        return self::$documentPropertySets;
    }

    /**
     * @param string $name
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
     * @param null|array $documentProperties map of a document's properties into values
     * @return Document created document
     * @throws Exception
     */
    protected function createDocument($documentProperties = null)
    {
        if ($documentProperties === null) {
            $documentProperties = self::$documentPropertySets['article'];
        } if (is_string($documentProperties)) {
            $documentProperties = self::$documentPropertySets[$documentProperties];
        }

        $document = new Document();

        /*
         * set all defined internal properties of document
         */

        foreach ($documentProperties as $property => $value) {
            if (! is_array($value)) {
                // got value of some document-internal field
                $method = 'set' . $property;
                $document->$method($value);
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
                    $value = [$value];
                }

                $adder = 'add' . $property;

                // add another dependent model for every given description
                foreach ($value as $set) {
                    /** @var AbstractDependentModel $related */
                    $related = null;

                    if ($pre) {
                        $related = $pre->newInstance();
                    } else {
                        $related = $document->$adder();
                    }

                    foreach ($set as $name => $value) {
                        $setter = 'set' . $name;
                        $related->$setter($value);
                    }

                    $related->store();

                    $this->created[] = $related;

                    if ($pre) {
                        $document->$adder($related);
                    }
                }
            }
        }

        // store document again e.g. for updating related caches
        $document->store();

        return $document;
    }

    /**
     * @param string $filename
     * @return string
     */
    public function qualifyTestFilename($filename)
    {
        return APPLICATION_PATH . '/tests/fulltexts/' . basename($filename);
    }

    /**
     * @param string $filename
     * @return false|string
     */
    public function getTestFile($filename)
    {
        return file_get_contents($this->qualifyTestFilename($filename));
    }

    /**
     * @param string $filename
     * @param string $label
     * @param bool   $visibleInFrontdoor
     * @return Document
     * @throws ModelException
     */
    public function addFileToDocument(Document $document, $filename, $label, $visibleInFrontdoor)
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

        $cache  = new Cache(false);
        $config = Config::get();
        $files  = $config->workspacePath . '/files/';

        foreach ($this->created as $model) {
            /** @var AbstractDb $model */
            if ($model instanceof Document) {
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
