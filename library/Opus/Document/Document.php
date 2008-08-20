<?
require_once 'Db/Db.php';
class Opus_Document
{
    private $documentData;
    private $documentsId;
    private function _log($string)
    {
        print ($string);
    }
    /**
    * set the document data for a Opus_Document object
    * 
    */
    public function setData($data)
    {
        //TODO check for validity ???
        $this->documentData= $data;
    }
    public function setDocumentsId($documentsId)
    {
        $this->documentsId= $documentsId;
    }
    public function Opus_Document($data= null, $documentsId= null)
    {
        if (is_array($data))
        {
            $this->setData($data);
        }
        else
        {
            $this->setData(null);
        }
        $this->setDocumentsId($documentsId);
    }
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
    * saves data to database, without checking the correctness of it
    *
    * updates the database, if an document id is given, creates now document else
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
        'documents' => new Opus_Data_Db_Documents(), 'document_enrichments' => new Opus_Data_Db_Document_Enrichments(), 'document_files' => new Opus_Data_Db_Document_Files(), 'document_identifiers' => new Opus_Data_Db_Document_Identifiers(), 'document_notes' => new Opus_Data_Db_Document_Notes(), 'document_patents' => new Opus_Data_Db_Document_Patents(), 'document_statistics' => new Opus_Data_Db_Document_Statistics(), 'document_subjects' => new Opus_Data_Db_Document_Subjects(), 'document_title_abstracts' => new Opus_Data_Db_Document_Title_Abstracts());
        //partition data to different tables
        foreach ($this->documentData as $key => $value)
        {
            $keyInSchema= false;
            foreach ($tables as $tableName => $table)
            {
                print ("<br>" . (is_array($value)));
                print_r($value);
                print ("<br>");
                //print_r(array_keys($value));
                //print("<b style=\"color:red\">  ------ </b> ");
                //print_r(array_values($table->info('cols')));
                if (is_array($value) && array_intersect(array_keys($value), array_values($table->info('cols'))) == array_keys($value))
                {
                    print ("tablename: $tableName");
                    $data[$tableName][]= $value;
                    $keyInSchema= true;
                    break;
                }
                if (!(is_array($value)) && in_array($key, array_values($table->info('cols'))))
                {
                    $data[$tableName][$key]= $value;
                    $keyInSchema= true;
                    break;
                }
            }
            if (!$keyInSchema)
            {
                if (is_array($value))
                {
                    throw new Exception('one of keys [' . implode(', ', array_keys($value)) . '] is not a key in database schema');
                }
                else
                {
                    throw new Exception($key . ' is not a key in database schema');
                }
            }
        }
        $noDocuments= false;
        if ($this->documentsId == null)
        {
            $this->documentsId= (int) $tables['documents']->insert($data['documents']);
            $this->_log("Document with document id $this->documentsId added, now trying to add additional data");
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
            print ("<br>");
            print ($tableName);
            $where= $table->getAdapter()->quoteInto('documents_id = ?', $this->documentsId);
            //if not associated array (repeatable data) iterate over data entry
            if (!$this->_is_assoc($data[$tableName]))
            {
                foreach ($data[$tableName] as $repeatableData)
                {
                    //TODO was soll passieren, wenn wiederholbare daten hinzugefügt werden? alle alten daten löschen oder neue einfach hinzufügen?
                    /*if ($newEntry)
                    {*/
                        $repeatableData['documents_id']= $this->documentsId;
                        $table->insert($repeatableData);
                    /*}
                    else
                    {
                        $table->update($repeatableData, $where);
                    }*/
                }
            }
            else
            {
                //TODO gleiches wie oben, wie sollen wiederholbare datensätze beim aktualisieren behandelt werden? 
               /* if ($newEntry)
                {*/
                    $data[$tableName]['documents_id']= $this->documentsId;
                    $table->insert($data[$tableName], $where);
                /*}
                else
                {
                    $table->update($data[$tableName], $where);
                }*/
            }
        }
        return $this->documentsId;
    }
}
?>