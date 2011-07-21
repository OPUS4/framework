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
        } else
        if ($value instanceof DateTime) {
            $this->setDateTime($value);
        } else
        if (is_string($value)) {
            $this->setFromString($value);
        } else
        if ($value instanceof Opus_Date) {
            $this->updateFrom($value);
        } else {
            // set all fields to 0
            $this->setYear(0)
                ->setMonth(0)
                ->setDay(0)
                ->setHour(0)
                ->setMinute(0)
                ->setSecond(0)
                ->setTimezone('UTC')
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
    
        foreach($fields as $fieldName) {
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
     * Sets timezone of Date object.  If input is not of type DateTimeZone, then
     * it must be a valid timezone that works with
     *     new DateTimeZone($timezone)
     *
     * @param string|DateTimeZone $timezone
     * @return Opus_Date Provide fluent interface.
     */
    public function setTimezone($timezone = 'UTC') {
        if (!$timezone instanceof DateTimeZone) {
            $timezone = @timezone_open($timezone);
        }

        if (!$timezone instanceof DateTimeZone) {
            throw new InvalidArgumentException('Could not get DateTimeZone object from timezone parameter.');
        }

        // TODO: We need array($timezone) to workaround model bug.
        // TODO: DateTimeZone == DateTimeZone always returns true in weak equal check!  Why?
        parent::setTimezone(array($timezone));
        return $this;
    }

    /**
     * Returns a DateTime instance properly set up with
     * date values as described in the Models fields.
     *
     * @return DateTime
     */
    public function getDateTime() {
        $datetime = new DateTime(null, $this->getTimezone());
        $datetime->setDate($this->getYear(), $this->getMonth(), $this->getDay());
        $datetime->setTime($this->getHour(), $this->getMinute(), $this->getSecond());
        return $datetime;
    }

    /**
     * Set date values from DateTime instance.
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

        $this->setTimezone($datetime->getTimezone());
        $this->setUnixTimestamp($datetime->getTimestamp());

        return $this;
    }

    /**
     * Set date values from Zend_Date instance.
     *
     * @param Zend_Date $date Zend_Date instance to use.
     * @return void
     */    
    public function setZendDate(Zend_Date $date) {
        $datearray = $date->toArray();
        $this->setYear($datearray['year']);
        $this->setMonth($datearray['month']);
        $this->setDay($datearray['day']);
        $this->setHour($datearray['hour']);
        $this->setMinute($datearray['minute']);
        $this->setSecond($datearray['second']);
        $this->setTimezone($datearray['timezone']);
        $this->setUnixTimestamp($date->getTimestamp());
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

        $datetime = null;
        if (strlen($date) >= 18) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:sP', $date);
        }
        else if (strlen($date) >= 10) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:s', substr($date, 0, 10) . 'T00:00:00');
        }

        $this->setDateTime($datetime);
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
     *
     * @return string ISO 8601 date string.
     */
    public function __toString() {
        return $this->getDateTime()->format('Y-m-d\TH:i:sP');
    }

}

