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
 * @package     Opus_Search_Util
 * @author      Sascha Szott <szott@zib.de>
 * @author      Michael Lang <lang@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */
class Opus_Search_Util_Indexer
{

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

    private $totalFileCount;

    private $errorFileCount;

    /**
     * Establishes a connection to a Solr server. Additionally, deletes all documents from index,
     * if $deleteAllDocs is set to true.
     *
     * @param boolean $deleteAllDocs Delete all docs.  Defaults to false.
     * @throws Opus_Search_InvalidConfigurationException If at least one
     * searchengine related parameter in configuration file is missing or empty.
     */
    public function __construct($deleteAllDocs = false) {
        $this->log = Zend_Registry::get('Zend_Log');

        $config = Zend_Registry::get('Zend_Config');
        // check if all config params exist and are not empty
        foreach(array('index', 'extract') as $server) {
            $errMsg = "Configuration parameter searchengine.%s.%s does not exist in config file.";
            foreach (array('host', 'port', 'app') as $param) {
                if (!isset($config->searchengine->$server->$param)) {
                    $errMsg = sprintf($errMsg, $server, $param);
                    $this->log->err($errMsg);
                    throw new Opus_Search_InvalidConfigurationException($errMsg);
                }
            }
            $host = $this->checkForExistence($config, $server, 'host');
            $port = $this->checkForExistence($config, $server, 'port');
            $app = $this->checkForExistence($config, $server, 'app');
            $urlVarName = $server . '_server_url';
            $this->$urlVarName = "http://$host:$port/$app";
        }

        if (true === $deleteAllDocs) {
            $this->deleteAllDocs();
            $this->commit();
        }
        $this->errorFileCount = 0;
        $this->totalFileCount = 0;
    }

    /**
     * Pings the given server. Throws an exception if it does not react.
     *
     * @param string $server Server that should be pinged against.
     * @throws Opus_Search_Exception If the given server does not react.
     */
    private function ping($server) {
        $url = $server . '_url';
        if (false === $this->$server->ping()) {
            $this->log->err('Connection to Solr server ' . $this->$url . ' could not be established.');
            throw new Opus_Search_Exception('Solr server ' . $this->$url . ' is not responding.');
        }
        $this->log->info('Connection to Solr server ' . $this->$url . ' was successfully established.');
    }

    /**
     * Returns a Apache_Solr_Service object which encapsulates the communication
     * with the Solr server.
     *
     * @return Apache_Solr_Service
     * @throws Opus_Search_Exception If no connection could be
     * established or Solr server does not react.
     */
    private function getSolrServer($server) {

        $serverVarName = $server.'_server';

        if(isset($this->$serverVarName)
        && $this->$serverVarName instanceof Apache_Solr_Service) {
            return $this->$serverVarName;
        }

        try {
            $config = Zend_Registry::get('Zend_Config');
            $this->$serverVarName = new Apache_Solr_Service(
                    $config->searchengine->$server->host,
                    $config->searchengine->$server->port,
                    $config->searchengine->$server->app);
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Connection to Solr server' . $this->{$server . '_server_url'} . 'could not be established';
            $this->log->err($msg . ": " . $e->getMessage());
            throw new Opus_Search_Exception($msg, null, $e);
        }
        $this->ping($serverVarName);
        return $this->$serverVarName;
    }

    /**
     * @param Zend_Config $config Configuration from which to read from.
     * @param string $configParamName Name of the config parameter needed for output purposes.
     * @return string Returns the value of the given configuration parameter if
     * it exists in config file and is not empty.
     * @throws Opus_Search_InvalidConfigurationException If the given configuration parameter
     * is empty.
     */
    private function checkForExistence($config, $server, $param) {
        $paramValue = $config->searchengine->$server->$param;
        if (empty($paramValue)) {
            $msg = "Configuration parameter searchengine.$server.$param is empty.";
            $this->log->err($msg);
            throw new Opus_Search_InvalidConfigurationException($msg);
        }
        return trim($paramValue);
    }

    /**
     * Add a document to the index.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param Opus_Document $doc Model of the document that should be added to the index
     * @throws InvalidArgumentException If given document $doc is null.
     * @throws Opus_Search_Exception If adding document to Solr index failed.
     * @return $this (fluent interface)
     */
    public function addDocumentToEntryIndex(Opus_Document $doc) {
        if (is_null($doc)) {
            throw new InvalidArgumentException("Document parameter must not be NULL.");
        }
        try {
            // send xml directly to solr server instead of wrapping the document data
            // into an Apache_Solr_Document object provided by the solr php client library
            $this->sendSolrXmlToServer($this->getSolrXmlDocument($doc));
        }
        catch (Exception $e) {
            $msg = 'Error while adding document with id ' . $doc->getId();
            $this->log->err("$msg : " . get_class($e) .': '. $e->getMessage());
            throw new Opus_Search_Exception($msg, 0, $e);
        }
        return $this;
    }

    /**
     * Removes a document from the index.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param Opus_Document $doc Model of the document that should be removed to the index
     * @throws InvalidArgumentException If given document $doc is null.
     * @throws Opus_Search_Exception If deleting document failed.
     * @return $this (fluent interface)
     *
     * @see removeDocumentFromEntryIndexById()
     */
    public function removeDocumentFromEntryIndex(Opus_Document $doc = null) {
        if (is_null($doc)) {
            throw new InvalidArgumentException("Document parameter must not be NULL.");
        }
        $this->removeDocumentFromEntryIndexById($doc->getId());
        return $this;
    }

    /**
     * Removes a document from the index.  The changes are not visible and a
     * subsequent call to commit is required, to make the changes visible.
     *
     * @param int $documentId Id document that should be removed to the index
     * @throws InvalidArgumentException If given document $documentId is null.
     * @throws Opus_Search_Exception If deleting document failed.
     * @return $this (fluent interface)
     */
    public function removeDocumentFromEntryIndexById($documentId = null) {
        if (true !== isset($documentId)) {
            throw new InvalidArgumentException("DocumentId parameter must not be NULL.");
        }
        try {
            $this->getSolrServer('index')->deleteById($documentId);
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while deleting document with id ' . $documentId;
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Exception($msg, 0, $e);
        }
        return $this;
    }

    /**
     * Returns an xml representation of the given document in the format that is
     * expected by Solr.
     *
     * @param Opus_Document $doc
     * @return DOMDocument
     */
    private function getSolrXmlDocument(Opus_Document $doc) {
        // Set up caching xml-model and get XML representation of document.
        $caching_xml_model = new Opus_Model_Xml;
        $caching_xml_model->setModel($doc);
        $caching_xml_model->excludeEmptyFields();
        $caching_xml_model->setStrategy(new Opus_Model_Xml_Version1);
        $cache = new Opus_Model_Xml_Cache($doc->hasPlugin('Opus_Document_Plugin_Index'));
        $caching_xml_model->setXmlCache($cache);

        $config = Zend_Registry::get('Zend_Config');

        $modelXml = $caching_xml_model->getDomDocument();

        // extract fulltext from file and append it to the generated xml.
        $this->attachFulltextToXml($modelXml, $doc->getFile(), $doc->getId());

        // Set up XSLT stylesheet
        $xslt = new DomDocument;
        if ( isset( $config->searchengine->solr->xsltfile ) ) {
            $xsltFilePath = $config->searchengine->solr->xsltfile;
            if ( !file_exists( $xsltFilePath ) ) {
                throw new Application_Exception( 'Solr XSLT file not found.' );
            }
            $xslt->load( $xsltFilePath );
        } else {
            throw new Application_Exception( 'Missing configuration of Solr XSLT file used to prepare Opus documents for indexing.' );
        }

        // Set up XSLT processor
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xslt);

        $solrXmlDocument = new DOMDocument();
        $solrXmlDocument->preserveWhiteSpace = false;
        $solrXmlDocument->loadXML($proc->transformToXML($modelXml));

        if (isset($config->log->prepare->xml) && $config->log->prepare->xml) {
            $modelXml->formatOutput = true;
            $this->log->debug("input xml\n" . $modelXml->saveXML());
            $solrXmlDocument->formatOutput = true;
            $this->log->debug("transformed solr xml\n" . $solrXmlDocument->saveXML());
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
        $docXml = $modelXml->getElementsByTagName('Opus_Document')->item(0);
        if (is_null($docXml)) {
            $this->log->warn('An error occurred while attaching fulltext information to the xml for document with id ' . $docId);
            return;
        }
        // only consider files which are visible in frontdoor
        $files = array_filter($files, function ($file) { return $file->getVisibleInFrontdoor() === '1'; });

        if (count($files) == 0) {
            $docXml->appendChild($modelXml->createElement('Has_Fulltext', 'false'));
            return;
        }
        $docXml->appendChild($modelXml->createElement('Has_Fulltext', 'true'));
        foreach ($files as $file) {
            $fulltext = '';
            try {
                $this->totalFileCount++;
                $fulltext = trim(iconv("UTF-8","UTF-8//IGNORE", $this->getFileContent($file)));
            }
            catch (Opus_Search_Exception $e) {
                $this->errorFileCount++;
                $this->log->err('An error occurred while getting fulltext data for document with id ' . $docId . ': ' . $e->getMessage());
            }

            if ($fulltext != '') {
                $element = $modelXml->createElement('Fulltext_Index');
                $element->appendChild($modelXml->createCDATASection($fulltext));
                $docXml->appendChild($element);

                $element = $modelXml->createElement('Fulltext_ID_Success');
                $element->appendChild($modelXml->createTextNode($this->getFulltextHash($file)));
                $docXml->appendChild($element);
            }
            else {
                $element = $modelXml->createElement('Fulltext_ID_Failure');
                $element->appendChild($modelXml->createTextNode($this->getFulltextHash($file)));
                $docXml->appendChild($element);
            }
        }
    }

    /**
     *
     * @param Opus_File $file
     * @return string
     */
    private function getFulltextHash($file) {
        $hash = '';
        try {
            $hash = $file->getRealHash('md5');
        }
        catch (Exception $e) {
            $this->log->err('could not compute MD5 hash for ' . $file->getPath() . ' : ' . $e);
        }
        return $file->getId() . ":" . $hash;
    }

    /**
     * returns the extracted fulltext of the given file or an exception in
     * case of errors
     *
     * @param Opus_File $file
     * @throws Opus_Search_Exception
     * @return extracted fulltext
     */
    private function getFileContent(Opus_File $file) {
        $this->log->debug('extracting fulltext from ' . $file->getPath());
        if (!$file->exists()) {
            $this->log->err($file->getPath() . ' does not exist.');
            throw new Opus_Search_Exception($file->getPath() . ' does not exist.');
        }
        if (!$file->isReadable()) {
            $this->log->err($file->getPath() . ' is not readable.');
            throw new Opus_Search_Exception($file->getPath() . ' is not readable.');
        }
        if (!$this->hasSupportedMimeType($file)) {
            $this->log->err($file->getPath() . ' has MIME type ' . $file->getMimeType() . ' which is not supported');
            throw new Opus_Search_Exception($file->getPath() . ' has MIME type ' . $file->getMimeType() . ' which is not supported');
        }

        // Check for cached ...
        $fulltext = $this->getCachedFileContent($file);
        if ($fulltext !== false and is_string($fulltext)) {
            $this->log->info('Found cached fulltext for file ' . $file->getPath());
            return $fulltext;
        }

        $params = array( 'extractOnly' => 'true', 'extractFormat' => 'text' );
        try {
            $response = $this->getSolrServer('extract')->extract($file->getPath(), $params);
            // TODO add mime type information
            $jsonResponse = Zend_Json_Decoder::decode($response->getRawResponse());
            if (array_key_exists('', $jsonResponse)) {
                $fulltext = trim($jsonResponse['']);

                $this->setCachedFileContent($file, $fulltext);
                return $fulltext;
                // TODO evaluate additional data in json response
            }
        }
        catch (Exception $e) {
            $this->log->err('error while extracting fulltext from file ' . $file->getPath());
            throw new Opus_Search_Exception('error while extracting fulltext from file ' . $file->getPath(), null, $e);
        }
        return '';
    }


    /**
     * Construct name of fulltext cache file for given Opus_File object.
     *
     * @param Opus_File $file
     * @return string Name of full absolute name of fulltext cache file or null if file name could not be computed.
     */
    private function getCachedFileName(Opus_File $file) {
        $config = Zend_Registry::get('Zend_Config');
        try {
            $hash = $file->getRealHash('md5') . "-" . $file->getRealHash('sha256');
        }
        catch (Exception $e) {
            $this->log->err(__CLASS__ . '::' . __METHOD__ . ' : could not compute hash values for ' . $file->getPath() . " : $e");
            return null;
        }
        $cache_path = realpath($config->workspacePath . "/cache/");
        $cache_filename = "solr_cache---$hash.txt";
        return $cache_path . DIRECTORY_SEPARATOR . $cache_filename;
    }

    /**
     * Cache extracted fulltext.  Do not create file if given fulltext is empty.
     *
     * @param Opus_File $file
     * @param string $fulltext
     * @return void
     */
    private function setCachedFileContent(Opus_File $file, $fulltext) {
        if (empty($fulltext)) {
            return;
        }

        $config = Zend_Registry::get('Zend_Config');
        $cache_file = $this->getCachedFileName($file);

        if (is_null($cache_file)) {
            return;
        }

        // Create tempfile with unique name.  This has to be done, to prevent
        // that two processes are writing their output to the same file.
        $tmp_path = realpath($config->workspacePath . "/tmp/");
        $tmp_file = tempnam($tmp_path, 'solr_tmp---');

        $temp_fh = fopen($tmp_file, 'w');
        if ($temp_fh == false) {
            $this->log->info('Failed writing fulltext temp file ' . $tmp_file);
            return;
        }

        fwrite($temp_fh, $fulltext);
        fclose($temp_fh);

        // Move temp file to final destination.
        if (true !== rename($tmp_file, $cache_file)) {
            $this->log->info('Failed renaming temp file to fulltext cache file ' . $cache_file);
        }

        return;
    }

    /**
     * Try to load cached fulltext for given Opus_File object.
     *
     * @param Opus_File $file
     * @return false|string Fulltext if loaded successfully, false otherwise.
     */
    private function getCachedFileContent(Opus_File $file) {
        $cache_file = $this->getCachedFileName($file);

        if (!is_null($cache_file) && is_readable($cache_file)) {
            $max_cache_file_size = 1024*1024*16;
            if (filesize($cache_file) > $max_cache_file_size) {
                $this->log->info('Skipped reading fulltext HUGE cache file ' . $cache_file);
                return;
            }

            $cache_fh = fopen($cache_file, 'r');
            if ($cache_fh == false) {
                $this->log->info('Failed reading fulltext cache file ' . $cache_file);
            }

            $fulltext_buffer = '';
            while (!feof($cache_fh)) {
               $fulltext_buffer .= fread($cache_fh, 1024*1024);
            }

            fclose($cache_fh);
            return trim($fulltext_buffer);
        }

        return false;
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
     * @throws Opus_Search_Exception If deletion of all documents failed.
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
     * @throws Opus_Search_Exception If delete by query $query failed.
     * @return void
     */
    public function deleteDocsByQuery($query) {
        try {
            $this->getSolrServer('index')->deleteByQuery($query);
            $this->log->info('deleted all docs that match ' . $query);
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while deleting all documents that match query ' . $query;
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Exception($msg, 0, $e);
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
        $this->log->debug('Solr Response Status: ' . $response->getHttpStatus());
        if (!$response->getRawResponse()) {
            throw new Opus_Search_Exception("Solr Server {$this->index_server_url} not responding.");
        }
    }

    /**
     * Commits changes to the index
     *
     * @throws Opus_Search_Exception If commit failed.
     * @return void
     */
    public function commit() {
        try {
            $this->getSolrServer('index')->commit();
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while committing changes';
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Exception($msg, 0, $e);
        }
    }

    /**
     * Optimizes the index
     *
     * @throws Opus_Search_Exception If index optimization failed.
     * @return void
     */
    public function optimize() {
        try {
            $this->getSolrServer('index')->optimize();
        }
        catch (Apache_Solr_Exception $e) {
            $msg = 'Error while performing index optimization';
            $this->log->err("$msg : " . $e->getMessage());
            throw new Opus_Search_Exception($msg, 0, $e);
        }
    }

    public function getErrorFileCount() {
        return $this->errorFileCount;
    }

    public function getTotalFileCount() {
        return $this->totalFileCount;
    }

}
