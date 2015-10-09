<?php

class ICC_Ecodes_Block_Customer_Form extends Mage_Core_Block_Template {

	public function getCreateUrl() {
		return $this->getUrl('*/*/createecodesaccount');
	}

	public function getUpdatePasswordUrl() {
		return $this->getUrl('*/*/updateecodespassword');
	}

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}

	public function getSession() {
		return Mage::getSingleton('customer/session');
	}
        
	public function hasLogin() {
		$customer = Mage::getSingleton('customer/session')->getCustomer();
		if($customer && $customer->getData('ecodes_master_user') && strlen($customer->getData('ecodes_master_user')))
			return true;
	
		return false;
	}
        
        public function hasPremiumSubscription() {
            $prems = Mage::getModel('ecodes/premiumsubs')->getCollection()->getRegisteredByCustomerId($this->getCustomer()->getId());
            return (bool) $prems->count();
        }
}
