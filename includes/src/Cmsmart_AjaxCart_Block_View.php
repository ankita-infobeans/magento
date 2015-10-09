<?php

//class Cmsmart_AjaxCart_Block_View extends Mage_Core_Block_Template
class Cmsmart_AjaxCart_Block_View extends Mage_Catalog_Block_Product_View
{
    
    public function getTemplate()
    {
       return 'cmsmart/ajax/view.phtml';
    }
	
    public function getProduct() // rewrite getProduct function
    {
        if (!Mage::registry('product') && $this->getProductId()) {
            $product = Mage::getModel('catalog/product')->load($this->getProductId());
            Mage::register('product', $product);
        }
        return Mage::registry('product');
    }
	
}