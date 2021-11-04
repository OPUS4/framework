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
 * @copyright   Copyright (c) 2021, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Db2
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Db2;

use Doctrine\DBAL\Connection;
use Opus\Db2\Database;
use Opus\Db2\Properties;
use Opus\Document;
use OpusTest\TestAsset\TestCase;

use function array_keys;

class DatabaseTest extends TestCase
{
    private $database;
    protected $properties;

    public function setUp()
    {
        parent::setUp();

        $this->database = new Database();

        $this->clearTables(false, [
            'documents',
            'model_properties',
            'model_types',
            'propertykeys',
            'document_identifiers',
        ]);

        $this->properties = new Properties();
    }

    /**
     * Adds a document with two properties to the database.
     *
     * @param array $documentProperties Associative array of document keys & values
     */
    private function prepareDocumentProperties($documentProperties)
    {
        $properties = $this->properties;

        $model = Document::new();
        $model->store();

        $properties->registerType('document');

        $keys = array_keys($documentProperties);
        foreach ($keys as $key) {
            $properties->registerKey($key);
        }

        foreach ($keys as $key) {
            $this->assertNull($properties->getProperty($model, $key));
        }

        foreach ($documentProperties as $key => $value) {
            $properties->setProperty($model, $key, $value);
        }

        foreach ($documentProperties as $key => $value) {
            $this->assertEquals($value, $properties->getProperty($model, $key));
        }
    }

    public function testGetConnectionParams()
    {
        $database = $this->database;

        $params = $database->getConnectionParams();

        // var_dump($params);

        $this->assertNotNull($params);

        $this->assertArrayHasKey('adapterNamespace', $params);
        $this->assertArrayHasKey('host', $params);
        $this->assertArrayHasKey('username', $params);
        $this->assertArrayHasKey('password', $params);
        $this->assertArrayHasKey('dbname', $params);
    }

    public function testGetConnection()
    {
        $database = $this->database;

        $conn = $database->getConnection();

        // var_dump($conn);

        $this->assertNotNull($conn);
        $this->assertInstanceOf(Connection::class, $conn);
    }

    public function testSqlInjectionWithConnectionQuerySelectSuccessful()
    {
        $key    = 'key1';
        $value  = 'value1';
        $key2   = 'key2';
        $value2 = 'value2';
        $this->prepareDocumentProperties([$key => $value, $key2 => $value2]);

        $database     = $this->database;
        $conn         = $database->getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $sqlInjection = '\'key1\'; DELETE FROM propertykeys WHERE 1=1';

        $select = $queryBuilder
            ->select('k.name', 'p.value')
            ->from('model_properties', 'p')
            ->join('p', 'propertykeys', 'k', 'p.key_id = k.id')
            ->where('k.name = ' . $sqlInjection);

        $values = $conn->fetchAllKeyValue($select);

        // query is successful and returns correct result
        $this->assertEquals([$key => $value], $values);

        $properties = new Properties();
        // due to successful SQL injection all property keys and properties were deleted
        $this->assertEmpty($properties->getKeys());
    }

    public function testSqlInjectionWithConnectionQuerySelectProtected()
    {
        $key    = 'key1';
        $value  = 'value1';
        $key2   = 'key2';
        $value2 = 'value2';
        $this->prepareDocumentProperties([$key => $value, $key2 => $value2]);

        $database     = $this->database;
        $conn         = $database->getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $sqlInjection = '\'key1\'; DELETE FROM propertykeys WHERE 1=1';

        $select = $queryBuilder
            ->select('k.name', 'p.value')
            ->from('model_properties', 'p')
            ->join('p', 'propertykeys', 'k', 'p.key_id = k.id')
            ->where('k.name = ' . $conn->quote($sqlInjection));

        $values = $conn->fetchAllKeyValue($select);

        // query return empty, because no key matches injection string
        $this->assertEmpty($values);

        $properties = new Properties();
        // All property keys still exist because injection failed
        $this->assertCount(2, $properties->getKeys());
    }

    public function testUnsuccessfulSqlInjectionWithConnectionQuerySelect()
    {
        $key    = 'key1';
        $value  = 'value1';
        $key2   = 'key2';
        $value2 = 'value2';
        $this->prepareDocumentProperties([$key => $value, $key2 => $value2]);

        $database     = $this->database;
        $conn         = $database->getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $sqlInjectionQuery = '" OR ""="';

        $select = $queryBuilder
            ->select('k.name', 'p.value')
            ->from('model_properties', 'p')
            ->join('p', 'propertykeys', 'k', 'p.key_id = k.id')
            ->where('k.name = ?');

        $values = $conn->fetchAllKeyValue($select, [$key . $sqlInjectionQuery]);

        // $values is empty instead of containing [$key => $value] since k.name gets set to: "key1" OR ""=""
        $this->assertEquals([], $values);

        $this->markTestIncomplete('TODO - can we still craft a successful SQL injection?');
    }

    public function testUnsuccessfulSqlInjectionWithQueryBuilderQuerySelect()
    {
        $key    = 'key1';
        $value  = 'value1';
        $key2   = 'key2';
        $value2 = 'value2';
        $this->prepareDocumentProperties([$key => $value, $key2 => $value2]);

        $database     = $this->database;
        $conn         = $database->getConnection();
        $queryBuilder = $conn->createQueryBuilder();

        $sqlInjectionQuery = '" OR ""="';

        $select = $queryBuilder
            ->select('k.name', 'p.value')
            ->from('model_properties', 'p')
            ->join('p', 'propertykeys', 'k', 'p.key_id = k.id')
            ->where('k.name = ?');

        $select->setParameters([$key . $sqlInjectionQuery]);
        $values = $queryBuilder->execute()->fetchAllKeyValue();

        // $values is empty instead of containing [$key => $value] since k.name gets set to: "key1" OR ""=""
        $this->assertEquals([], $values);

        $this->markTestIncomplete('TODO - can we still craft a successful SQL injection?');
    }
}
