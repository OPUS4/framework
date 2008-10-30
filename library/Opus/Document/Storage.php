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
 * @author      Tobias Leidinger (SULB: tobias.leidinger@googlemail.com)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Provides methods to store metadata of a document in the opus database
 *
 * @category Framework
 * @package  Opus_Document
 */
class Opus_Document_Storage
{

    /**
     * Reference to data that is going to be stored
     *
     * @var array
     */
    private $documentData;

    /**
     * Id from database table documents, if the storage object is used for updating database
     *
     * @var int
     */
    private $documentsId;

    /**
     * Reference to logging facility.
     *
     * @var Zend_Log
     */
    protected $_logger = null;

    /**
     * log system messages
     *
     * @param string $string
     */
    private function _log($string)
    {
        // The use of print statements is prohibited!!
        // It would break the output of the whole application.
        // print ($string);

        // Use a logging component instead:
        $this->_logger->info('Opus_Document_Storage: ' . $string);
    }

    /**
     * Set the document data to the storage object
     *
     * Data has to have a valid structure, single valued fields are mapped from field name to their value
     * and multivalued fields are mapped from field name to an array of arrays of name-value pairs
     *
     * E.g. 'document_type'  => 'article',
     *      'licences_id'    => '5',
     *      'title_abstract' => array(
     *          array(
     *              'value' => 'deutscher Abstract',
     *              'language'  => 'de'),
     *          array(
     *              'value' => 'abstract in English',
     *              'language' => 'en')
     *      )
     * @param array $data Array associating fieldnames to values.
     * @throws InvalidArgumentException if data is not valid or there is a problem converting the labels
     * @return void
     *
     */
    private function _setData($data)
    {
        //TODO
        //try to read document type from $data
        //$documentType = $data['document_type'];
        //read document structure from xml file
        //$opusDocumentType = new Opus_Document_Type($documentType . '.xml');
        //validate data
        //if ($documentType->validate($data) === false) {
        //    throw new InvalidArgumentException("array not valid");
        //}

        $storageData = array();
        foreach ($data as $fieldName => $values) {
            switch ($fieldName) {
                case 'title_abstract':
                    foreach ($values as $value) {
                        //TODO handle situation if $value['value'] or $value['language'] is not set
                        //TODO or is data checked by a validator?
                        $storageData[$fieldName][] = array(
                            'title_abstract_value' => $value['value'],
                            'title_abstract_type' => 'abstract',
                            'title_abstract_language' => $value['language']);
                    }
                    break;
                case 'title_main':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'title_abstract_value' => $value['value'],
                            'title_abstract_type' => 'main',
                            'title_abstract_language' => $value['language']);
                    }
                    break;
                case 'title_parent':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'title_abstract_value' => $value['value'],
                            'title_abstract_type' => 'parent',
                            'title_abstract_language' => $value['language']);
                    }
                    break;
                case 'subject_swd':
                    foreach ($values as $value) {
                        $data = array(
                            'subject_value' => $value['value'],
                            'subject_type' => 'swd',
                            );
                        if (isset($value['language'])) {
                            $data['subject_language'] = $value['language'];
                        }
                        if (isset($value['external_key'])) {
                            $data['external_subject_key'] = $value['external_key'];
                        }

                        $storageData[$fieldName][] = $data;
                    }
                    break;
                case 'subject_ddc':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'subject_value' => $value['value'],
                            'subject_type' => 'ddc',
                            'subject_language' => $value['language'],
                            'external_subject_key' => $value['external_key']);
                    }
                    break;
                case 'subject_psyndex':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'subject_value' => $value['value'],
                            'subject_type' => 'psyndex',
                            'subject_language' => $value['language'],
                            'external_subject_key' => $value['external_key']);
                    }
                    break;
                case 'subject_msc2000':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'subject_value' => $value['value'],
                            'subject_type' => 'msc2000',
                            'subject_language' => $value['language'],
                            'external_subject_key' => $value['external_key']);
                    }
                    break;
                case 'subject_uncontrolled':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'subject_value' => $value['value'],
                            'subject_type' => 'uncontrolled',
                            'subject_language' => $value['language'],
                            'external_subject_key' => $value['external_key']);
                    }
                    break;
                case 'identifier_urn':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'identifier_value' => $value,
                            'identifier_type' => 'urn',
                            'identifier_label' => 'URN');
                    }
                    break;
                case 'identifier_url':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'identifier_value' => $value,
                            'identifier_type' => 'url',
                            'identifier_label' => 'Frontdoor-URL');
                    }
                    break;
                case 'notes_private':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'message' => $value['value'],
                            'creator' => $value['creator'],
                            'scope' => 'private');
                    }
                    break;
                case 'notes_public':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'message' => $value['value'],
                            'creator' => $value['creator'],
                            'scope' => 'public');
                    }
                    break;
                case 'notes_reference':
                    foreach ($values as $value) {
                        $storageData[$fieldName][] = array(
                            'message' => $value['value'],
                            'creator' => $value['creator'],
                            'scope' => 'reference');
                    }
                    break;
                default:
                    if (is_array($values) === true) {
                        throw new InvalidArgumentException('No multivalue definition found for '.$fieldName);
                    }
                    $storageData[$fieldName] = $values;

            }
        }

        $this->documentData= $storageData;
    }

    /**
     * set the id for the documents table, if needed
     *
     * @param int $documentsId
     * @return void
     */
    public function _setDocumentsId($documentsId)
    {
        $this->documentsId= $documentsId;
    }

    /**
     * Initialize an instance of Opus_Document_Storage with the given data and an optional documents id
     *
     * structure of $data specified in comment for $this->_setData
     *
     * @param array $data array with data
     * @param int $documentsId documents id
     * @throws InvalidArgumentException if no data array is given
     */
    public function __construct($data, $documentsId= null)
    {
        if (is_array($data))
        {
            $this->_setData($data);
        }
        else
        {
            throw new InvalidArgumentException('there has to be an data array');
        }
        $this->_setDocumentsId($documentsId);

        // Fetch logging class from the registry.
        $this->_logger = Zend_Registry::get('Zend_Log');
    }

    /**
     * check whether the array is associative or not
     *
     * @param array $array
     * @return boolean
     */

    private function _is_assoc($array)
    {
        foreach (array_keys($array) as $k => $v)
        {
            if ($k !== $v)
                return true;
        }
        return false;
    }
    /**
     *
     *
     * Saves data to database, without checking the correctness of it
     *
     * updates the database, if an document id is given, creates new document else
     * @return document id
     *
     */
    public function saveDocumentData()
    {
        if (is_null($this->documentsId))
        {
            $newEntry= true;
        }
        else
        {
            $newEntry= false;
        }
        //access to the databases
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
        //partition data to different tables
        foreach ($this->documentData as $key => $value)
        {
            if (is_array($value) === false) {
                if (in_array($key, array_values($tables['documents']->info('cols')))) {
                    $data['documents'][$key] = $value;
                    $keyInSchema = true;
                } else {
                    throw new Opus_Document_Exception('single valued fields have to belong to documents table');
                }
            } else {
                foreach ($value as $val) {
                    $keyInSchema = false;
                    foreach ($tables as $tableName => $table)
                    {
                        if ($tableName == 'documents') {
                            continue;
                        }

                        //check if the actual keys fit in table schema, using the intersection of actual keys and table keys
                        if (array_intersect(array_keys($val), array_values($table->info('cols'))) == array_keys($val))
                        {
                            $data[$tableName][]= $val;
                            $keyInSchema= true;
                            break;
                        }
                    }
                    if (!$keyInSchema)
                    {
                        throw new Exception('one of keys [' . implode(', ', array_keys($value)) . '] is not a key in database schema');
                    }
                }
            }

        }
        $noDocuments= false;
        if ($this->documentsId == null)
        {
            $this->documentsId= (int) $tables['documents']->insert($data['documents']);
            if ($this->documentsId != null) {
                $this->_log("Document with document id $this->documentsId added, now trying to add additional data");
            }
            $noDocuments= true;
        }
        if (!is_int($this->documentsId))
        {
            throw new Exception('Document_id has to be integer value');
        }
        foreach ($tables as $tableName => $table)
        {
            if (!isset ($data[$tableName]))
            {
                continue;
            }
            if ($tableName == 'documents' && $noDocuments)
            {
                continue;
            }
            $where= $table->getAdapter()->quoteInto('documents_id = ?', $this->documentsId);
            //if not associated array (repeatable data) iterate over data entry
            if (!$this->_is_assoc($data[$tableName]))
            {
                foreach ($data[$tableName] as $repeatableData)
                {
                    $repeatableData['documents_id']= $this->documentsId;
                    $table->insert($repeatableData);
                }
            }
            else
            {
                $data[$tableName]['documents_id']= $this->documentsId;
                $table->insert($data[$tableName], $where);
            }
        }
        return $this->documentsId;
    }
}
?>