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
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright   Copyright (c) 2009, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 * @version     $Id$
 */

/**
 * Second implementation of Opus XML representation.
 */
class Opus_Model_Xml_Version2 extends Opus_Model_Xml_VersionAbstract {

    public function __construct() {
        $this->_version = '2.0';
        parent::__construct();
    }

    /**
     * Map field information to a DOMDocument.
     *
     * @param Opus_Model_Field $field    Contains informations about mapping field.
     * @param DOMDocument      $dom      General DOM document.
     * @param DOMNode          $rootNode Node where to add created structure.
     * @return void
     *
     * FIXME: remove code duplication (duplicates Opus_Model_Xml_Version*)
     */
    protected function _mapField(Opus_Model_Field $field, DOMDocument $dom, DOMNode $rootNode) {
        $fieldName = $field->getName();
        $modelClass = $field->getValueModelClass();
        $fieldValues = $field->getValue();

        if (true === $this->getConfig()->_excludeEmpty) {
            if (true === is_null($fieldValues)
                    or (is_string($fieldValues) && trim($fieldValues) == '')
                    or (is_array($fieldValues) && empty($fieldValues)) ) {
                return;
            }
        }

        if (null === $modelClass) {
            // create a new element
            $element = $dom->createElement($fieldName);
            // workaround for simple fields with multiple values
            if (true === $field->hasMultipleValues()) {
                $fieldValues = implode(',', $fieldValues);
            }
            if ($fieldValues instanceOf DateTimeZone) {
                $fieldValues = $fieldValues->getName();
            }
            // set value
            //if (empty($fieldValues) === false)
            $element->nodeValue = htmlspecialchars($fieldValues);
            $rootNode->appendChild($element);
        }
        else {
            if (!is_array($fieldValues)) {
                $fieldValues = array($fieldValues);
            }

            foreach ($fieldValues as $value) {
                $childNode = $dom->createElement($fieldName);
                $rootNode->appendChild($childNode);

                // if a field has no value then is nothing more to do
                // TODO maybe must be there an other solution
                // FIXME remove code duplication (duplicates Opus_Model_Xml_Version*)
                if (is_null($value)) {
                    continue;
                }

                // delivers a URI if a mapping for the given model exists
                $uri = $this->_createXlinkRef($value);
                if (null !== $uri) {
                    $childNode->setAttribute('xlink:type', 'simple');
                    $childNode->setAttribute('xlink:href', $uri);
                    $this->_mapAttributes($value, $dom, $childNode, true);
                }
                else {
                    $this->_mapAttributes($value, $dom, $childNode);
                }
            }
        }

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

        // fields exist as child elements
        foreach ($element->childNodes as $fieldNode) {

            // skip non-element nodes
            if (XML_ELEMENT_NODE !== $fieldNode->nodeType) {
                continue;
            }

            $fieldName = $fieldNode->nodeName;
            $fieldValue = $fieldNode->nodeValue;

            if (in_array($fieldName, $fieldList) === false) {
                throw new Opus_Model_Exception('Field ' . $fieldName . ' not defined. Model class: '
                        . get_class($model));
            }
            else {
                $fieldObj = $model->getField($fieldName);
                $modelclass = $fieldObj->getValueModelClass();
                // determine accessor function
                if (true === $fieldObj->hasMultipleValues()) {
                    $accessor = 'add';
                }
                else {
                    $accessor = 'set';
                }

                // omit setting values if XML node has no child nodes
                // neither XML_ELEMENT_TEXT nor XML_ELEMENT_NODE
                if (true === $fieldNode->hasChildNodes()) {
                    if (null !== $modelclass) {
                        $submodel = $this->_createModelFromElement($fieldNode, $modelclass);
                        $callname = $accessor . $fieldName;
                        // TODO better handling of accessor methods
                        if ('add' === $accessor) {
                            // if we add values then we need to do this on the returned model
                            $tempModel = $model->$callname($submodel);
                            $this->_populateModelFromXml($tempModel, $fieldNode);
                        }
                        else {
                            // setting of values should be done on submodel
                            $model->$callname($submodel);
                            $this->_populateModelFromXml($submodel, $fieldNode);
                        }
                    }
                    else {
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
     * @see library/Opus/Model/Xml/Opus_Model_Xml_Strategy#updateFromXml()
     */
    public function updateFromXml($xml) {
        throw new Opus_Model_Exception('Method not implemented for strategy Opus_Model_Xml_Version2.');
    }

}
