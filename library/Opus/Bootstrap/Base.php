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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Provide basic workflow of setting up an application.
 *
 * @category    Framework
 * @package     Opus_Bootstrap
 *
 */
class Opus_Bootstrap_Base {

    /**
     * Stores a reference to the application front controller component.
     *
     * @var Zend_Controller_Action
     */
    protected $_frontController = null;

    /**
     * Stores the abolute path to the application on the server.
     *
     * @var string
     */
    protected $_applicationRootDirectory = '';

    /**
     * Path to workspace directory.
     *
     * @var string
     */
    protected $_applicationWorkspaceDirectory = '';

    /**
     * Declare the use of production state configuration.
     *
     */
    const CONFIG_PRODUCTION = 'production';
    /**
     * Declare the use of test state configuration.
     */
    const CONFIG_TEST = 'test';

    /**
     * Setup and run the dispatch loop. Finally send the response to the client.
     *
     * @param string $applicationRootDirectory Full path to directory of application modules and configuration.
     *                                         Must not be empty.
     * @param string $configLevel              Determines wich level of configuration is to be used.
     *                                         choose CONFIG_PRODUCTION or CONFIG_TEST.
     * @param string $configPath               (Optional) Path to look for config.ini file.
     * @throws Exception                       Exception is thrown on empty application base path.
     * @return void
     *
     */
    public function run($applicationRootDirectory, $configLevel, $configPath = null) {
        if ( empty($applicationRootDirectory) === true ) {
            throw new Exception('Configuration error. No application base path given.');
        }
        $this->_applicationRootDirectory = $applicationRootDirectory;
        $this->_applicationWorkspaceDirectory = $this->_applicationRootDirectory . '/workspace';

        include_once 'Zend/Loader.php';
        Zend_Loader::registerAutoload();

        $this->_setupEnvironment();
        $this->_setupConfiguration($configLevel, $configPath);

        $this->_setupBackendCaching();
        $this->_setupBackend();

        $this->_setupInternalConfiguration();

        $this->_setupFrontendCaching();
        $this->_setupFrontend();

        $this->_run();
    }

    /**
     * Start bootstrapped application.
     *
     * @return void
     */
    protected function _run() {
    }

    /**
     * Override this to do custom frontend setup.
     *
     * @return void
     */
    protected function _setupFrontend() {
    }

    /**
     * Override this to do custom backend setup.
     *
     * @return void
     */
    protected function _setupBackend() {
        $this->_setupDatabase();
        $this->_setupLogging();
        $this->_setupLucene();
        $this->_setupDocumentType();
    }

    /**
     * Override to set up custom caching engines for any backend functionality.
     *
     * @return void
     */
    protected function _setupBackendCaching() {
    }

    /**
     * Override to set up custom caching engines for the frontend.
     *
     * @return void
     */
    protected function _setupFrontendCaching() {
    }

    /**
     * Setup database cache.
     *
     * @return void
     */
    protected function _setupDatabaseCache() {
        $cache = null;
        $frontendOptions = array(
        // Set cache lifetime to 5 minutes (in seconds)
            'lifetime' => 600,
            'automatic_serialization' => true,
        );

        $backendOptions = array(
        // Directory where to put the cache files. Must be writeable for application server
            'cache_dir' => $this->_applicationWorkspaceDirectory . '/cache/'
            );

            $cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);

            // enable db metadata caching
            Zend_Db_Table_Abstract::setDefaultMetadataCache($cache);
    }


    /**
     * Setup a database connection and store the adapter in the registry.
     *
     * @return void
     *
     */
    protected function _setupDatabase() {
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
        $db = Zend_Db::factory($config->db);
        Zend_Db_Table::setDefaultAdapter($db);

        // Register the adapter within Zend_Registry.
        Zend_Registry::getInstance()->set('db_adapter', $db);
    }

    /**
     * Setup error reporting, timezone and include path.
     *
     * @return void
     *
     */
    protected function _setupEnvironment() {
        // Setup error reporting.
        error_reporting(E_ALL | E_STRICT);
        ini_set('display_errors', 1);

        /*
         * Setup timezone and locale options.
         */
        date_default_timezone_set('Europe/Berlin');

        // This avoids an exception if the locale cannot determined automatically.
        Zend_Locale::setDefault('de');

    }


    /**
     * Augment the application configuration with configuration values
     * delivered by Opus_Configuration model.
     *
     * @return void
     */
    protected function _setupInternalConfiguration() {       
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
     * @param string $configLevel Determines wich level of configuration is to be used.
     *                            choose CONFIG_PRODUCTION or CONFIG_TEST.
     * @param string $configPath  (Optional) Path to config.ini. If no path is given,
     *                            the application root directory is assumed to hold the config.ini.
     * @throws Exception          Exception is thrown if configuration level is invalid.
     * @return void
     *
     */
    protected function _setupConfiguration($configLevel, $configPath = null) {

        // Make sure that invalid configuration level values fail.
        if (($configLevel !== self::CONFIG_PRODUCTION) and (($configLevel !== self::CONFIG_TEST))) {
            throw new Exception('Invalid configuration level: ' . $configLevel);
        }

        // build path to ini file
        $pathToIni = 'config.ini';
        if ( is_null($configPath) === true ) {
            // only prepend path information if given
            // to aviod invalid filename "/config.ini"
            $pathToIni = $this->_applicationRootDirectory . DIRECTORY_SEPARATOR . $pathToIni;
        } else {
            $pathToIni = $configPath . DIRECTORY_SEPARATOR . $pathToIni;
        }

        // Check if the config file really exists.
        if ( file_exists($pathToIni) === false ) {
            throw new Exception('Config file ' . $pathToIni . ' does not exist in: ' . $pathToIni);
        }

        $config = new Zend_Config_Ini($pathToIni, $configLevel);
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Config', $config);
    }





    /**
     * Setup Zend_Search_Lucene with Index
     *
     * It is assumed that the index is stored under lucene_index.
     *
     * @return void
     *
     */
    protected function _setupLucene()
    {
        $lucenePath = $this->_applicationWorkspaceDirectory . '/lucene_index';
        $registry = Zend_Registry::getInstance();
        Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
        $registry->set('Zend_LuceneIndexPath', $lucenePath);
    }


    /**
     * Setup Logging
     *
     * @throws Exception If logging file couldn't be opened.
     * @return void
     *
     */
    protected function _setupLogging()
    {
        $logfile = @fopen($this->_applicationWorkspaceDirectory . '/log/opus.log', 'a', false);
        if ( $logfile === false ) {
            throw new Exception('Failed to open logging file.');
        }
        $writer = new Zend_Log_Writer_Stream($logfile);
        $logger = new Zend_Log($writer);
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Log', $logger);
    }

    /**
     * Set up path pattern that is used to look for document type descriptions.
     *
     * @return void
     */
    protected function _setupDocumentType() {
        // Set location of xml document type definitions
        Opus_Document_Type::setXmlDoctypePath($this->_applicationRootDirectory .
                '/config/xmldoctypes');
    }

}
