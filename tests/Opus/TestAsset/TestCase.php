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
 * @copyright   Copyright (c) 2008-2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\TestAsset;

use DOMDocument;
use DOMXPath;
use Opus\Db2\Database;

use function array_diff;
use function is_dir;
use function rmdir;
use function scandir;
use function unlink;

use const DIRECTORY_SEPARATOR;

/**
 * Superclass for all tests.  Providing maintainance tasks.
 *
 * @category Tests
 */
class TestCase extends SimpleTestCase
{
    private $tables;

    protected function resetDatabase()
    {
        $this->clearTables(true);
    }

    protected function getTables()
    {
        if ($this->tables === null) {
            $conn = Database::getConnection();

            $conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
            $schema = $conn->getSchemaManager();

            $this->tables = [];

            foreach ($schema->listTables() as $table) {
                $this->tables[] = $table->getName();
            }
        }

        return $this->tables;
    }

    /**
     * Empty all listed tables.
     */
    protected function clearTables($always = false, $tables = null)
    {
        // This is needed to workaround the constraints on the parent_id column.
        $conn = Database::getConnection();

        $this->assertNotNull($conn);

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 0;');
        $conn->executeStatement('UPDATE collections SET parent_id = null ORDER BY left_id DESC');

        if ($tables === null) {
            $tables = $this->getTables();
        }

        foreach ($tables as $name) {
            self::clearTable($name, $always);
        }

        $conn->executeStatement('SET FOREIGN_KEY_CHECKS = 1;');
    }

    /**
     * Use the standard database adapter to remove all records from
     * a table.  Check, if the table is really empty.
     *
     * @param string $tablename Name of the table to be cleared.
     */
    protected function clearTable($tablename, $always = false)
    {
        $conn = Database::getConnection();

        $this->assertNotNull($conn);

        $tablename = $conn->quoteIdentifier($tablename);

        $count = $conn->fetchOne('SELECT COUNT(*) FROM ' . $tablename);

        if ($count > 0 || $always) {
            $conn->executeStatement('TRUNCATE ' . $tablename);

            $count = $conn->fetchOne('SELECT COUNT(*) FROM ' . $tablename);
            $this->assertEquals(0, $count, "Table $tablename is not empty!");
        }
    }

    /**
     * Deletes folders in workspace/files in case a test didn't do proper cleanup.
     *
     * @param null $directory
     */
    protected function clearFiles($directory = null)
    {
        if ($directory === null) {
            if (empty(APPLICATION_PATH)) {
                return;
            }
            $filesDir = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'tests' . DIRECTORY_SEPARATOR . 'workspace'
                . DIRECTORY_SEPARATOR . 'files';
            $files    = array_diff(scandir($filesDir), ['.', '..', '.gitignore']);
        } else {
            $filesDir = $directory;
            $files    = array_diff(scandir($filesDir), ['.', '..']);
        }

        foreach ($files as $file) {
            $path = $filesDir . DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $this->clearFiles($path);
            } else {
                unlink($path);
            }
        }

        if ($directory !== null) {
            rmdir($directory);
        }

        return;
    }

    /**
     * Standard setUp method for clearing database.
     */
    protected function setUp()
    {
        parent::setUp();
    }

    protected function prepareXpathFromResultString($resultString)
    {
        $domDocument = new DOMDocument();
        $domDocument->loadXML($resultString);

        $xpath = new DOMXPath($domDocument);

        $namespace = $domDocument->documentElement->namespaceURI;

        if ($namespace !== null) {
            $xpath->registerNamespace('ns', $namespace);
        }

        return $xpath;
    }
}
