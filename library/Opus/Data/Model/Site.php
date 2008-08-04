<?php
/**
 * Defines a model for manipulating Site entities.
 *
 * This file is part of OPUS. The software OPUS has been developed at the
 * University of Stuttgart with funding from the German Research Net
 * (Deutsches Forschungsnetz), the Federal Department of Higher Education and
 * Research (Bundesministerium fuer Bildung und Forschung) and The Ministry of
 * Science, Research and the Arts of the State of Baden-Wuerttemberg
 * (Ministerium fuer Wissenschaft, Forschung und Kunst des Landes
 * Baden-Wuerttemberg).
 *
 * PHP versions 4 and 5
 *
 * OPUS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * OPUS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation,
 * Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * @package     Opus_Application_Framework
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Universitaetsbibliothek Stuttgart, 1998-2008
 * @license     http://www.gnu.org/licenses/gpl.html
 * @version     $Id$
 */

/**
 * Provides accessor functions and business logic related to Sites.
 *
 * @package     Opus_Application_Framework
 * @subpackage 	Data_Model
 *
 */
class Opus_Data_Model_Site {


    /**
     * Holds identifier key if the entity has been succesfully persisted
     * to a datastore.
     *
     * @var mixed
     */
    protected $_id = null;

    /**
     * Holds short name of the Site.
     *
     * @var string
     */
    public $name = '';

    /**
     * Holds full name of the Site.
     *
     * @var string
     */
    public $fullName = '';


    /**
     * Return the datastore identifier.
     *
     * @return mixed
     */
    public function getId() {
        return $this->_id;
    }

    /**
     * Return all stored Site entities as an array with the Site's
     * short name as key.
     *
     * @return array Array of Opus_Data_Model_Site objects.
     */
    public static function getAll() {
        $sites = new Opus_Data_Db_Sites();
        $rowset = $sites->fetchAll();
        $result = array();
        foreach ($rowset as $row) {
            $site = new Opus_Data_Model_Site();
            $site->_id = $row->site_id;
            $site->name = $row->name;
            $site->fullName = $row->full_name;
            $result[$site->name] = $site;
        }
        return $result;
    }


	/**
	 * Return an specific instance of Site for a given identifier.
	 *
	 * @param mixed $id Identifier of Site entity.
	 * @return Opus_Data_Model_Site Returns an instance if a Site entity with the passed identifier
	 *     is existent in the database, otherwise null.
	 */
	public static function get($id) {
	    $sites = new Opus_Data_Db_Sites();
	    $row = $sites->find($id)->getRow(0);
	    if ( is_null($row) === false ) {
	        $site = new Opus_Data_Model_Site();
	        $site->_id = $row->site_id;
	        $site->name = $row->name;
	        $site->fullName = $row->full_name;
	        return $site;
	    }
	    return null;
	}

	/**
	 * Persist entity information previosly set via attributes or by
	 * retrieving an Site entity from storage.
	 *
	 * @return mixed Identifier of the saved entity.
	 */
    public function save() {
        $sites = new Opus_Data_Db_Sites();
        if ( is_null($this->_id) === true ) {
            $row = $sites->createRow();
        } else {
            $row = $sites->find($this->_id)->getRow(0);
        }
        $row->name = $this->name;
        $row->full_name = $this->fullName;
        $row->save();

        if ( is_null($this->_id) === true ) {
            $this->_id = $sites->getAdapter()->lastInsertId();
        }
        return $this->_id;
    }

}