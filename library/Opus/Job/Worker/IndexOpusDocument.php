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
 * @package     Opus_Job
 * @subpackage  Worker
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2009-2010 Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Worker class for indexing Opus documents.
 *
 */
class Opus_Job_Worker_IndexOpusDocument implements Opus_Job_Worker_Interface {

    const LABEL = 'opus-index-document';

    /**
     * Holds the index.
     *
     * @var Opus_SolrSearch_Index_Indexer
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
        return self::LABEL;
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
     * @param Opus_SolrSearch_Index_Indexer $index Index implementation.
     * @return void
     */
    public function setIndex(Opus_SolrSearch_Index_Indexer $index) {
        $this->_index = $index;
    }

    /**
     * Get the search index to add documents to.
     * If no index instance is set via setIndex(),
     * this method returns a new instance.
     *
     * @return Opus_SolrSearch_Index_Indexer $index
     */
    private function getIndex() {
        if(is_null($this->_index))
            $this->_index = new Opus_SolrSearch_Index_Indexer();
        return $this->_index;
    }

    /**
     * Load a document from database and optional file(s) and index them,
     * or remove document from index (depending on job)
     *
     * @param Opus_Job $job Job description and attached data.
     * @return void
     */
    public function work(Opus_Job $job) {

        // make sure we have the right job
        if ($job->getLabel() != $this->getActivationLabel()) {
            throw new Opus_Job_Worker_InvalidJobException($job->getLabel() . " is not a suitable job for this worker.");
        }

        $this->_job = $job;
        $data = $job->getData();

        if (!(is_object($data)
                && isset($data->documentId)
                && isset($data->task)
                ))
            throw new Opus_Job_Worker_InvalidJobException("Incomplete or missing data.");

        if (null !== $this->_logger) {
            $this->_logger->info('Indexing document with ID: ' . $data->documentId . '.');
        }

        // create index document or remove index, depending on task
        if ($data->task === 'index') {
            $document = new Opus_Document($data->documentId);
            $this->getIndex()
                    ->addDocumentToEntryIndex($document)
                    ->commit();
        } else if ($data->task === 'remove') {
            $this->getIndex()
                    ->removeDocumentFromEntryIndexById($data->documentId)
                    ->commit();
        } else {
            throw new Opus_Job_Worker_InvalidJobException("unknown task '{$data->task}'.");
        }
    }

}

