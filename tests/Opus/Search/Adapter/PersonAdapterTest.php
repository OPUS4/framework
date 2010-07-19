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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Thoralf Klein <thoralf.klein@zib.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Test case for Opus_Search_Adapter_PersonAdapter.
 *
 * @category    Tests
 * @package     Opus_Search
 *
 * @group       PersonAdapter
 */
class Opus_Search_Adapter_PersonAdapterTest extends TestCase {


    /**
     * Test fixture Opus_Person object.
     *
     * @var Opus_Person
     */
    protected $_personModel = null;

    /**
     * Set up a test fixture person model.
     *
     * @return void
     */
    public function setUp() {
        parent::setUp();

        // Persist a person model
        $this->_personModel = new Opus_Person();
        $this->_personModel->setFirstName('Gybrush');
        $this->_personModel->setLastName('Threepwood');
        $pid = $this->_personModel->store();
    }

    /**
     * Test if a person adapter gets initialized correctly when
     * passing a valid person id.
     *
     * @return void
     */
    public function testInitializeFromId() {
        $id = $this->_personModel->getId();
        $adapter = new Opus_Search_Adapter_PersonAdapter($id);
        $result = $adapter->get();

        $this->assertEquals($id, $result['id'], 'Identifier not correct.');
        $this->assertEquals($this->_personModel->getFirstName(), $result['firstName'], 'Attribute "firstName" not correct.');
        $this->assertEquals($this->_personModel->getLastName(), $result['lastName'], 'Attribute "lastName" not correct.');
    }

    /**
     * Test if a person adapter gets initialized correctly when
     * passing a valid person data array.
     *
     * @return void
     */
    public function testInitializeFromValidArray() {
        $valid = array(
            'id' => 1,
            'firstName' => 'Barack',
            'lastName'  => 'Obama');

        $adapter = new Opus_Search_Adapter_PersonAdapter($valid);
        $result = $adapter->get();
        $this->assertEquals($valid, $result, 'Person data returned is not as expected.');
    }

    /**
     * Test if a person adapter gets initialized correctly when
     * passing a valid person data array.
     *
     * @return void
     */
    public function testInitializeFromInvalidArrayThrowsException() {
        $invalid = array(
            'personNumber' => 4711,
            'firstNameOfTheFormerPresidentOfTheUnitedStates' => 'George Walker',
            'lastNameOfTheFormerPresidentOfTheUnitedStates'  => 'Bush');

        $this->setExpectedException('InvalidArgumentException');
        $adapter = new Opus_Search_Adapter_PersonAdapter($invalid);
    }

    /**
     * Test initializing person adapter using another adapter instance.
     *
     * @return void
     */
    public function testInitializeFromAdapterInstance() {
        $valid = array(
            'id' => 1,
            'firstName' => 'Barack',
            'lastName'  => 'Obama');

        $adapter1 = new Opus_Search_Adapter_PersonAdapter($valid);
        $adapter2 = new Opus_Search_Adapter_PersonAdapter($adapter1);
        $this->assertEquals($adapter1->get(), $adapter2->get(), 'Adapters do not hold the same data.');
    }

    /**
     * Test initializing person adapter using an Opus_Person instance.
     *
     * @return void
     */
    public function testInitializeFromModelInstance() {
        $adapter = new Opus_Search_Adapter_PersonAdapter($this->_personModel);

        $valid = array(
            'id' => $this->_personModel->getId(),
            'firstName' => $this->_personModel->getFirstName(),
            'lastName'  => $this->_personModel->getLastName());

        $this->assertEquals($valid, $adapter->get(), 'Adapter has not been initialized correctly.');
    }

    /**
     * Test initializing person adapter using an unpersistet Opus_Person instance
     * throws exception.
     *
     * @return void
     */
    public function testInitializeFromUnpersistetModelInstanceThrowsException() {
        $model = new Opus_Person();
        $model->setFirstName('Harry');
        $model->setLastName('Potter');

        $this->setExpectedException('Opus_Search_Adapter_Exception');
        $adapter = new Opus_Search_Adapter_PersonAdapter($model);
    }


}