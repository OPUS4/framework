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
 * @package     Opus\Doi
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Doi\Generator;

class DefaultGenerator implements DoiGeneratorInterface
{

    private $config;

    public function __construct()
    {
        $this->config = \Zend_Registry::get('Zend_Config');
    }

    /**
     * Erzeugt auf Basis der Konfigurationseinstellungen eine DOI in der Form
     *
     * doi.prefix/doi.localPrefix-docId
     *
     * Ist doi.localPrefix in der Konfiguration nicht gesetzt, so wird die Form
     *
     * doi.prefix/docId
     *
     * verwendet.
     *
     * Schrägstrich / bzw. Bindestrich - werden im Bedarfsfall eingefügt, sofern in den Konfigurationswerten
     * nicht angegeben.
     *
     * Der Konfigurationsparameter doi.suffixFormat wird von dieser DOI-Generierungsklasse NICHT berücksichtigt.
     * Er ist fest auf {docId} gesetzt.
     *
     * @throws DoiGeneratorException
     */
    public function generate($document)
    {
        $prefix = $this->getPrefix();

        $generatedDOI = $prefix . $document->getId();
        return $generatedDOI;
    }

    /**
     * Liefert true zurück, wenn die übergebene DOI als lokale DOI zu betrachten ist.
     * Im Falle der vorliegenden Implementierungsklasse muss eine lokale DOI folgenden
     * Präfix haben: '{doi.prefix}/{doi.localPrefix}-'
     *
     */
    public function isLocal($doiValue)
    {
        try {
            $prefix = $this->getPrefix();
        } catch (DoiGeneratorException $odgd) {
            // if no local prefix is configured, no DOI is local
            return false;
        }

        $result = substr($doiValue, 0, strlen($prefix)) == $prefix;

        return $result;
    }

    /**
     * Returns compound prefix for DOIs.
     * @return string
     * @throws DoiGeneratorException
     */
    public function getPrefix()
    {
        if (! isset($this->config->doi->prefix) or strlen(trim($this->config->doi->prefix)) === 0) {
            throw new DoiGeneratorException(
                'configuration setting doi.prefix is missing - DOI cannot be generated'
            );
        }

        // Schrägstrich als Trennzeichen, wenn Präfix nicht bereits einen Schrägstrich als Suffix besitzt
        $prefix = rtrim($this->config->doi->prefix, '/') . '/';

        if (isset($this->config->doi->localPrefix) and $this->config->doi->localPrefix != '') {
            $prefix .= $this->config->doi->localPrefix;

            // DocID wird als Suffix mit Bindestrich an das Präfix angefügt (füge Bindestrich hinzu, wenn erforderlich)
            $prefix = rtrim($prefix, '-') . '-';
        }

        return $prefix;
    }
}
