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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Exception;
use Opus\Common\IdentifierInterface;
use Opus\Common\Log;
use Opus\Common\Model\ModelException;
use Opus\Doi\DoiException;
use Opus\Doi\Generator\DefaultGenerator;
use Opus\Doi\Generator\DoiGeneratorFactory;
use Opus\Identifier\DoiAlreadyExistsException;
use Opus\Identifier\UrnAlreadyExistsException;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Field;
use Zend_Exception;
use Zend_Validate_NotEmpty;

use function array_combine;
use function array_search;
use function array_values;
use function count;
use function implode;
use function in_array;
use function preg_match;
use function substr;

/**
 * Domain model for document identifiers in the Opus framework
 *
 * TODO find way to remove DOI and URN functions to separate classes
 *
 * TODO desing issues - see below
 * The OPUS 4 framework is mapping objects to database tables (ORM). All identifiers are stored in the same table. The
 * table was extended with fields relevant only to DOI identifiers. In a pure object model it would make more sense to
 * extend the basic Opus\Identifier class for specific identifier types to add fields and functionality. Those classes
 * would then have to be mapped to different table, however they could also still be mapped to the same table. At some
 * point this will have to be revisited. We need a consistent object model independent of how the data is stored in the
 * end.
 *
 * phpcs:disable
 */
class Identifier extends AbstractDependentModel implements IdentifierInterface
{
    /**
     * Primary key of the parent model.
     *
     * @var mixed
     */
    protected $parentColumn = 'document_id';

    /**
     * Specify then table gateway.
     *
     * @var string
     */
    protected static $tableGatewayClass = Db\DocumentIdentifiers::class;

    /**
     * Mapping between identifier type and field name.
     *
     * @var array
     */
    private static $identifierMapping = [
        'Old'       => 'old',
        'Serial'    => 'serial',
        'Uuid'      => 'uuid',
        'Isbn'      => 'isbn',
        'Urn'       => 'urn',
        'Doi'       => 'doi',
        'Handle'    => 'handle',
        'Url'       => 'url',
        'Issn'      => 'issn',
        'StdDoi'    => 'std-doi',
        'CrisLink'  => 'cris-link',
        'SplashUrl' => 'splash-url',
        'Opus3'     => 'opus3-id',
        'Opac'      => 'opac-id',
        'Arxiv'     => 'arxiv',
        'Pubmed'    => 'pmid',
        'Ismn'      => 'ismn',
        'UnionCat'  => 'union-cat',
    ];

    /**
     * Initialize model with the following fields:
     * - Value
     * - Label
     */
    protected function init()
    {
        $value = new Field('Value');
        $value->setMandatory(true)
            ->setValidator(new Zend_Validate_NotEmpty());
        $this->addField($value);

        $typeDefaults = array_values(self::$identifierMapping);
        $typeDefaults = array_combine($typeDefaults, $typeDefaults);

        $type = new Field('Type');
        $type->setMandatory(true)
                ->setSelection(true)
                ->setValidator(new Zend_Validate_NotEmpty())
                ->setDefault($typeDefaults);
        $this->addField($type);

        // zwei Felder, die ausschließlich für Identifier vom Typ DOI genutzt werden
        $value = new Field('Status');
        $value->setMandatory(false)
            ->setDefault([
                'registered' => 'registered',
                'verified'   => 'verified',
            ]);
        $this->addField($value);

        $value = new Field('RegistrationTs');
        $value->setMandatory(false);
        $this->addField($value);
    }

    protected function _preStore()
    {
        $type  = $this->getType();
        $value = $this->getValue();
        if (isset($type) && isset($value)) {
            switch ($type) {
                case 'urn':
                    $this->checkUrnCollision($value);
                    break;
                case 'doi':
                    if ($this->checkDoiCollision()) {
                        throw new DoiAlreadyExistsException(
                            "could not save DOI with value $value since it already exists in your instance"
                        );
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
     * @throws UrnAlreadyExistsException
     * @throws ModelException
     */
    private function checkUrnCollision($value, $docId = null)
    {
        $log = Log::get();
        $log->debug('check URN collision for URN ' . $value);

        $finder = new DocumentFinder();
        $finder->setIdentifierTypeValue('urn', $value);
        $docIds = $finder->ids();
        // remove $docId of current document from $docIds

        if ($docId !== null) {
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
    public function isUrnUnique($docId = null)
    {
        try {
            $this->checkUrnCollision($this->getValue(), $docId);
            return true;
        } catch (UrnAlreadyExistsException $e) {
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
     */
    public function checkDoiCollision()
    {
        if (! $this->isLocalDoi()) {
            return false;
        }

        $log = Log::get();
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
     */
    public function isDoiUnique($docId = null)
    {
        $finder = new DocumentFinder();
        $finder->setIdentifierTypeValue('doi', $this->getValue());
        $docIds = $finder->ids();
        // remove $docId from $docIds
        if ($docId !== null) {
            if (($key = array_search($docId, $docIds)) !== false) {
                unset($docIds[$key]);
            }
        }

        try {
            $this->checkIdCollision('doi', $docIds);
        } catch (Exception $e) {
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
     *
     * TODO if generator class is missing use default class, if that is missing => fatal error
     */
    public function isLocalDoi()
    {
        $generator = null;
        try {
            $generator = DoiGeneratorFactory::create();
        } catch (DoiException $e) {
            // ignore exception
        }

        // wenn DOI-Generierungsklasse in Konfiguration angegeben wurde, dann nutze die von der Klasse
        // implementierte Methode isLocal für die Prüfung, ob eine lokale DOI vorliegt

        if ($generator === null) {
            $generator = new DefaultGenerator();
        }

        return $generator->isLocal($this->getValue());
    }

    /**
     * Prüft, dass in der DOI nur die von DataCite erlaubten Werte enthalten sind.
     */
    public function isValidDoi()
    {
        $value               = $this->getValue();
        $containsInvalidChar = preg_match('/[^0-9a-zA-Z\-\.\_\+\:\/]/', $value);
        return $containsInvalidChar !== 1;
    }

    private function checkIdCollision($type, $docIds)
    {
        $errorMsg = "$type collision (documents " . implode(",", $docIds) . ")";
        switch ($type) {
            case 'urn':
                $exception = new UrnAlreadyExistsException($errorMsg);
                break;

            case 'doi':
                $exception = new DoiAlreadyExistsException($errorMsg);
                break;

            default:
                $exception = new ModelException($errorMsg);
        }

        if ($this->isNewRecord() && ! empty($docIds)) {
            throw $exception;
        }

        if (count($docIds) > 1) {
            throw $exception;
        }

        if (count($docIds) === 1 && $this->getParentId() !== null && ! in_array($this->getParentId(), $docIds)) {
            throw $exception;
        }
    }

    public function getTypeForFieldname($fieldname)
    {
        return self::$identifierMapping[substr($fieldname, 10)];
    }

    public function getFieldnameForType($type)
    {
        return 'Identifier' . array_search($type, self::$identifierMapping);
    }

    public function getModelType()
    {
        return 'identifier';
    }

    public function getType()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setType($type)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function getValue()
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }

    public function setValue($value)
    {
        return $this->__call(__FUNCTION__, func_get_args());
    }
}
