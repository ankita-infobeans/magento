<?php

class ICC_Membership_Model_Resource_Group_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract      
{
	public function _construct()
    {
    	//parent::__construct();
    	$this->_init('membership/group');
    }
}
