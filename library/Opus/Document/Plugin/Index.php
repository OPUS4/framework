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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Plugin for updating the solr index triggered by document changes.
 *
 * @category    Framework
 * @package     Opus_Document_Plugin
 * @uses        Opus_Model_Plugin_Abstract
 */
class Opus_Document_Plugin_Index extends Opus_Model_Plugin_Abstract {

    private $logger;
    private $config;

    public function __construct($config = null) {
        $this->config = is_null($config) ? Zend_Registry::get('Zend_Config') : $config;
    }

    public function setLogger(Zend_Log $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Post-store hook will be called right after the document has been stored
     * to the database.  If set to synchronous, update index.  Otherwise add
     * job to worker-queue.
     *
     * If document state is set to something != published, remove document.
     *
     * @see {Opus_Model_Plugin_Interface::postStore}
     */
    public function postStore(Opus_Model_AbstractDb $model) {

        // only index Opus_Document instances
        if (false === ($model instanceof Opus_Document)) {
            return;
        }

        // Skip indexing if document has not been published yet.  First we need
        // to reload the document, just to make sure the object is new,
        // unmodified and clean...
        // TODO: Write unit test.
        $model = new Opus_Document($model->getId());
        if ($model->getServerState() !== 'published') {
            if ($model->getServerState() !== 'temporary') {
                $this->removeDocumentFromIndex($model->getId());
            }
            return;
        }

        $this->addDocumentToIndex($model);
    }

    /**
     * Post-delete-hook for document class: Remove document from index.
     *
     * @see {Opus_Model_Plugin_Interface::postDelete}
     */
    public function postDelete($modelId) {

        if (null === $modelId) {
            return;
        }

        $this->removeDocumentFromIndex($modelId);
        return;
    }

    /**
     * Helper method to remove document from index.
     *
     * @param integer $documentId
     */
    private function removeDocumentFromIndex($documentId) {

        if (isset($this->config->runjobs->asynchronous) && $this->config->runjobs->asynchronous) {
            
            if (null !== $this->logger) {
                $message = 'Adding remove-index job for document ' . $documentId . '.';
                $this->logger->debug(__METHOD__ . ': ' . $message);
            }

            $job = new Opus_Job();
            $job->setLabel('opus-index-document');
            $job->setData(array(
                'documentId' => $documentId,
                'task' => 'remove'
            ));

            // skip creating job if equal job already exists
            if (true === $job->isUniqueInQueue()) {
                $job->store();
            } else if (null !== $this->logger) {
                $message = 'remove-index job for document ' . $documentId . ' already exists!';
                $this->logger->debug(__METHOD__ . ': ' . $message);
            }

        } else {
            if (null !== $this->logger) {
                $message = 'Removing document ' . $documentId . ' from index.';
                $this->logger->debug(__METHOD__ . ': ' . $message);
            }
            try {
                $indexer = new Opus_SolrSearch_Index_Indexer;
                $indexer->removeDocumentFromEntryIndexById($documentId);
                $indexer->commit();
            }
            catch (Opus_SolrSearch_Index_Exception $e) {
                if (null !== $this->logger) {
                    $message = 'Removing document-id ' . $documentId . ' from index failed: ';
                    $message .= $e->getMessage();
                    $this->logger->debug(__METHOD__ . ': ' . $message);
                }
            }
            
        }

    }

    /**
     * Helper method to add document to index.
     *
     * @param Opus_Document $document
     * @return void
     */
    private function addDocumentToIndex(Opus_Document $document) {

        // create job if asynchronous is set
        if (isset($this->config->runjobs->asynchronous) && $this->config->runjobs->asynchronous) {
            if (null !== $this->logger) {
                $message = 'Adding index job for document ' . $document->getId() . '.';
                $this->logger->debug(__METHOD__ . ': ' . $message);
            }

            $job = new Opus_Job();
            $job->setLabel('opus-index-document');
            $job->setData(array(
                'documentId' => $document->getId(),
                'task' => 'index'
            ));

            // skip creating job if equal job already exists
            if (true === $job->isUniqueInQueue()) {
                $job->store();
            } else {
            if (null !== $this->logger) {
                $message = 'Indexing job for document ' . $document->getId() . ' already exists!';
                $this->logger->debug(__METHOD__ . ': ' . $message);
            }
            }
        } else {
            if (null !== $this->logger) {
                $message = 'Index document ' . $document->getId() . '.';
                $this->logger->debug(__METHOD__ . ': ' . $message);
            }

            try {
                $indexer = new Opus_SolrSearch_Index_Indexer;
                $indexer->addDocumentToEntryIndex($document);
                $indexer->commit();
            }
            catch (Opus_SolrSearch_Index_Exception $e) {
                if (null !== $this->logger) {
                    $message = 'Indexing document ' . $document->getId() . ' failed: ';
                    $message .= $e->getMessage();
                    $this->logger->debug(__METHOD__ . ': ' . $message);
                }
            }
            catch (InvalidArgumentException $e) {
                if (!is_null($this->logger)) {
                    $this->logger->warn(__METHOD__ . ': ' . $e->getMessage());
                }
            }
        }
    }
}

