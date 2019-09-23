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
 * @package     Opus_Document_Plugin
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Plugin for generating identifiers of type DOI.
 *
 */
class Opus_Document_Plugin_IdentifierDoi extends Opus_Model_Plugin_Abstract implements \Opus\Model\Plugin\ServerStateChangeListener
{

    // was muss hier alles ausgewertet werden:
    // automatische Generierung einer DOI für das vorliegende Dokument, wenn
    // 1. noch keine DOI vorhanden
    // 2. Enrichment opus.doi.autoCreate wurde gesetzt

    // außerdem automatische Registrierung der DOI (Aufruf MDS-Webservice von DataCite)
    // wenn DOI vorhanden und die Konfigurationseinstellung doi.registerAtPublish ist auf true/1 gesetzt


    // laut Spezifikation: jedes OPUS-Dokument kann maximal eine zugeordnete DOI haben
    // diese DOI ist entweder lokal oder extern
    // im Rahmen der automatischen DOI-Registrierung werden nur lokale DOIs betrachtet
    public function postStoreInternal(Opus_Model_AbstractDb $model)
    {
        $log = Zend_Registry::get('Zend_Log');

        if (! ($model instanceof Opus_Document)) {
            $log->err(__CLASS__ . ' found unexpected model class ' . get_class($model));
            return;
        }

        $serverState = $model->getServerState();
        $log->debug(__CLASS__ . ' postStoreInternal for ' . $model->getDisplayName() . ' and target state ' . $serverState);

        if ($serverState !== 'published') {
            $log->debug(__CLASS__ . ' postStoreInternal: nothing to do for document with server state ' . $serverState);
            return;
        }

        $this->handlePublishEvent($model, $log);
    }

    public function postDelete($modelId)
    {
        // check if database contains document with given id
        $doc = null;
        try {
            $doc = new Opus_Document($modelId);
        } catch (Opus_Model_NotFoundException $e) {
            // ignore silently and exit method since we do not need to perform any action
            return;
        }

        if ($doc != null && $doc->getServerState() === 'deleted') {
            $this->handleDeleteEvent($doc);
        }
    }

    private function handleDeleteEvent($document)
    {
        // Metadatensatz für DOI auf den Status "inactive" setzen
        $doiManager = new Opus_Doi_DoiManager();
        $doiManager->deleteMetadataForDoi($document);
    }

    private function handlePublishEvent($document, $log)
    {
        // prüfe zuerst, ob das Dokument das Enrichment opus.doi.autoCreate besitzt
        // in diesem Fall wird nun eine DOI gemäß der Konfigurationseinstellungen generiert
        $generateDoi = null;
        $enrichment = $document->getEnrichment('opus.doi.autoCreate');
        if (! is_null($enrichment)) {
            $enrichmentValue = $enrichment->getValue();
            $generateDoi = ($enrichmentValue == 'true');
            $log->debug('found enrichment opus.doi.autoCreate with value ' . $enrichmentValue);
        }

        $config = Zend_Registry::get('Zend_Config');

        if (is_null($generateDoi)) {
            // Enrichment opus.doi.autoCreate wurde nicht gefunden - verwende Standardwert für die DOI-Erzeugung aus Konfiguration
            $generateDoi = (isset($config->doi->autoCreate) && filter_var($config->doi->autoCreate, FILTER_VALIDATE_BOOLEAN));
        }

        // prüfe, ob bereits eine DOI mit dem Dokument verknüpft ist
        if (! empty($document->getIdentifierDoi())) {
            $log->debug('could not assign more than one DOI to document ' . $document->getId());
        } else {
            // $generateDoi kann hier nicht mehr null sein: aktueller Wert entscheidet, ob neue DOI generiert wird
            if ($generateDoi) {
                try {
                    $this->addDoi($document, $log);
                } catch (Exception $e) {
                    $log->err('could not generate local DOI for document ' . $document->getId() . ' - abort DOI registration procedure');
                    return;
                }
            }
        }

        // prüfe, ob DOI bei DataCite registriert werden soll -> wenn ja, dann Versuch der synchronen Registrierung
        $this->registerDoi($document, $log, $config);
    }

    /**
     * Fügt zum Dokument $model eine DOI hinzu, sofern noch keine existiert und die Konfiguration
     * entsprechend gesetzt ist.
     *
     * @param $model Opus_Document zu dem die DOI hinzugefügt werden soll
     */
    private function addDoi($model, $log)
    {

        try {
            $doiManager = new Opus_Doi_DoiManager();
            $doiValue = $doiManager->generateNewDoi($model);
        } catch (Opus_Doi_DoiException $e) {
            $message = 'could not generate DOI value for document ' . $model->getId() . ': ' . $e->getMessage();
            $log->err($message);
            throw new Exception($message);
        }

        $doi = new Opus_Identifier();
        $doi->setType('doi');
        $doi->setValue($doiValue);

        $identifiers = $model->getIdentifier();
        if (is_null($identifiers)) {
            $identifiers = [];
        }
        $identifiers[] = $doi;
        $model->setIdentifier($identifiers);

        $log->debug('DOI ' . $doiValue . ' was generated for document ' . $model->getId());
    }

    /**
     * Registriert die mit dem Dokument verknüpfte DOI bei DataCite unter Verwendung des DoiManagers
     *
     * @param $model
     */
    private function registerDoi($model, $log, $config)
    {

        // prüfe ob Konfigurationseinstellung eine Registrierung vorgibt
        if (! isset($config->doi->registerAtPublish) || ! (filter_var($config->doi->registerAtPublish, FILTER_VALIDATE_BOOLEAN))) {
            $log->debug('registration of DOIs at publish time is disabled in configuration');
            return;
        }

        // führe die Registrierung durch
        $log->info('start registration of DOI for document ' . $model->getId());

        try {
            $doiManager = new Opus_Doi_DoiManager();
            $registeredDoi = $doiManager->register($model);
            if (is_null($registeredDoi)) {
                $log->err('could not apply DOI registration on document ' . $model->getId());
            }
        } catch (Opus_Doi_RegistrationException $e) {
            $log->err('unexpected error in registration of DOI ' . $e->getDoi() . ' of document ' . $model->getId() . ': ' . $e->getMessage());
        } catch (Opus_Doi_DoiException $e) {
            $log->err('unexpected error in DOI-registration of document ' . $model->getId() . ': ' . $e->getMessage());
        }
    }
}
