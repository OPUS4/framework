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
 * @copyright   Copyright (c) 2009-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Identifier
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 *              Original implementation: http://us2.php.net/manual/en/function.uniqid.php#88400
 */

namespace Opus\Identifier;

use function bin2hex;
use function chr;
use function fclose;
use function fopen;
use function fread;
use function hexdec;
use function is_resource;
use function mt_rand;
use function sprintf;
use function substr;

/**
 * @see http://tools.ietf.org/html/rfc4122#section-4.4
 * @see http://en.wikipedia.org/wiki/UUID
 *
 * @brief Generates a Universally Unique IDentifier, version 4.
 */
class UUID
{
    /**
     * This function generates a truly random UUID.
     *
     * @return string A UUID, made up of 32 hex digits and 4 hyphens.
     */
    public static function generate()
    {
        $urand  = @fopen('/dev/urandom', 'rb');
        $prBits = false;
        if (is_resource($urand)) {
            $prBits .= @fread($urand, 16);
        }
        if (! $prBits) {
            $fp = @fopen('/dev/urandom', 'rb');
            if ($fp !== false) {
                $prBits .= @fread($fp, 16);
                @fclose($fp);
            } else {
                // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
                $prBits = "";
                for ($cnt = 0; $cnt < 16; $cnt++) {
                    $prBits .= chr(mt_rand(0, 255));
                }
            }
        }
        $timeLow               = bin2hex(substr($prBits, 0, 4));
        $timeMid               = bin2hex(substr($prBits, 4, 2));
        $timeHiAndVersion      = bin2hex(substr($prBits, 6, 2));
        $clockSeqHiAndReserved = bin2hex(substr($prBits, 8, 2));
        $node                  = bin2hex(substr($prBits, 10, 6));

        /**
         * Set the four most significant bits (bits 12 through 15) of the
         * time_hi_and_version field to the 4-bit version number from
         * Section 4.1.3.
         *
         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
         */
        $timeHiAndVersion   = hexdec($timeHiAndVersion);
        $timeHiAndVersion >>= 4;
        $timeHiAndVersion  |= 0x4000;

        /**
         * Set the two most significant bits (bits 6 and 7) of the
         * clock_seq_hi_and_reserved to zero and one, respectively.
         */
        $clockSeqHiAndReserved   = hexdec($clockSeqHiAndReserved);
        $clockSeqHiAndReserved >>= 2;
        $clockSeqHiAndReserved  |= 0x8000;

        return sprintf(
            '%08s-%04s-%04x-%04x-%012s',
            $timeLow,
            $timeMid,
            $timeHiAndVersion,
            $clockSeqHiAndReserved,
            $node
        );
    }
}
