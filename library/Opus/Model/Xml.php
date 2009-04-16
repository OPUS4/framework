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
     * @return DomDocument DOM representation of the current Model.
     */
    public function getDomDocument() {
        if (null === $this->_model) {
            throw new Opus_Model_Exception('No Model given for serialization.');
        }

        $dom = new DomDocument('1.0', 'UTF-8');
        $root = $dom->createElement('Opus');
        $dom->appendChild($root);
        $root->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');
        
        $element = $this->makeDomElement($this->_model, $dom);
        $root->appendChild($element);
        
        return $dom;
    }
    
    /**
     * Create a DomElement from a given model.
     *
     * @param Opus_Model_Abstract $model   Model to create DOM representation from.
     * @param DomDocument         $dom     DomDocument where the root element belongs to.
     * @param string              $usename Name for XML element if it differs from Models class name.
     * @return DomElement
     */
    private function makeDomElement(Opus_Model_Abstract $model, DomDocument $dom, $usename = null) {

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
     * Recurses over the model's field to add attributes for its fields
     * and sub elements for referenced models.
     *
     * @param Opus_Model_Abstract $model         Model to get serialized
     * @param DomElement          $root          DomElement to append generated elements to
     * @param array               $excludeFields Array of fields to exclude from serialization
     * @return void
     */
    private function _recurseXml(Opus_Model_Abstract $model, DomElement $root, array $excludeFields = null) {
        if (is_null($excludeFields) === true) {
            $excludeFields = array();
        }
        $fields = $model->describe();
        foreach (array_diff($fields, $excludeFields) as $fieldname) {
        
            $callname = 'get' . $fieldname;
            $fieldvalue = $model->$callname();
            $field = $model->getField($fieldname);
    
            // skip empty field
            if (($this->_excludeEmtpy) and (empty($fieldvalue) === true)) continue;

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
                    $subElement = $this->makeDomElement($value, $root->ownerDocument ,$fieldname);
                    $root->appendChild($subElement);
                } else {
                    // handle flat attribute
                    $root->setAttribute($fieldname, $value);
                }
            }
        }
    }


    /**
     * Set up a model instance from a given XML string.
     *
     * @param string $xml XML string representing a model.
     * @return Opus_Model_Xml Fluent interface.
     */
    public function setXml($xml) {
        $dom = new DomDocument('1.0', 'UTF-8');
        $dom->loadXml($xml);
        $this->setDomDocument($dom);        
        return $this;
    }
    
    /**
     * Set up a model instance from a given DomDocument.
     *
     * @param DomDocument $dom DomDocument representing a model.
     * @return Opus_Model_Xml Fluent interface.
     */
    public function setDomDocument(DomDocument $dom) {
        $root = $dom->getElementsByTagName('Opus')->item(0);
        $modelclass = $root->firstChild->nodeName;
        $model = new $modelclass;
        $this->_model = $this->populateModelFromXml($model, $root->firstChild); 
        return $this;
    }

    /**
     * Recursively populates model's fields from an Xml DomElement.
     *
     * @param  Opus_Model_Abstract  $model   The model to be populated.
     * @param  DomElement           $element The DomElement holding the field names and values.
     * @return Opus_Model_Abstract  $model   The populated model.
     */
    private function populateModelFromXml(Opus_Model_Abstract $model, DomElement $element) {
        // Internal fields exist as attributes
        foreach ($element->attributes as $field) {
            // FIXME: Implement adding values to multi-value internal fields.
            $callname = 'set' . $field->name;
            if ($field->value === '') {
                $model->$callname(null);
            } else {
                $model->$callname($field->value);
            }
        }

        // External fields exist as child elements
        foreach ($element->childNodes as $externalField) {
            $field = $model->getField($externalField->nodeName);
            if (is_null($field) === true) {
                throw new Opus_Model_Exception('Field ' . $externalField->nodeName . ' not defined');
            } else {
                $modelclass = $field->getValueModelClass();
            }
            $submodel = $this->populateModelFromXml(new $modelclass, $externalField);
            $callname = 'add' . $externalField->nodeName;
            $model->$callname($submodel);
        }
        return $model;
    }

}

