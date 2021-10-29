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
 * @copyright   Copyright (c) 2008-2020, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 *
 * @category    Tests
 * @package     Opus\Model
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 */

namespace OpusTest\Model\Mock;

use Opus\Model\AbstractDb;
use Opus\Model\Field;

/**
 * This class extends Opus\Model\AbstractModel to be able to test its code.
 * Opus\Model\AbstractModel is an abstract class.
 * This mock is needed to be able to instantiate Opus\Model\AbstractModel.
 *
 * @category Tests
 * @package Opus\Model
 */
class ModelDefiningExternalField extends AbstractDb
{
    /**
     * Array of field names for wich _loadExternal has been called.
     *
     * @var array Array of field names.
     */
    public $loadExternalHasBeenCalledOn = [];

    /**
     * Specify then table gateway.
     *
     * @var string Classname of\Zend_DB_Table to use if not set in constructor.
     */
    protected static $tableGatewayClass = AbstractTableProvider::class;

    /**
     * Provide a mockup external fields declaration.
     *
     * @var array
     */
    protected $externalFields = [
        'ExternalModel'     => [
            'model'   => ModelAbstractDbMock::class,
            'through' => LinkToAbstractMock::class,
            'options' => '',
            'fetch'   => 'eager',
        ],
        'LazyExternalModel' => [
            'model'   => 'Opus\Model\Mock\AbstractModelMock',
            'through' => '',
            'options' => '',
            'fetch'   => 'lazy',
        ],
    ];

    /**
     * Initialize model with the a single field "ExternalModel".
     */
    protected function init()
    {
        $this->addField(new Field('ExternalModel'));
        $this->addField(new Field('LazyExternalModel'));
    }

    /**
     * Mock up function to detect calls to loadExternal.
     *
     * @see    \Opus\Model\AbstractModel#_loadExternal()
     *
     * @param string $fieldname A fieldname.
     */
    protected function _loadExternal($fieldname)
    {
        $this->loadExternalHasBeenCalledOn[] = $fieldname;
    }
}
