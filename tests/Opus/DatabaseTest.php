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
 * @copyright   Copyright (c) 2008-2017, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

class Opus_DatabaseTest extends TestCase {

    public function testGetSqlFiles() {
        $database = new Opus_Database();

        $files = $database->getSqlFiles(APPLICATION_PATH . '/db/schema');

        $this->assertGreaterThan(4, $files);
        $this->assertContains(APPLICATION_PATH . '/db/schema/opus4schema.sql', $files);
        $this->assertContains( APPLICATION_PATH . '/db/schema/001-OPUS-4.4.4.sql', $files);
    }

    public function testGetSchemaFile() {
        $database = new Opus_Database();

        $this->assertEquals(APPLICATION_PATH . '/db/schema/opus4schema.sql', $database->getSchemaFile());
    }

    public function testGetUpdateScripts() {
        $database = new Opus_Database();

        $scripts = $database->getUpdateScripts();

        $this->assertGreaterThan(0, $scripts);

        $basenames = array_map('basename', $scripts);

        $this->assertContains('001-OPUS-4.4.4.sql', $basenames);
        $this->assertContains('002-OPUS-4.5.sql', $basenames);
    }

    public function testGetUpdateScriptsSorting() {
        $database = new Opus_Database();

        $scripts = $database->getUpdateScripts();

        $lastNumber = 0;

        foreach ($scripts as $script)
        {
            $basename = basename($script);
            $number = substr($basename, 0, 3);

            $this->assertGreaterThan($lastNumber, $number);
            $lastNumber = $number;
        }
    }

    public function testGetUpdateScriptsFrom() {
        $database = new Opus_Database();

        $scripts = $database->getUpdateScripts(2);

        $lastNumber = 0;

        foreach ($scripts as $script)
        {
            $number = substr(basename($script), 0, 3);
            $this->assertGreaterThan(2, $number);
            $this->assertGreaterThan($lastNumber, $number);
            $lastNumber = $number;
        }
    }

    public function testGetUpdateScriptsFromTo() {
        $database = new Opus_Database();

        $scripts = $database->getUpdateScripts(1, 2);

        $this->assertCount(1, $scripts);

        $number = substr(basename($scripts[0]), 0, 3);

        $this->assertEquals(2, $number);
    }

    public function testGetUpdateScriptsUntil() {
        $database = new Opus_Database();

        $scripts = $database->getUpdateScripts(null, 2);

        $lastNumber = 0;

        foreach($scripts as $script)
        {
            $number = substr(basename($script), 0, 3);
            $this->assertLessThanOrEqual(2, $number);
            $this->assertGreaterThan($lastNumber, $number);
            $lastNumber = $number;
        }
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
     * @expectedExceptionMessage schema_ver' doesn't exist
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
     * @expectedExceptionMessage schema_ver' doesn't exist
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

        $database->exec(
            'TRUNCATE TABLE `schema_version`; INSERT INTO `schema_version` (`version`) VALUES (\'5\');'
        );

        $version = $database->getVersion();

        $this->assertEquals('5', $version);

        $database->exec(
            'TRUNCATE TABLE `schema_version`; INSERT INTO `schema_version` (`version`) VALUES (\'2\');'
        );

        $version = $database->getVersion();

        $this->assertEquals(2, $version);
    }

    public function testGetVersionNullForOldDatabase()
    {
        $database = new Opus_Database();

        $version = $database->getVersion();

        $this->assertNull($version);
    }

    public function testGetLatestVersion()
    {
        $database = new Opus_Database();

        $scripts = $database->getUpdateScripts();

        // TODO this only works if there are no gaps in the numbering
        $this->assertEquals(count($scripts), $database->getLatestVersion());
        $this->assertInternalType('int', $database->getLatestVersion());
    }

    public function testImportSchema()
    {
        $this->markTestIncomplete('TODO - how to do schema import testing within the regular test environment?');
    }

}
