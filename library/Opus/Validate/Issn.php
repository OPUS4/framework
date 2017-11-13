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
 * @author      Maximilian Salomon <salomon@zib.de>
 * @copyright   Copyright (c) 2017, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Class Opus_Validate_Issn validates an ISSN-identifier.
 */
class Opus_Validate_Issn extends Zend_Validate_Abstract
{
    /**
     * Error message key for invalid check digit.
     *
     */
    const MSG_CHECK_DIGIT = 'checkdigit';

    /**
     * Error message key for malformed ISSN.
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
     * Verify the input, for formal criteria of an issn.
     * @param $value, with input to check
     * @return bool
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        // check length
        if (strlen($value) !== (8 + 1)) {
            $this->_error(self::MSG_FORM);
            return false;
        }

        // check form
        if (preg_match('/^[0-9]{4}[-][0-9]{3}[0-9X]$/', $value) === 0) {
            $this->_error(self::MSG_FORM);
            return false;
        }

        // Split ISSN into its parts
        $issn = str_split($value);

        // Calculate and compare check digit
        $checkdigit = $this->calculateCheckDigit($issn);
        if ($checkdigit !== $issn[8]) {
            $this->_error(self::MSG_CHECK_DIGIT);
            return false;
        }

        return true;
    }

    /**
     * Calculate the checkdigit from a given array of 8 digits.
     *
     * @param array $issn Array of digits that form ISSN
     * @return string The check digit
     */
    protected function calculateCheckDigit(array $issn)
    {
        $z = $issn;
        $check = (8 * $z[0] + 7 * $z[1] + 6 * $z[2] + 5 * $z[3] + 4 * $z[5] + 3 * $z[6] + 2 * $z[7]);
        if ($check % 11 === 0) {
            $checkdigit = 0;
        }
        elseif (11 - ($check % 11) == 10) {
            $checkdigit = 'X';
        }
        else {
            $checkdigit = 11 - ($check % 11);
        }

        return "$checkdigit";
    }

}