<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus;

use Exception;
use Opus\Common\CollectionRepositoryInterface;
use Opus\Db\TableGateway;

use function is_array;

/**
 * TODO rename functions, maybe merge some (use option to switch between search criteria)
 *      fetchCollectionsByRoleNumber -> fetchCollectionsByRoleAndNumber (easier to understand)
 */
class CollectionRepository implements CollectionRepositoryInterface
{
    /** @var string Table class for collections. */
    public const COLLECTIONS_TABLE_CLASS = Db\Collections::class;

    /**
     * Removes document from current collection by deleting from the relation
     * table "link_documents_collections".
     *
     * @param null|int $documentId
     * @return int
     *
     * TODO: Move method to Opus\Db\LinkDocumentsCollections.
     * TODO: Usable return value.
     * TODO TEST not tested yet
     */
    public function unlinkCollectionsByDocumentId($documentId = null)
    {
        if ($documentId === null) {
            return 0; // TODO throw exception?
        }

        $table = TableGateway::getInstance(Db\LinkDocumentsCollections::class);
        $db    = $table->getAdapter();

        $condition = [
            'document_id = ?' => $documentId,
        ];

        return $db->delete("link_documents_collections", $condition);
    }

    /**
     * Returns all collection for given (role_id, collection number) as array
     * with Opus\Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int    $roleId
     * @param  string $number
     * @return array   Array of Opus\Collection objects.
     *
     * TODO TEST not tested
     */
    public function fetchCollectionsByRoleNumber($roleId, $number)
    {
        if (! isset($number)) {
            throw new Exception("Parameter 'number' is required.");
        }

        if (! isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table  = TableGateway::getInstance(self::COLLECTIONS_TABLE_CLASS);
        $select = $table->select()->where('role_id = ?', $roleId)
            ->where('number = ?', "$number");
        $rows   = $table->fetchAll($select);

        return Collection::createObjects($rows);
    }

    /**
     * Returns all collection for given (role_id, collection name) as array
     * with Opus\Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int    $roleId
     * @param  string $name
     * @return array   Array of Opus\Collection objects.
     *
     * TODO TEST not tested
     */
    public function fetchCollectionsByRoleName($roleId, $name)
    {
        if (! isset($name)) {
            throw new Exception("Parameter 'name' is required.");
        }

        if (! isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table  = TableGateway::getInstance(self::COLLECTIONS_TABLE_CLASS);
        $select = $table->select()->where('role_id = ?', $roleId)
            ->where('name = ?', $name);
        $rows   = $table->fetchAll($select);

        return Collection::createObjects($rows);
    }

    /**
     * Returns all collection for given (role_id) as array
     * with Opus\Collection objects.  Always returning an array, even if the
     * result set has zero or one element.
     *
     * @param  int $roleId
     * @return array   Array of Opus\Collection objects.
     *
     * TODO TEST not tested
     */
    public function fetchCollectionsByRoleId($roleId)
    {
        if (! isset($roleId)) {
            throw new Exception("Parameter 'role_id' is required.");
        }

        $table  = TableGateway::getInstance(self::COLLECTIONS_TABLE_CLASS);
        $select = $table->select()->where('role_id = ?', $roleId);
        $rows   = $table->fetchAll($select);

        return Collection::createObjects($rows);
    }

    /**
     * Returns all collection_ids for a given document_id.
     *
     * @param  int $documentId
     * @return array  Array of collection Ids.
     *
     * FIXME: This method belongs to Opus\Db\Link\Documents\Collections
     * TODO TEST not tested
     */
    public function fetchCollectionIdsByDocumentId($documentId)
    {
        if (! isset($documentId)) {
            return [];
        }

        // FIXME: self::$tableGatewayClass not possible in static methods.
        $table = TableGateway::getInstance(self::COLLECTIONS_TABLE_CLASS);

        // FIXME: Don't use internal knowledge of foreign models/tables.
        // FIXME: Don't return documents if collection is hidden.
        $select = $table->getAdapter()->select()
            ->from("link_documents_collections AS ldc", "collection_id")
            ->where('ldc.document_id = ?', $documentId)
            ->distinct();

        return $table->getAdapter()->fetchCol($select);
    }

    /**
     * @param string         $term Search term for matching collections
     * @param int|array|null $roles CollectionRole IDs
     * @return array
     */
    public function find($term, $roles = null)
    {
        $table = TableGateway::getInstance(self::COLLECTIONS_TABLE_CLASS);

        $database = $table->getAdapter();

        $quotedTerm = $database->quote("%$term%");

        $select = $table->select()
            ->from("collections", ['Id' => 'id', 'RoleId' => 'role_id', 'Name' => 'name', 'Number' => 'number'])
            ->where("name LIKE $quotedTerm OR number LIKE $quotedTerm")
            ->distinct()
            ->order(['role_id', 'number', 'name']);

        if ($roles !== null) {
            if (! is_array($roles)) {
                $select->where('role_id = ?', $roles);
            } else {
                $select->where('role_id IN (?)', $roles);
            }
        }

        return $database->fetchAll($select);
    }
}
