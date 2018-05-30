<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @package     Opus_Model
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Domain model for document identifiers in the Opus framework
 *
 * @category    Framework
 * @package     Opus_Model
 * @uses        Opus_Model_Dependent_Abstract
 *
 * TODO find way to remove DOI and URN functions to separate classes
 */
class Opus_Identifier extends Opus_Model_Dependent_Abstract {
    /**
     * Primary key of the parent model.
     *
     * @var mixed $_parentId.
     */
    protected $_parentColumn = 'document_id';

    /**
     * Specify then table gateway.
     *
     * @var string
     */
    protected static $_tableGatewayClass = 'Opus_Db_DocumentIdentifiers';

    /**
     * Initialize model with the following fields:
     * - Value
     * - Label
     *
     * @return void
     */
    protected function _init() {
        $value = new Opus_Model_Field('Value');
        $value->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $this->addField($value);

        $type = new Opus_Model_Field('Type');
        $type->setMandatory(true)
                ->setSelection(true)
                ->setValidator(new Zend_Validate_NotEmpty())
                ->setDefault(
                    array(
                    'old' => 'old',
                    'serial' => 'serial',
                    'uuid' => 'uuid',
                    'isbn' => 'isbn',
                    'urn' => 'urn',
                    'doi' => 'doi',
                    'handle' => 'handle',
                    'url' => 'url',
                    'issn' => 'issn',
                    'std-doi' => 'std-doi',
                    'cris-link' => 'cris-link',
                    'splash-url' => 'splash-url',
                    'opus3-id' => 'opus3-id',
                    'opac-id' => 'opac-id',
                    'pmid' => 'pmid',
                    'arxiv' => 'arxiv'
                    )
                );
        $this->addField($type);

        // zwei Felder, die ausschließlich für Identifier vom Typ DOI genutzt werden
        $value = new Opus_Model_Field('Status');
        $value->setMandatory(false)
            ->setDefault(array(
                    'registered' => 'registered',
                    'verified' => 'verified')
            );
        $this->addField($value);

        $value = new Opus_Model_Field('RegistrationTs');
        $value->setMandatory(false);
        $this->addField($value);
    }

    protected function _preStore()
    {
        $type  = $this->getType();
        $value = $this->getValue();
        if (isset($type) and isset($value)) {
            switch ($type) {
                case 'urn':
                    $this->checkUrnCollision($value);
                    break;
                case 'doi':
                    if ($this->checkDoiCollision()) {
                        throw new Opus_Identifier_DoiAlreadyExistsException('could not save DOI with value ' . $value . ' since it already exists in your instance');
                    }
                    break;
            }
        }

        return parent::_preStore();
    }

    /**
     * Prüfe, dass die in $value gespeicherte URN nicht bereits in der Datenbank existiert.
     *
     * Wird als zweites Argument eine ID eines OPUS-Dokuments übergeben, so wird das zugehörige Dokument bei der
     * Eindeutigkeitsüberprüfung nicht berücksichtigt.
     *
     * @param $value
     * @throws Opus_Identifier_UrnAlreadyExistsException
     * @throws Opus_Model_Exception
     */
    private function checkUrnCollision($value, $docId = null)
    {
        $log = Zend_Registry::get('Zend_Log');
        $log->debug('check URN collision for URN ' . $value);

        $finder = new Opus_DocumentFinder();
        $finder->setIdentifierTypeValue('urn', $value);
        $docIds = $finder->ids();
        // remove $docId of current document from $docIds

        if (!is_null($docId)) {
            if (($key = array_search($docId, $docIds)) !== false) {
                unset($docIds[$key]);
            }
        }
        $this->checkIdCollision('urn', $docIds);

        $log->debug('no URN collision was found for URN ' . $value);
    }

    /**
     * Pürft, ob die vorliegende URN innerhalb der OPUS-Instanz nur einmal vorkommt.
     * Optional kann eine ID eines OPUS-Dokuments übergeben werden. Das zugehörige Dokument wird dann bei der
     * Eindeutigkeitsüberprüfung nicht betrachtet.
     *
     * @param $docId optionale ID eines OPUS-Dokuments
     * @return bool
     */
    public function isUrnUnique($docId = null) {
        try {
            $this->checkUrnCollision($this->getValue(), $docId);
            return true;
        }
        catch (Opus_Identifier_UrnAlreadyExistsException $e) {
            // ignore exception
        }
        return false;
    }

    /**
     * Prüfe, ob die vorliegende DOI nicht bereits in der Datenbank exitsiert. Die Prüfung erstreckt sich hierbei aber
     * nur auf lokale DOIs.
     *
     * Im Falle einer DOI-Kollision (d.h. der DOI-Wert existiert bereits in der Datenbank) liefert die Methode true
     * zurück; andernfalls false.
     *
     */
    private function checkDoiCollision() {
        if (!$this->isLocalDoi()) {
            return;
        }

        $log = Zend_Registry::get('Zend_Log');
        $log->debug('check collision for local DOI ' . $this->getValue());

        if ($this->isDoiUnique()) {
            $log->debug('no DOI collision was found for DOI ' . $this->getValue());
            return false;
        }

        $log->debug('found a DOI collision for DOI ' . $this->getValue());
        return true;
    }

    /**
     * Prüft, ob die vorliegende DOI einmalig innerhalb der OPUS-Instanz ist. Es werden bei der Eindeutigkeitsprüfung
     * alle DOIs (auch von Dokumenten, die nicht im Zustand published vorliegen) betrachtet.
     *
     * Im Falle der Eindeutigkeit der DOI innerhalb der OPUS-Instanz liefert die Methode true zurück, andernfalls false.
     *
     * Wird eine ID eines Opus-Dokuments übergeben, so wird das zugehörige Dokument bei der Eindeutigkeitsprüfung nicht
     * betrachtet.
     *
     */
    public function isDoiUnique($docId = null)
    {
        $finder = new Opus_DocumentFinder();
        $finder->setIdentifierTypeValue('doi', $this->getValue());
        $docIds = $finder->ids();
        // remove $docId from $docIds
        if (!is_null($docId)) {
            if (($key = array_search($docId, $docIds)) !== false) {
                unset($docIds[$key]);
            }

            $generator = Opus_Doi_Generator_DoiGeneratorFactory::create();
            $isLocalDoi = $generator->isLocal($this->getValue());
            return $isLocalDoi;
        }

        try {
            $this->checkIdCollision('doi', $docIds);
        }
        catch (Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Prüfe, ob es sich bei dem vorliegenden Identifier um lokale DOI handelt: eine lokale DOI hat das konfigurierte
     * DOI-Präfix und das konfigurierte Lokal-Präfix, sofern es konfiguriert wurden.
     *
     * Wird eine DOI-Generierungsklasse verwendet, so wird auf die von der Klasse zu implementierende Prüfmethode
     * zurückgegriffen.
     *
     * Im Erfolgsfall gibt die Methode true zurück; sonst false.
     *
     * @return bool
     * @throws Zend_Exception
     */
    public function isLocalDoi()
    {

        $generator = null;
        try {
            $generator = Opus_Doi_Generator_DoiGeneratorFactory::create();
        }
        catch (Opus_Doi_DoiException $e) {
            // ignore exception
        }

        // wenn DOI-Generierungsklasse in Konfiguration angegeben wurde, dann nutze die von der Klasse
        // implementierte Methode isLocal für die Prüfung, ob eine lokale DOI vorliegt
        if (!is_null($generator)) {
            $isLocalDoi = $generator->isLocal($this->getValue());
            return $isLocalDoi;
        }

        $config = Zend_Registry::get('Zend_Config');

        // es wurde keine DOI-Generierungsklasse angegeben bzw. die Klasse kann nicht gefunden werden.
        // wir prüfen lediglich, ob die DOI mit dem konfigurierten Präfix beginnt und nach dem Schrägstrich
        // das ggf. konfigurierte lokale Präfix folgt
        if (!isset($config->doi->prefix) || $config->doi->prefix == '') {
            // DOI-Präfix für nicht definiert: daher können wir nicht entscheiden, ob eine DOI lokal ist
            return false;
        }

        $prefix = $config->doi->prefix;
        if (substr($prefix, -1) != '/') {
            $prefix .= '/';
        }

        if (isset($config->doi->localPrefix) && $config->doi->localPrefix != '') {
            $prefix .= $config->doi->localPrefix;
        }

        // beginnt die DOI mit $prefix
        if (substr($this->getValue(), 0, strlen($prefix)) !== $prefix) {
            return false;
        }

        return true; // lokale DOI gefunden
    }

    /**
     * Prüft, dass in der DOI nur die von DataCite erlaubten Werte enthalten sind.
     */
    public function isValidDoi()
    {
        $value = $this->getValue();
        $containsInvalidChar = preg_match('/[^0-9a-zA-Z\-\.\_\+\:\/]/', $value);
        return $containsInvalidChar !== 1;
    }

    private function checkIdCollision($type, $docIds)
    {
        $errorMsg = "$type collision (documents " . implode(",", $docIds) . ")";
        switch ($type) {
            case 'urn':
                $exception = new Opus_Identifier_UrnAlreadyExistsException($errorMsg);
                break;

            case 'doi':
                $exception = new Opus_Identifier_DoiAlreadyExistsException($errorMsg);
                break;

            default:
                $exception = new Opus_Model_Exception($errorMsg);
        }

        if ($this->isNewRecord() and !empty($docIds)) {
            throw $exception;
        }

        if (count($docIds) > 1) {
            throw $exception;
        }

        if (count($docIds) == 1 and !is_null($this->getParentId()) and !in_array($this->getParentId(), $docIds)) {
            throw $exception;
        }
    }
}
