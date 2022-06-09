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
 * @copyright   Copyright (c) 2008-2016, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Script for creating OPUS 4 database with optional name and version
 * parameters.
 */

// Define path to application directory
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', getenv('APPLICATION_PATH') ? getenv('APPLICATION_PATH') : realpath(dirname(dirname(__FILE__))));

// Define application environment
defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', getenv('APPLICATION_ENV') ? getenv('APPLICATION_ENV') : 'production');

// Configure include path.
set_include_path(
    implode(
        PATH_SEPARATOR,
        [
            '.',
            dirname(__FILE__),
            APPLICATION_PATH . '/library',
            APPLICATION_PATH . '/src',
            APPLICATION_PATH . '/vendor',
            get_include_path(),
        ]
    )
);

require_once 'autoload.php';

// TODO OPUSVIER-4420 remove after switching to Laminas/ZF3
$dirPath = dirname(__FILE__, 2);
require_once $dirPath . '/library/OpusDb/Mysqlutf8.php';

$configFiles = array_filter([
    APPLICATION_PATH . '/test/config.ini',
    APPLICATION_PATH . '/tests/config.ini',
], 'file_exists');

// Environment initializiation
$application = new Zend_Application(
    APPLICATION_ENV,
    [
        "config" => $configFiles,
    ]
);

$options                                        = $application->getOptions();
$options['opus']['disableDatabaseVersionCheck'] = true;
$application->setOptions($options);

// Bootstrapping application
$application->bootstrap('Backend');

$options = getopt('v:n:');

$version = null;

if (array_key_exists('v', $options)) {
    $version = $options['v'];
    if (! ctype_digit($version)) {
        $version = null;
    }
}

/**
 * Prepare database.
 */

$database = new Opus\Database();

$dbName = $database->getName();

echo "Dropping database '$dbName' ... ";
$database->drop();
echo 'done' . PHP_EOL;

echo "Creating database '$dbName' ... ";
$database->create();
echo 'done' . PHP_EOL;

echo PHP_EOL . "Importing database schema ... " . PHP_EOL;
$database->importSchema($version);
