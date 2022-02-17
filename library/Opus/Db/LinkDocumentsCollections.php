<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @copyright   Copyright (c) 2010-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus\Db
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Db;

/**
 * Table gateway class to table 'link_documents_collections'.
 *
 * phpcs:disable
 */
class LinkDocumentsCollections extends TableGateway
{
    /**
     * DB table name.
     *
     * @var string
     */
    protected $_name = 'link_documents_collections';

    /**
     * DB table primary key name.
     *
     * @var string
     */
    protected $_primary = ['document_id', 'collection_id'];

    /**
     * Map foreign keys in this table to the column in the table they originate
     * from (i.e. the referenced table)
     *
     * @var array
     */
    protected $_referenceMap = [
        'Documents'       => [
            'columns'       => 'document_id',
            'refTableClass' => Documents::class,
            'refColumns'    => 'id',
        ],
        'Collections'     => [
            'columns'       => 'collection_id',
            'refTableClass' => Collections::class,
            'refColumns'    => 'id',
        ],
        'CollectionRoles' => [
            'columns'       => 'role_id',
            'refTableClass' => CollectionsRoles::class,
            'refColumns'    => 'id',
        ],
    ];
}
