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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2008-2016, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_DatabaseTest extends TestCase {

    public function testGetSqlFiles() {
        $database = new Opus_Database();

        $files = $database->getSqlFiles(APPLICATION_PATH . '/db/schema');

        $this->assertCount(1, $files);
        $this->assertEquals(APPLICATION_PATH . '/db/schema/opus4schema.sql', $files[0]);
    }

    public function testGetSchemaFile() {
        $database = new Opus_Database();

        $this->assertEquals(APPLICATION_PATH . '/db/schema/opus4schema.sql', $database->getSchemaFile());
    }

    public function testGetUpdateScripts() {
        $database = new Opus_Database();

        $scripts = $database->getUpdateScripts();

        $this->assertGreaterThan(0, $scripts);

        $this->assertEquals("update-4.5.sql", basename($scripts[0]));
    }

    public function testGetUpdateScriptsSorting() {
        $this->markTestIncomplete('not yet implemented');
    }

    public function testGetUpdateScriptsRange() {
        $this->markTestIncomplete('not yet implemented');
    }

    /**
     * @expectedException PDOException
     */
    public function testBadSqlThrowsException() {
        $this->markTestIncomplete('exec function does not throw exceptions yet');

        $database = new Opus_Database();

        $sql = 'SELECT * FROM `schema_version2`';

        $database->exec($sql);
    }

    /**
     * Tests if an error in multiple statements is reported.
     * @expectedException PDOException
     * @expectedExceptionMessage 'opusdb.schema_ver' doesn't exist
     */
    public function testPdoExecErrorReportingFirstStatement()
    {
        $database = new Opus_Database();

        $pdo = $database->getPdo($database->getName());

        $sql = 'TRUNCATE TABLE `schema_ver`; INSERT INTO `schema_version` (`version`) VALUES (\'5.0\');';

        $pdo->exec($sql);
    }

    /**
     * Error in second statement does not produce exception.
     */
    public function testPdoExecErrorReportingSecondStatement()
    {
        $database = new Opus_Database();

        $pdo = $database->getPdo($database->getName());

        $sql = 'TRUNCATE TABLE `schema_version`; INSERT INTO `schema_ver` (`version`) VALUES (\'5.0\');';

        $statement = $pdo->exec($sql);

        $this->assertEquals('00000', $pdo->errorCode());

        $stmt = $pdo->query('SELECT `version` FROM `schema_version`');

        $this->assertEquals(0, $stmt->rowCount());
    }

    /**
     * Using 'query' function produces exceptions when iterating through statements.
     *
     * @expectedException PDOException
     * @expectedExceptionMessage 'opusdb.schema_ver' doesn't exist
     */
    public function testPdoQueryErrorReporting()
    {
        $database = new Opus_Database();

        $pdo = $database->getPdo($database->getName());

        $sql = 'TRUNCATE TABLE `schema_version`; INSERT INTO `schema_ver` (`version`) VALUES (\'5.0\');';

        $statement = $pdo->query($sql);

        $this->assertEquals('00000', $statement->errorCode());

        $statement->nextRowset();
    }

    public function testPdoQueryErrorReportingExecutionAfterError()
    {
        $database = new Opus_Database();

        $pdo = $database->getPdo($database->getName());

        $sql = 'TRUNCATE TABLE `schema_version`; INSERT INTO `schema_ver` (`version`) VALUES (\'5.0\');'
            . 'INSERT INTO `schema_version` (`version`) VALUES (\'6.0\');';

        $statement = $pdo->query($sql);

        $this->assertEquals('00000', $statement->errorCode());

        try {
            $statement->nextRowset();

            $this->fail('Should have thrown exception.');
        }
        catch(PDOException $pdoex) {
        }

        $this->assertFalse($statement->nextRowset());
    }


    public function testPdoQueryIteratingResults()
    {
        $database = new Opus_Database();

        $pdo = $database->getPdo($database->getName());

        $sql = 'TRUNCATE TABLE `schema_version`; INSERT INTO `schema_version` (`version`) VALUES (\'5.0\');'
            . 'INSERT INTO `schema_version` (`version`) VALUES (\'6.0\');';

        $statement = $pdo->query($sql);

        // at this point all statements have been executed (if there is no error)

        $this->assertEquals('00000', $statement->errorCode());

        $this->assertTrue($statement->nextRowset()); // 2nd statement
        $this->assertTrue($statement->nextRowset()); // 3rd statement
        $this->assertFalse($statement->nextRowset()); // 4th not existing statement
    }

    public function testGetVersion()
    {
        $database = new Opus_Database();

        $database->exec('INSERT INTO `schema_version` (`version`) VALUES (\'4.5\');');

        $version = $database->getVersion();

        $this->assertEquals('4.5', $version);
    }

    public function testGetVersionOldSchema()
    {
        $database = new Opus_Database();

        $database->exec('ALTER TABLE `opusdb.schema_version` DROP `version`;');

        $version = $database->getVersion();

        $this->assertNull($version);
    }

}
