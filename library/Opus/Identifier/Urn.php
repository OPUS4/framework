<?php
/**
 *
 */

/**
 * Generates a URN with check digit included.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 * 
 * @package     Opus_Identifier
 * @author      Frank Niebling <niebling@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id:
 * @category	Framework   
 */
class Opus_Identifier_Urn {

    /**
     * Standard prefix of the URN for DNB (Deutsche Nationalbibliothek).
     *
     * @var string
     */
    const NBN_URN_PREFIX = 'urn:nbn:de';

    /**
     * First subnamespace identifier part of the URN.
     *
     * @var string
     */
    protected $snid1;

    /**
     * Second subnamespace identifier part of the URN.
     *
     * @var string
     */
    protected $snid2;

    /**
     * Namespace specific string part of the URN.
     *
     * @var string
     */
    protected $niss;

    /**
     * Use standard URN parts given to build an URN generator applicable to document identifiers.
     * A complete URN might look like "urn:nbn:de:swb:14-opus-87650" having the component parts set to:
     *
     * $snid1    = swb
     * $snid2    = 14
     * $niss     = opus
     *
     * The last part of the URN above "87650" consists of a document identifier "8765" and a check digit "0".
     *
     * @param string $snid1 First subnamespace identifier part of the URN.
     * @param string $snid2 Second subnamespace identifier part of the URN.
     * @param string $niss  Namespace specific string part of the URN.
     * @throws InvalidArgumentException Thrown if at least one of the subnamespace parts contain other
     *                                  characters than characters.
     */
    public function __construct($snid1, $snid2, $niss)
    {
        if ( preg_match('/^[a-zA-Z]*$/', $snid1) !== 0 ) {
            $this->snid1 =  $snid1;
        } else {
            throw new InvalidArgumentException('Used invalid first subnamespace identifier.');
        }

        if ( preg_match('/^[1-9][0-9]*$/', $snid2) !== 0 ) {
            $this->snid2 = $snid2;
        } else {
            throw new InvalidArgumentException('Used invalid second subnamespace identifier.');
        }

        if ( preg_match('/^[a-zA-Z0-9\-]*$/', $niss) !== 0 ) {
            $this->niss = $niss;
        } else {
            throw new InvalidArgumentException('Used invalid namespace specific string.');
        }

        // compose nbn
        $nbn = self::NBN_URN_PREFIX . ':' . $this->snid1 . ':' . $this->snid2 . '-' . $this->niss . '-';
    }

    /**
     * Generates complete URNs given a document identifier.
     *
     * @param integer $document_id Identifier of the Document
     * @throws InvalidArgumentException Thrown if the document identifier is not a number.
     * @return string The URN.
     */
    public function getUrn($document_id) {

        // regexp pattern for valid document id
        $id_pattern = '/^[1-9][0-9]*$/';

        // Check if document identifier is valid.
        if ( preg_match($id_pattern, $document_id) === 0 ) {

            throw new InvalidArgumentException('Used invalid arguments for document id.');

        } else {

            // calculate matching check digit
            $check_digit = self::getCheckDigit($document_id);

            // compose and return standard, snid1, snid2, niss, document id and check digit
            return self::NBN_URN_PREFIX . ':' . $this->snid1 . ':' . $this->snid2 . '-' . $this->niss
            . '-' . $document_id . $check_digit;
        }
    }

    /**
     * Generates check digit for a given document identifer.
     *
     * @param integer $document_id ID of the Document
     * @throws InvalidArgumentException Thrown if the document identifier is not a number.
     * @return integer Check digit.
     */

    public function getCheckDigit($document_id) {

        // regexp pattern for valid document id
        $id_pattern = '/^[1-9][0-9]*$/';

        // Check if document identifier is valid.
        if ( preg_match($id_pattern, $document_id) === 0 ) {

            throw new InvalidArgumentException('Used invalid arguments for document id.');

        } else {

            // compose urn with document id
            $nbn = self::NBN_URN_PREFIX . ':' . $this->snid1 . ':' . $this->snid2 . '-' . $this->niss . '-' . $document_id;

            // Replace characters by numbers.
            $nbn_numbers = $this->replaceUrnChars($nbn);

            // identify string length
            $nbn_numbers_length = mb_strlen($nbn_numbers);

            // convert string to array of characters
            $nbn_numbers_array = preg_split('//', $nbn_numbers);

            // initialize sum
            $sum = 0;

            // calculate product sum
            for ($ii = 1; $ii <= $nbn_numbers_length; $ii++) {
                $sum = ($sum + ($nbn_numbers_array[$ii] * $ii));
            }

            // identify last digit
            $last_digit = $nbn_numbers_array[$nbn_numbers_length];

            // calculate quotient, round down
            $quotient = floor($sum/$last_digit);

            // convert to string
            $quotient = (string) $quotient;

            // identify last digit, which is the check digit
            $check_digit = ($quotient{mb_strlen($quotient)-1});

            // return check digit
            return $check_digit;
        }
    }


    /**
     * Do a replacement of every character by a specific number according to DNB check digit allegation.
     *
     * @param string $urn A partial URN with the checkdigit missing.
     * @return string The given URN with all characters replaced by numbers.
     */
    private function replaceUrnChars($urn) {
        // However, the preg_replace function calls itself on the result of a previos run. In order to get
        // the replacement right, characters and numbers in the arrays below have got a specific order to make
        // it work. Be careful when changing those numbers! Tests may help ;)
         
        // convert to lower case
        $nbn = strtolower($urn);

        // array of characters to match
        $search_pattern = array('/9/', '/8/', '/7/', '/6/', '/5/', '/4/', '/3/', '/2/', '/1/', '/0/', '/a/', '/b/', '/c/',
            	'/d/', '/e/', '/f/', '/g/', '/h/', '/i/', '/j/', '/k/', '/l/', '/m/', '/n/', '/o/', '/p/', '/q/', '/r/', '/s/', 
            	'/t/', '/u/', '/v/', '/w/', '/x/', '/y/', '/z/', '/-/', '/:/');

        // array of corresponding replacements, '9' will be temporarily replaced with placeholder '_' to prevent
        // replacement of '41' with '52'
        $replacements = array('_', 9, 8, 7, 6, 5, 4, 3, 2, 1, 18, 14, 19, 15, 16, 21, 22, 23, 24, 25, 42, 26, 27, 13, 28, 29,
        31, 12, 32, 33, 11, 34, 35, 36, 37, 38, 39, 17);

        // replace matching pattern in given nbn with corresponding replacement
        $nbn_numbers = preg_replace($search_pattern, $replacements, $nbn);

        // replace placeholder '_' with 41
        $nbn_numbers = preg_replace('/_/', 41, $nbn_numbers);

        return $nbn_numbers;
    }

}