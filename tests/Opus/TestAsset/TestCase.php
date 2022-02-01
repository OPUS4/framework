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
use Opus\Config;
use Opus\Util\DatabaseHelper;

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
class TestCase extends AbstractSimpleTestCase
{
    /** @var DatabaseHelper */
    private $databaseHelper;

    /**
     * @return DatabaseHelper
     */
    protected function getDatabaseHelper()
    {
        if ($this->databaseHelper === null) {
            $this->databaseHelper = new DatabaseHelper();
        }

        return $this->databaseHelper;
    }

    protected function resetDatabase()
    {
        $this->getDatabaseHelper()->clearTables(true);
    }

    /**
     * Empty all listed tables.
     *
     * @param bool          $always Should tables be cleared even if empty
     * @param null|string[] $tables Names of tables for clearing
     */
    protected function clearTables($always = false, $tables = null)
    {
        $this->getDatabaseHelper()->clearTables($always, $tables);
    }

    /**
     * Use the standard database adapter to remove all records from
     * a table.  Check, if the table is really empty.
     *
     * @param string $tablename Name of the table to be cleared.
     * @param bool   $always Should table be cleared even if empty
     */
    protected function clearTable($tablename, $always = false)
    {
        $this->getDatabaseHelper()->clearTable($tablename, $always);
    }

    /**
     * Deletes folders in workspace/files in case a test didn't do proper cleanup.
     *
     * @param null|string $directory
     */
    protected function clearFiles($directory = null)
    {
        if ($directory === null) {
            if (empty(APPLICATION_PATH)) {
                return;
            }
            $config   = Config::get();
            $filesDir = $config->workspacePath . '/files';
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
    }

    /**
     * Standard setUp method for clearing database.
     */
    protected function setUp()
    {
        parent::setUp();

        // Test are running in the same process, EntityManager needs to be reset to make them independent
        Database::resetEntityManager();
    }

    /**
     * @param string $resultString
     * @return DOMXPath
     */
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
