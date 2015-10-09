<?php
    class ICC_Premiumaccess_Model_Mysql4_Registry_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
    {

        public function _construct(){
                $this->_init("icc_premiumaccess/registry","id");
        }
        /**
         * this method returns premium access registry collection by subscription id.
         * @param type $sid
         * @return \ICC_Premiumaccess_Model_Mysql4_Registry_Collection
         */
        public function getBySubscriptionId($sid) {
		$this->getSelect()->where("subscription_id = " . (int)$sid);
		return $this;
	}
        /**
         * This method used to return premium access registry collection by subscription id and user id.
         * @param type $sid
         * @param type $uid
         * @return \ICC_Premiumaccess_Model_Mysql4_Registry_Collection
         */
        public function getBySubscriptionAndUserId($sid, $uid) {
		$this->getSelect()->where('subscription_id = ' . (int)$sid. ' AND assign_customer_id = ' . (int)$uid.' AND (status = 0 OR status = 1)');
		return $this;
	}
        /**
         * This method used to return premium access registry collection by subscription id and user email id.
         * @param type $sid
         * @param type $email
         * @return \ICC_Premiumaccess_Model_Mysql4_Registry_Collection
         */
        public function getBySubscriptionAndUserEmail($sid, $email) {
		$this->getSelect()->where('subscription_id = ' . (int)$sid. ' AND assign_customer_email = "'.$email.'"  AND (status = 0 OR status = 1)');
		return $this;
	}
        
        /**
         *  This method used to return premium access registry collection by user id.
         * @param type $uid
         * @return \ICC_Premiumaccess_Model_Mysql4_Registry_Collection
         */
        public function getByUserId($uid) {
		$this->getSelect()->where("assign_customer_id = " . (int)$uid. ' and status = "1"');
		return $this;
	}
        
        /**
         *  This method used to return premium access registry collection by user email id.
         * @param type $email
         * @return \ICC_Premiumaccess_Model_Mysql4_Registry_Collection
         */
        public function getBySharedEmail($email){
                $this->getSelect()->where('assign_customer_email = "' . $email .'" AND status = "0"');
		return $this;
        }
        
        /**
         * This method used to validate premium access regisrty collection by subscription id and assign customer id.
         * @param type $sid
         * @param type $uid
         * @return \ICC_Premiumaccess_Model_Mysql4_Registry_Collection
         */
        public function isRegistred($sid,$uid)
        {
            $this->getSelect()->where('subscription_id = ' . (int)$sid." AND assign_customer_id = " . (int)$uid. ' and status = "1"');
            return $this;
        }
                
    }
	 
