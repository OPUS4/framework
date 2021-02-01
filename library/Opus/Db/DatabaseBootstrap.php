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
 * @package     Opus\Bootstrap
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Db;

use Opus\Bootstrap\Base;
use Opus\Version;

/**
 * Provide basic workflow of setting up an application.
 *
 * @category    Framework
 * @package     Opus\Bootstrap
 *
 * TODO eliminate inheritance from common -> framework -> application
 *      (database initialisation must be a "plugin/component")
 */
class DatabaseBootstrap extends Base
{

    /**
     * Setup a database connection and store the adapter in the registry.
     *
     * @return void
     *
     * TODO put into configuration file (custom DB adapter) and move code out of Bootstrap
     * TODO this make configuration modifiable (as a side effect)
     */
    protected function _initDatabase()
    {
        $this->bootstrap(['ZendCache', 'Logging', 'Configuration']);

        $logger = $this->getResource('Logging');
        $logger->debug('Initializing database.');

        // use custom DB adapter
        $config = Config::get();

        $config->merge(new \Zend_Config([
            'db' => [
                'adapter' => 'Mysqlutf8',
                'params' => [
                    'adapterNamespace' => 'OpusDb'
                ]
            ]
        ]));

        // Use \Zend_Db factory to create a database adapter
        // and make it the default for all tables.
        $db = null;

        try {
            $db = \Zend_Db::factory($config->db);
            \Zend_Db_Table::setDefaultAdapter($db);
        } catch (\Zend_Db_Adapter_Exception $e) {
            $logger->err($e);
            throw new \Exception('OPUS Bootstrap Error: Could not connect to database.');
        }

        // Check database version
        if (! isset($config->opus->disableDatabaseVersionCheck) ||
            ! filter_var($config->opus->disableDatabaseVersionCheck, FILTER_VALIDATE_BOOLEAN)
            ) {
            try {
                $query = $db->query('SELECT version FROM schema_version');

                $result = $query->fetch();

                if (is_array($result) && array_key_exists('version', $result)) {
                    $version = $result['version'];
                    $expectedVersion = Version::getSchemaVersion();

                    if ($version !== $expectedVersion) {
                        throw new \Exception(
                            "Database version '$version' does not match required '$expectedVersion'."
                        );
                    }
                } else {
                    throw new \Exception(
                        'No database schema version found. Database is probably too old. Please update.'
                    );
                }
            } catch (\Zend_Db_Statement_Exception $e) {
                throw new \Exception('Database schema is too old. Please update database.');
            }
        }

        return $db;
    }
}
