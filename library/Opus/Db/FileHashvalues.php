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
 * @category    Framework
 * @package     Opus\Db
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db;

/**
 * Model class for database operations on table file_hashvalues.
 *
 * @category    Framework
 * @package     Opus\Db
 *
 */
class FileHashvalues extends TableGateway
{
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
    protected $_primary = ['file_id', 'type'];

    /**
     * Map foreign keys in this table to the column in the table they originate
     * from
     *
     * @var array $_referenceMap
     */
    protected $_referenceMap = [
        'DocumentFiles' => [
            'columns' => 'file_id',
            'refTableClass' => 'Opus\Db\DocumentFiles',
            'refColumns' => 'id'
        ]
    ];
}
