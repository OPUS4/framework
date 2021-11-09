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
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Util
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest\Util;

use Opus\Config;
use Opus\Util\File;
use OpusTest\TestAsset\TestCase;

use function file_exists;
use function mkdir;
use function touch;
use function uniqid;

use const DIRECTORY_SEPARATOR;

class FileTest extends TestCase
{
    private $srcPath = '';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $config = Config::get();
        $path   = $config->workspacePath . '/' . uniqid();

        $this->srcPath = $path . '/src';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
        File::deleteDirectory($this->srcPath);
        parent::tearDown();
    }

    /**
     * Test deleting a non-existing directory.
     */
    public function testDeleteNonExistingDirectory()
    {
        $this->assertFalse(file_exists($this->srcPath));
        $this->assertTrue(File::deleteDirectory($this->srcPath));
    }

    /**
     * Test deleting an empty directory.
     */
    public function testDeleteEmptyDirectory()
    {
        mkdir($this->srcPath, 0777, true);
        $this->assertTrue(File::deleteDirectory($this->srcPath));
        $this->assertFalse(file_exists($this->srcPath));
    }

    /**
     * Test deleting a non-empty directory.
     */
    public function testDeleteNonEmptyDirectory()
    {
        mkdir($this->srcPath, 0777, true);
        touch($this->srcPath . '/test.txt');
        $this->assertTrue(File::deleteDirectory($this->srcPath));
        $this->assertFalse(file_exists($this->srcPath));
        $this->assertFalse(file_exists($this->srcPath));
    }

    /**
     * Test using deleteDirectory on a file (should work).
     */
    public function testDeleteDirectoryOnFile()
    {
        mkdir($this->srcPath, 0777, true);
        $file = $this->srcPath . '/test.txt';
        touch($file);
        $this->assertTrue(File::deleteDirectory($file));
        $this->assertFalse(file_exists($file));
    }

    /**
     * Test adding a directory separator to path.
     */
    public function testAddDirectorySeparator()
    {
        $path = $this->srcPath;
        $this->assertEquals(
            $path . DIRECTORY_SEPARATOR,
            File::addDirectorySeparator($path)
        );
    }

    /**
     * Test using addDirectorySeparator on null value.
     */
    public function testAddDirectorySeparatorOnNull()
    {
        $path = null;
        $this->assertEquals(null, File::addDirectorySeparator($path));
    }

    /**
     * Test using addDirectorySeparator on path with existing separator.
     */
    public function testAddDirectorySeparatorWithAlreadyExistingSeparator()
    {
        $path = $this->srcPath . DIRECTORY_SEPARATOR;
        $this->assertEquals(
            $path,
            File::addDirectorySeparator($path)
        );
    }

    /**
     * Test adding directory separator to path with trailing whitespaces.
     *
     * TODO is the function meant to keep the trailing whitespaces or remove them?
     *      the original implementation of the function contains a line for removing whitespace including a comment
     *      about it, but the line didn't work, so the function kept the whitespace (like it is tested here), but
     *      was that the goal?
     */
    public function testAddDirectorySeparatorWithTrailingWhitespaces()
    {
        $path = $this->srcPath;
        $this->assertEquals(
            $path . DIRECTORY_SEPARATOR,
            File::addDirectorySeparator($path . '    ')
        );
    }
}
