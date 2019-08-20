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
 * @package     Opus_Model
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Model_UnixTimestampField extends Opus_Model_DateField
{

    /**
     * Returns UNIX timestamp for Opus_Date, but does not allow setting value.
     *
     * Only return a timestamp if the Opus_Date object is including a time and a timezone. If it is just a date return
     * null.
     *
     * @param null $index
     * @return mixed|null
     */
    public function getValue($index = null)
    {
        $timestamp = $this->parent->getTimestamp();
        if (! is_null($timestamp) and $timestamp > 0) {
            return $timestamp;
        } else {
            return null;
        }
    }

    /**
     * UnixTimestamp is an read-only field.
     *
     * This does not make sense initially, however the original code was written in a way that setting UnixTimestamp
     * did not really have an effect. In order to maintain compatibility and support the new functionality of importing
     * from an Array the field UnixTimestamp cannot be set anymore.
     *
     * @param $value
     * @return Opus_Model_Field|void
     */
    public function setValue($value)
    {
    }
}
