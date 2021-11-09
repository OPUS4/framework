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

namespace Opus\Model2;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Opus\Db2\OpusEntityManager;
use Opus\Model\ModelException;

abstract class AbstractModel
{
    private static $entityManager;
    private static $repository;

    /**
     * @return OpusEntityManager
     */
    protected static function getEntityManager()
    {
        if (self::$entityManager === null) {
            self::$entityManager = new OpusEntityManager();
        }
        return self::$entityManager;
    }

    /**
     * @return EntityRepository|ObjectRepository
     * @throws ORMException
     */
    protected static function getRepository()
    {
        if (self::$repository === null) {
            self::$repository = self::getEntityManager()->getRepository(static::class);
        }
        return self::$repository;
    }

    /** @return int */
    abstract public function getId();

    /**
     * Persist all the models information to its database locations.
     *
     * @return mixed
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function store()
    {
        return $this->getEntityManager()->store($this);
    }

    /**
     * Remove the model instance from the database.
     *
     * @throws ModelException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function delete()
    {
        $this->getEntityManager()->delete($this);
    }
}
