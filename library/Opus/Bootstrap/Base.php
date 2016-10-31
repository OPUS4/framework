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
 * @package     Opus_Bootstrap
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2016, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Provide basic workflow of setting up an application.
 *
 * @category    Framework
 * @package     Opus_Bootstrap
 *
 */
class Opus_Bootstrap_Base extends Zend_Application_Bootstrap_Bootstrap {

    /**
     * Override this to do custom backend setup.
     *
     * @return void
     */
    protected function _initBackend() {
        $this->bootstrap(array('ZendCache', 'OpusLocale', 'Database', 'Logging'));
    }

    /**
     * Initializes the location for temporary files.
     *
     */
    protected function _initTemp() {
        $this->bootstrap('Configuration');
        $config = $this->getResource('Configuration');
        $tempDirectory = $config->workspacePath . '/tmp/';
        Zend_Registry::set('temp_dir', $tempDirectory);
    }

    /**
     * Setup zend cache directory.
     *
     * @return void
     */
    protected function _initZendCache() {
        $this->bootstrap('Configuration');
        $config = $this->getResource('Configuration');

        $frontendOptions = array(
            'lifetime' => 600, // in seconds
            'automatic_serialization' => true,
        );

        $backendOptions = array(
            // Directory where to put the cache files. Must be writeable for
            // application server
            'cache_dir' => $config->workspacePath . '/cache/'
        );

        $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

        Zend_Translate::setCache($cache);
        Zend_Locale::setCache($cache);
        Zend_Locale_Data::setCache($cache);
        Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);

        return $cache;
    }

    /**
     * Setup a database connection and store the adapter in the registry.
     *
     * @return void
     *
     * TODO put into configuration file (custom DB adapter)
     */
    protected function _initDatabase() {
        $this->bootstrap(array('ZendCache', 'Logging','Configuration'));

        $logger = $this->getResource('Logging');
        $logger->debug('Initializing database.');

        // use custom DB adapter
        $config = new Zend_Config(array(
            'db' => array(
                'adapter' => 'Pdo_Mysqlutf8',
                'params' => array(
                    'adapterNamespace' => 'Opus_Db_Adapter'))), true);

        // Include the above made configuration changes in the application configuration.
        $config->merge(Zend_Registry::get('Zend_Config'));

        // Put manipulated database configuration back to registry.
        Zend_Registry::set('Zend_Config', $config);

        // Use zend_Db factory to create a database adapter
        // and make it the default for all tables.
        $db = null;

        try {
            $db = Zend_Db::factory($config->db);
            Zend_Db_Table::setDefaultAdapter($db);

            // Register the adapter within Zend_Registry.
            Zend_Registry::set('db_adapter', $db);
        }
        catch (Zend_Db_Adapter_Exception $e) {
            $logger->err($e);
            throw new Exception('OPUS Bootstrap Error: Could not connect to database.');
        }

        // Check database version
        if (!Zend_Registry::isRegistered('opus.disableDatabaseVersionCheck') ||
            !Zend_Registry::get('opus.disableDatabaseVersionCheck')) {
            try {
                $query = $db->query('SELECT version FROM schema_version');

                $result = $query->fetch();

                if (is_array($result) && array_key_exists('version', $result)) {
                    $version = $result['version'];
                    $expectedVersion = Opus_Version::getSchemaVersion();

                    if ($version !== $expectedVersion) {
                        throw new Exception("Database version '$version' does not match required '$expectedVersion'.");
                    }
                }
                else {
                    throw new Exception(
                        'No database schema version found. Database is probably too old. Please update.'
                    );
                }
            }
            catch (Zend_Db_Statement_Exception $e) {
                throw new Exception('Database schema is too old. Please update database.');
            }
        }
    }

    /**
     * Load application configuration file and register the configuration
     * object with the Zend registry under 'Zend_Config'.
     *
     * To access parts of the configuration you have to retrieve the registry
     * instance and call the get() method:
     * <code>
     * $registry = Zend_Registry::getInstance();
     * $config = $registry->get('Zend_Config');
     * </code>
     *
     * @throws Exception          Exception is thrown if configuration level is invalid.
     * @return Zend_Config
     *
     */
    protected function _initConfiguration() {
        $config = new Zend_Config($this->getOptions());
        Zend_Registry::set('Zend_Config', $config);

        return $config;
    }

    /**
     * Setup Logging
     *
     * @throws Exception If logging file couldn't be opened.
     * @return void
     *
     */
    protected function _initLogging()
    {
        $this->bootstrap('Configuration');

        $config = $this->getResource('Configuration');

        // Detect if running in CGI environment.
        $logFilename = 'opus.log';
        if (!array_key_exists('SERVER_PROTOCOL', $_SERVER) and !array_key_exists('REQUEST_METHOD', $_SERVER)) {
            $logFilename = "opus-console.log";
        }

        $logfilePath = $config->workspacePath . '/log/' . $logFilename;

        $logfile = @fopen($logfilePath, 'a', false);
        
        if ( $logfile === false ) {
            $path = dirname($logfilePath);

            if (!is_dir($path)) {
                throw new Exception('Directory for logging does not exist');
            }
            else {
                throw new Exception('Failed to open logging file:' . $logfilePath);
            }
        }

        $GLOBALS['id_string'] = uniqid(); // Write ID string to global variables, so we can identify/match individual runs.

        $format = '%timestamp% %priorityName% (%priority%, ID '.$GLOBALS['id_string'].'): %message%' . PHP_EOL;
        $formatter = new Zend_Log_Formatter_Simple($format);

        $writer = new Zend_Log_Writer_Stream($logfile);
        $writer->setFormatter($formatter);

        $logger = new Zend_Log($writer);
        $logLevelName = 'INFO';
        $logLevelNotConfigured = false;

        if (isset($config->log->level)) {
            $logLevelName = strtoupper($config->log->level);
        }
        else {
            $logLevelNotConfigured = true;
        }

        $zendLogRefl = new ReflectionClass('Zend_Log');

        $invalidLogLevel = false;

        $logLevel = $zendLogRefl->getConstant($logLevelName);

        if (empty($logLevel)) {
            $logLevel = Zend_Log::INFO;
            $invalidLogLevel = true;
        }

        // filter log output
        $priorityFilter = new Zend_Log_Filter_Priority($logLevel);
        Zend_Registry::set('LOG_LEVEL', $logLevel);
        $logger->addFilter($priorityFilter);

        if ($logLevelNotConfigured) {
            $logger->warn('Log level not configured, using default \'' . $logLevelName . '\'.');
        }

        if ($invalidLogLevel) {
            $logger->err('Invalid log level \'' . $logLevelName .
                    '\' configured.');
        }

        Zend_Registry::set('Zend_Log', $logger);

        $logger->debug('Logging initialized');

        return $logger;
    }

    /**
     * Setup timezone and default locale.
     *
     * Registers locale with key Zend_Locale as mentioned in the ZF documentation.
     *
     * @return void
     */
    protected function _initOpusLocale() {
        // Need cache initializatino for Zend_Locale.
        $this->bootstrap('ZendCache');

        // This avoids an exception if the locale cannot determined automatically.
        // TODO setup in config, still put in registry?
        $locale = new Zend_Locale("de");
        Zend_Registry::set('Zend_Locale', $locale);
    }

}
