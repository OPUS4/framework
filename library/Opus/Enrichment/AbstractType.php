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
 * @category    Application
 * @package     Opus_Enrichment
 * @author      Sascha Szott <opus-development@saschaszott.de>
 * @copyright   Copyright (c) 2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Enrichment_AbstractType implements Opus_Enrichment_TypeInterface
{

    public function getName()
    {
        return substr(get_class($this), strlen('Opus_Enrichment_'));
    }

    public function getDescription()
    {
        return 'admin_enrichmenttype_' . strtolower($this->getName()) . '_description';
    }

    public function getFormElementName()
    {
        return 'Text';
    }

    public function getFormElement($value = null)
    {
        // Standardverhalten Text-Element, das nicht leer sein darf

        $form = new Admin_Form_Document_Enrichment();
        $options = ['required' => true, 'size' => 60]; // FIXME required wenn checkbox?
        $element = $form->createElement($this->getFormElementName(), Admin_Form_Document_Enrichment::ELEMENT_VALUE, $options);

        if (! is_null($value)) {
            $element->setValue($value);
        }

        return $element;
    }

    /**
     * Mappt JSON als String, das in der Datenbank gespeichert ist, oder
     * alternativ ein Array auf die internen Felder des vorliegenden Enrichment-Typs
     *
     * @param $options entweder String oder Array
     */
    public function setOptions($options)
    {
        if (is_array($options)) {
            $optionsArray = $options;
        } else {
            if (is_null($options) || $options === '') {
                return;
            }

            $optionsArray = json_decode($options);
            if (is_null($optionsArray)) {
                $log = Opus_Log::get();
                $log->err('could not decode JSON string: ' . $options);
                return;
            }
        }

        foreach ($optionsArray as $key => $value) {
            $setMethod = 'set' . ucfirst($key);
            if (method_exists($this, $setMethod)) {
                $this->$setMethod($value);
            } else {
                $log = Opus_Log::get();
                $log->err('method ' . $setMethod . ' does not exist on enrichment type ' . get_class($this));
            }
        }
    }

    /**
     * Erzeugt aus allen registrierten Options ein gefülltes JSON-Objekt, das
     * als String zurückgegeben wird.
     *
     * @return JSON
     */
    public function getOptions()
    {
        $options = null;
        foreach ($this->getOptionProperties() as $optionProperty) {
            $methodName = 'get' . lcfirst($optionProperty);
            $attributeValue = $this->$methodName();
            if ($attributeValue != null) {
                if (is_null($options)) {
                    $options = [];
                }
                $options[$optionProperty] = $attributeValue;
            }
        }

        if (is_null($options)) {
            return null;
        }

        $result = json_encode($options);
        if ($result === false) {
            // Fehler bei der Erzegung des JSON
            return null;
        }
        return $result;
    }

    /**
     * Ermittelt die Klassennamen aller im System verfügbaren EnrichmentTypes.
     */
    public static function getAllEnrichmentTypes($rawNames = false)
    {
        $files = array_diff(scandir(__DIR__), ['.', '..', 'AbstractType.php', 'TypeInterface.php']);
        $result = [];

        if ($files === false) {
            return $result;
        }

        foreach ($files as $file) {
            if (substr($file, strlen($file) - 4) == '.php') {
                // found PHP file - try to instantiate
                $className = 'Opus_Enrichment_' . substr($file, 0, strlen($file) - 4);
                $interfaces = class_implements($className);
                if (in_array('Opus_Enrichment_TypeInterface', $interfaces)) {
                    $type = new $className();
                    if (! $rawNames) {
                        $typeName = $type->getName();
                        $result[$typeName] = $typeName;
                    } else {
                        $result[] = $className;
                    }
                }
            }
        }

        return $result;
    }

    public function getOptionsAsString()
    {
        return null;
    }

    public function setOptionsFromString($string)
    {
        return null;
    }

    public function isStrictValidation()
    {
        return false;
    }

    public function getOptionProperties()
    {
        return [];
    }
}
