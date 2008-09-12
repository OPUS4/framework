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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
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
    const DT_NUMBER         = 1;
    /**
     * Datatype for date values.
     *
     */
    const DT_DATE           = 2;
    /**
     * Datatype for language specification.
     *
     */
    const DT_LANGUAGE       = 3;
    /**
     * Datatype for ISBN identifier.
     *
     */
    const DT_ISBN           = 4;
    /**
     * Datatype for boolean values.
     *
     */
    const DT_BOOLEAN        = 5;
    /**
     * Datatype for document type enum.
     *
     */
    const DT_DOCUMENTTYPE   = 6;
    /**
     * Datatype for review type enum.
     *
     */
    const DT_REVIEWTYPE     = 7;
    /**
     * Datatype for document abstract.
     *
     */
    const DT_TITLE_ABSTRACT = 8;
    /**
     * Datatype for main document title.
     *
     */
    const DT_TITLE_MAIN     = 9;
    /**
     * Datatype for title of superordinate document, catalog or list etc.
     *
     */
    const DT_TITLE_PARENT   = 10;
    /**
     * Datatype for subjects following SWD standard.
     *
     */
    const DT_SUBJECT_SWD            = 11;
    /**
     * Datatype for subjects following DDC standard.
     *
     */
    const DT_SUBJECT_DDC            = 12;
    /**
     * Datatype for subjects following PSYNDEX standard.
     *
     */
    const DT_SUBJECT_PSYNDEX        = 13;
    /**
     * Datatype for free form subjects.
     *
     */
    const DT_SUBJECT_UNCONTROLLED   = 14;
    /**
     * Datatype for notes on documents.
     *
     */
    const DT_NOTE           = 15;
    /**
     * Datatype for publication scope of document notes.
     *
     */
    const DT_NOTE_SCOPE     = 16;

    /**
     * This array internally defines all available fields with their corresponding types
     * and other flags. It is used to return all available fields and to guide the
     * validation of field values. Complex types like the title_* fields come with
     * their subsequent field definitons.
     *
     * @var array
     */
    private $__fields = array(
    
        // Simple types with single values.
        // For each field multiplicity is assumed to equal 1.
        
        'licences_id'               => array('type' => self::DT_NUMBER),
        'range_id'                  => array('type' => self::DT_NUMBER),

        'completed_date'            => array('type' => self::DT_DATE),
        'completed_year'            => array('type' => self::DT_DATE),

        'contributing_corporation'  => array('type' => self::DT_TEXT),
        'creating_corporation'      => array('type' => self::DT_TEXT),
        'date_accepted'             => array('type' => self::DT_DATE),
        'document_type'             => array('type' => self::DT_DOCUMENTTYPE),
        'edition'                   => array('type' => self::DT_NUMBER),
        'issue'                     => array('type' => self::DT_TEXT),
        'language'                  => array('type' => self::DT_LANGUAGE),
        'non_institute_affiliation' => array('type' => self::DT_TEXT),

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
                'external_key'  => array('type' => self::DT_LANGUAGE))),

        'subject_ddc' => array('type' => self::DT_SUBJECT_DDC,
            'fields' => array(
                'value'         => array('type' => self::DT_TEXT),
                'language'      => array('type' => self::DT_LANGUAGE),
                'external_key'  => array('type' => self::DT_LANGUAGE))),

        'subject_psyndex' => array('type' => self::DT_SUBJECT_PSYNDEX,
            'fields' => array(
                'value'         => array('type' => self::DT_TEXT),
                'language'      => array('type' => self::DT_LANGUAGE),
                'external_key'  => array('type' => self::DT_LANGUAGE))),

        'subject_uncontrolled'  => array('type' => self::DT_SUBJECT_UNCONTROLLED,
            'fields' => array(
                'value'         => array('type' => self::DT_TEXT),
                'language'      => array('type' => self::DT_LANGUAGE),
                'external_key'  => array('type' => self::DT_LANGUAGE))),
    
        'note' => array('type' => self::DT_NOTE, 'multiplicity' => '*',
            'fields' => array(
                'message'   => array('type' => self::DT_TEXT),
                'creator'   => array('type' => self::DT_TEXT),
                'scope'     => array('type' => self::DT_NOTE_SCOPE))),
    );


    /**
     * Initialize an instance with an XML document type specification.
     *
     * @param string|DOMDocument $xml XML string, a filename or an DOMDocument instance representing
     *                                the document type specification.
     */
    public function __construct($xml) {
        if (empty($xml) === false) {
            if (is_string($xml) === true) {
                if (is_file($xml)) {

                }
            } else if ($xml instanceof DOMDocument) {

            }
        }
        throw new InvalidArgumentException('Argument should be an XML string, a filename or an DOMDocument object.');
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
     * @return array Nested associative array of available fields with corresponding datatypes.
     */
    public static function getAvailableFields() {
         return self::$__fields;
    }

    /**
     * Return all field definitions that are available for the document type
     * represented by this instance.
     *
     * @return array Nested associative array of available fields with corresponding datatypes.
     */
    public function getFields() {
        return array();
    }

    /**
     * Given a fieldname this method returns an validator instance implementing
     * Zend_Validate_Interface in correspondance to the defined datatype of the field.
     *
     * @param string $name Name of the field.
     * @return Zend_Validate_Interface Validator instance.
     */
    public function getValidatorFor($name) {
        return null;
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
    public function validate(array $data) {
        return false;
    }

}