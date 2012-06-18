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
 * @package     Opus_SolrSearch
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_SolrSearch_Searcher {

    /**
     * Logger
     *
     * @var Zend_Log
     */
    private $log;

    /**
     * Connection to Solr server
     *
     * @var Apache_Solr_Service
     */
    private $solr_server;

    /**
     * Connection string
     *
     * @var string
     */
    private $solr_server_url;

    /**
     *
     * @throws Opus_SolrSearch_Exception If connection to Solr server could not be established.
     */
    public function  __construct() {
        $this->log = Zend_Registry::get('Zend_Log');
        $this->solr_server = $this->getSolrServer();
        if (false === $this->solr_server->ping()) {
            $this->log->err('Connection to Solr server ' . $this->solr_server_url . ' could not be established.');
            throw new Opus_SolrSearch_Exception('Solr server ' . $this->solr_server_url . ' is not responding.', Opus_SolrSearch_Exception::SERVER_UNREACHABLE);
        }
        $this->log->info('Connection to Solr server ' . $this->solr_server_url . ' was successfully established.');
    }


    /**
     * TODO remove code duplication (Opus_SolrSearch_Index_Indexer)
     * Returns a Apache_Solr_Service object which encapsulates the communication
     * with the Solr server.
     *
     * @return Apache_Solr_Server
     */
    private function getSolrServer() {
        $config = Zend_Registry::get('Zend_Config');
        $solr_host = $config->searchengine->index->host;
        $solr_port = $config->searchengine->index->port;
        $solr_app = '/' . $config->searchengine->index->app;
        $this->solr_server_url = 'http://' . $solr_host . ':' . $solr_port . $solr_app;
        return new Apache_Solr_Service($solr_host, $solr_port, $solr_app);
    }

    /**
     *
     * @param Opus_SolrSearch_Query $query
     * @return Opus_SolrSearch_ResultList
     * @throws Opus_SolrSearch Exception If Solr server responds with an error or the response is empty.
     */
    public function search($query) {
        /**
         * @var Apache_Solr_Response $solr_response
         */
        $solr_response = null;
        try {
            $this->log->debug("query: " . $query->getQ());
            $solr_response = $this->solr_server->search($query->getQ(), $query->getStart(), $query->getRows(), $this->getParams($query));            
        }
        catch (Exception $e) {
            $msg = 'Solr server responds with an error ' . $e->getMessage();
            $this->log->err($msg);
            if ($e instanceof Apache_Solr_HttpTransportException) {
                if ($e->getResponse()->getHttpStatus() == '400') {
                    // 400 seems to indicate org.apache.lucene.query.ParserParseException
                    throw new Opus_SolrSearch_Exception($msg, Opus_SolrSearch_Exception::INVALID_QUERY, $e);
                }
                if ($e->getResponse()->getHttpStatus() == '404') {
                    // 404 seems to indicate Solr server is unreachable
                    throw new Opus_SolrSearch_Exception($msg, Opus_SolrSearch_Exception::SERVER_UNREACHABLE, $e);
                }
            }
            throw new Opus_SolrSearch_Exception($msg, null, $e);
        }
        if (is_null($solr_response)) {
            $msg = 'could not get an Apache_Solr_Response object';
            $this->log->err($msg);
            throw new Opus_SolrSearch_Exception($msg);
        }
        $responseRenderer = new Opus_SolrSearch_ResponseRenderer($solr_response, $query->getSeriesId());
        return $responseRenderer->getResultList();
    }

    /**
     *
     * @param Opus_SolrSearch_Query $query
     * @return string
     */
    private function getParams($query) {
        if ($query->getSearchType() === Opus_SolrSearch_Query::LATEST_DOCS) {
            return array(
                'fl' => $query->isReturnIdsOnly() ? 'id' : '* score',
                'facet' => 'false',
                'sort' => $query->getSortField() . ' ' . $query->getSortOrder()
            );
        }

        if ($query->getSearchType() === Opus_SolrSearch_Query::FACET_ONLY) {
            return array(
                'fl' => '',
                'facet' => 'true',
                'facet.field' => $query->getFacetField(),
                'facet.mincount' => 1,
                'facet.limit' => -1
            );
        }

        if ($query->getSearchType() === Opus_SolrSearch_Query::DOC_ID) {
            return array(
                'fl' => $query->isReturnIdsOnly() ? 'id' : '* score',
                'facet' => 'false'
            );
        }
        
        $params = array( 
            'fl' => $query->isReturnIdsOnly() ? 'id' : '* score',
            'facet' => $query->isReturnIdsOnly() ? 'false' : 'true',
            'facet.field' => $this->setFacetFieldsFromConfig(),
            'facet.mincount' => 1,
            'sort' => $query->getSortField() . ' ' . $query->getSortOrder(),
            'facet.limit' => 10
        );
        $fq = $query->getFilterQueries();
        if (!empty($fq)) {
            $params['fq'] = $fq;
        }
        return $params;
    }

    private function setFacetFieldsFromConfig() {
        $config = Zend_Registry::get('Zend_Config');
        if (!isset($config->searchengine->solr->facets)) {
            // no facets are being configured
            $this->log->warn("Key searchengine.solr.facets is not present in config. No facets will be displayed.");
            return array();
        }
        $result = array();
        $facets = explode((","), $config->searchengine->solr->facets);
        foreach ($facets as $facet) {
            array_push($result, trim($facet));
        }
        return $result;
    }
}

