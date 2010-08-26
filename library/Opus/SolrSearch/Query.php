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
 * @category    TODO
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
    const SIMPLE = 'simple';
    const ADVANCED = 'advanced';
    const DEFAULT_START = 0;
    const DEFAULT_ROWS = 10;
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
    private $escape = true;

    /**
     *
     * @param string $searchType
     * @throws Opus_SolrSearch_Exception If $searchType is not supported.
     */
    public function  __construct($searchType = self::SIMPLE) {
        if ($searchType === self::SIMPLE) {
            $this->searchType = self::SIMPLE;
            return;
        }
        if ($searchType === self::ADVANCED) {
            $this->searchType = self::ADVANCED;
            return;
        }
        throw new Opus_SolrSearch_Exception("searchtype $searchType is not supported");
    }

    public function getStart() {
        return $this->start;
    }

    public function setStart($start) {
        $this->start = $start;
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
        $this->sortField = $sortField;
    }

    public function getSortOrder() {
        return $this->sortOrder;
    }

    public function setSortOrder($sortOrder) {
        $this->sortOrder = $sortOrder;
    }

    /**
     *
     * @return string A combined string representation of all specified filter queries
     * that can be directly used as a value for Solr's fq parameter.
     */
    public function getFilterQueriesString() {
        if (count($this->filterQueries) === 0) {
            return null;
        }
        $first = true;
        $fqString = '';
        foreach ($this->filterQueries as $fq) {
            $fq = str_replace(' ', '\ ', $fq);
            if ($first === true) {
                $fqString = '+' . $fq;
                $first = false;
            }
            else {
                $fqString = $fqString . ' +' . $fq;
            }
        }
        return $fqString;
    }

    /**
     *
     * @param string $filterQuery A query that should be used as a filter query.
     */
    public function addFilterQuery($filterQuery) {
        array_push($this->filterQueries, $filterQuery);
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
    }

    /**
     *
     * @param string $name
     * @param string $value
     * @param string $modifier
     */
    public function setField($name, $value, $modifier = self::SEARCH_MODIFIER_CONTAINS_ANY) {
        if (!empty($value)) {
            $this->fieldValues[$name] = $value;
            $this->modifier[$name] = $modifier;
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
        if ($this->searchType === self::SIMPLE) {
            if ($this->getCatchAll() === '*:*') {
                return $this->catchAll;
            }
            return $this->escape($this->getCatchAll());
        }
        return $this->buildAdvancedQString();
    }

    private function buildAdvancedQString() {
        $q = "{!lucene q.op=AND}";
        $first = true;
        foreach ($this->fieldValues as $fieldname => $fieldvalue) {
            if (!$first) {
                $q = $q . ' ';
            }
            else {
                $first = false;
            }
            if ($this->modifier === self::SEARCH_MODIFIER_CONTAINS_ALL) {
                $q = $q . $fieldname . ':(' . $this->escape($fieldvalue) . ')';
                continue;
            }
            if ($this->modifier === self::SEARCH_MODIFIER_CONTAINS_NONE) {
                $q = $q . '-' . $fieldname . ':(' . $this->escape($fieldvalue) . ')';
                continue;
            }            
            $q = $q . $fieldname . ':(';
            $firstTerm = true;
            foreach (explode(' ', $this->escape($fieldvalue)) as $queryTerm) {
                if ($firstTerm) {
                    $firstTerm = false;
                    $q = $q . $queryTerm;
                }
                else {
                    $q = ' OR ' . $queryTerm;
                }

            }
            $q = $q . ')';

            
        }
        return $q;
    }

    public function  __toString() {
        if ($this->searchType === self::SIMPLE) {
            return 'simple search with query ' . $this->getQ();
        }
        return 'advanced search with query  ' . $this->getQ();
    }

    public function disableEscaping() {
        $this->escape = false;
    }

    /**
     * Escape Lucene's special query characters specified in
     * http://lucene.apache.org/java/3_0_2/queryparsersyntax.html#Escaping%20Special%20Characters
     * Escaping currently ignores * and ? which are used as wildcard operators.
     * Additionally, double-quotes are not escaped and a double-quote is added to
     * the end of $query in case it contains an odd number of double-quotes.
     * @param string $query The query which needs to be escaped.
     */
    private function escape($query) {
        if ($this->escape === false) {
            return $query;
        }
        $query = trim($query);
        // add one " to the end of $query if it contains an odd number of "
        if (substr_count($query, '"') % 2 == 1) {
            $query = $query . '"';
        }
        // escape special characters (currently ignore " \* \?)
        return preg_replace('/(\+|-|&&|\|\||!|\(|\)|\{|}|\[|]|\^|~|:|\\\)/', '\\\$1', $query);
    }
}
?>