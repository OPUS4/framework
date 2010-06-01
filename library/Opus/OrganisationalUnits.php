<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @package     Opus
 * @author     	Thoralf Klein <thoralf.klein@zib.de>
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
 * @copyright   Copyright (c) 2010, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Extends generic collection role to act as organisation unit container.
 *
 */
class Opus_OrganisationalUnits extends Opus_CollectionRole {

    /**
     * The documents external fields, i.e. those not mapped directly to the
     * Opus_Db_Documents table gateway.
     *
     * @var array
     * @see Opus_Model_Abstract::$_externalFields
     * @see Opus_CollectionRole::$_externalFields
     */
//  protected $_externalFields = array(
//            'CollectionsContentSchema' => array(),
//            'SubCollection' => array(
//                'fetch' => 'lazy',
//                'model' => 'Opus_OrganisationalUnit'
//            ),
//  );

    /**
     * Overwrite constructor to set fixed id.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct(1);
    }

    /**
     * Overwrite delete to prevent deletion of organisational unit role.
     *
     * @return void
     */
    public function delete() {
        throw new Opus_Model_Exception('Cannot delete institute role.');
    }

    /**
     * Returns a list of organisational units that act as (thesis) grantors.
     *
     * @return array A list of Opus_OrganisationalUnit that act as grantors.
     *
     * TODO: Cache Grantors?
     */
    public static function getGrantors() {
        $table = new Opus_Db_Collections();
        $select = $table->select()
                ->where('is_grantor = ?', 1)
                ->where('role_id = 1');

        $rows = $table->fetchAll($select);
        $result = array();
        foreach ($rows as $row) {
            $result[] = new Opus_Collection($row);
        }
        return $result;
    }

    /**
     * Returns a list of organisational units that act as (thesis) publishers.
     *
     * @return array A list of Opus_OrganisationalUnit that act as publishers.
     *
     * TODO: Cache Publishers?
     */
    public static function getPublishers() {
        $table = new Opus_Db_Collections();
        $select = $table->select()
                ->where('dnb_contact_id != ?', '')
                ->where('role_id = 1');

        $rows = $table->fetchAll($select);
        $result = array();
        foreach ($rows as $row) {
            $result[] = new Opus_Collection($row);
        }
        return $result;
    }

}
