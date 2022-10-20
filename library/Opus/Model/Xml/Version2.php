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
use Opus\Common\Model\ModelException;
use Opus\Common\Model\ModelInterface;

use function get_class;
use function htmlspecialchars;
use function in_array;

use const XML_ELEMENT_NODE;

/**
 * Second implementation of Opus XML representation.
 *
 * Simple fields are expressed as child elements.
 *
 * TODO Version2 does not seem to be used - What was the intend?
 */
class Version2 extends AbstractVersion
{
    public function __construct()
    {
        $this->version = '2.0';
        parent::__construct();
    }

    /**
     * @param DOMDocument    $dom
     * @param DOMNode        $rootNode
     * @param FieldInterface $field
     * @throws DOMException
     */
    public function mapSimpleField($dom, $rootNode, $field)
    {
        $fieldName   = $field->getName();
        $fieldValues = $this->getFieldValues($field);

        // create a new element
        $element = $dom->createElement($fieldName);

        // set value
        //if (empty($fieldValues) === false)
        $element->nodeValue = htmlspecialchars($fieldValues);
        $rootNode->appendChild($element);
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

        // fields exist as child elements
        foreach ($element->childNodes as $fieldNode) {
            // skip non-element nodes
            if (XML_ELEMENT_NODE !== $fieldNode->nodeType) {
                continue;
            }

            $fieldName  = $fieldNode->nodeName;
            $fieldValue = $fieldNode->nodeValue;

            if (in_array($fieldName, $fieldList) === false) {
                throw new ModelException(
                    'Field ' . $fieldName . ' not defined. Model class: ' . get_class($model)
                );
            } else {
                $fieldObj   = $model->getField($fieldName);
                $modelclass = $fieldObj->getValueModelClass();
                // determine accessor function
                if (true === $fieldObj->hasMultipleValues()) {
                    $accessor = 'add';
                } else {
                    $accessor = 'set';
                }

                // omit setting values if XML node has no child nodes
                // neither XML_ELEMENT_TEXT nor XML_ELEMENT_NODE
                if (true === $fieldNode->hasChildNodes()) {
                    if (null !== $modelclass) {
                        $submodel = $this->createModelFromElement($fieldNode, $modelclass);
                        $callname = $accessor . $fieldName;
                        // TODO better handling of accessor methods
                        if ('add' === $accessor) {
                            // if we add values then we need to do this on the returned model
                            $tempModel = $model->$callname($submodel);
                            $this->populateModelFromXml($tempModel, $fieldNode);
                        } else {
                            // setting of values should be done on submodel
                            $model->$callname($submodel);
                            $this->populateModelFromXml($submodel, $fieldNode);
                        }
                    } else {
                        $callname = $accessor . $fieldName;
                        $model->$callname($fieldValue);
                    }
                }
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
        throw new ModelException('Method not implemented for strategy ' . self::class);
    }
}
