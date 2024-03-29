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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Document\Plugin;

use Exception;
use Opus\Common\Config;
use Opus\Common\Identifier;
use Opus\Common\Log;
use Opus\Common\Model\ModelInterface;
use Opus\Common\Model\Plugin\AbstractPlugin;
use Opus\Common\Model\Plugin\ServerStateChangeListenerInterface;
use Opus\Document;
use Opus\Doi\DoiException;
use Opus\Doi\DoiManager;
use Opus\Doi\RegistrationException;

use function filter_var;
use function get_class;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Plugin for generating identifiers of type DOI.
 *
 * phpcs:disable
 */
class IdentifierDoi extends AbstractPlugin implements ServerStateChangeListenerInterface
{
    /**
     * was muss hier alles ausgewertet werden:
     * automatische Generierung einer DOI für das vorliegende Dokument, wenn
     * 1. noch keine DOI vorhanden
     * 2. Enrichment opus.doi.autoCreate wurde gesetzt
     *
     * außerdem automatische Registrierung der DOI (Aufruf MDS-Webservice von DataCite)
     * wenn DOI vorhanden und die Konfigurationseinstellung doi.registerAtPublish ist auf true/1 gesetzt
     *
     *
     * laut Spezifikation: jedes OPUS-Dokument kann maximal eine zugeordnete DOI haben
     * diese DOI ist entweder lokal oder extern
     * im Rahmen der automatischen DOI-Registrierung werden nur lokale DOIs betrachtet
     */
    public function postStoreInternal(ModelInterface $model)
    {
        $log = Log::get();

        if (! $model instanceof Document) {
            $log->err(self::class . ' found unexpected model class ' . get_class($model));
            return;
        }

        $serverState = $model->getServerState();
        $log->debug(self::class . ' postStoreInternal for ' . $model->getDisplayName() . ' and target state ' . $serverState);

        if ($serverState === Document::STATE_PUBLISHED) {
            $this->handlePublishEvent($model, $log);
        } elseif ($serverState === Document::STATE_DELETED && $model->getServerStateChanged()) {
            $this->handleDeleteEvent($model);
        } else {
            $log->debug(self::class . ' postStoreInternal: nothing to do for document with server state ' . $serverState);
            return;
        }
    }

    public function preDelete(ModelInterface $doc)
    {
        if ($doc === null) {
            // ignore silently and exit method since we do not need to perform any action
            return;
        }

        if ($doc != null && $doc->getServerState() === Document::STATE_DELETED) {
            $this->handleDeleteEvent($doc);
        }
    }

    /**
     * Removes metadata for DOI, if document gets "deleted".
     *
     * @param $document
     * @return mixed|void
     */
    public function serverStateChanged($document)
    {
        if ($document != null && $document->getServerState() === Document::STATE_DELETED) {
            $this->handleDeleteEvent($document);
        }
    }

    protected function handleDeleteEvent($document)
    {
        // Metadatensatz für DOI auf den Status "inactive" setzen
        $doiManager = $this->getDoiManager();
        $doiManager->deleteMetadataForDoi($document);
    }

    protected function handlePublishEvent($document, $log)
    {
        // prüfe zuerst, ob das Dokument das Enrichment opus.doi.autoCreate besitzt
        // in diesem Fall wird nun eine DOI gemäß der Konfigurationseinstellungen generiert
        $generateDoi = null;
        $enrichment  = $document->getEnrichment('opus.doi.autoCreate');
        if ($enrichment !== null) {
            $enrichmentValue = $enrichment->getValue();
            $generateDoi     = $enrichmentValue === 'true';
            $log->debug('found enrichment opus.doi.autoCreate with value ' . $enrichmentValue);
        }

        $config = Config::get();

        if ($generateDoi === null) {
            // Enrichment opus.doi.autoCreate wurde nicht gefunden - verwende Standardwert für die DOI-Erzeugung aus Konfiguration
            $generateDoi = isset($config->doi->autoCreate) && filter_var($config->doi->autoCreate, FILTER_VALIDATE_BOOLEAN);
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
     * @param $model Document zu dem die DOI hinzugefügt werden soll
     */
    protected function addDoi($model, $log)
    {
        try {
            $doiManager = $this->getDoiManager();
            $doiValue   = $doiManager->generateNewDoi($model);
        } catch (DoiException $e) {
            $message = 'could not generate DOI value for document ' . $model->getId() . ': ' . $e->getMessage();
            $log->err($message);
            throw new Exception($message);
        }

        $doi = Identifier::new();
        $doi->setType('doi');
        $doi->setValue($doiValue);

        $identifiers = $model->getIdentifier();
        if ($identifiers === null) {
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
    protected function registerDoi($model, $log, $config)
    {
        // prüfe ob Konfigurationseinstellung eine Registrierung vorgibt
        if (! isset($config->doi->registerAtPublish) || ! filter_var($config->doi->registerAtPublish, FILTER_VALIDATE_BOOLEAN)) {
            $log->debug('registration of DOIs at publish time is disabled in configuration');
            return;
        }

        // führe die Registrierung durch
        $log->info('start registration of DOI for document ' . $model->getId());

        try {
            $doiManager    = $this->getDoiManager();
            $registeredDoi = $doiManager->register($model);
            if ($registeredDoi === null) {
                $log->err('could not apply DOI registration on document ' . $model->getId());
            }
        } catch (RegistrationException $e) {
            $log->err('unexpected error in registration of DOI ' . $e->getDoi() . ' of document ' . $model->getId() . ': ' . $e->getMessage());
        } catch (DoiException $e) {
            $log->err('unexpected error in DOI-registration of document ' . $model->getId() . ': ' . $e->getMessage());
        }
    }

    protected function getDoiManager()
    {
        return DoiManager::getInstance();
    }
}
