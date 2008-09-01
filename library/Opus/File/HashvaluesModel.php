<?php
/**
 * Model class for database operations on table file_hashvalues.
 *
 * @package     Opus_Application_Framework
 * @subpackage  Db_Adapter_Pdo
 *
 */
class Opus_File_HashvaluesModel extends Zend_Db_Table {
    /**
     * Contains table name
     *
     * @var string
     */
    protected $_name = 'file_hashvalues';

    /**
     * Contains primary key names
     *
     * @var array
     */
    protected $_primary = array('file_hashvalues_id', 'document_files_id');
}