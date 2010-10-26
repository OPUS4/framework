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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases to load all class files.
 *
 * @package Opus
 * @category Tests
 *
 * @group RequireTest
 *
 */
class Opus_RequireTest extends TestCase {

    /**
     * Overwrite standard setUp method, no database connection needed.  Will
     * create a file listing of class files instead.
     *
     * @return void
     */
    public function setUp() {
    }

    /**
     * Overwrite standard tearDown method, no cleanup needed.
     *
     * @return void
     */
    public function tearDown() {
    }

    /**
     * Try to load all class files, just to make sure no syntax error have
     * been introduced.  As a side effect, all classes will be visible to
     * code coverage report.
     *
     * @return void
     */
    public function testRequire() {
        $cmd = 'find ../library/Opus/ -type f -iname "*php" |cut -d/ -f3-';
        $classFiles = array();
        exec($cmd, $classFiles);

        foreach ($classFiles AS $file) {
            require_once($file);
        }
    }

    /**
     * Try to load all class files and instanciate objects.
     *
     * @return void
     */
    public function testInstanciateTest() {
        $cmd = 'find ../library/Opus/ -type f -iname "*php" -print0 |xargs -r0 grep -hE "class[[:space:]]+Opus_" |cut -d" " -f 2 |grep Opus_';
        $classes = array();
        exec($cmd, $classes);

        $blacklist = array(
           "Opus_Validate_MateDecorator",
           "Opus_Db_Adapter_Pdo_Mysqlutf8",
           "Opus_Bootstrap_Base",
           "Opus_Statistic_LocalCounter",
           "Opus_Identifier_Urn",
           "Opus_GPG",
           "Opus_SolrSearch_ResponseRenderer",
           "Opus_SolrSearch_FacetItem",
           "Opus_Document_Type",
           "Opus_Security_Realm",
           "Opus_Model_Field",
           "Opus_Storage_File",
        );

        foreach ($classes AS $class) {
            if (in_array($class, $blacklist)) {
               continue;
            }
            try {
               $object = new $class();
            }
            catch (Exception $e) {
               $this->fail("Loading class $class failed: " . $e->getMessage());
            }
        }
    }
}
