<?php
class Gorilla_Paymentech_Model_Mysql4_Profile_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('paymentech/profile','id');
    }
}