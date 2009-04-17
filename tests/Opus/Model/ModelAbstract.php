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
 * @author      Pascal-Nicolas Becker <becker@zib.de>
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
 */
class Opus_Model_ModelAbstract extends Opus_Model_Abstract {

    /**
     * Variable holds constructor parameter.
     *
     * @var mixed
     */
    public $cons;

    /**
     * Mockup constructor code to test parameter passing.
     *
     * @param mixed $cons (Optional) Value to be passed.
     */
    public function __construct($cons = null) {
        parent::__construct();
        $this->cons = $cons;
    }

    /**
     * Initialize model with the a single field "value".
     *
     * @return void
     */
    protected function _init() {
        $this->_validatorPrefix[] = 'Opus_Model_ValidateTest';
        $this->_filterPrefix[] = 'Opus_Model_FilterTest';

        $value = new Opus_Model_Field('Value');
        $this->addField($value);
    }
}
