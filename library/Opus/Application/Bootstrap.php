<?php
/**
 * Starting point of the application. Do all the initialization and run
 * the dispatch loop.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id$
 */

/**
 * Provide methods to setup and run the application. It also provides a couple of static
 * variables for quicker access to application components like the front controller.
 *
 * @package     Opus_Application_Framework
 * @subpackage  Application
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
     * @param string $configLevel              Determines wich level of configuration is to be used.
     *                                         choose CONFIG_PRODUCTION or CONFIG_TEST.
     * @return void
     *
     */
    public static function run($applicationRootDirectory, $configLevel) {
        self::$applicationRootDirectory = $applicationRootDirectory;

        self::setupEnvironment();
        self::configure($configLevel);
        self::setupDatabase();
        self::setupAuthentication();
        self::setupLogging();
        self::setupCache();
        self::setupTranslation();
        self::prepare();

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
        // use custom DB adapter and options
        $config = new Zend_Config(array(
            'db' => array(
                'adapter' => 'Pdo_Mysqlutf8',
                'params' => array(
                    'adapterNamespace' => 'Opus_Db_Adapter',
                    'options' => array(
        Zend_Db::CASE_FOLDING => Zend_Db::CASE_LOWER)))), true);

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
     * Setup Zend_Auth component with custom database adapter and
     * register the adapter configuration with Zend_Registry.
     *
     * @return void
     *
     */
    protected static function setupAuthentication() {
        $adapter = new Zend_Auth_Adapter_DbTable(Zend_Db_Table::getDefaultAdapter());
        $adapter->setTableName('ACCOUNTS');
        $adapter->setIdentityColumn('username');
        $adapter->setCredentialColumn('password');

        // Setup session based storage of identities.
        Zend_Auth::getInstance()->setStorage(new Zend_Auth_Storage_Session());

        // Register.
        Zend_Registry::getInstance()->set('auth_adapter', $adapter);
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
     * @throws Exception          Exception is thrown if configuration level is invalid.
     * @return void
     *
     */
    protected static function configure($configLevel) {

        // Make sure that invalid configuration level values fail.
        if (($configLevel !== self::CONFIG_PRODUCTION) and (($configLevel !== self::CONFIG_TEST))) {
            throw new Exception('Invalid configuration level: ' . $configLevel);
        }

        // build path to ini file
        $pathToIni = 'config.ini';
        if ( empty(self::$applicationRootDirectory) === false ) {
            // only prepend path information if given
            // to aviod invalid filename "/config.ini"
            $pathToIni = self::$applicationRootDirectory . DIRECTORY_SEPARATOR . $pathToIni;
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
        $moduleprepare->appendClassPath('models')->appendClassPath('views/forms');
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
			'layoutPath'=>self::$applicationRootDirectory . '/modules/default/views/layouts',
			'layout'=>'common'));

        // Initialize view with custom encoding and global view helpers.
        $view = new Zend_View;
        $view->setEncoding('UTF-8');
        $view->addHelperPath(self::$applicationRootDirectory . '/modules/default/views/helpers', 'Opus_View_Helper');

        // Set path to Zend extension view helpers to be accessible in other
        // modules too.
        $libRealPath = realpath(self::$applicationRootDirectory . '/../library');
        $view->addHelperPath($libRealPath . '/Opus/View/Helper', 'Opus_View_Helper');

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
        $translate = new Zend_Translate('tmx', self::$applicationRootDirectory . '/modules/');
        if (empty($sessiondata->language) === false) {
            // Example for logging something
            $logger = Zend_Registry::get('Zend_Log');
            $logger->info('Switching to language "' . $sessiondata->language . '".');
            $translate->setLocale($sessiondata->language);
        }
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Translate', $translate);
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
                'automatic_serialization' => true
        );

        $backendOptions = array(
        // Directory where to put the cache files. Must be writeable for application server
                'cache_dir' => dirname(self::$applicationRootDirectory) . '/tmp/'
                );

                self::$cache = Zend_Cache::factory('Page', 'File', $frontendOptions, $backendOptions);
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
        $logfile = @fopen(dirname(self::$applicationRootDirectory) . '/tmp/opus.log', 'a', false);
        if ( $logfile === false ) {
            throw new Exception('Failed to open logging file.');
        }
        $writter = new Zend_Log_Writer_Stream($logfile);
        $logger = new Zend_Log($writter);
        $registry = Zend_Registry::getInstance();
        $registry->set('Zend_Log', $logger);
    }

}
