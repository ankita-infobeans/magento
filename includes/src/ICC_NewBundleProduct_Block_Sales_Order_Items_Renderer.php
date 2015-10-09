<?php
class ICC_NewBundleProduct_Block_Sales_Order_Items_Renderer extends Mage_Bundle_Block_Sales_Order_Items_Renderer{
	
	public function getValueHtml($item)
	{
		return $this->htmlEscape($item->getName());
	}
}
?> 