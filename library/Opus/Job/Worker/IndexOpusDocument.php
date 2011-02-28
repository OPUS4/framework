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
 * @category    TODO
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
    public function setIndex(Opus_Search_Index_Solr_Indexer $index) {
        $this->_index = $index;
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
        $this->_index->addDocumentToEntryIndex($document);
        $this->_index->commit();
    }

}

