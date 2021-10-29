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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Update\Plugin;

use Opus\Database;
use Opus\Util\ConsoleColors;
use PDO;

use function explode;
use function in_array;
use function strtolower;

/**
 * phpcs:disable
 */
class DatabaseCharset extends AbstractUpdatePlugin
{
    private $pdo;

    private $database;

    public function run()
    {
        $this->convertDatabase();

        $colors = new ConsoleColors();

        $this->log();
        $this->log($colors->yellow(
            'After converting the database to \'utf8mb4\' you should run \'repair\' and \'optimize\' on your database.'
            . ' The following command performs these operations for all databases. See your database documentation for more'
            . ' information.'
        ));
        $this->log();
        $this->log($colors->yellow(
            '$ mysqlcheck -u root -p --auto-repair --optimize --all-databases'
        ));
        $this->log();
    }

    /**
     * Performs conversion to 'utf8mb4'.
     *
     * @return mixed|void
     */
    public function convertDatabase()
    {
        $this->log('Converting database to character set \'utf8mb4\' ...');

        $pdo = $this->getPdo();

        $pdo->beginTransaction();

        $database = $this->getDatabase();

        // set character set and collation for entire database
        $database->execWithoutDbName(
            'ALTER DATABASE `' . $database->getName() . '`'
            . ' character set = ' . Database::DEFAULT_CHARACTER_SET
            . ' collate = ' . Database::DEFAULT_COLLATE
        );

        $tables = $this->getAllTables();

        foreach ($tables as $table) {
            $this->convertTable($table);
        }

        $pdo->commit();

        $this->log('Conversion to \'utf8mb4\' complete.');
    }

    /**
     * Returns database object.
     *
     * @return PDO
     */
    public function getPdo()
    {
        if ($this->pdo === null) {
            $database  = $this->getDatabase();
            $this->pdo = $database->getPdo($database->getName());
        }

        return $this->pdo;
    }

    public function getDatabase()
    {
        if ($this->database === null) {
            $this->database = new Database();
        }

        return $this->database;
    }

    /**
     * Updates table to utf8mb4 if possible.
     *
     * @param $table
     * @return bool true - if table was converted
     */
    public function convertTable($table)
    {
        $pdo = $this->getPdo();

        $result = $pdo->query("SHOW FULL COLUMNS FROM `$table`")->fetchAll();

        if (! $result) {
            $this->log("Could not retrieve column info for table '$table'.");
            return false;
        }

        foreach ($result as $column) {
            if (isset($column['Collation'])) {
                [$charset] = explode('_', $column['Collation']);

                $charset = strtolower($charset);

                if (! in_array($charset, ['utf8', 'utf8mb4'])) {
                    $this->log("Table '$table' Column '$column' is using '$charset'. Skip conversion for table.");
                    return false;
                }
            }
        }

        $details = $pdo->query("SHOW TABLE STATUS LIKE '$table'")->fetch();

        if (! $details) {
            $this->log("Could not retrieve info for table '$table'. Skip conversion.");
            return false;
        }

        [$tableCharset] = explode('_', $details['Collation']);

        $tableCharset = strtolower($tableCharset);

        if ('utf8mb4' === $tableCharset) {
            $this->log("Table '$table' is already using 'utf8mb4'.");
            return true;
        }

        $this->log("Converting '$table' to 'utf8mb4'.");

        return $pdo->query(
            "ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        )->execute();
    }

    /**
     * Returns names of all tables.
     *
     * @return array Names of tables
     */
    public function getAllTables()
    {
        $pdo = $this->getPdo();

        return $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    }
}
