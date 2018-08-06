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

class Opus_Doi_DataCiteXmlGenerator
{

    const STYLESHEET_FILENAME = 'datacite.xslt';

    /**
     * Erzeugt für das übergebene OPUS-Dokument eine XML-Repräsentation, die von DataCite als
     * Metadatenbeschreibung des Dokuments bei der DOI-Registrierung akzeptiert wird.
     *
     * @param $doc Opus_Document
     */
    public function getXml($doc)
    {
        // DataCite-XML wird mittels XSLT aus OPUS-XML erzeugt
        $xslt = new DOMDocument();
        $xsltPath = $this->getStylesheetPath();

        $success = false;
        if (file_exists($xsltPath)) {
            $success = $xslt->load($xsltPath);
        }

        $log = Zend_Registry::get('Zend_Log');
        if (!$success) {
            $message = "could not find XSLT file $xsltPath";
            $log->err($message);
            throw new Opus_Doi_DataCiteXmlGenerationException($message);
        }

        $proc = new XSLTProcessor();
        $proc->importStyleSheet($xslt);

        if (!$this->checkRequiredFields($doc, $log)) {
            throw new Opus_Doi_DataCiteXmlGenerationException('required fields are missing in document ' . $doc->getId() . ' - check log for details');
        }

        $modelXml = $this->getModelXml($doc);
        $log->debug('OPUS-XML: ' . $modelXml->saveXML());

        $this->handleLibXmlErrors($log, true);
        $result = $proc->transformToDoc($modelXml);
        if (!$result) {
            $message = 'errors occurred in XSLT transformation of document ' . $doc->getId();
            $log->err($message);
            $this->handleLibXmlErrors($log);
            throw new Opus_Doi_DataCiteXmlGenerationException($message);
        }

        $log->debug('DataCite-XML: '. $result->saveXML());

        $this->handleLibXmlErrors($log, true);

        $xsdPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'metadata.xsd';
        if (!file_exists($xsdPath)) {
            $message = 'could not load schema file from ' . $xsdPath;
            $log->err($message);
            throw new Opus_Doi_DataCiteXmlGenerationException($message);
        }

        // Validierung des erzeugten DataCite-XML findet bereits hier statt, da ein invalides XML
        // beim späteren Registrierungsversuch einen HTTP Fehler 400 auslöst
        $validationResult = $result->schemaValidate($xsdPath);
        if (!$validationResult) {
            $message = 'generated DataCite XML for document ' . $doc->getId() . ' is NOT valid';
            $log->err($message);
            $this->handleLibXmlErrors($log);
            throw new Opus_Doi_DataCiteXmlGenerationException($message);
        }

        return $result->saveXML();
    }

    /**
     * @param $doc Opus_Document
     */
    private function checkRequiredFields($doc, $log)
    {
        $log->info('checking document ' . $doc->getId());

        // mind. ein Autor mit einem nicht-leeren LastName oder FirstName oder CreatingCorporation darf nicht leer sein
        $authorOk = false;
        $authors = $doc->getPersonAuthor();
        foreach ($authors as $author) {
            if ($author->getLastName() != '' or $author->getFirstName() != '') {
                $authorOk = true;
                break;
            }
        }
        if (!$authorOk) {
            if ($doc->getCreatingCorporation() == '') {
                $log->err('document ' . $doc->getId() . ' does not provide content for element creatorName');
                return false;
            }
        }

        // mind. ein nicht-leerer TitleMain oder TitleSub
        $titleOk = false;
        $titles = $doc->getTitleMain();
        foreach ($titles as $title) {
            if ($title->getValue() != '') {
                $titleOk = true;
                break;
            }
        }
        if (!$titleOk) {
            $titles = $doc->getTitleSub();
            foreach ($titles as $title) {
                if ($title->getValue() != '') {
                    $titleOk = true;
                    break;
                }
            }
            if (!$titleOk) {
                $log->err('document ' . $doc->getId() . ' does not provide content for element title');
                return false;
            }
        }

        // PublisherName nicht leer oder mind. ein ThesisPublisher mit nicht-leerem Namen
        // FIXME was passiert, wenn mehr als ein ThesisPublisher mit Dokument verknüpft ist?
        $publisherOk = false;
        if ($doc->getPublisherName() != '') {
            $publisherOk = true;
        }
        if (!$publisherOk) {
            $thesisPublishers = $doc->getThesisPublisher();
            foreach ($thesisPublishers as $thesisPublisher) {
                if ($thesisPublisher->getName() != '') {
                    $publisherOk = true;
                    break;
                }
            }
            if (!$publisherOk) {
                $log->err('document ' . $doc->getId() . ' does not provide content for element publisher');
                return false;
            }
        }

        // CompletedYear muss gefüllt sein
        // FIXME alternativ auch andere Datumsfelder betrachten?
        if ($doc->getCompletedYear() == '') {
            $log->err('document ' . $doc->getId() . ' does not provide content for element publicationYear');
            return false;
        }

        return true;
    }

    private function handleLibXmlErrors($log, $reset = false) {
        if ($reset) {
            libxml_clear_errors();
        }
        else {
            foreach (libxml_get_errors() as $error) {
                $log->err("libxml error: {$error->message}");
            }
        }
        libxml_use_internal_errors($reset);
    }

    private function getModelXml($doc) {
        $xmlDoc = new Opus_Model_Xml();
        $xmlDoc->setModel($doc);
        $xmlDoc->excludeEmptyFields();
        $xmlDoc->setStrategy(new Opus_Model_Xml_Version1);
        return $xmlDoc->getDomDocument();
    }

    /**
     * Returns path to DataCite XSLT file.
     *
     * TODO refactor getting Zend_Config and Zend_Log
     */
    public function getStylesheetPath()
    {
        $config = Zend_Registry::get('Zend_Config');

        $stylesheetPath = null;

        if (isset($config->datacite->stylesheetPath)) {
            $stylesheetPath = $config->datacite->stylesheetPath;

            if (!is_readable($stylesheetPath)) {
                Zend_Registry::get('Zend_Log')->warn('Configured DataCite XSLT file not found');
                $stylesheetPath = null;
            }
        }

        // use default path if non was given or found
        if (is_null($stylesheetPath)) {
            $stylesheetPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . self::STYLESHEET_FILENAME;
        }

        return $stylesheetPath;
    }
}
