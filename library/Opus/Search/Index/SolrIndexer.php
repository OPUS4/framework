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
 * @version     $Id: Indexer.php 3834 2009-11-18 16:28:06Z becker $
 */

class Opus_Search_Index_SolrIndexer {
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
     * Document that should get indexed by this object
     * This should spare memory at all
     */
    private $docToIndex = null;

	/**
	 * Constructor
	 *
	 * @throws Zend_Search_Lucene_Exception Exception is thrown when there are problems with the index
	 */
	public function __construct($createIndex = false, $bufferedDocs = 3) {
        /*$registry = Zend_Registry::getInstance();
        $this->indexPath = $registry->get('Zend_LuceneIndexPath');
        try
        {
            if ($createIndex === true) {
            	$this->entryindex = Zend_Search_Lucene::create($this->indexPath);
            }
            else {
                $this->entryindex = Zend_Search_Lucene::open($this->indexPath);
            }
        }
        catch (Zend_Search_Lucene_Exception $zsle) {
            if (false !== strpos($zsle->getMessage(), 'Index doesn\'t exists in the specified directory.')) {
                // re-creating could cause deleting existing lucene search index
                $this->entryindex = Zend_Search_Lucene::create($this->indexPath);
            } else {
                throw $zsle;
            }
        }
        // Decrease desired memory for indexing by limiting the amount of documents in memory befory writing them to index 
        $this->entryindex->setMaxBufferedDocs($bufferedDocs);
        */
	}

	/**
	 * Stores a document in the Search Engine Index
	 *
	 * @param Opus_Document $doc Model of the document that should be added to the index
	 * @throws Exception Exceptions from Zend_Search_Lucene are thrown
	 * @return void
	 */
	public function addDocumentToEntryIndex(Opus_Document $doc)
	{
        $this->docToIndex = $doc;
        unset($doc);
    	$returnarray = array();

    	try {
    	    // remove existing entries
    	    // not necessary with solr
    	    //if (count($this->entryindex->find('docid:' . $doc->getId() . ' ')) > 0) {
    	    //    $this->removeDocumentFromEntryIndex($doc);
    	    //}
    	    $analyzedDocs = $this->analyzeDocument();
            unset($doc);
    	    foreach ($analyzedDocs as $analyzedDoc) {
			 	// TODO: print out exceptions and errors
			 	//if (true === array_key_exists('exception', $analyzedDoc))
			 	//{
			 	//	$returnarray[] = $analyzedDoc['source'] . ' in document ID ' . $analyzedDoc['docid'] . ': ' . $analyzedDoc['exception'];
			 	//}
			 	//else
			 	//{
            	    //$indexDoc = new Opus_Search_Index_Document($analyzedDoc);
			 		#echo "Memorybedarf nach Analyse " . memory_get_usage() . "\n";
            	    echo $this->addDocument($analyzedDoc);
			 		#echo "Memorybedarf nach Indizierung " . memory_get_usage() . "\n";
			 		$returnarray[] = "indexed document " . $analyzedDoc;
			 	//}
            }
		} catch (Exception $e) {
			throw $e;
        }
        unset($analyzedDoc);
        unset($analyzedDocs);
        return $returnarray;
	}

    /**
     * Removes a document from the Search Engine Index
     *
     * @param Opus_Document $doc Model of the document that should be removed to the index
     * @throws Exception Exceptions from Zend_Search_Lucene are thrown
     * @return void
     */
    public function removeDocumentFromEntryIndex(Opus_Document $doc = null)
    {
    	if ($doc !== null) {
    		$this->docToIndex = $doc;
    		unset ($doc);
    	}
        try {
            // Weird: some IDs are only found with adding whitespace behind the query...
            // So let's add a space behind the ID.
            $hits = $this->entryindex->find('docid:' . $this->docToIndex . ' ');
            foreach ($hits as $hit) {
                $this->entryindex->delete($hit->id);
            }
        } catch (Exception $e) {
            throw $e;
        }
        $this->entryindex->commit();
        #$this->entryindex->optimize();
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

	private function analyzeDocument() {
        
        // Set up filter and get XML-Representation of filtered document.
        $type = new Opus_Document_Type($this->docToIndex->getType());
        $filter = new Opus_Model_Filter;
        $filter->setModel($this->docToIndex);
        $xml = $filter->toXml();

        // Set up XSLT-Stylesheet
        $xslt = new DomDocument;
        $template = 'solr.xslt';
        $xslt->load(dirname(__FILE__) . '/' . $template);

        // Set up XSLT-Processor
        $proc = new XSLTProcessor;
        $proc->importStyleSheet($xslt);

        $docArray = array();
        // index files (each file will get one data set)
        if (count($this->docToIndex->getFile()) > 0) {
            $files = $this->docToIndex->getFile();
            $file_count = count($files);
            $numberOfIndexableFiles = $file_count;
            foreach ($files as $file)
            {
            	try {
           	        $proc->setParameter('', 'fulltext', $this->getFileContent($file));
           	        $proc->setParameter('', 'source', $file->getPathName());
           	        $document = $proc->transformToXML($xml);
       	        	array_push($docArray, $document);
                }
                catch (Exception $e) {
            	    #$proc->setParameter('', 'source', $file->getPathName());
            	    #$proc->setParameter('', 'content', '');
            	    #$document = $proc->transformToXML($xml);
            	    $document = $e->getMessage();
            	    $numberOfIndexableFiles--;
            	    array_push($docArray, $document);
                }
            }
        } else {
            $numberOfIndexableFiles = 0;
        }
        // if there is no file (or only non-readable ones) associated with the document, index only metadata
        if ($numberOfIndexableFiles === 0)
        {
            $proc->setParameter('', 'source', 'metadata');
            $proc->setParameter('', 'fulltext', '');
            $document = $proc->transformToXML($xml);
            array_push($docArray, $document);
        }

        return ($docArray);
	}

    private function getFileContent($file) {
        $fulltext = '';
        //FIXME: Hard coded path!
        $path_prefix = '../workspace/files/' . $file->getDocumentId();
		$mimeType = $file->getMimeType();
		if (substr($mimeType, 0, 9) === 'text/html') {
			$mimeType = 'text/html';
		}
		try {
			$fileToConvert = realpath($path_prefix . '/' . addslashes($file->getPathName()));
		    switch ($mimeType)
		    {
			    case 'application/pdf':
				    $fulltext = Opus_Search_Index_FileFormatConverter_PdfDocument::toText($fileToConvert);
				    break;
			    case 'application/postscript':
				    $fulltext = Opus_Search_Index_FileFormatConverter_PsDocument::toText($fileToConvert);
				    break;
			    case 'text/html':
    				$fulltext = Opus_Search_Index_FileFormatConverter_HtmlDocument::toText($fileToConvert);
	    			break;
			    case 'text/plain':
    				$fulltext = Opus_Search_Index_FileFormatConverter_TextDocument::toText($fileToConvert);
	    			break;
		    	default:
			    	throw new Exception('No converter for MIME-Type ' . $mimeType);
		    }
		}
		catch (Exception $e) {
			throw $e;
		}
		return $fulltext;
	}
	
	private function addDocument($solrDoc) {
		// HTTP-Header vorbereiten
		$out  = "POST /solr/update HTTP/1.1\r\n";
		$out .= "Host: localhost\r\n";
		$out .= "Content-type: text/xml; charset=utf-8\r\n";
		$out .= "Content-length: ". strlen($solrDoc) ."\r\n";
		$out .= "User-Agent: SolrIndexer\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "\r\n";
		$out .= $solrDoc;
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
