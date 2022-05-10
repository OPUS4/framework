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
 * @copyright   Copyright (c) 2010-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @author      Jens Schwidder <schwidder@zib.de>
 */

namespace OpusTest;

use Exception;
use Opus\Common\Bootstrap\Base;
use Opus\Common\Validate\MateDecorator;
use Opus\Doi\DataCiteXmlGenerationException;
use Opus\Identifier\Urn;
use Opus\Model\DateField;
use Opus\Model\Field;
use Opus\Model\UnixTimestampField;
use Opus\Security\Realm;
use Opus\Statistic\LocalCounter;
use Opus\Storage\File as OpusStorageFile;
use Opus\Translate\DatabaseAdapter;
use OpusDb_Mysqlutf8;
use OpusTest\TestAsset\TestCase;

use function exec;
use function in_array;

/**
 * Test cases to load all class files.
 *
 * @package Opus
 * @category Tests
 * @group RequireTest
 */
class RequireTest extends TestCase
{
    /**
     * Overwrite standard setUp method, no database connection needed.  Will
     * create a file listing of class files instead.
     */
    public function setUp()
    {
    }

    /**
     * Overwrite standard tearDown method, no cleanup needed.
     */
    public function tearDown()
    {
    }

    /**
     * Try to load all class files, just to make sure no syntax error have
     * been introduced.  As a side effect, all classes will be visible to
     * code coverage report.
     */
    public function testRequire()
    {
        $path       = APPLICATION_PATH . '/library/Opus/';
        $cmd        = "find $path -type f -iname \"*php\"";
        $classFiles = [];
        exec($cmd, $classFiles);

        foreach ($classFiles as $file) {
            require_once $file;
        }
    }

    /**
     * @return array
     *
     * TODO NAMESPACE fix - it is looking for classes with Opus_
     */
    public function instantiateTestProvider()
    {
        $path    = APPLICATION_PATH . '/library/Opus/';
        $cmd     = "find $path -type f -iname \"*php\" -print0 |xargs -r0 grep -hE \"class[[:space:]]+Opus_\" |cut -d\" \" -f 2 |grep Opus_";
        $classes = [];
        exec($cmd, $classes);

        $blacklist = [
            MateDecorator::class,
            OpusDb_Mysqlutf8::class,
            Base::class,
            LocalCounter::class,
            Urn::class,
            Realm::class,
            Field::class,
            UnixTimestampField::class,
            DateField::class,
            OpusStorageFile::class,
            DatabaseAdapter::class,
            DataCiteXmlGenerationException::class,
        ];

        $data = [];

        foreach ($classes as $class) {
            if (in_array($class, $blacklist)) {
                continue;
            }
            $data[$class] = [$class];
        }

        return $data;
    }

    /**
     * Try to load all class files and instantiate objects.
     *
     *
     *
     * Class files must be loaded (required_once) before the classes can be used.
     *
     * @param string $class
     * @depends testRequire
     * @dataProvider instantiateTestProvider
     */
    public function testInstantiateTest($class)
    {
        try {
            new $class();
        } catch (Exception $e) {
            $this->fail("Loading class $class failed: " . $e->getMessage());
        }
    }
}
