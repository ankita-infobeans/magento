<?php
class ICC_NewBundleProduct_Block_Catalog_Product_View_Type_Bundle_Option extends Mage_Bundle_Block_Catalog_Product_View_Type_Bundle_Option { 
	public function getProduct() {
		Mage::log("sdfsdfsdf", null,'abcdtest.log');
		if (Mage::registry ( 'grouped-product-child-product' )) {
			$this->setData ( 'product', Mage::registry ( 'grouped-product-child-product' ) );
		}
		
		if (! $this->hasData ( 'product' )) {
			$this->setData ( 'product', Mage::registry ( 'current_product' ) );
		}
		return $this->getData ( 'product' );
	}
	
	/**
	 * Get title price for selection product
	 *
	 * @param Mage_Catalog_Model_Product $_selection        	
	 * @param bool $includeContainer        	
	 * @return string
	 */
	public function getSelectionTitlePrice($_selection, $includeContainer = true) {
		$price = $this->getProduct ()->getPriceModel ()->getSelectionPreFinalPrice ( $this->getProduct (), $_selection, 1, Mage::getSingleton ( 'customer/session' )->getCustomerGroupId () );
		$this->setFormatProduct ( $_selection );
		$priceTitle = $_selection->getName ();
		$priceTitle .= ' &nbsp; ' . ($includeContainer ? '<span class="price-notice">' : '') . '+' . $this->formatPriceString ( $price, $includeContainer ) . ($includeContainer ? '</span>' : '');
		return $priceTitle;
	}
}
?>