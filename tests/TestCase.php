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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008-2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Superclass for all tests.  Providing maintainance tasks.
 *
 * @category Tests
 */
class TestCase extends PHPUnit_Framework_TestCase {

    /**
     * Empty all listed tables.
     *
     * @return void
     */
    private function _clearTables() {
        // This is needed to workaround the constraints on the parent_id column.
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $this->assertNotNull($adapter);
        $adapter->query('UPDATE collections SET parent_id = null ORDER BY left_id DESC');

        foreach ($adapter->listTables() as $tableName) {
            self::clearTable($tableName);
        }
    }

    /**
     * Use the standard database adapter to remove all records from
     * a table.  Check, if the table is really empty.
     *
     * @param string $tablename Name of the table to be cleared.
     * @return void
     */
    protected function clearTable($tablename) {
        $adapter = Zend_Db_Table::getDefaultAdapter();
        $this->assertNotNull($adapter);

        $tablename = $adapter->quoteIdentifier($tablename);
        $adapter->query('TRUNCATE ' . $tablename);

        $count = $adapter->fetchOne('SELECT COUNT(*) FROM ' . $tablename);
        $this->assertEquals(0, $count, "Table $tablename is not empty!");
    }

    /**
     * Standard setUp method for clearing database.
     *
     * @return void
     */
    protected function setUp() {
        parent::setUp();
        self::_clearTables();
    }

}
