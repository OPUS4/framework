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
	 * @param Opus_Model_Document $doc Model of the document that should be added to the index
	 * @throws Exception Exceptions from Zend_Search_Lucene are thrown
	 * @return void
	 */
	public function addDocumentToEntryIndex(Opus_Model_Document $doc) {
    	try {
			#print_r($doc->getDocument());
    	    $this->entryindex->addDocument(new Opus_Search_Index_Document($doc));
			# Do not flush, it will work without it
			# Flush sends some return value that Zend interprets as header information
			#flush();
		} catch (Exception $e) {
			#echo $e->getMessage();
			throw $e;
        }
	}
}