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
    const DEFAULT_FILTERQUERIES = array();

    private $start = self::DEFAULT_START;
    private $rows = self::DEFAULT_ROWS;
    private $sortField = self::DEFAULT_SORTFIELD;
    private $sortOrder = self::DEFAULT_SORTORDER;
    private $filterQueries = self::DEFAULT_FILTERQUERIES;
    private $year;
    private $urn;
    private $isbn;
    private $abstractDeu;
    private $abstractEng;
    private $titleDeu;
    private $titleEng;
    private $author;
    private $fulltext;
    private $catchAll;
    private $searchType;

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

    public function getFilterQueriesString() {
        if (count($this->filterQueries) === 0) {
            return null;
        }
        $first = true;
        $fqString = '';
        foreach ($this->filterQueries as $field => $value) {
            if ($first === true) {
                $fqString = $field . ':' . $value;
                $first = false;
            }
            else {
                $fqString = $fqString . ' +' . $field . ':' . $value;
            }
        }
        return $fqString;
    }

    public function addFilterQuery($filterQuery) {
        array_push($this->filterQueries, $filterQuery);
    }

    public function setFilterQueries($filterQueries) {
        $this->filterQueries = $filterQueries;
    }

    public function getYear() {
        return $this->year;
    }

    public function setYear($year) {
        $this->year = $year;
    }

    public function getUrn() {
        return $this->urn;
    }

    public function setUrn($urn) {
        $this->urn = $urn;
    }

    public function getIsbn() {
        return $this->isbn;
    }

    public function setIsbn($isbn) {
        $this->isbn = $isbn;
    }

    public function getAuthor() {
        return $this->author;
    }

    public function setAuthor($author) {
        $this->author = $author;
    }

    public function getFulltext() {
        return $this->fulltext;
    }

    public function setFulltext($fulltext) {
        $this->fulltext = $fulltext;
    }

    public function getCatchAll() {
        return $this->catchAll;
    }

    public function setCatchAll($catchAll) {
        $this->catchAll = $catchAll;
    }

    public function getAbstractDeu() {
        return $this->abstractDeu;
    }

    public function setAbstractDeu($abstractDeu) {
        $this->abstractDeu = $abstractDeu;
    }

    public function getAbstractEng() {
        return $this->abstractEng;
    }

    public function setAbstractEng($abstractEng) {
        $this->abstractEng = $abstractEng;
    }

    public function getTitleDeu() {
        return $this->titleDeu;
    }

    public function setTitleDeu($titleDeu) {
        $this->titleDeu = $titleDeu;
    }

    public function getTitleEng() {
        return $this->titleEng;
    }

    public function setTitleEng($titleEng) {
        $this->titleEng = $titleEng;
    }

    public function getQ() {
        if ($this->searchType === self::SIMPLE) {
            return $this->getCatchAll();
        }
        // TODO
        return "*:*";
    }

    public function  __toString() {
        if ($this->searchType === self::SIMPLE) {
            return 'simple search with query ' . $this->getQ();
        }
        // TODO
        return "advanced search for TODO";
    }}

?>