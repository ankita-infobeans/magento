<?php
/**
 * @author Amasty
 */ 
class Amasty_Acart_Model_Mysql4_Blist_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct()
    {
        $this->_init('amacart/blist');
    }
}