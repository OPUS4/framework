<?php

class Opus_Db_Documents extends Zend_Db_Table {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'documents';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'documents_id';
}