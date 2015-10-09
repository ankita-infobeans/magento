<?php
class ICC_OptRemainder_Model_Mysql4_Optremainder extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init("optremainder/optremainder", "id");
    }
}