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
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Julian Heise (heise@zib.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @copyright   Copyright (c) 2010-2012, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Plugin for generating identifier urn.
 *
 * @category    Framework
 * @package     Opus_Document_Plugin
 * @uses        Opus_Model_Plugin_Abstract
 */
class Opus_Document_Plugin_IdentifierUrn extends Opus_Model_Plugin_Abstract {

    /**
     * Generates a new URN for any document that has no URN assigned yet.
     * URN's are generated for Opus_Document instances only.
     */
    public function postStoreInternal(Opus_Model_AbstractDb $model) {

        if(!($model instanceof Opus_Document))
            return;

        if ($model->getServerState() !== 'published') {
            return;
        }

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

        if($this->urnAlreadyPresent($model)) {
            $log->debug('Document #' . $id . ' already has a URN. Skipping automatic generation.');
            return;
        }

        if (!$this->allowUrnOnThisDocument($model)) {
            $log->debug('Document #' . $id . ' has no oai-visible files. Skipping automatic URN generation.');
            return;
        }

        $log->debug('Generating URN for document ' . $id);

        $nid = $config->urn->nid;
        $nss = $config->urn->nss;

        $urn = new Opus_Identifier_Urn($nid, $nss);
        $urn_value = $urn->getUrn($id);
        $urn_model = new Opus_Identifier();
        $urn_model->setValue($urn_value);
        $urn_model->setType('urn');
        $model->addIdentifier($urn_model);
        $model->addIdentifierUrn($urn_model);
    }

    public function urnAlreadyPresent($document) {
        $identifierUrns = $document->getIdentifierUrn();
        if(count($identifierUrns) > 0) {
            return true;
        }

        $identifiers = $document->getIdentifier();
        foreach ($identifiers AS $identifier) {
            if ($identifier->getType() === 'urn') {
                return true;
            }
        }

        return false;
    }

    public function allowUrnOnThisDocument($document) {
        $files = array_filter($document->getFile(),
            function ($f) { return $f->getVisibleInOai() == 1; });
        return count($files) > 0;
    }
}

