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
 * @copyright   Copyright (c) 2009-2018
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @copyright   Copyright (c) 2010-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Julian Heise (heise@zib.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Document\Plugin;

use Opus\Common\Config;
use Opus\Common\Log;
use Opus\Common\Model\ModelInterface;
use Opus\Common\Model\Plugin\AbstractPlugin;
use Opus\Common\Model\Plugin\ServerStateChangeListenerInterface;
use Opus\Document;
use Opus\Document\DocumentException;
use Opus\Identifier;
use Opus\Identifier\Urn;

use function array_filter;
use function count;
use function filter_var;
use function get_class;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Plugin for generating identifier urn.
 *
 * phpcs:disable
 */
class IdentifierUrn extends AbstractPlugin implements ServerStateChangeListenerInterface
{
    /**
     * Generates a new URN for any document that has no URN assigned yet.
     * URN's are generated for Opus\Document instances only.
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

        if ($serverState !== 'published') {
            $log->debug(self::class . ' postStoreInternal: nothing to do for document with server state ' . $serverState);
            return;
        }

        // prüfe zuerst, ob das Dokument das Enrichment opus.urn.autoCreate besitzt
        // in diesem Fall bestimmt der Wert des Enrichments, ob eine URN beim Publish generiert wird
        $generateUrn = null;
        $enrichment  = $model->getEnrichment('opus.urn.autoCreate');
        if ($enrichment !== null) {
            $enrichmentValue = $enrichment->getValue();
            $generateUrn     = $enrichmentValue === 'true';
            $log->debug('found enrichment opus.urn.autoCreate with value ' . $enrichmentValue);
        }

        $config = Config::get();
        if ($generateUrn === null) {
            // Enrichment opus.urn.autoCreate wurde nicht gefunden - verwende Standardwert für die URN-Erzeugung aus Konfiguration
            $generateUrn = isset($config->urn->autoCreate) && filter_var($config->urn->autoCreate, FILTER_VALIDATE_BOOLEAN);
        }

        if (! $generateUrn) {
            $log->debug('URN auto creation is not configured. skipping...');
            return;
        }

        if (! isset($config->urn->nid) || ! isset($config->urn->nss)) {
            throw new DocumentException('URN data is not present in config. Aborting...');
            // FIXME hier sollte keine Exception geworfen werden, weil sonst
            //       die Ausführung aller nachfolgenden Plugins im Plugin-Array abgebrochen wird
            //       Plugins werden nämlich in Schleife nacheinander aufgerufen (ohne Exception Handling zwischen
            //       den einzelnen Aufrufen)

            // FIXME außerdem ist der Exception Type schlecht gewählt, weil es sich in diesem
            //       Fall ja um einen Konfigurationsfehler handelt und nicht um einen Fehler im Dokument
        }

        $log->debug('config.ini is set to support urn auto generation');

        if ($this->urnAlreadyPresent($model)) {
            $log->debug('Model ' . $model->getDisplayName() . ' already has a URN. Skipping automatic generation.');
            return;
        }

        if (! $this->allowUrnOnThisDocument($model)) {
            $log->debug('Model ' . $model->getDisplayName() . ' has no oai-visible files. Skipping automatic URN generation.');
            return;
        }

        $log->debug('Generating URN for document ' . $model->getDisplayName());

        $nid = $config->urn->nid;
        $nss = $config->urn->nss;

        $urn       = new Urn($nid, $nss);
        $urn_value = $urn->getUrn($model->getId());
        $urn_model = Identifier::new();
        $urn_model->setValue($urn_value);
        $urn_model->setType('urn');
        $model->addIdentifier($urn_model);
    }

    /**
     * Liefert true, wenn das vorliegende Dokument bereits einen Identifier vom Typ URN besitzt; andernfalls false.
     *
     * @param $document
     * @return bool
     */
    public function urnAlreadyPresent($document)
    {
        $identifierUrns = $document->getIdentifierUrn();
        if (count($identifierUrns) > 0) {
            return true;
        }

        $identifiers = $document->getIdentifier();
        foreach ($identifiers as $identifier) {
            if ($identifier->getType() === 'urn') {
                return true;
            }
        }

        return false;
    }

    /**
     * Liefert true, wenn das vorliegende Dokumente mindestens eine Datei mit OAI-Sichtbarkeit besitzt (nur für solche
     * Dokumente kann bei der DNB eine URN registriert werden)
     *
     * @param $document
     * @return bool
     */
    public function allowUrnOnThisDocument($document)
    {
        $files = array_filter(
            $document->getFile(),
            function ($f) {
                return ( int )$f->getVisibleInOai() === 1;
            }
        );
        return count($files) > 0;
    }

    /**
     * @param $document
     * @return mixed|void
     *
     * TODO don't do anything, interface used to be just a marker
     */
    public function serverStateChanged($document)
    {
    }
}
