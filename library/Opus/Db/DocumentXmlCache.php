<?php
/**
 * LICENCE
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * @category    Framework
 * @package     Qucosa_Search
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2009-2010
 *              Saechsische Landesbibliothek - Staats- und Universitaetsbibliothek Dresden (SLUB)
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Table gateway class to table 'document_title_abstracts'.
 *
 * @category    Framework
 * @package     Opus_Db
 *
 */
class Opus_Db_DocumentXmlCache extends Opus_Db_TableGateway
{
    /**
     * Real database name of the documents table.
     *
     * @var string
     */
    protected $_name = 'document_xml_cache';

    /**
     * DB table primary key name.
     *
     * @var string
     */
    protected $_primary = [ 'document_id', 'xml_version'];
}
