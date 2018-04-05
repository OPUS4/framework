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
 * @package     Opus_Model
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * Trait for adding plugin support to a class.
 */
trait Opus_Model_PluginsTrait
{

    /**
     * Array mapping plugin class names to model plugins.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @var Array
     */
    protected $_plugins = [];

    /**
     * Instanciate and install plugins for this model.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @return void
     */
    protected function _loadPlugins()
    {
        foreach ($this->_plugins as $pluginname => $plugin) {
            if (true === is_string($plugin)) {
                $pluginname = $plugin;
                $plugin = null;
            }

            if (null === $plugin) {
                $plugin = new $pluginname;
            }

            $this->registerPlugin($plugin);
        }
    }

    /**
     * Register a pre- or post processing plugin.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @param Opus_Model_Plugin_Interface $plugin Plugin to register for this very model.
     * @return void
     */
    public function registerPlugin(Opus_Model_Plugin_Interface $plugin)
    {
        $this->_plugins[get_class($plugin)] = $plugin;
    }

    /**
     * Unregister a pre- or post processing plugin.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @param string|object $plugin Instance or class name to unregister plugin.
     * @throw Opus_Model_Exception Thrown if specified plugin does not exist.
     * @return void
     */
    public function unregisterPlugin($plugin)
    {
        $key = '';
        if (true === is_string($plugin)) {
            $key = $plugin;
        }
        if (true === is_object($plugin)) {
            $key = get_class($plugin);
        }
        if (false === isset($this->_plugins[$key])) {
            // don't throw exception, just write a warning
            $this->getLogger()->warn('Cannot unregister specified plugin: ' . $key);
        } else {
            unset($this->_plugins[$key]);
        }
    }

    /**
     * Return true if the given plugin was already registered; otherwise false.
     * @param string $plugin class name of the plugin
     */
    public function hasPlugin($plugin)
    {
        return array_key_exists($plugin, $this->_plugins);
    }

    /**
     * Calls a specified plugin method in all available plugins.
     *
     * Copy-Paste from Qucosa-Code base.
     *
     * @param string $methodname Name of plugin method to call
     * @param mixed  $parameter  Value that gets passed instead of the model instance.
     */
    protected function _callPluginMethod($methodname, $parameter = null)
    {
        try {
            if (null === $parameter) {
                $param = $this;
            } else {
                $param = $parameter;
            }

            foreach ($this->_plugins as $name=>$plugin) {
                $plugin->$methodname($param);
            }
        } catch (Exception $ex) {
            throw new Opus_Model_Exception(
                'Plugin ' . $name . ' failed in ' . $methodname
                . ' with ' . $ex->getMessage()
            );
        }
    }
}
