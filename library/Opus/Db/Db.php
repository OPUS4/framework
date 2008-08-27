<?php
/**
 * Defines a model for accessing the documents table.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id: Accounts.php 480 2008-07-21 07:28:56Z claussnitzer $
 */

class DbConnection extends Zend_Db_Table
{
    /**
     * sets up database
     * 
     * overrides Zend_Db_Table::_setupDatabaseAdapter
     */
    protected function _setupDatabaseAdapter()
    {
        //TODO get connection information from ini-file
        $options= array(
            'host'=> 'localhost',
            'username'=> 'opus_mysql',
            'password'=> 'my2005S',
            'dbname'=> 'opus400'
        );
        $db= Zend_Db :: factory('mysqli', $options);
        $this->_setAdapter($db);
        $db->query('SET NAMES utf8');
        parent :: _setupDatabaseAdapter();
    }
    
    /**
     * action performed if connection is closed
     */
    public function closeConnection()
    {
        $this->getAdapter()->closeConnection();
    }
}

/**
 * Model for documents table.
 */
class Opus_Data_Db_Documents extends DbConnection {

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

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Notes extends DbConnection {

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

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Patents extends DbConnection {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_patents';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_patents_id';
}

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Enrichments extends DbConnection {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_enrichments';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_enrichments_id';
}

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Files extends DbConnection {

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

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Identifiers extends DbConnection {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_identifiers';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_identifiers_id';
}

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Statistics extends DbConnection {

    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_statistics';

    /**
     * Real database name of the primary key column.
     *
     * @var string
     */
    protected $_primary = 'document_statistics_id';
}

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Subjects extends DbConnection {

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

/**
 * Model for document notes table.
 */
class Opus_Data_Db_Document_Title_Abstracts extends DbConnection {

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
?>
