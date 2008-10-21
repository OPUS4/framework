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
 * @author      Henning Gerhardt <henning.gerhardt@slub-dresden.de>
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Builds a form.
 * @category    Framework
 * @package     Opus_Form
 *
 */
class Opus_Form_Builder {

    /**
     * Switch to encrypt form data instead of plain transmitting
     *
     * @var boolean
     */
    private static $encrypted_form = false;

    /**
     * Holds used type fields
     *
     * @var array
     */
    private static $usedfields = array();

    /**
     * Holds translated language names
     *
     * @var array
     */
    private static $language_names = array();

    /**
     * Builds a single Element depending on type.
     *
     * @param string $elementdata Used as element name
     * @param array  $typeinfo    Contains type informations for creating element
     * @throws InvalidArgumentException Thrown if data not in necessary format
     * @return array Returns an array with one element or a structure array on complex element
     */
    protected static function generateSingleElement($elementdata, array $typeinfo) {
        if (empty($elementdata) === true) {
            throw new InvalidArgumentException('Elementdata is empty.');
        }
        if (is_string($elementdata) === false) {
            throw new InvalidArgumentException('Elementdata is not a string.');
        }
        if (count($typeinfo) === 0) {
            throw new InvalidArgumentException('Typeinfo is an empty array.');
        }
        $result = array();
        if (array_key_exists('fields', $typeinfo) === true) {
            foreach ($typeinfo['fields'] as $key => $field) {
                // if parent are mandatory then should childs mandatory too
                if (array_key_exists('mandatory', $typeinfo) === true) {
                    $field['mandatory'] = $typeinfo['mandatory'];
                }
                $field['languageoption'] = $typeinfo['languageoption'];
                $result[] = self::generateSingleElement($key, $field);
            }
        } else {
            $result['name'] = $elementdata;
            // TODO use correct element types instead text for all
            $result['html_type'] = 'text';
            $result['data_type'] = $typeinfo['type'];
            if (array_key_exists('mandatory', $typeinfo) === true) {
                $result['mandatory'] = $typeinfo['mandatory'];
            } else {
                $result['mandatory'] = false;
            }

            if (array_key_exists('languageoption', $typeinfo) === true) {
                $result['languageoption'] = $typeinfo['languageoption'];
            } else {
                $result['languageoption'] = 'off';
            }
        }
        return $result;
    }

    /**
     * Build current element and all subelements recursively
     *
     * @param array $elements   Contains all elements to create
     * @param array $typefields Holds type information for this elements
     * @throws InvalidArgumentException Thrown if parameters not in correct format
     * @throws Opus_Form_Exception Thrown if type information are not correct
     * @return array Returns an empty array if no elements or created elements
     */
    protected static function generateSubElements(array $elements, array $typefields) {
        if (count($typefields) === 0) {
            throw new InvalidArgumentException('Typefields is an empty array.');
        }
        $result = array();
        foreach ($elements as $key => $element) {
            $res = array();
            if (is_array($element) === true) {
                $res['name'] = $key;
                $res['elements'] = self::generateSubElements($element, $typefields);
            } else if (array_key_exists($element, $typefields) === true) {
                $typeinfo = $typefields[$element];
                if (is_array($typeinfo) === false) {
                    throw new Opus_Form_Exception('Typeinfo is not an array.');
                }
                self::$usedfields[] = $element;
                if (($typeinfo['multiplicity'] === '*') or ($typeinfo['multiplicity'] > 1)) {
                    $res['name'] = $element;
                    $res['add'] = true;
                    $res['seq'] = 1;
                    $res['maxmulti'] = $typeinfo['multiplicity'];
                    $subelements =  self::generateSingleElement($element, $typeinfo);
                    $res['elements'] = array(array('name' => 1, 'elements' => array($subelements)));
                } else if (array_key_exists('fields', $typeinfo) === true) {
                    $res['name'] = $element;
                    $res['elements'] = self::generateSingleElement($element, $typeinfo);
                } else {
                    $res = self::generateSingleElement($element, $typeinfo);
                }
            }
            $result[] = $res;
        }
        return $result;
    }

    /**
     * Return a filled select element with all available language names.
     * Language list will be cached.
     *
     * @param mixed &$name Name for select element. Get a postfix _lang for unique form names.
     * @return Zend_Form_Element_Select
     */
    protected static function buildSelectLanguage(&$name) {
        $l = new Zend_Form_Element_Select($name . '_lang');
        if (empty(self::$language_names) === true) {
            $locale = new Zend_Locale();
            self::$language_names = $locale->getLanguageTranslationList();
            asort(self::$language_names);
        }
        $l->setMultiOptions(self::$language_names);
        return $l;
    }

    /**
     * Check for a key in an array and if found returns value of this key.
     *
     * @param mixed $key        Array key to check for
     * @param array &$container Reference to array
     * @return null|mixed Return Null if key not exists or value of key
     */
    protected static function getOption($key, array &$container) {
        $result = null;
        if (array_key_exists($key, $container) === true) {
            $result = $container[$key];
        }
        return $result;
    }

    /**
     * Build a form with Zend_Form.
     *
     * @param array     &$par      Array structure for building elements
     * @param Zend_Form $container Container format
     * @return Zend_Form Returns builded Zend_Form object
     */
    protected static function build(array &$par, Zend_Form $container) {
        $partype = '';
        if ((array_key_exists('name', $par) === true) and (array_key_exists('html_type', $par) === true)) {
            $partype = 'simple';
            $name = $par['name'];
            $type = $par['html_type'];
            $options = array('label' => $name);
            $validator = null;
            if (array_key_exists('data_type', $par) === true) {
                $validator = Opus_Document_Type::getValidatorFor($par['data_type']);
            }
            $mandatory = self::getOption('mandatory', $par);
            $language = self::getOption('languageoption', $par);
        } else if ((array_key_exists('name', $par) === true)
        and (array_key_exists('elements', $par) === true)
        and (count($par['elements'] > 0))) {
            $partype = 'elementset';
            $name = $par['name'];
            $elementset = $par['elements'];
            $add = self::getOption('add', $par);
            $remove = self::getOption('remove', $par);
        }

        switch ($partype) {
            case 'simple':
                $s = new Zend_Form_Element_Text($name);
                $s->setOptions($options);
                if (is_null($validator) === false) {
                    $s->addValidator($validator);
                }
                if ($mandatory === 'yes') {
                    $s->setRequired(true);
                }
                $container->addElement($s);
                if ($language === 'on') {
                    $container->addElement(self::buildSelectLanguage($name));
                }
                break;

            case 'elementset':
                if (is_numeric($name) === true) {
                    $legendname = $name . '. ' . $container->getName();
                } else {
                    $legendname = $name;
                }
                $subform = new Zend_Form_SubForm(array('name' => $name, 'legend' => $legendname));
                foreach ($elementset as $element) {
                    self::build($element, $subform);
                }
                if (empty($add) === false) {
                    $subform->addElement('submit', 'add_' . $name, array('label' => '+'));
                }
                if (empty($remove) === false) {
                    $subform->addElement('submit', 'remove_' . $container->getName() . '_' . $name, array('label' => '-'));
                }
                $container->addSubForm($subform, $name);
                break;

            default:
                foreach ($par as $a) {
                    if (is_array($a) === true) {
                        self::build($a, $container);
                    }
                }
                break;
        }
        return $container;
    }

    /**
     * Little helper function for creating and recreating of forms. Added all "standard" elements
     *
     * @param array &$daten Array structure for building elements
     * @return Zend_Form Returns a form (Zend_Form) with submit button and serialized form information
     */
    protected static function create(array &$daten) {
        $form = self::build($daten, new Zend_Form());
        $form->addElement('submit', 'submit', array('label' => 'transmit'));
        $daten = serialize($daten);
        if (self::$encrypted_form === true) {
            $daten = base64_encode(bzcompress($daten));
        }
        $form->addElement('hidden', 'form', array('value' => $daten));
        $form->setMethod('post');

        return $form;
    }

    /**
     * Create a Zend form object.
     *
     * @param Opus_Document_Type     $type    Describe document type
     * @param Opus_Form_Layout       $layout  (Optional) Describe field arrangement
     * @param Zend_Translate_Adapter $adapter (Optional) Holds necessary translation messages (not used yet)
     * @return Zend_Form
     */
    public static function createForm(Opus_Document_Type $type, Opus_Form_Layout $layout = null, Zend_Translate_Adapter $adapter = null) {
        $documentname = $type->getName();
        $typefields = $type->getFields();
        $layout_group = array();
        if (empty($layout) === false) {
            $pages = $layout->getPages();
            foreach ($pages as $page) {
                $lp = array();
                $lp['name'] = $page;
                $subelements = $layout->getPageElements($page);
                $lp['elements'] = self::generateSubElements($subelements, $typefields);
                $layout_group[] = $lp;
            }
        }
        // add missing type fields to layout
        $diff_array = array_diff(array_keys($typefields), self::$usedfields);
        $layout_group= array_merge($layout_group, self::generateSubElements($diff_array, $typefields));
        $form = self::create($layout_group);
        if (empty($adapter) === false) {
            $form->setDefaultTranslator($adapter);
        }
        return $form;
    }

    /**
     * Recreate a Zend form object depending on submitted data and action.
     *
     * @param array &$daten Contains submitted data including work action for recreating the form object
     * @throws Opus_Form_Exception Throws an exception if serialized data is corrupted
     * @return Zend_Form
     */
    public static function recreateForm(array &$daten) {
        if (self::$encrypted_form === true) {
            $daten['form'] = bzdecompress(base64_decode($daten['form']));
        }

        try {
            $form = unserialize($daten['form']);
        } catch (Exception $e) {
            throw new Opus_Form_Exception('Serialized data are corrupted! Aborting.');
        }

        $action = '';

        $remove_data = self::findPathToKey('remove_', $daten);
        if (is_array($remove_data) === true) {
            $action = 'remove';
            $path = array_reverse($remove_data);
        }

        $add_data = self::findPathToKey('add_', $daten);
        if (is_array($add_data) === true) {
            $action = 'add';
            $path = array_reverse($add_data);
        }

        switch ($action) {
            case 'add':
                $element =& self::findElementByPath($path, $form);
                if (array_key_exists('seq', $element) === false) {
                    $element['seq'] = 1;
                }
                if (($element['maxmulti'] === '*') or (count($element['elements']) < $element['maxmulti'])) {
                    $element['seq']++;
                    $new_element = $element['elements'][0];
                    $new_element['name'] = $element['seq'];
                    $new_element['remove'] = true;
                    $element['elements'][] = $new_element;
                }
                if (($element['maxmulti'] !== '*') and (count($element['elements']) === (int) $element['maxmulti'])) {
                    $element['add'] = false;
                }
                break;

            case 'remove':
                $remove_name = array_pop($path);
                $element =& self::findElementByPath($path, $form);
                $subelements =& $element['elements'];
                foreach ($subelements as $work_key => $work_element) {
                    if ($work_element['name'] === $remove_name) {
                        unset($subelements[$work_key]);
                        break;
                    }
                }
                if (($element['maxmulti'] !== '*') and (count($element['elements']) < $element['maxmulti'])) {
                    $element['add'] = true;
                }
                break;

            default:
                // No Action necessary
                break;
        }
        return self::create($form);
    }

    /**
     * Find a element (last element on path array) on a giving path and returns
     * a reference to this element or null if not found.
     *
     * @param array $path      Contains path to searching element
     * @param array &$haystack Where to search
     * @return reference|null Null is returned if nothing is found else a reference to element
     */
    protected static function &findElementByPath(array $path, array &$haystack) {
        $path_name = array_shift($path);
        foreach ($haystack as &$element) {
            if ($path_name === $element['name']) {
                if (count($path) === 0) {
                    $ref = &$element;
                    return $ref;
                } else {
                    return self::findElementByPath($path , $element['elements']);
                }
            }
        }
        $result = null;
        return $result;
    }

    /**
     * Search for key name (regular expression) on array and returns a list
     * of names to this key or null if not found.
     *
     * @param string $keypattern Search expression
     * @param array  &$haystack  Where to search
     * @return array|null Null is returned if nothing is found else a path list
     */
    protected static function findPathToKey($keypattern, array &$haystack) {
        foreach ($haystack as $a_key => &$a_value) {
            if (preg_match('/' . $keypattern . '/', $a_key) === 1) {
                return array();
            }
            if (is_array($a_value) === true) {
                $ref = self::findPathToKey($keypattern, $a_value);
                if (is_array($ref) === true) {
                    $ref[] = $a_key;
                    return $ref;
                }
            }
        }
        $result = null;
        return $result;
    }
}