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
class Opus_Bootstrap_Base extends Zend_Application_Bootstrap_Bootstrap {

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
     * TODO how to execute this using Zend_Application
     * TODO where can I get the parameters from?
     */
    protected function _initMain() {
        $this->bootstrap('Configuration');

        $this->_setupBackendCaching();
        $this->_setupBackend();

        $this->_setupFrontendCaching();
        $this->_setupFrontend();
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
        $this->bootstrap('Logging');
        $this->_setupLucene();
        $this->_setupTemp();
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

    protected function _setupTemp() {
        $config = $this->getResource('Configuration');
        $tempDirectory = $config->workspacePath . '/tmp/';
        Zend_Registry::set('temp_dir', $tempDirectory);
    }

    /**
     * Setup database cache.
     *
     * @return void
     */
    protected function _setupDatabaseCache() {
        $config = $this->getResource('Configuration');

        $cache = null;
        $frontendOptions = array(
        // Set cache lifetime to 5 minutes (in seconds)
            'lifetime' => 600,
            'automatic_serialization' => true,
        );

        $backendOptions = array(
        // Directory where to put the cache files. Must be writeable for application server
            'cache_dir' => $config->workspacePath . '/cache/'
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
     * TODO put into configuration file
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
        Zend_Registry::set('db_adapter', $db);
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
     * @return void
     *
     */
    protected function _initConfiguration() {
        $config = new Zend_Config($this->getOptions());

        Zend_Registry::set('Zend_Config', $config);

        return $config;
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
        $config = $this->getResource('Configuration');
        $lucenePath = $config->workspacePath . '/lucene_index';
        Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
        #Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Opus_Search_Adapter_Lucene_NumberFinder());
        Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
        Zend_Registry::set('Zend_LuceneIndexPath', $lucenePath);
        $personslucenePath = $config->workspacePath. '/persons_index';
        Zend_Registry::set('Zend_LucenePersonsIndexPath', $personslucenePath);
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

        $logfile = @fopen($config->workspacePath . '/log/opus.log', 'a', false);
        
        if ( $logfile === false ) {
            // TODO use Opus exception
            throw new Exception('Failed to open logging file.');
        }

        $writer = new Zend_Log_Writer_Stream($logfile);
        $logger = new Zend_Log($writer);
        
        Zend_Registry::set('Zend_Log', $logger);

        $logger->debug('Logging initialized');

        return $logger;
    }

    /**
     * Set up path pattern that is used to look for document type descriptions.
     *
     * @return void
     */
    protected function _setupDocumentType() {
        // Set location of xml document type definitions
        Opus_Document_Type::setXmlDoctypePath(APPLICATION_PATH . '/config/xmldoctypes');
    }

}
