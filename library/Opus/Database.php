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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2014-2016, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Class for basic database operations.
 *
 * This class is used to drop and create the database schema and also import the master data and the test data.
 *
 * TODO maybe move to Opus_Util_Database?
 * TODO logging
 * TODO use for createdb.sh
 */
class Opus_Database {

    /**
     * Path to current OPUS 4 SQL schema.
     */
    const SCHEMA_PATH = '/db/schema/opus4schema.sql';

    /**
     * Path to folder containing SQL files for updates.
     */
    const UPDATE_SCRIPTS_PATH = '/db/schema';

    /**
     * @var Zend_Config
     */
    private $_config;

    /**
     * @var Zend_Log
     */
    private $_logger;

    /**
     * @var int
     */
    private $_latestVersion = 0;

    /**
     * @return string Name of database
     */
    public function getName() {
        $config = $this->getConfig();

        return $config->db->params->dbname;
    }

    /**
     * Returns name of admin user.
     * @return mixed
     */
    public function getUsername() {
        $config = $this->getConfig();

        return $config->opusdb->params->admin->name;
    }

    /**
     * Returns password for admin user.
     * @return mixed
     */
    public function getPassword() {
        $config = $this->getConfig();

        return $config->opusdb->params->admin->password;
    }

    /**
     * Creates database schema.
     */
    public function create() {
        $dbName = $this->getName();
        $sql = "CREATE SCHEMA IF NOT EXISTS ${dbName} DEFAULT CHARACTER SET = utf8 DEFAULT COLLATE = utf8_general_ci;";
        $this->execWithoutDbName($sql);
    }

    /**
     * Imports SQL file or folder containing SQL files.
     * @param $path string Path to file or folder
     * @throws Exception
     */
    public function import($path) {
        if (!is_readable($path)) {
            throw new Exception('Path not readable');
        }

        $files = array();

        if (is_dir($path)) {
            $files = $this->getSqlFiles($path);
        }
        else {
            $files[] = $path;
        }

        foreach ($files as $file) {
            // TODO make output optional
            $name = substr($file, strlen(APPLICATION_PATH) + 1);
            echo("Importing '$name' ... ");
            $sql = file_get_contents($file);
            $this->getLogger()->info("Import SQL file: $name");
            $this->exec($sql);
            echo('done' . PHP_EOL);
        }
    }

    /**
     * Loads and executes SQL file.
     * @param $path Path to SQL file
     */
    public function execScript($path) {
        $sql = file_get_contents($path);
        return $this->exec($sql);
    }

    /**
     * Returns database connection object.
     * @param null $dbName string
     * @return PDO
     */
    public function getPdo($dbName = null) {
        $dbUser = $this->getUsername();
        $dbPwd = $this->getPassword();

        $connStr = "mysql:host=localhost;default-character-set=utf8;default-collate=utf8_general_ci";

        if (!is_null($dbName) && strlen(trim($dbName)) > 0)
        {
            $connStr .= ";dbname=$dbName";
        }

        $pdo = new PDO($connStr, $dbUser, $dbPwd);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        // TODO unit test for character encoding?
        $pdo->exec('SET CHARACTER SET `utf8`');

        return $pdo;
    }

    /**
     * Executes SQL statement.
     * @param $sql string SQL statement
     * TODO review error handling (one level up?)
     */
    public function exec($sql)
    {
        $dbName = $this->getName();

        try {
            $pdo = $this->getPdo($dbName);

            $statement = $pdo->query($sql);

            while($statement->nextRowset()) {
                // iterate through results until finished or exception thrown
            }
        }
        catch (PDOException $pdoex) {
            $message = $pdoex->getMessage();
            echo('Error executing SQL' . PHP_EOL);
            echo($message . PHP_EOL);
            $logger = $this->getLogger();
            $logger->err($message);
        }
    }

    /**
     * Executes SQL without specifying database name.
     *
     * This is used for drop and create statements.
     *
     * @param $sql string
     */
    public function execWithoutDbName($sql) {
        try {
            $pdo = $this->getPdo();

            $statement = $pdo->query($sql);

            while ($statement->nextRowset())
            {
                // iterate over rowsets until finished or exception is thrown
            }
        }
        catch (PDOException $pdoex) {
            echo(PHP_EOL . $pdoex->getMessage());
        }
    }

    /**
     * Drops database schema.
     */
    public function drop() {
        $dbName = $this->getName();

        $sql = "DROP DATABASE IF EXISTS ${dbName};";

        $this->execWithoutDbName($sql);
    }

    /**
     * Returns SQL files in a directory.
     * @param $path string Path to directory containing SQL files
     * @return array
     */
    public function getSqlFiles($path, $pattern = null) {
        // TODO check $path

        $files = new DirectoryIterator($path);

        $sqlFiles = array();

        foreach($files as $file) {
            $filename = $file->getBasename();
            if (strrchr($filename, '.') == '.sql' && (is_null($pattern) || preg_match($pattern, $filename))) {
                $sqlFiles[] = $file->getPathname();
            }
        }

        sort($sqlFiles);

        return $sqlFiles;
    }

    /**
     * Returns path to database schema file.
     * @return string Path to schema file
     * @throws Exception
     */
    public function getSchemaFile() {
        $path = dirname(dirname(dirname(__FILE__))) . self::SCHEMA_PATH;

        if (!is_file($path)) {
            throw new Exception('could not find schema file');
        }

        return $path;
    }

    /**
     * Returns application configuration.
     * @return null|Zend_Config
     * @throws Zend_Exception
     */
    public function getConfig() {
        if (is_null($this->_config)) {
            $this->_config = Zend_Registry::get('Zend_Config');
        }

        return $this->_config;
    }

    /**
     * Returns logger.
     * @return mixed|Zend_Log
     * @throws Zend_Exception
     */
    public function getLogger() {
        if (is_null($this->_logger)) {
            $this->_logger = Zend_Registry::get('Zend_Log');
        }

        return $this->_logger;
    }

    /**
     * Returns schema version from database.
     */
    public function getVersion()
    {
        $pdo = $this->getPdo($this->getName());

        $version = null;

        try {
            $sql = 'SELECT * FROM `schema_version`';

            $result = $pdo->query($sql)->fetch();

            $version = $result['version'];
        }
        catch(PDOException $pdoex) {
            // TODO logging
        }

        return $version;
    }

    public function getLatestVersion()
    {
        if ($this->_latestVersion == 0)
        {
            $scripts = $this->getUpdateScripts();
            $this->_latestVersion = ( int )substr(basename(end($scripts)), 0, 3);
        }

        return $this->_latestVersion;
    }

    /**
     * Update database for a new version of OPUS.
     */
    public function update() {
        $schemaUpdate = new Opus_Update_Plugin_DatabaseSchema();
        $schemaUpdate->run();
    }

    /**
     * Returns SQL update scripts.
     *
     * If a version is specified only the update scripts after that version number are returned. This can be used to
     * update a database to the newest version.
     *
     * @param $version, Current version
     * @param $targetVersion, Version that should be updated to
     * @return array with full paths to update script files
     */
    public function getUpdateScripts($version = null, $targetVersion = null) {
        $scriptsPath = APPLICATION_PATH . self::UPDATE_SCRIPTS_PATH;

        $files = $this->getSqlFiles($scriptsPath, '/^\d{3}-.*/');

        if (!is_null($version))
        {
            $files = array_filter($files, function($value) use ($version) {
                $basename = basename($value);
                $number = substr($basename, 0, 3);
                return ($number > $version);
            });
        }

        if (!is_null($targetVersion))
        {
            $files = array_filter($files, function($value) use ($targetVersion) {
                $basename = basename($value);
                $number = substr($basename, 0, 3);
                return ($number <= $targetVersion);
            });
        }

        $files = array_values($files);

        return $files;
    }

}
