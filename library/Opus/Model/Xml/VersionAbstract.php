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
 * @package     Opus\Model\Xml
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2009-2019, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Xml;

use Opus\Model\AbstractDb;
use Opus\Model\AbstractModel;
use Opus\Model\Dependent\AbstractDependentModel;
use Opus\Model\Dependent\Link\AbstractLinkModel;
use Opus\Model\Field;
use Opus\Model\ModelException;

abstract class VersionAbstract implements Strategy
{

    /**
     * Holds current configuration.
     *
     * @var Conf
     */
    private $_config;

    /**
     * Holds current representation version.
     *
     * @var string
     */
    protected $_version = null;

    /**
     * Initiate class with a valid config object.
     */
    public function __construct()
    {
        $this->_config = new Conf;
    }

    /**
     * (non-PHPdoc)
     * @see \Opus\Model\Xml\Strategy#setDomDocument()
     */
    public function setDomDocument(\DOMDocument $dom)
    {
        $this->_config->dom = $dom;
    }

    /**
     * Use the given element to create a model instance. If a constructor attribute map is set
     * the object creation incorporates using constructor arguments from the XML element.
     *
     * If an Xlink Resolver is configured an occurance of xlink:href will be used to fetch
     * a Model instance from the specified URL.
     *
     * @param \DOMElement $element   Element to use for model creation.
     * @param string     $classname (Optional) Class name of class to be created. If not given, the node name is used.
     * @throws ModelException Thrown if the model reffered to by the elements name is unknown.
     * @return AbstractModel Created model
     */
    protected function _createModelFromElement(\DOMElement $element, $classname = null)
    {
        if (null === $classname) {
            $classname = preg_replace('/_/', '\\', $element->nodeName);
        }

        if (false === class_exists($classname)) {
            throw new ModelException('Model class ' . $classname . ' not known.');
        }

        // When xlink:href given use resolver to obtain model
        $ref = $element->attributes->getNamedItem('href');
        if ((null !== $this->_config->xlinkResolver) and (null !== $ref)) {
            $model = $this->_config->xlinkResolver->get($ref->value);
            return $model;
        }

        // Handle constructor attributes
        $model = new $classname;
        return $model;
    }

    /**
     * If there is a mapping for a model available a xlink:href string is created.
     *
     * @param AbstractModel $model Model to link.
     * @return null|string Returns a string or null if no mapping is available
     */
    protected function _createXlinkRef(AbstractModel $model)
    {
        // detect wether the model is persistent and shall be represented as xlink
        $uri = null;

        // determine the real model class name (there might be an link model in between)
        $valueModelClassName = get_class($model);
        if ($model instanceof AbstractLinkModel) {
            $valueModelClassName = $model->getModelClass();
        }

        // is there a mapping from class name to resource name?
        if (true === array_key_exists($valueModelClassName, $this->_config->resourceNameMap)) {
            // is the model a persisted database object?
            if ($model instanceof AbstractDb) {
                // return associated model id if $model is a link model
                if ($model instanceof AbstractLinkModel) {
                    $modelId = $model->getLinkedModelId();
                } else {
                    $modelId = $model->getId();
                }

                if (null !== $modelId) {
                    $resourceName = $this->_config->resourceNameMap[$valueModelClassName];
                    $uri = $this->_config->baseUri . '/' . $resourceName . '/' . $modelId;
                }
            }
        }

        return $uri;
    }

    /**
     * Maps attribute model informations to a DOMDocument.
     *
     * @param AbstractModel $model      Model informations for attribute mapping.
     * @param \DOMDocument         $dom        General DOM document.
     * @param \DOMNode             $rootNode   Node where to add created structure.
     * @param boolean             $unTunneled Should only current (true) or all (false, default) fields shown.
     * @return void
     */
    protected function _mapAttributes(
        AbstractModel $model,
        \DOMDocument $dom,
        \DOMNode $rootNode,
        $unTunneled = false
    ) {

        if ((true === $unTunneled) and ($model instanceof AbstractLinkModel)) {
            $fields = $model->describeUntunneled();
        } elseif ((true === $unTunneled) and ($model instanceof AbstractDependentModel)) {
            return; // short-circuit
        } else {
            $fields = $model->describe();
        }

        $excludeFields = $this->_config->excludeFields;
        if (count($excludeFields) > 0) {
            $fieldsDiff = array_diff($fields, $excludeFields);
        } else {
            $fieldsDiff = $fields;
        }

        foreach ($fieldsDiff as $fieldname) {
            $field = $model->getField($fieldname);
            $this->_mapField($field, $dom, $rootNode);
        }
    }

    /**
     * (non-PHPdoc)
     * @see \Opus\Model\Xml\Strategy#getDomDocument()
     */
    public function getDomDocument()
    {
        if (null === $this->_config->model) {
            throw new ModelException('No Model given for serialization.');
        }

        $this->_config->dom = new \DomDocument('1.0', 'UTF-8');
        $root = $this->_config->dom->createElement('Opus');
        $root->setAttribute('version', $this->getVersion());
        $this->_config->dom->appendChild($root);
        $root->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

        $this->_mapModel($this->_config->model, $this->_config->dom, $root);

        return $this->_config->dom;
    }

    /**
     * (non-PHPdoc)
     * @see \Opus\Model\Xml\Strategy#getModel()
     */
    public function getModel()
    {
        if (null !== $this->_config->dom) {
            $root = $this->_config->dom->getElementsByTagName('Opus')->item(0);
            if (null === $root) {
                throw new ModelException('Root element "Opus" not found.');
            }
            $model = $this->_createModelFromElement($root->firstChild);
            $this->_config->model = $this->_populateModelFromXml($model, $root->firstChild);
        }

        return $this->_config->model;
    }

    /**
     * Return version value of current xml representation.
     *
     * @see \Opus\Model\Xml\Strategy#getVersion()
     */
    public function getVersion()
    {
        return floor($this->_version);
    }

    /**
     * (non-PHPdoc)
     * @see \Opus\Model\Xml\Strategy#setDomDocument()
     */
    public function setup(Conf $conf)
    {
        $this->_config = $conf;
    }

    /**
     * (non-PHPdoc)
     * @see \Opus\Model\Xml\Strategy#setXml()
     */
    public function setXml($xml)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        // Disable libxml error reporting because it generates warnings
        // wich will be ignored in production but turned into an exception
        // in PHPUnit environments
        $useInternalErrors = libxml_use_internal_errors(true);
        $success = $dom->loadXml($xml);
        if (false === $success) {
            $errmsg = '';
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $errmsg = $errmsg . $error->message . "\n";
            }
            libxml_clear_errors();
            throw new ModelException($errmsg);
        }
        libxml_use_internal_errors($useInternalErrors);
        $this->setDomDocument($dom);
    }

    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Map field information to a DOMDocument.
     *
     * @param Field $field    Contains informations about mapping field.
     * @param \DOMDocument      $dom      General DOM document.
     * @param \DOMNode          $rootNode Node where to add created structure.
     * @return void
     *
     * FIXME: remove code duplication (duplicates Opus\Model\Xml\Version*)
     */
    protected function _mapField(Field $field, \DOMDocument $dom, \DOMNode $rootNode)
    {
        $modelClass = $field->getValueModelClass();
        $fieldValues = $field->getValue();

        if (true === $this->getConfig()->excludeEmpty) {
            if (true === is_null($fieldValues)
                or (is_string($fieldValues) && trim($fieldValues) == '')
                or (is_array($fieldValues) && empty($fieldValues)) ) {
                return;
            }
        }

        if (null === $modelClass) {
            $this->mapSimpleField($dom, $rootNode, $field);
        } else {
            $fieldName = $field->getName();

            if (! is_array($fieldValues)) {
                $fieldValues = [$fieldValues];
            }

            foreach ($fieldValues as $value) {
                $childNode = $this->createFieldElement($dom, $fieldName, $value);
                $rootNode->appendChild($childNode);

                // if a field has no value then is nothing more to do
                // TODO maybe must be there an other solution
                // FIXME remove code duplication (duplicates Opus\Model\Xml\Version*)
                if (is_null($value)) {
                    continue;
                }

                // delivers a URI if a mapping for the given model exists
                $uri = $this->_createXlinkRef($value);
                if (null !== $uri) {
                    $childNode->setAttribute('xlink:type', 'simple');
                    $childNode->setAttribute('xlink:href', $uri);
                    $this->_mapAttributes($value, $dom, $childNode, true);
                } else {
                    $this->_mapAttributes($value, $dom, $childNode);
                }
            }
        }
    }

    abstract public function mapSimpleField(\DOMDocument $dom, \DOMNode $rootNode, Field $field);

    public function getFieldValues($field)
    {
        $fieldValues = $field->getValue();

        // workaround for simple fields with multiple values
        if (true === $field->hasMultipleValues()) {
            $fieldValues = implode(',', $fieldValues);
        }
        if ($fieldValues instanceof \DateTimeZone) {
            $fieldValues = $fieldValues->getName();
        }

        return trim($fieldValues);
    }

    /**
     * Maps model information to a DOMDocument.
     *
     * @param AbstractModel $model    Contains model information of mapping.
     * @param \DOMDocument         $dom      General DOM document.
     * @param \DOMNode             $rootNode Node where to add created structure.
     * @return void
     */
    protected function _mapModel(AbstractModel $model, \DOMDocument $dom, \DOMNode $rootNode)
    {
        $fields = $model->describe();
        $excludeFields = $this->getConfig()->excludeFields;
        if (count($excludeFields) > 0) {
            $fieldsDiff = array_diff($fields, $excludeFields);
        } else {
            $fieldsDiff = $fields;
        }

        $childNode = $this->createModelNode($dom, $model);
        $rootNode->appendChild($childNode);

        foreach ($fieldsDiff as $fieldname) {
            $field = $model->getField($fieldname);
            $this->_mapField($field, $dom, $childNode);
        }
    }

    protected function createFieldElement(\DOMDocument $dom, $fieldName, $value)
    {
        return $dom->createElement($fieldName);
    }

    protected function createModelNode(\DOMDocument $dom, AbstractModel $model)
    {
        return $dom->createElement(preg_replace('/\\\\/', '_', get_class($model)));
    }
}
