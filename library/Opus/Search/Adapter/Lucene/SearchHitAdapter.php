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
 * class Opus_Search_Adapter_Lucene_LuceneSearchHitAdapter
 * adapts a search hit from Lucene to the Opus-compliant format
 */
class Opus_Search_Adapter_Lucene_SearchHitAdapter implements Opus_Search_Adapter_SearchHitAdapterInterface
{

  /**
   * Attribute holding the original query hit from Lucene
   *
   * @var Zend_Search_Lucene_Search_QueryHit QueryHit in Lucene format
   * @access private
   */
  private $_parent = null;

  /**
   * Constructor
   *
   * @param Zend_Search_Lucene_Search_QueryHit $luceneHit QueryHit to be adapted into OPUS format
   */
  public function __construct(Zend_Search_Lucene_Search_QueryHit $luceneHit) {
        $this->_parent = $luceneHit;
  }

  /**
   * Converts a Lucene search hit from the index to a Opus-compliant Hit to fit into the HitList
   *
   * @return SearchHit
   */
  public function convertToSearchHit($query = null) {
    	// make the Zend_Lucene_Search_QueryHit to a Opus-SearchHit
	    // Ranking and other attributes are taken from the Lucene class
        $document = $this->_parent->getDocument();
        $docid = str_replace('nr', '', $document->getFieldValue('docid'));
        #$qhit = new Opus_Search_SearchHit($docid);
        $qhit = new Opus_Search_SearchHit();
        $qhit->setRelevance($this->_parent->score);

        $lucenePath = Zend_Registry::get('Zend_LuceneIndexPath');
        Zend_Search_Lucene_Search_QueryParser::setDefaultEncoding('utf-8');
        #Zend_Search_Lucene_Analysis_Analyzer::setDefault(new Zend_Search_Lucene_Analysis_Analyzer_Common_Utf8Num_CaseInsensitive());
        $index = new Zend_Search_Lucene($lucenePath);
        $q2 = $query->rewrite($index);

        #$highlighter = $query->highlightMatches('<meta http-equiv="content-type" content="charset=UTF-8">' . $document->getFieldValue('abstract'));
        #$highlighter = $query->htmlFragmentHighlightMatches($document->getFieldValue('abstract'));
        $highlighter = new Opus_Search_Highlighter($document->getFieldValue('abstract'), $q2->getQueryTerms());
        // hold b-Tags (highlighted text), remove all others
        #$highlighted = strip_tags($highlighter, '<b>');
        #$highlighter->zoom();
        $highlighted = $highlighter->mark_words();
        // Without Syntax Highlighting
        // $highlighted = $document->getFieldValue('abstract');

        // set the query hit by fields from lucene index
        $opusdoc = new Opus_Search_Adapter_DocumentAdapter(array('id' => $document->getFieldValue('docid'), 'title' => $document->getFieldValue('title'), 'abstract' => $highlighted, 'author' => $document->getFieldValue('author'), 'year' => $document->getFieldValue('year')));
        $qhit->setDocument($opusdoc);
        return $qhit;
  }
}
