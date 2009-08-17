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
 * @package     Opus_Search
 * @author      Oliver Marahrens <o.marahrens@tu-harburg.de>
 * @author      Ralf Claußnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Adapter for Opus_Person to query person information for index generation
 * and hitlist. This adapter class is used in all index based search components to
 * protect date retrieval from Opus_Model specifics.
 *
 * @category    Framework
 * @package     Opus_Search
 */
class Opus_Search_Adapter_PersonAdapter
{
	/**
	 * Attribute to store the Person as an Array
	 *
	 * @var array Data from the person
	 * @access private
	 */
	private $personData;

  /**
   * Initialize the adapter with requested person data.
   *
   * @param integer|array|Opus_Search_Adapter_PersonAdapter $person (Optional) Either an id of a person, an array of adapter data or another Opus_Search_Adapter_PersonAdapter instance.   *
   */
	public function __construct($person = null)
	{
  		if (is_numeric($person) === true) {
  		    $model = new Opus_Person((int) $person);
  		    $this->personData['id'] = (int) $person;
            $this->personData['lastName'] = $model->getLastName();
            $this->personData['firstName'] = $model->getFirstName();
  		} else if (is_array($person) === true) {
  		    try {
            $this->personData['id'] = (int) $person['id'];
            $this->personData['lastName'] = $person['lastName'];
            $this->personData['firstName'] = $person['firstName'];
  		    } catch (Exception $ex) {
  		        throw new InvalidArgumentException('Given person data array is malformed.');
  		    }
  		} else if ($person instanceof Opus_Search_Adapter_PersonAdapter) {
  			$this->personData = $person->get();
  		} else if ($person instanceof Opus_Person) {
            $this->personData['id'] = $person->getId();
            if (is_null($this->personData['id']) === true) {
                throw new Opus_Search_Adapter_Exception('Given Opus_Person instance has not been persistet yet.');
            }
            $this->personData['lastName'] = $person->getLastName();
            $this->personData['firstName'] = $person->getFirstName();
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
     * Compare method to sort persons list (descending)
     */
    static function cmp_person_desc($a, $b)
    {
    	$a1 = $a->get();
    	$aName = $a1['lastName'];
        $b1 = $b->get();
        $bName = $b1['lastName'];
        if ($aName == $bName) {
            return 0;
        }
        return ($aName < $bName) ? +1 : -1;
    }

    /**
     * Compare method to sort persons list (ascending)
     */
    static function cmp_person($a, $b)
    {
    	$a1 = $a->get();
    	$aName = $a1['lastName'];
        $b1 = $b->get();
        $bName = $b1['lastName'];
        if ($aName == $bName) {
            return 0;
        }
        return ($aName > $bName) ? +1 : -1;
    }

}
