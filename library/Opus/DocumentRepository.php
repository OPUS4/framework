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
 * @copyright   Copyright (c) 2022, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Exception;
use Opus\Common\Date;
use Opus\Common\DocumentRepositoryInterface;
use Opus\Common\Log;
use Opus\Db\TableGateway;

use function count;
use function is_array;
use function preg_match;

class DocumentRepository implements DocumentRepositoryInterface
{
    /** @var string TableGateway class for 'documents' table TODO use constant */
    protected static $documentTableClass = Db\Documents::class;

    /**
     * Returns the earliest date (server_date_published) of all documents.
     *
     * TODO return Date instead of string
     *
     * @return string|null /^\d{4}-\d{2}-\d{2}$/ on success, null otherwise
     */
    public function getEarliestPublicationDate()
    {
        $table     = TableGateway::getInstance(self::$documentTableClass);
        $select    = $table->select()->from($table, 'min(server_date_published) AS min_date')
            ->where('server_date_published IS NOT NULL')
            ->where('TRIM(server_date_published) != \'\'');
        $timestamp = $table->fetchRow($select)->toArray();

        if (! isset($timestamp['min_date'])) {
            return null;
        }

        $matches = [];
        if (preg_match("/^(\d{4}-\d{2}-\d{2})T/", $timestamp['min_date'], $matches) > 0) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Bulk update of ServerDateModified for documents matching selection
     *
     * TODO remove support for string date? - check if it works at all
     *
     * @param string|Date $date Date-Object holding the date to be set
     * @param int[]       $ids array of document ids
     */
    public function setServerDateModifiedForDocuments($date, $ids)
    {
        // Update wird nur ausgeführt, wenn IDs übergeben werden
        if ($ids === null || ! is_array($ids) || count($ids) === 0) {
            return;
        }

        $table = TableGateway::getInstance(self::$documentTableClass);

        $where = $table->getAdapter()->quoteInto('id IN (?)', $ids);

        try {
            $table->update(['server_date_modified' => "$date"], $where);
        } catch (Exception $e) {
            $logger = Log::get();
            if ($logger !== null) {
                $logger->err(__METHOD__ . ' ' . $e);
            }
        }
    }
}
