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
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Db;

use Exception;
use Opus\Db\Accounts;
use Opus\Db\Collections;
use Opus\Db\CollectionsEnrichments;
use Opus\Db\CollectionsRoles;
use Opus\Db\DnbInstitutes;
use Opus\Db\DocumentEnrichments;
use Opus\Db\DocumentFiles;
use Opus\Db\DocumentIdentifiers;
use Opus\Db\DocumentLicences;
use Opus\Db\DocumentNotes;
use Opus\Db\DocumentPatents;
use Opus\Db\DocumentReferences;
use Opus\Db\Documents;
use Opus\Db\DocumentStatistics;
use Opus\Db\DocumentSubjects;
use Opus\Db\DocumentTitleAbstracts;
use Opus\Db\FileHashvalues;
use Opus\Db\Ipranges;
use Opus\Db\Languages;
use Opus\Db\LinkAccountsRoles;
use Opus\Db\LinkDocumentsCollections;
use Opus\Db\LinkDocumentsDnbInstitutes;
use Opus\Db\LinkDocumentsLicences;
use Opus\Db\LinkIprangesRoles;
use Opus\Db\LinkPersonsDocuments;
use Opus\Db\Persons;
use Opus\Db\TableGateway;
use Opus\Db\UserRoles;
use OpusTest\TestAsset\TestCase;

use function get_class;
use function is_array;

/**
 * Test cases for instanciation of table gateway classes.
 *
 * @category    Tests
 * @package     Opus\Db
 * @group       InstanciateGatewayTest
 */
class InstanciateGatewayTest extends TestCase
{
    /**
     * Overwrite parent methods.
     */
    public function setUp(): void
    {
    }

    public function tearDown(): void
    {
    }

    /**
     * Provider for table gateway class names.
     *
     * @return array List of table gateways.
     */
    public static function tableGatewayDataProvider()
    {
        return [
            [Accounts::class],
            [CollectionsRoles::class],
            [Collections::class],
            [CollectionsEnrichments::class],
            [DnbInstitutes::class],
            [DocumentEnrichments::class],
            [DocumentFiles::class],
            [DocumentIdentifiers::class],
            [DocumentLicences::class],
            [DocumentNotes::class],
            [DocumentPatents::class],
            [DocumentReferences::class],
            [Documents::class],
            [DocumentStatistics::class],
            [DocumentSubjects::class],
            [DocumentTitleAbstracts::class],
            [FileHashvalues::class],
            [Ipranges::class],
            [Languages::class],
            [LinkAccountsRoles::class],
            [LinkDocumentsCollections::class],
            [LinkDocumentsDnbInstitutes::class],
            [LinkDocumentsLicences::class],
            [LinkIprangesRoles::class],
            [LinkPersonsDocuments::class],
            [Persons::class],
            [UserRoles::class],
        ];
    }

    /**
     * Test if a given table gateway class can be instanciated.
     *
     * @param string $tableGateway Class name of a table gateway.
     * @dataProvider tableGatewayDataProvider
     */
    public function testSpawnGateway($tableGateway)
    {
        try {
            // Test, if creating instance works.
            $table = TableGateway::getInstance($tableGateway);
            $this->assertNotNull($table);
            $this->assertNotNull(get_class($table) === $tableGateway);

            $exampleRow = $table->createRow();

            // Test, if instance exists in instances array afterwards.
            $instances = TableGateway::getAllInstances();
            $this->assertTrue(
                is_array($instances),
                'Instances should be array.'
            );
            $this->assertArrayHasKey(
                $tableGateway,
                $instances,
                'Current instance should be in instance array.'
            );

            // Test, if second call gives same TableGateway.
            $table2 = TableGateway::getInstance($tableGateway);
            $this->assertNotNull($table2);
            $this->assertNotNull(get_class($table2) === $tableGateway);

            $this->assertTrue(
                $table === $table2,
                'Singleton should return same object on second call'
            );
        } catch (Exception $ex) {
            $this->fail("Failed to instanciate $tableGateway: " . $ex->getMessage());
        }
    }
}
