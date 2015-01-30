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
 * @package     Opus
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for date and time storage.
 *
 * @category    Framework
 * @package     Opus
 */
class Opus_DateTimestamp extends Opus_Date {

    const TIMEDATE_REGEXP = '/^\d+-\d{1,2}-\d{1,2}T\d{1,2}:\d{1,2}:\d{1,2}([A-Za-z]+|[+-][0-9:]+)$/';
    const DATEONLY_REGEXP = '/^\d+-\d{1,2}-\d{1,2}$/';

    /**
     * Set up model with given value or with the current timestamp.
     *
     * @param Zend_Date|Opus_Date|string $value (Optional) Some sort of date representation.
     * @return void
     */
    public function __construct($value = null) {
        parent::__construct();

        if ($value instanceof Zend_Date) {
            $this->setZendDate($value);
        }
        else if ($value instanceof DateTime) {
            $this->setDateTime($value);
        }
        else if (is_string($value)) {
            $this->setFromString($value);
        }
        else if ($value instanceof Opus_Date) {
            $this->updateFrom($value);
        }
        else {
            // set all fields to 0
            $this->setYear(0)
                ->setMonth(0)
                ->setDay(0)
                ->setHour(NULL)
                ->setMinute(NULL)
                ->setSecond(NULL)
                ->setTimezone(NULL)
                ->setUnixTimestamp(0);
        }
    }

    /**
     * Return ISO 8601 string representation of the date.  For instance:
     *    2011-02-28T23:59:59[+-]01:30
     *    2011-02-28T23:59:59(Z|UTC|...)
     *    2011-02-28                    (if some time values/time zone are null)
     *
     * @return string ISO 8601 date string.
     */
    public function __toString() {
        $dateStr = implode("-", array($this->getYear(), $this->getMonth(), $this->getDay()));
        if ($this->isDateOnly()) {
            return $dateStr;
        }

        $timeStr = implode(":", array($this->getHour(), $this->getMinute(), $this->getSecond()));
        $tzStr   = $this->getTimezone();
        return $dateStr . "T" . $timeStr . $tzStr;
    }

    /**
     * Check dates.
     *
     * @return bool
     */
    public function isValid() {
        if (! parent::isValid()) {
           return false;
        }

        if (! checkdate("-".$this->getMonth(), $this->getDay(), $this->getYear()) ) {
            throw new Exception("invalid date: " . $this->__toString());
        }
        return true;
    }
}

