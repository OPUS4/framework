<?php
/**
 * Model for document notes table.
 */
class Opus_Db_DocumentFiles extends DbConnection {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_files';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_files_id';
}