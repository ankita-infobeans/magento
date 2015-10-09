<?php

class ICC_Volumelicence_Block_Customer_Volumedownloads_volumes extends Mage_Core_Block_Template {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}

        /**
         * 
         * This method returns volume license collection by customer id.
         */
        public function getPremiumSubscriptions() {
		return Mage::getModel('volumelicense/volumelicense')->getCollection()->getRegisteredByCustomerId($this->getCustomer()->getId());               
	}
        
        /**
         * This method returns volume license registry collection by assign customer id.
         * 
         */
        public function getPremiumSubscriptionsRegisrty() {
                return Mage::getModel('volumelicense/registry')->getCollection()->getByUserId($this->getCustomer()->getId());
        }

        /**
         * This method return given date in (M J, Y ) format for display in frontend
         * @param type $sqlDate
         * @return date
         */
	public function formatDate1($sqlDate) {
		return date('M j, Y', strtotime($sqlDate));
	}
}