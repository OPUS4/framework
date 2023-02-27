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

use Admin_Form_Document_Enrichment;
use Zend_Form_Element;
use Zend_Validate_Exception;
use Zend_Validate_InArray;

use function array_key_exists;
use function array_keys;
use function array_search;
use function is_array;
use function is_bool;
use function strtok;
use function trim;

class SelectType extends AbstractType
{
    /** @var string[] Enthält die zur Auswahl stehenden Werte */
    private $values;

    /** @var string */
    private $validation = 'none';

    /**
     * @return string[]
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * @param string[]|null $values
     */
    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @return string|null
     */
    public function getValidation()
    {
        if ($this->values === null) {
            return null; // wenn keine Werteliste definiert ist, braucht auch keine Validierung spezifiziert werden
        }
        return $this->validation;
    }

    /**
     * @param bool|string $validation
     */
    public function setValidation($validation)
    {
        if (is_bool($validation)) {
            $this->validation = $validation ? 'strict' : 'none';
        } else {
            $this->validation = $validation;
        }
    }

    /**
     * @return string
     */
    public function getFormElementName()
    {
        return 'Select';
    }

    /**
     * @param string|null $value
     * @return Zend_Form_Element
     * @throws Zend_Validate_Exception
     */
    public function getFormElement($value = null)
    {
        $form    = new Admin_Form_Document_Enrichment();
        $options = ['required' => true];
        $element = $form->createElement($this->getFormElementName(), Admin_Form_Document_Enrichment::ELEMENT_VALUE, $options);

        if ($this->values !== null) {
            $element->setMultiOptions($this->values);
            $validator = new Zend_Validate_InArray(array_keys($this->values));
            $element->addValidator($validator);
        }

        // wenn es sich um ein Select-Element handelt, dann steht in $value
        // der Index des ausgewählten Eintrags (Zählung beginnt bei 0)
        // der ausgewählte Wert muss aus dem Index abgeleitet werden wenn
        if ($value !== null) {
            $value = array_search($value, $this->values);
            $element->setValue($value);
        }

        return $element;
    }

    /**
     * Liefert einen String, in dem die einzelnen Optionen zeilenweise stehen.
     *
     * @return string
     */
    public function getOptionsAsString()
    {
        $result = null;
        if ($this->values !== null) {
            foreach ($this->values as $value) {
                if ($result === null) {
                    $result = "";
                } else {
                    $result .= "\n";
                }
                $result .= $value;
            }
        }
        return $result;
    }

    /**
     * Übersetzt die Optionen im übergebenen String auf das interne Array.
     * Hierbei wird die Eingabe zeilenweise ausgewertet, d.h. ein Wert pro Zeile.
     *
     * @param string $string Optionen aus der Eingabe
     */
    public function setOptionsFromString($string)
    {
        if (is_array($string)) {
            $this->setValidation(array_key_exists('validation', $string) && $string['validation'] === '1');
            $string = $string['options'];
        }

        $separator = "\r\n";
        $line      = strtok($string, $separator);
        while ($line !== false) {
            if (trim($line) !== '') {
                if ($this->values === null) {
                    $this->values = [];
                }
                $this->values[] = $line;
            }
            $line = strtok($separator);
        }
    }

    /**
     * @return bool
     */
    public function isStrictValidation()
    {
        return $this->validation === 'strict';
    }

    /**
     * @return string[]
     */
    public function getOptionProperties()
    {
        return ['values', 'validation'];
    }
}
