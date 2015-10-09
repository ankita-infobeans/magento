<?php

class ICC_Ecodes_Block_Customer_Users extends Mage_Customer_Block_Account_Dashboard {
	
	public function getCreateUrl() {
		return $this->getUrl('*/*/adduser');
	}

	public function getSubscription() {
		return Mage::registry('current_subscription');
	}
}