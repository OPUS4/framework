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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for date and time storage.
 *
 * @category    Framework
 * @package     Opus
 */
class Opus_Date extends Opus_Model_Abstract {

    const TIMEDATE_REGEXP = '/^(\d{1,4})-(\d{1,2})-(\d{1,2})T(\d{1,2}):(\d{1,2}):(\d{1,2})([A-Za-z]+|[+-][\d:]+)$/';
    const DATEONLY_REGEXP = '/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/';

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
        else if (is_string($value) and preg_match(self::TIMEDATE_REGEXP, $value)) {
            $this->setFromString($value);
        }
        else if (is_string($value) and preg_match(self::DATEONLY_REGEXP, $value)) {
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
     * Initialize model by adding the corresponding fields
     * Year, Month, Day, Hour, Minute, Second, Timezone, and UnixTimestamp.
     *
     * @return void
     */
    protected function _init() {
        $fields = array(
            'Year', 'Month', 'Day',
            'Hour', 'Minute', 'Second');

        foreach ($fields as $fieldName) {
            $field = new Opus_Model_Field($fieldName);
            $field->setValidator(new Zend_Validate_Int);
            $this->addField($field);
        }

        $field = new Opus_Model_Field('Timezone');
        $this->addField($field);

        $field = new Opus_Model_Field('UnixTimestamp');
        $this->addField($field);
    }

    /**
     * Returns a Zend_Date instance properly set up with
     * date values as described in the Models fields.
     *
     * @return Zend_Date
     */
    public function getZendDate() {
        $datearray = array(
            'year' => $this->getYear(),
            'month' => $this->getMonth(),
            'day' => $this->getDay(),
            'hour' => $this->getHour(),
            'minute' => $this->getMinute(),
            'second' => $this->getSecond(),
            'timezone' => $this->getTimezone());

        foreach ($datearray as $key => $value) {
            if (is_null($value)) {
                unset($datearray[$key]);
            }
        }

        return new Zend_Date($datearray);
    }

    /**
     * Returns a DateTime instance properly set up with
     * date values as described in the Models fields.
     *
     * @return DateTime
     */
    public function getDateTime() {
        $date = $this->__toString();
        if ($this->isDateOnly()) {
            $date = substr($date, 0, 10) . 'T00:00:00';
            return DateTime::createFromFormat('Y-m-d\TH:i:s', $date);
        }

        return DateTime::createFromFormat('Y-m-d\TH:i:se', $date);
    }

    /**
     * Set date and time values from DateTime instance.
     *
     * @param DateTime $date DateTime instance to use.
     * @return Opus_Date provide fluent interface.
     */
    public function setDateTime($datetime) {
        if (!$datetime instanceof DateTime) {
            throw new InvalidArgumentException('Invalid DateTime object.');
        }

        $this->setYear($datetime->format("Y"));
        $this->setMonth($datetime->format("m"));
        $this->setDay($datetime->format("d"));
        $this->setHour($datetime->format("H"));
        $this->setMinute($datetime->format("i"));
        $this->setSecond($datetime->format("s"));

        $timeZone = $datetime->format("P");
        $this->setTimezone($timeZone === '+00:00' ? 'Z' : $timeZone);
        $this->setUnixTimestamp($datetime->getTimestamp());

        return $this;
    }

    /**
     * Set date values from DateTime instance; shortcut for date-setting only.
     *
     * @param DateTime $date DateTime instance to use.
     * @return Opus_Date provide fluent interface.
     */
    public function setDateOnly($datetime) {
        $this->setDateTime($datetime);
        $this->setHour(null);
        $this->setMinute(null);
        $this->setSecond(null);
        $this->setTimezone(null);

        return $this;
    }

    /**
     * Checks, if the current date object also defines time/time zone values.
     *
     * @return bool
     */
    public function isDateOnly() {
        return is_null($this->getHour())
                || is_null($this->getMinute())
                || is_null($this->getSecond())
                || is_null($this->getTimezone());
    }

    /**
     * Set date values from Zend_Date instance.
     *
     * @param Zend_Date $date Zend_Date instance to use.
     * @return void
     *
     * @deprecated
     */
    public function setZendDate(Zend_Date $date) {
        $this->setFromString($date->get(Zend_Date::ISO_8601));
    }

    /**
     * Set up date model from string representationo of a date.
     * Date parsing depends on current set locale date format.
     *
     * @param  string $date Date string to set.
     * @return void
     */
    public function setFromString($date) {
        if (true === empty($date)) {
            throw new InvalidArgumentException('Empty date string passed.');
        }

        if (preg_match(self::TIMEDATE_REGEXP, $date)) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:se', $date);
            $this->setDateTime($datetime);
        }
        else if (preg_match(self::DATEONLY_REGEXP, $date)) {
            $date = substr($date, 0, 10) . 'T00:00:00';
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', $date);
            $this->setDateOnly($datetime);
        }
        else {
            throw new InvalidArgumentException('Invalid date-time string.');
        }
    }

    /**
     * Set the current date, time and timezone.
     *
     * @return void
     */
    public function setNow() {
        $this->setDateTime(new DateTime());
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
        $dateStr = sprintf("%04d-%02d-%02d", $this->getYear(), $this->getMonth(), $this->getDay());
        if ($this->isDateOnly()) {
            return $dateStr;
        }

        $timeStr = sprintf("%02d:%02d:%02d", $this->getHour(), $this->getMinute(), $this->getSecond());
        $tzStr   = $this->getTimezone();
        return $dateStr . "T" . $timeStr . $tzStr;
    }

    /**
     * Overload isValid to for additional date checks.
     *
     * @return bool
     */
    public function isValid() {
        return checkdate($this->getMonth(), $this->getDay(), $this->getYear()) and parent::isValid();
    }

}

