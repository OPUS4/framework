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

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use Exception;
use Opus\Model2\Job;

use function count;

class JobRepository extends EntityRepository
{
    /**
     * Retrieve all Job instances from the database.
     *
     * @return Job[]
     * @throws ORMException
     */
    public function getAll()
    {
        return $this->findAll();
    }

    /**
     * Retrieves existing Job instances by their ID. Returns null if the given
     * set of IDs is empty or if nothing was found.
     *
     * @param  null|int[] $ids (Optional) Set of IDs specifying the Job instances to fetch.
     * @return Job[]|null
     */
    public function getJobs($ids = null)
    {
        if (empty($ids)) {
            return null;
        }

        return $this->findBy(['id' => $ids]);
    }

    /**
     * Removes all Job instances from the database.
     */
    public function deleteAll()
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $query = $queryBuilder->delete()
            ->from(Job::class, 'j')
            ->getQuery();

        $query->execute();
    }

    /**
     * Returns all Job instances with the given SHA1 Hash. Always returns an array, even if
     * the result set has zero or one element.
     *
     * @param  string $sha1Id The SHA1 Hash for which matching Job instances shall be returned.
     * @return Job[]
     */
    public function getJobsWithSha1Id($sha1Id)
    {
        if (! isset($sha1Id)) {
            throw new Exception("Parameter 'sha1Id' is required.");
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $query = $queryBuilder->select('j')
            ->from(Job::class, 'j')
            ->where('j.sha1Id = :sha1Id')
            ->setParameter('sha1Id', $sha1Id)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Retrieve number of Job entries in the database.
     *
     * @param null|string $state (Optional) Only retrieve jobs in given state (@see Job class for state definitions).
     * @return int
     */
    public function getJobsCount($state = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $select = $queryBuilder->select('j')
            ->from(Job::class, 'j');

        if ($state !== null) {
            if ($state === Job::STATE_UNDEFINED) {
                $select->where('j.state is NULL');
            } else {
                $select->where('j.state = :state')
                    ->setParameter('state', $state);
            }
        }

        $query = $select->getQuery();

        return count($query->getResult());
    }

    /**
     * Retrieve number of Job entries for a given label in the database.
     *
     * @param string      $label Only consider jobs with the given label.
     * @param null|string $state (Optional) Only retrieve jobs in given state (@see Job class for state definitions).
     * @return int
     */
    public function getJobsCountForLabel($label, $state = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $select = $queryBuilder->select('j')
            ->from(Job::class, 'j');

        $select->where('j.label = :label')
            ->setParameter('label', $label);

        if ($state !== null) {
            if ($state === Job::STATE_UNDEFINED) {
                $select->andWhere('j.state is NULL');
            } else {
                $select->andWhere('j.state = :state')
                    ->setParameter('state', $state);
            }
        }

        $query = $select->getQuery();

        return count($query->getResult());
    }

    /**
     * Retrieve number of Job entries in the database grouped by label.
     *
     * @param null|string $state (Optional) Only retrieve jobs in given state (@see Job class for state definitions).
     * @return array Key / Value pairs of label / count for database entries.
     */
    public function getJobsCountPerLabel($state = null)
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $select = $queryBuilder->select(['j.label', 'COUNT(j.id) AS count'])
            ->from(Job::class, 'j');

        if ($state !== null) {
            if ($state === Job::STATE_UNDEFINED) {
                $select->andWhere('j.state is NULL');
            } else {
                $select->andWhere('j.state = :state')
                    ->setParameter('state', $state);
            }
        }

        $select->groupBy('j.label');

        $query   = $select->getQuery();
        $results = $query->getArrayResult();

        $countsPerLabel = [];
        foreach ($results as $result) {
            $countsPerLabel[$result['label']] = $result['count'];
        }

        return $countsPerLabel;
    }

    /**
     * Retrieve all Job instances having the given label(s). Returns null if the given set of labels is empty.
     *
     * @param string[]    $labels Set of labels to get jobs for.
     * @param null|int    $limit (Optional) Number of jobs to retrieve.
     * @param null|string $state (Optional) Only retrieve jobs in given state (@see Job class for state definitions).
     * @return Job[]|null
     */
    public function getJobsWithLabels($labels, $limit = null, $state = null)
    {
        if (empty($labels)) {
            return null;
        }

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();

        $select = $queryBuilder->select('j')
            ->from(Job::class, 'j');

        $select->where('j.label IN (:labels)')
            ->setParameter('labels', $labels);

        if ($state !== null) {
            if ($state === Job::STATE_UNDEFINED) {
                $select->andWhere('j.state is NULL');
            } else {
                $select->andWhere('j.state = :state')
                    ->setParameter('state', $state);
            }
        }

        $select->orderBy('j.id');

        if ($limit !== null) {
            $select->setMaxResults($limit);
        }

        $query = $select->getQuery();

        return $query->getResult();
    }
}
