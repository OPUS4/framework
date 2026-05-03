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
 * @copyright   Copyright (c) 2026, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Db2;

use Opus\Db2\Configuration;
use OpusTest\TestAsset\TestCase;
use Zend_Config;

class ConfigurationTest extends TestCase
{
    /** @var Configuration */
    private $configuration;

    public function setUp(): void
    {
        parent::setUp();

        $this->clearTable('configuration');

        $this->configuration = new Configuration();
    }

    public function testSetOption()
    {
        $this->configuration->setOption('testOption', 'testValue');
        $this->assertEquals('testValue', $this->configuration->getOption('testOption'));
    }

    public function testSetOptionNull()
    {
        $this->configuration->setOption('testOption', 'testValue');
        $this->assertEquals('testValue', $this->configuration->getOption('testOption'));

        $this->configuration->setOption('testOption', null);
        $this->assertNull($this->configuration->getOption('testOption'));
    }

    public function testSetOptionUpdateValue()
    {
        $this->configuration->setOption('i18n.languages.active', 'deu, eng');
        $this->assertEquals('deu, eng', $this->configuration->getOption('i18n.languages.active'));

        $this->configuration->setOption('i18n.languages.active', 'deu, eng, fra');
        $this->assertEquals('deu, eng, fra', $this->configuration->getOption('i18n.languages.active'));
    }

    public function testGetOption()
    {
        $this->configuration->setOption('i18n.languages.active', 'deu, eng');
        $result = $this->configuration->getOption('i18n.languages.active');
        $this->assertEquals('deu, eng', $result);
    }

    public function testGetOptionUnknown()
    {
        $value = $this->configuration->getOption('opus.test.option');
        $this->assertNull($value);
    }

    public function testGetOptionArray()
    {
        $this->configuration->setOption('i18n.languages.local.cmn', ', zho, zh, Chinesisch/Manadrin');
        $this->configuration->setOption('i18n.languages.local.wuu', ', zho, zh, Chinesisch/Wu');

        $result = $this->configuration->getOption('i18n.languages.local');

        $this->assertEquals([
            'cmn' => ', zho, zh, Chinesisch/Manadrin',
            'wuu' => ', zho, zh, Chinesisch/Wu',
        ], $result);
    }

    public function testGetOptionArrayConflict()
    {
        $this->configuration->setOption('i18n.languages.local.cmn', ', zho, zh, Chinesisch/Manadrin');
        $this->configuration->setOption('i18n.languages.local.wuu', ', zho, zh, Chinesisch/Wu');
        $this->configuration->setOption('i18n.languages.localEnabled', '1');

        $result = $this->configuration->getOption('i18n.languages.local');

        $this->assertEquals([
            'cmn' => ', zho, zh, Chinesisch/Manadrin',
            'wuu' => ', zho, zh, Chinesisch/Wu',
        ], $result);
    }

    public function testGetOptionShortConflict()
    {
        $this->configuration->setOption('opusVersion', '4.9');
        $this->configuration->setOption('opusTheme', 'default');

        $result = $this->configuration->getOption('opus');

        $this->assertNull($result);
    }

    public function testGetOptionAllWithMatchingPrefix()
    {
        $this->configuration->setOption('opusVersion', '4.9');
        $this->configuration->setOption('opus.default', 'test1');
        $this->configuration->setOption('opus.current', 'test2');

        $result = $this->configuration->getOption('opus', true);

        $this->assertEquals([
            'opusVersion'  => '4.9',
            'opus.default' => 'test1',
            'opus.current' => 'test2',
        ], $result);
    }

    public function testGetConfig()
    {
        $this->configuration->setOption('i18n.languages.active', 'deu, eng, fra, rus, spa, por');
        $this->configuration->setOption('i18n.languages.sortByName', '0');

        $config = $this->configuration->getConfig();

        $this->assertInstanceOf(Zend_Config::class, $config);
        $this->assertTrue(isset($config->i18n->languages->active));
        $this->assertEquals('deu, eng, fra, rus, spa, por', $config->i18n->languages->active);
        $this->assertTrue(isset($config->i18n->languages->sortByName));
        $this->assertEquals('0', $config->i18n->languages->sortByName);
    }

    public function testImport()
    {
        $config = new Zend_Config([
            'i18n' => [
                'languages' => [
                    'active'     => 'deu, eng',
                    'sortByName' => '1',
                ],
            ],
        ]);

        $this->configuration->import($config);

        $this->assertEquals('deu, eng', $this->configuration->getOption('i18n.languages.active'));
        $this->assertEquals('1', $this->configuration->getOption('i18n.languages.sortByName'));
    }

    public function testImportSimpleArrayWithoutKeys()
    {
        $config = new Zend_Config([
            'languages' => ['deu', 'eng'],
        ]);

        $this->configuration->import($config);

        $this->assertEquals(['deu', 'eng'], $this->configuration->getOption('languages'));
    }

    public function testGetConfigSimpleArrayWithoutKeys()
    {
        $this->configuration->import(new Zend_Config([
            'languages' => ['deu', 'eng'],
        ]));

        $config = $this->configuration->getConfig();

        $this->assertEquals([
            'languages' => ['deu', 'eng'],
        ], $config->toArray());
    }

    public function testRemoveOption()
    {
        $this->configuration->setOption('i18n.languages.active', 'deu, eng');
        $this->assertEquals('deu, eng', $this->configuration->getOption('i18n.languages.active'));
        $this->configuration->remove('i18n.languages.active');
        $this->assertNull($this->configuration->getOption('i18n.languages.active'));
    }

    public function testRemoveArray()
    {
        $config = new Zend_Config([
            'languages' => ['deu', 'eng'],
        ]);

        $this->configuration->import($config);
        $this->assertEquals(['deu', 'eng'], $this->configuration->getOption('languages'));

        $this->configuration->remove('languages');

        $this->assertNull($this->configuration->getOption('languages'));
    }

    public function testReset()
    {
        $this->configuration->setOption('i18n.languages.active', 'deu, eng');
        $this->assertEquals('deu, eng', $this->configuration->getOption('i18n.languages.active'));

        $this->configuration->reset();
        $this->assertNull($this->configuration->getOption('i18n.languages.active'));
    }
}
