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
 * @copyright   Copyright (c) 2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Enrichment;

interface TypeInterface
{
    /**
     * Name für Anzeige, z.B. Auswahl beim Anlegen eines neuen EnrichmentKeys
     *
     * @return string
     */
    public function getName();

    /**
     * Für Anzeige (Rueckgabe eines Uebersetzungsschluessels), um den Typ und
     * die Art der Konfiguration naeher zu erlaeutern. Wenn keine Beschreibung
     * gewuenscht wird, so muss diese Methode null oder den Leerstring zurueckliefern.
     *
     * @return string
     */
    public function getDescription();

    /**
     * Name des Formularelements, das das Rendering im Dokument-Metadatenformular
     * festlegt (z.B. Checkbox, Textfeld, Select-Feld, Textarea, etc.). Sollte
     * mit einem Grossbuchstaben beginnen.
     *
     * @return string
     */
    public function getFormElementName();

    /**
     * Erzeugt eine Formularelement (inkl. Validatoren) für die Eingabe des
     * Enrichment-Werts.
     *
     * Die Pruefung des eingegebenen Wertes erfolgt nur innerhalb der Application,
     * aber nicht auf Framework-Ebene. Somit kann nicht verhindert werden, dass
     * ueber die Framework-API nicht valide Enrichment-Werte für einen Enrichment-
     * Key in der Datenbank gespeichert werden.
     *
     * Ist der uebergebene Wert nicht null, so wird der Wert auch gleich in das
     * erzeugte Formularelement eingetragen.
     *
     * @param null|string $value anzuzeigender Wert des Enrichments
     */
    public function getFormElement($value = null);

    /**
     * Optionen, die für die Konfiguration des Enrichment Types relevant sind
     *
     * @return mixed
     */
    public function getOptions();

    /**
     * Uebersetzt die vom Benutzer eingegebene textuelle Typkonfiguration auf die
     * interne Felder
     *
     * @param string|array $string
     */
    public function setOptionsFromString($string);

    /**
     * Erzeugt eine textuelle Repraesentation der Typkonfiguration, die dem Benutzer angezeigt werden kann.
     *
     * @return string
     */
    public function getOptionsAsString();

    /**
     * Sollen bereits vorhandene Werte für ein Enrichment dieses Typs vor der Speicherung validiert werden?
     *
     * @return bool
     */
    public function isStrictValidation();

    /**
     * Liefert die Namen der Properties, die für die Erzeugung des JSON relevant sind.
     *
     * @return mixed
     */
    public function getOptionProperties();
}
