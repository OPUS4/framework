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

/**
 * Encapsulates all parameter values needed to build the Solr query URL.
 */
class Opus_SolrSearch_Query {
    // currently available search types
    const SIMPLE = 'simple';
    const ADVANCED = 'advanced';
    const FACET_ONLY = 'facet_only';
    const LATEST_DOCS = 'latest';
    const ALL_DOCS = 'all_docs';
    const DOC_ID = 'doc_id';

    const DEFAULT_START = 0;
    const DEFAULT_ROWS = 10;

    // java.lang.Integer.MAX_VALUE
    const MAX_ROWS = 2147483647;

    const DEFAULT_SORTFIELD = 'score';
    const DEFAULT_SORTORDER = 'desc';

    const SEARCH_MODIFIER_CONTAINS_ALL = "contains_all";
    const SEARCH_MODIFIER_CONTAINS_ANY = "contains_any";
    const SEARCH_MODIFIER_CONTAINS_NONE = "contains_none";

    private $start = self::DEFAULT_START;
    private $rows = self::DEFAULT_ROWS;
    private $sortField = self::DEFAULT_SORTFIELD;
    private $sortOrder = self::DEFAULT_SORTORDER;
    private $filterQueries = array();
    private $catchAll;
    private $searchType;
    private $modifier;
    private $fieldValues = array();
    private $escapingEnabled = true;
    private $q;
    private $facetField;
    private $returnIdsOnly = false;
    private $seriesId = null;

    /**
     *
     * @param string $searchType
     * @throws Opus_SolrSearch_Exception If $searchType is not supported.
     */
    public function  __construct($searchType = self::SIMPLE) {
        $this->invalidQCache();

        if ($searchType === self::SIMPLE || $searchType === self::ADVANCED || $searchType === self::ALL_DOCS) {
            $this->searchType = $searchType;
            return;
        }

        if ($searchType === self::FACET_ONLY) {
            $this->searchType = self::FACET_ONLY;
            $this->setRows(0);
            return;
        }

        if ($searchType === self::LATEST_DOCS) {
            $this->searchType = self::LATEST_DOCS;
            $this->sortField = 'server_date_published';
            $this->sortOrder = 'desc';
            return;
        }

        if ($searchType === self::DOC_ID) {
            $this->searchType = self::DOC_ID;
            return;
        }

        throw new Opus_SolrSearch_Exception("searchtype $searchType is not supported");
    }

    public function getSearchType() {
        return $this->searchType;
    }

    public function getFacetField() {
        return $this->facetField;
    }

    public function setFacetField($facetField) {
        $this->facetField = $facetField;
    }

    public function getStart() {
        return $this->start;
    }

    public function setStart($start) {
        $this->start = $start;
    }

    public static function getDefaultRows() {
        return Opus_Search_Query::getDefaultRows();
    }

    public function getRows() {
        return $this->rows;
    }

    public function setRows($rows) {
        $this->rows = $rows;
    }

    public function getSortField() {
        return $this->sortField;
    }

    public function setSortField($sortField) {
        if ($sortField === self::DEFAULT_SORTFIELD) {
            if ($this->searchType === self::ALL_DOCS) {
                // change the default sortfield for searchtype all
                // since sorting by relevance does not make any sense here
                $this->sortField = 'server_date_published';
            }
            else {
                $this->sortField = self::DEFAULT_SORTFIELD;
            }
            return;
        }
        $this->sortField = $sortField;
        if (strpos($sortField, 'doc_sort_order_for_seriesid_') !== 0 && strpos($sortField, 'server_date_published') !== 0) {
            // add _sort to the end of $sortField if not already done
            $suffix = '_sort';
            if (substr($sortField, strlen($sortField) - strlen($suffix)) !== $suffix) {
                $this->sortField .= $suffix;
            }
        }
    }

    public function getSortOrder() {
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder) {
        $this->sortOrder = $sortOrder;
    }

    public function getSeriesId() {
        return $this->seriesId;
    }

    /**
     *
     * @return array An array that contains all specified filter queries.
     */
    public function getFilterQueries() {
        return $this->filterQueries;
    }

    /**
     *
     * @param string $filterField The field that should be used in a filter query.
     * @param string $filterValue The field value that should be used in a filter query.
     */
    public function addFilterQuery($filterField, $filterValue) {
        if ($filterField == 'has_fulltext') {
            $filterQuery = $filterField . ':' . $filterValue;
        }
        else {
            $filterQuery = '{!raw f=' . $filterField . '}' . $filterValue;
        }
        array_push($this->filterQueries, $filterQuery);

        // we need to store the ID of the requested series here,
        // since we need it later to build the index field name
        if ($filterField === 'series_ids') {
            $this->seriesId = $filterValue;
        }
    }

    /**
     *
     * @param array $filterQueries An array of queries that should be used as filter queries.
     */
    public function setFilterQueries($filterQueries) {
        $this->filterQueries = $filterQueries;
    }

    public function getCatchAll() {
        return $this->catchAll;
    }

    public function setCatchAll($catchAll) {
        $this->catchAll = $catchAll;
        $this->invalidQCache();
    }

    /**
     *
     * @param string $name
     * @param string $value
     * @param string $modifier
     */
    public function setField($name, $value, $modifier = self::SEARCH_MODIFIER_CONTAINS_ALL) {
        if (!empty($value)) {
            $this->fieldValues[$name] = $value;
            $this->modifier[$name] = $modifier;
            $this->invalidQCache();
        }
    }

    /**
     *
     * @param string $name
     * @return Returns null if no values was specified for the given field name.
     */
    public function getField($name) {
        if (array_key_exists($name, $this->fieldValues)) {
            return $this->fieldValues[$name];
        }
        return null;
    }

    /**
     *
     * @param string $fieldname
     * @return Returns null if no modifier was specified for the given field name.
     */
    public function getModifier($fieldname) {
        if (array_key_exists($fieldname, $this->modifier)) {
            return $this->modifier[$fieldname];
        }
        return null;
    }

    public function getQ() {
        if (is_null($this->q)) {
            // earlier cached query was marked as invalid: perform new setup of query cache
            $this->q = $this->setupQCache();
        }

        // return cached result (caching is done here since building q is an expensive operation)
        return $this->q;
    }

    private function setupQCache() {
        if ($this->searchType === self::SIMPLE) {
            if ($this->getCatchAll() === '*:*') {
                return $this->catchAll;
            }
            return $this->escape($this->getCatchAll());
        }
        if ($this->searchType === self::FACET_ONLY || $this->searchType === self::LATEST_DOCS || $this->searchType === self::ALL_DOCS) {
            return '*:*';
        }
        if ($this->searchType === self::DOC_ID) {
            return 'id:' . $this->fieldValues['id'];
        }
        return $this->buildAdvancedQString();
    }

    private function invalidQCache() {
        $this->q = null;
    }

    private function buildAdvancedQString() {
        $q = "{!lucene q.op=AND}";
        $first = true;
        foreach ($this->fieldValues as $fieldname => $fieldvalue) {
            if ($first) {
                $first = false;
            }
            else {
                $q .= ' ';
            }

            if ($this->modifier[$fieldname] === self::SEARCH_MODIFIER_CONTAINS_ANY) {
                $q .= $this->combineSearchTerms($fieldname, $fieldvalue, 'OR');
                continue;
            }

            if ($this->modifier[$fieldname] === self::SEARCH_MODIFIER_CONTAINS_NONE) {
                $q .= '-' . $this->combineSearchTerms($fieldname, $fieldvalue, 'OR');
                continue;
            }

            // self::SEARCH_MODIFIER_CONTAINS_ALL
            $q .= $this->combineSearchTerms($fieldname, $fieldvalue);
        }
        return $q;
    }

    private function combineSearchTerms($fieldname, $fieldvalue, $conjunction = null) {
        $result = $fieldname . ':(';
        $firstTerm = true;
        $queryTerms = preg_split("/[\s]+/", $this->escape($fieldvalue), null, PREG_SPLIT_NO_EMPTY);
        foreach ($queryTerms as $queryTerm) {
            if ($firstTerm) {
                $firstTerm = false;
            }
            else {
                $result .= is_null($conjunction) ? " " : " $conjunction ";
            }
            $result .= $queryTerm;
        }
        $result .= ')';
        return $result;
    }

    public function disableEscaping() {
        $this->invalidQCache();
        $this->escapingEnabled = false;
    }

    /**
     * Escape Lucene's special query characters specified in
     * http://lucene.apache.org/java/3_0_2/queryparsersyntax.html#Escaping%20Special%20Characters
     * Escaping currently ignores * and ? which are used as wildcard operators.
     * Additionally, double-quotes are not escaped and a double-quote is added to
     * the end of $query in case it contains an odd number of double-quotes.
     * @param string $query The query which needs to be escaped.
     */
    public function escape($query)
    {
        if (!$this->escapingEnabled)
        {
            return $query;
        }
        $query = trim($query);

        // add one " to the end of $query if it contains an odd number of "
        $count = preg_match_all('/^"|[^\\\]"/', $query);
        if ($count % 2 == 1) {
            $query .= '"';
        }

        // escape special characters (currently ignore " \* \?) outside of ""
        $insidePhrase = false;
        $result = '';
        foreach (explode('"', $query) as $phrase) {
            if ($insidePhrase) {
                $result .= '"' . $phrase . '"';
            }
            else {
                $result .= preg_replace('/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|~|:|\\\)/', '\\\$1', $this->lowercaseWildcardQuery($phrase));
            }
            $insidePhrase = !$insidePhrase;
        }

        // add one " to the end of $query if it contains an odd number of "
        $activeQuotes = preg_replace('/(?<=[^\\\])(\\\")/', '', $result); // remove escaped quotes for check
        if (substr_count($activeQuotes, '"') % 2 == 1) {
            $result .= '"';
        }

        return $result;
    }

    public function lowercaseWildcardQuery($query) {
        // check if $query is a wildcard query
        if (strpos($query, '*') === FALSE && strpos($query, '?') === FALSE) {
            return $query;
        }
        // lowercase query
        return strtolower($query);
    }

    public function  __toString() {
        if ($this->searchType === self::SIMPLE) {
            return 'simple search with query ' . $this->getQ();
        }
        if ($this->searchType === self::FACET_ONLY) {
            return 'facet only search with query *:*';
        }
        if ($this->searchType === self::LATEST_DOCS) {
            return 'search for latest documents with query *:*';
        }
        if ($this->searchType === self::ALL_DOCS) {
            return 'search for all documents';
        }
        if ($this->searchType === self::DOC_ID) {
            return 'search for document id ' . $this->getQ();
        }
        return 'advanced search with query  ' . $this->getQ();
    }

    /**
     *
     * @param boolean $returnIdsOnly
     */
    public function setReturnIdsOnly($returnIdsOnly) {
        $this->returnIdsOnly = $returnIdsOnly;
    }

    /**
     * @return boolean
     */
    public function isReturnIdsOnly() {
        return $this->returnIdsOnly;
    }
}

