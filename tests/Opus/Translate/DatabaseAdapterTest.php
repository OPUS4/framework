<?php
/*
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
 * @package     Opus_Translate
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */
class Opus_Translate_DatabaseAdapterTest extends TestCase
{

    private $cache;

    public function setUp()
    {
        parent::setUp();
        $this->cache = Zend_Translate::getCache();
    }

    public function tearDown()
    {
        Zend_Translate::setCache($this->cache);
        parent::tearDown();
    }

    public function testUsingAdapter()
    {
        Zend_Translate::clearCache();

        $database = new Opus_Translate_Dao();

        $database->setTranslation('admin', [
            'en' => 'Administration',
            'de' => 'Verwaltung'
        ]);

        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'default',
            'locale' => 'en'
        ]);

        $this->assertTrue($translate->isTranslated('admin'));
        $this->assertFalse($translate->isTranslated('unknownkey'));
        $this->assertEquals('Administration', $translate->translate('admin'));
        $this->assertEquals('Verwaltung', $translate->translate('admin', 'de'));
    }

    /**
     * Cache is setup during the bootstrapping of the tests.
     * @throws Zend_Translate_Exception
     *
     * TODO setup cache explicitly in this test, do not rely on bootstrap or check at least
     */
    public function testUpdatingTranslation()
    {
        $database = new Opus_Translate_Dao();

        $database->setTranslation('admin', [
            'en' => 'Administration',
            'de' => 'Verwaltung'
        ]);

        Zend_Translate::clearCache(); // clear cache between test runs

        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'default',
            'locale' => 'en'
        ]);

        $this->assertTrue($translate->isTranslated('admin'));
        $this->assertFalse($translate->isTranslated('unknownkey'));
        $this->assertEquals('Administration', $translate->translate('admin'));
        $this->assertEquals('Verwaltung', $translate->translate('admin', 'de'));

        // update database entry
        $database->setTranslation('admin', [
            'en' => 'Edited',
            'de' => 'Editiert'
        ]);

        // create new translation object will not update cache
        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'default',
            'locale' => 'en'
        ]);

        // translations are cached in memory independent of Zend_Cache
        $this->assertEquals('Administration', $translate->translate('admin'));
        $this->assertEquals('Verwaltung', $translate->translate('admin', 'de'));

        // it is necessary to clear the cache before updates
        Zend_Translate::clearCache();

        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'default',
            'locale' => 'en'
        ]);

        $this->assertEquals('Edited', $translate->translate('admin'));
        $this->assertEquals('Editiert', $translate->translate('admin', 'de'));
    }

    // check behaviour with cache
    public function testUsingAdapterWithoutCache()
    {
        Zend_Translate::clearCache();

        $database = new Opus_Translate_Dao();

        $database->setTranslation('admin', [
            'en' => 'Administration',
            'de' => 'Verwaltung'
        ]);

        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'default',
            'locale' => 'en'
        ]);

        $translate->removeCache();

        $this->assertFalse($translate->hasCache());

        $this->assertTrue($translate->isTranslated('admin'));
        $this->assertFalse($translate->isTranslated('unknownkey'));
        $this->assertEquals('Administration', $translate->translate('admin'));
        $this->assertEquals('Verwaltung', $translate->translate('admin', 'de'));

        // update database entry
        $database->setTranslation('admin', [
            'en' => 'Edited',
            'de' => 'Editiert'
        ]);

        // check translations in old Zend_Translate object that has already loaded from database
        $this->assertEquals('Administration', $translate->translate('admin'));
        $this->assertEquals('Verwaltung', $translate->translate('admin', 'de'));

        // create new Zend_Translate object so translation will be read again
        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'default',
            'locale' => 'en'
        ]);

        $this->assertEquals('Edited', $translate->translate('admin'));
        $this->assertEquals('Editiert', $translate->translate('admin', 'de'));
    }

    public function testLoadTranslationsForModule()
    {
        $this->markTestIncomplete('not implemented yet');
    }

    public function testClearCacheForJustOneModule()
    {
        Zend_Translate::clearCache();

        $database = new Opus_Translate_Dao();

        $database->setTranslation('admin_title', [
            'en' => 'Administration',
            'de' => 'Verwaltung'
        ], 'admin');

        $database->setTranslation('home_title', [
            'en' => 'Mainpage',
            'de' => 'Hauptseite'
        ], 'home');

        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'admin',
            'locale' => 'en'
        ]);

        // $translate->addTranslation(['content' => 'home']);
        $translate->addTranslation('home');

        $this->assertTrue($translate->isTranslated('admin_title'));
        $this->assertEquals('Administration', $translate->translate('admin_title'));
        $this->assertEquals('Verwaltung', $translate->translate('admin_title', 'de'));

        $this->assertTrue($translate->isTranslated('home_title'));
        $this->assertEquals('Mainpage', $translate->translate('home_title'));
        $this->assertEquals('Hauptseite', $translate->translate('home_title', 'de'));

        $database->setTranslation('admin_title', [
            'en' => 'Settings',
            'de' => 'Einstellungen'
        ], 'admin');

        $database->setTranslation('home_title', [
            'en' => 'Home',
            'de' => 'Start'
        ], 'home');

        $translate->clearCache();

        Zend_Translate::clearCache();

        $translate = new Zend_Translate([
            'adapter' => 'Opus_Translate_DatabaseAdapter',
            'content' => 'admin',
            'locale' => 'en',
        ]);

        $translate->clearCache();

        // $translate->addTranslation(['content' => 'home']);
        $translate->addTranslation('home');

        $this->assertTrue($translate->isTranslated('admin_title'));
        $this->assertEquals('Administration', $translate->translate('admin_title'));
        $this->assertEquals('Verwaltung', $translate->translate('admin_title', 'de'));

        $this->assertTrue($translate->isTranslated('home_title'));
        $this->assertEquals('Home', $translate->translate('home_title'));
        $this->assertEquals('Start', $translate->translate('home_title', 'de'));
    }
}