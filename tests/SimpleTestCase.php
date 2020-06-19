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
 * @category    Tests
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Superclass for all tests.  Providing maintainance tasks.
 *
 * @category Tests
 */
class SimpleTestCase extends PHPUnit_Framework_TestCase
{

    private $config_backup;

    const CONFIG_VALUE_FALSE = ''; // Zend_Config übersetzt false in den Wert ''

    const CONFIG_VALUE_TRUE = '1'; // Zend_Config übersetzt true in den Wert '1'

    /**
     * Overwrites selected properties of current configuration.
     *
     * @note A test doesn't need to backup and recover replaced configuration as
     *       this is done in setup and tear-down phases.
     *
     * @param array $overlay properties to overwrite existing values in configuration
     * @param callable $callback callback to invoke with adjusted configuration before enabling e.g. to delete some options
     * @return Zend_Config reference on previously set configuration
     */
    protected function adjustConfiguration($overlay, $callback = null)
    {
        $previous = Zend_Registry::get('Zend_Config');
        $updated  = new Zend_Config([], true);

        $updated
            ->merge($previous)
            ->merge(new Zend_Config($overlay));

        if (is_callable($callback)) {
            $updated = call_user_func($callback, $updated);
        }

        $updated->setReadOnly();

        Zend_Registry::set('Zend_Config', $updated);

        Opus_Search_Config::dropCached();

        return $previous;
    }

    /**
     * Drops configuration options available in deprecated format supported as
     * part of downward compatibility but breaking some tests regarding new
     * setup due to using that deprecated configuration in preference.
     *
     */
    protected function dropDeprecatedConfiguration()
    {
        $config = Opus_Config::get()->searchengine;

        unset(
            $config->index->host,
            $config->index->port,
            $config->index->app,
            $config->extract->host,
            $config->extract->port,
            $config->extract->app
        );

        Opus_Search_Config::dropCached();
    }

    /**
     * Standard setUp method for clearing database.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $config = Zend_Registry::get('Zend_Config');
        if (! is_null($config)) {
            $this->config_backup = clone $config;
        }
    }

    protected function tearDown()
    {
        if (! is_null($this->config_backup)) {
            Zend_Registry::set('Zend_Config', $this->config_backup);
        }

        parent::tearDown();
    }
}
