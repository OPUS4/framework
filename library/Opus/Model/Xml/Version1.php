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

use DOMDocument;
use DOMElement;
use DOMException;
use DOMNode;
use Opus\Common\Model\FieldInterface;
use Opus\Common\Model\LinkModelInterface;
use Opus\Common\Model\ModelException;
use Opus\Common\Model\ModelInterface;
use Opus\Common\Model\PersistableInterface;
use Opus\Model\Filter;

use function array_unique;
use function count;
use function get_class;
use function in_array;
use function is_array;
use function preg_replace;
use function str_replace;

/**
 * First implementation of Opus XML representation.
 *
 * Simple fields are converted to attributes of element.
 *
 * TODO make XML independent of names of PHP classes
 */
class Version1 extends AbstractVersion
{
    public function __construct()
    {
        $this->version = '1.0';
        parent::__construct();
    }

    /**
     * @param DOMDocument    $dom
     * @param DOMNode        $rootNode
     * @param FieldInterface $field
     */
    public function mapSimpleField($dom, $rootNode, $field)
    {
        $fieldName   = $field->getName();
        $fieldValues = $this->getFieldValues($field);

        // Replace invalid XML-1.0-Characters by UTF-8 replacement character.
        $fieldValues = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', "\xEF\xBF\xBD ", $fieldValues);
        $rootNode->setAttribute($fieldName, $fieldValues);
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
        $childNode = $dom->createElement($fieldName);
        if ($value instanceof PersistableInterface) {
            if ($value instanceof LinkModelInterface) {
                $modelId = $value->getLinkedModelId();
            } else {
                $modelId = $value->getId();
            }
            // Ignore compound keys.
            if (false === is_array($modelId) && $modelId !== null) {
                $childNode->setAttribute('Id', $modelId);
            }
        }
        return $childNode;
    }

    /**
     * @param DOMDocument    $dom
     * @param ModelInterface $model
     * @return DOMElement|false
     * @throws DOMException
     * @throws ModelException
     */
    protected function createModelNode($dom, $model)
    {
        $modelClass = get_class($model);

        $modelClass = str_replace('\\', '_', $modelClass);

        $childNode = $dom->createElement($modelClass);

        if ($model instanceof PersistableInterface) {
            $modelId = $model->getId();
            if ($modelId !== null) {
                $childNode->setAttribute('Id', $modelId);
            }
        } elseif (
            $model instanceof Filter &&
            $model->getModel() instanceof PersistableInterface
        ) {
            $modelId = $model->getId();
            if ($modelId !== null) {
                $childNode->setAttribute('Id', $modelId);
            }
        }

        return $childNode;
    }

    /**
     * Recursively populates model's fields from an Xml DomElement.
     *
     * @param  ModelInterface $model   The model to be populated.
     * @param  DOMElement     $element The DomElement holding the field names and values.
     * @return ModelInterface
     */
    protected function populateModelFromXml($model, $element)
    {
        $fieldList = $model->describe();

        // Internal fields exist as attributes
        foreach ($element->attributes as $field) {
            // Implement adding values to multi-value internal fields.
            // This is implemented in store-procedure, not here
            // multi-value internal fields should hold values concatenated because they have only one field in database
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
                throw new ModelException('Field ' . $fieldName . ' not defined');
            } else {
                $modelclass = $model->getField($fieldName)->getValueModelClass();
            }

            $submodel = $this->createModelFromElement($externalField, $modelclass);
            $submodel = $this->populateModelFromXml($submodel, $externalField);
            $callname = 'add' . $externalField->nodeName;
            $model->$callname($submodel);
        }
        return $model;
    }

    /**
     * Update a model from a given xml structure.
     *
     * @param ModelInterface $model   Model for updating.
     * @param DOMElement     $element Element with new data.
     * @return ModelInterface
     */
    protected function updateModelFromXml($model, $element)
    {
        $config = $this->getConfig();
        // When xlink:href given use resolver to obtain model
        $ref = $element->attributes->getNamedItem('href');
        if ((null !== $config->xlinkResolver) && (null !== $ref)) {
            $model = $config->xlinkResolver->get($ref->value);
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

        $externalFields = [];
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

        foreach ($externalFields as $fieldName) {
            $field      = $model->getField($fieldName);
            $fieldValue = $field->getValue();

            $subModels = [];

            $domElements = $element->getElementsByTagName($fieldName);

            $i = 0;
            foreach ($domElements as $domElement) {
                if (false === is_array($fieldValue)) {
                    $submodel = $fieldValue;
                } else {
                    $submodel = $fieldValue[$i];
                }
                $subModels[] = $this->updateModelFromXml($submodel, $domElement);
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

    /**
     * (non-PHPdoc)
     *
     * @see \Opus\Model\Xml\StrategyInterface#updateFromXml()
     *
     * @param string $xml
     */
    public function updateFromXml($xml)
    {
        $this->setXml($xml);
        $config       = $this->getConfig();
        $modelElement = $config->dom->getElementsByTagName(
            preg_replace('/\\\\/', '_', get_class($config->model))
        )->item(0);
        if (null !== $modelElement) {
            $this->updateModelFromXml($config->model, $modelElement);
        }
    }
}
