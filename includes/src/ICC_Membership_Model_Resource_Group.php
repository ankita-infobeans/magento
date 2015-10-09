<?php

class ICC_Membership_Model_Resource_Group extends Mage_Core_Model_Resource_Db_Abstract
{
	public function _construct()
    {
    	//parent::_construct();
		$this->_init('membership/group', 'id');
    }
}
