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
 * @package     Opus_Validate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Defines an validator for ISBN-13 numbers.
 *
 * @category    Framework
 * @package     Opus_Validate
 */
class Opus_Validate_Isbn13 extends Zend_Validate_Abstract {

    /**
     * Error message key for invalid check digit.
     *
     */
    const MSG_CHECK_DIGIT = 'checkdigit';


    /**
     * Error message key for malformed ISBN.
     *
     */
    const MSG_FORM = 'form';

    /**
     * Error message templates.
     *
     * @var array
     */
    protected $_messageTemplates = array(
    self::MSG_CHECK_DIGIT => "The check digit of '%value%' is not valid",
    self::MSG_FORM => "'%value%' is malformed"
    );

    /**
     * Validate the given ISBN-13 string.
     *
     * @param string $value An ISBN-13 number.
     * @return boolean True if the given ISBN string is valid.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // check lenght
        if (strlen($value) !== (13+4)) {
            $this->_error(self::MSG_FORM);
            return false;
        }

        // check form
        if (preg_match('/^(978|979)((-|\s)[\d]*){4}$/', $value) === 0) {
            $this->_error(self::MSG_FORM);
            return false;
        }

        // check for mixed separators
        if ((preg_match('/-/', $value) > 0) and (preg_match('/\s/', $value) > 0)) {
            $this->_error(self::MSG_FORM);
            return false;
        }

        // Split ISBN into its parts
        $isbn_parts = preg_split('/(-|\s)/', $value);

        // Separate digits for checkdigit calculation
        $digits = array();
        for ($i=0; $i<4; $i++) {
            foreach (str_split($isbn_parts[$i]) as $digit) {
                $digits[] = $digit;
            }
        }

        // Calculate and compare check digit
        $checkdigit = $this->calculateCheckDigit($digits);
        if ($checkdigit !== $isbn_parts[4]) {
            $this->_error(self::MSG_CHECK_DIGIT);
            return false;
        }

        return true;
    }

    /**
     * Calculate the checkdigit from a given array of 12 digits.
     *
     * @param array $digits Array of digits that form ISBN.
     * @return string The check digit.
     */
    protected function calculateCheckDigit(array $digits) {
        $z = $digits;
        $z[12] = ((10 - (($z[0]+$z[2]+$z[4]+$z[6]+$z[8]+$z[10]+(3*($z[1]+$z[3]+$z[5]+$z[7]+$z[9]+$z[11]))) % 10)) % 10);
        return "$z[12]";
    }

}