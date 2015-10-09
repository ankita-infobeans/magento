<?php

class ICC_Checkout_Helper_Data extends Mage_Core_Helper_Abstract
{

	protected function _checkMemberInCart()
	{
		$items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();

		foreach ($items as $item) {
			$sku = $item->getSku();
			$product_full = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);
			if(is_object($product_full)){
			$memberAttribute = $product_full->getItemType();
			if ($memberAttribute === '534') { // member type attribute of a product is not for a guest
				return false;
			}
			
			}
		}
        return true;
	}
	
    public function allowGuestCheckout() {        
        
        $items = Mage::getSingleton('checkout/session')->getQuote()->getAllVisibleItems();        
        foreach($items as $itm) {
            $attrID = $itm->getProduct()->getAttributeSetId();
            if ($attrID == 14 || $attrID == 15) return false;  //downloadable and ecodes
        }
		
		$check = $this->_checkMemberInCart();
		if (!$check) return false; 		
		
        return true;
    }    

	public function allowRegisterOnCheckout()
	{
		return $check = $this->_checkMemberInCart();
	}
}