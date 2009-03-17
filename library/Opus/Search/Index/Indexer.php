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
            	$doc = new Opus_Search_Index_Document($analyzedDoc);
				#print_r($doc);
			 	$this->entryindex->addDocument($doc);
            }
			# Do not flush, it will work without it
			# Flush sends some return value that Zend interprets as header information
			#flush();
		} catch (Exception $e) {
			#echo $e->getMessage();
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
        $docarray = array();
        $returnarray = array();
        $langarray = array();

        $document['docid'] = $doc->getId();
        $document['year'] = $doc->getField('CompletedYear')->getValue();
        $document['urn'] = '';
        if (true === is_array($doc->getField('IdentifierUrn')->getValue())) {
            $urnCount = count($doc->getField('IdentifierUrn'));
            if ($urnCount > 0)
            {
                // Does the field have content?
                if (count($doc->getField('IdentifierUrn')->getValue()) > 0)
                {
                    for ($n = 0; $n < $urnCount; $n++)
                    {
            	        $document['urn'] .= $doc->getIdentifierUrn($n)->getValue() . ' ';
                    }
                }
            }
        }
        else {
        	$document['urn'] .= $doc->getIdentifierUrn()->getValue() . ' ';
        }
        $document['isbn'] = '';
        if (true === is_array($doc->getField('IdentifierIsbn')->getValue())) {
            $isbnCount = count($doc->getField('IdentifierIsbn'));
            if ($isbnCount > 0)
            {
                // Does the field have content?
                if (count($doc->getField('IdentifierIsbn')->getValue()) > 0)
                {
                    for ($n = 0; $n < $isbnCount; $n++)
                    {
            	        $document['isbn'] .= $doc->getIdentifierIsbn($n)->getValue() . ' ';
                    }
                }
            }
        }
        else {
        	$document['isbn'] .= $doc->getIdentifierIsbn()->getValue() . ' ';
        }
 
        $document['author'] = '';
        // Does the field exist?
        if (count($doc->getField('PersonAuthor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonAuthor')->getValue()) > 0)
            {
                $document['author'] = $this->getAuthors($doc->getField('PersonAuthor')->getValue());
            }
        }
        $document['persons'] = '';
        // Does the field exist?
        if (count($doc->getField('PersonContributor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonContributor')->getValue()) > 0)
            {
        	    $document['persons'] .= $this->getAuthors($doc->getField('PersonContributor')->getValue());
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonAdvisor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonAdvisor')->getValue()) > 0)
            {
        	    $document['persons'] .= $this->getAuthors($doc->getField('PersonAdvisor')->getValue());
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonEditor')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonEditor')->getValue()) > 0)
            {
        	    $document['persons'] .= $this->getAuthors($doc->getField('PersonEditor')->getValue());
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonReferee')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonReferee')->getValue()) > 0)
            {
        	    $document['persons'] .= $this->getAuthors($doc->getField('PersonReferee')->getValue());
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonOther')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonOther')->getValue()) > 0)
            {
        	    $document['persons'] .= $this->getAuthors($doc->getField('PersonOther')->getValue());
            }
        }
        // Does the field exist?
        if (count($doc->getField('PersonTranslator')) > 0)
        {
            // Does the field have content?
            if (count($doc->getField('PersonTranslator')->getValue()) > 0)
            {
        	    $document['persons'] .= $this->getAuthors($doc->getField('PersonTranslator')->getValue());
            }
        }
        
        $document['doctype'] = $doc->getType();

        $titles = $doc->getField('TitleMain')->getValue();
        $title_count = count($titles);
        $abstracts = $doc->getField('TitleAbstract')->getValue();

        // Look at all titles of the document
        $document['title'] = '';
        $document['abstract'] = '';
        foreach ($titles as $title)
        {
            $document['title'] .= ' ' . $title->getValue();
            $lang = $title->getLanguage();
            $document['abstract'] .= ' ' . $this->getAbstract($abstracts, $lang);
            array_push($langarray, $lang);
        }
        // Look if there are non-indexed abstracts left
        $not_processed_abstracts = $this->checkAbstractLanguage($abstracts, $langarray);
        foreach ($not_processed_abstracts as $abstract) {
            $document['abstract'] .= ' ' . $abstract;
        }

        // Missing fields
        $document['subject'] = '';
        $document['institute'] = '';

        // index files (each file will get one data set)
        $files = $doc->getField('File')->getValue();
        $file_count = count($files);
        foreach ($files as $file)
        {
        	if ($this->getFileContent($file) !== null)
        	{
        	    $document['source'] = $file->getPathName();
        	    $document['content'] = $this->getFileContent($file); 
        	    array_push($returnarray, $document);
            }
        }
        // if there is no file (or a non-readable one) associated with the document, index only metadata
        if (count($returnarray) === 0)
        {
            $document['source'] = 'metadata';
            $document['content'] = '';
            array_push($returnarray, $document);
        }

        #print_r($returnarray);
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
		$mimeType = $file->getMimeType();
		if (substr($mimeType, 0, 9) === 'text/html') {
			$mimeType = 'text/html';
		}
		switch ($mimeType)
		{
			case 'application/pdf':
				$fulltext = Opus_Search_Index_FileFormatConverter_PdfDocument::toText($path_prefix . '/' . $file->getPathName()); 
				break;
			case 'text/html':
				$fulltext = Opus_Search_Index_FileFormatConverter_HtmlDocument::toText($path_prefix . '/' . $file->getPathName()); 
				break;
			default:
				$fulltext = null;
		}
		return $fulltext;
	}
}