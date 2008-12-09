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
 * class LuceneSearchHitAdapter
 * adapts a search hit from Lucene to the Opus-compliant format
 */
class Opus_Search_Adapter_Lucene_SearchHitAdapter implements Opus_Search_Adapter_SearchHitAdapterInterface
{

  /**
   * @access private
   */
  private $_parent = null;

  /**
   * Constructor
   * @access public
   */
  public function __construct($luceneHit) {
        $this->_parent = $luceneHit;
  } // end of Constructor

  /**
   * converts a Lucene search hit from the index to a Opus-compliant Hit to fit into the HitList
   * @return SearchHit
   * @access public
   */
  public function convertToSearchHit() {
    // aus dem Lucene_Search_QueryHit einen QueryHit für OPUS machen
    // Ranking und sonstige Eigenschaften werden aus der Lucene-Klasse übernommen
        $document = $this->_parent->getDocument();
        $docid = str_replace("nr", "", $document->getFieldValue('docid'));
        $qhit = new SearchHit($docid);
        $qhit->setRelevance($this->_parent->score);
        #$opusfile = new OPUSDocumentFile($document->getFieldValue('source'), $docid);

        $opusdoc = new Opus_Search_Adapter_DocumentAdapter((int) $docid);
        #$prove = $opusdoc->loadRecord();
        $qhit->setDocument($opusdoc);
        #$qhit->addFile($opusfile);
        return $qhit;
        #return false;
  } 


} // end of LuceneSearchHitAdapter
