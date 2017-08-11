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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2017, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Script for updating OPUS 4 database schema with optional name and version
 * parameters.
 *
 * The version parameter specifies the target version for update. If it is not
 * provided the script will update to the latest version of the schema.
 *
 * TODO name parameter not supported yet (still needed?)
 */

defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(dirname(__FILE__))));

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production'));

// Configure include path.
set_include_path(
    implode(
        PATH_SEPARATOR, array(
            '.',
            dirname(__FILE__),
            APPLICATION_PATH . '/library',
            APPLICATION_PATH . '/vendor',
            get_include_path(),
        )
    )
);

require_once 'autoload.php';

$application = new Zend_Application(
    APPLICATION_ENV,
    array(
        "config"=>array(
            APPLICATION_PATH . '/tests/config.ini',
            APPLICATION_PATH . '/tests/tests.ini'
        )
    )
);

Zend_Registry::set('opus.disableDatabaseVersionCheck', true);

// Bootstrapping application
$application->bootstrap('Backend');

$options = getopt('v:n:');

$targetVersion = null;

if (array_key_exists('v', $options))
{
    $targetVersion = $options['v'];
    if (!ctype_digit($targetVersion))
    {
        $targetVersion = null;
    }
}

$database = new Opus_Database();

echo $database->getName() . PHP_EOL;

$database->update($targetVersion);



