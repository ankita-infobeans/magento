<?php

class ICC_TEC_Model_Mysql4_Roster_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('icc_tec/roster');
    }   
}