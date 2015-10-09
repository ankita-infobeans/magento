<?php

class ICC_Ecodes_Block_Customer_UpdatePassword extends Mage_Customer_Block_Account_Dashboard {
	
	public function getSaveUrl() {
		return $this->getUrl('*/*/saveupdateuserpassword');
	}

	public function getUser() {
		return Mage::registry('current_ecodesuser');
	}

	public function getSubscription() {
		return Mage::registry('current_subscription');
	}
}