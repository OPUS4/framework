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
 * @package     Opus_Model
 * @author      Felix Ostrowski (ostrowski@hbz-nrw.de)
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Domain model for fields in the Opus framework
 *
 * @category Framework
 * @package  Opus_Model
 */
class Opus_Model_Field
{

    /**
     * Datatype for textfields.
     *
     */
    const DT_TEXT           = 1;

    /**
     * Datatype for textfields.
     *
     */
    const DT_TEXTAREA       = 3;

    /**
     * Datatype for numeric values.
     *
     */
    const DT_NUMBER         = 10;
    /**
     * Datatype for date values.
     *
     */
    const DT_DATE           = 20;
    /**
     * Datatype for language specification.
     *
     */
    const DT_LANGUAGE       = 30;
    /**
     * Datatype for ISBN-10 identifier.
     *
     */
    const DT_ISBN_10        = 35;
    /**
     * Datatype for ISBN-13 identifier.
     *
     */
    const DT_ISBN_13        = 40;
    /**
     * Datatype for boolean values.
     *
     */
    const DT_BOOLEAN        = 50;
    /**
     * Datatype for document type enum.
     *
     */
    const DT_DOCUMENTTYPE   = 60;
    /**
     * Datatype for review type enum.
     *
     */
    const DT_REVIEWTYPE     = 70;
    /**
     * Datatype for document abstract.
     *
     */
    const DT_TITLE_ABSTRACT = 80;
    /**
     * Datatype for main document title.
     *
     */
    const DT_TITLE_MAIN     = 90;
    /**
     * Datatype for title of superordinate document, catalog or list etc.
     *
     */
    const DT_TITLE_PARENT   = 100;
    /**
     * Datatype for subjects following SWD standard.
     *
     */
    const DT_SUBJECT_SWD    = 110;
    /**
     * Datatype for subjects following DDC standard.
     *
     */
    const DT_SUBJECT_DDC    = 120;
    /**
     * Datatype for subjects following PSYNDEX standard.
     *
     */
    const DT_SUBJECT_PSYNDEX = 130;
    /**
     * Datatype for free form subjects.
     *
     */
    const DT_SUBJECT_UNCONTROLLED = 140;
    /**
     * Datatype for notes on documents.
     *
     */
    const DT_NOTE           = 150;
    /**
     * Datatype for publication scope of document notes.
     *
     */
    const DT_NOTESCOPE  = 160;

    /**
     * Datatype for referring to a person.
     *
     */
    const DT_PERSON     = 170;

    /**
     * Datatype for referring to an institute.
     *
     */
    const DT_INSTITUTE  = 180;

    /**
     * Datatype for referring to a collection.
     *
     */
    const DT_COLLECTION  = 190;

    /**
     * Hold validator.
     *
     * @var Zend_Validate_Interface
     */
    protected $_validator = null;

    /**
     * Hold value filter.
     *
     * @var Zend_Filter
     */
    protected $_filter = null;

    /**
     * Hold multiplicity constraint.
     *
     * @var Integer
     */
    protected $_multiplicity = 1;

    /**
     * Specifies datatype.
     *
     * @var Integer
     */
    protected $_type = self::DT_TEXT;

    /**
     * Specifiy whether the field is required or not.
     *
     * @var unknown_type
     */
    protected $_mandatory = false;

    /**
     * Specify whether a language can be choosen for the field. 
     *
     * @var Boolean
     */
    protected $_languageoption = false;
    
    
    /**
     * Holds the actual language for the field value.
     *
     * @var String
     */
    protected $_language = '';
    
    /**
     * Hold the fields value.
     *
     * @var Mixed
     */
    protected $_value = null;
    
    
    /**
     * Holds the fields default values. For selection list fields this should
     * contain the list of options.
     *
     * @var Mixed
     */
    protected $_default = null;
    
    
    /**
     * Internal name of the field.
     *
     * @var String
     */
    protected $_name = '';
    
    /**
     * Create an new field instance and set the given name.
     * 
     * Creating a new instance also sets some default values:
     * - type = DT_TEXT
     * - multiplicity = 1
     * - languageoption = false
     * - mandatory = false
     *
     * @param String $name Internal name of the field.
     */
    public function __construct($name) {
        $this->_name = $name;
    }
    
    /**
     * Get the internal name of the field.
     *
     * @return String Internal field name.
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Set a validator for the field.
     *
     * @param Zend_Validate_Interface $validator A validator.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setValidator(Zend_Validate_Interface $validator) {
        $this->_validator = $validator;
        return $this;
    }

    /**
     * Get the assigned validator for the field.
     *
     * @return Zend_Validate_Interface The fields validator if one is assigned.
     */
    public function getValidator() {
        return $this->_validator;
    }


    /**
     * Set a filter for the field.
     *
     * @param Zend_Filter $filter A filter.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setFilter(Zend_Filter $filter) {
        $this->_filter = $filter;
        return $this;
    }

    /**
     * Get the assigned filter for the field.
     *
     * @return Zend_Filter The fields filter if one is assigned.
     */
    public function getFilter() {
        return $this->_filter;
    }

    /**
     * Set multiplicity constraint for multivalue fields.  
     *
     * @param Integer $max Upper limit for multiple values.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setMultiplicity($max) {
        $this->_multiplicity = $max;
        return $this;
    }

    /**
     * Return the fields maximum number of values. 
     *
     * @return Integer Upper limit for multiple values.
     */
    public function getMultiplicity() {
        return $this->_multiplicity;
    }

    /**
     * Set the field type. Automatically sets the validator associated with the given type. 
     *
     * @param Integer $type Integer value describing the field type. Use DT_* constants of Opus_Model_Field.
     * @return Opus_Model_Field Provides fluent interface.
     */
    public function setType($type) {
        $this->_type = $type;
        $this->_validator = $this->getValidatorFor($type);
        return $this;
    }
    
    /**
     * Get the type of the field.
     *
     * @return Integer Number representing the fields type. Types are representet by DT_* constants of Opus_Model_Field.
     */
    public function getType() {
        return $this->_type;
    }
    

    /**
     * Set the mandatory flag for the field. This flag states out whether a field is required
     * to have a value or not.
     *
     * @param Boolean $mandatory Set to true if the field shall be a required field.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setMandatory($mandatory) {
        $this->_mandatory = $mandatory;
        return $this;
    }
    
    /**
     * Get the mandatory flag.
     *
     * @return Boolean True, if the field is marked tobe mandatory.
     */
    public function getMandatory() {
        return $this->_mandatory;
    }
    
    
    /**
     * Enable or disable optional specification of the fields language.
     *
     * @param Boolean $languageoption True, if a language can be defined for the fields value.
     * @return Opus_Model_Fiel Provide fluent interface.
     */
    public function setLanguageOption($languageoption) {
        $this->_languageoption = $languageoption;
        return $this;
    }
    
    /**
     * Return the current language option.
     *
     * @return Boolean True, if a language can be defined for the fields value.
     */
    public function getLanguageOption() {
        return $this->_languageoption;
    }
    
    /**
     * Set the field value language.
     *
     * @param String $language Zend locale string specifying the fields language.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setLanguage($language) {
        $this->_language = $language;
        return $this;
    }
    
    /**
     * Get the field value language.
     *
     * @return String Zend locale string specifying the fields language.
     */
    public function getLanguage() {
        return $this->_language;
    }
    
    /**
     * Set the field value.
     *
     * @param Mixed $value The field value to be set.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setValue($value) {
        $this->_value = $value;
        return $this;
    }
    
    
    /**
     * Get the fields value
     * 
     * @return Mixed Whatever the value of the field might be.
     */
    public function getValue() {
        return $this->_value;
    }
    
    /**
     * Set the fields default value.
     *
     * @param Mixed $value The field default value to be set.
     * @return Opus_Model_Field Provide fluent interface.
     */
    public function setDefault($value) {
        $this->_default = $value;
        return $this;
    }
    
    
    /**
     * Get the fields default value.
     * 
     * @return Mixed Whatever the default value of the field might be.
     */
    public function getDefault() {
        return $this->_default;
    }
    
    
    
    
    /**
     * Given a type this method returns an validator instance implementing
     * Zend_Validate_Interface in correspondance to the defined datatype of the field.
     *
     * @param integer $type DT_* constant.
     * @return Zend_Validate_Interface Validator instance. Null is returned if no
     *                                 validator is defined or needed for the field type.
     *
     */
    protected function getValidatorFor($type) {

        switch ($type) {
            case self::DT_NUMBER:
                return new Zend_Validate_Int();
                break;

            case self::DT_DATE:
                $validator = new Zend_Validate_Date();
                $locale = new Zend_Locale();
                $validator->setLocale($locale);
                $validator->setFormat(Zend_Locale_Format::getDateFormat($locale));
                return $validator;
                break;

            case self::DT_LANGUAGE:
                return new Opus_Validate_Locale();
                break;

            case self::DT_ISBN_10:
                return new Opus_Validate_Isbn10();
                break;

            case self::DT_ISBN_13:
                return new Opus_Validate_Isbn13();
                break;

            case self::DT_DOCUMENTTYPE:
                return new Opus_Validate_DocumentType();
                break;

            case self::DT_REVIEWTYPE:
                return new Opus_Validate_ReviewType();
                break;

            case self::DT_NOTESCOPE:
                return new Opus_Validate_NoteScope();
                break;

            default:
                return null;
                break;
        }
    }

}
