<?php
/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the North Rhine-Westphalian Library Service Center,
 * the Cooperative Library Network Berlin-Brandenburg, the Saarland University
 * and State Library, the Saxon State Library - Dresden State and University
 * Library, the Bielefeld University Library and the University Library of
 * Hamburg University of Technology with funding from the German Research
 * Foundation and the European Regional Development Fund.
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
 * @category   Framework
 * @package    Opus_View
 * @author     Henning Gerhardt (henning.gerhardt@slub-dresden.de)
 * @copyright  Copyright (c) 2009, OPUS 4 development team
 * @license    http://www.gnu.org/licenses/gpl.html General Public License
 * @version    $Id$
 */

/**
 * View helper for displaying a model
 *
 * @category    Framework
 * @package     Opus_View
 */
class Opus_View_Helper_ShowModel extends Zend_View_Helper_Abstract {

    private function __skeleton(Opus_Model_Field $field, $label = null, $value = null) {
        $result = array();
        $result['divclass'] = $field->getName();
        $result['labelclass'] = $field->getName() . ' label';
        $result['valueclass'] = $field->getName() . ' value';
        $result['label'] = $label;
        $result['value'] = $value;
        return $result;
    }

    private function __personHelper(Opus_Model_Interface $model, $fieldname) {
        $field = $model->getField($fieldname);
        $label = $field->getName();
        $value = $field->getValue();
        $iterim = $this->__skeleton($field, $label, $value);
        return $iterim;
    }

    private function __personDisplay(Opus_Model_Field $field) {
        $value = $field->getValue();
        $result = '';
        // only one element to display
        if (is_object($value) === true) {
            $label = $field->getName();
            $model = $field->getValue();
            $data = array();
            foreach ($value->describe() as $fieldname) {
                $data[] = $this->__personHelper($model, $fieldname);
            }
            $iterim_value = $this->view->partialLoop('_model.phtml', $data);
            $outer = $this->__skeleton($field, $label, $iterim_value);
            $result = $this->view->partial('_model.phtml', $outer);
        } if (is_array($value) === true) {
            // more than one element to display
            foreach ($value as $number => $model) {
                $data = array();
                $label = ++$number . '.' . $field->getName();
                foreach($model->describe() as $fieldname)  {
                    $data[] = $this->__personHelper($model, $fieldname);
                }
                $iterim_value = $this->view->partialLoop('_model.phtml', $data);
                $outer = $this->__skeleton($field, $label, $iterim_value);
                $result .= $this->view->partial('_model.phtml', $outer);
            }
        }
        return $result;
    }

    private function __titleHelper(Opus_Model_Interface $model) {
        $data = array();
        $language_list = Zend_Registry::get('Available_Languages');
        // set language field
        $field_titleLanguage = $model->getField('TitleAbstractLanguage');
        $label = $field_titleLanguage->getName();
        $value = $language_list[$field_titleLanguage->getValue()];
        $titleLanguage = $this->__skeleton($field_titleLanguage, $label, $value);
        $data[] = $titleLanguage;
        // set value field
        $field_titleValue = $model->getField('TitleAbstractValue');
        $label = $field_titleValue->getName();
        $value = $field_titleValue->getValue();
        $titleValue = $this->__skeleton($field_titleValue, $label, $value);
        $data[] = $titleValue;
        return $data;
    }

    private function __titleDisplay(Opus_Model_Field $field) {
        $value = $field->getValue();
        $result = '';
        // only one element to display
        if (is_object($value) === true) {
            $value = $field->getValue();
            $label = $field->getName();
            $data = $this->__titleHelper($value);
            $iterim_value = $this->view->partialLoop('_model.phtml', $data);
            $outer = $this->__skeleton($field, $label, $iterim_value);
            $result = $this->view->partial('_model.phtml', $outer);
        } if (is_array($value) === true) {
            // more than one element to display
            foreach ($value as $number => $model) {
                $label = ++$number . '.' . $field->getName();
                $iterim_data = $this->__titleHelper($model);
                $iterim_value = $this->view->partialLoop('_model.phtml', $iterim_data);
                $outer = $this->__skeleton($field, $label, $iterim_value);
                $result .= $this->view->partial('_model.phtml', $outer);
            }
        }
        return $result;
    }

    protected function _displayGeneralElement(Opus_Model_Field $field) {
        $label = $field->getName();
        $value = $field->getValue();
        $data = $this->__skeleton($field, $label, $value);
        return $this->view->partial('_model.phtml', $data);
    }

    protected function _displayLicence(Opus_Model_Field $field) {
        $label = $field->getName();
        $value = $field->getValue();
        $value = $value->getField('NameLong')->getValue();
        $data = $this->__skeleton($field, $label, $value);
        return $this->view->partial('_model.phtml', $data);
    }

    protected function _displayLanguage(Opus_Model_Field $field) {
        $language_list = Zend_Registry::get('Available_Languages');
        $label = $field->getName();
        $value = $language_list[$field->getValue()];
        $data = $this->__skeleton($field, $label, $value);
        return $this->view->partial('_model.phtml', $data);
    }

    protected function _displayPersonAdvisor(Opus_Model_Field $field) {
        return $this->__personDisplay($field);
    }

    protected function _displayPersonAuthor(Opus_Model_Field $field) {
        return $this->__personDisplay($field);
    }

    protected function _displayPersonReferee(Opus_Model_Field $field) {
        return $this->__personDisplay($field);
    }

    protected function _displayPersonOther(Opus_Model_Field $field) {
        return $this->__personDisplay($field);
    }

    protected function _displayIsbn(Opus_Model_Field $field) {
        return $this->__personDisplay($field);
    }

    protected function _displayTitleAbstract(Opus_Model_Field $field) {
        return $this->__titleDisplay($field);
    }

    protected function _displayTitleMain(Opus_Model_Field $field) {
        return $this->__titleDisplay($field);
    }

    protected function _displayTitleParent(Opus_Model_Field $field) {
        return $this->__titleDisplay($field);
    }

    public function showModel(Opus_Model_Interface $model) {
        $return = '';
        foreach ($model->describe() as $fieldname) {
            $field = $model->getField($fieldname);
            $method_name = '_display' . $fieldname;
            if (method_exists($this, $method_name) === true) {
                $return .= $this->$method_name($field);
            } else {
                $return .= $this->_displayGeneralElement($field);
            }
        }
        return $return;
    }

}