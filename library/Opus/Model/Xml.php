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
     * Holds the current DOM representation.
     *
     * @var DOMDocument
     */
    protected $_dom = null;

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
     * Holds Resolver instance to get contents of xlink referenced resources.
     *
     * @var Opus_Uri_Resolver
     */
    protected $_xlinkResolver = null;
    

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
     * Set up Xlink-Resolver called to obtain contents of Xlink referenced resources.
     *
     * @param Opus_Uri_Resolver $resolver Resolver implementation that gets called for xlink:ref content.
     * @return Opus_Model_Xml Fluent interface
     */
    public function setXlinkResolver(Opus_Uri_Resolver $resolver) {
        $this->_xlinkResolver = $resolver;
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
     * Set XML model representation.
     *
     * @param string $xml XML string representing a model.
     * @throws Opus_Model_Exception Thrown if XML loading failed.
     * @return Opus_Model_Xml Fluent interface.
     */
    public function setXml($xml) {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
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
     * Set a DomDocument instance.
     *
     * @param DOMDocument $dom DomDocument representing a model.
     * @return Opus_Model_Xml Fluent interface.
     */
    public function setDomDocument(DOMDocument $dom) {
        $this->_dom = $dom;
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
     * Return the current Model instance if there is any. If there is an XML representation set up,
     * a new model is created by unserialising it from the XML data.
     *
     * @throws Opus_Model_Exception If an error occured during deserialisation
     * @return Opus_Model_Abstract Deserialised or previously set Model.
     */
    public function getModel() {
    
        if (null !== $this->_dom) {
            $root = $this->_dom->getElementsByTagName('Opus')->item(0);
            if (null === $root) {
                throw new Opus_Model_Exception('Root element "Opus" not found.');
            }
            $model = $this->_createModelFromElement($root->firstChild);
            $this->_model = $this->_populateModelFromXml($model, $root->firstChild);
        }
    
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

        $this->_dom = new DomDocument('1.0', 'UTF-8');
        $root = $this->_dom->createElement('Opus');
        $this->_dom->appendChild($root);
        $root->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

        $this->_mapModel($this->_model, $this->_dom, $root);

        return $this->_dom;
    }

    /**
     * If there is a mapping for a model available a xlink:href string is created.
     *
     * @param Opus_Model_Abstract $model Model to link.
     * @return null|string Returns a string or null if no mapping is available
     */
    protected function _createXlinkRef(Opus_Model_Abstract $model) {
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
        return $uri;
    }

    /**
     * Maps model information to a DOMDocument.
     *
     * @param Opus_Model_Abstract $model    Contains model information of mapping.
     * @param DOMDocument         $dom      General DOM document.
     * @param DOMNode             $rootNode Node where to add created structure.
     * @return void
     */
    protected function _mapModel(Opus_Model_Abstract $model, DOMDocument $dom, DOMNode $rootNode) {
        $fields = $model->describeAll();
        $excludeFields = $this->_excludeFields;
        if (count($excludeFields) > 0) {
            $fields_diff = array_diff($fields, $excludeFields);
        } else {
            $fields_diff = $fields;
        }

        $childNode = $dom->createElement(get_class($model));
        $rootNode->appendChild($childNode);

        foreach ($fields_diff as $fieldname) {
            $field = $model->getField($fieldname);
            $this->_mapField($field, $dom, $childNode);
        }

    }

    /**
     * Map field information to a DOMDocument.
     *
     * @param Opus_Model_Field $field    Contains informations about mapping field.
     * @param DOMDocument      $dom      General DOM document.
     * @param DOMNode          $rootNode Node where to add created structure.
     * @return void
     */
    protected function _mapField(Opus_Model_Field $field, DOMDocument $dom, DOMNode $rootNode) {

        $fieldName = $field->getName();
        $modelClass = $field->getValueModelClass();
        $fieldValues = $field->getValue();

        if ((true === empty($fieldValues)) and (true === $this->_excludeEmtpy)) {
            return;
        }

        if (null === $modelClass) {
            $attr = $dom->createAttribute($fieldName);
            // workaround for simple fields with multiple values
            if (true === $field->hasMultipleValues()) {
                $fieldValues = implode(',', $fieldValues);
            }
            $attr->value = $fieldValues;
            $rootNode->appendChild($attr);
        } else {
            if (false === is_array($fieldValues)) {
                $fieldValues = array($fieldValues);
            }

            foreach ($fieldValues as $value) {
                $childNode = $dom->createElement($fieldName);
                $rootNode->appendChild($childNode);

                // delivers a URI if a mapping for the given model exists
                $uri = $this->_createXlinkRef($value);
                if (null !== $uri) {
                    $childNode->setAttribute('xlink:ref', $uri);
                    $this->_mapAttributes($value, $dom, $childNode, true);
                } else {
                    $this->_mapAttributes($value, $dom, $childNode);
                }
            }
        }
    }

    /**
     * Maps attribute model informations to a DOMDocument.
     *
     * @param Opus_Model_Abstract $model      Model informations for attribute mapping.
     * @param DOMDocument         $dom        General DOM document.
     * @param DOMNode             $rootNode   Node where to add created structure.
     * @param boolean             $unTunneled Should only current (true) or all (false, default) fields shown.
     * @return void
     */
    protected function _mapAttributes(Opus_Model_Abstract $model, DOMDocument $dom, DOMNode $rootNode, $unTunneled = false) {

        if ((true === $unTunneled) and ($model instanceOf Opus_Model_Dependent_Link_Abstract)) {
            $fields = $model->describeUntunneled();
        } else if ((true === $unTunneled) and ($model instanceOf Opus_Model_Dependent_Abstract)) {
            $fields = array();
        } else {
            $fields = $model->describeAll();
        }
        $excludeFields = $this->_excludeFields;
        if (count($excludeFields) > 0) {
            $fields_diff = array_diff($fields, $excludeFields);
        } else {
            $fields_diff = $fields;
        }

        foreach ($fields_diff as $fieldname) {
            $field = $model->getField($fieldname);
            $this->_mapField($field, $dom, $rootNode);
        }
    }

    /**
     * Use the given element to create a model instance. If a constructor attribute map is set
     * the object creation incorporates using constructor arguments from the XML element.
     *
     * If an Xlink Resolver is configured an occurance of xlink:href will be used to fetch
     * a Model instance from the specified URL.
     *
     * @param DOMElement $element   Element to use for model creation.
     * @param string     $classname (Optional) Class name of class to be created. If not given, the node name is used.
     * @throws Opus_Model_Exception Thrown if the model reffered to by the elements name is unknown.
     * @return Opus_Model_Abstract Created model
     */
    protected function _createModelFromElement(DOMElement $element, $classname = null) {
        if (null === $classname) {
            $classname = $element->nodeName;
        }
        if (false === class_exists($classname)) {
            throw new Opus_Model_Exception('Model class ' . $classname . ' not known.');
        }

        // When xlink:href given use resolver to obtain model
        $ref = $element->attributes->getNamedItem('ref');
        if ((null !== $this->_xlinkResolver) and (null !== $ref)) {
            $model = $this->_xlinkResolver->get($ref->value);
            return $model;
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
            $fieldName = $externalField->nodeName;
            if (in_array($fieldName, $fieldList) === false) {
                throw new Opus_Model_Exception('Field ' . $fieldName . ' not defined');
            } else {
                $modelclass = $model->getField($fieldName)->getValueModelClass();
            }

            $submodel = $this->_createModelFromElement($externalField, $modelclass);
            $submodel = $this->_populateModelFromXml($submodel, $externalField);
            $callname = 'add' . $externalField->nodeName;
            $model->$callname($submodel);
        }
        return $model;
    }

    /**
     * Update a model from a given xml string.
     *
     * @param string $xml String of xml structure.
     * @return void
     */
    public function updateFromXml($xml) {
        $this->setXml($xml);
        $model_element = $this->_dom->getElementsByTagName(get_class($this->_model))->item(0);
        if (null !== $model_element) {
            $this->_updateModelFromXml($this->_model, $model_element);
        }
    }

    /**
     *
     * @param Opus_Model_Abstract $model   Model for updating.
     * @param DOMElement          $element Element with new data.
     * @return Opus_Model_Abstract
     */
    protected function _updateModelFromXml(Opus_Model_Abstract $model, DOMElement $element) {
        // When xlink:href given use resolver to obtain model
        $ref = $element->attributes->getNamedItem('ref');
        if ((null !== $this->_xlinkResolver) and (null !== $ref)) {
            $model = $this->_xlinkResolver->get($ref->value);
        }

        $fieldList = $model->describe();

        // Internal fields exist as attributes
        foreach ($element->attributes as $field) {
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

        $externalFields = array();
        // collect all external field names
        foreach ($element->childNodes as $externalField) {
            $fieldName = $externalField->nodeName;
            // step unkown fields
            if (true === in_array($fieldName, $fieldList)) {
                $externalFields[] = $fieldName;
            }
        }
        // make names unique
        $externalFields = array_unique($externalFields);

        //
        foreach ($externalFields as $fieldName) {
            $field = $model->getField($fieldName);
            $fieldValue = $field->getValue();

            $subModels = array();

            $domElements = $element->getElementsByTagName($fieldName);

            $i = 0;
            foreach ($domElements as $domElement) {
                if (false === is_array($fieldValue)) {
                    $submodel = $fieldValue;
                } else {
                    $submodel = $fieldValue[$i];
                }
                $subModels[] = $this->_updateModelFromXml($submodel, $domElement);
                $i++;
            }

            $callName = 'set' . $fieldName;
            if (1 === count($subModels)) {
                $model->$callName($subModels[0]);
            } else {
                $model->$callName($subModels);
            }
        }

        return $model;
    }

}

