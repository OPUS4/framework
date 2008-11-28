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
 * @package     Opus_Search_Adapter_Lucene
 * @author      Oliver Marahrens (o.marahrens@tu-harburg.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * class LuceneAdapter
 */
class Opus_Search_Adapter_Lucene_SearchEngineAdapter implements Opus_Search_Adapter_SearchEngineAdapterInterface
{

  public $boolean;

  /**
   * Konstruktor
   * @access public
   */
  public function __construct($boolean = "AND") {
    $this->boolean = $boolean;
  } // end of Konstruktor

  /**
   * Suchfunktion: Gibt die Anfrage an das Lucene-System weiter
   * @param string query
   * @return LuceneQueryHitAdapter
   * @access public
   */
  public function find($query) {
        // Fehler bei der Verarbeitung von quoted Strings: Quotes und Escapezeichen entfernen
        $query = str_replace("\\", "", $query);
        $query = str_replace("\"", "", $query);
        // Das + am Ende des Suchstrings entfernen (Metager baut bei gequoteten Strings ein + ans Ende...)
        $query = ereg_replace("[(\ )|\+|(%20)]$", "", $query);
        try
        {
                $index = Zend_Registry::get('Zend_Luceneindex');
                // Eigene boole'sche Operatoren rausgreppen
                #$query = ereg_replace("(\\x)", "%", $query);
                $oquery = $query;
                if (ereg("(\ and\ |\ or\ |\ not\ )", $query)) $this->boolean = "ignore";
                switch ($this->boolean)
                {
                    case "AND":
                        $query = ereg_replace("[(\ )|\+|(%20)]", " AND ", $query);
                        //echo $query;
                        break;
                    case "OR":
                    case "ignore":
                        $query = $oquery;
                        break;
                }
                $hits = $index->find(utf8_encode(strtolower($query)));
        }
        catch (Zend_Search_Lucene_Exception $searchException)
        {
                echo "Error: ".$searchException->getMessage()."<br/>";
        }
        // Die Suchergebnisse sind jetzt im Lucene-Format
        // Die Methode soll aber eine OPUS-konforme QueryHitList zur.ckgeben
        $hitlist = new HitList();
        $done = array();
        $hitlistarray = array();
        if (count($hits) > 0)
        {
                foreach ($hits as $queryHit)
                {
                        $document = $queryHit->getDocument();
                        $docid = str_replace("nr", "", $document->getFieldValue('docid'));
                        if (!in_array($docid, $done))
                        {
                                array_push($done, $docid);
                                $opusHit = new Opus_Search_Adapter_Lucene_SearchHitAdapter($queryHit);
                                $curdoc = $opusHit->convertToSearchHit();
                                if ($curdoc !== false) array_push($hitlistarray, $curdoc);
                        }
                        else
                        {
                                $key = array_search($docid, $done);
                                #$opusfile = new OPUSDocumentFile($document->getFieldValue('source'), $docid);
                                #$hitlistarray[$key]->addFile($opusfile);
                        }
                }
        }
        #$hitlist->add($curdoc);
        $hitlist->query = $query;
        foreach ($hitlistarray as $singlehit)
        {
        	$hitlist->add($singlehit);
        }
    return $hitlist;
  } // end of member function find


} // end of LuceneAdapter