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

/**
 * Class Opus_Translate_DaoTest
 *
 * TODO test protection against SQL-injection
 * TODO test updating existing entries
 */
class Opus_Translate_DaoTest extends TestCase
{

    public function testAddTranslations()
    {
        $dao = new Opus_Translate_Dao();

        $data = [
            'testkey1' => [
                'de' => 'Testschlüssel 1',
                'en' => 'test key one'
            ],
            'testkey2' => [
                'de' => 'Testschlüssel 2',
                'en' => 'test key two'
            ]
        ];

        $dao->addTranslations($data);

        $all = $dao->getTranslations();

        $this->assertEquals($data, $all);
    }

    public function testAddTranslationsForModule()
    {
        $dao = new Opus_Translate_Dao();

        $data =[
            'testkey1' => [
                'de' => 'Testschlüssel 1',
                'en' => 'test key one'
            ],
            'testkey2' => [
                'de' => 'Testschlüssel 2',
                'en' => 'test key two'
            ]
        ];

        $dao->addTranslations($data, 'admin');

        $all = $dao->getTranslations('admin');

        $this->assertEquals($data, $all);
    }

    public function testSetTranslation()
    {
        $dao = new Opus_Translate_Dao();

        $dao->setTranslation('admin_index_title', [
            'de' => 'Verwaltung',
            'en' => 'Administration'
        ]);
        $dao->setTranslation( 'testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one'
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
        $dao = new Opus_Translate_Dao();

        $dao->setTranslation('admin_index_title', [
            'de' => 'Verwaltung',
            'en' => 'Administration'
        ]);
        $dao->setTranslation( 'testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one'
        ]);

        $translations = $dao->getAll();

        $this->assertCount(2, $translations);
        $this->assertArrayHasKey('admin_index_title', $translations);
        $this->assertArrayHasKey('testkey1', $translations);
        $this->assertEquals('Verwaltung', $translations['admin_index_title']['de']);
        $this->assertEquals('Administration', $translations['admin_index_title']['en']);

        $dao->setTranslation('admin_index_title', [
            'de' => 'Editiert',
            'en' => 'Edited'
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

    }

    public function testSetTranslationWithModule()
    {

    }

    public function testRemove()
    {
        $dao = new Opus_Translate_Dao();

        $translations = [
            'de' => 'Verwaltung',
            'en' => 'Administration'
        ];

        $dao->setTranslation('admin', $translations);

        $data = $dao->getTranslation('admin');

        $this->assertEquals($translations, $data);

        $dao->remove('admin');

        $this->assertNull($dao->getTranslation('admin'));
    }

    public function testRemoveAll()
    {
        $dao = new Opus_Translate_Dao();

        $translations = [
            'de' => 'Verwaltung',
            'en' => 'Administration'
        ];

        $dao->setTranslation('admin', $translations);

        $this->assertEquals($translations, $dao->getTranslation('admin'));

        $dao->removeAll();

        $this->assertNull($dao->getTranslation('admin'));
    }

    public function testRemoveModule()
    {
        $dao = new Opus_Translate_Dao();

        $translations1 = [
            'de' => 'Verwaltung',
            'en' => 'Administration'
        ];

        $dao->setTranslation('admin', $translations1);

        $this->assertEquals($translations1, $dao->getTranslation('admin'));

        $translations2 = [
            'de' => 'Testschüssel',
            'en' => 'test key'
        ];

        $dao->setTranslation('testkey', $translations2, 'test');

        $this->assertEquals($translations2, $dao->getTranslation('testkey'));
    }

    public function testGetTranslation()
    {
        $dao = new Opus_Translate_Dao();

        $dao->setTranslation('testkey1', [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one'
        ]);

        $translation = $dao->getTranslation('testkey1');

        $this->assertEquals([
            'de' => 'Testschlüssel 1',
            'en' => 'test key one'
        ], $translation);
    }

    public function testGetTranslationForLocale()
    {

    }

    public function testGetTranslations()
    {
        $dao = new Opus_Translate_Dao();

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one'
        ];

        $dao->setTranslation('testkey1', $data);

        $dataModule = [
            'de' => 'Modul',
            'en' => 'Module'
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
        $dao = new Opus_Translate_Dao();

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one'
        ];

        $dao->setTranslation('testkey1', $data);

        $dataAdmin = [
            'de' => 'Verwaltung',
            'en' => 'Administration'
        ];

        $dao->setTranslation('admin', $dataAdmin, 'admin');

        $translations = $dao->getTranslations('admin');

        $this->assertCount(1, $translations);
        $this->arrayHasKey('admin', $translations);
        $this->assertEquals($dataAdmin, $translations['admin']);
    }

    public function testGetTranslationsByLocale()
    {
        $dao = new Opus_Translate_Dao();

        $data = [
            'de' => 'Testschlüssel 1',
            'en' => 'test key one'
        ];

        $dao->setTranslation('testkey1', $data);

        $dataModule = [
            'de' => 'Modul',
            'en' => 'Module'
        ];

        $dao->setTranslation('modulekey', $dataModule);

        $all = $dao->getTranslationsByLocale();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('en', $all);
        $this->assertArrayHasKey('de', $all);

        $this->assertEquals([
            'en' => [
                'modulekey' => 'Module',
                'testkey1' => 'test key one'
            ],
            'de' => [
                'modulekey' => 'Modul',
                'testkey1' => 'Testschlüssel 1'
            ]
        ], $all);
    }
}
