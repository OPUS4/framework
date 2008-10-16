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
 * @version     $Id$
 */

/**
 * Defines an validator for combining validators to be used with
 * complex typed field data.
 *
 * @category    Framework
 * @package     Opus_Validate
 */
class Opus_Validate_ComplexType extends Zend_Validate_Abstract {


    /**
     * Error message key for invalid check digit.
     *
     */
    const MSG_INVALID = 'invalid';

    /**
     * Error message templates.
     *
     * @var array
     */
    protected $_messageTemplates = array(
        self::MSG_INVALID => 'At least one sub field of the complex field is invalid',
    );
    
    
    /**
     * Hold field type descriptions corresponding validator instances.
     *
     * @var array
     */
    private $__fielddescription = array();

    /**
     * Construct the complex type validator using the given
     * field description. For example, such a description may have the form:
     *
     * E.g. array('value'     => array('type' => self::DT_TEXT),
     *            'language'  => array('type' => self::DT_LANGUAGE))
     *
     * Those descriptions can be obtained by the getAvailableFields()
     * method of Opus_Document_Type.
     *
     * @param string $fielddesciption Description of a field type.
     * @throws InvalidArgumentException If the field type definition is malformed.
     */
    public function __construct($fielddesciption) {
        if (empty($fielddesciption) === true) {
            throw new InvalidArgumentException('No field type definition given.');
        }
        // Copy by access to check the input parameter on thy fly.
        try {
            foreach ($fielddesciption as $name => $desc) {
                if (is_string($name) === false) {
                    throw new InvalidArgumentException('Invalid field name: ' . $name);
                }
                if (is_array($desc) === false) {
                    throw new InvalidArgumentException('Type description is not in form of an array');
                }
                // Cache validators for later use.
                $validator = Opus_Document_Type::getValidatorFor($desc['type']);
                $this->__fielddescription[$name] = array('validator' => $validator);
            }
        } catch (Exception $ex) {
            throw new InvalidArgumentException('Field type description could not be parsed: '
            . $ex->getMessage());
        }
    }

    /**
     * Validate the given complex field information. Note that the validator
     * can only check values that are present. There is no checking for missing
     * mandatory fields whatsoever.
     *
     * @param mixed $value Data of complex field type.
     * @return boolean True if the all values are valid.
     */
    public function isValid($value)
    {
        $this->_setValue($value);

        $result = true;
        foreach ($this->__fielddescription as $name => $fdesc) {
            // Look for present values by checking every known fieldname. 
            if (array_key_exists($name, $value) === true) {
                $validator = $fdesc['validator'];
                if (is_null($validator) === false) {
                    $result = ($result and $validator->isValid($value[$name]));
                }
            } 
        }
        if ($result === false) {
            $this->_error(self::MSG_INVALID);
        }
        return $result;
    }


}