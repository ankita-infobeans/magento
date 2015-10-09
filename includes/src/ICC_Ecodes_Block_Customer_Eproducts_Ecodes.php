<?php

class ICC_Ecodes_Block_Customer_Eproducts_Ecodes extends Mage_Core_Block_Template {

	public function getCustomer() {
		return Mage::getSingleton('customer/session')->getCustomer();
	}

	public function getPremiumSubscriptions() {
		return Mage::getModel('ecodes/premiumsubs')->getCollection()->getRegisteredByCustomerId($this->getCustomer()->getId());
	}

	public function formatDate1($sqlDate) {
		return date('M j, Y', strtotime($sqlDate));
	}

	public function isExpiring($expriationDate) {
        $num_days = Mage::getStoreConfig('catalog/renew_expire_date/email_before_days');
		$expTime = strtotime($expriationDate);
		return ($expTime < (time() + (60*60*24* $num_days )));
	}

	public function isExpired($expirationDate,$sub=null) {
        $num_days = Mage::getStoreConfig('catalog/renew_expire_date/renewal_grace_days');
		$expTime = strtotime($expirationDate) + 86400;
		if($sub) {
			if(!$this->canRenew($sub)) {
				return (time() > $expTime);
			}
		}
		return (time() > ($expTime + (60*60*24* $num_days )));
	}
        
    public function getRenewalProductUrl($sub)
    {
    	$product_id = $sub->getProductId();
        $product = Mage::getModel('catalog/product')->load($product_id);
        $renewal_product = Mage::getModel('catalog/product')->loadByAttribute('sku', $product->getRenewSku() );
        return ( ! empty($renewal_product))?($renewal_product->getProductUrl()) : ('#');
    }

    public function canRenew($sub) {
    	$product_id = $sub->getProductId();
        $product = Mage::getModel('catalog/product')->load($product_id);
        if($product->getSubscriptionDuration() != "150") {
        	return true;
        }
        return false;
    }
}