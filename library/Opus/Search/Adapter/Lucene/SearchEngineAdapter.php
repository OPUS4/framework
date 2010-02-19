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
 * class LuceneAdapter
 */
class Opus_Search_Adapter_Lucene_SearchEngineAdapter implements Opus_Search_Adapter_SearchEngineAdapterInterface
{

  /**
   * Standard Operator for queries (if not specified in combination of search terms)
   *
   * @var string Operator
   * @access private
   */
  private $boolean;
  
  /**
   * parsed Query
   *
   * @var Zend_Search_Lucene_Search_Query
   * @access public
   */
  public $parsedQuery;

  /**
   * Constructor
   *
   * @param string $boolean (Optional) Boolean operator used in the query by default; if not specified, AND will be used
   */
  public function __construct($boolean = 'AND') {
    $this->boolean = $boolean;
    Zend_Search_Lucene_Search_QueryParser::setDefaultOperator(Zend_Search_Lucene_Search_QueryParser::B_AND);
  }

  /**
   * Search function: Gives the query to Lucene
   *
   * @param string $query Complete query typed by the user, to be analysed in this function
   * @return Opus_Search_Adapter_Lucene_SearchHitAdapter
   */
  public function find($query) {
        // Bugfix for quoted strings: remove quotes and escapes
        $query = str_replace('\\', '', $query);
        $query = str_replace('\"', '', $query);
        // remove + at the end of a query (given from metager for quoted strings) - its useless anyway
        $query = preg_replace('/[(\ )|\+|(%20)]$/','', $query);
        $query = str_replace(' and ',' AND ', $query);
        $query = str_replace(' or ',' OR ', $query);
        $query = preg_replace('/(\ and\ )?(\ not\ )/',' AND NOT ', $query);
        try {
            $lucenePath = Zend_Registry::get('Zend_LuceneIndexPath');
                #Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
                #Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
        		$index = new Zend_Search_Lucene($lucenePath);
                // Get the boolean operators used in the query
                $oquery = $query;
                if (preg_match('/(\ and\ |\ or\ |\ not\ )/', $query) === 1) {
                	$this->boolean = 'ignore';
                }
                switch ($this->boolean)
                {
                    case 'AND':
                        $query = preg_replace('/[(\ )|\+|(%20)]/', ' AND ', $query);
                        //echo $query;
                        break;

                    # we don't need other cases right now, OR is standard operator for Lucene
                    default:
                        $query = $oquery;
                        break;
                }
                #echo $query;
                $lucenequery = Zend_Search_Lucene_Search_QueryParser::parse($query);
                $this->parsedQuery = $lucenequery;
                if (strlen($query) < 2) {
                    throw new Exception('Query string should be at least 2 characters long!');
                }
                $hits = $index->find($lucenequery);
        } catch (Zend_Search_Lucene_Exception $searchException) {
                throw $searchException;
        }
        
        // Query results only DocumentId (duplicates are getting removed)
        /*$hitlistarray = array();
        if (count($hits) > 0) {
                foreach ($hits as $queryHit) {
                        $document = $queryHit->getDocument();
                        $docid = $document->getFieldValue('docid');
                        if (in_array($docid, $hitlistarray) === false) {
                           	array_push($hitlistarray, $docid);
                        }
                }
        }*/
        // Query results are in Lucene format now
        // We need an OPUS-compliant result list to return
        $hitlist = new Opus_Search_List_HitList();
        $done = array();
        $hitlistarray = array();
        if (count($hits) > 0) {
                foreach ($hits as $queryHit) {
                        $document = $queryHit->getDocument();
                        $docid = $document->getFieldValue('docid');
                        if (in_array($docid, $done) === false) {
                                array_push($done, $docid);
                                $opusHit = new Opus_Search_Adapter_Lucene_SearchHitAdapter($queryHit);
                                $curdoc = $opusHit->convertToSearchHit($lucenequery);
                                if ($curdoc !== false) {
                                	array_push($hitlistarray, $curdoc);
                                }
                        } else {
                                $key = array_search($docid, $done);
                        }
                }
        }
        $hitlist->query = $query;
        foreach ($hitlistarray as $singlehit) {
        	$hitlist->add($singlehit);
        }
    return $hitlist;
  }
}
