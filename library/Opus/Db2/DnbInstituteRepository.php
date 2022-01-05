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

/**
 * Database specific class for DnbInstitute functions.
 *
 * This class keeps the database (Doctrine) specific code out of the model class.
 */
class DnbInstituteRepository extends EntityRepository
{
    /**
     * Returns a list of organisational units that act as (thesis) grantors.
     *
     * @return array A list of Opus\Model2\DnbInstitutes that act as grantors.
     */
    public function getGrantors()
    {
        return $this->findBy(['isGrantor' => true]);
    }

    /**
     * Returns a list of organisational units that act as (thesis) publishers.
     *
     * @return array A list of Opus\Model2\DnbInstitutes that act as publishers.
     */
    public function getPublishers()
    {
        return $this->findBy(['isPublisher' => true]);
    }

    /**
     * Checks if DNB institute is used by any document.
     *
     * @param int $id
     * @return bool
     */
    public function isUsed($id)
    {
        /*
        TODO: ORM queryBuilder can not be used here because there is currently no entity class
         for the table 'link_documents_dnb_institutes'.

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $select       = $queryBuilder->select('count(d.id)')
            ->from(DnbInstitute::class, 'd')
            ->where('d.dnbInstituteId = ?1')
            ->setParamater(1, $id);

        $select->setMaxResults(1);

        $query   = $select->getQuery();
        $result = $query->getSingleScalarResult();

        return $result > 0;
        */

        $conn         = $this->getEntityManager()->getConnection();
        $queryBuilder = $conn->createQueryBuilder();
        $select       = $queryBuilder
            ->select('count(dnb_institute_id)')
            ->from('link_documents_dnb_institutes')
            ->where("dnb_institute_id = :id")
            ->setParameter('id', $id);

        $result = $select->execute()->fetchOne();

        return $result > 0;
    }
}
