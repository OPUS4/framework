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

class Opus_Enrichment_RegexType extends Opus_Enrichment_AbstractType
{
    private $regex = null;

    public function getRegex()
    {
        return $this->regex;
    }

    public function setRegex($regex)
    {
        $this->regex = $regex;
    }

    public function getFormElement($value = null)
    {
        $element = parent::getFormElement();

        $validator = new Zend_Validate_Regex(array('pattern' => '/' . $this->regex . '/'));
        $validator->setMessage('admin_validate_error_regex_pattern');
        $element->addValidator($validator);

        if (!is_null($value)) {
            $element->setValue($value);
        }

        return $element;
    }

    public function getOptionsAsString()
    {
        return $this->regex;
    }

    public function setOptionsFromString($string)
    {
        if (is_null($string)) {
            return; // nothing to check
        }

        // check if given option string is a valid regular expression

        // turn off error reporting and save current value for later restore
        $old_error = error_reporting(0);

        // add '/' delimiters to string that will be validated as a regex
        $stringToCheck = '/' . $string . '/';

        if (preg_match($stringToCheck, null) === false) {
            $error = error_get_last();
            $log = Opus_Log::get();
            $log->warn('given type option regex ' . $string . ' is not valid: ' . $error);
        }
        else {
            $this->regex = $string;
        }

        // restore previous error reporting level
        error_reporting($old_error);
    }

    public function getOptionProperties()
    {
        return array('regex');
    }

}
