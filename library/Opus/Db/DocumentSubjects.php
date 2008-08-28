<?php
/**
 * Model for document notes table.
 */
class Opus_Data_Db_DocumentSubjects extends DbConnection {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_subjects';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_subjects_id';
}