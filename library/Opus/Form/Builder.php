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
            if ($field->hasMultipleValues() === true) {
                $i = 1;
                $subform = new Zend_Form_SubForm();
                $subform->setLegend($fieldname);
                $counts = count($field->getValue());
                foreach ($field->getValue() as $fieldvalue) {
                    $this->_makeElement("$i", $fieldvalue, $subform);
                    if ($counts > 1) {
                        $remove = new Zend_Form_Element_Submit('remove_' . $fieldname . '_'  . $i);
                        $remove->setLabel('-');
                        $subform->addElement($remove);
                    }
                    $i++;
                }
                $mult = $field->getMultiplicity();
                if (($mult === '*') or ($counts < $mult)) {
                    $add = new Zend_Form_Element_Submit('add_' . $fieldname);
                    $add->setLabel('+');
                    $subform->addElement($add);
                }
                $form->addSubForm($subform, $fieldname);
            } else {
                $this->_makeElement($fieldname, $field->getValue(), $form);
                $this->_addValidator($field, $form);
                $this->_addMandatory($field, $form);
            }

        }

        if ($createSubForm === false) {
            $element = new Zend_Form_Element_Hidden(self::HIDDEN_MODEL_ELEMENT_NAME);
            $element->setValue(base64_encode(bzcompress(serialize($model))));
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
        $model = unserialize(bzdecompress(base64_decode($post[$modelelementname])));

        $this->_addRemove($post);

        $this->setFromPost($model, $post);

        $form = $this->build($model);
        $form->$modelelementname->setValue(base64_encode(bzcompress(serialize($model))));

        return $form;
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
            $model_compact = $modelelement->getValue();
            $model = unserialize(bzdecompress(base64_decode($model_compact)));
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
            // set only field which exists in model
            if (is_null($field) === true) {
                continue;
            }
            if (is_null($field->getValueModelClass()) === false) {
                if (is_null($field->getValue()) === true) {
                    $callname = 'add' . $fieldname;
                    $model->$callname();
                }

                if ($field->hasMultipleValues() === true) {
                    $this->_setFieldModelValuesFromArray($field, $value);
                } else {
                    // should never be null
                    $classname = $field->getValueModelClass();
                    $model2 = new $classname;
                    if (is_array($value) === false) {
                        $value = array($value);
                    }
                    $this->setFromPost($model2, $value);
                    $field->setValue($model2);
                }

            } else {
                if (is_array($value) === true) {
                    $value = array_values($value);
                }
                $field->setValue($value);
            }
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
        $mandatory = $field->getMandatory();
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
     * Map field name and value to an Zend_Form_Element and add it to
     * the given container object. If the value is a model instance then
     * a sub form is added.
     *
     * @param string    $name      Name of the field.
     * @param mixed     $value     Value of then field.
     * @param Zend_Form $container Zend_Form object to add the created element to.
     * @return void
     */
    protected function _makeElement($name, $value, Zend_Form $container) {
        if ($value instanceof Opus_Model_Interface) {
            $subform = $this->build($value, true);
            $container->addSubForm($subform, $name);
        } else {
            $element = new Zend_Form_Element_Text($name);
            $element->setValue($value);
            $element->setLabel($name);
            $container->addElement($element);
        }
    }

    /**
     * Set up field values from post data array.
     *
     * @param Opus_Model_Field $field  Field object.
     * @param array            $values Post data.
     * @return void
     */
    protected function _setFieldModelValuesFromArray(Opus_Model_Field $field, array $values) {
        $new_values = array();
        // should never be null
        $classname = $field->getValueModelClass();
        foreach ($values as $postvalue) {
            $model = new $classname;
            if (is_array($postvalue) === false) {
                $postvalue = array($postvalue);
            }
            $this->setFromPost($model, $postvalue);
            $new_values[] = $model;
        }
        $field->setValue($new_values);
    }

    /**
     * Alter post data array with proper action.
     *
     * @param unknown_type $ref
     * @param array $value
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