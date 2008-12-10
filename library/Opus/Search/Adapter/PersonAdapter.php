<?php
/**
 * Adapter to use the Persons from the framework in Module_Search
 * 
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
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

class Opus_Search_Adapter_PersonAdapter extends Opus_Model_Person
{
	/**
	 * Attribute to store the Person as an Array
	 * 
	 * @var array Data from the person
	 * @access private
	 */
	private $personData;
	
  /**
   * Constructor
   * 
   * @param [integer|array|Opus_Search_Adapter_PersonAdapter] $person (Optional) Data for the new OpusPersonAdapter-Object 
   */
	public function __construct($person = null)
	{
  		if (is_int($person) === true) {
  			$this->personData['id'] = $person;
  			$this->mapPerson();
  		} else if (is_array($person) === true) {
  			$this->personData = $person;
  		} else if (get_class($person) === 'Opus_Search_Adapter_PersonAdapter') {
  			$this->personData = $person->get();
  		}
	}
	
  /**
   * Returns the person data as an array
   * 
   * @return array Array with person data usable in Module_Search 
   */
	public function get()
	{
		return $this->personData;
	}
	
  /**
   * Get a person by its ID
   * 
   * @param integer $id ID of the person
   * @return Opus_Search_Adapter_PersonAdapter OpusPersonAdapter of the person with the given ID, if this ID does not exists, null will be returned
   */
	public static function getDummyPerson($id)
	{
		$data = DummyData::getDummyPersons();
		foreach ($data as $obj) {
			$d = $obj->get();
			if ($d['id'] === $id) {
				return $obj;
			}
		}
		return null;
	}

  /**
   * Maps a person from Opus_Model_Person to OpusPersonAdapter by its ID
   * 
   * @return void
   */
	private function mapPerson()
	{
		parent::__construct($this->personData['id']);
		$this->personData['lastName'] = $this->getLastName();
		$this->personData['firstName'] = $this->getFirstName();
	}
}
