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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Plugin for updating the solr index triggered by document changes.
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Document_Plugin_Index extends Opus_Model_Plugin_Abstract {

    private $synchronous = true;

    /**
     * Post-store hook will be called right after the document has been stored
     * to the database.  If set to synchronous, update index.  Otherwise add
     * job to worker-queue.
     *
     * @see {Opus_Model_Plugin_Interface::postStore}
     */
    public function postStore(Opus_Model_AbstractDb $model) {

        // only index Opus_Document instances
        if (false === ($model instanceof Opus_Document)) {
            return;
        }

        // create-job flag to determine, if we need to enqueue an index-job.
        $create_job = true;

        // if synchronous is set, try to index document
        if ($this->synchronous) {
            $logger = Zend_Registry::get('Zend_Log');
            if (null !== $logger) {
                $message = 'Indexing document ' . $model->getId() . '.';
                $logger->debug(__METHOD__ . ': ' . $message);
            }

            try {
                $indexer = new Opus_Search_Index_Solr_Indexer;
                $indexer->addDocumentToEntryIndex($model);
                $indexer->commit();
                $create_job = false;
            }
            catch (Opus_Search_Index_Solr_Exception $e) {
                $message = 'Indexing document ' . $model->getId() . ' failed: ';
                $message .= $e->getMessage();
                $logger->debug(__METHOD__ . ': ' . $message);
            }
        }

        // enqueue job, if synchronous update disabled *or* failed
        if ($create_job) {
            $logger = Zend_Registry::get('Zend_Log');
            if (null !== $logger) {
                $message = 'Adding index job for document ' . $model->getId() . '.';
                $logger->debug(__METHOD__ . ': ' . $message);
            }

            $job = new Opus_Job();
            $job->setLabel('opus-index-document');
            $job->setData(array(
                'documentId' => $model->getId(),
            ));

            // skip creating job if equal job already exists
            if (true === $job->isUniqueInQueue()) {
                $job->store();
            }
        }
    }

    /**
     * Remove document from index.
     *
     * @see {Opus_Model_Plugin_Interface::postDelete}
     */
    public function postDelete($modelId) {

        if (null === $modelId) {
            return;
        }

        $logger = Zend_Registry::get('Zend_Log');
        if (null !== $logger) {
            $message = 'Removing document ' . $modelId . ' from index.';
            $logger->debug(__METHOD__ . ': ' . $message);
        }

        $indexer = new Opus_Search_Index_Solr_Indexer;
        $indexer->removeDocumentFromEntryIndexById($modelId);
        $indexer->commit();
    }

}

