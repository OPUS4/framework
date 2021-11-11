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
 *
 * @category    Framework
 * @package     Opus\Db2
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Db2;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Exception;
use Opus\Model\ModelException;
use Opus\Model2\AbstractModel;

use function get_class;

class OpusEntityManager
{
    /**
     * Persist all the models information to its database locations.
     *
     * @param AbstractModel $entity
     * @return int
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store($entity)
    {
        // TODO: pre store?

        $entityManager = Database::getEntityManager();

        $entityManager->beginTransaction();

        // store internal and external fields
        try {
            $entityManager->persist($entity);
            $entityManager->flush();
        } catch (Exception $e) {
            $entityManager->rollback();
            throw $e;
        }

        // commit transaction
        $entityManager->commit();

        // TODO: post store?

        return $entity->getId();
    }

    /**
     * Remove the model instance from the database.
     *
     * @param AbstractModel $entity
     * @throws ModelException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete($entity)
    {
        $modelId = $entity->getId();

        // if no primary key is set the model has
        // not been stored yet, so delete gets skipped
        // therefore postDelete of plugins does not get called either
        if (null === $modelId) {
            return;
        }

        // TODO: pre delete?

        $entityManager = Database::getEntityManager();

        // Start transaction
        $entityManager->beginTransaction();
        try {
            $entityManager->remove($entity);
            $entityManager->flush();
            $entityManager->commit();
        } catch (Exception $e) {
            $entityManager->rollback();
            $msg = $e->getMessage() . ' Model: ' . get_class($entity);
            throw new ModelException($msg);
        }

        // TODO: post delete?
    }

    /**
     * @param string $entityName
     * @return ObjectRepository|EntityRepository
     * @throws ORMException
     */
    public function getRepository($entityName)
    {
        return Database::getEntityManager()->getRepository($entityName);
    }
}
