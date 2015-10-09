<?php
class ICC_Ecodes_Model_Mysql4_Premiumsubusers_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract {

    public function _construct() {
        $this->_init('ecodes/premiumsubusers', 'id');
    }

	public function getBySubscriptionAndUserId($sid, $uid) {
		$this->getSelect()->where('subs_id = ' . (int)$sid. ' AND user_id = ' . (int)$uid);
		return $this->getFirstItem();
	}
        
    public function delete()
    {
        foreach($this as $psu_row)
        {
            $psu_row->delete();
        }
    }
}