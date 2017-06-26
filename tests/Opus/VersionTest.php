<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2010-2017, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_VersionTest extends TestCase
{

    public function testCompareVersion()
    {
        $this->assertEquals(1, Opus_Version::compareVersion('5.0')); // greater
        $this->assertEquals(-1, Opus_Version::compareVersion('4.0')); // smaller
        $this->assertEquals(0, Opus_Version::compareVersion(Opus_Version::VERSION)); // same
    }

    public function testGetSchemaVersion()
    {
        $version = Opus_Version::getSchemaVersion();

        $this->assertInternalType('string', $version);
        $this->assertTrue(ctype_digit($version));
    }

    /**
     * Check if version in Opus_Version matches current version in 'opus4schema.sql'.
     */
    public function testSchemaVersionMatches()
    {
        $expectedVersion = Opus_Version::getSchemaVersion();

        $schema = file_get_contents(APPLICATION_PATH . '/db/schema/opus4schema.sql');

        $matches = array();

        $match = preg_match('/INSERT INTO `schema_version` \\(`version`\\) VALUES \\((\d+)\\);/', $schema, $matches);

        $this->assertEquals(1, $match);
        $this->assertCount(2, $matches);

        $schemaVersion = $matches[1];

        $this->assertEquals($expectedVersion, $schemaVersion, 'Schema version and expected version must match.');
    }

    public function testSchemaVersionInUpdateScriptMatchesName()
    {
        $update = new Opus_Database();

        $scripts = $update->getUpdateScripts();

        foreach ($scripts as $script)
        {
            $basename = basename($script);
            $scriptNameVersion = ( int )substr($basename, 0, 3);

            if ($scriptNameVersion < 3)
            {
                // skip check because versioning schema did not exist before
                continue;
            }

            $scriptContent = file_get_contents($script);

            $matches = array();

            $match = preg_match(
                '/INSERT INTO `schema_version` \\(`version`\\) VALUES.*\\((\d+)\\);/', $scriptContent, $matches
            );

            $this->assertEquals(1, $match, "Could not find version in script '$basename'.");
            $this->assertCount(2, $matches);

            $scriptSpecifiedVersion = $matches[1];

            $this->assertEquals(
                $scriptNameVersion, $scriptSpecifiedVersion,
                'Number of script name must match schema version specified in script.'
            );
        }
    }

}

