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
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Model\Xml;

use DateTimeZone;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use Opus\Common\Model\DependentModelInterface;
use Opus\Common\Model\FieldInterface;
use Opus\Common\Model\LinkModelInterface;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\ModelInterface;
use Opus\Common\Model\PersistableInterface;

use function array_diff;
use function array_key_exists;
use function class_exists;
use function count;
use function floor;
use function get_class;
use function implode;
use function is_array;
use function is_string;
use function libxml_clear_errors;
use function libxml_get_errors;
use function libxml_use_internal_errors;
use function preg_replace;
use function trim;

/**
 * Basic class for XML formats.
 *
 * TODO review and refine documentation (comments in code)
 */
abstract class AbstractVersion implements StrategyInterface
{
    /**
     * Holds current configuration.
     *
     * @var Conf
     */
    private $config;

    /**
     * Holds current representation version.
     *
     * @var string
     */
    protected $version;

    /**
     * Initiate class with a valid config object.
     */
    public function __construct()
    {
        $this->config = new Conf();
    }

    /**
     * (non-PHPdoc)
     *
     * @see StrategyInterface#setDomDocument()
     *
     * @return $this
     */
    public function setDomDocument(DOMDocument $dom)
    {
        $this->config->dom = $dom;

        return $this;
    }

    /**
     * Use the given element to create a model instance. If a constructor attribute map is set
     * the object creation incorporates using constructor arguments from the XML element.
     *
     * If an Xlink Resolver is configured an occurance of xlink:href will be used to fetch
     * a Model instance from the specified URL.
     *
     * @param DOMElement  $element   Element to use for model creation.
     * @param null|string $classname (Optional) Class name of class to be created. If not given, the node name is used.
     * @throws ModelException Thrown if the model reffered to by the elements name is unknown.
     * @return ModelInterface Created model
     */
    protected function createModelFromElement(DOMElement $element, $classname = null)
    {
        if (null === $classname) {
            $classname = preg_replace('/_/', '\\', $element->nodeName);
        }

        if (false === class_exists($classname)) {
            throw new ModelException('Model class ' . $classname . ' not known.');
        }

        // When xlink:href given use resolver to obtain model
        $ref = $element->attributes->getNamedItem('href');
        if ((null !== $this->config->xlinkResolver) && (null !== $ref)) {
            return $this->config->xlinkResolver->get($ref->value);
        }

        // Handle constructor attributes
        return new $classname();
    }

    /**
     * If there is a mapping for a model available a xlink:href string is created.
     *
     * @param ModelInterface $model Model to link.
     * @return null|string Returns a string or null if no mapping is available
     */
    protected function createXlinkRef($model)
    {
        // detect wether the model is persistent and shall be represented as xlink
        $uri = null;

        // determine the real model class name (there might be an link model in between)
        $valueModelClassName = get_class($model);
        if ($model instanceof LinkModelInterface) {
            $valueModelClassName = $model->getModelClass();
        }

        // is there a mapping from class name to resource name?
        if (true === array_key_exists($valueModelClassName, $this->config->resourceNameMap)) {
            // is the model a persisted database object?
            if ($model instanceof PersistableInterface) {
                // return associated model id if $model is a link model
                if ($model instanceof LinkModelInterface) {
                    $modelId = $model->getLinkedModelId();
                } else {
                    $modelId = $model->getId();
                }

                if (null !== $modelId) {
                    $resourceName = $this->config->resourceNameMap[$valueModelClassName];
                    $uri          = $this->config->baseUri . '/' . $resourceName . '/' . $modelId;
                }
            }
        }

        return $uri;
    }

    /**
     * Maps attribute model informations to a DOMDocument.
     *
     * @param ModelInterface $model      Model informations for attribute mapping.
     * @param DOMDocument    $dom        General DOM document.
     * @param DOMNode        $rootNode   Node where to add created structure.
     * @param bool           $unTunneled Should only current (true) or all (false, default) fields shown.
     */
    protected function mapAttributes($model, $dom, $rootNode, $unTunneled = false)
    {
        if ((true === $unTunneled) && $model instanceof LinkModelInterface) {
            $fields = $model->describeUntunneled();
        } elseif ((true === $unTunneled) && $model instanceof DependentModelInterface) {
            return; // short-circuit
        } else {
            $fields = $model->describe();
        }

        $excludeFields = $this->config->excludeFields;
        if (count($excludeFields) > 0) {
            $fieldsDiff = array_diff($fields, $excludeFields);
        } else {
            $fieldsDiff = $fields;
        }

        foreach ($fieldsDiff as $fieldname) {
            $field = $model->getField($fieldname);
            $this->mapField($field, $dom, $rootNode);
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see StrategyInterface#getDomDocument()
     *
     * @return DOMDocument
     */
    public function getDomDocument()
    {
        if (null === $this->config->model) {
            throw new ModelException('No Model given for serialization.');
        }

        $this->config->dom = new DOMDocument('1.0', 'UTF-8');
        $root              = $this->config->dom->createElement('Opus');
        $root->setAttribute('version', $this->version); // TODO use $this->getVersion()
        $this->config->dom->appendChild($root);
        $root->setAttribute('xmlns:xlink', 'http://www.w3.org/1999/xlink');

        $this->mapModel($this->config->model, $this->config->dom, $root);

        return $this->config->dom;
    }

    /**
     * (non-PHPdoc)
     *
     * @see StrategyInterface#getModel()
     *
     * @return ModelInterface
     */
    public function getModel()
    {
        if (null !== $this->config->dom) {
            $root = $this->config->dom->getElementsByTagName('Opus')->item(0);
            if (null === $root) {
                throw new ModelException('Root element "Opus" not found.');
            }
            $model               = $this->createModelFromElement($root->firstChild);
            $this->config->model = $this->populateModelFromXml($model, $root->firstChild);
        }

        return $this->config->model;
    }

    /**
     * Return version value of current xml representation.
     *
     * @see StrategyInterface#getVersion()
     *
     * @return int
     */
    public function getVersion()
    {
        return floor($this->version);
    }

    /**
     * (non-PHPdoc)
     *
     * @see StrategyInterface#setDomDocument()
     *
     * @return $this
     */
    public function setup(Conf $conf)
    {
        $this->config = $conf;

        return $this;
    }

    /**
     * (non-PHPdoc)
     *
     * @see StrategyInterface#setXml()
     *
     * @param string $xml
     * @return $this
     */
    public function setXml($xml)
    {
        $dom                     = new DOMDocument('1.0', 'UTF-8');
        $dom->preserveWhiteSpace = false;
        // Disable libxml error reporting because it generates warnings
        // wich will be ignored in production but turned into an exception
        // in PHPUnit environments
        $useInternalErrors = libxml_use_internal_errors(true);
        $success           = $dom->loadXml($xml);
        if (false === $success) {
            $errmsg = '';
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $errmsg .= $error->message . "\n";
            }
            libxml_clear_errors();
            throw new ModelException($errmsg);
        }
        libxml_use_internal_errors($useInternalErrors);
        $this->setDomDocument($dom);

        return $this;
    }

    /**
     * @return Conf
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Map field information to a DOMDocument.
     *
     * @param FieldInterface $field    Contains informations about mapping field.
     * @param DOMDocument    $dom      General DOM document.
     * @param DOMNode        $rootNode Node where to add created structure.
     *
     *
     * FIXME: remove code duplication (duplicates Opus\Model\Xml\Version*)
     */
    protected function mapField($field, $dom, $rootNode)
    {
        $modelClass  = $field->getValueModelClass();
        $fieldValues = $field->getValue();

        if (true === $this->getConfig()->excludeEmpty) {
            if (
                $fieldValues === null
                || (is_string($fieldValues) && trim($fieldValues) === '')
                || (is_array($fieldValues) && empty($fieldValues))
            ) {
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
                if ($value === null) {
                    continue;
                }

                // delivers a URI if a mapping for the given model exists
                $uri = $this->createXlinkRef($value);
                if (null !== $uri) {
                    $childNode->setAttribute('xlink:type', 'simple');
                    $childNode->setAttribute('xlink:href', $uri);
                    $this->mapAttributes($value, $dom, $childNode, true);
                } else {
                    $this->mapAttributes($value, $dom, $childNode);
                }
            }
        }
    }

    /**
     * @param DOMDocument    $dom
     * @param DOMNode        $rootNode
     * @param FieldInterface $field
     * @return mixed
     */
    abstract public function mapSimpleField($dom, $rootNode, $field);

    /**
     * @param FieldInterface $field
     * @return string
     */
    public function getFieldValues($field)
    {
        $fieldValues = $field->getValue();

        // workaround for simple fields with multiple values
        if (true === $field->hasMultipleValues()) {
            $fieldValues = implode(',', $fieldValues);
        }
        if ($fieldValues instanceof DateTimeZone) {
            $fieldValues = $fieldValues->getName();
        }

        return trim($fieldValues ?? '');
    }

    /**
     * Maps model information to a DOMDocument.
     *
     * @param ModelInterface $model    Contains model information of mapping.
     * @param DOMDocument    $dom      General DOM document.
     * @param DOMNode        $rootNode Node where to add created structure.
     */
    protected function mapModel($model, $dom, $rootNode)
    {
        $fields        = $model->describe();
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
            $this->mapField($field, $dom, $childNode);
        }
    }

    /**
     * @param DOMDocument $dom
     * @param string      $fieldName
     * @param mixed       $value
     * @return DOMElement|false
     * @throws DOMException
     */
    protected function createFieldElement($dom, $fieldName, $value)
    {
        return $dom->createElement($fieldName);
    }

    /**
     * @param DOMDocument    $dom
     * @param ModelInterface $model
     * @return DOMElement|false
     * @throws DOMException
     */
    protected function createModelNode($dom, $model)
    {
        return $dom->createElement(preg_replace('/\\\\/', '_', get_class($model)));
    }

    /**
     * Recursively populates model's fields from an Xml DomElement.
     *
     * @param  ModelInterface $model   The model to be populated.
     * @param  DOMElement     $element The DomElement holding the field names and values.
     * @return ModelInterface
     */
    abstract protected function populateModelFromXml($model, $element);
}
