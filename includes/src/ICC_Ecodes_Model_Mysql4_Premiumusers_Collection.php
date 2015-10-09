<?php
class ICC_Ecodes_Model_Mysql4_Premiumusers_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        $this->_init('ecodes/premiumusers', 'id');
    }
	
	public function getAll() {
		$this->getSelect();		
		return $this;
	}
	
	public function getByUsername($username) {
		$this->getSelect()->where("user = '" . $username . "'");		
		return $this->getFirstItem();
	}

	public function getBySubscriptionId($sid) {
		$this->getSelect()->where("id in (select user_id from ecodes_premium_sub_users where subs_id = " . (int)$sid .")");
		return $this;
	}
}