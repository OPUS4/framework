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

class Opus_Search_Index_Document extends Zend_Search_Lucene_Document
{

    private $encoding = 'UTF-8';

    /**
     * Constructor
     *
     * @param Opus_Model_Document $document Document to index
     * @param Opus_Search_Adapter_FileAdapter     $file      (Optional) File to index
     */
    public function __construct(array $document)
    {
        if ($document['file'] !== null) {
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('docurl', $file->getURL(), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnStored('contents', $file->getFulltext(), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('source', $file->_path, $this->encoding));
        } else {
                #$this->addField(Zend_Search_Lucene_Field::UnIndexed('docurl', join("/", $doc['frontdoorUrl']), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnStored('contents', '', $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('source', 'Metadaten', $this->encoding));
        }
        $this->addField(Zend_Search_Lucene_Field::Keyword('docid', $document['id'], $this->encoding));
        #$this->addField(Zend_Search_Lucene_Field::UnIndexed('werkurl', $doc['frontdoorUrl']));
        $this->addField(Zend_Search_Lucene_Field::UnIndexed('year', $document['year'], $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('teaser', $document['title'], $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('title', $document['abstract'], $this->encoding));
        $authoriterator = $document['authors'];
        $aut = '';
        foreach ($authoriterator as $pers) {
            $aut .= $pers['LastName'] . ', ' . $pers['FirstName'];
            $aut .= '; ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('author', $aut, $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Keyword('urn', $document['urn'], $this->encoding));
    }


    /**
     * Constructor
     *
     * @param Opus_Model_Document $document Document to index
     * @param Opus_Search_Adapter_FileAdapter 	  $file 	 (Optional) File to index
     */
    /*public function __construct(Opus_Model_Document $document, Opus_Search_Adapter_FileAdapter $file = null)
    {
        $doc = $document;
        $docarray = $doc->toArray();
        #print_r($docarray);
        if ($file !== null) {
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('docurl', $file->getURL(), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnStored('contents', $file->getFulltext(), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('source', $file->_path, $this->encoding));
        } else {
                #$this->addField(Zend_Search_Lucene_Field::UnIndexed('docurl', join("/", $doc['frontdoorUrl']), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnStored('contents', '', $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('source', 'Metadaten', $this->encoding));
        }
        $this->addField(Zend_Search_Lucene_Field::Keyword('docid', $doc->getId(), $this->encoding));
        #$this->addField(Zend_Search_Lucene_Field::UnIndexed('werkurl', $doc['frontdoorUrl']));
        $this->addField(Zend_Search_Lucene_Field::UnIndexed('year', $doc->getPublishedYear(), $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('teaser', $doc->getTitleAbstract(0)->getTitleAbstractValue(), $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('title', $doc->getTitleMain(0)->getTitleAbstractValue(), $this->encoding));
        $authoriterator = $docarray['PersonAuthor'];
        $aut = '';
        foreach ($authoriterator as $pers) {
            $aut .= $pers['LastName'] . ', ' . $pers['FirstName'];
            $aut .= '; ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('author', $aut, $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Keyword('urn', $doc->getUrn()->getIdentifierValue(), $this->encoding));
    }*/

    public function getTitles() {

    }

    public function getAbstracts() {

    }

    public function addToIndex() {
        $doc = $document;
        $docarray = $doc->toArray();
        #print_r($docarray);
        if ($file !== null) {
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('docurl', $file->getURL(), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnStored('contents', $file->getFulltext(), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('source', $file->_path, $this->encoding));
        } else {
                #$this->addField(Zend_Search_Lucene_Field::UnIndexed('docurl', join("/", $doc['frontdoorUrl']), $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnStored('contents', '', $this->encoding));
                $this->addField(Zend_Search_Lucene_Field::UnIndexed('source', 'Metadaten', $this->encoding));
        }
        $this->addField(Zend_Search_Lucene_Field::Keyword('docid', $doc->getId(), $this->encoding));
        #$this->addField(Zend_Search_Lucene_Field::UnIndexed('werkurl', $doc['frontdoorUrl']));
        $this->addField(Zend_Search_Lucene_Field::UnIndexed('year', $doc->getPublishedYear(), $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('teaser', $doc->getTitleAbstract(0)->getTitleAbstractValue(), $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Text('title', $doc->getTitleMain(0)->getTitleAbstractValue(), $this->encoding));
        $authoriterator = $docarray['PersonAuthor'];
        $aut = '';
        foreach ($authoriterator as $pers) {
            $aut .= $pers['LastName'] . ', ' . $pers['FirstName'];
            $aut .= '; ';
        }
        $this->addField(Zend_Search_Lucene_Field::Text('author', $aut, $this->encoding));
        $this->addField(Zend_Search_Lucene_Field::Keyword('urn', $doc->getUrn()->getIdentifierValue(), $this->encoding));
    }
}