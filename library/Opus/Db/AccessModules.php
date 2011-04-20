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
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2011, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Table gateway class to table 'access_moduules'.
 *
 * @category    Framework
 * @package     Opus_Db
 *
 */
class Opus_Db_AccessModules extends Opus_Db_TableGateway {

    /**
     * Table schema name.
     *
     * @var string
     */
    protected $_name = 'access_modules';

    /**
     * For a given role-id, return a hash of module-access-rights.
     *
     * @param  int $role_id
     * @return array
     */
    public function listByRoleId($role_id) {
        $adapter = $this->getAdapter();
        $select = $adapter->select()
                        ->from($this->_name, array('module_name', 'controller_name'))
                        ->where('role_id = ?', $role_id)
                        ->order('module_name');

        $returnHash = $this->groupKeyValue(
                        $adapter->fetchAll($select),
                        'module_name',
                        'controller_name'
        );

        return $returnHash;
    }

    /**
     * Given a database result from fetchAll, groups the results by key-names.
     * The grouped values will be array to an associative array of the
     * following form:
     * 
     * array(
     *   "key" => array(grouped values),
     * );
     *
     * @param array $array
     * @param string $key_name
     * @param string $value_name
     * @return array
     */
    private static function groupKeyValue($array, $key_name, $value_name) {
        $returnHash = array();

        foreach ($array AS $row) {
            $key   = $row[$key_name];
            $value = $row[$value_name];

            if (!array_key_exists($key, $returnHash)) {
                $returnHash[$key] = array();
            }

            $returnHash[$key][] = $value;
        }

        return $returnHash;
    }

}
