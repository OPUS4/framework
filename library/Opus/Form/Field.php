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
    protected $add = false;
    private $translate;
    
    private function _translateArray($array) {
        /*foreach ($array as $number => $value) {
            $array[$number] = $this->translate->_($value);
        }*/
        return $array;
    }
    
    public function Field($type, $mandatory = false, $repeatable = false, $language = false, $data = null) {
        //$this->translate = Zend_Registry::getInstance()->get('Zend_Translate');
        
        $this->type = $type;
        $this->mandatory = $mandatory;
        $this->repeatable = $repeatable;
        $this->language = $language;
        $this->value = isset($data['value'])?$data['value']:array();
        $this->valueLanguage = isset($data['language'])?$data['language']:array();
        @$this->valueAdditional = isset($data['additional'])?$data['additional']:array();
        if (isset($data['add'])) {
            $this->setAdd();
        }
        
        foreach ((array) $this->value as $number => $value) {
            if ($value == '') {
                unset($this->value[$number]);
                unset($this->valueLanguage[$number]);
                unset($this->valueAdditional[$number]);
            }
        }    
    }
    
    public function setAdd() {
        $this->add = true;
    }
    
    public function addLine() {
        return $this->add;
    }
    
    public function isMandatoryFilled() {
        return (!$this->mandatory || count($this->value) != 0);
    }
    
    public function isValid($lineNr = null) {
        
        if (!is_null($lineNr) && !is_null($this->errorRegularExp)) {
            print('Test_');
            
            if (!isset($this->value[$lineNr])) {
                print('value nicht vorhanden');
                return true;
            }
            print($this->value[$lineNr]);
            print(mb_ereg_match('/^[a-zA-ZäÄöÖüÜß-]*, [a-zA-ZäÄöÖüÜß. ]*$/', 'Leidinger, T'));
            return preg_match($this->errorRegularExp, $this->value[$lineNr]);
        }
        
        if (is_null($lineNr)) {
            print('Test1');
            if (!$this->isMandatoryFilled()) {
                return false;
            }
            foreach ($this->value as $nr => $valueLine) {
                if (!$this->isValid($nr)) {
                    return false;
                }
            }       
        }
        print('Test2');
        return true;
    }
    
    public function getValues() {
        return array_values($this->value);
    }
    
    public function getValuesLanguage() {
        return array_values($this->valueLanguage);
    }
    
    public function getValuesAdditional() {
        return $this->valueAdditional;
    }
    
    public function getFieldType() {
        return $this->type;
    }
    
    public function getName() {
        return $this->type;
    }
    
    public function isMandatory() {
        return $this->mandatory;
    }
    
    public function isRepeatable() {
        return $this->repeatable;
    }
    
    public function hasLanguage() {
        return $this->language;
    }
    
    public function getErrorMessage($lineNr) {
        
        return $this->errorMessage;
    }
    
    public function setRegularExpression($regExp) {
        $this->errorRegularExp = $regExp;
    }
    
    public function render() {
        $view = new Zend_View();
        $view->addScriptPath('../library/Opus/Form');
        $view->field = $this;
        return $view->render('form_element.phtml');
    }
}
