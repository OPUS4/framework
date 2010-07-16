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
 * @author      Felix Ostrowski <ostrowski@hbz-nrw.de>
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
     * Name of the registry key holding the array of all registered document types.
     *
     */
    const ZEND_REGISTRY_KEY = 'Opus_Document_Type';

    /**
     * Holds the document type definition that has been parsed
     * on object construction.
     *
     * @var array
     */
    protected $_definition = array(
        'fields' => array(),
        'pages' => array(),
    );

    /**
     * Holds the blacklists for frontdoor, publish form and admin form.
     *
     * @var array
     */
    protected $_blacklist = array(
        'frontdoor' => array(),
        'publishform' => array(),
        'adminform' => array(),
    );

    /**
     * Holds the sortorder for frontdoor, publish form and admin form.
     *
     * @var array
     */
    protected $_sortorder = array(
        'frontdoor' => array(),
        'publishform' => array(),
        'adminform' => array(),
    );

    /**
     * Name of the document type.
     *
     * @var string
     */
    protected $_name = '';

    /**
     * Path to location of xml document type definitions.
     *
     * @var string
     */
    static protected $_xmlDocTypePath = '';

    /**
     * Set location of xml document type definitions.
     *
     * @param  string $path
     */
    static function setXmlDoctypePath($path) {
        if (is_dir($path) === false) {
            throw new InvalidArgumentException("Argument should be a valid path.");
        }
        self::$_xmlDocTypePath = $path;
    }

    /**
     * Returns the names of all available document types.
     *
     * @return array An array of strings.
     */
    public static function getAvailableTypeNames() {
        $typeList = array();
        foreach (glob(self::$_xmlDocTypePath . DIRECTORY_SEPARATOR . '*.xml') as $filename) {
            $docType = new DomDocument;
            $docType->load($filename);
            $typeList[] = $docType->getElementsByTagName('documenttype')->item(0)->attributes->getNamedItem('name')->value;
        }
        return $typeList;
    }

    /**
     * Initialize an instance with an XML document type specification and register it with the Zend Registry.
     *
     * @param string|DOMDocument $xml XML string, a filename, type name or
     *                                an DOMDocument instance representing
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

            // Check if a type with the given name is already registered
            $registry = Zend_Registry::getInstance();
            if ($registry->isRegistered(self::ZEND_REGISTRY_KEY) === true) {
                $registered = $registry->get(self::ZEND_REGISTRY_KEY);

                // If the type has already been instanciated use the
                // registred instance as a prototype.
                if (array_key_exists($xml, $registered) === true) {
                    $type = 'prototype';
                }

            }

            // Check if the given string is the name of a file
            $filename = $xml;

            // Check for a regular filename.
            if (is_file($filename) === true ) {
                $type = 'filename';
                $xml = $filename;
            } else {
                $filename = self::$_xmlDocTypePath . DIRECTORY_SEPARATOR . $filename . '.xml';
                if (is_file($filename) === true) {
                    $type = 'filename';
                    $xml = $filename;
                }
            }

            unset($filename);

        } else if ($xml instanceof DOMDocument) {
            $type = 'domdocument';
        }
       
        // Apply XML loading method respectivly
        try {
            switch ($type) {
                case 'string':
                    $document = new DOMDocument();
                    if ($document->loadXML($xml) === false) {
                        // Trigger catch block
                        throw new Exception();
                    }
                    break;

                case 'filename':
                    $document = new DOMDocument();
                    if ($document->load($xml) === false) {
                        // Trigger catch block
                        throw new Exception();
                    }
                    break;

                case 'domdocument':
                    $document = $xml;
                    break;

                case 'prototype':
                    $registered = Zend_Registry::get(self::ZEND_REGISTRY_KEY);
                    $prototype = $registered[$xml];
                    $this->_definition = $prototype->_definition;
                    $this->_name = $prototype->_name;
                    return;
                    break;

                default:
                    // just to trigger the catch block
                    throw new InvalidArgumentException();
                    break;
            }
        } catch (Exception $ex) {
            throw new InvalidArgumentException('Argument should be a valid document type name, an XML string, a filename or an DOMDocument object:' . $xml);
        }

        // Validate the XML definition.
        $schemapath = dirname(__FILE__) . '/documenttype.xsd';
        libxml_use_internal_errors(true);
        if (@$document->schemaValidate($schemapath) === false) {
            $errors = libxml_get_errors();
            $errmsg = '';
            foreach ($errors as $error) {
                $errmsg .= "line:$error->line code:$error->code message:$error->message\n";
            }
            libxml_clear_errors();
            throw new Opus_Document_Exception($errmsg);
        }

        // Parse the definition.
        try {
            $this->_parse($document);
        } catch (Exception $ex) {
            throw new Opus_Document_Exception('Failure while parsing the XML definition: ' . $ex->getMessage());
        }

        // Register
        $registry = Zend_Registry::getInstance();
        if ($registry->isRegistered(self::ZEND_REGISTRY_KEY) === false) {
            $registry->set(self::ZEND_REGISTRY_KEY, array());
        }
        $registered = $registry->get(self::ZEND_REGISTRY_KEY);
        $registered[$this->_name] = $this;
        $registered = $registry->set(self::ZEND_REGISTRY_KEY, $registered);
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
        $fields = $dom->getElementsByTagName('field');
        $fieldsdef = &$this->_definition['fields'];
        foreach ($fields as $field) {
            $fieldname = $field->attributes->getNamedItem('name')->value;
            $mandatory =  $field->attributes->getNamedItem('mandatory');
            $multiplicity = $field->attributes->getNamedItem('multiplicity');
            $publishformweight = $field->attributes->getNamedItem('publishformweight');
            $adminformweight = $field->attributes->getNamedItem('adminformweight');

            // Add the field.
            $fieldsdef[$fieldname] = array();

            // Check for attributes and set values or defaults respectivly.
            if (is_null($multiplicity) === false) {
                if ($multiplicity->value !== '*') {
                    $multval = (int) $multiplicity->value;
                } else {
                    $multval = '*';
                }
                $fieldsdef[$fieldname]['multiplicity'] = $multval;
            } else {
                $fieldsdef[$fieldname]['multiplicity'] = 1;
            }

            if (is_null($mandatory) === false) {
                $mandval = ($mandatory->value === 'yes');
                $fieldsdef[$fieldname]['mandatory'] = $mandval;
            } else {
                $fieldsdef[$fieldname]['mandatory'] = false;
            }

            if (is_null($publishformweight) === false) {
                if ($publishformweight->value > 0) {
                    $this->_sortorder['publishform'][$fieldname] = $publishformweight->value;
                } else {
                    $this->_blacklist['publishform'][] = $fieldname;
                }
            }

            if (is_null($adminformweight) === false) {
                if ($adminformweight->value > 0) {
                    $this->_sortorder['adminform'][$fieldname] = $adminformweight->value;
                } else {
                    $this->_blacklist['adminform'][] = $fieldname;
                }
            }
        }
        asort($this->_sortorder['frontdoor']);
        asort($this->_sortorder['publishform']);
        asort($this->_sortorder['adminform']);

        // Parse pages.
        $pages = $dom->getElementsByTagName('page');
        foreach ($pages as $page) {
            $caption = $page->getAttribute('caption');
            $fields = array();
            foreach ($page->getElementsByTagName('field') as $field) {
               $fields[] = $field->attributes->getNamedItem('name')->value;
            }
            $this->_definition['pages'][] = array('caption' => $caption, 'fields' => $fields);
        }
    }


    /**
     * Get name of document type.
     *
     * @return string Type name.
     */
    public function getName() {
        return $this->_name;
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
     * Return page definitions.
     *
     * @return array Nested array of pages with corresponding field names.
     */
    public function getPages() {
        return $this->_definition['pages'];
    }

    /**
     * Return the names of all fields that should not be displayed in the publish form.
     *
     * @return array Array of field names.
     */
    public function getPublishFormBlackList() {
        return $this->_blacklist['publishform'];
    }

    /**
     * Returns a sorted array of field names as they should be displayed in the publish form.
     *
     * @return array Sorted array of field names.
     */
    public function getPublishFormSortOrder() {
        return array_keys($this->_sortorder['publishform']);
    }

    /**
     * Return the names of all fields that should not be displayed in the admin form.
     *
     * @return array Array of field names.
     */
    public function getAdminFormBlackList() {
        return $this->_blacklist['adminform'];
    }

    /**
     * Returns a sorted array of field names as they should be displayed in the admin form.
     *
     * @return array Sorted array of field names.
     */
    public function getAdminFormSortOrder() {
        return array_keys($this->_sortorder['adminform']);
    }

}
