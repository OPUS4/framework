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
 * @category    Framework
 * @package     Opus
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2016, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Class for updating the database schema for new version of OPUS.
 */
class Opus_Update_Plugin_DatabaseSchema extends Opus_Update_Plugin_Abstract {

    private $_targetVersion = null;

    /**
     * Performs update of database schema.
     */
    public function run() {
        $database = new Opus_Database();

        $version = $database->getVersion();

        $this->log("Current version of database: $version");

        $version = $this->mapVersion($version);

        $scripts = $database->getUpdateScripts($version, $this->getTargetVersion());

        if (count($scripts) > 0)
        {
            foreach ($scripts as $scriptPath) {
                $this->log("Running $scriptPath ...");

                $result = $database->execScript($scriptPath);
            }
        }
        else
        {
            $this->log('No update needed');
        }
    }

    /**
     * Maps version value to schema version.
     *
     * @param $version
     * @return int
     */
    public function mapVersion($version)
    {
        if (is_null($version))
        {
            return 1;
        }
        else if ($version === '4.5')
        {
            return 2;
        }

        return $version;
    }

    public function setTargetVersion($targetVersion)
    {
        $this->_targetVersion = $targetVersion;
    }

    public function getTargetVersion()
    {
        return $this->_targetVersion;
    }

}
