<?php

class ICC_TEC_Model_Mysql4_Roster extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        // this is the entity table - in the config xml the table is defined there
        $this->_init('icc_tec/roster', 'entity_id');
    }
    
}