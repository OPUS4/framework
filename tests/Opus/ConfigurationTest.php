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
 * @package     Opus
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */


/**
 * Test cases for class Opus_Configuration.
 *
 * @package Opus
 * @category Tests
 *
 * @group ConfigurationTest
 *
 */
class Opus_ConfigurationTest extends PHPUnit_Framework_TestCase {

    /**
     * Remove stored configurations.
     *
     * @return void
     */
    public function tearDown() {
        TestHelper::clearTable('configurations');
    }

    /**
     * Test creation of component.
     *
     * @return void
     */
    public function testCreate() {
        $conf = new Opus_Configuration;
    }
    
    /**
     * Test generate Zend_Config object from configuration model.
     *
     * @return void
     */
    public function testRetrieveZendConfigurationType() {
        $conf = new Opus_Configuration;
        $zend = $conf->getZendConfig();
        $this->assertTrue($zend instanceof Zend_Config, 'Returned object is of wrong type.');
    }
    
    /**
     * Test if configuration parameters are correctly mapped to
     * Zend_Config options prefixed with the given name of the
     * configuration object.
     *
     * @return void
     */
    public function testSetConfigParameterAndGenerateZendConfig() {
        $conf = new Opus_Configuration;
        $conf->setName('MyConf')
            ->setTheme('MyTheme')
            ->setSiteName('MySiteName');
        $zcfg = $conf->getZendConfig();

        $mycfg = $zcfg->MyConf;
        $this->assertNotNull($mycfg, 'Configuration not mapped to Zend_Config object.');
           
        $this->assertEquals('MyTheme', $zcfg->MyConf->Theme, 'Zend_Config value does not match configuration object value.');
        $this->assertEquals('MySiteName', $zcfg->MyConf->SiteName, 'Zend_Config value does not match configuration object value.');
    }
    
    /**
     * Test creation, storage and reloading of a configuration.
     *
     * @return void
     */
    public function testPersistConfiguration() {
        $conf = new Opus_Configuration;
        $conf->setName('MyConf')
            ->setTheme('MyTheme')
            ->setSiteName('MySiteName')
            ->setLoadOnStartup(0);
        $id = $conf->store();
        
        $conf2 = new Opus_Configuration($id);
        
        $this->assertEquals($conf->toArray(), $conf2->toArray(), 'Array representation of configurations does not match.');
    }      

}
