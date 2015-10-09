<?php
class ICC_Premiumaccess_Model_Mysql4_Registry extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("icc_premiumaccess/registry", "id");
    }
}