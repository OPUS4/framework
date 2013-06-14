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

class Opus_SolrSearch_ResponseRenderer {
    /**
     * Logger
     *
     * @var Zend_Log
     */
    private $log;

    /**
     * @var Opus_SolrSearch_ResultList
     */
    private $resultList;

    /**
     * @var array
     */
    private $jsonResponse;

    /**
     *
     * @param Apache_Solr_Response $solrResponse
     */
    public function  __construct($solrResponse, $seriesId = null) {
        $this->log = Zend_Registry::get('Zend_Log');
        $this->setJsonResponseAsArray($solrResponse);
        $this->buildResultList($solrResponse, $seriesId);
    }

    /**
     * @return Opus_SolrSearch_ResultList
     */
    public function getResultList() {
        return $this->resultList;
    }

    /**
     * @param Apache_Solr_Response $solrResponse
     */
    private function buildResultList($solrResponse, $seriesId = null) {
        if (is_null($solrResponse->response) || $solrResponse->response->numFound == 0) {
            $this->resultList = new Opus_SolrSearch_ResultList();
            return;
        }
        $results = array();
        foreach ($solrResponse->response->docs as $doc) {
            $result = new Opus_SolrSearch_Result();
            if (isset($doc->id)) $result->setId($doc->id);
            if (isset($doc->score)) $result->setScore($doc->score);            
            if (isset($doc->author)) $result->setAuthors($doc->author);
            if (isset($doc->year)) $result->setYear($doc->year);
            if (isset($doc->title_output)) $result->setTitle($doc->title_output);
            if (isset($doc->abstract_output)) $result->setAbstract($doc->abstract_output);
            if (!is_null($seriesId)) {
                $seriesNumberFieldname = 'series_number_for_id_' . $seriesId;
                if (isset($doc->$seriesNumberFieldname)) $result->setSeriesNumber($doc->$seriesNumberFieldname);
            }
            if (isset($doc->server_date_modified)) $result->setServerDateModified ($doc->server_date_modified);
            array_push($results, $result);
        }
        $numFound = $solrResponse->response->numFound;
        $qtime = $this->jsonResponse['responseHeader']['QTime'];
        $this->log->debug("number of hits: $numFound");
        $this->log->debug("query time: $qtime");
        $this->resultList = new Opus_SolrSearch_ResultList($results, $numFound, $qtime, $this->getFacets(), $this->log);
    }

    /**
     *
     * @param Apache_Solr_Response $solrResponse
     */
    private function setJsonResponseAsArray($solrResponse) {
        try {
            $this->jsonResponse = Zend_Json::decode($solrResponse->getRawResponse());
            if (is_null($this->jsonResponse)) {
                $this->log->warn("result of decoding solr's json string is null");
            }
        }
        catch (Exception $e) {
            $this->log->warn("error while decoding solr's json response");            
        }
    }

    private function getFacets() {
        $config = Zend_Registry::get('Zend_Config');
        if (!isset($config->searchengine->solr->facets)) {
            $this->log->debug('config parameter searchengine.solr.facets is not defined -- no facets will be displayed');
            return array();
        }
        
        if (!array_key_exists('facet_counts', $this->jsonResponse) ||
            !array_key_exists('facet_fields', $this->jsonResponse['facet_counts'])) {
            return array();
        }

        $facetsResult = $this->jsonResponse['facet_counts']['facet_fields'];
        $facets = explode(",", $config->searchengine->solr->facets);        
        $result = array();
        foreach ($facets as $facet) {
            $facet = trim($facet);
            $facetItems = array();
            foreach ($this->getFacet($facetsResult, $facet) as $text => $count) {
                if ($facet == 'year_inverted') {
                    $valueParts = explode(':', $text, 2);
                    $text = $valueParts[1];
                }
                array_push($facetItems, new Opus_SolrSearch_FacetItem($text, $count));
            }
            if ($facet == 'year_inverted') {
                $facet = 'year';
            }
            $result[$facet] = $facetItems;
        }
        return $result;
    }

    private function getFacet($facets, $facetName) {
        if (array_key_exists($facetName, $facets)) {
            return $facets[$facetName];
        }
        return array();
    }
}

