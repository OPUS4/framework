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
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de), Julian Heise (heise@zib.de)
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: IdentifierUrn.php 5765 2010-06-07 14:15:00Z claussni $
 */

/**
 * Plugin for generating identifier urn.
 *
 * @category    Framework
 * @package     Opus
 * @uses        Opus_Model_Abstract
 */
class Opus_Document_Plugin_IdentifierUrn extends Opus_Model_Plugin_Abstract {

    /**
     * Generates a new URN for any document that has no URN assigned yet.
     * URN's are generated for Opus_Document instances only.
     */
    public function postStoreInternal(Opus_Model_AbstractDb $model) {

        if(!($model instanceof Opus_Document))
            return;

        $config = Zend_Registry::get('Zend_Config');
        $log = Zend_Registry::get('Zend_Log');
        $id = $model->getId();

        $log->debug('IdentifierUrn postStoreInternal for ' . $id);

        if(!isset($config->urn->autoCreate) or $config->urn->autoCreate != '1') {
            $log->debug('URN auto creation is not configured. skipping...');
            return;
        }

        if(!isset($config->urn->nid) || !isset($config->urn->nss)) {
            throw new Opus_Document_Exception('URN data is not present in config. Aborting...');
        }

        $log->debug('config.ini is set to support urn auto generation');

        $identifierUrns = $model->getIdentifierUrn();
        if(count($identifierUrns) > 0) {
            $log->debug('Document #' . $id . ' already has a URN. Skipping automatic generation.');
            return;
        }

        $log->debug('Generating URN for document ' . $id);

        $nid = $config->urn->nid;
        $nss = $config->urn->nss;

        $urn = new Opus_Identifier_Urn($nid, $nss);
        $urn_value = $urn->getUrn($id);
        $urn_model = new Opus_Identifier();
        $urn_model->setValue($urn_value);
        $model->addIdentifierUrn($urn_model);
    }
}

