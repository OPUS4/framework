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
 * @package     Opus_Document
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Provides functions to parse a document type description from XML. It has methods to
 * return an array describing the document type's fields that can be filled with data for
 * each field.
 *
 * It also specifies the fields and datatypes that can be used with Opus_Document and provides
 * validation facilities for them.
 *
 * @category Framework
 * @package  Opus_Document
 */
class Opus_Document_Type {

    /**
     * Datatype for textfields.
     *
     */
    const DT_TEXT           = 0;
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
     * This array internally defines all available fields with their corresponding types
     * and other flags. It is used to return all available fields and to guide the
     * validation of field values. Complex types like the title_* fields come with
     * their subsequent field definitons.
     *
     * @var array
     */
    static private $__fields = array(

    // Simple types with single values.
    // For each field multiplicity is assumed to equal 1.

        'licences_id'               => array('type' => self::DT_NUMBER),
        'range_id'                  => array('type' => self::DT_NUMBER),

        'completed_date'            => array('type' => self::DT_DATE),
        'completed_year'            => array('type' => self::DT_NUMBER),

        'contributing_corporation'  => array('type' => self::DT_TEXT),
        'creating_corporation'      => array('type' => self::DT_TEXT),
        'date_accepted'             => array('type' => self::DT_DATE),
        'document_type'             => array('type' => self::DT_DOCUMENTTYPE),
        'edition'                   => array('type' => self::DT_NUMBER),
        'issue'                     => array('type' => self::DT_TEXT),
        'language'                  => array('type' => self::DT_LANGUAGE),
        'identifier_isbn'           => array('type' => self::DT_ISBN_13),

        'page_first'                => array('type' => self::DT_NUMBER),
        'page_last'                 => array('type' => self::DT_NUMBER),
        'page_number'               => array('type' => self::DT_NUMBER),

        'publication_status'        => array('type' => self::DT_NUMBER),
        'published_date'            => array('type' => self::DT_DATE),
        'published_year'            => array('type' => self::DT_NUMBER),
        'publisher_name'            => array('type' => self::DT_TEXT),
        'publisher_place'           => array('type' => self::DT_TEXT),
        'publisher_university'      => array('type' => self::DT_NUMBER),

        'reviewed'                  => array('type' => self::DT_REVIEWTYPE),
        'server_date_modified'      => array('type' => self::DT_DATE),
        'server_date_published'     => array('type' => self::DT_DATE),
        'server_date_unlocking'     => array('type' => self::DT_DATE),
        'server_date_valid'         => array('type' => self::DT_DATE),
        'source'                    => array('type' => self::DT_TEXT),
        'swb_id'                    => array('type' => self::DT_NUMBER),
        'vg_wort_pixel_url'         => array('type' => self::DT_TEXT),
        'volume'                    => array('type' => self::DT_NUMBER),

        'institute'                 => array('type' => self::DT_INSTITUTE, 'multiplicity' => '*'),
        'non_institute_affiliation' => array('type' => self::DT_TEXT),

        'collection'                => array('type' => self::DT_COLLECTION, 'multiplicity' => '*'),

    // Complex types with subsequent fields and multiple occurences.

        'title_abstract' => array('type' => self::DT_TITLE_ABSTRACT, 'multiplicity' => '*',
            'fields' => array(
                'value'     => array('type' => self::DT_TEXT),
                'language'  => array('type' => self::DT_LANGUAGE))),

        'title_main' => array('type' => self::DT_TITLE_MAIN, 'multiplicity' => '*',
            'fields' => array(
                'value'     => array('type' => self::DT_TEXT),
                'language'  => array('type' => self::DT_LANGUAGE))),

        'title_parent' => array('type' => self::DT_TITLE_ABSTRACT, 'multiplicity' => '*',
            'fields' => array(
                'value'     => array('type' => self::DT_TEXT),
                'language'  => array('type' => self::DT_LANGUAGE))),

        'subject_swd' => array('type' => self::DT_SUBJECT_SWD,
            'fields' => array(
                'value'         => array('type' => self::DT_TEXT),
                'language'      => array('type' => self::DT_LANGUAGE),
                'external_key'  => array('type' => self::DT_TEXT))),

        'subject_ddc' => array('type' => self::DT_SUBJECT_DDC,
            'fields' => array(
                'value'         => array('type' => self::DT_TEXT),
                'language'      => array('type' => self::DT_LANGUAGE),
                'external_key'  => array('type' => self::DT_TEXT))),

        'subject_psyndex' => array('type' => self::DT_SUBJECT_PSYNDEX,
            'fields' => array(
                'value'         => array('type' => self::DT_TEXT),
                'language'      => array('type' => self::DT_LANGUAGE),
                'external_key'  => array('type' => self::DT_TEXT))),

        'subject_uncontrolled'  => array('type' => self::DT_SUBJECT_UNCONTROLLED,
            'fields' => array(
                'value'         => array('type' => self::DT_TEXT),
                'language'      => array('type' => self::DT_LANGUAGE),
                'external_key'  => array('type' => self::DT_TEXT))),

        'note' => array('type' => self::DT_NOTE, 'multiplicity' => '*',
            'fields' => array(
                'message'   => array('type' => self::DT_TEXT),
                'creator'   => array('type' => self::DT_TEXT),
                'scope'     => array('type' => self::DT_NOTESCOPE))),

        'person_advisor' => array('type' => self::DT_PERSON,
            'fields' => array(
                'first_name' => array('type' => self::DT_TEXT),
                'last_name' => array('type' => self::DT_TEXT))),

        'person_author' => array('type' => self::DT_PERSON,
            'fields' => array(
                'first_name' => array('type' => self::DT_TEXT),
                'last_name' => array('type' => self::DT_TEXT))),

        'person_other' => array('type' => self::DT_PERSON,
            'fields' => array(
                'first_name' => array('type' => self::DT_TEXT),
                'last_name' => array('type' => self::DT_TEXT))),

        'person_referee' => array('type' => self::DT_PERSON,
            'fields' => array(
                'first_name' => array('type' => self::DT_TEXT),
                'last_name' => array('type' => self::DT_TEXT))),
    );


    /**
     * Holds the document type definition that has been parsed
     * on object construction.
     *
     * @var array
     */
    protected $_definition = array(
        'fields' => array()
    );

    /**
     * Name of the document type.
     *
     * @var string
     */
    protected $_name = '';
    
    /**
     * Initialize an instance with an XML document type specification.
     *
     * @param string|DOMDocument $xml XML string, a filename or an DOMDocument instance representing
     *                                the document type specification.
     *
     * @throws InvalidArgumentException If given argument is not a kind of XML source.
     * @throws Opus_Document_Exception  If parsing or validating fails.
     *
     */
    public function __construct($xml) {

        // Determine the type of argument
        $document = null;
        $type = '';
        if (is_string($xml) === true) {
            $type = 'string';
            if ( is_file($xml) === true ) {
                $type = 'filename';
            }
        } else if ($xml instanceof DOMDocument) {
            $type = 'domdocument';
        }

        // Apply XML loading method respectivly
        try {
            switch ($type) {
                case 'string':
                    $document = new DOMDocument();
                    $document->loadXML($xml);
                    break;

                case 'filename':
                    $document = new DOMDocument();
                    $document->load($xml);
                    break;

                case 'domdocument':
                    $document = $xml;
                    break;

                default:
                    // just to trigger the catch block
                    throw new InvalidArgumentException();
                    break;
            }
        } catch (Exception $ex) {
            throw new InvalidArgumentException('Argument should be an XML string, a filename or an DOMDocument object.');
        }

        // Validate the XML definition.
        $schemapath = dirname(__FILE__) . '/documenttype.xsd';
        if (@$document->schemaValidate($schemapath) === false) {
            $errors = libxml_get_errors();
            $errmsg = '';
            foreach ($errors as $error) {
                // TODO Deliver more detailed error description.
                $errmsg .= $error->message . "\n";
            }
            libxml_clear_errors();
            throw new Opus_Document_Exception('XML definition has errors: ' . $errmsg);
        }

        // Parse the definition.
        try {
            $this->_parse($document);
        } catch (Exception $ex) {
            throw new Opus_Document_Exception('Failure while parsing the XML definition: ' . $ex->getMessage());
        }
    }


    /**
     * Parse a DOM document to extract field descriptions.
     *
     * @param DOMDocument $dom The DOMDocument representing the XML document type specification.
     * @throws Opus_Document_Exception Thrown on parsing errors.
     * @return void
     */
    protected function _parse(DOMDocument $dom) {
        // Set name of document type
        $root = $dom->getElementsByTagName('documenttype')->item(0);
        $this->_name = $root->attributes->getNamedItem('name')->value;

        // Parse fields.
        $fields=$dom->getElementsByTagName('field');
        $fieldsdef = &$this->_definition['fields'];
        foreach ($fields as $field) {
            $fieldname = $field->attributes->getNamedItem('name')->value;
            $mandatory =  $field->attributes->getNamedItem('mandatory');
            $multiplicity = $field->attributes->getNamedItem('multiplicity');
            $languageoption = $field->attributes->getNamedItem('languageoption');

            // check if the specified fieldname is valid
            if (array_key_exists($fieldname, self::$__fields) === false ) {
                throw new Opus_Document_Exception('"' . $fieldname . '" is not a valid field name');
            }
            // and if so, put into this types fieldlist
            $fieldsdef[$fieldname] = array();
            if (is_null($multiplicity) === false) {
                $fieldsdef[$fieldname]['multiplicity'] = $multiplicity->value;
            }
            if (is_null($languageoption) === false) {
                $fieldsdef[$fieldname]['languageoption'] = $languageoption->value;
            }
        }
    }

    
    /**
     * Get name of document type. 
     *
     * @return void
     */
    public function getName() {
        return $this->_name;
    }

    /**
     * Retrieve the complete list of fields that are available within Opus.
     *
     * The array contains items of the form 'fieldname' => array(...) whereas the array
     * specifies the datatype and multiplicity options. Every type description array defines
     * at least the key 'type' to determine its datatype (Opus_Document_Type::DT_* constants).
     *
     * E.g. 'date_accepted' => array('type' => self::DT_DATE)
     *
     * An optional 'multiplicity' key may state the allowed number of value instances. It can be
     * any positive integer greater then 0 or '*' to signal an unlimited number. If a datatype
     * is composed of subsequent fields, the 'fields' key specifies them in an array.
     *
     * E.g. 'title_abstract' => array('type' => self::DT_TITLE_ABSTRACT, 'multiplicity' => '*',
     *          'fields' => array(
     *              'value'     => array('type' => self::DT_TEXT),
     *              'language'  => array('type' => self::DT_LANGUAGE)))
     *
     * Some datatypes describe complex datasets like institutes (DT_INSTITUTE), persons (ST_PERSON)
     * or collections (DT_COLLECTION). Field values of those datatypes are usally internal identifier
     * numbers corresponding with persistent entities of the referred kind respectivly.
     *
     * @return array Nested associative array of available fields with corresponding datatypes.
     */
    public static function getAvailableFields() {
        return self::$__fields;
    }

    /**
     * Given a fieldname this method returns an validator instance implementing
     * Zend_Validate_Interface in correspondance to the defined datatype of the field.
     *
     * @param string|integer $par Name of the field or DT_* constant.
     * @throws InvalidArgumentException If the specified type or field name is invalid.
     *
     * @return Zend_Validate_Interface Validator instance. Null is returned if no
     *                                 validator is defined or needed for the field type.
     *
     */
    public static function getValidatorFor($par) {

        if (is_integer($par) === true) {
            $type = $par;
        } else if (is_string($par) === true) {
            // get field description
            if (array_key_exists($par, self::$__fields) === false) {
                throw new InvalidArgumentException($par . ' is not a valid field name.');
            } 
            $desc = self::$__fields[$par];
            $type = $desc['type'];
        } else {
            throw new InvalidArgumentException($par . ' is not a valid field type.');
        }

        switch ($type) {
            case self::DT_NUMBER:
                return new Zend_Validate_Int();
                break;

            case self::DT_DATE:
                return new Opus_Validate_InstanceOf('Zend_Date');
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

            case self::DT_TITLE_ABSTRACT:
                return new Opus_Validate_ComplexType(self::$__fields['title_abstract']['fields']);
                break;

            case self::DT_TITLE_MAIN:
                return new Opus_Validate_ComplexType(self::$__fields['title_main']['fields']);
                break;

            case self::DT_TITLE_PARENT:
                return new Opus_Validate_ComplexType(self::$__fields['title_parent']['fields']);
                break;

            case self::DT_SUBJECT_DDC:
                return new Opus_Validate_ComplexType(self::$__fields['subject_ddc']['fields']);
                break;

            case self::DT_SUBJECT_PSYNDEX:
                return new Opus_Validate_ComplexType(self::$__fields['subject_psyndex']['fields']);
                break;

            case self::DT_SUBJECT_SWD:
                return new Opus_Validate_ComplexType(self::$__fields['subject_swd']['fields']);
                break;

            case self::DT_SUBJECT_UNCONTROLLED:
                return new Opus_Validate_ComplexType(self::$__fields['subject_uncontrolled']['fields']);
                break;

            case self::DT_NOTE:
                return new Opus_Validate_ComplexType(self::$__fields['note']['fields']);
                break;

            case self::DT_PERSON:
                return new Opus_Validate_ComplexType(self::$__fields['person_advisor']['fields']);
                break;

            default:
                return null;
                break;
        }
    }


    /**
     * Return all field definitions that are available for the document type
     * represented by this instance.
     *
     * @return array Nested associative array of available fields with corresponding datatypes.
     */
    public function getFields() {
        return $this->_definition['fields'];
    }


    /**
     * Validates fieldname-value pairs.
     *
     * The given array has to map valid fieldnames to values. For complex datatyped fields
     * the value itself has to be an array, itself mapping fieldnames to values as well.
     *
     * E.g. 'title_abstract' => array(
     *          array(
     *              'value' => 'My title',
     *              'language'  => 'de')
     *          )
     *      )
     *
     * @param array $data Array associating fieldnames to values.
     * @return booelean True if the data is valid, false if not.
     */
    public static function validate(array $data) {

        // That the validation fails if the given array is empty.
        if (empty($data) === true) {
            return false;
        }
        $result = true;
        foreach ($data as $fieldname => $value) {
            $validator = self::getValidatorFor($fieldname);
            if (is_null($validator) === false) {
                $result = ($result and $validator->isValid($value));
            }
        }
        return $result;
    }

}