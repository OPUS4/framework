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
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db2;

use Doctrine\ORM\ORMException;
use Exception;
use Gedmo\Tree\Entity\Repository\NestedTreeRepository;
use Opus\Model2\Collection;

class CollectionRepository extends NestedTreeRepository
{
    /**
     * Retrieve all Collection instances from the database.
     *
     * @return Collection[]
     * @throws ORMException
     */
    public function getAll()
    {
        return $this->findAll();
    }

    /**
     * Returns all Collection nodes with the given role ID. Always returns an array, even if the
     * result set has zero or one element.
     *
     * @param  int  $roleId The ID of the tree structure whose Collection nodes shall be returned.
     * @param  bool $sortResults (Optional) If true sort results by left ID.
     * @return Collection[]
     */
    public function fetchCollectionsByRoleId($roleId, $sortResults = false)
    {
        if (! isset($roleId)) {
            throw new Exception("Parameter 'roleId' is required.");
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $select = $queryBuilder->select('c')
            ->from(Collection::class, 'c')
            ->where('c.roleId = :roleId')
            ->setParameter('roleId', $roleId);

        if ($sortResults === true) {
            $select->orderBy('c.left', 'ASC');
        }

        $query = $select->getQuery();

        // TODO double check that getResult() always returns an array
        return $query->getResult();
    }

    /**
     * Returns all Collection nodes with the given role ID & name. Always returns an array, even if
     * the result set has zero or one element.
     *
     * @param  int    $roleId The ID of the tree structure whose Collection nodes shall be returned.
     * @param  string $name
     * @return Collection[]
     */
    public function fetchCollectionsByRoleName($roleId, $name)
    {
        if (! isset($roleId)) {
            throw new Exception("Parameter 'roleId' is required.");
        }

        if (! isset($name)) {
            throw new Exception("Parameter 'name' is required.");
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $query = $queryBuilder->select('c')
            ->from(Collection::class, 'c')
            ->where('c.roleId = :roleId')
            ->andWhere('c.name = :name')
            ->setParameter('roleId', $roleId)
            ->setParameter('name', $name)
            ->getQuery();

        // TODO double check that getResult() always returns an array
        return $query->getResult();
    }

    /**
     * Returns all child nodes of the Collection node with given ID.
     *
     * @param  int  $parentId The ID of the node whose children shall be returned.
     * @param  bool $sortResults (Optional) If true sort results by left ID.
     * @return Collection[]
     */
    public function fetchChildrenByParentId($parentId, $sortResults = false)
    {
        if (! isset($parentId)) {
            throw new Exception("Parameter 'parentId' is required.");
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $select = $queryBuilder->select('c')
            ->from(Collection::class, 'c')
            ->where('c.parentId = :parentId')
            ->setParameter('parentId', $parentId);

        if ($sortResults === true) {
            $select->orderBy('c.left', 'ASC');
        }

        $query = $select->getQuery();

        return $query->getResult();
    }
}
