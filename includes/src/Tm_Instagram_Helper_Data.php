<?php 

class Tm_Instagram_Helper_Data extends Mage_Core_Helper_Data
{
	public function productTag()
	{
		$product_id 			= Mage::registry('current_product')->getId();
		$product 				= Mage::getModel('catalog/product')->load($product_id);
		$product_name 			= strtolower(str_replace(' ', '', $product->getName()));
		$product_sku 			= $product->getSku();
		$store_title 			= strtolower(str_replace(' ', '', Mage::getStoreConfig('design/head/default_title', Mage::app()->getStore())));
		$sample_tag 			= Mage::getStoreConfig('instagram/instagram_tag/sample_tag', Mage::app()->getStore());
		$product_tag_option 	= explode(',', Mage::getStoreConfig('instagram/instagram_tag/product_tag', Mage::app()->getStore()));

		if (gettype($product_tag_option) == 'string') {
			$product_tag = $sample_tag;
		} else {
			if (in_array('store_title', $product_tag_option)) {
			$product_tag .= $store_title;
			} 
			if (in_array('product_name', $product_tag_option)){
				$product_tag .= $product_name;
			} 
			if (in_array('product_sku', $product_tag_option)){
				$product_tag .= $product_sku;
			} 
		}
		
		return $product_tag;
	}
}