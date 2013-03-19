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
 * @package     Opus_Model
 * @author      Pascal-Nicolas Becker <becker@zib.de>
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for class Opus_Model_Dependent_Abstract.
 *
 * @package Opus_Model
 * @category Tests
 *
 * @group DependentAbstractTest
 */
class Opus_Model_Dependent_AbstractTest extends TestCase {

    /**
     * Class instance under test.
     *
     * @var Opus_Model_Dependent_Abstract
     */
    private $_cut = null;

    /**
     * Zend_Db_Table mockup.
     *
     * @var Zend_Db_Table
     */
    private $_mockTableGateway = null;


    /**
     * Zend_Db_Table_Row mockup
     *
     * @var Zend_Db_Table_Row
     */
    private $_mockTableRow = null;
    
    
    /**
     * Zend_Db_Adapter mockup
     *
     * @var Zend_Db_Adapter
     */
    private $_mockAdapter = null; 
    
    /**
     * Set up test instance and mock environment.
     *
     * @return void
     */
    public function setUp() {
        if (false === class_exists('Opus_Model_Dependent_AbstractTest_MockTableGateway', false)) {
            eval('
                class Opus_Model_Dependent_AbstractTest_MockTableGateway
                extends Zend_Db_Table {
                    protected function _setup() {}
                    protected function _init() {}

                    // Method/array copy-pasted from Zend_Db_Table_Abstract
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

        $config = array('dbname' => 'exampledb', 'password' => 'nopass', 'username' => 'nouser');

        $this->_mockAdapter = $this->getMock('Zend_Db_Adapter_Abstract',
            array('_connect', '_beginTransaction', '_commit', '_rollback',
                'listTables', 'describeTable', 'closeConnection', 'prepare', 'lastInsertId', 
                'setFetchMode', 'limit', 'supportsParameters', 'isConnected', 'getServerVersion'),
            array($config));

        $this->_mockTableGateway = $this->getMock('Opus_Model_Dependent_AbstractTest_MockTableGateway',
            array('createRow'), array(array(Zend_Db_Table_Abstract::ADAPTER => $this->_mockAdapter)));

        $this->_mockTableRow = $this->getMock('Zend_Db_Table_Row', 
            array('delete'), 
            array(array('table' => $this->_mockTableGateway)));
        $this->_mockTableRow->expects($this->any())
            ->method('delete')
            ->will($this->returnValue(1));
        
        $this->_mockTableGateway->expects($this->any())
            ->method('createRow')
            ->will($this->returnValue($this->_mockTableRow));
            
        $this->_cut = $this->getMock('Opus_Model_Dependent_Abstract', 
            array('_init', 'getId'), array(null, $this->_mockTableGateway));
        $this->_cut->expects($this->any())->method('getId')->will($this->returnValue(4711));
    }

    /**
     * Overwrite parent methods.
     */
    public function tearDown() {}

    /**
     * Test if no row is actually deleted on delete() call.
     *
     * @return void
     */
    public function testDeleteCallDoesNotDeleteRow() {
        $this->_mockTableRow->expects($this->never())->method('delete');
        $this->_cut->delete();
    }
    
    /**
     * Test if delete() returns a deletion token.
     *
     * @return void
     */
    public function testDeleteCallReturnsToken() {
        $token = $this->_cut->delete();
        $this->assertNotNull($token, 'No deletion token returned.');
    }
    
    /**
     * Test if doDelete() rejects invalid deletion token.
     *
     * @return void
     */   
    public function testInvalidDeletionTokenThrowsException() {
        $this->setExpectedException('Opus_Model_Exception');
        $this->_cut->delete();
        $this->_cut->doDelete('foo');
    }

    /**
     * Test if doDelete() throws Exception if no deletion token has been required.
     *
     * @return void
     */   
    public function testMissingDeletionTokenThrowsException() {
        $this->setExpectedException('Opus_Model_Exception');
        $this->_cut->doDelete(null);
    }

    
    /**
     * Test if doDelete() accepts a valid deletion token.
     *
     * @return void
     */
    public function testDoDeleteAcceptsValidDeletionToken() {
        try {
            $token = $this->_cut->delete();
            $this->_cut->doDelete($token);
        } catch (Opus_Model_Exception $ex) {
            $this->fail('Valid deletion token rejected.');
        }
    }   
    
    /**
     * Test if call to doDelete() with valid token deletes the actual row.
     *
     * @return void
     */
    public function testDoDeleteRemovesParentRow() {
        $this->_mockTableRow->expects($this->once())->method('delete');
        $token = $this->_cut->delete();
        $this->_cut->doDelete($token);
    }
    
    /**
     * Regression Test for OPUSVIER-1687
     * make sure cache invalidation is enabled when document caching enabled
     */
    public function testInvalidateDocumentCacheEnabled() {
        
        $reflectedClass = new ReflectionClass('Opus_Document');
        $property = $reflectedClass->getProperty('_plugins');
        $props = $reflectedClass->getDefaultProperties();
        $docPlugins = @$props['_plugins'] ?: array() ;
        if(array_key_exists('Opus_Document_Plugin_XmlCache', $docPlugins)) {
            $reflectedClass = new ReflectionClass('Opus_Model_Dependent_Abstract');
            $props = $reflectedClass->getDefaultProperties();
            $modelPlugins = @$props['_plugins'] ?: array() ;
            $this->assertTrue(array_key_exists('Opus_Model_Plugin_InvalidateDocumentCache', $modelPlugins), 'Expected plugin Opus_Model_Plugin_InvalidateDocumentCache');
        }

    }

      
}
