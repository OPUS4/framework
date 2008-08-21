<?php
/*
 * Created on 21.08.2008
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
class Field {
    protected $type;
    protected $value;
    protected $valueLanguage;
    protected $valueAdditional;
    
    protected $mandtory;
    protected $repeatable;
    protected $language;
    protected $errorMessage = 'standard_error';
    protected $errorRegularExp = null;
    private $translate;
    
    private function _translateArray($array) {
        foreach ($array as $number => $value) {
            $array[$number] = $this->translate->_($value);
        }
        return $array;
    }
    
    public function Field($type, $mandatory = false, $repeatable = false, $language = false, $data = null) {
        $this->translate = Zend_Registry::getInstance()->get('Zend_Translate');
        
        $this->type = $type;
        $this->mandatory = $mandatory;
        $this->repeatable = $repeatable;
        $this->language = $language;
        $this->value = $data['value'];
        $this->valueLanguage = $data['language'];
        $this->valueAdditional = $data['additional'];
        
        foreach ((array) $this->value as $number => $value) {
            if ($value == '') {
                unset($this->value[$number]);
                unset($this->valueLanguage[$number]);
                unset($this->valueAdditional[$number]);
            }
        }    
    }
    
    public function isValid() {
        if (!is_null($this->errorRegularExp)) {
            foreach ($this->value as $valueLine) {
                if (!mb_ereg_match($this->errorRegularExp, $valueLine)) 
                    return false;
            }       
        }
        return true;
    }
    
    public function getValues() {
        return array_values($this->value);
    }
    
    public function getValuesLanguage() {
        return _translateArray(array_values($this->valueLanguage));
    }
    
    public function getValuesAdditional() {
        return _translateArray(array_values($this->valueAdditional));
    }
    
    public function getFieldType() {
        return $this->type;
    }
    
    public function getName() {
        $this->translate->_($this->type.'_name');
    }
    
    public function isMandatory() {
        return $this->mandatory;
    }
    
    public function getErrorMessage() {
        return $this->translate->_($this->errorMessage);
    }
}
?>
