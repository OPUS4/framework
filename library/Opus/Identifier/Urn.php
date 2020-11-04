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
 * @package     Opus\Identifier
 * @author      Frank Niebling (frank.niebling@slub-dresden.de)
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Identifier;

/**
 * Generates a URN with check digit included.
 *
 * @link http://tools.ietf.org/html/rfc2141 "RFC 2141 URN Syntax"
 *
 * @category Framework
 * @package Opus\Identifier
 */
class Urn
{

    /**
     * The URN prefix
     *
     * @var string
     */
    const URN_PREFIX = 'urn';

    /**
     * The URN namespace.
     *
     * @var string
     */
    protected $_nid;

    /**
     * Namespace specific string part of the URN.
     *
     * @var string
     */
    protected $_nss;

    /**
     * Concatenation of URN-Namespace.
     *
     * @var string
     */
    private $_namespace;

    /**
     * Use standard URN parts given to build an URN generator applicable to document identifiers.
     * A complete URN might look like "urn:nbn:de:swb:14-opus-87650" having the component parts set to:
     *
     * $nid = 'nbn'
     * $nss = "de:swb:14-opus"
     *
     * The last part of the URN above "87650" consists of a document identifier "8765" and a check digit "0".
     *
     * @param string $nid The namespace identifier.
     * @param string $nss  Namespace specific string.
     * @throws \InvalidArgumentException Thrown if nid or nss does not follow RFS 2141.
     */
    public function __construct($nid, $nss)
    {
        $nidRegex = '/^[a-zA-Z0-9][a-zA-z0-9\-]+$/';
        if (preg_match($nidRegex, $nid) !== 0) {
            $this->_nid = $nid;
        } else {
            throw new \InvalidArgumentException('Used invalid namespace identifier. See RFC 2141.');
        }

        $nssRegex = '/^[a-zA-z0-9\(\)\+,\-\.:=@;\$_!\*\'%\/\?#]+$/';
        if (preg_match($nssRegex, $nss) !== 0) {
            $this->_nss = $nss;
        } else {
            throw new \InvalidArgumentException('Used invalid namespace specific string. See RFC 2141.');
        }

        // compose namespace
        $this->_namespace = self::URN_PREFIX . ':' . $this->_nid . ':' . $this->_nss . '-';
    }

    /**
     * Generates complete URNs given a document identifier.
     *
     * @param integer $documentId Identifier of the Document
     * @throws \InvalidArgumentException Thrown if the document identifier is not a number.
     * @return string The URN.
     */
    public function getUrn($documentId)
    {
        // regexp pattern for valid document id
        $idPattern = '/^[1-9][0-9]*$/';

        // Check if document identifier is valid.
        if (preg_match($idPattern, $documentId) === 0) {
            throw new \InvalidArgumentException('Used invalid arguments for document id.');
        } else {
            // calculate matching check digit
            $checkDigit = self::getCheckDigit($documentId);

            // compose and return urn and check digit
            return $this->_namespace . $documentId . $checkDigit;
        }
    }

    /**
     * Generates check digit for a given document identifer.
     *
     * @param integer $documentId ID of the Document
     * @throws \InvalidArgumentException Thrown if the document identifier is not a number.
     * @return integer Check digit.
     */

    public function getCheckDigit($documentId)
    {

        // regexp pattern for valid document id
        $idPattern = '/^[1-9][0-9]*$/';

        // Check if document identifier is valid.
        if (preg_match($idPattern, $documentId) === 0) {
            throw new \InvalidArgumentException('Used invalid arguments for document id.');
        } else {
            // compose urn with document id
            $nbn = $this->_namespace . $documentId;

            // Replace characters by numbers.
            $nbnNumbers = $this->replaceUrnChars($nbn);

            // identify string length
            // usr of strlen instead of mb_strlen is okay here
            // the urn syntax (rfc 2141) ensures that no charactes are used
            // that could make problems.
            $nbnNumbersLength = strlen($nbnNumbers);

            // convert string to array of characters
            $nbnNumbersArray = preg_split('//', $nbnNumbers);

            // initialize sum
            $sum = 0;

            // calculate product sum
            for ($ii = 1; $ii <= $nbnNumbersLength; $ii++) {
                $sum = ($sum + ($nbnNumbersArray[$ii] * $ii));
            }

            // identify last digit
            $lastDigit = $nbnNumbersArray[$nbnNumbersLength];

            // calculate quotient, round down
            $quotient = floor($sum / $lastDigit);

            // convert to string
            $quotient = (string) $quotient;

            // identify last digit, which is the check digit
            // TODO: (Thoralf)  Not supported by every PHP
            // $check_digit = ($quotient{mb_strlen($quotient)-1});
            $checkDigit = ($quotient{strlen($quotient) - 1});

            // return check digit
            return $checkDigit;
        }
    }


    /**
     * Do a replacement of every character by a specific number according to DNB check digit allegation.
     *
     * @param string $urn A partial URN with the checkdigit missing.
     * @return string The given URN with all characters replaced by numbers.
     */
    private function replaceUrnChars($urn)
    {
        // However, the preg_replace function calls itself on the result of a previos run. In order to get
        // the replacement right, characters and numbers in the arrays below have got a specific order to make
        // it work. Be careful when changing those numbers! Tests may help ;)

        // convert to lower case
        $nbn = strtolower($urn);

        // array of characters to match
        $searchPattern = [
            '/9/', '/8/', '/7/', '/6/', '/5/', '/4/', '/3/', '/2/', '/1/', '/0/', '/a/', '/b/',
            '/c/', '/d/', '/e/', '/f/', '/g/', '/h/', '/i/', '/j/', '/k/', '/l/', '/m/', '/n/', '/o/', '/p/', '/q/',
            '/r/', '/s/', '/t/', '/u/', '/v/', '/w/', '/x/', '/y/', '/z/', '/-/', '/:/'
        ];

        // array of corresponding replacements, '9' will be temporarily replaced with placeholder '_' to prevent
        // replacement of '41' with '52'
        $replacements = [
            '_', 9, 8, 7, 6, 5, 4, 3, 2, 1, 18, 14, 19, 15, 16, 21, 22, 23, 24, 25, 42, 26, 27, 13, 28, 29, 31, 12, 32,
            33, 11, 34, 35, 36, 37, 38, 39, 17
        ];

        // replace matching pattern in given nbn with corresponding replacement
        $nbnNumbers = preg_replace($searchPattern, $replacements, $nbn);

        // replace placeholder '_' with 41
        $nbnNumbers = preg_replace('/_/', 41, $nbnNumbers);

        return $nbnNumbers;
    }
}
