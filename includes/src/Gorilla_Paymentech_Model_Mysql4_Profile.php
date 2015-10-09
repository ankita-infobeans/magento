<?php
class Gorilla_Paymentech_Model_Mysql4_Profile extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {
         //$this->_setResource("paymentech");
        $this->_init('paymentech/profile','id');
       
    }
    
    
    
   
}