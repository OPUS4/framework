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
 * @version     $Id: Indexer.php 2222 2009-03-20 13:04:15Z marahrens $
 */

class Opus_Search_Index_PersonIndexer {
	/**
	 * Index variable
	 *
	 * @var Zend_Search_Lucene Index for the search engine
	 * @access private
	 */
	private $entryindex;

	/**
	 * Index path
	 *
	 * @var String Path to the index for the search engine
	 * @access private
	 */
	private $indexPath;

	/**
	 * Constructor
	 *
	 * @throws Zend_Search_Lucene_Exception Exception is thrown when there are problems with the index
	 */
	public function __construct() {
        $registry = Zend_Registry::getInstance();
        $this->indexPath = $registry->get('Zend_LucenePersonsIndexPath');
        $this->entryindex = Zend_Search_Lucene::create($this->indexPath);
	}

	/**
	 * Stores a document in the Search Engine Index
	 *
	 * @param Opus_Document $doc Model of the document that should be added to the index
	 * @throws Exception Exceptions from Zend_Search_Lucene are thrown
	 * @return void
	 */
	public function addDocumentPersonsToIndex(Opus_Document $doc) 
	{
    	try {
    	    $analyzedDocs = $this->analyzeDocument($doc);
    	    foreach ($analyzedDocs as $d) {
    	        $this->entryindex->addDocument($d);
    	    }
		} catch (Exception $e) {
			throw $e;
        }
	}

	/**
	 * Finalizes the entry in Search Engine Index
	 *
	 * @return void
	 */
	public function finalize() {
		$this->entryindex->commit();
    	$this->entryindex->optimize();
    	flush();
	}

	private function analyzeDocument(Opus_Document $doc) {
        $persons = array();
        // Does the field exist?
        if (count($doc->getField('PersonAuthor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonAuthor')->getValue()) > 0)
            {
                foreach ($this->getAuthors($doc->getField('PersonAuthor')->getValue()) as $pers) {
                    foreach ($pers as $id) {
                        $persons[] = new Opus_Search_Index_Person(array('role' => 'author', 'id' => $id));
                    }
                }
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonContributor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonContributor')->getValue()) > 0)
            {
                foreach ($this->getAuthors($doc->getField('PersonContributor')->getValue()) as $pers) {
                    foreach ($pers as $id) {
                        $persons[] = new Opus_Search_Index_Person(array('role' => 'contributor', 'id' => $id));
                    }
                }
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonAdvisor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonAdvisor')->getValue()) > 0)
            {
                foreach ($this->getAuthors($doc->getField('PersonAdvisor')->getValue()) as $pers) {
                    foreach ($pers as $id) {
                        $persons[] = new Opus_Search_Index_Person(array('role' => 'advisor', 'id' => $id));
                    }
                }
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonEditor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonEditor')->getValue()) > 0)
            {
                foreach ($this->getAuthors($doc->getField('PersonEditor')->getValue()) as $pers) {
                    foreach ($pers as $id) {
                        $persons[] = new Opus_Search_Index_Person(array('role' => 'editor', 'id' => $id));
                    }
                }
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonReferee')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonReferee')->getValue()) > 0)
            {
                foreach ($this->getAuthors($doc->getField('PersonReferee')->getValue()) as $pers) {
                    foreach ($pers as $id) {
                        $persons[] = new Opus_Search_Index_Person(array('role' => 'referee', 'id' => $id));
                    }
                }
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonOther')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonOther')->getValue()) > 0)
            {
                foreach ($this->getAuthors($doc->getField('PersonOther')->getValue()) as $pers) {
                    foreach ($pers as $id) {
                    	$persons[] = new Opus_Search_Index_Person(array('role' => 'other', 'id' => $id));
                    }
                }
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonTranslator')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonTranslator')->getValue()) > 0)
            {
                foreach ($this->getAuthors($doc->getField('PersonTranslator')->getValue()) as $pers) {
                    foreach ($pers as $id) {
                    	$persons[] = new Opus_Search_Index_Person(array('role' => 'translator', 'id' => $id));
                    }
                }
            }
        }
        
        // return array of documents to index
        return $persons;
	}

	private function getAuthors($authors) {
	    $aut = array();
	    foreach ($authors as $author)
	    {
	        array_push($aut, $author->getId());
	    }
	    return $aut;
	}
}