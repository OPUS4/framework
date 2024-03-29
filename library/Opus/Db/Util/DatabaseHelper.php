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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db\Util;

use Doctrine\DBAL\Exception;
use Opus\Model\DbException;
use Zend_Db_Table;

/**
 * Superclass for all tests.  Providing maintainance tasks.
 *
 * @category Tests
 */
class DatabaseHelper
{
    /** @var string[] */
    private $tables;

    public function resetDatabase()
    {
        $this->clearTables(true);
    }

    /**
     * @return array
     * @throws Exception
     */
    public function getTables()
    {
        if ($this->tables === null) {
            $conn = Zend_Db_Table::getDefaultAdapter();

            $this->tables = $conn->listTables();
        }

        return $this->tables;
    }

    /**
     * Empty all listed tables.
     *
     * @param bool          $always Should tables be cleared even if empty
     * @param null|string[] $tables Names of tables for clearing
     */
    public function clearTables($always = false, $tables = null)
    {
        $conn = Zend_Db_Table::getDefaultAdapter();

        if ($conn === null) {
            throw new DbException('Could not get database connection.');
        }

        // This is needed to workaround the constraints on the parent_id column.
        $conn->query('SET FOREIGN_KEY_CHECKS = 0;');
        $conn->query('UPDATE collections SET parent_id = null ORDER BY left_id DESC');

        if ($tables === null) {
            $tables = $this->getTables();
        }

        foreach ($tables as $name) {
            self::clearTable($name, $always);
        }

        $conn->query('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Use the standard database adapter to remove all records from
     * a table.  Check, if the table is really empty.
     *
     * @param string $tablename Name of the table to be cleared.
     * @param bool   $always Should table be cleared even if empty
     */
    public function clearTable($tablename, $always = false)
    {
        $conn = Zend_Db_Table::getDefaultAdapter();

        if ($conn === null) {
            throw new DbException('Could not get database connection.');
        }

        $tablename = $conn->quoteIdentifier($tablename);

        $count = $conn->fetchOne('SELECT COUNT(*) FROM ' . $tablename);

        if ($count > 0 || $always) {
            $conn->query('TRUNCATE ' . $tablename);

            $count = $conn->fetchOne('SELECT COUNT(*) FROM ' . $tablename);

            if ($count > 0) {
                throw new DbException("Table $tablename is not empty!");
            }
        }
    }
}
