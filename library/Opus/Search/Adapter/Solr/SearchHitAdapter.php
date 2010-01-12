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
 * @version     $Id: SearchHitAdapter.php 3881 2009-12-03 16:32:17Z marahrens $
 */

/**
 * class Opus_Search_Adapter_Lucene_LuceneSearchHitAdapter
 * adapts a search hit from Lucene to the Opus-compliant format
 */
class Opus_Search_Adapter_Solr_SearchHitAdapter implements Opus_Search_Adapter_SearchHitAdapterInterface
{

  /**
   * Attribute holding the original query hit from Solr
   *
   * @var DomDocument QueryHit in Solr (XML) format
   * @access private
   */
  private $_parent = null;

  /**
   * Constructor
   *
   * @param DomNode $solrHit QueryHit to be adapted into OPUS format
   */
  public function __construct($solrHit) {
        $this->_parent = $solrHit;
  }

  /**
   * Converts a Solr search hit from the index to a Opus-compliant Hit to fit into the HitList
   *
   * @return SearchHit
   */
  public function convertToSearchHit($query = null) {
    	// make the Zend_Lucene_Search_QueryHit to a Opus-SearchHit
	    // Ranking and other attributes are taken from the Lucene class
        $document = new DOMDocument();
        $document->loadXml($this->_parent);
        $qhit = new Opus_Search_SearchHit();
        // relevance ranking not supported by solr by default
        #$qhit->setRelevance($this->_parent->score);

        // initialize variables
        $year = '';
        $title = '';
        $abstract = '';
        $author = '';

        // set the query hit by fields from solr index
        $strelements = $document->getElementsByTagName('str');
        $intelements = $document->getElementsByTagName('int');
        $arrelements = $document->getElementsByTagName('arr');
        $k = 0;
        foreach ($strelements as $str) {
        	if ($strelements->item($k)->getAttribute('name') === 'docid') {
        		$id = $strelements->item($k)->nodeValue;
        	}
        	$k++;
        }
        $l = 0;
        foreach ($intelements as $int) {
        	if ($intelements->item($l)->getAttribute('name') === 'year') {
        		$year = $intelements->item($l)->nodeValue;
        	}
        	$l++;
        }
        $m = 0;
        foreach ($arrelements as $arr) {
        	if ($arrelements->item($m)->getAttribute('name') === 'title') {
        		$titleelements = $arrelements->item($m)->getElementsByTagName('str');
        		$n = 0;
        		foreach ($titleelements as $t) {
        		    $title .= $titleelements->item($n)->nodeValue . ' ';
        		    $n++;
        		}
        	}
         	if ($arrelements->item($m)->getAttribute('name') === 'abstract') {
        		$abstractelements = $arrelements->item($m)->getElementsByTagName('str');
        		$n = 0;
        		foreach ($abstractelements as $a) {
        		    $abstract .= $abstractelements->item($n)->nodeValue . ' ';
        		    $n++;
        		}
        	}
         	if ($arrelements->item($m)->getAttribute('name') === 'author') {
        		$authorelements = $arrelements->item($m)->getElementsByTagName('str');
        		$n = 0;
        		$authors = array();
        		foreach ($authorelements as $b) {
        		    array_push($authors, $authorelements->item($n)->nodeValue);
        		    $n++;
        		}
        		$author = join('; ', $authors);
        	}
        	$m++;
        }
        $opusdoc = new Opus_Search_Adapter_DocumentAdapter(array('id' => $id, 'title' => $title, 'abstract' => $abstract, 'author' => $author, 'year' => $year));
        $qhit->setDocument($opusdoc);
        return $qhit;
  }
}