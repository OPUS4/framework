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
 * @package     Opus_Document
 * @author      Oliver Marahrens (o.marahrens@tu-harburg.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: $
 */

class Opus_Document_Document
{
	var $docId;
	
	function __construct($id)
	{
		$this->docId = $id;
	}
	
	function getFieldValue($fieldName)
	{
		
	}
	
	function getAssociatedFiles()
	{
		
	}
	
	function getAllFieldValues()
	{
		//creates an array to loop over the databases
        $tables= array (
        'documents' => new Opus_Db_Documents(), 
        'document_enrichments' => new Opus_Db_DocumentEnrichments(), 
        'document_files' => new Opus_Db_DocumentFiles(), 
        'document_identifiers' => new Opus_Db_DocumentIdentifiers(), 
        'document_notes' => new Opus_Db_DocumentNotes(), 
        'document_patents' => new Opus_Db_DocumentPatents(), 
        //'document_statistics' => new Opus_Db_DocumentStatistics(), 
        'document_subjects' => new Opus_Db_DocumentSubjects(), 
        'document_title_abstracts' => new Opus_Db_DocumentTitleAbstracts());
        
        $resultarray = array();
        
        foreach ($tables as $tableName => $table)
        {
            $where= $table->getAdapter()->quoteInto('documents_id = ?', $this->docId);
            $db = $table->getAdapter();
            $select = $db->select();
            $select->from($tableName);
            $select->where($where);
            $docresult = $db->fetchAll($select);
            if ($tableName == 'documents')
            {
            	// these fields are no arrays, just push them to the resultarray
            	foreach ($docresult as $key => $value)
            	{
            		$resultarray[$key] = $value;
            	}
            }
            else
            {
            	$newarray = array();
            	// all other fields are arrays, so prepare them as array and push them to the resultarray after that
            	foreach ($docresult as $key => $value)
            	{
            		$newarray[$key] = $value; 
            	}
            	array_push($resultarray, $newarray);
            }
        }
        return ($resultarray);
	}
}
?>