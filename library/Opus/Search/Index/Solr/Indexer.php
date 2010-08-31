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
 * @category    Framework
 * @package     Opus_Search
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: Indexer.php 3834 2009-11-18 16:28:06Z becker $
 */
class Opus_Search_Index_Solr_Indexer {

    /**
     * Connection to Solr server
     *
     * @var Apache_Solr_Service
     */
    private $index_server = null;

    /**
     * Connection to extraction server
     *
     * @var Apache_Solr_Service
     */
    private $extract_server = null;
    
    /**
     * Solr server URL
     * @var string
     */
    private $index_server_url;

    /**
     * extraction server URL
     * @var string
     */
    private $extract_server_url;

    /**    
     * @var Zend_Log
     */
    private $log;

    /**
     * Establishes a connection to a Solr server. Additionally, deletes all documents from index,
     * if $deleteAllDocs is set to true.
     *
     * @param boolean $deleteAllDocs Delete all docs.  Defaults to false.
     * @throws Opus_Search_Index_Solr_Exception If Solr server does not react or at least one
     * searchengine related parameter in configuration file is missing or empty.
     */
    public function __construct($deleteAllDocs = false) {
        $this->log = Zend_Registry::get('Zend_Log');
        
        $this->index_server = $this->getSolrServer('index');
        $this->ping('index_server');

        $this->extract_server = $this->getSolrServer('extract');
        $this->ping('extract_server');
        
        if (true === $deleteAllDocs) {
            $this->deleteAllDocs();
            $this->commit();
        }
    }

    /**
     * Pings the given server. Throws an exception if it does not react.
     *
     * @param string $server Server that should be pinged against.
     * @throws Opus_Search_Index_Solr_Exception If the given server does not react.
     */
    private function ping($server) {
        $url = $server . '_url';
        if (false === $this->$server->ping()) {
            $this->log->err('Connection to Solr server ' . $this->$url . ' could not be established.');
            throw new Opus_Search_Index_Solr_Exception('Solr server ' . $this->$url . ' is not responding.');
        }
        $this->log->info('Connection to Solr server ' . $this->$url . ' was successfully established.');
    }

    /**
     * Returns a Apache_Solr_Service object which encapsulates the communication
     * with the Solr server.
     *
     * @return Apache_Solr_Server
     * @throws Opus_Search_Index_Solr_Exception If no connection could be established.
     */
    private function getSolrServer($server) {
        $config = Zend_Registry::get('Zend_Config');
        // check if all config params exist and are not empty
        $errMsg = "Configuration parameter searchengine.%s.%s does not exist in config file.";
        foreach (array('host', 'port', 'app') as $param) {
            if (!isset($config->searchengine->$server->$param)) {
                $errMsg = sprintf($errMsg, $server, $param);
                $this->log->err($errMsg);
                throw new Opus_Search_Index_Solr_Exception($errMsg);
            }
        }
        $host = $this->checkForExistence($config, $server, 'host');
        $port = $this->checkForExistence($config, $server, 'port');
        $app = $this->checkForExistence($config, $server, 'app');
        $urlVarName = $server . '_server_url';
        $this->$urlVarName = "http://$host:$port/$app";

        try {
            return new Apache_Solr_Service($host, $port, $app);
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Connection to Solr server' . $this->$urlVarName . 'could not be established';
            $this->log->err($msg . ": " . $e->getMessage());
            throw new Opus_Search_Index_Solr_Exception($msg, null, $e);
        }
    }

    /**
     * @param Zend_Config $config Configuration from which to read from.
     * @param string $configParamName Name of the config parameter needed for output purposes.
     * @return string Returns the value of the given configuration parameter if
     * it exists in config file and is not empty.
     * @throws Opus_Search_Index_Solr_Exception If the given configuration parameter
     * is empty.
     */
    private function checkForExistence($config, $server, $param) {
        $paramValue = $config->searchengine->$server->$param;
        if (empty($paramValue)) {
            $msg = "Configuration parameter searchengine.$server.$param is empty.";
            $this->log->err($msg);
            throw new Opus_Search_Index_Solr_Exception($msg);
        }
        return trim($paramValue);
    }

    /**
     * Add a document to the index.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param Opus_Document $doc Model of the document that should be added to the index
     * @throws Opus_Search_Index_Solr_Exception If adding document to Solr index failed.
     * @return void
     */
    public function addDocumentToEntryIndex(Opus_Document $doc) {
        try {            
            // send xml directly to solr server instead of wrapping the document data
            // into an Apache_Solr_Document object offered by the solr php client library
            $this->sendSolrXmlToServer($this->getSolrXmlDocument($doc));
        }
        catch (Exception $e) {
            $msg = 'Error while adding document with id ' . $doc->getId();
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Index_Solr_Exception($msg, 0, $e);
        }
    }

    /**
     * Removes a document from the index.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param Opus_Document $doc Model of the document that should be removed to the index
     * @throws InvalidArgumentException If given document $doc is null.
     * @throws Opus_Search_Index_Solr_Exception If deleting document failed.
     * @return void
     */
    public function removeDocumentFromEntryIndex(Opus_Document $doc = null) {
        if (true !== isset($doc)) {
            throw new InvalidArgumentException("Document parameter must not be NULL.");
        }
        try {
            $this->index_server->deleteById($doc->getId());
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while deleting document with id ' . $doc->getId();
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Index_Solr_Exception($msg, 0, $e);
        }
    }

    /**
     * Returns an xml representation of the given document in the format that is
     * expected by Solr.
     *
     * @param Opus_Document $doc
     * @return DOMDocument
     */
    private function getSolrXmlDocument($doc) {
        // Set up filter and get XML representation of filtered document.
        $filter = new Opus_Model_Filter;
        $filter->setModel($doc);
        $modelXml = $filter->toXml(array(), new Opus_Model_Xml_Version1(), false);
        $this->attachFulltextToXml($modelXml, $doc->getFile(), $doc->getId());

        // Set up XSLT stylesheet
        $xslt = new DomDocument;
        $xslt->load(dirname(__FILE__) . '/solr.xslt');

        // Set up XSLT processor
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xslt);

        $solrXmlDocument = new DOMDocument();
        $solrXmlDocument->preserveWhiteSpace = false;        
        $solrXmlDocument->loadXML($proc->transformToXML($modelXml));

        $config = Zend_Registry::get('Zend_Config');
        if (isset($config->log->prepare->xml) && $config->log->prepare->xml) {
            $modelXml->formatOutput = true;
            $this->log->debug("\n" . $modelXml->saveXML());
            $solrXmlDocument->formatOutput = true;
            $this->log->debug("\n" . $solrXmlDocument->saveXML());
        }
        return $solrXmlDocument;
    }

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
            $docXml->appendChild($modelXml->createElement('Has_Fulltext', 'false'));
            return;
        }
        if (count($files) == 0) {
            $docXml->appendChild($modelXml->createElement('Has_Fulltext', 'false'));
            return;
        }
        $docXml->appendChild($modelXml->createElement('Has_Fulltext', 'true'));
        foreach ($files as $file) {
            try {
                $fulltext = $this->getFileContent($file);
                if (!empty($fulltext)) {
                    $element = $modelXml->createElement('Fulltext_Index');
                    $element->appendChild($modelXml->createTextNode($fulltext));
                    $docXml->appendChild($element);
                }
            }
            catch (Opus_Search_Index_Solr_Exception $e) {
                $this->log->debug('An error occurred while getting fulltext data for document with id ' . $docId . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * returns the extracted fulltext of the given file or an exception in
     * case of errors
     *
     * @param Opus_File $file
     * @throws Opus_Search_Index_Solr_Exception
     * @return extracted fulltext
     */    
    private function getFileContent($file) {
        $this->log->debug('extracting fulltext from ' . $file->getPath());
        if (!$file->exists()) {
            throw new Opus_Search_Index_Solr_Exception($file->getPath() . ' does not exist.');
        }
        if (!$this->hasSupportedMimeType($file)) {
            throw new Opus_Search_Index_Solr_Exception($file->getPath() . ' has MIME type ' . $file->getMimeType() . ' which is not supported');
        }
        $fulltext = '';
        try {
            $params = array( 'extractOnly' => 'true', 'extractFormat' => 'text' );            
            $response = $this->extract_server->extract($file->getPath(), $params);
            // TODO add mime type information
            $jsonResponse = Zend_Json_Decoder::decode($response->getRawResponse());
            if (array_key_exists('', $jsonResponse)) {                
                return trim($jsonResponse['']);
                // TODO evaluate additional data in json response
            }
        }
        catch (Exception $e) {
            throw new Opus_Search_Index_Solr_Exception('error while extracting fulltext from file ' . $file->getPath(), null, $e);
        }        
        return $fulltext;
    }

    /**
     *
     * @param Opus_File $file
     * @return boolean Returns true if fulltext extraction for the file's MIME type
     * is supported.
     */
    private function hasSupportedMimeType($file) {
        if (    $file->getMimeType() === 'text/html' ||
                $file->getMimeType() === 'text/plain' ||
                $file->getMimeType() === 'application/pdf' ||
                $file->getMimeType() === 'application/postscript' ||
                $file->getMimeType() === 'application/xhtml+xml' ||
                $file->getMimeType() === 'application/xml') {
         return true;
        }
        return false;
    }

    /**
     * Deletes all index documents.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param query
     * @throws Opus_Search_Index_Solr_Exception If deletion of all documents failed.
     * @return void
     */
    public function deleteAllDocs() {
        $this->deleteDocsByQuery("*:*");
    }

    /**
     * Deletes all index documents that match the given query $query.  The
     * changes are not visible and a subsequent call to commit is required, to
     * make the changes visible.
     *
     * @param query
     * @throws Opus_Search_Index_Solr_Exception If delete by query $query failed.
     * @return void
     */
    public function deleteDocsByQuery($query) {
        try {
            $this->index_server->deleteByQuery($query);
            $this->log->info('deleted all docs that match ' . $query);
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while deleting all documents that match query ' . $query;
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Index_Solr_Exception($msg, 0, $e);
        }
    }

    /**
     * Posts the given xml document to the Solr server without using the solr php client library.
     *
     * @param DOMDocument $solrXml
     * @return void
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
        $response = new Apache_Solr_Response(@file_get_contents($this->index_server_url . '/update', false, $stream));
        $this->log->debug('HTTP Status: ' . $response->getHttpStatus());
    }

    /**
     * Commits changes to the index
     *
     * @throws Opus_Search_Index_Solr_Exception If commit failed.
     * @return void
     */
    public function commit() {
        try {
            $this->index_server->commit();
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while committing changes';
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Index_Solr_Exception($msg, 0, $e);
        }
    }

    /**
     * Optimizes the index
     *
     * @throws Opus_Search_Index_Solr_Exception If index optimization failed.
     * @return void
     */
    public function optimize() {
        try {
            $this->index_server->optimize();
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while optimizing changes';
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Index_Solr_Exception($msg, 0, $e);
        }
    }
}