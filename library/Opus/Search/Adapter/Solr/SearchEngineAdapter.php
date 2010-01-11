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
 * @version     $Id: SearchEngineAdapter.php 2791 2009-05-14 15:02:26Z marahrens $
 */

/**
 * class SolrAdapter
 */
class Opus_Search_Adapter_Solr_SearchEngineAdapter implements Opus_Search_Adapter_SearchEngineAdapterInterface
{

  /**
   * Standard Operator for queries (if not specified in combination of search terms)
   *
   * @var string Operator
   * @access private
   */
  private $boolean;

  /**
   * Constructor
   *
   * @param string $boolean (Optional) Boolean operator used in the query by default; if not specified, AND will be used
   */
  public function __construct($boolean = 'AND') {
  }

  /**
   * Search function: Gives the query to Solr
   *
   * @param string $query Complete query typed by the user, to be analysed in this function
   * @return Opus_Search_Adapter_Lucene_SearchHitAdapter
   */
  public function find($query) {
        echo $result = $this->postSearch($query);
        // We need an OPUS-compliant result list to return
        /*$hitlist = new Opus_Search_List_HitList();
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
        }*/
    //return $hitlist;
  }
  
  private function postSearch($query) {
		// HTTP-Header vorbereiten
		$out  = "GET /solr/select/?q=$query&version=2.2&start=0&rows=10&indent=on HTTP/1.1\r\n";
		$out .= "Host: localhost\r\n";
		$out .= "Content-type: text/xml; charset=utf-8\r\n";
		$out .= "Content-length: 0\r\n";
		$out .= "User-Agent: SolrIndexer\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "\r\n";
		if (!$conex = @fsockopen('localhost', '8983', $errno, $errstr, 10)) return 0;
		fwrite($conex, $out);
		$data = '';
		while (!feof($conex)) {
			$data .= fgets($conex, 512);
		}
		fclose($conex);
		return $data;		
	}
}