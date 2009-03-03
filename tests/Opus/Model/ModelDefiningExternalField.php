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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * This class extends Opus_Model_Abstract to be able to test its code.
 * Opus_Model_Abstract is an abstract class.
 * This mock is needed to be able to instantiate Opus_Model_Abstract.
 *
 * @category Tests
 * @package Opus_Model
 *
 */
class Opus_Model_ModelDefiningExternalField extends Opus_Model_AbstractDb {

    /**
     * Array of field names for wich _loadExternal has been called.
     *
     * @var array Array of field names.
     */
    public $loadExternalHasBeenCalledOn = array();


    /**
     * Specify then table gateway.
     *
     * @var string Classname of Zend_DB_Table to use if not set in constructor.
     */
    protected static $_tableGatewayClass = 'Opus_Model_AbstractTableProvider';

    /**
     * Provide a mockup external fields declaration.
     *
     * @var array
     */
    protected $_externalFields = array(
        'ExternalModel' => array(
            'model' => 'Opus_Model_ModelAbstract',
            'through' => 'Opus_Model_LinkToAbstractMock',
            'options' => ''),
        'LazyExternalModel' => array(
            'model' => 'Opus_Model_ModelAbstract',
            'through' => '',
            'options' => '',
            'fetch' => 'lazy')
    );

    /**
     * Initialize model with the a single field "ExternalModel".
     *
     * @return void
     */
    protected function _init() {
        $this->addField(new Opus_Model_Field('ExternalModel'));
        $this->addField(new Opus_Model_Field('LazyExternalModel'));
    }

    /**
     * Mock up function to detect calls to loadExternal.
     *
     * @param string $fieldname A fieldname.
     * @see    library/Opus/Model/Opus_Model_Abstract#_loadExternal()
     * @return void
     */
    protected function _loadExternal($fieldname) {
        $this->loadExternalHasBeenCalledOn[] = $fieldname;
    }
}
