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

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\Tools\Setup;
use Opus\Config;
use Opus\Database as OpusDatabase;

/**
 * TODO Allgemeine Funktionen für Datenbankanbindung mit Doctrine. Das Design insgesamt ist aber noch unklar. Diese
 *      Klasse sollte vermutlich später mit Opus\Database verschmolzen werden.
 */
class Database
{
    private static $conn;
    private static $entityManager;

    public static function resetEntitiyManager()
    {
        self::$entityManager = null;
    }

    /**
     * @return array
     */
    public static function getConnectionParams()
    {
        $config = Config::get(); // TODO use function (no direkt class dependency)

        if (isset($config->db->params)) {
            $dbConfig = $config->db->params;
        }

        return $dbConfig->toArray();
    }

    /**
     * @return Connection
     * @throws Exception
     */
    public static function getConnection()
    {
        if (self::$conn === null) {
            $params = self::getConnectionParams();

            $db = new OpusDatabase();

            $dbName = $db->getName();

            $pdo = $db->getPdo($dbName);

            $params['pdo'] = $pdo;

            self::$conn = DriverManager::getConnection($params);

            self::$conn->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        }

        return self::$conn;
    }

    /**
     * @return EntityManager
     * @throws ORMException
     */
    public static function getEntityManager()
    {
        $isDevMode                 = true;
        $proxyDir                  = null;
        $cache                     = null;
        $useSimpleAnnotationReader = false;
        $config                    = Setup::createAnnotationMetadataConfiguration([__DIR__], $isDevMode, $proxyDir, $cache, $useSimpleAnnotationReader);

        $conn = self::getConnection();

        if (self::$entityManager === null) {
            self::$entityManager = EntityManager::create($conn, $config);
        }

        return self::$entityManager;
    }
}
