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

namespace Opus\Db\Util;

use Exception;
use Opus\Common\LoggingTrait;
use Opus\Db\Documents;
use Opus\Db\TableGateway;
use Zend_Db_Expr;

use function count;

/**
 * Database operations that are not part of normal operations/API.
 */
class Maintenance
{
    use LoggingTrait;

    /** @var string[] Column names for Document date fields */
    private $dateColumns = [
        'completed_date',
        'published_date',
        'thesis_date_accepted',
        'embargo_date',
    ];

    /**
     * Fixes date values that were stored as timestamps because of a bug.
     *
     * - completed_date
     * - published_date
     * - thesis_date_accepted
     * - embargo_date
     *
     * UPDATE documents SET server_date_modified = SUBSTRING_INDEX(server_date_modified, 'T', 1)
     * WHERE server_date_modified LIKE '%T%';
     */
    public function fixDateValues(): void
    {
        $table = TableGateway::getInstance(Documents::class);

        foreach ($this->dateColumns as $column) {
            try {
                $table->update(
                    [$column => new Zend_Db_Expr("SUBSTRING_INDEX({$column}, 'T', 1)")],
                    "{$column} LIKE '%T%'"
                );
            } catch (Exception $e) {
                $this->getLogger()->err($e->getMessage());
            }
        }
    }

    /**
     * Finds date values that were stored as timestamps.
     */
    public function checkDateValues(): array
    {
        $results = [];

        $table = TableGateway::getInstance(Documents::class);

        foreach ($this->dateColumns as $column) {
            $select = $table->select()
                ->from($table, ['id', $column])
                ->where("{$column} LIKE '%T%'");

            $dates = $table->fetchAll($select)->toArray();

            if (count($dates) > 0) {
                $results[$column] = count($dates);
            }
        }

        return $results;
    }
}
