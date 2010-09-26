<?php
/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Worker class for indexing Opus documents.
 *
 * @category    Framework
 * @package     Opus_Job
 * @subpackage  Worker
 */
class Opus_Job_Worker_IndexOpusDocument
    implements Opus_Job_Worker_Interface {

    /**
     * Holds the index.
     *
     * @var Opus_Search_Index_Interface
     */
    private $_index = null;

    /**
     * Holds the job currently worked on.
     *
     * @var Opus_Job
     */
    private $_job = null;

    /**
     * Hold current logger instance.
     *
     * @var Zend_Log
     */
    private $_logger = null;
    
    
    /**
     * Holds file base path for locating attached documents.
     *
     * @var string
     */
    private $_basePath = '';

    /**
     *
     *
     * @param mixed $logger (Optional)
     * @return void
     */
    public function __construct($logger = null) {
        $this->setLogger($logger);        
    }

    /**
     * Return message label that is used to trigger worker process.
     *
     * @return string Message label.
     */
    public function getActivationLabel() {
        return 'opus-index-document';
    }

    /**
     * Set logging facility.
     *
     * @param Zend_Log $logger Logger instance.
     * @return void
     */
    public function setLogger($logger) {
        if (null === $logger) {
            $this->_logger = new Zend_Log(new Zend_Log_Writer_Null());
        } else if ($logger instanceof Zend_Log) {
            $this->_logger = $logger;
        } else {
            throw new IllegalArgumentException('Zend_Log instance expected.');
        }
    }
    
    /**
     * Set the search index to add documents to.
     *
     * @param Opus_Search_Index_Interface $index Index implementation.
     * @return void
     */
    public function setIndex(Opus_Search_Index_Interface $index) {
        $this->_index = $index;
    }
    
    /**
     * Set a file path pattern to help the indexer determine the path
     * to concrete files. 
     *
     * @param string $basePath Path pattern containing $documentId variable.
     * @return void
     */
    public function setFileBasePathPattern($basePath) {
        // TODO check if valid
        // TODO Replace stuff like this by a Resource Manager component
        $this->_basePath = $basePath;
    }

    /**
     * Load a document from database and optional file(s) and index them.
     *
     * @param Opus_Job $job Job description and attached data.
     * @return void
     */
    public function work(Opus_Job $job) {
        $this->_job = $job;
        $data = $job->getData();

        $documentId = (int) $data->documentId;

        if (null !== $this->_logger) {
            $this->_logger->info('Indexing document with ID: ' . $documentId . '.');
        }

        // create index document
        $document = new Opus_Document($documentId);
        $idxDocument = Opus_Search_Solr_Document_OpusDocument::loadOpusDocument($document);
        
        // add fulltext index information if document is 'published'
        if ('published' === $document->getServerState()) {
            $files = $document->getFile();
            $fulltext = array();
            foreach ($files as $file) {

                // skip files which are invisible on frontdoor
                if (false == $file->getFrontdoorVisible()) {
                    continue;
                }

                $filepath = str_replace('$documentId', $documentId, $this->_basePath);
                $filepath .= DIRECTORY_SEPARATOR . $file->getPathName();

                $mimeType = mime_content_type($filepath);

                if ($mimeType === 'application/pdf') {
                    $idxPdf = Opus_Search_Solr_Document_Pdf::loadPdf($filepath);
                    if (false === empty($idxPdf->body)) {
                        $fulltext = array_merge_recursive($fulltext, $idxPdf->body);
                    }
                }
            }

            if (false === empty($fulltext)) {
                $idxDocument->setField('fulltext', $fulltext);
            }
        }
        
        
        $this->_index->put($idxDocument);
        $this->_index->commit();
    }

}

