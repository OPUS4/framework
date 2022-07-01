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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use Opus\Common\Model\ComparableInterface;
use Opus\Common\Model\ModelException;
use Opus\Model\AbstractModel;
use Opus\Model\DateField;
use Opus\Model\Field;
use Opus\Model\UnixTimestampField;
use Opus\Security\SecurityException;
use Zend_Validate_Int;

use function checkdate;
use function get_class;
use function gmdate;
use function htmlspecialchars;
use function is_array;
use function is_int;
use function is_integer;
use function is_string;
use function preg_match;
use function sprintf;
use function substr;

/**
 * Domain model for date and time storage.
 *
 * UnixTimestamp and the other fields are different representations of the same value. If any of the element, like year,
 * month or date, is modified, the UnixTimestamp needs to be updated or vice versa.
 *
 * The information is stored in a DateTime object. However the Opus\Model\Field objects are still there to present the
 * old API.
 *
 * If a UnixTimestamp is set it override all the other fields. The string presention of a UNIX timestamp depends on the
 * time zone. Therefore a UNIX timestamp is always interpreted for UTC (Z). Otherwise tests would provide different
 * results depending on the local timezone.
 *
 * When a UNIX timestamp is set it always create
 *
 * phpcs:disable
 *
 * @method void setYear(integer $year)
 * @method integer getYear()
 * @method void setMonth(integer $month)
 * @method integer getMonth()
 * @method void setDay(integer $day)
 * @method integer getDay()
 * @method void setHour(integer $hour)
 * @method integer getHour()
 * @method void setMinute(integer $minute)
 * @method integer getMinute()
 * @method void setSecond(integer $second)
 * @method integer getSecond()
 * @method void setTimezone(string $timezone)
 * @method string getTimezone()
 *
 * TODO remove Field objects
 * TODO extend Opus\Date with functions to provide string appropriate for Locale
 */
class Date extends AbstractModel implements ComparableInterface
{
    const FIELD_YEAR     = 'Year';
    const FIELD_MONTH    = 'Month';
    const FIELD_DAY      = 'Day';
    const FIELD_HOUR     = 'Hour';
    const FIELD_MINUTE   = 'Minute';
    const FIELD_SECOND   = 'Second';
    const FIELD_TIMEZONE = 'Timezone';

    /**
     * Regular expression for complete time string.
     */
    const TIMEDATE_REGEXP = '/^(\d{1,4})-(\d{1,2})-(\d{1,2})T(\d{1,2}):(\d{1,2}):(\d{1,2})([A-Za-z]+|[+-][\d:]+)$/';

    /**
     * Regular expression for time string with just a date.
     */
    const DATEONLY_REGEXP = '/^(\d{1,4})-(\d{1,2})-(\d{1,2})$/';

    const DATETIME_FORMAT_FULL = ''; // TODO use

    const DATETIME_FORMAT_DATE_ONLY = ''; // TODO use

    /** @var array */
    private $values = [];

    /**
     * Set up model with given value or with the current timestamp.
     *
     * @param null|DateTime|Date|string $value (Optional) Some sort of date representation.
     */
    public function __construct($value = null)
    {
        parent::__construct();

        if ($value instanceof DateTime) {
            $this->setDateTime($value);
        } elseif (is_string($value) and preg_match(self::TIMEDATE_REGEXP, $value)) {
            $this->setFromString($value);
        } elseif (is_string($value) and preg_match(self::DATEONLY_REGEXP, $value)) {
            $this->setFromString($value);
        } elseif ($value instanceof Date) {
            $this->updateFrom($value);
        } elseif (is_integer($value)) {
            $this->setTimestamp($value);
        } else {
            $this->resetValues();
        }
    }

    protected function resetValues()
    {
        $this->values = [
            self::FIELD_YEAR     => null,
            self::FIELD_MONTH    => null,
            self::FIELD_DAY      => null,
            self::FIELD_HOUR     => null,
            self::FIELD_MINUTE   => null,
            self::FIELD_SECOND   => null,
            self::FIELD_TIMEZONE => null,
        ];
    }

    /**
     * Initialize model by adding the corresponding fields
     * Year, Month, Day, Hour, Minute, Second, Timezone, and UnixTimestamp.
     */
    protected function init()
    {
        $this->resetValues();

        foreach ($this->values as $fieldName => $value) {
            $field = new DateField($fieldName, $this);
            if ($fieldName !== 'Timezone') {
                $field->setValidator(new Zend_Validate_Int());
            }
            $this->addField($field);
        }

        $field = new UnixTimestampField('UnixTimestamp', $this);
        $this->addField($field);
    }

    /**
     * Returns a DateTime instance properly set up with
     * date values as described in the Models fields.
     *
     * @return DateTime
     */
    public function getDateTime($timezone = null)
    {
        if (! $this->isValidDate()) {
            return null;
        }

        $date = $this->__toString();
        if ($this->isDateOnly()) {
            if ($timezone !== null) {
                $date = substr($date, 0, 10) . 'T00:00:00' . $timezone;
                return DateTime::createFromFormat('Y-m-d\TH:i:se', $date);
            } else {
                $date = substr($date, 0, 10) . 'T00:00:00';
                return DateTime::createFromFormat('Y-m-d\TH:i:s', $date);
            }
        }

        $dateTime = DateTime::createFromFormat('Y-m-d\TH:i:se', $date);
        if ($timezone !== null) {
            if ($timezone === 'Z') {
                $timezone = 'UTC';
            }
            $dateTime->setTimezone(new DateTimeZone($timezone));
        }
        return $dateTime;
    }

    /**
     * Set date and time values from DateTime instance.
     *
     * @param DateTime $date DateTime instance to use.
     * @return $this provide fluent interface.
     */
    public function setDateTime($datetime)
    {
        if (! $datetime instanceof DateTime) {
            throw new InvalidArgumentException('Invalid DateTime object.');
        }

        $this->values[self::FIELD_YEAR]   = $datetime->format("Y");
        $this->values[self::FIELD_MONTH]  = $datetime->format("m");
        $this->values[self::FIELD_DAY]    = $datetime->format("d");
        $this->values[self::FIELD_HOUR]   = $datetime->format("H");
        $this->values[self::FIELD_MINUTE] = $datetime->format("i");
        $this->values[self::FIELD_SECOND] = $datetime->format("s");

        $timeZone                           = $datetime->format("P");
        $this->values[self::FIELD_TIMEZONE] = $timeZone === '+00:00' ? 'Z' : $timeZone;

        return $this;
    }

    /**
     * Set date values from DateTime instance; shortcut for date-setting only.
     *
     * @param DateTime $date DateTime instance to use.
     * @return $this provide fluent interface.
     */
    public function setDateOnly($datetime)
    {
        $this->setDateTime($datetime);
        $this->values[self::FIELD_HOUR]     = null;
        $this->values[self::FIELD_MINUTE]   = null;
        $this->values[self::FIELD_SECOND]   = null;
        $this->values[self::FIELD_TIMEZONE] = null;

        return $this;
    }

    /**
     * Checks, if the current date object also defines time/time zone values.
     *
     * @return bool
     */
    public function isDateOnly()
    {
        return $this->values[self::FIELD_HOUR] === null
                || $this->values[self::FIELD_MINUTE] === null
                || $this->values[self::FIELD_SECOND] === null
                || $this->values[self::FIELD_TIMEZONE] === null;
    }

    /**
     * Set up date model from string representationo of a date.
     * Date parsing depends on current set locale date format.
     *
     * @param  string $date Date string to set.
     */
    public function setFromString($date)
    {
        if (true === empty($date)) {
            throw new InvalidArgumentException('Empty date string passed.');
        }

        if (preg_match(self::TIMEDATE_REGEXP, $date)) {
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:se', $date);
            $this->setDateTime($datetime);
        } elseif (preg_match(self::DATEONLY_REGEXP, $date)) {
            $date     = substr($date, 0, 10) . 'T00:00:00Z';
            $datetime = DateTime::createFromFormat('Y-m-d\TH:i:se', $date);
            $this->setDateOnly($datetime);
        } else {
            throw new InvalidArgumentException('Invalid date-time string.');
        }
    }

    /**
     * Set the current date, time and timezone.
     */
    public function setNow()
    {
        $this->setDateTime(new DateTime());
    }

    /**
     * Creates Opus\Date object set to the time of creation.
     *
     * @return Date
     */
    public static function getNow()
    {
        $date = new Date();
        $date->setNow();
        return $date;
    }

    /**
     * Return ISO 8601 string representation of the date.  For instance:
     *    2011-02-28T23:59:59[+-]01:30
     *    2011-02-28T23:59:59(Z|UTC|...)
     *    2011-02-28                    (if some time values/time zone are null)
     *
     * @return string ISO 8601 date string.
     *
     * TODO how to deal with invalid
     */
    public function __toString()
    {
        $dateStr = sprintf(
            '%04d-%02d-%02d',
            $this->values[self::FIELD_YEAR],
            $this->values[self::FIELD_MONTH],
            $this->values[self::FIELD_DAY]
        );
        if ($this->isDateOnly()) {
            return $dateStr;
        }

        $timeStr = sprintf(
            '%02d:%02d:%02d',
            $this->values[self::FIELD_HOUR],
            $this->values[self::FIELD_MINUTE],
            $this->values[self::FIELD_SECOND]
        );
        $tzStr   = $this->values[self::FIELD_TIMEZONE];
        return $dateStr . "T" . $timeStr . $tzStr;
    }

    /**
     * Returns string matching Zend_Date::getIso format.
     * @return string
     *
     * TODO should this format be changed - Does it differ from __toString
     */
    public function getIso()
    {
        return $this->getDateTime()->format(DateTime::RFC3339);
    }

    /**
     * Overload isValid to for additional date checks.
     *
     * @return bool
     *
     * TODO is call to parent::isValid() necessary? how can endless recursion be resolved?
     */
    public function isValid()
    {
        return $this->isValidDate() and parent::isValid();
    }

    /**
     * Checks if date is valid.
     *
     * This function is used because the regular isValid function calls the parent::isValid function which checks the
     * values of all the fields which leads to an endless recursion.
     *
     * @return bool
     */
    public function isValidDate()
    {
        return checkdate(
            $this->values[self::FIELD_MONTH],
            $this->values[self::FIELD_DAY],
            $this->values[self::FIELD_YEAR]
        );
    }

    /**
     * Synchronize dependent fields.
     *
     * @param Field      $field
     * @param array|null $values
     * @return parent
     *
     * TODO If multiple values are set the unix timestamp is updated several times. It might make more sense to generate it on demand.
     */
    public function updateFromArray($data)
    {
        if (is_array($data)) {
            parent::updateFromArray($data);
        } else {
            if (is_int($data)) {
                $this->setTimestamp($data);
            } else {
                $this->setFromString($data);
            }
        }
    }

    public function updateValue($name, $value)
    {
        $this->values[$name] = $value;
    }

    public function getValue($name)
    {
        return $this->values[$name];
    }

    public function clear()
    {
        $this->resetValues();
    }

    /**
     * Compares to another Opus\Date objekt.
     *
     * @param $date2 Date object
     * @throws ModelException
     */
    public function compare($date)
    {
        if ($date === null) {
            // a date is always "larger than" null
            return 1;
        }

        if (! $date instanceof Date) {
            $class     = self::class;
            $dateClass = get_class($date);
            throw new ModelException("Cannot compare $dateClass with $class object.");
        }

        $thisDateTime = $this->getDateTime('Z');

        if ($thisDateTime === null) {
            $dateStr = htmlspecialchars($this->__toString());
            throw new ModelException("Date '$dateStr' is invalid.");
        }

        $dateTime = $date->getDateTime('Z');

        if ($dateTime === null) {
            $dateStr = htmlspecialchars($date->__toString());
            throw new ModelException("Date '$dateStr' is invalid.");
        }

        $thisTimestamp = $thisDateTime->getTimestamp();
        $timestamp     = $dateTime->getTimestamp();

        if ($thisTimestamp === $timestamp) {
            return 0; // equal
        } elseif ($thisTimestamp < $timestamp) {
            return -1; // less than
        } else {
            return 1; // larger than
        }
    }

    /**
     * Checks if given date is later.
     *
     * @param Date $date
     * @return bool True if given date is later than parameter date
     * @throws ModelException
     */
    public function isLater($date)
    {
        return $this->compare($date) === 1;
    }

    /**
     * Returns a UNIX timestamp if the value is valid.
     *
     * The UnixTimestamp field may return null if only a date is stored, but not a time.
     *
     * @return int
     */
    public function getTimestamp()
    {
        $dateTime = $this->getDateTime('Z');
        if ($dateTime !== null) {
            return $dateTime->getTimestamp();
        } else {
            return null;
        }
    }

    /**
     * Updates all values for the provided timestamp and UTC.
     *
     * UTC is used, because the string presentation of a timestamp depends on the time zone.
     *
     * @param $value
     *
     * TODO extend function to provide time zone as second parameter?
     */
    public function setTimestamp($value)
    {
        if ($value === null) {
            $this->clear();
        } else {
            $dateTime = gmdate('Y-m-d\TH:i:s\Z', $value);
            $this->setFromString($dateTime);
        }
    }

    /**
     * Function declared here to mark it as deprecated.
     *
     * The UnixTimestamp is a read-only field and should not be set.
     *
     * @deprecated
     *
     * @param $value
     * @throws ModelException
     * @throws SecurityException
     */
    public function setUnixTimestamp($value)
    {
        parent::setUnixTimestamp($value);
    }
}
