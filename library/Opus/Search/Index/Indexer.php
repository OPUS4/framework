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
		// Queue starten
		//IndexerQueue::getInstance();
		// Registrieren bei den Events, die beobachtet werden sollen
		//$EnabledDocument = EnabledDocument::getInstance();
		//$DeletedDocument = DeletedDocument::getInstance();
		//$EnabledDocument->register(new IndexEventListener("NewDocumentWatcher"));
		//$DeletedDocument->register(new IndexEventListener("DeleteWatcher"));
	}

	/**
	 * Stores a document in the Search Engine Index
	 * 
	 * @param Opus_Search_Adapter_DocumentAdapter $doc DocumentAdapter from the document that should be added to the index
	 * @return void
	 */
	public function addDocumentToEntryIndex(Opus_Search_Adapter_DocumentAdapter $doc) {
    	try {
			#if (count($doc->getAssociatedFiles()) == 0) {
				$document = $doc->getDocument();
				$this->entryindex->addDocument(new Opus_Search_Index_Document($doc));
			#} else {
			#	$n = 0;
			#	$i = 0;
			#	foreach ($doc->getAssociatedFiles() as $docfile) {
			#		$i++;
			#		// Das indexierte Dokument dem Index hinzufuegen
			#		echo date("Y-m-d H:i:s").": Indexiere ".$docfile->_path."....\n";
			#		try {
			#			$docfile->loadFulltext();
			#			$this->entryindex->addDocument(new OPUSIndexEntry($doc, $docfile));
			#		} catch (FileFormatException $e) {
			#			$n++;
			#			echo $e->getMessage()."\n";
			#			// $n gibt die Anzahl der Exceptions an, die f.r dieses Werk schon geworfen wurden
			#			// Die Metadaten m.ssen nur indexiert werden, wenn genau so viele Exceptions
			#			// aufgetreten sind wie die Anzahl der zu indexierenden Dateien betr.gt
			#			// (d.h. wenn bislang keine Datei f.r das Dokument indexiert
			#			// werden konnte und das Ende der Liste erreicht ist)
			#			if ($i === (count($doc->getAssociatedFiles())) && $n === (count($doc->getAssociatedFiles()))) {
			#				echo date("Y-m-d H:i:s").": Konnte keine Daten f.r Eintrag ".$doc->getProperty('id')." finden! Indexiere Metadaten f.r ".$doc->getProperty('id')."....\n";
			#				$this->entryindex->addDocument(new OPUSIndexEntry($doc));
			#			}
			#		}
			#	}
			#}
			flush();
		} catch (Exception $e) {
			#echo $e->getMessage() . '<br/>\n';
			throw $e;
        }
	}
}