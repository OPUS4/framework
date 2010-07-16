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
 * @package     Opus_Db
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test cases for Site entity.
 *
 * @category    Tests
 * @package     Opus_Db
 *
 * @group       Mysqlutf8Test
 */
class Opus_Db_Adapter_Pdo_Mysqlutf8Test extends PHPUnit_Framework_TestCase {

    /** Ensure a clean database table.
     *
     * @return void
     */
    public function setUp() {
        // Clean setup of default database adapter
        $config = Zend_Registry::get('Zend_Config');
        // Use zend_Db factory to create a database adapter
        // and make it the default for all tables.
        $db = Zend_Db::factory($config->db);
        Zend_Db_Table::setDefaultAdapter($db);

        // Register the adapter within Zend_Registry.
        Zend_Registry::getInstance()->set('db_adapter', $db);
            
        // drop helper table
        TestHelper::dropTable('test_timmy');

        // Set up table prefix for subsequent tests
        $dba = Zend_Db_Table::getDefaultAdapter();
    }
    
    /**
     * Tear down database changed.
     *
     * @return void
     */
    public function tearDown() {
        // drop helper table
        TestHelper::dropTable('test_timmy');
    }

    /**
     * Test if starting nested transactions gets handeld by the adapter.
     *
     * @return void
     */
    public function testStartNestingTransactions() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->beginTransaction();
        try {
            $dba->beginTransaction();
        } catch (Exception $ex) {
            $this->fail('Failed start of nested transaction.');
        }
    }
    
    /**
     * Test if all opened transactions can be committed.
     *
     * @return void
     */
    public function testCommitNestedTransactions() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->beginTransaction();
        $dba->beginTransaction();
        $dba->beginTransaction();
        
        $dba->commit();
        $dba->commit();
        $dba->commit();

        try {
            $dba->commit();
        } catch (Exception $ex) {
            return;
        }
        $this->fail('Commit without transaction goes ok.');
    }   


    /**
     * Test if all opened transactions can be ended by rollback.
     *
     * FIXME: design fault: on rollback should abort all enclosing transactions!
     *
     * @return void
     */
    public function testRollbackNestedTransactions() {
        $dba = Zend_Db_Table::getDefaultAdapter();
        $dba->beginTransaction();
        $dba->beginTransaction();
        $dba->beginTransaction();
        
        $dba->rollback();
        $dba->rollback();
        $dba->rollback();

        try {
            $dba->rollback();
        } catch (Exception $ex) {
            return;
        }
        $this->fail('Rollback without transaction goes ok.');
    }   

    
}
