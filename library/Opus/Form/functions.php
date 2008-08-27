<?php
/*
 * Created on 27.08.2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */

function output($nr, $value, $language, $field) {
    $translate = Zend_Registry::getInstance()->get('Zend_Translate');
    $languages = array('lang_1', 'lang_2', 'lang_3', 'lang_4');
    $output = '';
    $output .= '<tr>' . "\n" . 
        '<td>' .
        '   <input type = "text" ' .
        '           name = "%4$s[value][%1$d]" ' . 
        '           value = "%2$s">' .
        '</td>' . "\n" . 
        '<td>';
        
        if ($field->hasLanguage()) {
            $output .= '<select name="%4$s[language][%1$d]" size = "1">';
            foreach ($languages as $lang) {
                $selected = ($lang == $language)?'selected':'';
                $output .= '<option value="' . $lang . '" ' . $selected . '>' . $translate->_($lang) . '</option>';
            }
            $output .= '</select>';
        }
        
        $output .= '</td>' . "\n".
        '</tr>';
        
        return sprintf($output, $nr, $value, $language, $field->getName());
} 

?>
