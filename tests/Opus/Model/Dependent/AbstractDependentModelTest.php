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
 * @copyright   Copyright (c) 2008-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace OpusTest\Model\Dependent;

use Opus\Common\Model\ModelException;
use Opus\Document;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Plugin\InvalidateDocumentCache;
use Opus\Subject;
use OpusTest\TestAsset\TestCase;
use Zend_Db_Table_Abstract;

use function class_exists;

/**
 * Test cases for class Opus\Model\Dependent\AbstractDependentModel.
 *
 * @package Opus\Model
 * @category Tests
 * @group DependentAbstractTest
 */
class AbstractDependentModelTest extends TestCase
{
    /**
     * Class instance under test.
     *
     * @var AbstractDependentModel
     */
    private $cut;

    /**
     * \Zend_Db_Table mockup.
     *
     * @var\Zend_Db_Table
     */
    private $mockTableGateway;

    /**
     * \Zend_Db_Table_Row mockup
     *
     * @var\Zend_Db_Table_Row
     */
    private $mockTableRow;

    /**
     * \Zend_Db_Adapter mockup
     *
     * @var\Zend_Db_Adapter
     */
    private $mockAdapter;

    /**
     * Set up test instance and mock environment.
     */
    public function setUp()
    {
        if (false === class_exists('Opus_Model_Dependent_AbstractTest_MockTableGateway', false)) {
            eval('
                class Opus_Model_Dependent_AbstractTest_MockTableGateway extends \Zend_Db_Table {
                    protected function _setup() {}
                    protected function _init() {}

                    // Method/array copy-pasted from\Zend_Db_Table_Abstract
                    public function info($key = null) {
                        $info = array(
                            self::SCHEMA           => "",
                            self::NAME             => "",
                            self::COLS             => "",
                            self::PRIMARY          => array("id"),
                            self::METADATA         => "",
                            self::ROW_CLASS        => "",
                            self::ROWSET_CLASS     => "",
                            self::REFERENCE_MAP    => "",
                            self::DEPENDENT_TABLES => "",
                            self::SEQUENCE         => "",
                        );
                        return $info;
                    }
                }
            ');
        }

        $config = ['dbname' => 'exampledb', 'password' => 'nopass', 'username' => 'nouser'];

        $this->mockAdapter = $this->getMockBuilder('Zend_Db_Adapter_Abstract')
            ->setMethods(
                [
                    '_connect',
                    '_beginTransaction',
                    '_commit',
                    '_rollback',
                    'listTables',
                    'describeTable',
                    'closeConnection',
                    'prepare',
                    'lastInsertId',
                    'setFetchMode',
                    'limit',
                    'supportsParameters',
                    'isConnected',
                    'getServerVersion',
                ]
            )
            ->setConstructorArgs([$config])
            ->getMock();

        $this->mockTableGateway = $this->getMockBuilder('Opus_Model_Dependent_AbstractTest_MockTableGateway')
            ->setMethods(['createRow'])
            ->setConstructorArgs([[Zend_Db_Table_Abstract::ADAPTER => $this->mockAdapter]])
            ->getMock();

        $this->mockTableRow = $this->getMockBuilder('Zend_Db_Table_Row')
            ->setMethods(['delete'])
            ->setConstructorArgs([['table' => $this->mockTableGateway]])
            ->getMock();

        $this->mockTableRow->expects($this->any())
            ->method('delete')
            ->will($this->returnValue(1));

        $this->mockTableGateway->expects($this->any())
            ->method('createRow')
            ->will($this->returnValue($this->mockTableRow));

        $this->cut = $this->getMockBuilder(AbstractDependentModel::class)
            ->setMethods(['init', 'getId'])
            ->setConstructorArgs([null, $this->mockTableGateway])
            ->getMock();

        $this->cut->expects($this->any())->method('getId')->will($this->returnValue(4711));
        // unregister plugin to avoid side effects using mock object
        // plugin relies on table gateway class which is not available
        try {
            $this->cut->unregisterPlugin(InvalidateDocumentCache::class);
        } catch (ModelException $ome) {
        }
    }

    /**
     * Overwrite parent methods.
     */
    public function tearDown()
    {
    }

    /**
     * Test if no row is actually deleted on delete() call.
     */
    public function testDeleteCallDoesNotDeleteRow()
    {
        $this->mockTableRow->expects($this->never())->method('delete');
        $this->cut->delete();
    }

    /**
     * Test if delete() returns a deletion token.
     */
    public function testDeleteCallReturnsToken()
    {
        $token = $this->cut->delete();
        $this->assertNotNull($token, 'No deletion token returned.');
    }

    /**
     * Test if doDelete() rejects invalid deletion token.
     */
    public function testInvalidDeletionTokenThrowsException()
    {
        $this->expectException(ModelException::class);
        $this->cut->delete();
        $this->cut->doDelete('foo');
    }

    /**
     * Test if doDelete() throws Exception if no deletion token has been required.
     */
    public function testMissingDeletionTokenThrowsException()
    {
        $this->expectException(ModelException::class);
        $this->cut->doDelete(null);
    }

    /**
     * Test if doDelete() accepts a valid deletion token.
     */
    public function testDoDeleteAcceptsValidDeletionToken()
    {
        try {
            $token = $this->cut->delete();
            $this->cut->doDelete($token);
        } catch (ModelException $ex) {
            $this->fail('Valid deletion token rejected with Exception: ' . $ex->getMessage());
        }
    }

    /**
     * Test if call to doDelete() with valid token deletes the actual row.
     */
    public function testDoDeleteRemovesParentRow()
    {
        $this->mockTableRow->expects($this->once())->method('delete');
        $token = $this->cut->delete();
        $this->cut->doDelete($token);
    }

    /**
     * Regression Test for OPUSVIER-1687
     * make sure cache invalidation is enabled when document caching enabled
     */
    public function testInvalidateDocumentCacheEnabled()
    {
        $document = new Document();

        $cachingEnabled = $document->hasPlugin(Document\Plugin\XmlCache::class);

        if ($cachingEnabled) {
            $subject = new Subject(); // inherits from Opus\Model\Dependent\AbstractDependentModel

            $this->assertTrue($subject->hasPlugin(InvalidateDocumentCache::class));
        }
    }
}
