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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Sascha Szott <szott@zib.de>
 * @author      Gunar Maiwald <maiwald@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Util;

use DOMDocument;
use DOMElement;
use DOMNamedNodeMap;
use DOMNode;
use DOMNodeList;
use Exception;
use Opus\Collection;
use Opus\DnbInstitute;
use Opus\Document;
use Opus\EnrichmentKey;
use Opus\Licence;
use Opus\LoggingTrait;
use Opus\Model\NotFoundException;
use Opus\Person;
use Opus\Series;
use Opus\Subject;

use function array_diff;
use function libxml_clear_errors;
use function libxml_use_internal_errors;
use function substr;
use function trim;
use function ucfirst;

/**
 * phpcs:disable
 */
class MetadataImport
{
    use LoggingTrait;

    /** @var mixed|null */
    private $logfile;

    /** @var DOMDocument */
    private $xml;

    /** @var string */
    private $xmlFile;

    /** @var string */
    private $xmlString;

    /** @var array */
    private $fieldsToKeepOnUpdate = [];

    public function __construct($xml, $isFile = false, $logger = null, $logfile = null)
    {
        if ($logger !== null) {
            $this->setLogger($logger);
        }

        $this->logfile = $logfile;

        if ($isFile) {
            $this->xmlFile = $xml;
        } else {
            $this->xmlString = $xml;
        }
    }

    public function run()
    {
        $this->xml = $this->__getXML();

        $this->__validateXML();

        $numOfDocsImported = 0;
        $numOfSkippedDocs  = 0;

        foreach ($this->xml->getElementsByTagName('opusDocument') as $opusDocumentElement) {
            // save oldId for later referencing of the record under consideration
            $oldId = $opusDocumentElement->getAttribute('oldId');
            $opusDocumentElement->removeAttribute('oldId');

            $this->log("Start processing of record #" . $oldId . " ...");

            /*
             * @var Document
             */
            $doc = null;
            if ($opusDocumentElement->hasAttribute('docId')) {
                // perform metadata update on given document
                $docId = $opusDocumentElement->getAttribute('docId');
                try {
                    $doc = new Document($docId);
                    $opusDocumentElement->removeAttribute('docId');
                } catch (NotFoundException $e) {
                    $this->log('Could not load document #' . $docId . ' from database: ' . $e->getMessage());
                    $this->appendDocIdToRejectList($oldId);
                    $numOfSkippedDocs++;
                    continue;
                }

                $this->resetDocument($doc);
            } else {
                // create new document
                $doc = new Document();
            }

            try {
                $this->processAttributes($opusDocumentElement->attributes, $doc);
                $this->processElements($opusDocumentElement->childNodes, $doc);
            } catch (Exception $e) {
                $this->log('Error while processing document #' . $oldId . ': ' . $e->getMessage());
                $this->appendDocIdToRejectList($oldId);
                $numOfSkippedDocs++;
                continue;
            }

            try {
                $doc->store();
            } catch (Exception $e) {
                $this->log('Error while saving imported document #' . $oldId . ' to database: ' . $e->getMessage());
                $this->appendDocIdToRejectList($oldId);
                $numOfSkippedDocs++;
                continue;
            }

            $numOfDocsImported++;
            $this->log('... OK');
        }

        if ($numOfSkippedDocs === 0) {
            $this->log("Import finished successfully. $numOfDocsImported documents were imported.");
        } else {
            $this->log("Import finished. $numOfDocsImported documents were imported. $numOfSkippedDocs documents were skipped.");
            throw new MetadataImportSkippedDocumentsException("$numOfSkippedDocs documents were skipped during import.");
        }
    }

    private function log($string)
    {
        if ($this->logger === null) {
            return;
        }
        $this->logger->log($string);
    }

    private function __getXML()
    {
        // Enable user error handling while validating input
        libxml_clear_errors();
        $useInternalErrors = libxml_use_internal_errors(true);

        $this->log("Load XML ...");
        $xml = new DOMDocument();

        if ($this->xmlFile !== null) {
            if (! $xml->load($this->xmlFile)) {
                $errMsg = MetadataImportXmlValidation::getErrorMessage();
                $this->log(
                    "... ERROR: Cannot load XML document $this->xmlFile: make sure it is well-formed. $errMsg"
                );
                throw new MetadataImportInvalidXmlException('XML is not well-formed.');
            }
        } else {
            if (! $xml->loadXML($this->xmlString)) {
                $errMsg = MetadataImportXmlValidation::getErrorMessage();
                $this->log("... ERROR: Cannot load XML document: make sure it is well-formed. $errMsg");
                throw new MetadataImportInvalidXmlException('XML is not well-formed.');
            }
        }

        $this->log('... OK');
        libxml_use_internal_errors($useInternalErrors);
        libxml_clear_errors();
        return $xml;
    }

    private function __validateXML()
    {
        $this->log("Validate XML ...");

        $validation = new MetadataImportXmlValidation($this->xml);
        try {
            $validation->checkValidXml();
        } catch (MetadataImportInvalidXmlException $e) {
            $this->log("... ERROR: XML document is not valid: " . $e->getMessage());
            throw $e;
        }

        $this->log('... OK');
    }

    /**
     * @param int $docId
     */
    private function appendDocIdToRejectList($docId)
    {
        $this->log('... SKIPPED');
        if ($this->logfile === null) {
            return;
        }
        $this->logfile->log($docId);
    }

    /**
     * Allows certain fields to be kept on update.
     *
     * @param array $fields DescriptionArray of fields to keep on update
     */
    public function keepFieldsOnUpdate($fields)
    {
        $this->fieldsToKeepOnUpdate = $fields;
    }

    /**
     * @param Document $doc
     */
    private function resetDocument($doc)
    {
        $fieldsToDelete = array_diff([
            'TitleMain',
            'TitleAbstract',
            'TitleParent',
            'TitleSub',
            'TitleAdditional',
            'Identifier',
            'Note',
            'Enrichment',
            'Licence',
            'Person',
            'Series',
            'Collection',
            'Subject',
            'ThesisPublisher',
            'ThesisGrantor',
            'PublishedDate',
            'PublishedYear',
            'CompletedDate',
            'CompletedYear',
            'ThesisDateAccepted',
            'ThesisYearAccepted',
            'ContributingCorporation',
            'CreatingCorporation',
            'Edition',
            'Issue',
            'Language',
            'PageFirst',
            'PageLast',
            'PageNumber',
            'ArticleNumber',
            'PublisherName',
            'PublisherPlace',
            'Type',
            'Volume',
            'BelongsToBibliography',
            'ServerState',

            // 'ServerDateCreated', TODO do not delete ServerDateCreated when updating document (no default in db)
            'ServerDateModified',
            'ServerDatePublished',
            'ServerDateDeleted',
        ], $this->fieldsToKeepOnUpdate);

        $doc->deleteFields($fieldsToDelete);
    }

    /**
     * @param DOMNamedNodeMap $attributes
     * @param Document        $doc
     */
    private function processAttributes($attributes, $doc)
    {
        foreach ($attributes as $attribute) {
            $method = 'set' . ucfirst($attribute->name);
            $value  = trim($attribute->value);
            // TODO use filtervar for BOOLEAN here
            if ($attribute->name === 'belongsToBibliography') {
                if ($value === 'true') {
                    $value = '1';
                } elseif ($value === 'false') {
                    $value = '0';
                }
            }
            $doc->$method($value);
        }
    }

    /**
     * @param  DOMNodeList $elements
     * @param Document    $doc
     */
    private function processElements($elements, $doc)
    {
        foreach ($elements as $node) {
            if ($node instanceof DOMElement) {
                switch ($node->tagName) {
                    case 'titlesMain':
                        $this->handleTitleMain($node, $doc);
                        break;
                    case 'titles':
                        $this->handleTitles($node, $doc);
                        break;
                    case 'abstracts':
                        $this->handleAbstracts($node, $doc);
                        break;
                    case 'persons':
                        $this->handlePersons($node, $doc);
                        break;
                    case 'keywords':
                        $this->handleKeywords($node, $doc);
                        break;
                    case 'dnbInstitutions':
                        $this->handleDnbInstitutions($node, $doc);
                        break;
                    case 'identifiers':
                        $this->handleIdentifiers($node, $doc);
                        break;
                    case 'notes':
                        $this->handleNotes($node, $doc);
                        break;
                    case 'collections':
                        $this->handleCollections($node, $doc);
                        break;
                    case 'series':
                        $this->handleSeries($node, $doc);
                        break;
                    case 'enrichments':
                        $this->handleEnrichments($node, $doc);
                        break;
                    case 'licences':
                        $this->handleLicences($node, $doc);
                        break;
                    case 'dates':
                        $this->handleDates($node, $doc);
                        break;
                    default:
                        break;
                }
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleTitleMain($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $t = $doc->addTitleMain();
                $t->setValue(trim($childNode->textContent));
                $t->setLanguage(trim($childNode->getAttribute('language')));
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleTitles($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $method = 'addTitle' . ucfirst($childNode->getAttribute('type'));
                $t      = $doc->$method();
                $t->setValue(trim($childNode->textContent));
                $t->setLanguage(trim($childNode->getAttribute('language')));
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleAbstracts($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $t = $doc->addTitleAbstract();
                $t->setValue(trim($childNode->textContent));
                $t->setLanguage(trim($childNode->getAttribute('language')));
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handlePersons($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $p = new Person();

                // mandatory fields
                $p->setFirstName(trim($childNode->getAttribute('firstName')));
                $p->setLastName(trim($childNode->getAttribute('lastName')));

                // optional fields
                $optionalFields = ['academicTitle', 'email', 'placeOfBirth', 'dateOfBirth'];
                foreach ($optionalFields as $optionalField) {
                    if ($childNode->hasAttribute($optionalField)) {
                        $method = 'set' . ucfirst($optionalField);
                        $p->$method(trim($childNode->getAttribute($optionalField)));
                    }
                }

                $method = 'addPerson' . ucfirst($childNode->getAttribute('role'));
                $link   = $doc->$method($p);

                if ($childNode->hasAttribute('allowEmailContact') && ($childNode->getAttribute('allowEmailContact') === 'true' || $childNode->getAttribute('allowEmailContact') === '1')) {
                    $link->setAllowEmailContact(true);
                }
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleKeywords($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $s = new Subject();
                $s->setLanguage(trim($childNode->getAttribute('language')));
                $s->setType($childNode->getAttribute('type'));
                $s->setValue(trim($childNode->textContent));
                $doc->addSubject($s);
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleDnbInstitutions($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $instId   = trim($childNode->getAttribute('id'));
                $instRole = $childNode->getAttribute('role');
                // check if dnbInstitute with given id and role exists
                try {
                    $inst = new DnbInstitute($instId);

                    // check if dnbInstitute supports given role
                    $method = 'getIs' . ucfirst($instRole);
                    if ($inst->$method() === '1') {
                        $method = 'addThesis' . ucfirst($instRole);
                        $doc->$method($inst);
                    } else {
                        throw new Exception('given role ' . $instRole . ' is not allowed for dnbInstitution id ' . $instId);
                    }
                } catch (NotFoundException $e) {
                    throw new Exception('dnbInstitution id ' . $instId . ' does not exist: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleIdentifiers($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $i = $doc->addIdentifier();
                $i->setValue(trim($childNode->textContent));
                $i->setType($childNode->getAttribute('type'));
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleNotes($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $n = $doc->addNote();
                $n->setMessage(trim($childNode->textContent));
                $n->setVisibility($childNode->getAttribute('visibility'));
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleCollections($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $collectionId = trim($childNode->getAttribute('id'));
                // check if collection with given id exists
                try {
                    $c = new Collection($collectionId);
                    $doc->addCollection($c);
                } catch (NotFoundException $e) {
                    throw new Exception('collection id ' . $collectionId . ' does not exist: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleSeries($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $seriesId = trim($childNode->getAttribute('id'));
                // check if document set with given id exists
                try {
                    $s    = new Series($seriesId);
                    $link = $doc->addSeries($s);
                    $link->setNumber(trim($childNode->getAttribute('number')));
                } catch (NotFoundException $e) {
                    throw new Exception('series id ' . $seriesId . ' does not exist: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleEnrichments($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $key = trim($childNode->getAttribute('key'));
                // check if enrichment key exists
                try {
                    new EnrichmentKey($key);
                } catch (NotFoundException $e) {
                    throw new Exception('enrichment key ' . $key . ' does not exist: ' . $e->getMessage());
                }

                $e = $doc->addEnrichment();
                $e->setKeyName($key);
                $e->setValue(trim($childNode->textContent));
            }
        }
    }

    /**
     * @param  DOMNode  $node
     * @param Document $doc
     */
    private function handleLicences($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $licenceId = trim($childNode->getAttribute('id'));
                try {
                    $l = new Licence($licenceId);
                    $doc->addLicence($l);
                } catch (NotFoundException $e) {
                    throw new Exception('licence id ' . $licenceId . ' does not exist: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * @param DOMNode  $node
     * @param Document $doc
     */
    private function handleDates($node, $doc)
    {
        foreach ($node->childNodes as $childNode) {
            if ($childNode instanceof DOMElement) {
                $method = '';
                if ($childNode->hasAttribute('monthDay')) {
                    $method = 'Date';
                } else {
                    $method = 'Year';
                }

                if ($childNode->getAttribute('type') === 'thesisAccepted') {
                    $method = 'setThesis' . $method . 'Accepted';
                } else {
                    $method = 'set' . ucfirst($childNode->getAttribute('type')) . $method;
                }

                $date = trim($childNode->getAttribute('year'));
                if ($childNode->hasAttribute('monthDay')) {
                    // ignore first character of monthDay's attribute value (is always a hyphen)
                    $date .= substr(trim($childNode->getAttribute('monthDay')), 1);
                }

                $doc->$method($date);
            }
        }
    }
}
