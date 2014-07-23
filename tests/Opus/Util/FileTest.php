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
 * @package     Opus_Util
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Util_FileTest extends TestCase {

    private $__src_path = '';

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        parent::setUp();

        $config = Zend_Registry::get('Zend_Config');
        $path = $config->workspacePath . '/' . uniqid();

        $this->__src_path = $path . '/src';
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {
        Opus_Util_File::deleteDirectory($this->__src_path);
        parent::tearDown();
    }

    /**
     * Test deleting a non-existing directory.
     */
    public function testDeleteNonExistingDirectory() {
        $this->assertFalse(file_exists($this->__src_path));
        $this->assertTrue(Opus_Util_File::deleteDirectory($this->__src_path));
    }

    /**
     * Test deleting an empty directory.
     */
    public function testDeleteEmptyDirectory() {
        mkdir($this->__src_path, 0777, true);
        $this->assertTrue(Opus_Util_File::deleteDirectory($this->__src_path));
        $this->assertFalse(file_exists($this->__src_path));
    }

    /**
     * Test deleting a non-empty directory.
     */
    public function testDeleteNonEmptyDirectory() {
        mkdir($this->__src_path, 0777, true);
        touch($this->__src_path . '/' . 'test.txt');
        $this->assertTrue(Opus_Util_File::deleteDirectory($this->__src_path));
        $this->assertFalse(file_exists($this->__src_path));
        $this->assertFalse(file_exists($this->__src_path));
    }

    /**
     * Test using deleteDirectory on a file (should work).
     */
    public function testDeleteDirectoryOnFile() {
        mkdir($this->__src_path, 0777, true);
        $file = $this->__src_path . '/' . 'test.txt';
        touch($file);
        $this->assertTrue(Opus_Util_File::deleteDirectory($file));
        $this->assertFalse(file_exists($file));
    }

    /**
     * Test adding a directory separator to path.
     */
    public function testAddDirectorySeparator() {
        $path = $this->__src_path;
        $this->assertEquals($path . DIRECTORY_SEPARATOR,
                Opus_Util_File::addDirectorySeparator($path));
    }

    /**
     * Test using addDirectorySeparator on null value.
     */
    public function testAddDirectorySeparatorOnNull() {
        $path = null;
        $this->assertEquals(null, Opus_Util_File::addDirectorySeparator($path));
    }

    /**
     * Test using addDirectorySeparator on path with existing separator.
     */
    public function testAddDirectorySeparatorWithAlreadyExistingSeparator() {
        $path = $this->__src_path . DIRECTORY_SEPARATOR;
        $this->assertEquals($path,
                Opus_Util_File::addDirectorySeparator($path));
    }

    /**
     * Test adding directory separator to path with trailing whitespaces.
     */
    public function testAddDirectorySeparatorWithTrailingWhitespaces() {
        $path = $this->__src_path . '   ';
        $this->assertEquals($path . DIRECTORY_SEPARATOR,
                Opus_Util_File::addDirectorySeparator($path));
    }

}

