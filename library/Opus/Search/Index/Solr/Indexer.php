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
 * the University Library of Hamburg Univeresity of Technology with funding from
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
 * @category    Framework
 * @package     Opus_Search
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: Indexer.php 3834 2009-11-18 16:28:06Z becker $
 */
class Opus_Search_Index_Solr_Indexer {

    // Connection to Solr server
    private $solr_server = null;

    // Solr server URL
    private $solr_server_url;

    // Logger
    private $log;

    /**
     * Establishes a connection to SolrServer.  Deletes all documents from index,
     * if $deleteAllDocs is set to true.
     *
     * @param boolean $deleteAllDocs Delete all docs.  Defaults to false.
     */
    public function __construct($deleteAllDocs = false) {
        $this->log = Zend_Registry::get('Zend_Log');
        $this->solr_server = $this->getSolrServer();
        $this->log->info('try to establish connection to Solr server ' . $this->solr_server_url);
        if (is_null($this->solr_server) || !$this->solr_server->ping()) {
            $this->log->err('Connection to Solr server ' . $this->solr_server_url . ' could not be established.');
            throw new Exception('Solr server ' . $this->solr_server_url . ' is not responding.');
        }
        $this->log->info('Connection to Solr server ' . $this->solr_server_url . ' was successfully established.');
        if (true === $deleteAllDocs) {
            $this->deleteAllDocs();
        }
    }

    /**
     * returns a Apache_Solr_Service object which encapsulates the communication
     * with the Solr server
     *
     * @return Apache_Solr_Server
     */
    private function getSolrServer() {        
        $config = Zend_Registry::get('Zend_Config');
        $solr_host = $config->searchengine->solr->host;
        $solr_port = $config->searchengine->solr->port;
        $solr_app = '/' . $config->searchengine->solr->app;
        $this->solr_server_url = 'http://' . $solr_host . ':' . $solr_port . $solr_app;
        return new Apache_Solr_Service($solr_host, $solr_port, $solr_app);
    }

    /**
     * Add a document to the index.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param Opus_Document $doc Model of the document that should be added to the index
     * @throws Exception
     * @return void
     */
    public function addDocumentToEntryIndex(Opus_Document $doc) {
        try {            
            // send xml directly to solr server instead of wrapping the document data
            // into an Apache_Solr_Document object offered by the solr php client library
            $this->sendSolrXmlToServer($this->getSolrXmlDocument($doc));
        }
        catch (Exception $e) {
            throw new Exception('error while adding document with id ' . $doc->getId() . ' : ' . $e->getMessage());
        }
    }

    /**
     * Removes a document from the index.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param Opus_Document $doc Model of the document that should be removed to the index
     * @throws InvalidArgumentException
     * @return void
     */
    public function removeDocumentFromEntryIndex(Opus_Document $doc = null) {
        if (true !== isset($doc)) {
            throw new InvalidArgumentException("Document parameter must not be NULL.");
        }
        try {
            $this->solr_server->deleteById($doc->getId());
        }
        catch (Exception $e) {
            $this->log->error('error while deleting document with id ' . $doc->getId() . ' : ' . $e->getMessage());
            throw $e;
        }
    }

    /* @var $doc Opus_Document */
    /**
     * returns a xml representation of the given document in the format that is
     * expected by Solr
     *
     * @param Opus_Document $doc
     * @return DOMDocument
     */
    private function getSolrXmlDocument($doc) {
        // Set up filter and get XML representation of filtered document.
        $filter = new Opus_Model_Filter;
        $filter->setModel($doc);
        $modelXml = $filter->toXml();
        $this->attachFulltextToXml($modelXml, $doc->getFile(), $doc->getId());

        // Set up XSLT stylesheet
        $xslt = new DomDocument;
        $xslt->load(dirname(__FILE__) . '/solr.xslt');

        // Set up XSLT processor
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xslt);

        $solrXmlDocument = new DOMDocument();
        $solrXmlDocument->preserveWhiteSpace = false;
        $solrXmlDocument->formatOutput = true;
        $solrXmlDocument->loadXML($proc->transformToXML($modelXml));

        /*
        $this->log->debug("\n" . $modelXml->saveXML());
        $this->log->debug("\n" . $solrXmlDocument->saveXML());
        */
        
        return $solrXmlDocument;
    }

    /* @var $modelXml DomDocument */
    /* @var $files    Opus_File   */
    /**
     * for each file that is associated to the given document the fulltext and
     * path information are attached to the xml representation of the document model     
     *
     * @param DomDocument $modelXml
     * @param Opus_File $files
     * @param $docId
     * @return void
     */
    private function attachFulltextToXml($modelXml, $files, $docId) {
        $docXml = $modelXml->getElementsByTagName('Opus_Model_Filter')->item(0);
        if (is_null($docXml)) {
            $this->log->warn('An error occurred while attaching fulltext information to the xml for document with id ' . $doc->getId());
            return;
        }
        if (count($files) == 0) {
            // Dokument besteht ausschließlich aus Metadaten
            $docXml->appendChild($modelXml->createElement('Source_Index', 'metadata'));
            $docXml->appendChild($modelXml->createElement('Fulltext_Index', ''));
            return;
        }
        foreach ($files as $file) {
            $docXml->appendChild($modelXml->createElement('Source_Index', $file->getPathName()));
            $fulltext = '';
            try {
                $fulltext = $this->getFileContent($file);
            }
            catch (Exception $e) {
                $this->log->debug('An error occurred while getting fulltext data for document with id ' . $docId . ': ' . $e->getMessage());
            }
            $element = $modelXml->createElement('Fulltext_Index');
            $element->appendChild($modelXml->createCDATASection(trim($fulltext)));
            $docXml->appendChild($element);            
        }
    }

    /* @var $file Opus_File */
    /**
     * returns the extracted fulltext of the given file or an exception in
     * case of errors
     *
     * @param Opus_File $file
     * @throws Exception
     * @return extracted fulltext
     */    
    private function getFileContent($file) {
        if (!$file->exists()) {
            throw new Exception($file->getPath() . ' does not exist.');
        }
        $fulltext = '';
        $mimeType = $file->getMimeType();
        if (substr($mimeType, 0, 9) === 'text/html') {
            $mimeType = 'text/html';
        }        
        switch ($mimeType) {
            case 'application/pdf':
                $fulltext = Opus_Search_Index_FileFormatConverter_PdfDocument::toText($file->getPath());
                break;
            case 'application/postscript':
                $fulltext = Opus_Search_Index_FileFormatConverter_PsDocument::toText($file->getPath(), true);
                break;
            case 'text/html':
                $fulltext = Opus_Search_Index_FileFormatConverter_HtmlDocument::toText($file->getPath());
                break;
            case 'text/plain':
                $fulltext = Opus_Search_Index_FileFormatConverter_TextDocument::toText($file->getPath());
                break;
            default:
                throw new Exception('No converter for MIME-Type ' . $mimeType);
        }
        return $fulltext;
    }

    /**
     * Deletes all index documents.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param query
     * @exception Exception
     * @return void
     */
    public function deleteAllDocs() {
        $this->deleteDocsByQuery("*");
        $this->log->info('all docs were deleted');
    }

    /**
     * Deletes all index documents that match the given query $query.  The
     * changes are not visible and a subsequent call to commit is required, to
     * make the changes visible.
     *
     * @param query
     * @exception
     * @return void
     *
     */
    public function deleteDocsByQuery($query) {
        try {
            $this->solr_server->deleteByQuery($query);
        }
        catch (Exception $e) {
            $this->log->error('error while deleting all documents that match query ' . $query . " : " . $e->getMessage());
            throw new Exception('error while deleting all documents that match query ' . $query . " : " . $e->getMessage());
        }
    }

    /**
     * Posts the given xml document to the Solr server without using the solr php client library.
     *
     * @param DOMDocument $solrXml
     */
    private function sendSolrXmlToServer($solrXml) {
        $stream = stream_context_create();
        stream_context_set_option(
            $stream,
            array(
                'http' => array(
                    'method' => 'POST',
                    'header' => 'Content-Type: text/xml; charset=UTF-8',
                    'content' => $solrXml->saveXML(),
                    'timeout' => '3600'
                )
            )
        );
        $response = new Apache_Solr_Response(@file_get_contents($this->solr_server_url . '/update', false, $stream));
        $this->log->debug('HTTP Status: ' . $response->getHttpStatus());
    }

    /**
     * Commits changes to the index
     *
     * @return void
     */
    public function commit() {
        try {
            $this->solr_server->commit();
        }
        catch (Exception $e) {
            $this->log->error('error while committing changes: ' . $e->getMessage());
            throw new Exception('error while committing changes: ' . $e->getMessage());
        }
    }

    /**
     * Optimizes the index
     *
     * @return void
     */
    public function optimize() {
        try {
            $this->solr_server->optimize();
        }
        catch (Exception $e) {
            $this->log->error('error while optimizing index: ' . $e->getMessage());
            throw new Exception('error while optimizing index: ' . $e->getMessage());
        }
    }

}