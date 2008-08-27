<?php
/**
 *
 */

/**
 * Model class for database operations on table document_files.
 *
 * @package     Opus_Application_Framework
 * @subpackage  Db_Adapter_Pdo
 *
 */
class Opus_File_DocumentFilesModel extends Zend_Db_Table {
    /**
     * Contain table name.
     *
     * @var string
     */
    protected $_name = 'document_files';

    /**
     * Contain primary key name
     *
     * @var string
     */
    protected $_primary = 'document_files_id';
}