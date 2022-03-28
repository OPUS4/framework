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
 * @copyright   Copyright (c) 2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db2;

use Doctrine\DBAL\Query\QueryBuilder;
use Opus\Model\ModelException;

/**
 * Table gateway class to nested sets.
 *
 * WARNING: This class does not use transactions.  If you want to be transaction
 * WARNING: safe, beginTransaction() before using methods from here and commit()
 * WARNING: when you're done.
 *
 * ANOTHER WARNING: Always make sure, $treeId, $id and all the parameters you
 * ANOTHER WARNING: are clean.  Currently, we assume that tree_id and id are
 * ANOTHER WARNING: integer datatypes and explicitly cast to int.  This might
 * ANOTHER WARNING: result in strange behaviour, if you're not using integers
 * ANOTHER WARNING: or submitting NULL values.
 *
 * TODO Reevaluate above warnings
 *
 * phpcs:disable
 */
abstract class NestedSet extends AbstractTableGateway
{
    /**
     * Returns the row data for the node of the given ID.
     *
     * @param  int $id Primary key of the node.
     * @throws ModelException
     * @return array
     */
    public function getNodeById($id)
    {
        $select = $this->selectNodeById($id);
        $row    = $select->execute()->fetchAssociative();

        if ($row === null) {
            throw new ModelException("Node $id not found.");
        }

        return $row;
    }

    /**
     * Returns an SQL query builder instance preconfigured with an SQL statement
     * for retrieving nodes by ID.
     *
     * @param  int $id Primary key of the node.
     * @return QueryBuilder
     */
    private function selectNodeById($id)
    {
        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('*')
            ->from($this->name, 'node')
            ->where("node.{$this->primary} = ?");

        return $select->setParameters([$id]);
    }

    /**
     * Returns the row data for all child nodes of the node with given ID.
     *
     * @param  int   $id The ID of the node whose children shall be returned.
     * @return array
     */
    public function getChildrenById($id)
    {
        $select = $this->selectChildrenById($id, 'id');

        return $select->execute()->fetchAllAssociative();
    }

    /**
     * Returns an SQL query builder instance preconfigured with an SQL statement
     * for fetching all children of the node with the given ID. A second
     * parameter allows the selection of the returned columns in the same format
     * as \QueryBuilder::select() takes.
     *
     * @param int   $id   The ID of the parent node.
     * @param mixed $cols The columns to show, defaults to '*'.
     *
     * @return QueryBuilder
     */
    public function selectChildrenById($id, $cols = '*')
    {
        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select($cols)
            ->from($this->name, 'node')
            ->where("node.{$this->parent} = ?")
            ->orderBy("node.{$this->left}", "ASC");

        return $select->setParameters([$id]);
    }

    /**
     * Returns the row data for all nodes with the given tree ID. Returns null
     * if nothing was found.
     *
     * @param  int   $treeId The ID of the nested-set structure whose nodes shall be returned.
     * @return array|null
     */
    public function getNodesByTreeId($treeId)
    {
        $queryBuilder = $this->getQueryBuilder();

        $select = $queryBuilder
            ->select('*')
            ->from($this->name)
            ->where("{$this->tree} = ?")
            ->orderBy("{$this->left}", "ASC");

        $select->setParameters([$treeId]);

        return $queryBuilder->execute()->fetchAllAssociative();
    }
}
