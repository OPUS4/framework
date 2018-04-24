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
 * @package     Opus_Model_Xml
 * @author      Ralf Claußnitzer (ralf.claussnitzer@slub-dresden.de)
 * @author      Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @author      Jens Schwidder <schwidder@zib.de>
 * @copyright   Copyright (c) 2009-2018, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

/**
 * First implementation of Opus XML representation.
 *
 * Simple fields are converted to attributes of element.
 */
class Opus_Model_Xml_Version1 extends Opus_Model_Xml_VersionAbstract
{

    public function __construct()
    {
        $this->_version = '1.0';
        parent::__construct();
    }

    public function mapSimpleField(DOMDocument $dom, DOMNode $rootNode, Opus_Model_Field $field)
    {
        $fieldName = $field->getName();
        $fieldValues = $this->getFieldValues($field);

        // Replace invalid XML-1.0-Characters by UTF-8 replacement character.
        $fieldValues = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', "\xEF\xBF\xBD ", $fieldValues);
        $rootNode->setAttribute($fieldName, $fieldValues);
    }

    protected function createFieldElement(DOMDocument $dom, $fieldName, $value)
    {
        $childNode = $dom->createElement($fieldName);
        if ($value instanceof Opus_Model_AbstractDb) {
            if ($value instanceof Opus_Model_Dependent_Link_Abstract) {
                $modelId = $value->getLinkedModelId();
            }
            else {
                $modelId = $value->getId();
            }
            // Ignore compound keys.
            if (false === is_array($modelId)) {
                $childNode->setAttribute('Id', $modelId);
            }
        }
        return $childNode;
    }

    protected function createModelNode(DOMDocument $dom, Opus_Model_Abstract $model)
    {
        $childNode = $dom->createElement(get_class($model));

        if ($model instanceof Opus_Model_AbstractDb) {
            $childNode->setAttribute('Id', $model->getId());
        }
        else if ($model instanceof Opus_Model_Filter and
            $model->getModel() instanceof Opus_Model_AbstractDb) {
            $childNode->setAttribute('Id', $model->getId());
        }

        return $childNode;
    }

    /**
     * Recursively populates model's fields from an Xml DomElement.
     *
     * @param  Opus_Model_Abstract  $model   The model to be populated.
     * @param  DOMElement           $element The DomElement holding the field names and values.
     * @return Opus_Model_Abstract  $model   The populated model.
     */
    protected function _populateModelFromXml(Opus_Model_Abstract $model, DOMElement $element)
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
                }
                else {
                    $model->$callname($field->value);
                }
            }
        }

        // External fields exist as child elements
        foreach ($element->childNodes as $externalField) {
            $fieldName = $externalField->nodeName;
            if (in_array($fieldName, $fieldList) === false) {
                throw new Opus\Model\Exception('Field ' . $fieldName . ' not defined');
            }
            else {
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
     * Update a model from a given xml structure.
     *
     * @param Opus_Model_Abstract $model   Model for updating.
     * @param DOMElement          $element Element with new data.
     * @return Opus_Model_Abstract
     */
    protected function _updateModelFromXml(Opus_Model_Abstract $model, DOMElement $element)
    {
        $config = $this->getConfig();
        // When xlink:href given use resolver to obtain model
        $ref = $element->attributes->getNamedItem('href');
        if ((null !== $config->xlinkResolver) and (null !== $ref)) {
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
                }
                else {
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
                }
                else {
                    $submodel = $fieldValue[$i];
                }
                $subModels[] = $this->_updateModelFromXml($submodel, $domElement);
                $i++;
            }

            $callName = 'set' . $fieldName;
            if (1 === count($subModels)) {
                $model->$callName($subModels[0]);
            }
            else {
                $model->$callName($subModels);
            }
        }

        return $model;
    }

    /**
     * (non-PHPdoc)
     * @see library/Opus/Model/Xml/Opus_Model_Xml_Strategy#updateFromXml()
     */
    public function updateFromXml($xml)
    {
        $this->setXml($xml);
        $config = $this->getConfig();
        $modelElement = $config->dom->getElementsByTagName(get_class($config->model))->item(0);
        if (null !== $modelElement) {
            $this->_updateModelFromXml($config->model, $modelElement);
        }
    }
}
