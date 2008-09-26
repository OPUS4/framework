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
 * @package     Opus_Form
 * @author      Ralf Claussnitzer <ralf.claussnitzer@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Definition of form layout. Parses and validates XML layout descriptions.
 *
 * @category    Framework
 * @package     Opus_Form
 *
 */
class Opus_Form_Layout {

    /**
     * Name of the registry key holding the array of all registered layouts.
     *
     */
    const ZEND_REGISTRY_KEY = 'Opus_Form_Layout';
    
    /**
     * Holds all page definitions that make up a form.
     *
     * E.g. array('a_page' => array(
     *              'a_group' => array('a_field'),
     *              'another_field'));
     * 
     * @var array
     */
    protected $_pages = array();

    /**
     * Name of the layout.
     *
     * @var string
     */
    protected $_name = '';
    
    /**
     * Reads an arbitrary XML source, whether it is an string, path or DOMDocument and
     * provides the specified form layout in an Opus_Form_Layout instance.
     *
     * @param string|DOMDocument $xml XML Source.
     * @throws InvalidArgumentException Thrown if any parameter given is invalid.
     * @throws Opus_Form_Exception Thrown if parsing the XML source failed.
     * @return Opus_Form_Layout
     * 
     */
    public static function fromXml($xml) {
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
        $schemapath = dirname(__FILE__) . '/formlayout.xsd';
        if (@$document->schemaValidate($schemapath) === false) {
            $errors = libxml_get_errors();
            $errmsg = '';
            foreach ($errors as $error) {
                // TODO Deliver more detailed error description.
                $errmsg .= $error->message . "\n";
            }
            libxml_clear_errors();
            throw new Opus_Form_Exception('XML definition has errors: ' . $errmsg);
        }

        // Parse the definition.
        try {
            $layout = new Opus_Form_Layout();
            $layout->_parse($document);
            
            // Register
            $registry = Zend_Registry::getInstance();
            if ($registry->isRegistered(self::ZEND_REGISTRY_KEY) === false) {
                $registry->set(self::ZEND_REGISTRY_KEY, array());            
            }
            $registered = $registry->get(self::ZEND_REGISTRY_KEY);
            $registered[$layout->_name] = $layout;
            $registered = $registry->set(self::ZEND_REGISTRY_KEY, $registered);
            
            return $layout;
        } catch (Exception $ex) {
            throw new Opus_Form_Exception('Failure while parsing the XML definition: ' . $ex->getMessage());
        }
    }

    /**
     * Parse a DOM document to extract field descriptions.
     *
     * @param DOMDocument $dom The DOMDocument representing the XML layout specification.
     * @throws Opus_Document_Exception Thrown on parsing errors.
     * @return void
     * 
     */
    protected function _parse(DOMDocument $dom) {
        // Set name of layout
        $root = $dom->getElementsByTagName('formlayout')->item(0);
        $this->_name = $root->attributes->getNamedItem('name')->value;
        
        // Add pages.        
        $pages = $dom->getElementsByTagName('page');
        foreach ($pages as $page) {
            $pagename = $page->attributes->getNamedItem('name')->value;
            $this->addPage($pagename);
        }
        
        // Add groups
        $groups = $dom->getElementsByTagName('group');
        foreach ($groups as $group) {
            $groupname = $group->attributes->getNamedItem('name')->value;
            $parentname = $group->parentNode->attributes->getNamedItem('name')->value;
            $this->addGroup($groupname, $parentname); 
        }
        
        // Add fields
        $fields = $dom->getElementsByTagName('field');
        foreach ($fields as $field) {
            $fieldname = $field->attributes->getNamedItem('name')->value;
            $parentname = $field->parentNode->attributes->getNamedItem('name')->value;
            $this->addField($fieldname, $parentname); 
        }
        
    }

    /**
     * Get name of this layout. 
     *
     * @return string Layout name.
     */
    public function getName() {
        return $this->_name;
    }
    
    /**
     * Add a page layout element.
     *
     * @param string $caption Name and caption of the element.
     * @throws InvalidArgumentException Thrown if no page caption has been given.
     * @throws Opus_Form_Exception      Thrown if a page with the given caption has already been added.
     * @return Opus_Form_Layout Provides a fluent interface.
     */
    public function addPage($caption) {
        if (empty($caption) === true) {
            throw new InvalidArgumentException('Page caption has not been given.');
        }
        if (array_key_exists($caption, $this->_pages) === true) {
            throw new Opus_Form_Exception('Page with caption ' . $caption . ' already added.');
        }
        $this->_pages[$caption] = array();
        return $this;
    }

    /**
     * Add a group layout element to its corresponding page.
     *
     * @param string $caption Name and caption of the element.
     * @param string $page    Name of the page this group element belongs to.
     * @throws InvalidArgumentException Thrown if caption and/or page parameters are missing.
     * @throws Opus_Form_Exception      Thrown if a page with the given name does not exist.
     * @return Opus_Form_Layout Provides a fluent interface.
     */
    public function addGroup($caption, $page) {
        if ((empty($caption) === true) or (empty($page) === true)) {
            throw new InvalidArgumentException('Caption and Page parameters must be set.');
        }
        if (array_key_exists($page, $this->_pages) === false) {
            throw new Opus_Form_Exception('Page with caption ' . $page . ' does not exist.');
        }
        $this->_pages[$page][$caption] = array();
        return $this;
    }


    /**
     * Find a given key in an array and provide a reference to the associated element.
     *
     * @param string $key      Key name.
     * @param array  $haystack Array reference to search through.
     * @return Reference Reference to the associated value if the key has been found. Otherwise
     *                   it returns an reference to "null".
     * 
     */
    private function &findElementByKey($key, array &$haystack) {
        foreach ($haystack as $a_key => &$a_value) {
            if ($a_key === $key) {
                return $a_value;
            }
            if (is_array($a_value)) {
                $ref =& $this->findElementByKey($key, $a_value);
                if (is_null($ref) === false) {
                    return $ref;
                }
            }
        }
        $result = null;
        return $result;
    }

    /**
     * Add a field to a parent element. It searches through the structure of already added elements
     * and places the field element in the first page or group element that is found by the name
     * passed by $target.
     *
     * @param string $caption Name and caption of the field.
     * @param string $target  Name and caption of the element that shall hold the field.
     * @throws InvalidArgumentException Thrown if caption is missing.
     * @throws Opus_Form_Exception      Thrown if a page or group with the given targetname does not exist.
     * @return Opus_Form_Layout Provides a fluent interface.
     */
    public function addField($caption, $target) {
        if (empty($caption) === true) {
            throw new InvalidArgumentException('Field caption has not been given.');
        }
        $element =& $this->findElementByKey($target, &$this->_pages);
        if (is_null($element) === true) {
            throw new Opus_Form_Exception('Element ' . $target . ' does not exist.');
        }
        $element[] = $caption;
        return $this;
    }

    /**
     * Return a list of names of all added pages. 
     *
     * @return array List of page names.
     */
    public function getPages() {
        return array_keys($this->_pages);
    }


    /**
     * Return all elements of a page. For instance if a page consists of two fields
     * "first_name" and "last_name" and a group "credentials" of fields "account" and "password"
     * it would probably look like follows:
     * 
     * Array(
     *  'first_name','last_name',
     *  'credentials' => array('account', 'password'));
     *
     * @param string $page Name of a page.
     * @return array Associative array containing field and group elements.
     */
    public function getPageElements($page) {
        if (empty($page) === true) {
            throw new InvalidArgumentException('Page caption has not been given.');
        }
        if (array_key_exists($page, $this->_pages) === false) {
            throw new Opus_Form_Exception('Page with caption ' . $page . ' does not exist.');
        }
        return $this->_pages[$page];
    }

}
