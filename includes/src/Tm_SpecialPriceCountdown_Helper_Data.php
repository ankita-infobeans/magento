<?php 

class Tm_SpecialPriceCountdown_Helper_Data extends Mage_Core_Helper_Data
{
	function countdownTime()
	{
		$productModel = Mage::getModel('catalog/product');
		$product = $productModel->load(Mage::registry('current_product')->getId());
		$special_counter_time = strtotime($product->getSpecialToDate()) - strtotime("now");

		return $special_counter_time;
	}
}