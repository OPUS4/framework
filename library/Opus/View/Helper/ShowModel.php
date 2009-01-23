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

    private function __skeleton($name, $value, $label = null) {
        $result = array();
        $result['divclass'] = $name;
        $result['labelclass'] = $name . ' label';
        $result['valueclass'] = $name . ' value';
        if (empty($label) === true) {
            $result['label'] = $name;
        } else {
            $result['label'] = $label;
        }
        $result['value'] = $value;
        return $result;
    }

    private function __personHelper($field, &$value, $label = null) {
        $data = array();
        foreach ($value as $fieldname => $internal_value) {
            $data[] = $this->__skeleton($fieldname, $internal_value);
        }
        $iterim_data = $this->view->partialLoop('_model.phtml', $data);
        $outer = $this->__skeleton($field, $iterim_data, $label);
        return $this->view->partial('_model.phtml', $outer);
    }

    private function __personDisplay($field, &$values) {
        // silence decision about multi values or not
        $result = '';
        if (@is_array($values[0]) === false) {
            // only one element to display
            $result = $this->__personHelper($field, $values);
        } else {
            // more than one element to display
            foreach ($values as $number => $value) {
                $label = ++$number . '. ' . $field;
                $result .= $this->__personHelper($field, $value, $label);
            }
        }
        return $result;
    }

    private function __titleHelper($field, &$value, $label = null) {
        $data = array();
        // title language
        $language_list = Zend_Registry::get('Available_Languages');
        $language_field = 'TitleAbstractLanguage';
        $language = $language_list[$value[$language_field]];
        $data[] = $this->__skeleton($language_field, $language);
        // title value
        $title_field = 'TitleAbstractValue';
        $iterim_value = $value[$title_field];
        $data[] = $this->__skeleton($title_field, $iterim_value);
        $iterim_data = $this->view->partialLoop('_model.phtml', $data);
        $outer = $this->__skeleton($field, $iterim_data, $label);
        return $this->view->partial('_model.phtml', $outer);
    }

    private function __titleDisplay($field, $values) {
        $result = '';
        if (@is_array($values[0]) === false) {
            // only one element to display
            $result = $this->__titleHelper($field, $values);
        } else {
            // more than one element to display
            foreach ($values as $number => $value) {
                $label = ++$number . '. '. $field;
                $result .= $this->__titleHelper($field, $value, $label);
            }
        }
        return $result;
    }

    protected function _displayGeneralElement($name, $value) {
        $data = $this->__skeleton($name, $value);
        return $this->view->partial('_model.phtml', $data);
    }

    protected function _displayLicence($field, $value) {
        // we "know" that the licence name is in NameLong
        $iterim_value = $value['NameLong'];
        $data = $this->__skeleton($field, $iterim_value);
        return $this->view->partial('_model.phtml', $data);
    }

    protected function _displayLanguage($field, $value) {
        $language_list = Zend_Registry::get('Available_Languages');
        $iterim_value = $language_list[$value];
        $data = $this->__skeleton($field, $iterim_value);
        return $this->view->partial('_model.phtml', $data);
    }

    protected function _displayFile($field, $value) {
        // TODO need more information for displaying
        return;
    }

    protected function _displayPersonAdvisor($field, $value) {
        return $this->__personDisplay($field, $value);
    }

    protected function _displayPersonAuthor($field, $value) {
        return $this->__personDisplay($field, $value);
    }

    protected function _displayPersonReferee($field, $value) {
        return $this->__personDisplay($field, $value);
    }

    protected function _displayPersonOther($field, $value) {
        return $this->__personDisplay($field, $value);
    }

    protected function _displayIsbn($field, $value) {
        return $this->__personDisplay($field, $value);
    }

    protected function _displayTitleAbstract($field, $value) {
        return $this->__titleDisplay($field, $value);
    }

    protected function _displayTitleMain($field, $value) {
        return $this->__titleDisplay($field, $value);
    }

    protected function _displayTitleParent($field, $value) {
        return $this->__titleDisplay($field, $value);
    }

    public function showModel(array $modeldata) {
        $result = '';
        foreach ($modeldata as $field => $value) {
            $method_name = '_display' . $field;
            if (method_exists($this, $method_name) === true) {
                $result .= $this->$method_name($field, $value);
            } else {
                $result .= $this->_displayGeneralElement($field, $value);
            }
        }
        return $result;
    }

}
