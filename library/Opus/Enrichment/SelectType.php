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
 * @package     Opus\Enrichment
 * @author      Sascha Szott <opus-development@saschaszott.de>
 * @copyright   Copyright (c) 2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Enrichment;

class SelectType extends AbstractType
{

    /**
     * enthält die zur Auswahl stehenden Werte
     *
     * @var
     */
    private $values = null;

    private $validation = 'none';

    public function getValues()
    {
        return $this->values;
    }

    public function setValues($values)
    {
        $this->values = $values;
    }

    /**
     * @return string
     */
    public function getValidation()
    {
        if (is_null($this->values)) {
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

    public function getFormElementName()
    {
        return 'Select';
    }

    public function getFormElement($value = null)
    {
        $form = new Admin_Form_Document_Enrichment();
        $options = ['required' => true];
        $element = $form->createElement($this->getFormElementName(), Admin_Form_Document_Enrichment::ELEMENT_VALUE, $options);

        if (! is_null($this->values)) {
            $element->setMultiOptions($this->values);
            $validator = new \Zend_Validate_InArray(array_keys($this->values));
            $validator->setMessage('admin_validate_error_select_inarray');
            $element->addValidator($validator);
        }

        // wenn es sich um ein Select-Element handelt, dann steht in $value
        // der Index des ausgewählten Eintrags (Zählung beginnt bei 0)
        // der ausgewählte Wert muss aus dem Index abgeleitet werden wenn
        if (! is_null($value)) {
            $value = array_search($value, $this->values);
            $element->setValue($value);
        }

        return $element;
    }

    /**
     * Liefert einen String, in dem die einzelnen Optionen zeilenweise stehen.
     */
    public function getOptionsAsString()
    {
        $result = null;
        if (! is_null($this->values)) {
            foreach ($this->values as $value) {
                if (is_null($result)) {
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
     * @param $string Optionen aus der Eingabe
     */
    public function setOptionsFromString($string)
    {
        if (is_array($string)) {
            $this->setValidation(array_key_exists('validation', $string) && $string['validation'] === '1');
            $string = $string['options'];
        }

        $separator = "\r\n";
        $line = strtok($string, $separator);
        while ($line !== false) {
            if (trim($line) !== '') {
                if ($this->values == null) {
                    $this->values = [];
                }
                $this->values[] = $line;
            }
            $line = strtok($separator);
        }
    }

    public function isStrictValidation()
    {
        return $this->validation === 'strict';
    }

    public function getOptionProperties()
    {
        return ['values', 'validation'];
    }
}
