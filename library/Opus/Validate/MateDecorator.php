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
 * @package     Opus_Validate
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id: DocumentType.php 714 2008-09-12 13:15:39Z claussnitzer $
 */

/**
 * Defines an extension for validators so that the interface and functionality
 * of Opus_Validate_AbstractMate is provided.
 *
 * @see Opus_Validate_AbstractMate
 * @category    Framework
 * @package     Opus_Validate
 */
class Opus_Validate_MateDecorator extends Opus_Validate_AbstractMate {
    
    /**
     * Validator object that is decorated.
     *
     * @var Zend_Validate_Interface
     */
    protected $_decorated = null;
    
    /**
     * Create decoration for given validator.
     *
     * @param Zend_Validate_Interface $validator Validator to be decorated.
     */
    public function __construct(Zend_Validate_Interface $validator) {
        $this->_decorated = $validator;
    }

    /**
     * Create and return a decorated validator.
     *
     * @param Zend_Validate_Interface $validator Validator to be decorated.
     * @return Opus_Validate_MateDecorator Decorater instance.
     */
    public static function decorate(Zend_Validate_Interface $validator) {
        return new Opus_Validate_MateDecorator($validator);
    }
    
    /**
     * Call the decorated validator. This method is called by Opus_Validate_AbstractMate::isValid(). 
     *
     * @param mixed $value Value to validate.
     * @return boolean Whatever the decorated validators isValid() method returns.
     */
    protected function _isValid($value) {
        return $this->_decorated->isValid($value);
    }
    
}