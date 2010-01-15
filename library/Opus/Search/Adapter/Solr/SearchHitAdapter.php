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
        $qhit = new Opus_Search_SearchHit();
        // $qhit->setRelevance($this->_parent->score);
        // initialize variables
        $year = '';
        $title = '';
        $abstract = '';
        $author = '';
        $id = $this->_parent['docid'];
        $year = $this->_parent['year'];
        $authors = array();
        // iterate document fields / values
        foreach ($this->_parent as $field => $value)
        {
        	if ($field === 'title') $title .= $value . ' ';
        	if ($field === 'abstract') $abstract .= $value . ' ';
        	if ($field === 'author') {
        		array_push($authors, $value);
        	}
        }
        $author = join('; ', $authors);
        $opusdoc = new Opus_Search_Adapter_DocumentAdapter(array('id' => $id, 'title' => $title, 'abstract' => $abstract, 'author' => $author, 'year' => $year));
        $qhit->setDocument($opusdoc);
        return $qhit;
  }
}