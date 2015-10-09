<?php

class ICC_Volumelicense_Block_Customer_Users extends Mage_Customer_Block_Account_Dashboard {
	
	public function getCreateUrl() {
		return $this->getUrl('*/*/adduser');
	}

	public function getVolumeLicense() {
		return Mage::registry('current_volumelicense');
        }
        
        public function getChildOrders() {
		return Mage::registry('current_child_order');
        }
        
        
        public function getParentOrder() {
		return Mage::registry('current_parent_order');
        }
         public function getParentOrderProduct() {
		return Mage::registry('current_parent_product');
        }
        
        public function getUpdateUrl() {
		return $this->getUrl('*/*/updateuser');
	}
	
	public function getCurrentChildOrder() {
		return Mage::registry('current_order');
        }
        
        
        public function getCurrentChildOrderId() {
		return Mage::registry('current_child_order_id');
        }
        
        public function getCurrentParentOrderId() {
		return Mage::registry('parent_order_id');
        }
        
        
        public function getChildProducts($type, $itemId, $quoteId){
       if ($type == 'bundle'){
               $item = Mage::getModel('sales/quote_item')->getCollection()
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToFilter('parent_item_id', $itemId);
               return $item;
        }
    }
   
	
}
