<?php
class ICC_Volumelicense_Model_Mysql4_Links extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("volumelicense/links", "id");
    }
}