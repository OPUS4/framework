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
 * @copyright   Copyright (c) 2008-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Framework
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace Opus\Update\Plugin;

use Opus\Database;
use Zend_Db_Table_Abstract;

use function count;

/**
 * Class for updating the database schema for new version of OPUS.
 */
class DatabaseSchema extends AbstractUpdatePlugin
{
    private $targetVersion;

    /**
     * Performs update of database schema.
     */
    public function run()
    {
        $this->clearCache();
        Zend_Db_Table_Abstract::setDefaultMetadataCache(null);

        $database = new Database();

        $version = $database->getVersion();

        $this->log("Current version of database: $version");

        $version = $this->mapVersion($version);

        $scripts = $database->getUpdateScripts($version, $this->getTargetVersion());

        if (count($scripts) > 0) {
            foreach ($scripts as $scriptPath) {
                $this->log("Running $scriptPath ...");

                $result = $database->execScript($scriptPath);
            }
        } else {
            $this->log('No update needed');
        }
    }

    /**
     * Maps version value to schema version.
     *
     * @param string $version
     * @return int
     */
    public function mapVersion($version)
    {
        if ($version === null) {
            return 1;
        } elseif ($version === '4.5') {
            return 2;
        }

        return $version;
    }

    /**
     * @param string $targetVersion
     */
    public function setTargetVersion($targetVersion)
    {
        $this->targetVersion = $targetVersion;
    }

    /**
     * @return string
     */
    public function getTargetVersion()
    {
        return $this->targetVersion;
    }

    public function clearCache()
    {
        $cache = Zend_Db_Table_Abstract::getDefaultMetadataCache();
        if ($cache !== null) {
            $cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        }
    }
}
