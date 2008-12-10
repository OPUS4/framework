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
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * class Query
 */
class Opus_Search_Query
{

  /**
   * Querystring without any modification (as given from the user)
   * 
   * @var string Querystring
   * @access private
   */
  private $query;

  /**
   * Characterset of the querystring
   * 
   * @var string Encoding charset of the querystring
   * @access private
   */
  private $encoding;

  /**
   * Searchengine for this query
   * 
   * @var string Search Engine backend to be used (there must be an Adapter for it)
   * @access private
   */
  private $searchEngine;

  /**
   * Constructor
   * 
   * @param string $query Querystring for this query
   * @param string $defaultop (Optional) Boolean operator to be used for query, by default any boolean operators are ignored
   * @param string $searchengine (Optional) Searchengine to be used for this query, if none is given, Lucene will be used by default
   */
  public function __construct($query, $defaultop = "ignore", $searchengine =  "Lucene") {
    $adapterclass = 'Opus_Search_Adapter_' . $searchengine . '_SearchEngineAdapter';
    if (class_exists($adapterclass) === true) {
    	$this->searchEngine = new $adapterclass($defaultop);
    } else {
    	throw new Exception("No adapter for search engine $searchengine!");
    }
    $this->query = $query;
  }

  /**
   * Commit the query to the selected searchengine
   * 
   * @return SearchHitList
   */
  public function commit() {
    $result = $this->searchEngine->find($this->query);
    return $result;
  }
}