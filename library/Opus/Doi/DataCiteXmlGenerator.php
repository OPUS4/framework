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
 * @author      Sascha Szott <szott@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Doi;

use Opus\Document;
use Opus\Model\Xml;
use Opus\Model\Xml\Version1;

/**
 * Class Opus\Doi\DataCiteXmlGenerator
 *
 * TODO processing multiple documents requires getting logger and XSLT over and over again
 * TODO use LoggingTrait to get standard logger
 */
class DataCiteXmlGenerator
{

    const STYLESHEET_FILENAME = 'datacite.xslt';

    const USE_PLACEHOLDERS_FOR_EMPTY_VALUES_DEFAULT = true;

    private $doiLog;

    // wenn true, dann werden für bestimmte DataCite-XML-Pflichtfelder, die nicht mit Inhalt belegt werden können,
    // alternativ Platzhalterwerte verwendet
    private $usePlaceholdersForEmptyValues;

    /**
     * Opus\Doi\DataCiteXmlGenerator constructor.
     * @param boolean $usePlaceholdersForEmptyValues
     */
    public function __construct($usePlaceholdersForEmptyValues = self::USE_PLACEHOLDERS_FOR_EMPTY_VALUES_DEFAULT)
    {
        $this->usePlaceholdersForEmptyValues = $usePlaceholdersForEmptyValues;
    }


    public function getDoiLog()
    {
        if (is_null($this->doiLog)) {
            // use standard logger if nothing is set
            $this->doiLog = \Zend_Registry::get('Zend_Log');
        }

        return $this->doiLog;
    }

    public function setDoiLog(\Zend_Log $logger)
    {
        $this->doiLog = $logger;
    }

    /**
     * Erzeugt für das übergebene OPUS-Dokument eine XML-Repräsentation, die von DataCite als
     * Metadatenbeschreibung des Dokuments bei der DOI-Registrierung akzeptiert wird.
     *
     * @param $doc Document
     * @param $allowInvalidXml bool wenn true, dann erfolgt bei der XML-Generierung keine Prüfung des erzeugten Resultats
     *                              auf Validität, z.B. erwartete Pflichtfelder
     * @param $skipTestOfRequiredFields bool wenn true, dann wird der Pflichtfeld-Existenztest bei der XML-Generierung
     *                                       übersprungen
     * @return string XML for DataCite registration
     * @throws DataCiteXmlGenerationException
     */
    public function getXml($doc, $allowInvalidXml = false, $skipTestOfRequiredFields = false)
    {
        // DataCite-XML wird mittels XSLT aus OPUS-XML erzeugt
        $xslt = new \DOMDocument();
        $xsltPath = $this->getStylesheetPath();

        $success = false;
        if (is_readable($xsltPath)) {
            $success = $xslt->load($xsltPath);
        }

        $log = \Zend_Registry::get('Zend_Log'); // TODO use LoggingTrait

        if (! $success) {
            $message = "could not find XSLT file $xsltPath";
            $log->err($message);
            throw new DataCiteXmlGenerationException($message);
        }

        $proc = new \XSLTProcessor();
        $proc->registerPHPFunctions('Opus\Language::getLanguageCode');
        $proc->importStyleSheet($xslt);

        if (! $skipTestOfRequiredFields && ! $allowInvalidXml && ! $this->checkRequiredFields($doc)) {
            throw new DataCiteXmlGenerationException(
                'required fields are missing in document ' . $doc->getId() . ' - check log for details'
            );
        }

        $modelXml = $this->getModelXml($doc);
        $log->debug('OPUS-XML: ' . $modelXml->saveXML());

        $this->removeNodesOfInvisibleFiles($modelXml);

        $this->handleLibXmlErrors($log, true);

        $result = $proc->transformToDoc($modelXml);
        if (! $result) {
            $message = 'errors occurred in XSLT transformation of document ' . $doc->getId();
            $log->err($message);
            $xmlErrors = $this->handleLibXmlErrors($log);
            throw new DataCiteXmlGenerationException($message, $xmlErrors);
        }

        $log->debug('DataCite-XML: ' . $result->saveXML());

        $this->handleLibXmlErrors($log, true);

        // Validierung des erzeugten DataCite-XML findet bereits hier statt, da ein invalides XML beim späteren
        // Registrierungsversuch einen HTTP Fehler 400 auslöst
        if (! $allowInvalidXml) {
            $xsdPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'metadata.xsd';
            if (! is_readable($xsdPath)) {
                $message = 'could not load schema file from ' . $xsdPath;
                $log->err($message);
                throw new DataCiteXmlGenerationException($message);
            }

            $validationResult = $result->schemaValidate($xsdPath);
            if (! $validationResult) {
                $message = 'generated DataCite XML for document ' . $doc->getId() . ' is NOT valid';
                $log->err($message);
                $xmlErrors = $this->handleLibXmlErrors($log);
                throw new DataCiteXmlGenerationException($message, $xmlErrors);
            }
        }

        return $result->saveXML();
    }

    /**
     * @param $doc Document
     * @param $lazyChecking wenn true, dann wird die Prüfung beendet, sobald ein fehlendes Pflichtfeld
     *                      festgestellt wurde; in diesem Modus gibt die Methode entweder true (alle
     *                      Pflichtfelder vorhanden) oder false zurück
     *                      wenn $lazyChecking auf false gesetzt, so wird ein Status-Array zurückgegeben,
     *                      in dem für jedes Pflichtfeld die Existenz ausgewiesen ist
     * @return boolean | array
     */
    public function checkRequiredFields($doc, $lazyChecking = true)
    {
        $doiLog = $this->getDoiLog();
        $doiLog->info('checking required field of document ' . $doc->getId());

        $status = [];

        $result = $this->checkExistenceOfLocalDoi($doc);
        if (! empty($result) && $lazyChecking) {
            $doiLog->err('document ' . $doc->getId() . ' does not provide content for element identifier');
            return false;
        }
        $this->setStatusEntry($status, 'identifier', $result);

        $result = $this->checkExistenceOfCreator($doc);
        if (! empty($result) && $lazyChecking) {
            $doiLog->err('document ' . $doc->getId() . ' does not provide content for element creators');
            return false;
        }
        $this->setStatusEntry($status, 'creators', $result);

        $result = $this->checkExistenceOfTitle($doc);
        if (! empty($result) && $lazyChecking) {
            $doiLog->err('document ' . $doc->getId() . ' does not provide content for element titles');
            return false;
        }
        $this->setStatusEntry($status, 'titles', $result);

        $result = $this->checkExistenceOfPublisher($doc);
        if (! empty($result) && $lazyChecking) {
            $doiLog->err('document ' . $doc->getId() . ' does not provide content for element publisher');
            return false;
        }
        $this->setStatusEntry($status, 'publisher', $result);

        $result = $this->checkExistenceOfPublicationYear($doc);
        if (! empty($result) && $lazyChecking) {
            $doiLog->err('document ' . $doc->getId() . ' does not provide content for element publicationYear');
            return false;
        }
        $this->setStatusEntry($status, 'publicationYear', $result);

        // Dokumenttyp darf nicht leer sein
        if ($doc->getType() == '') {
            if ($lazyChecking) {
                $doiLog->err('document ' . $doc->getId() . ' does not provide content for element resourceType');
                return false;
            }
            $this->setStatusEntry($status, 'resourceType', ['document_type_missing']);
        } else {
            $this->setStatusEntry($status, 'resourceType');
        }

        if ($lazyChecking) {
            // nur wenn $lazyChecking gesetzt ist, gibt die Methode einen booleschen Rückgabewert zurück
            return true;
        }
        return $status;
    }

    /**
     * Hilfsmethode für die Initialisierung des Status-Arrays.
     *
     * @param $key
     * @param $result
     */
    private function setStatusEntry(&$status, $key, $result = null)
    {
        if (empty($result)) {
            $status[$key] = true;
        } else {
            $status[$key] = $result[0];
        }
    }

    /**
     * In dem übergebenen Dokument muss genau eine lokale DOI existieren.
     *
     * @param $doc das zu prüfende Dokument
     * @return array gibt leeres Array zurück, wenn das übergebene Dokument genau eine lokale DOI besitzt;
     *               andernfalls stehen im Array die gefundenen Fehler
     */
    private function checkExistenceOfLocalDoi($doc)
    {
        // existiert überhaupt eine DOI?
        $dois = $doc->getIdentifierDoi();
        if (empty($dois)) {
            return ['local_DOI_missing'];
        }

        // ist unter den vorhandenen DOIs überhaupt eine lokale DOI
        $localDois = [];
        foreach ($dois as $doi) {
            if ($doi->isLocalDoi()) {
                $localDois[] = $doi;
            }
        }
        if (empty($localDois)) {
            return ['local_DOI_missing'];
        }

        // existiert genau eine lokale DOI
        if (count($localDois) > 1) {
            return ['multiple_local_DOIs'];
        }

        return [];
    }

    /**
     * In dem übergebenen Dokument muss mindestens ein Autor mit einem nicht-leeren LastName oder FirstName
     * oder eine nicht leere CreatingCorporation existieren.
     *
     * @param Document $doc das zu prüfende Dokument
     *
     * @return array gibt leeres Array zurück, wenn Autor mit den o.g. Bedingungen existiert; andernfalls steht im
     *               Array der gefundene Fehler
     */
    private function checkExistenceOfCreator($doc)
    {
        if ($this->usePlaceholdersForEmptyValues) {
            return []; // keine Prüfung erforderlich, weil Platzhalter im Bedarfsfall genutzt wird
        }

        $authorOk = false;
        $authors = $doc->getPersonAuthor();

        foreach ($authors as $author) {
            if ($author->getLastName() != '' or $author->getFirstName() != '') {
                $authorOk = true;
                break;
            }
        }

        if (! $authorOk) {
            if ($doc->getCreatingCorporation() == '') {
                // Pflichtfeld creatorName kann nicht mit Inhalt belegt werden
                return ['creator_missing'];
            }
        }

        return [];
    }

    /**
     * In dem übergebenen Dokument muss mindestens ein nicht leerer Titel existieren.
     *
     * @param Document $doc das zu prüfende Dokument
     *
     * @return array gibt ein leeres Array zurück, wenn ein nicht leerer Titel gefunden wurde; andernfalls steht im
     *               Array der gefundene Fehler
     */
    private function checkExistenceOfTitle($doc)
    {
        if ($this->usePlaceholdersForEmptyValues) {
            return []; // keine Prüfung erforderlich, weil Platzhalter im Bedarfsfall genutzt wird
        }

        // mindestens ein nicht-leerer TitleMain oder TitleSub
        $titleOk = false;
        $titles = $doc->getTitleMain();

        foreach ($titles as $title) {
            if ($title->getValue() != '') {
                $titleOk = true;
                break;
            }
        }

        if (! $titleOk) {
            $titles = $doc->getTitleSub();

            foreach ($titles as $title) {
                if ($title->getValue() != '') {
                    $titleOk = true;
                    break;
                }
            }

            if (! $titleOk) {
                return ['title_missing'];
            }
        }

        return [];
    }

    /**
     * In dem übergebenen Dokument muss genau ein Publisher existieren.
     *
     * @param Document $doc das zu prüfende Dokument
     * @return array gibt ein leeres Array zurück, wenn genau ein nicht leerer Titel gefunden wurde; andernfalls
     *               steht im Array der gefundene Fehler
     */
    private function checkExistenceOfPublisher($doc)
    {
        if ($this->usePlaceholdersForEmptyValues) {
            return []; // keine Prüfung erforderlich, weil Platzhalter im Bedarfsfall genutzt wird
        }

        $publisherOk = $doc->getPublisherName() != '';

        if (! $publisherOk) {
            $thesisPublishers = $doc->getThesisPublisher();
            foreach ($thesisPublishers as $thesisPublisher) {
                if ($thesisPublisher->getName() != '') {
                    if ($publisherOk) {
                        // mehr als einen nicht leeren ThesisPublisher gefunden
                        return ['multiple_publishers'];
                    }

                    $publisherOk = true;
                }
            }
        }

        if (! $publisherOk) {
            $publisherOk = $doc->getCreatingCorporation() != '';
        }

        if (! $publisherOk) {
            $publisherOk = $doc->getContributingCorporation() != '';
        }

        if (! $publisherOk) {
            return ['publisher_missing'];
        }

        return [];
    }

    /**
     * In dem übergebenen Dokument muss ein Publikationsjahr existieren. Dieses wird aus dem Feld
     * ServerDatePublished abgeleitet, das bei der Freischaltung eines OPUS-Dokuments automatisch
     * gesetzt wird.
     *
     * @param Document $doc das zu prüfende Dokument
     *
     * @return bool gibt ein leeres Array zurück, wenn ein Publikationsjahr gefunden wurde; andernfalls
     *              steht im Array der gefundene Fehler
     */
    private function checkExistenceOfPublicationYear($doc)
    {
        $publicationDate = $doc->getServerDatePublished();
        if (is_null($publicationDate)) {
            // dieser Fall kann eigentlich nur eintreten, wenn das Dokument noch nicht freigeschaltet wurde
            if ($doc->getServerState() !== 'published') {
                return ['publication_date_missing_non_published'];
            }

            // wenn ein freigeschaltetes Dokument kein Freischaltungsdatum hat, dann ist das ein Fehler
            return ['publication_date_missing'];
        }

        $publicationYear = $publicationDate->getYear();
        if (is_null($publicationYear) || $publicationYear == 0 || preg_match('/^[\d]{4}$/', $publicationYear) !== 1) {
            // dieser Fall kann nicht auftreten, wenn das Freischaltungsdatum automatisch vom System gesetzt wird
            return ['publication_year_missing'];
        }

        return [];
    }

    private function handleLibXmlErrors($log, $reset = false)
    {
        $result = [];
        if ($reset) {
            libxml_clear_errors();
        } else {
            foreach (libxml_get_errors() as $error) {
                $log->err("libxml error: {$error->message}");
                $result[] = $error->message;
            }
        }
        libxml_use_internal_errors($reset);
        return $result;
    }

    private function getModelXml($doc)
    {
        $xmlDoc = new Xml();
        $xmlDoc->setModel($doc);
        $xmlDoc->excludeEmptyFields();
        $xmlDoc->setStrategy(new Version1());
        return $xmlDoc->getDomDocument();
    }

    /**
     * Returns path to DataCite XSLT file.
     *
     * TODO refactor getting \Zend_Config and \Zend_Log
     */
    public function getStylesheetPath()
    {
        $config = \Zend_Registry::get('Zend_Config');

        $stylesheetPath = null;

        if (isset($config->datacite->stylesheetPath)) {
            $stylesheetPath = $config->datacite->stylesheetPath;

            if (! is_readable($stylesheetPath)) {
                \Zend_Registry::get('Zend_Log')->warn('Configured DataCite XSLT file not found');
                $stylesheetPath = null;
            }
        }

        // use default path if non was given or found
        if (is_null($stylesheetPath)) {
            $stylesheetPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::STYLESHEET_FILENAME;
        }

        return $stylesheetPath;
    }

    /**
     * Entfernt alle File-Elemente aus dem übergebenen XML von Dateien, für die das Flag VisibleInOai nicht gesetzt ist.
     * Die Metadaten solcher Dateien sollen im DataCite-XML nicht erscheinen.
     *
     * @param $modelXml
     */
    private function removeNodesOfInvisibleFiles($modelXml)
    {
        $filenodes = $modelXml->getElementsByTagName('File');

        // Iterating over DOMNodeList is only save for readonly-operations -> create separate list
        $filenodesList = [];
        foreach ($filenodes as $filenode) {
            $filenodesList[] = $filenode;
        }

        // Remove filenodes which are invisible in oai (should not be in DataCite)
        foreach ($filenodesList as $filenode) {
            if ((false === $filenode->hasAttribute('VisibleInOai'))
                or ('1' !== $filenode->getAttribute('VisibleInOai'))) {
                $filenode->parentNode->removeChild($filenode);
            }
        }
    }
}
