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
 * Plugin for generating sequence numbers on published documents.
 *
 * @category    Framework
 * @package     Opus_Document_Plugin
 * @uses        Opus_Model_Plugin_Abstract
 */
class Opus_Document_Plugin_SequenceNumber extends Opus_Model_Plugin_Abstract {

    /**
     * @see {Opus_Model_Plugin_Interface::postStore}
     */
    public function postStoreInternal(Opus_Model_AbstractDb $model) {

        $log = Zend_Registry::get('Zend_Log');
        $log->debug('Opus_Document_Plugin_SequenceNumber::postStore() with id ' . $model->getId());

        if (!($model instanceof Opus_Document)) {
            $message = 'Model is not an Opus_Document. Aborting...';
            $log->err($message);
            throw new Opus_Document_Exception($message);
        }

        if ($model->getServerState() !== 'published') {
            $message = 'Skip Opus_Documents not in ServerState *published* ...';
            $log->info($message);
            return;
        }

        $config = Zend_Registry::get('Zend_Config');
        if(!isset($config, $config->sequence->identifier_type)) {
            $log->debug('Sequence auto creation is not configured. skipping...');
            return;
        }
        $sequence_type = trim($config->sequence->identifier_type);

        $sequence_ids = array();
        foreach ($model->getIdentifier() AS $id) {
            if ($id->getType() === $sequence_type) {
                $sequence_ids[] = trim($id->getValue());
            }
        }
        
        if (count($sequence_ids) > 0) {
            $message = "Sequence IDs for type '$sequence_type' already exists: "
                . implode(",", $sequence_ids);
            $log->debug($message);
            return;
        }
        
        // Create and initialize new sequence number...
        $next_sequence_number = $this->_fetchNextSequenceNumber($sequence_type);
        
        $model->addIdentifier()
            ->setType($sequence_type)
            ->setValue($next_sequence_number);
        
        return;
    }

    /**
     * Small helper method to fetch next sequence number from database.
     */
    protected function _fetchNextSequenceNumber($sequence_type) {
        $id_table = Opus_Db_TableGateway::getInstance('Opus_Db_DocumentIdentifiers');
        $select = $id_table->select()->from($id_table, '')
                ->columns(new Zend_Db_Expr('MAX(CAST(value AS SIGNED))'))
                ->where("type = ?", $sequence_type)
                ->where("value REGEXP '^[[:digit:]]+$'");
        $last_sequence_id = (int) $id_table->getAdapter()->fetchOne($select);

        if (is_null($last_sequence_id) or $last_sequence_id <= 0) {
            return 1;
        }
        
        return $last_sequence_id + 1;
    }
    
}

