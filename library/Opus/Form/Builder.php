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
                foreach ($field->getValue() as $fieldvalue) {
                    $this->_makeElement("$i", $fieldvalue, $subform);
                    $i++;
                }
                $form->addSubForm($subform, $fieldname);
            } else {
                $this->_makeElement($fieldname, $field->getValue(), $form);
                $this->_addValidator($field, $form);
            }

        }

        if ($createSubForm === false) {
            $element = new Zend_Form_Element_Hidden(self::HIDDEN_MODEL_ELEMENT_NAME);
            $element->setValue(base64_encode(bzcompress(serialize($model))));
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

        foreach ($post as $key => $value) {
            if (preg_match('/^add_/', $key) === 1) {
                $fname = explode('_', $key);
                $fname = $fname[1];
                $post[$fname][] = '';
            }
            if (preg_match('/^remove_/', $key) === 1) {
                $fname = explode('_', $key);
                $index = (int) $fname[2];
                $fname = $fname[1];
                unset($post[$fname][$index]);
            }
        }

        $this->_setFromPost($model, $post);

        $form = $this->build($model);
        $form->$modelelementname->setValue(base64_encode(bzcompress(serialize($model))));

        return $form;
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
            $this->_setFromPost($model, $postvalue);
            $new_values[] = $model;
        }
        $field->setValue($new_values);
    }

    /**
     * Set all field values of a given model instance by using form post data.
     *
     * @param Opus_Model_Interface $model Model to be updated.
     * @param array                $post  Post data.
     * @return void
     */
    protected function _setFromPost(Opus_Model_Interface $model, array $post) {
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
                    $this->_setFromPost($model2, $value);
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

}