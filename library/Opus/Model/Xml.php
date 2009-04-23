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
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @copyright   Copyright (c) 2008, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Provides creation XML from models and creation of models by valid XML respectivly.
 *
 * @category    Framework
 * @package     Opus_Model
 */
class Opus_Model_Xml {

    /**
     * Holds the current model either directly set or deserialized from XML.
     *
     * @var Opus_Model_Abstract
     */
    protected $_model = null;

    /**
     * List of fields to skip on serialization.
     *
     * @var array
     */
    protected $_excludeFields = array();


    /**
     * True, if empty fields get excluded from serialization.
     *
     * @var bool
     */
    protected $_excludeEmtpy = false;

    /**
     * Base URI for xlink:ref elements
     *
     * @var string
     */
    protected $_baseUri = '';

    /**
     * Map of model class names to resource names for URI generation.
     *
     * @var array
     */
    protected $_resourceNameMap = array();


    /**
     * Map of model class names to constructor attribute lists.
     *
     * @var array
     */
    protected $_constructionAttributesMap = array();

    /**
     * Set up the list of XML attributes that get used for initializing
     * newly created objects via constructor call rather then calls to set* methods.
     *
     * The order in wich the attributes are given must correspond to the order in
     * wich the particular model constructor accepts the values.
     *
     * Such a map may look like: array('Model1' => array('Attr1', 'Attr2'))
     * assuming the constructor signature of Model1 beeing: __construct($attr1, $attr2).
     *
     * @param array $map List of constructor attributes.
     * @return Opus_Model_Xml Fluent interface
     */
    public function setConstructionAttributesMap(array $map) {
        $this->_constructionAttributesMap = $map;
    }

    /**
     * Set up base URI for xlink URI generation.
     *
     * @param string $uri Base URI.
     * @return Opus_Model_Xml Fluent interface
     */
    public function setXlinkBaseUri($uri) {
        $this->_baseUri = $uri;
        return $this;
    }

    /**
     * Define the class name to resource name mapping.
     *
     * If a submodel is referenced by an xlink this map and the base URI are used
     * to generate the full URI. E.g. if a model is Opus_Licence, the array may specify
     * an mapping of this class name to "licence". Assuming a baseURI of "http://pub.service.org"
     * the full URI for a Licence with ID 4711 looks like this:
     * "http://pub.service.org/licence/4711"
     *
     * @param array $map Map of class names to resource names.
     * @return Opus_Model_Xml Fluent interface
     */
    public function setResourceNameMap(array $map) {
        $this->_resourceNameMap = $map;
        return $this;
    }

    /**
     * Set up list of fields to exclude from serialization.
     *
     * @param array Field list
     * @return Opus_Model_Xml Fluent interface
     */
    public function exclude(array $fields) {
        $this->_excludeFields = $fields;
        return $this;
    }

    /**
     * Define that empty fields (value===null) shall be excluded.
     *
     * @return Opus_Model_Xml Fluent interface
     */
    public function excludeEmptyFields() {
        $this->_excludeEmtpy = true;
        return $this;
    }


    /**
     * Set the Model for XML generation.
     *
     * @param Opus_Model_Abstract $model Model to serialize.
     * @return Opus_Model_Xml Fluent interface.
     */
    public function setModel($model) {
        $this->_model = $model;
        return $this;
    }

    /**
     * Return the current Model instance if there is any.
     *
     * @return Opus_Model_Abstract Deserialized or previously set Model.
     */
    public function getModel() {
        return $this->_model;
    }


    /**
     * If a model has been set this method generates and returnes
     * DOM representation of it.
     *
     * @throws Opus_Model_Exception Thrown if no Model is given.
     * @return DOMDocument DOM representation of the current Model.
     */
    public function getDomDocument() {
        if (null === $this->_model) {
            throw new Opus_Model_Exception('No Model given for serialization.');
        }

        $dom = new DomDocument('1.0', 'UTF-8');
        $root = $dom->createElement('Opus');
        $dom->appendChild($root);
        $root->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

        $element = $this->_makeDomElement($this->_model, $dom);
        $root->appendChild($element);

        return $dom;
    }

    /**
     * Create a DomElement from a given model.
     *
     * @param Opus_Model_Abstract $model   Model to create DOM representation from.
     * @param DOMDocument         $dom     DOMDocument where the root element belongs to.
     * @param string              $usename Name for XML element if it differs from Models class name.
     * @return DOMElement
     */
    protected function _makeDomElement(Opus_Model_Abstract $model, DOMDocument $dom, $usename = null) {

        if (null === $usename) {
            $elementName = get_class($model);
        } else {
            $elementName = (string) $usename;
        }

        $element = $dom->createElement($elementName);

        // detect wether the model is persistent and shall be represented as xlink
        $uri = null;

        // determine the real model class name (there might be an link model in between)
        $valueModelClassName = get_class($model);
        if ($model instanceof Opus_Model_Dependent_Link_Abstract) {
            $valueModelClassName = $model->getModelClass();
        }

        // is there a mapping from class name to resource name?
        if (true === array_key_exists($valueModelClassName, $this->_resourceNameMap)) {
            // is the model a persisted database object?
            if ($model instanceof Opus_Model_AbstractDb) {

                // return associated model id if $model is a link model
                if ($model instanceof Opus_Model_Dependent_Link_Abstract) {
                    $modelId = $model->getLinkedModelId();
                } else {
                    $modelId = $model->getId();
                }

                if (null !== $modelId) {
                    $resourceName = $this->_resourceNameMap[$valueModelClassName];
                    $uri = $this->_baseUri . '/' . $resourceName . '/' . $modelId;
                }
            }
        }

        // set up the xlink attribute if an URI is given
        if (null !== $uri) {
            $element->setAttribute('xlink:ref', $uri);
        // insert a serialized submodel if no URI is given
        } else {
            $this->_recurseXml($model, $element, $this->_excludeFields);
        }
        return $element;
    }


    /**
     * Maps all single value fields of a given Model to Attributes of an DOMElement.
     *
     * @param Opus_Model_Abstract $model         A Model instance to map attributes out of.
     * @param DOMElement          $element       A DOMElement instance to append attributes to.
     * @param array               $excludeFields (Optional) Array of fields to exclude from serialization
     * @return void
     */
    protected function _addAttributesFromModelSimpleFields(Opus_Model_Abstract $model, DOMElement $element, array $excludeFields = array()) {
        if (is_null($excludeFields) === true) {
            $excludeFields = array();
        }
        $fieldNames = $model->describeAll();
        $fieldNames = array_diff($fieldNames, $excludeFields);
        foreach ($fieldNames as $fieldName) {
            $field = $model->getField($fieldName);
            if (null === $field->getValueModelClass()) {
                $value = $field->getValue();
                $element->setAttribute($fieldName, $value);
            }
        }
    }

    /**
     * Recurses over the model's field to add attributes for its fields
     * and sub elements for referenced models.
     *
     * @param Opus_Model_Abstract $model         Model to get serialized
     * @param DOMElement          $root          DOMElement to append generated elements to
     * @param array               $excludeFields (Optional) Array of fields to exclude from serialization
     * @return void
     */
    protected function _recurseXml(Opus_Model_Abstract $model, DOMElement $root, array $excludeFields = array()) {
        $fields = $model->describeAll();
        foreach (array_diff($fields, $excludeFields) as $fieldname) {

            $callname = 'get' . $fieldname;
            $fieldvalue = $model->$callname();
            $field = $model->getField($fieldname);

            // skip empty field
            if (($this->_excludeEmtpy) and (empty($fieldvalue) === true)) continue;

            // Map simple fields to attributes
            $this->_addAttributesFromModelSimpleFields($model, $root, $excludeFields);

            // Create array from non-multiple fieldvalue.
            if (false === $field->hasMultipleValues()) {
                $fieldvalue = array($fieldvalue);
            }

            foreach($fieldvalue as $value) {
                if (null !== $field->getValueModelClass()) {
                    // handle sub model
                    if (null === $value) {
                        $classname = $field->getValueModelClass();
                        $value = new $classname;
                    }
                    $subElement = $this->_makeDomElement($value, $root->ownerDocument ,$fieldname);
                    $root->appendChild($subElement);
                }
            }
        }
    }


    /**
     * Set up a model instance from a given XML string.
     *
     * @param string $xml XML string representing a model.
     * @throws Opus_Model_Exception Thrown if XML loading failed.
     * @return Opus_Model_Xml Fluent interface.
     */
    public function setXml($xml) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        // Disable libxml error reporting because it generates warnings
        // wich will be ignored in production but turned into an exception
        // in PHPUnit environments
        libxml_use_internal_errors(true);
        $success = $dom->loadXml($xml);
        if (false === $success) {
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $errmsg = $error->message . "\n";
            }
            libxml_clear_errors();
            throw new Opus_Model_Exception($errmsg);
        }
        $this->setDomDocument($dom);
        return $this;
    }

    /**
     * Set up a model instance from a given DomDocument.
     *
     * @param DOMDocument $dom DomDocument representing a model.
     * @throws Opus_Model_Exception Thrown if parsing the XML data failes.
     * @return Opus_Model_Xml Fluent interface.
     */
    public function setDomDocument(DOMDocument $dom) {
        $root = $dom->getElementsByTagName('Opus')->item(0);
        if (null === $root) {
            throw new Opus_Model_Exception('Root element "Opus" not found.');
        }
        $model = $this->_createModelFromElement($root->firstChild);
        $this->_model = $this->_populateModelFromXml($model, $root->firstChild);
        return $this;
    }

    /**
     * Use the given element to create a model instance. If a constructor attribute map is set
     * the object creation incorporates using constructor arguments from the XML element.
     *
     * @param DOMElement $element   Element to use for model creation.
     * @param string     $classname (Optional) Class name of class to be created. If not given, the node name is used.
     * @throws Opus_Model_Exception Thrown if the model reffered to by the elements name is unknown.
     * @return Opus_Model_Abstract Created model
     */
    protected function _createModelFromElement(DOMElement $element, string $classname = null) {
        if (null === $classname) {
            $classname = $element->nodeName;
        }
        if (false === class_exists($classname)) {
            throw new Opus_Model_Exception('Model class ' . $classname . ' not known.');
        }

        // Handle constructor attributes
        if (true === array_key_exists($classname, $this->_constructionAttributesMap)) {
            $init = array();
            foreach ($this->_constructionAttributesMap[$classname] as $constructorAttribute) {
                if (null !== $constructorAttribute) {
                    $init[] = $element->getAttribute($constructorAttribute);
                } else {
                    $init[] = null;
                }
            }
            $creator = new ReflectionClass($classname);
            $model = $creator->newInstanceArgs($init);
        } else {
            $model = new $classname;
        }

        return $model;
    }

    /**
     * Recursively populates model's fields from an Xml DomElement.
     *
     * @param  Opus_Model_Abstract  $model   The model to be populated.
     * @param  DOMElement           $element The DomElement holding the field names and values.
     * @return Opus_Model_Abstract  $model   The populated model.
     */
    protected function _populateModelFromXml(Opus_Model_Abstract $model, DOMElement $element) {
        $fieldList = $model->describe();

        // Internal fields exist as attributes
        foreach ($element->attributes as $field) {
            // FIXME: Implement adding values to multi-value internal fields.

            // ignore unknown attributes
            if (true === in_array($field->nodeName, $fieldList)) {

                $callname = 'set' . $field->name;
                if ($field->value === '') {
                    $model->$callname(null);
                } else {
                    $model->$callname($field->value);
                }
            }
        }

        // External fields exist as child elements
        foreach ($element->childNodes as $externalField) {
            if (in_array($externalField, $fieldList) === false) {
                throw new Opus_Model_Exception('Field ' . $externalField->nodeName . ' not defined');
            } else {
                $modelclass = $field->getValueModelClass();
            }

            $submodel = $this->_createModelFromElement($externalField, $modelclass);
            $submodel = $this->_populateModelFromXml($submodel, $externalField);
            $callname = 'add' . $externalField->nodeName;
            $model->$callname($submodel);
        }
        return $model;
    }

}

