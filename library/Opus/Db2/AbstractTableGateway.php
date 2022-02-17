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
 * @copyright   Copyright (c) 2009-2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Db
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Db2;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

use function implode;
use function is_array;
use function rtrim;

/**
 * Implements the singleton pattern for table gateway classes.
 *
 * TODO nothing preventing creation of table classes directly
 */
abstract class AbstractTableGateway
{
    private $connection;

    /**
     * Returns the database connection.
     *
     * @return Connection
     */
    public function getConnection()
    {
        if ($this->connection === null) {
            $this->connection = Database::getConnection();
        }

        return $this->connection;
    }

    /**
     * Returns a new SQL query builder instance for the connection.
     *
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        $conn = $this->getConnection();

        return $conn->createQueryBuilder();
    }

    /**
     * Insert given array into table and ignore duplicate entries.  (Silently
     * skipping insert, if unique constraint has been violated.)
     *
     * If an update occurs instead of an insert the lastInserId() function normally does not return the ID of the
     * modified row.
     *
     * @param string $table
     * @param string $primary
     * @param array  $data
     */
    public function insertIgnoreDuplicate($table, $primary, $data)
    {
        $conn = $this->getConnection();

        $quotedKeys   = [];
        $quotedValues = [];
        $update       = '';

        foreach ($data as $key => $value) {
            $quotedKey      = $conn->quoteIdentifier($key);
            $quotedKeys[]   = $quotedKey;
            $quotedValues[] = $conn->quote($value);
            $update        .= " $quotedKey=VALUES($quotedKey),";
        }

        // if an update is performed instead of an insert this is necessary for lastInsertId() to provide a value
        $primaryKey = $primary;

        if ($primaryKey !== null && ! is_array($primaryKey)) {
            // no support for composite keys
            $update .= " $primaryKey=LAST_INSERT_ID($primaryKey)";
        } else {
            $update = rtrim($update, ',');
        }

        $insert = 'INSERT INTO ' . $table . ' (' . implode(', ', $quotedKeys) . ') '
                . ' VALUES (' . implode(', ', $quotedValues) . ") ON DUPLICATE KEY UPDATE $update";

        $stmt = $conn->prepare($insert);

        $stmt->executeStatement();
    }

    /**
     * Delete the table row that matches the given array.  (Silently ignoring
     * deletes of non-existent entries.)
     *
     * @param string $table
     * @param array  $data
     */
    public function deleteWhereArray($table, $data)
    {
        $conn = $this->getConnection();

        $queryClauses = [];

        foreach ($data as $key => $value) {
            $quotedKey   = $conn->quoteIdentifier($key);
            $quotedValue = $conn->quote($value);
            if (is_array($value)) {
                $queryClauses[] = $quotedKey . ' IN (' . $quotedValue . ')';
            } else {
                $queryClauses[] = $quotedKey . " = " . $quotedValue;
            }
        }

        $where = implode(" AND ", $queryClauses);
        $conn->delete($table, $where);
    }
}