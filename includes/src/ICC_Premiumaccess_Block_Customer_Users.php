<?php

class ICC_Premiumaccess_Block_Customer_Users extends Mage_Customer_Block_Account_Dashboard {
	
	public function getCreateUrl() {
		return $this->getUrl('*/*/adduser');
	}
        
        public function getChildOrders() {
		return Mage::registry('premium_current_child_order');
        }
        
        
        public function getParentOrder() {
		return Mage::registry('premium_current_parent_order');
        }
         public function getParentOrderProduct() {
		return Mage::registry('premium_current_parent_product');
        }
        
        public function getUpdateUrl() {
		return $this->getUrl('*/*/updateuser');
	}
	
	public function getCurrentChildOrder() {
		return Mage::registry('premium_current_order');
        }
        
        
        public function getCurrentChildOrderId() {
		return Mage::registry('premium_current_child_order_id');
        }
        
        public function getCurrentParentOrderId() {
		return Mage::registry('premium_parent_order_id');
        }
        
        

   
	
}
