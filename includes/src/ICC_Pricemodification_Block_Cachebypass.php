<?php

class ICC_Pricemodification_Block_Cachebypass extends Mage_Core_Block_Template {

    public function getCacheKeyInfo() {
        return array('BUSINESSMODEL_COVERAGE', Mage::app() -> getStore() -> getCode(), 'template' => $this -> getTemplate());
    }

    

    protected function createMessage($msg) {
        $this -> message = $msg;
    }

    public function receiveMessage() {
        if ($this -> message != '') {
            return $this -> message;
        } else {
            //$this -> createMessage('Hello World');
            return $this -> message;
        }
    }

    protected function _toHtml() {
        $html = parent::_toHtml();

        

//            $now = date('m-d-Y h:i:s A');
//            $html .= $now;
//            $html .= '<br />';
        

        return $html;
    }

}
