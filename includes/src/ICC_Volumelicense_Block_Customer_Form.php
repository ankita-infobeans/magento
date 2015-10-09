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
}
