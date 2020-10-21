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
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2010-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest;

use OpusTest\TestAsset\TestCase;

/**
 * Test cases to load all class files.
 *
 * @package Opus
 * @category Tests
 *
 * @group RequireTest
 *
 */
class RequireTest extends TestCase
{

    /**
     * Overwrite standard setUp method, no database connection needed.  Will
     * create a file listing of class files instead.
     *
     * @return void
     */
    public function setUp()
    {
    }

    /**
     * Overwrite standard tearDown method, no cleanup needed.
     *
     * @return void
     */
    public function tearDown()
    {
    }

    /**
     * Try to load all class files, just to make sure no syntax error have
     * been introduced.  As a side effect, all classes will be visible to
     * code coverage report.
     *
     * @return void
     */
    public function testRequire()
    {
        $path = APPLICATION_PATH . '/library/Opus/';
        $cmd = "find $path -type f -iname \"*php\"";
        $classFiles = [];
        exec($cmd, $classFiles);

        foreach ($classFiles as $file) {
            require_once($file);
        }
    }

    /**
     * @return array
     *
     * TODO NAMESPACE fix - it is looking for classes with Opus_
     */
    public function instantiateTestProvider()
    {
        $path = APPLICATION_PATH . '/library/Opus/';
        $cmd = "find $path -type f -iname \"*php\" -print0 |xargs -r0 grep -hE \"class[[:space:]]+Opus_\" |cut -d\" \" -f 2 |grep Opus_";
        $classes = [];
        exec($cmd, $classes);

        $blacklist = [
            'Opus\Validate\MateDecorator',
            'Opus\Db\Adapter\Pdo\Mysqlutf8',
            'Opus\Bootstrap\Base',
            'Opus\Statistic\LocalCounter',
            'Opus\Identifier\Urn',
            'Opus\GPG',
            'Opus\Security\Realm',
            'Opus\Model\Field',
            'Opus\Model\UnixTimestampField',
            'Opus\Model\DateField',
            'Opus\Storage\File',
            'Opus\Reviewer',
            'Opus\Privilege',
            'Opus\SolrSearch\Exception',
            'Opus\Util\MetadataImport',
            'Opus\Search\Solr\Solarium\Document',
            'Opus\Search\Solr\Solarium\Adapter',
            'Opus\Search\Solr\Solarium\Filter\Complex',
            'Opus\Search\Solr\Document\Xslt',
            'Opus\Search\Solr\Filter\Raw',
            'Opus\Search\Facet\Set',
            'Opus\Search\Facet\Field',
            'Opus\Search\Result\Facet',
            'Opus\Search\Result\Match',
            'Opus\Search\Filter\Simple',
            'Opus\Translate\DatabaseAdapter',
            'Opus\Translate\DefaultAdapter',
            'Opus\Doi\DataCiteXmlGenerationException'
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
     * @return void
     *
     * Class files must be loaded (required_once) before the classes can be used.
     * @depends testRequire
     *
     * @dataProvider instantiateTestProvider
     */
    public function testInstantiateTest($class)
    {
        try {
            new $class();
        } catch (\Exception $e) {
            $this->fail("Loading class $class failed: " . $e->getMessage());
        }
    }
}
