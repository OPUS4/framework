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
 * Methods to builds a Zend_Form from an Opus_Model_* class.
 *
 * @category    Framework
 * @package     Opus_Form
 *
 */
class Opus_Form_Builder {

    /**
     * Name of the form element that contains the serialized model.
     *
     */
    const HIDDEN_MODEL_ELEMENT_NAME = '__model';

    /**
     * Build an Zend_Form object from a given model. The generated form object
     * containes Zend_Form_Elements for each field of the document. If a
     * document field refers to another model instance then a sub form is
     * created.
     *
     * Additionally the given model object is serialized, compressed and base64
     * encoded and stored in a hidden form field "__model".
     *
     * @param Opus_Model_Interface $model         Model to build a form for.
     * @param boolean              $createSubForm (Optional) True, if a sub form should be
     *                                            generated instead of a form.
     * @return Zend_Form The generated form object.
     */
    public function build(Opus_Model_Interface $model, $createSubForm = false) {
        if ($createSubForm === true) {
            $form = new Zend_Form_SubForm();
        } else {
            $form = new Zend_Form();
        }

        foreach ($model->describe() as $fieldname) {
            $field = $model->getField($fieldname);
            $counts = count($field->getValue());
            $modelclass = $field->getValueModelClass();

            if ($field->hasMultipleValues() === true) {
                $i = 1;
                $subform = new Zend_Form_SubForm();
                $subform->setLegend($fieldname);

                if (($counts === 0) and ($modelclass !== null) and ($field->isSelection() === false)) {
                    // build a subform for multiple new depend model
                    // should contain afterwards one empty element
                    $this->_makeSubForm("$i", new $modelclass, $subform);
                } else {
                    foreach ($field->getValue() as $fieldvalue) {
                        // build each multi element
                        $this->_makeElement("$i", $fieldvalue, $subform, $field);
                        // Adding remove button if more than one element
                        if ($counts > 1) {
                            $remove = new Zend_Form_Element_Submit('remove_' . $fieldname . '_'  . $i);
                            $remove->setLabel('-');
                            $subform->addElement($remove);
                        }
                        $i++;
                    }
                }

                $mult = $field->getMultiplicity();
                // Adding add button
                if (($mult === '*') or ($counts < $mult)) {
                    $add = new Zend_Form_Element_Submit('add_' . $fieldname);
                    $add->setLabel('+');
                    $subform->addElement($add);
                }
                // add sub form to parent form
                $form->addSubForm($subform, $fieldname);
            } else {
                // non multiple values
                if (($counts === 0) and (is_null($modelclass) === false) and ($field->isSelection() === false)) {
                    // build a subform for a new single depend model
                    // should contain afterwards an empty element
                    $this->_makeSubForm($fieldname, new $modelclass, $form);
                } else {
                    // build a element
                    $this->_makeElement($fieldname, $field->getValue(), $form, $field);
                }
            }

        }

        if ($createSubForm === false) {
            $element = new Zend_Form_Element_Hidden(self::HIDDEN_MODEL_ELEMENT_NAME);
            $element->setValue($this->compressModel($model));
            $form->addElement($element);

            $element = new Zend_Form_Element_Submit('submit');
            $element->setLabel('transmit');
            $form->addElement($element);
        }

        return $form;
    }

    /**
     * Use form post data to recreate the form and update the serialized model.
     *
     * @param array $post Form post data as sent back from the browser.
     * @return Zend_Form The recreated and updated form object.
     */
    public function buildFromPost(array $post) {
        $modelelementname = self::HIDDEN_MODEL_ELEMENT_NAME;
        $model = $this->uncompressModel($post[$modelelementname]);

        $this->_addRemove($post);
        $this->setFromPost($model, $post);

        $form = $this->build($model);
        $form->$modelelementname->setValue($this->compressModel($model));

        return $form;
    }

    /**
     * Compress a model object for transfering in forms.
     *
     * @param Opus_Model_Interface $model Model object to compress
     * @return string
     */
    public function compressModel(Opus_Model_Interface $model) {
        return base64_encode(bzcompress(serialize($model)));
    }

    /**
     * Returns model from given form.
     *
     * @param Zend_Form $form Form object with compact model information
     * @return Opus_Model_Document|null Returns an Opus_Model_Document or
     *                                  null if no model information are in form
     */
    public function getModelFromForm(Zend_Form $form) {
        $model = null;
        $modelelementname = self::HIDDEN_MODEL_ELEMENT_NAME;
        $modelelement = $form->getElement($modelelementname);
        if (is_null($modelelement) === false) {
            $model = $this->uncompressModel($modelelement->getValue());
        }
        return $model;
    }

    /**
     * Set all field values of a given model instance by using form post data.
     *
     * @param Opus_Model_Interface $model Model to be updated.
     * @param array                $post  Post data.
     * @return void
     */
    public function setFromPost(Opus_Model_Interface $model, array $post) {
        foreach ($post as $fieldname => $value) {
            $field = $model->getField($fieldname);
            $setCallName = 'set' . $fieldname;
            $addCallName = 'add' . $fieldname;
            // set only field which exists in model
            if (is_null($field) === true) {
                continue;
            }
            $modelclass = $field->getValueModelClass();
            if (is_null($modelclass) === false) {
                if ($field->hasMultipleValues() === true) {
                    $model->$setCallName(null);
                    foreach ($value as $postvalue) {
                        // Skip empty postvalues
                        if ($postvalue === null) {
                            continue;
                        }
                        $submodel = new $modelclass;
                        if (is_array($postvalue) === false) {
                            $postvalue = array($postvalue);
                        }
                        $this->setFromPost($submodel, $postvalue);
                        $model->$addCallName($submodel);
                    }

                } else {
                    if ($field->isSelection() === true) {
                        $value = new $modelclass($value);
                        $model->$setCallName($value);
                    } else {
                        $submodel = new $modelclass;
                        if (is_array($value) === false) {
                            $value = array($value);
                        }
                        $this->setFromPost($submodel, $value);
                        $model->$setCallName($submodel);
                    }
                }
            } else {
                if (is_array($value) === true) {
                    $value = array_values($value);
                }
                $model->$setCallName($value);
            }
        }
    }

    /**
     * Uncompress a compressed model object.
     *
     * @param string $model Compressed model object.
     * @throws Opus_Form_Exception Thrown if compressed model data are invalid.
     * @return Opus_Model_Interface
     */
    public function uncompressModel($model) {
        try {
            $result = unserialize(bzdecompress(base64_decode($model)));
        } catch (Exception $e) {
            throw new Opus_Form_Exception('Model data are not unserializable.');
        }
        return $result;
    }

    /**
     * Add a given filter to form element.
     *
     * @param Opus_Model_Field $field Field object with necessary field informations
     * @param Zend_Form        $form  Form object which filter should be added
     * @return void
     */
    protected function _addFilter(Opus_Model_Field $field, Zend_Form $form) {
        $fieldname = $field->getName();
        $filter = $field->getFilter();
        if ((empty($filter) === false) and ($form->$fieldname instanceof Zend_Form_Element)) {
            $form->$fieldname->addFilter($filter);
        }
    }

    /**
     * Add a required attribute to proper fields
     *
     * @param Opus_Model_Field $field Field object with necessary field informations
     * @param Zend_Form        $form  Form object which validator should be added
     * @return void
     */
    protected function _addMandatory(Opus_Model_Field $field, Zend_Form $form) {
        $fieldname = $field->getName();
        $mandatory = $field->isMandatory();
        if ($form->$fieldname instanceof Zend_Form_Element) {
            $form->$fieldname->setRequired($mandatory);
        }
    }

    /**
     * Search for an action (add or remove) and do this action.
     *
     * @param array &$haystack Where to search
     * @return array|null Null is returned if nothing is found else a path list
     */
    protected function _addRemove(array &$haystack) {
        $result = null;
        foreach ($haystack as $a_key => &$a_value) {
            if (preg_match('/^(add|remove)_(.*)/', $a_key) === 1) {
                $result = $a_key;
            }
            if (is_array($a_value) === true) {
                $ref = $this->_addRemove($a_value);
                if (is_null($ref) === false) {
                    $this->__addRemoveAction($ref, $a_value);
                }
            }
        }
        return $result;
    }

    /**
     * Add a validator or a chain of validators to a Zend_Form field
     *
     * @param Opus_Model_Field $field Field object with necessary field informations
     * @param Zend_Form        $form  Form object which validator should be added
     * @return void
     */
    protected function _addValidator(Opus_Model_Field $field, Zend_Form $form) {
        $fieldname = $field->getName();
        $validator = $field->getValidator();
        if ((is_string($validator) === true) or ($validator instanceOf Zend_Validate_Interface)) {
            $form->$fieldname->addValidator($validator);
        }
    }

    /**
     * Build a checkbox element.
     *
     * @param Opus_Model_Field $field     Field object with building informations.
     * @param Zend_Form        $container Zend_Form object to add created element to.
     * @return void
     */
    protected function _makeCheckboxElement(Opus_Model_Field $field, Zend_Form $container) {
        $fieldname = $field->getName();
        $element = new Zend_Form_Element_Checkbox($fieldname);
        $element->setLabel($fieldname);
        $element->setValue($field->getValue());
        $container->addElement($element);
        $this->_setFieldAttributes($field, $container);
    }

    /**
     * Map field name and value to an Zend_Form_Element and add it to
     * the given container object. If the value is a model instance then
     * a sub form is added.
     *
     * @param string           $name      Name of the field.
     * @param mixed            $value     Value of then field.
     * @param Zend_Form        $container Zend_Form object to add the created element to.
     * @param Opus_Model_Field $field     Field object containing more information.
     * @return void
     */
    protected function _makeElement($name, $value, Zend_Form $container, Opus_Model_Field $field) {
        if ($field->isSelection() === true) {
            $this->_makeSelectionElement($field, $container);
        } else if ($field->isTextarea() === true) {
            $this->_makeTextAreaElement($field, $container);
        } else if ($field->isCheckbox() === true) {
            $this->_makeCheckboxElement($field, $container);
        } else if ($value instanceof Opus_Model_Interface) {
            $this->_makeSubForm($name, $value, $container);
        } else {
            $this->_makeTextElement($field, $container);
        }
    }

    /**
     * Build a selection element.
     *
     * @param Opus_Model_Field $field     Field object with building informations.
     * @param Zend_Form        $container Zend_Form object to add created element to.
     * @return void
     */
    protected function _makeSelectionElement(Opus_Model_Field $field, Zend_Form $container) {
        $fieldname = $field->getName();
        $element = new Zend_Form_Element_Select($fieldname);
        $element->setLabel($fieldname);
        $defaults = $field->getDefault();
        foreach ($defaults as $key => $default) {
            if ($default instanceOf Opus_Model_Interface) {
                $key = $default->getId();
                $value = $default->getDisplayName();
                $element->addMultiOption($key, $value);
            } else {
                $element->addMultiOption($key, $default);
            }
        }
        $value = $field->getValue();
        if ($value instanceOf Opus_Model_Interface) {
            $element->setValue($value->getId());
        } else {
            $element->setValue($value);
        }
        $container->addElement($element);
        $this->_setFieldAttributes($field, $container);
    }

    /**
     * Build a sub form.
     *
     * @param string               $name      Name of the subform.
     * @param Opus_Model_Interface $model     Model object with building informations.
     * @param Zend_Form            $container Zend_Form object to add created element to.
     * @return void
     */
    protected function _makeSubForm($name, Opus_Model_Interface $model, Zend_Form $container) {
        $subform = $this->build($model, true);
        $subform->setLegend($name);
        $container->addSubForm($subform, $name);
    }

    /**
     * Build a textarea element.
     *
     * @param Opus_Model_Field $field     Field object with building informations.
     * @param Zend_Form        $container Zend_Form object to add created element to.
     * @return void
     */
    protected function _makeTextAreaElement(Opus_Model_Field $field, Zend_Form $container) {
        $fieldname = $field->getName();
        $element = new Zend_Form_Element_Textarea($fieldname);
        $element->setLabel($fieldname);
        $element->setValue($field->getValue());
        // TODO values should be configurable
        $element->setAttribs(array('rows' => 10, 'cols' => 60));
        $container->addElement($element);
        $this->_setFieldAttributes($field, $container);
    }

    /**
     * Build a text element.
     *
     * @param Opus_Model_Field $field     Field object with building informations.
     * @param Zend_Form        $container Zend_Form object to add created element to.
     * @return void
     */
    protected function _makeTextElement(Opus_Model_Field $field, Zend_Form $container) {
        $fieldname = $field->getName();
        $element = new Zend_Form_Element_Text($fieldname);
        $element->setLabel($fieldname);
        $element->setValue($field->getValue());
        $container->addElement($element);
        $this->_setFieldAttributes($field, $container);
    }

    /**
     * Set field attributes.
     *
     * @param Opus_Model_Field $field Field with necessary attribute information.
     * @param Zend_Form $form         Form where field attributes are to be set.
     * @return void
     */
    protected function _setFieldAttributes(Opus_Model_Field $field, Zend_Form $form) {
        // set element attributes
        $this->_addFilter($field, $form);
        $this->_addMandatory($field, $form);
        $this->_addValidator($field, $form);
    }

    /**
     * Alter post data array with proper action.
     *
     * @param string $ref    Contains action to perform
     * @param array  &$value Reference to post data array
     * @return void
     */
    private function __addRemoveAction($ref, array &$value) {
        // split action command
        $fname = explode('_', $ref);
        // action to do
        $action = $fname[0];
        // remove action expression
        unset($value[$ref]);
        switch($action) {
            case 'add':
                // add a new field
                $value[] = '';
                break;

            case 'remove':
                // remove field at position
                $index = (int) $fname[2];
                // protect removing nonexisting fields or emptying structure
                if ((array_key_exists($index, $value) === true)
                    and (count($value) > 1)) {
                    unset($value[$index]);
                }
                break;

            default:
                // No action taken
                break;
        }
    }
}
