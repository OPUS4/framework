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

class Opus_Search_Index_Indexer {
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
        $this->indexPath = $registry->get('Zend_LuceneIndexPath');
        $this->entryindex = Zend_Search_Lucene::create($this->indexPath);
	}

	/**
	 * Stores a document in the Search Engine Index
	 *
	 * @param Opus_Document $doc Model of the document that should be added to the index
	 * @throws Exception Exceptions from Zend_Search_Lucene are thrown
	 * @return void
	 */
	public function addDocumentToEntryIndex(Opus_Document $doc) {
    	try {
			#print_r($doc->getDocument());
    	    $analyzedDocs = $this->analyzeDocument($doc);
            foreach ($analyzedDocs as $analyzedDoc) {
			 	$this->entryindex->addDocument(new Opus_Search_Index_Document($analyzedDoc));
            }
			# Do not flush, it will work without it
			# Flush sends some return value that Zend interprets as header information
			#flush();
		} catch (Exception $e) {
			#echo $e->getMessage();
			throw $e;
        }
	}

	private function analyzeDocument(Opus_Document $doc) {
        #print_r($doc->toArray());
        $docarray = array();
        $document['docid'] = $doc->getId();
        $document['source'] = 'metadata';
        $document['year'] = $doc->getField('PublishedYear')->getValue();
        $document['author'] = $this->getAuthors($doc->getField('PersonAuthor')->getValue());
        $document['urn'] = $doc->getUrn()->getValue();
        $document['content'] = '';
        $titles = $doc->getField('TitleMain')->getValue();
        $title_count = count($titles);
        $abstracts = $doc->getField('TitleAbstract')->getValue();
        $returnarray = array();
        $langarray = array();
        // Look at all titles of the document
        foreach ($titles as $title)
        {
            $document['title'] = $title->getValue();
            $lang = $title->getLanguage();
            $document['abstract'] = $this->getAbstract($abstracts, $lang);
            array_push($langarray, $lang);
            array_push($returnarray, $document);
        }
        // index files and add the last title and last abstract to data set
        $files = $doc->getField('File')->getValue();
        $file_count = count($files);
        foreach ($files as $file)
        {
        	$document['source'] = $file->getPathName();
        	$document['content'] = $this->getFileContent($file); 
        	array_push($returnarray, $document);
        }
        // Look if there are non-indexed abstracts left
        $document['source'] = 'metadata';
        $document['content'] = '';
        $not_processed_abstracts = $this->checkAbstractLanguage($abstracts, $langarray);
        $document['title'] = $doc->getTitleMain(0)->getValue();
        foreach ($not_processed_abstracts as $abstract) {
            $document['abstract'] = $abstract;
            array_push($returnarray, $document);
        }
        // return array of documents to index
        return $returnarray;
	}

	private function getAbstract($abstracts, $language) {
        foreach ($abstracts as $abstract)
        {
            if ($abstract->getLanguage() === $language) {
                return $abstract->getValue();
            }
        }
        return null;
	}

	private function checkAbstractLanguage($abstracts, array $languages) {
        $not_processed = array();
	    foreach ($abstracts as $abstract)
        {
            if (false === in_array($abstract->getLanguage(), $languages)) {
                array_push($not_processed, $abstract->getValue());
            }
        }
        return $not_processed;
	}

	private function getAuthors($authors) {
	    $aut = array();
	    foreach ($authors as $author)
	    {
	        array_push($aut, $author->getLastName() . ', ' . $author->getFirstName());
	    }
	    if (count($aut) > 0) {
	       return implode('; ', $aut);
	    }
	    return null;
	}
	
	private function getFileContent($file) {
       //FIXME: Hard coded path!
        $path_prefix = '../workspace/files/' . $file->getDocumentId();
		switch ($file->getMimeType())
		{
			case 'application/pdf':
				$fulltext = Opus_Search_Index_FileFormatConverter_PdfDocument::toText($path_prefix . '/' . $file->getPathName()); 
				break;
			default:
				$fulltext = null;
		}
		return $fulltext;
	}
}