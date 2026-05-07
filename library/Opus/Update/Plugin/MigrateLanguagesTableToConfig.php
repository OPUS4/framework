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

namespace Opus\Update\Plugin;

use Opus\Db2\Configuration;
use Opus\Db2\Database;
use Opus\I18n\Languages;
use Opus\Update\SchemaUpdatePluginInterface;

use function filter_var;
use function implode;
use function trim;

use const FILTER_VALIDATE_BOOLEAN;

/**
 * Migrates active languages from 'languages' table to configuration.
 *
 * The 'languages' table is removed in version 25 of the database schema.
 * Before the update the active languages should be migrated as a list into
 * the 'configuration' table.
 */
class MigrateLanguagesTableToConfig implements SchemaUpdatePluginInterface
{
    public function beforeUpdate(): void
    {
        $sql = 'select * from languages';

        $conn = Database::getConnection();
        $stmt = $conn->executeQuery($sql);

        $activeLanguages      = [];
        $unsupportedLanguages = [];

        while (($row = $stmt->fetchAssociative()) !== false) {
            $part2t = $row['part2_t'];
            $active = filter_var($row['active'], FILTER_VALIDATE_BOOLEAN);
            if ($active) {
                $activeLanguages[] = $part2t;
            }
            if (null === Languages::getLanguage($part2t)) {
                $unsupportedLanguages[] = $row;
            }
        }

        $config = new Configuration();

        // Set active languages in local configuration
        $config->setOption('i18n.languages.active', trim(implode(', ', $activeLanguages)));

        // Add unknown languages to local configuration
        foreach ($unsupportedLanguages as $language) {
            $part2t  = $language['part2_t'];
            $part2b  = $language['part2_b'];
            $part1   = $language['part1'];
            $refName = $language['ref_name'];
            $config->setOption("i18n.languages.local.{$part2t}", "{$part2b}, ${part2t}, ${part1}, ${refName}");
        }
    }

    public function afterUpdate(): void
    {
        // do nothing
    }
}
