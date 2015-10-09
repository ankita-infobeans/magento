<?php

class ICC_Quickorder_Model_Mysql4_Quickorder extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {   
        $this->_init('quickorder/quickorder', 'quickorder_id');
    }
}