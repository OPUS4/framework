<?php
/**
 * Structure of search hits in Module_Search
 * 
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
 * @category    Application
 * @package     Module_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * class SearchHit
 */
class Opus_Search_SearchHit 
{

  /**
   * Document of the search hit matching the query
   * 
   * @var Opus_Search_Adapter_DocumentAdapter Document of this search hit
   * @access private
   */
  private $document;

  /**
   * File of the search hit matching the query
   * 
   * @var Opus_Search_Adapter_DocumentAdapter File of this search hit 
   * @access private
   */
  private $file;

  /**
   * Relevance of the search hit - get it from the search engine framework
   * 
   * @var float Relevance of this match concerning the query
   * @access private
   */
  private $relevance;

  /**
   * Type of the Search hit - does the search term match the fulltext or metadata?
   * 
   * @var integer Type of the search hit as an integer representation (0=metadata, 1=fulltext)
   * @access private
   */
  private $type;

  /**
   * Constructor
   * 
   * @param integer $id (Optional) ID of the document for this search hit - if not given or invalid, the Search hit wont have a document
   */
  public function __construct($id = null) {
  	if ($id !== null) {
  		$this->getDocument($id);
  	} else {
  		$this->document = null;
  	}
  }

  /**
   * Get the document as a Opus_Search_Adapter_DocumentAdapter by its ID
   * 
   * @param integer $id ID of the document
   * @return Opus_Search_Adapter_DocumentAdapter Document assigned to this search hit
   */
  private function getDocument($id) {
    $this->document = new Opus_Search_Adapter_DocumentAdapter($id);
    return $this->document;
  }

  /**
   * Get the Opus_Search_Adapter_DocumentAdapter from this search hit
   * 
   * @return Opus_Search_Adapter_DocumentAdapter Document assigned to this search hit
   */
  public function getSearchHit() {
    return $this->document;
  }

  /**
   * Set the relevance from this search hit
   *
   * @param float $relevance Relevance of this search hit concerning the query 
   * @return void
   */
  public function setRelevance($relevance) {
    $this->relevance = $relevance;
  }

  /**
   * Get the relevance from this search hit
   * 
   * @return float Relevance
   */
  public function getRelevance() {
    return $this->relevance;
  }

  /**
   * Assign a Opus-Document to this search hit  
   * 
   * @param Opus_Search_Adapter_DocumentAdapter $doc Document that should be set as a search hit
   * @return void
   */
  public function setDocument(Opus_Search_Adapter_DocumentAdapter $doc) {
    $this->document = $doc;
  }
    
    /**
     * Compare method to sort Hits by year (ascending)
     */
    static function cmp_year($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['year'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['year'];
        if ($ayear == $byear) {
            return 0;
        }
        return ($ayear > $byear) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by title (ascending)
     */
    static function cmp_title($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['title'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['title'];
        if ($ayear == $byear) {
            return 0;
        }
        return ($ayear > $byear) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by author (anytime the first one) (ascending)
     */
    static function cmp_author($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['author'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['author'];
        if ($ayear == $byear) {
            return 0;
        }
        return ($ayear > $byear) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by relevance (descending)
     */
    static function cmp_relevance($a, $b)
    {
    	$a1 = $a->getRelevance();
        $b1 = $b->getRelevance();
        if ($a1 == $b1) {
            return 0;
        }
        return ($a1 < $b1) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by year and title (ascending)
     */
    static function cmp_yat($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['year'];
        $atitle = $a1['title'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['year'];
        $btitle = $b1['title'];
        if ($ayear == $byear) {
        	if ($atitle == $btitle) {
        		return 0;
        	}
            return ($atitle > $btitle) ? +1 : -1;
        }
        return ($ayear > $byear) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by year (descending)
     */
    static function cmp_year_desc($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['year'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['year'];
        if ($ayear == $byear) {
            return 0;
        }
        return ($ayear < $byear) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by title (descending)
     */
    static function cmp_title_desc($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['title'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['title'];
        if ($ayear == $byear) {
            return 0;
        }
        return ($ayear < $byear) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by author (anytime the first one) (descending)
     */
    static function cmp_author_desc($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['author'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['author'];
        if ($ayear == $byear) {
            return 0;
        }
        return ($ayear < $byear) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by relevance (ascending)
     */
    static function cmp_relevance_asc($a, $b)
    {
    	$a1 = $a->getRelevance();
        $b1 = $b->getRelevance();
        if ($a1 == $b1) {
            return 0;
        }
        return ($a1 > $b1) ? +1 : -1;
    }

    /**
     * Compare method to sort Hits by year and title (descending)
     */
    static function cmp_yat_desc($a, $b)
    {
    	$a1 = $a->getSearchHit()->getDocument();
        $ayear = $a1['year'];
        $atitle = $a1['title'];
        $b1 = $b->getSearchHit()->getDocument();
        $byear = $b1['year'];
        $btitle = $b1['title'];
        if ($ayear == $byear) {
        	if ($atitle == $btitle) {
        		return 0;
        	}
            return ($atitle < $btitle) ? +1 : -1;
        }
        return ($ayear < $byear) ? +1 : -1;
    }
}