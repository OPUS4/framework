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
 * @package     Opus_Search_Util
 * @author      Sascha Szott <szott@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_Search_Util_ResultList {

    private $results;
    private $numberOfHits;
    private $queryTime;
    private $facets;

    /**
     *
     * @var Zend_Log
     */
    private $log;

    public function __construct($results = array(), $numberOfHits = 0, $queryTime = 0, $facets = array(), $validateDocIds = true, $log = null) {
        $this->log = $log;
        $this->numberOfHits = $numberOfHits;
        $this->queryTime = $queryTime;
        $this->facets = $facets;
        $this->results = array();

        // make sure that documents returned from index exist in database
        if (!empty($results)) {
            if ($validateDocIds) {
                $docIds = array();
                foreach ($results as $result) {
                    array_push($docIds, $result->getId());
                }
                $finder = new Opus_DocumentFinder();
                $finder->setServerState('published');
                $finder->setIdSubset($docIds);
                $docIdsDB = $finder->ids();
                $notInDB = 0;
                foreach ($results as $result) {
                    if (in_array($result->getId(), $docIdsDB)) {
                        array_push($this->results, $result);
                    }
                    else {
                        $notInDB++;
                    }
                }
                $resultsSize = count($this->results);
                if ($notInDB > 0 && !is_null($this->log)) {
                    $inDB = $resultsSize - $notInDB;
                    $this->log->err("found inconsistency between database and solr index: index returns $resultsSize documents, but only " . $inDB . " found in database");
                }
            }
            else {
                $this->results = $results;
            }
        }
    }

    /**
     *
     * @return array Returns an array of Opus_Search_Util_Result objects.
     */
    public function getResults() {
        return $this->results;
    }

    public function getNumberOfHits() {
        return $this->numberOfHits;
    }

    public function getQueryTime() {
        return $this->queryTime;
    }

    /**
     *
     * @return array Returns an array with a facet name as key and an array of
     * Opus_SolrSearch_FacetItem objects as value
     */
    public function getFacets() {
        return $this->facets;
    }

    public function  __toString() {
        // TODO
        return "Result list consisting of " . $this->numberOfHits . " results retrieved in " . $this->queryTime . " milliseconds.";
    }
}

