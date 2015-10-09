<?php

class ICC_Volumelicense_Block_Customer_Users extends Mage_Customer_Block_Account_Dashboard {
	
	public function getCreateUrl() {
		return $this->getUrl('*/*/adduser');
	}

	public function getVolumeLicense() {
		return Mage::registry('current_volumelicense');
        }
        
        public function getRegistry() {
		return Mage::registry('current_registry');
        }
        
        public function getUpdateUrl() {
		return $this->getUrl('*/*/updateuser');
	}
}
