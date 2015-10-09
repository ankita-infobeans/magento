<?php
class ICC_Volumelicense_Model_Mysql4_Volumelicense extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("volumelicense/volumelicense", "id");
    }
}