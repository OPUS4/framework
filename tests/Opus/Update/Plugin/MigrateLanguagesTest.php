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

namespace OpusTest\Update\Plugin;

use Opus\Database;
use Opus\Db2\Configuration;
use Opus\Db2\Database as DoctrineDatabase;
use Opus\Translate\Dao;
use Opus\Update\Plugin\MigrateLanguages;
use OpusTest\TestAsset\TestCase;

class MigrateLanguagesTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $database = new Database();
        $database->setQuiet(true);
        $database->drop();
        $database->create();
        $database->importSchema(24);
    }

    public function tearDown(): void
    {
        $database = new Database();
        $database->setQuiet(true);
        $database->drop();
        $database->create();
        $database->importSchema();

        parent::tearDown();
    }

    public function testBeforeUpdate()
    {
        $this->addLanguage('ger', 'deu', 'de', 1, 'German');
        $this->addLanguage('fre', 'fra', 'fr', 1, 'French');
        $this->addLanguage('eng', 'eng', 'en', 0, 'English');
        $this->addLanguage('l1B', 'l1T', 'l1', 1, 'Test');

        $translate = new Dao();
        $translate->setTranslation('deu', [
            'en' => 'German',
            'de' => 'Deutsch',
        ]);
        $translate->setTranslation('fra', [
            'en' => 'French',
            'de' => 'Französisch',
        ]);
        $translate->setTranslation('eng', [
            'en' => 'English',
            'de' => 'Englisch',
        ]);

        $plugin = new MigrateLanguages();

        $plugin->beforeUpdate();

        $config = new Configuration();
        $this->assertEquals('deu, fra, l1T', $config->getOption('i18n.languages.active'));

        $this->assertEquals('l1B, l1T, l1, Test', $config->getOption('i18n.languages.local.l1T'));

        $this->assertNull($translate->getTranslation('deu'));
        $this->assertNotNull($translate->getTranslation('i18n_language_deu'));
        $this->assertNull($translate->getTranslation('fra'));
        $this->assertNotNull($translate->getTranslation('i18n_language_fra'));
        $this->assertNull($translate->getTranslation('eng'));
    }

    protected function addLanguage(string $part2b, string $part2t, string $part1, int $active, string $refName): void
    {
        $conn         = DoctrineDatabase::getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $queryBuilder->insert('languages')
            ->values([
                'part2_b'  => ':part2b',
                'part2_t'  => ':part2t',
                'part1'    => ':part1',
                'active'   => ':active',
                'ref_name' => ':refName',
            ])
            ->setParameter('part2b', $part2b)
            ->setParameter('part2t', $part2t)
            ->setParameter('part1', $part1)
            ->setParameter('active', $active)
            ->setParameter('refName', $refName);

        $queryBuilder->executeQuery();
    }
}
