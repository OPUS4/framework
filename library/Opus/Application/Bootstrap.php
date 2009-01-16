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
 * @package     Opus_Application
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Provide methods to setup and run the application. It also provides a couple of static
 * variables for quicker access to application components like the front controller.
 *
 * @category    Framework
 * @package     Opus_Application
 *
 */
class Opus_Application_Bootstrap {

    /**
     * Stores a reference to the application front controller component.
     *
     * @var Zend_Controller_Action
     */
    protected static $frontController = null;

    /**
     * Stores the abolute path to the application on the server.
     *
     * @var string
     */
    protected static $applicationRootDirectory = '';


    /**
     * Stores a reference to the cache component.
     *
     * @var Zend_Cache
     */
    protected static $cache = null;

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
    public static function run($applicationRootDirectory, $configLevel, $configPath = null) {
        if ( empty($applicationRootDirectory) === true ) {
            throw new Exception('Configuration error. No application base path given.');
        }
        self::$applicationRootDirectory = $applicationRootDirectory;

        self::setupEnvironment();
        self::configure($configLevel, $configPath);
        self::setupDatabase();
        self::setupLogging();
        self::setupCache();
        self::setupTranslation();
        self::setupLucene();
        self::setupDocumentType();
        self::prepare();

        // start caching
        //self::$cache->start();
        // if the cache is hit, the result is sent to the browser and the script stop here

        $response = self::$frontController->dispatch();
        self::sendResponse($response);
    }


    /**
     * Setup a database connection and store the adapter in the registry.
     *
     * @return void
     *
     */
    protected static function setupDatabase() {
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
     * Setup error reporting, timezone and include path. Include and register
     * the Zend autoloader.
     *
     * @return void
     *
     */
    protected static function setupEnvironment() {
        include_once 'Zend/Loader.php';
        Zend_Loader::registerAutoload();

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
     * Load application configuration file and register the configuration
     * object with the Zend registry under 'config'.
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
    protected static function configure($configLevel, $configPath = null) {

        // Make sure that invalid configuration level values fail.
        if (($configLevel !== self::CONFIG_PRODUCTION) and (($configLevel !== self::CONFIG_TEST))) {
            throw new Exception('Invalid configuration level: ' . $configLevel);
        }

        // build path to ini file
        $pathToIni = 'config.ini';
        if ( empty(self::$configPath) === false ) {
            // only prepend path information if given
            // to aviod invalid filename "/config.ini"
            $pathToIni = self::$applicationRootDirectory . DIRECTORY_SEPARATOR . $pathToIni;
        } else {
            $pathToIni = $configPath . DIRECTORY_SEPARATOR . $pathToIni;
        }

        // Check if the config file really exists.
        if ( file_exists($pathToIni) === false ) {
            throw new Exception('Config file ' . $pathToIni . ' does not exist.');
        }

        $config = new Zend_Config_Ini($pathToIni, $configLevel);
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Config', $config);
    }

    /**
     * Prepare the dispatch process with setting up front controller and view.
     *
     * @return void
     *
     */
    protected static function prepare()
    {
        self::setupFrontController();
        self::setupView();
    }

    /**
     * Setup a front controller instance with error options and module
     * directory.
     *
     * @return void
     *
     */
    protected static function setupFrontController()
    {
        self::$frontController = Zend_Controller_Front::getInstance();
        // If you want to use the error controller, disable throwExceptions
        self::$frontController->throwExceptions(true);
        self::$frontController->returnResponse(true);
        self::$frontController->addModuleDirectory(self::$applicationRootDirectory . '/modules');

        /*
         * Add a custom front controller plugin for setting up an appropriate
         * include path to the form classes of modules.
         */
        $moduleprepare = new Opus_Controller_Plugin_ModulePrepare(self::$applicationRootDirectory . '/modules');
        $moduleprepare->appendClassPath('models')->appendClassPath('forms');
        self::$frontController->registerPlugin($moduleprepare);
    }

    /**
     * Configure view with UTF-8 options and ViewRenderer action helper.
     * The Zend_Layout component also gets initialized here.
     *
     * @return void
     *
     */
    protected static function setupView()
    {
        Zend_Layout::startMvc(array(
			'layoutPath'=>self::$applicationRootDirectory . '/layouts',
			'layout'=>'common'));

        // Initialize view with custom encoding and global view helpers.
        $view = new Zend_View;
        $view->setEncoding('UTF-8');

        // Set doctype to XHTML1 strict
        $view->doctype('XHTML1_STRICT');

        // Set path to Zend extension view helpers to be accessible in other
        // modules too.
        $libRealPath = realpath(self::$applicationRootDirectory . '/library');
        $view->addHelperPath($libRealPath . '/Opus/View/Helper', 'Opus_View_Helper');

        // Set path to shared view partials
        $view->addScriptPath($libRealPath . '/Opus/View/Partials');

        $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer($view);
        Zend_Controller_Action_HelperBroker::addHelper($viewRenderer);

    }

    /**
     * Setup global header options for the response and send it to the client.
     *
     * @param Zend_Controller_Response_Http $response The response to be sent.
     * @return void
     *
     */
    protected static function sendResponse(Zend_Controller_Response_Http $response)
    {
        $response->setHeader('Content-Type', 'text/html; charset=UTF-8', true);
        $response->sendResponse();
    }

    /**
     * Setup Zend_Translate with language resources of all existent modules.
     *
     * It is assumed that all modules are stored under modules/. The search
     * pattern Zend_Translate gets configured with is to look for a
     * folder and file structure similar to:
     *
     * language/
     *         index.tmx
     *         loginform.tmx
     *         ...
     *
     * @return void
     *
     */
    protected static function setupTranslation()
    {

        Zend_Translate::setCache(self::$cache);
        $sessiondata = new Zend_Session_Namespace();
        $translate = new Zend_Translate(Zend_Translate::AN_TMX, self::$applicationRootDirectory . '/modules/');
        if (empty($sessiondata->language) === false) {
            // Example for logging something
            $logger = Zend_Registry::get('Zend_Log');
            $logger->info('Switching to language "' . $sessiondata->language . '".');
            $translate->setLocale($sessiondata->language);
        }
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Translate', $translate);

        $locale = new Zend_Locale();
        $availableLanguages = $locale->getLanguageTranslationList();
        asort($availableLanguages);
        $registry->set('Available_Languages', $availableLanguages);
    }

    /**
     * Setup Zend_Search_Lucene with Index
     *
     * It is assumed that the index is stored under lucene_index.
     *
     * @return void
     *
     */
    protected static function setupLucene()
    {
        $lucenePath = self::$applicationRootDirectory . '/lucene_index';
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_LuceneIndexPath', $lucenePath);
    }

    /**
     * Setup Zend_Cache for caching application data
     *
     * @return void
     *
     */
    protected static function setupCache()
    {
        // Set cache lifetime to 5 minutes
        $frontendOptions = array(
            'lifetime' => 600,
            'debug_header' => false,
            // turning on could slow down caching
            'automatic_serialization' => false,
            'default_options' => array(
                // standard value false
                'cache_with_get_variables' => true,
                // standard value false
                'cache_with_post_variables' => true,
                // standard value false
                'cache_with_session_variables' => true,
                // standard value false
                'cache_with_files_variables' => true,
                // standard value false
                'cache_with_cookie_variables' => true,
                'make_id_with_get_variables' => true,
                'make_id_with_post_variables' => true,
                'make_id_with_session_variables' => true,
                'make_id_with_files_variables' => true,
                'make_id_with_cookie_variables' => true,
                'cache' => true
            )
        );

        $backendOptions = array(
            // Directory where to put the cache files. Must be writeable for application server
            'cache_dir' => self::$applicationRootDirectory . '/tmp/'
        );

        self::$cache = Zend_Cache::factory('Page', 'File', $frontendOptions, $backendOptions);

        // enable db metadata caching
        // Zend_Db_Table_Abstract::setDefaultMetadataCache(self::$cache);
    }

    /**
     * Setup Logging
     *
     * @throws Exception If logging file couldn't be opened.
     * @return void
     *
     */
    protected static function setupLogging()
    {
        $logfile = @fopen(self::$applicationRootDirectory . '/log/opus.log', 'a', false);
        if ( $logfile === false ) {
            throw new Exception('Failed to open logging file.');
        }
        $writter = new Zend_Log_Writer_Stream($logfile);
        $logger = new Zend_Log($writter);
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Log', $logger);
    }
    
    /**
     * Set up path pattern that is used to look for document type descriptions.
     *
     * @return void
     */
    protected static function setupDocumentType() {
        // Set location of xml document type definitions
        Opus_Document_Type::setXmlDoctypePath(self::$applicationRootDirectory .
                '/config/xmldoctypes');
    }

}
