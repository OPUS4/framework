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
 * @copyright   Copyright (c) 2026, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db2;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Query\QueryBuilder;
use Zend_Config;

use function array_merge;
use function array_walk;
use function count;
use function explode;
use function is_array;
use function str_replace;

/**
 * Storing and retrieving configuration options in and from the database.
 *
 * The keys are like entries in INI files. OPUS 4 is using INI files for
 * the default configuration and local settings. The database is used for
 * local settings that can be edited in the administration UI.
 *
 * TODO handle DBAL Exception
 * TODO remove dependency on Zend_Config
 */
class Configuration
{
    const TABLE_NAME = 'configuration';

    const COLUMN_OPTION_KEY = 'option_key';

    const COLUMN_OPTION_VALUE = 'option_value';

    /**
     * Returns all settings in the database as Zend_Config object.
     */
    public function getConfig(): Zend_Config
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->select('option_key', 'option_value')
            ->from(self::TABLE_NAME);

        $result = $queryBuilder->fetchAllKeyValue();

        if (false === $result || count($result) === 0) {
            $options = [];
        } else {
            $options = [];

            array_walk($result, function ($optionValue, $optionKey) use (&$options) {
                $temp = &$options;
                foreach (explode('.', $optionKey) as $level) {
                    $temp = &$temp[$level];
                }
                $temp = $optionValue;
            });
        }

        return new Zend_Config($options);
    }

    /**
     * Clears all settings in the database.
     */
    public function reset(): void
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->delete(self::TABLE_NAME);

        $queryBuilder->executeQuery();
    }

    /**
     * Remove all matching keys.
     *
     * If the exact key exists, it is removed. If matching keys with a dot at the end exist, an array of options, they
     * are removed.
     */
    public function remove(string $key): void
    {
        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->delete(self::TABLE_NAME)
            ->where(self::COLUMN_OPTION_KEY . ' = :key')
            ->setParameter('key', $key);
        $queryBuilder->executeQuery();

        $queryBuilder = $this->getQueryBuilder();
        $queryBuilder->delete(self::TABLE_NAME)
            ->where(self::COLUMN_OPTION_KEY . ' LIKE :key')
            ->setParameter('key', $key . '.%');
        $queryBuilder->executeQuery();
    }

    /**
     * Imports the options in a Zend_Config object into the database.
     *
     * Optionally the existing configuration in the database is cleared.
     */
    public function import(Zend_Config $config, bool $reset = false): void
    {
        $options = $this->arr2ini($config->toArray());

        if ($reset) {
            $this->reset();
        }

        foreach ($options as $optionKey => $optionValue) {
            $this->setOption($optionKey, $optionValue);
        }
    }

    /**
     * Converts an array into the INI format, where the levels of the array are connected with dots.
     */
    protected function arr2ini(array $config, string $prefix = ''): array
    {
        $output = [];

        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $output = array_merge($output, $this->arr2ini($value, $prefix . $key . '.'));
            } else {
                $output["{$prefix}{$key}"] = $value;
            }
        }

        return $output;
    }

    /**
     * Returns the value of an option.
     *
     * Simple options are returned as strings. Unknown options return NULL. If there
     * is an array option, with dot at the end, the array is returned.
     *
     * Array options are for instance:
     *
     * i18n.languages.local.cmn =
     * i18m.languages.local.wuu =
     *
     * The key 'i18n.languages.local' returns array with 'cmn' und 'wuu' as keys.
     */
    public function getOption(string $key, bool $returnAllWithPrefix = false): string|array|null
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->select('option_key', 'option_value')
            ->from(self::TABLE_NAME)
            ->where('option_key LIKE :key')
            ->setParameter('key', $key . '%');

        $result = $queryBuilder->fetchAllKeyValue();

        // Return null if no results are found
        if (false === $result || count($result) === 0) {
            return null;
        }

        // Return all values that start with that key
        if ($returnAllWithPrefix) {
            return $result;
        }

        // If multiple results, check to see if it is an array option
        if (count($result) > 1) {
            $normalized = [];
            array_walk($result, function ($optionValue, $optionKey) use (&$normalized, $key) {
                // Remove prefix from keys that have a dot after the prefix (array option)
                $shortKey = str_replace($key . '.', '', $optionKey);

                // If key was shortened store as normalized
                if ($shortKey !== $optionKey) {
                    $normalized[$shortKey] = $optionValue;
                }
            });
            if (count($normalized) === 0) {
                // No matching array options were found
                return null;
            }
            return $normalized;
        }

        return $result[$key];
    }

    /**
     * Adds, updates or removes an option.
     *
     * If the new value is null, the option is removed.
     */
    public function setOption(string $key, ?string $value): self
    {
        $queryBuilder = $this->getQueryBuilder();

        if ($value !== null) {
            try {
                // Try inserting the option
                $queryBuilder->insert(self::TABLE_NAME)
                    ->values([
                        self::COLUMN_OPTION_KEY   => '?',
                        self::COLUMN_OPTION_VALUE => '?',
                    ])
                    ->setParameter(0, $key)
                    ->setParameter(1, $value);

                $queryBuilder->executeQuery();
                return $this;
            } catch (DBALException $e) {
                // If insert fails, try updating the option
                $queryBuilder = $this->getQueryBuilder();
                $queryBuilder->update(self::TABLE_NAME)
                    ->set(self::COLUMN_OPTION_VALUE, ':value')
                    ->where(self::COLUMN_OPTION_KEY . ' = :key')
                    ->setParameter('key', $key)
                    ->setParameter('value', $value);
            }
        } else {
            // Remove option if new value is NULL
            $queryBuilder->delete(self::TABLE_NAME)
                ->where(self::COLUMN_OPTION_KEY . ' = ?')
                ->setParameter(0, $key);
        }

        $queryBuilder->executeQuery();

        return $this;
    }

    /**
     * TODO move to parent or component class?
     */
    protected function getQueryBuilder(): QueryBuilder
    {
        $conn = Database::getConnection();
        return $conn->createQueryBuilder();
    }
}
