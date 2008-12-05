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
 * @category    Framework
 * @package     Opus_Db
 * @author      Ralf Claussnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Interface for schema modifying database adapters.
 *
 * @category    Framework
 * @package     Opus_Db
 */
interface Opus_Db_Adapter_AlterSchemaInterface {

    /**
     * Checks for a valid table and optionally field name.
     * Returns false on invalid names or nonexisting tables / fields.
     *
     * @param string $tablename Contains table name
     * @param string $fieldname (Optional) Contains field name
     * @throws Exception Exception on empty table
     * @return boolean
     */
    public function isExistent($tablename, $fieldname = null);
    
    /**
     * Create a table with the table name with _id added as primary key.
     *
     * @param string $name Contains the name for table and primary key
     * @throws Exception Exception at invalid name or already existing table
     * @return boolean true on success
     */
    public function createTable($name);
        
        
    /**
     * Delete a table. Tableprefix is added automaticly
     *
     * @param string $name Contains the table name fro dropping
     * @throws Exception Exception on non valid name or non-existing table
     * @return bool true on success
     */
    public function deleteTable($name);

}