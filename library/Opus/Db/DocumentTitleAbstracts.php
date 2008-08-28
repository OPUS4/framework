<?php
/**
 * Model for document notes table.
 */
class Opus_Db_DocumentTitleAbstracts extends DbConnection {
    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_title_abstracts';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_title_abstracts_id';
}