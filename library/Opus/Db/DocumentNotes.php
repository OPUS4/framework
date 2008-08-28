<?php

/**
 * Model for document notes table.
 */
class Opus_Db_DocumentNotes extends DbConnection {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_notes';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_notes_id';
}