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

use Doctrine\DBAL\Query\QueryBuilder;
use Zend_Config;

use function array_walk;
use function count;
use function explode;
use function str_replace;

class Configuration
{
    const TABLE_NAME = 'configuration';

    const COLUMN_OPTION_KEY = 'option_key';

    const COLUMN_OPTION_VALUE = 'option_value';

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
     * TODO use separate function for arrays?
     */
    public function getOption(string $key): string|array|null
    {
        $queryBuilder = $this->getQueryBuilder();

        $queryBuilder->select('option_key', 'option_value')
            ->from(self::TABLE_NAME)
            ->where('option_key LIKE :key')
            ->setParameter('key', $key . '%');

        $result = $queryBuilder->fetchAllKeyValue();

        if (false === $result) {
            return null;
        }

        if (count($result) > 1) {
            $normalized = [];
            array_walk($result, function ($optionValue, $optionKey) use (&$normalized, $key) {
                $shortKey              = str_replace($key . '.', '', $optionKey);
                $normalized[$shortKey] = $optionValue;
            });
            return $normalized;
        }

        return $result[$key];
    }

    public function setOption(string $key, ?string $value): self
    {
        $queryBuilder = $this->getQueryBuilder();

        if ($value !== null) {
            $queryBuilder->insert(self::TABLE_NAME)
                ->values([
                    self::COLUMN_OPTION_KEY   => '?',
                    self::COLUMN_OPTION_VALUE => '?',
                ])
                ->setParameter(0, $key)
                ->setParameter(1, $value);
        } else {
            $queryBuilder->delete(self::TABLE_NAME)
                ->where(self::COLUMN_OPTION_KEY . ' = ?')
                ->setParameter(0, $key);
        }

        $queryBuilder->executeQuery();

        return $this;
    }

    protected function getQueryBuilder(): QueryBuilder
    {
        $conn = Database::getConnection();
        return $conn->createQueryBuilder();
    }
}
