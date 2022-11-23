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
 * @copyright   Copyright (c) 2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Translate;

use Opus\Common\Translate\TranslateException;
use Opus\Translate\Dao;
use OpusTest\TestAsset\TestCase;

/**
 * TODO test protection against SQL-injection
 * TODO test updating existing entries
 */
class DaoTest extends TestCase
{
    /** @var Dao */
    private $translations;

    public function setUp(): void
    {
        parent::setUp();

        $this->translations = new Dao();
    }

    public function tearDown(): void
    {
        $this->translations->removeAll();

        parent::tearDown();
    }

    public function testAddTranslations()
    {
        $dao = $this->translations;

        $data = [
            'testkey1' => [
                'de' => 'Testschlüssel 1',
                'en' => 'test key one',
            ],
            'testkey2' => [
                'de' => 'Testschlüssel 2',
                'en' => 'test key two',
            ],
        ];

        $dao->addTranslations($data);

        $all = $dao->getTranslations();

        $this->assertEquals($data, $all);
    }

    public function testAddTranslationsForModule()
    {
        $dao = $this->translations;

        $data = [
            'testkey1' => [
                'de' => 'Testschlüssel 1',
                'en' => 'test key one',
            ],
            'testkey2' => [
                'de' => 'Testschlüssel 2',
                'en' => 'test key two',
            ],
        ];

        $dao->addTranslations($data, 'admin');

        $all = $dao->getTranslations('admin');

        $this->assertEquals($data, $all);
    }

    public function testSetTranslation()
    {
        $dao = $this->translations;

        $dao->setTranslation('admin_index_title', [
            'de' => 'Verwaltung',
            'en' => 'Administration',
        ]);
        $dao->setTranslation('testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ]);

        $translations = $dao->getAll();

        $this->assertCount(2, $translations);
        $this->assertArrayHasKey('admin_index_title', $translations);
        $this->assertArrayHasKey('testkey1', $translations);
        $this->assertEquals('Verwaltung', $translations['admin_index_title']['de']);
        $this->assertEquals('Administration', $translations['admin_index_title']['en']);
    }

    public function testSetTranslationForUpdate()
    {
        $dao = $this->translations;

        $dao->setTranslation('admin_index_title', [
            'de' => 'Verwaltung',
            'en' => 'Administration',
        ]);
        $dao->setTranslation('testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ]);

        $translations = $dao->getAll();

        $this->assertCount(2, $translations);
        $this->assertArrayHasKey('admin_index_title', $translations);
        $this->assertArrayHasKey('testkey1', $translations);
        $this->assertEquals('Verwaltung', $translations['admin_index_title']['de']);
        $this->assertEquals('Administration', $translations['admin_index_title']['en']);

        $dao->setTranslation('admin_index_title', [
            'de' => 'Editiert',
            'en' => 'Edited',
        ]);

        $translations = $dao->getAll();

        $this->assertCount(2, $translations);
        $this->assertArrayHasKey('admin_index_title', $translations);
        $this->assertArrayHasKey('testkey1', $translations);
        $this->assertEquals('Editiert', $translations['admin_index_title']['de']);
        $this->assertEquals('Edited', $translations['admin_index_title']['en']);
    }

    public function testSetTranslationWithNullRemovesEntry()
    {
        $dao = $this->translations;

        $data = [
            'en' => 'test key one',
            'de' => 'Testschlüssel 1',
        ];

        $dao->setTranslation('testkey1', $data);

        $this->assertEquals($data, $dao->getTranslation('testkey1'));

        $dao->setTranslation('testkey1', null);

        $this->assertNull($dao->getTranslation('testkey1'));
    }

    public function testSetTranslationWithModule()
    {
        $dao = $this->translations;

        $data = [
            'en' => 'test key one',
            'de' => 'Testschlüssel 1',
        ];

        $dao->setTranslation('testkey1', $data, 'module');

        $this->assertEquals($data, $dao->getTranslation('testkey1'));
        $this->assertEquals($data, $dao->getTranslations('module')['testkey1']);
    }

    public function testRemove()
    {
        $dao = $this->translations;

        $translations = [
            'de' => 'Verwaltung',
            'en' => 'Administration',
        ];

        $dao->setTranslation('admin', $translations);

        $data = $dao->getTranslation('admin');

        $this->assertEquals($translations, $data);

        $dao->remove('admin');

        $this->assertNull($dao->getTranslation('admin'));
    }

    public function testRemoveAll()
    {
        $dao = $this->translations;

        $translations = [
            'de' => 'Verwaltung',
            'en' => 'Administration',
        ];

        $dao->setTranslation('admin', $translations);

        $this->assertEquals($translations, $dao->getTranslation('admin'));

        $dao->removeAll();

        $this->assertNull($dao->getTranslation('admin'));
    }

    public function testRemoveModule()
    {
        $dao = $this->translations;

        $translations1 = [
            'de' => 'Verwaltung',
            'en' => 'Administration',
        ];

        $dao->setTranslation('admin', $translations1);

        $this->assertEquals($translations1, $dao->getTranslation('admin'));

        $translations2 = [
            'de' => 'Testschüssel',
            'en' => 'test key',
        ];

        $dao->setTranslation('testkey', $translations2, 'test');

        $this->assertEquals($translations2, $dao->getTranslation('testkey'));
    }

    public function testGetTranslation()
    {
        $dao = $this->translations;

        $dao->setTranslation('testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ]);

        $translation = $dao->getTranslation('testkey1');

        $this->assertEquals([
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ], $translation);
    }

    public function testGetTranslationForUnknownKey()
    {
        $dao = $this->translations;

        $translation = $dao->getTranslation('unknownkey');

        $this->assertNull($translation);
    }

    public function testGetTranslationForLocale()
    {
        $dao = $this->translations;

        $dao->setTranslation('testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ]);

        $this->assertEquals('Testschlüssel 1', $dao->getTranslation('testkey1', 'de'));
        $this->assertEquals('test key one', $dao->getTranslation('testkey1', 'en'));
    }

    public function testGetTranslationForUnknownLocale()
    {
        $dao = $this->translations;

        $dao->setTranslation('testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ]);

        $this->assertNull($dao->getTranslation('testkey1', 'fr'));
    }

    /**
     * TODO is this functionality needed?
     */
    public function testGetTranslationForKeyExistingInDefaultAndModule()
    {
        $dao = $this->translations;

        $dao->setTranslation('testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ]);

        $dao->setTranslation('testkey1', [
            'de' => 'Moduletestschlüssel 1',
            'en' => 'module test key one',
        ], 'module');

        $translation = $dao->getTranslation('testkey1');

        $this->markTestIncomplete('not sure what result should look like, not sure this is needed');
    }

    public function testGetTranslations()
    {
        $dao = $this->translations;

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ];

        $dao->setTranslation('testkey1', $data);

        $dataModule = [
            'de' => 'Modul',
            'en' => 'Module',
        ];

        $dao->setTranslation('modulekey', $dataModule);

        $all = $dao->getTranslations();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('testkey1', $all);
        $this->assertArrayHasKey('modulekey', $all);
        $this->assertEquals($all['testkey1'], $data);
        $this->assertEquals($all['modulekey'], $dataModule);
    }

    public function testGetTranslationsForModule()
    {
        $dao = $this->translations;

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ];

        $dao->setTranslation('testkey1', $data);

        $dataAdmin = [
            'de' => 'Verwaltung',
            'en' => 'Administration',
        ];

        $dao->setTranslation('admin', $dataAdmin, 'admin');

        $translations = $dao->getTranslations('admin');

        $this->assertCount(1, $translations);
        $this->arrayHasKey('admin', $translations);
        $this->assertEquals($dataAdmin, $translations['admin']);
    }

    public function testGetTranslationsByLocale()
    {
        $dao = $this->translations;

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ];

        $dao->setTranslation('testkey1', $data);

        $dataModule = [
            'de' => 'Modul',
            'en' => 'Module',
        ];

        $dao->setTranslation('modulekey', $dataModule, 'admin');

        $all = $dao->getTranslationsByLocale();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('en', $all);
        $this->assertArrayHasKey('de', $all);

        $this->assertEquals([
            'en' => [
                'modulekey' => 'Module',
                'testkey1'  => 'test key one',
            ],
            'de' => [
                'modulekey' => 'Modul',
                'testkey1'  => 'Testschlüssel 1',
            ],
        ], $all);
    }

    public function testRenameKey()
    {
        $dao = $this->translations;

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ];

        $dao->setTranslation('testkey1', $data);

        $translations = $dao->getTranslation('testkey1');

        $this->assertEquals($data, $translations);

        $dao->renameKey('testkey1', 'testkeyone');

        $translations = $dao->getTranslation('testkeyone');

        $this->assertEquals($data, $translations);

        $translations = $dao->getTranslation('testkey1');

        $this->assertNull($translations);
    }

    public function testRenameKeyNewKeyAlreadyExists()
    {
        $dao = $this->translations;

        $data = [
            'testkey1' => [
                'en' => 'test key one',
                'de' => 'Testschlüssel 1',
            ],
            'testkey2' => [
                'en' => 'test key two',
                'de' => 'Testschlüssel 2',
            ],
        ];

        $dao->addTranslations($data);

        $this->assertEquals($data['testkey1'], $dao->getTranslation('testkey1'));
        $this->assertEquals($data['testkey2'], $dao->getTranslation('testkey2'));

        $this->expectException(TranslateException::class, 'Duplicate entry');

        $dao->renameKey('testkey1', 'testkey2');
    }

    public function testRenameKeyInModuleOnly()
    {
        $dao = $this->translations;

        $data = [
            'en' => 'test key one',
            'de' => 'Testschlüssel 1',
        ];

        $dao->setTranslation('testkey1', $data);
        $dao->setTranslation('testkey1', $data, 'module');

        $this->assertNotNull($dao->getTranslation('testkey1'));
        $this->assertNotNull($dao->getTranslations('module'));
        $this->assertArrayHasKey('testkey1', $dao->getTranslations('module'));

        $dao->renameKey('testkey1', 'testkeyone', 'module');

        $this->assertNotNull($dao->getTranslation('testkey1'));

        $translations = $dao->getTranslations('module');

        $this->assertCount(1, $translations);
        $this->assertArrayHasKey('testkeyone', $translations);

        $this->assertEquals('test key one', $dao->getTranslation('testkey1', 'en'));
        $this->assertEquals('test key one', $dao->getTranslation('testkeyone', 'en'));
    }

    public function testRenameKeyDefaultOnly()
    {
        $dao = $this->translations;

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one',
        ];

        $data2 = [
            'de' => 'Modulschlüssel',
            'en' => 'module key',
        ];

        $dao->setTranslation('testkey1', $data);
        $dao->setTranslation('testkey1', $data2, 'module');

        $translations = $dao->getTranslation('testkey1');
        $this->assertEquals($data2, $translations);

        $dao->renameKey('testkey1', 'testkeyone');

        $translations = $dao->getTranslation('testkeyone');
        $this->assertEquals($data, $translations);

        $translations = $dao->getTranslation('testkey1');
        $this->assertEquals($data2, $translations);
    }

    public function testSetSpecialTranslation()
    {
        $dao = $this->translations;

        $data = [
            'de' => 'Jump to',
            'en' => 'Gehe zu',
        ];

        $dao->setTranslation('admin-actionbox-goto-section', $data);

        $translations = $dao->getTranslation('admin-actionbox-goto-section');

        $this->assertEquals($data, $translations);
    }

    public function testGetTranslationsWithModules()
    {
        $dao = $this->translations;

        $data = [
            'de' => 'Deutsch',
            'en' => 'Englisch',
        ];

        $dao->setTranslation('testKey', $data, 'setup');

        $translations = $dao->getTranslationsWithModules();

        $this->assertNotNull($translations);
        $this->assertArrayHasKey('testKey', $translations);

        $testKey = $translations['testKey'];

        $this->assertArrayHasKey('module', $testKey);
        $this->assertEquals('setup', $testKey['module']);
        $this->assertArrayHasKey('values', $testKey);
        $this->assertEquals($data, $testKey['values']);
    }

    public function testGetTranslationsWithModulesFilteredByModules()
    {
        $dao = $this->translations;

        $keyData        = ['en' => 'keyEN', 'de' => 'keyDE'];
        $defaultKeyData = ['en' => 'defaultKeyEN', 'de' => 'defaultKeyDE'];
        $publishKeyData = ['en' => 'publishKeyEN', 'de' => 'publishKeyDE'];
        $adminKeyData   = ['en' => 'adminKeyEN', 'de' => 'adminKeyDE'];

        $dao->setTranslation('key', $keyData);
        $dao->setTranslation('default_key', $defaultKeyData, 'default');
        $dao->setTranslation('publish_key', $publishKeyData, 'publish');
        $dao->setTranslation('admin_key', $adminKeyData, 'admin');

        // test no module specified
        $translations = $dao->getTranslationsWithModules();

        $this->assertCount(4, $translations);

        // test single module
        $translations = $dao->getTranslationsWithModules('default');

        $this->assertCount(2, $translations);
        $this->assertArrayHasKey('key', $translations);
        $this->assertArrayHasKey('default_key', $translations);

        // test multiple modules
        $translations = $dao->getTranslationsWithModules(['publish', 'admin']);

        $this->assertCount(2, $translations);
        $this->assertArrayHasKey('publish_key', $translations);
        $this->assertArrayHasKey('admin_key', $translations);

        // test unknown module
        $translations = $dao->getTranslationsWithModules('unknown857');

        $this->assertCount(0, $translations);
    }

    public function testGetModules()
    {
        $dao = $this->translations;

        $dao->setTranslation('testKey1', [
            'en' => 'test key 1',
            'de' => 'Testschlüssel 1',
        ]);

        $dao->setTranslation('testKey2', [
            'en' => 'test key 2',
            'de' => 'Testschlüssel 2',
        ], 'home');

        $dao->setTranslation('testKey3', [
            'en' => 'test key 3',
            'de' => 'Testschlüssel 3',
        ], 'admin');

        $dao->setTranslation('testKey3', [
            'en' => 'test key 4',
            'de' => 'Testschlüssel 4',
        ], 'admin');

        $modules = $dao->getModules();

        $this->assertCount(3, $modules);
        $this->assertEquals([
            'default',
            'home',
            'admin',
        ], $modules);
    }
}
